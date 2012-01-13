<?php

require "init.php";

class YouTubeCacher
{
        public $original_url = null;
        public $parsed_url = null;
        public $client_ip = null;
        public $cache_request = null;   // Youtube request
        public $cache_filename = null;  // Path+Filename to the storage
        public $cache_storage = null;  // Storage
        public $cache_header_size = null; // Size of HTTP response headers saved in the cache file.
        public $temp_cache_filename = null; // Temporary cache filename
        public $client_request_headers = array();   // Headers from client
        public $server_reply_headers = array();     // Reply headers from server
        public $server_fp = null;   // Server pointer
        public $cache_fp = null;    // Cache file pointer
        private $db = null;     // DB Handler
        private $settings = array();     // All settings in array
        private $chunksize = 131072;    // Size of the chunk for both file and internet fread
        private $retry_limit=3; // How much times trying to connect to server
        private $miss=0;    // How much misses (get from internet)
        private $hit=0;     // How much hits (files on disk)
        private $localtraffic=0;        // How much traffic is send to local
        private $internettraffic=0;     // How much traffic is get from internet
        private $connect_time=0;        // fopen for internet, connection time needed to connect to server
        private $file_access_time=0;    // fopen benchmark, high times means too much files/too many fragments
        private $troughput_internet=0;      // Internet troughtput (serving from internet)
        private $troughput_local=0;     // Local troughput (serving local files)
        private $already_downloading=false; // File is already downloading, do not download again
        private $nocache=false; // File caching is disabled
        private $known_formats=array("FLVK","ftypmp42isom");
        private $dretry=0;  // data transfer retry
        private $tsize=0;   // transfer size so far
        private $tcount=0;  // packet count

        //
        // $allowed_request_headers
        //  Browser originated request headers sent to YouTube.
        //
        // $custom_request_headers
        //  Custom headers sent to YouTube.
        //
        // $cached_headers
        //  List of YouTube response headers that are cached and sent to clients.
        //    The 'Server' header is overwritten by Apache and may be removed by Squid.
        //    Date related headers are generated dynamically by 'send_dynamic_headers_to_client()'.
        //
        private $allowed_request_headers=array('Host', 'Referer', 'Range', 'Cookie');
        private $custom_request_headers=array('User-Agent' => 'YouTube Cache', 'X-Cache-Admin' => "admin@gromnet.net");
        private $cached_headers=array('Content-Type', 'Content-Length', 'Last-Modified', 'Server');

        private function close_temporary_transfer($request) {
            $this->db->sql("DELETE FROM `temporary` WHERE `request`='{$request}'");
            }
        
        private function temporary_transfer($request,$filename,$accessed,$size,$progress,$packet_count,$client_ip) {
            $pid=getmypid();
            $this->db->sql("REPLACE INTO `temporary` (request,filename,accessed,size,progress,packet_count,ip,pid) VALUES ('{$request}','{$filename}',NOW(),'{$size}','{$progress}','{$packet_count}','{$client_ip}','{$pid}')");
        }
        
        private function add_to_stats() {
            $q=" `miss`=`miss`+{$this->miss},";
            $q.=" `hit`=`hit`+{$this->hit},";
            $q.=" `localtraffic`=`localtraffic`+{$this->localtraffic},";
            $q.=" `internettraffic`=`internettraffic`+{$this->internettraffic},";
            if  ($this->connect_time>0)
                $q.=" `connect_time`=ROUND((`connect_time`+{$this->connect_time})/2),";
            if  ($this->file_access_time>0)
                $q.=" `file_access_time`=ROUND((`file_access_time`+{$this->file_access_time})/2),";
            if  ($this->troughput_internet>0)
                $q.=" `troughput_internet`=ROUND((`troughput_internet`+{$this->troughput_internet})/2),";
            if  ($this->troughput_local>0)
                $q.=" `troughput_local`=ROUND((`troughput_local`+{$this->troughput_local})/2),";
            if ($q[strlen($q)-1]==",")
                $q[strlen($q)-1]=";";
            $this->db->sql("UPDATE `stats` SET ".$q);
        }
        
        private function add_video($size) {
            $storage=$this->cache_filename;
            $path=$this->cache_storage;
            
            $hs = array();
            $h = $this->server_reply_headers;
            foreach ($this->cached_headers as $n) {
                if (isset($h[$n])) {
                    $hs []= "$n: {$h[$n]}";
                    $this->log(0,__FUNCTION__,"Reply header > cache file [$n: {$h[$n]}]");
                }
            }
            $headers_serialized=serialize($hs);
            $row=array("request"=>$this->cache_request,"path"=>$path,"storage"=>$storage,"ip"=>$this->client_ip,"added"=>"NOW()","enabled"=>"1","size"=>$size,"accessed"=>"NOW()","visits"=>1,"reply_headers"=>$headers_serialized);
            $this->db->insert_row("videos",$row);
            $this->miss=1;
            $this->internettraffic=$size;
        }
        
        private function add_visit($id,$fsize) {
            $request=$this->cache_request;
            $row=array("ip"=>$this->client_ip,"visit_date"=>"NOW()","request"=>$this->original_url,"video_id"=>$id);
            $this->db->insert_row("visits",$row);
            $this->db->sql("UPDATE `videos` SET `visits`=`visits`+1 WHERE `request`='{$request}'");
            $this->hit=1;
            $this->localtraffic=$fsize;
        }
        
        private function log($severity,$facility,$message) {
            if ((int)$severity<$this->settings["debug"] || !$this->db)
                return;
            $message=str_replace("\n"," ",$message);
            $pid=getmypid();
            $row=array("severity"=>$severity,"facility"=>$facility,"message"=>$message,"debug_date"=>"NOW()","pid"=>$pid);
            $this->db->insert_row("debug",$row);
        }

        private function logdie($severity,$facility,$message) {
            $this->log($severity,$facility,$message);
            $this->stop_caching();
            require "deinit.php";
            die;
        }

        public function run($db,$set)
        {
                if (!$db)
                    fatal_error("Database not instanced!");
                $this->settings=$set;
                $this->db=$db;
                $this->get_original_url();     // unscramble original url
                $this->produce_cache_filename();    // generate cache file name
                $this->get_client_request_headers();
                $this->connect_to_server("0:0");
                $this->get_server_reply_headers();
                if ($video=$this->exists_in_cache()) {    // video exists in cache?
                        $this->fake_get_chunk();
                        if ($this->send_cached_file($video)) { // If sending is successfull add to stats and shut down
                            $this->add_to_stats();
                            require "deinit.php";   // Finish the script if sending the cache is successful
                            }
                }
                $this->send_reply_headers_to_client();
                if ($this->cache_filename && $this->is_cachable() && !$this->already_downloading && !$this->nocache)
                        $this->open_cache_file();
                    else
                        $this->log(1,__FUNCTION__,"Unable to cache: cache filename [{$this->cache_filename}], cachable [".(int)$this->is_cachable()."], already downloading [".(int)$this->already_downloading."], do not cache [".(int)$this->nocache."]");
                $this->transfer_file();
        }

        // fake reading file
        public function fake_get_chunk() {
        if (!feof($this->server_fp))
            $data = fread($this->server_fp, $this->chunksize);
        }
        
        public function exists_in_cache() {
                if ($this->parsed_url['id']=="" || $this->parsed_url['itag']=="")
                    return FALSE;
                $temp=$this->db->get_row('temporary',array('request'=>'id='.safe_filename($this->parsed_url['id']).'.itag='.safe_filename($this->parsed_url['itag'])));
                if (!empty($temp))
                    {
                        // Temporary table is having this request already
                        // So the file is already beign downloaded from internet
                        // We should forbid duplicated files on both disk and in DB
                        // Set the already downloading to true
                        $this->log(1,__FUNCTION__,"Rejected: already downloading this file");
                        $this->already_downloading=true;
                        return FALSE;
                    } else {
                        //
                        // All values in $p are provided by the user.
                        // Do not use them directly in 'fopen()'.
                        // Find request in DB
                        $video=$this->db->get_row("videos",array("request"=>$this->cache_request,"enabled"=>"1"));
                        // Is video exists in cache?
                        if (empty($video)) {
                            $this->log(1,__FUNCTION__,"Cache file with request {$this->cache_request} not found in db!");
                            $this->already_downloading=false;
                            return FALSE;
                        } else
                        // Video found in db but not on disk, should not happend!
                        if (!file_exists($video["storage"])) {
                            $this->log(2,__FUNCTION__,"Error: Cache file {$video['storage']} does not exist, deleted from DB!");
                            $this->db->sql("DELETE FROM `video` WHERE `id`='{$video["id"]}'");
                            $this->already_downloading=false;
                            return FALSE;
                        } else {
                        // Everything fine, set variables
                        $this->cache_filename=$video["storage"];
                        $this->cache_storage=$video["path"];
                        $this->already_downloading=false;
                        $this->log(1,__FUNCTION__,"Found cache file id {$id} filename {$this->cache_filename} for request {$this->cache_request}!");
                        // Dump variables to log
                        foreach (array('original_url', 'cache_filename', 'temp_cache_filename') as $n) {
                            $this->log(0,__FUNCTION__,"$n = [{$this->$n}]");
                            }
                        return $video;
                        }
                }
        }

        public function get_original_url()
        {
                if (!isset($_GET['url']))
                        $this->logdie(2,__FUNCTION__,"Proxy URL rewriter error: url GET parameter not found.");

                $this->original_url = base64_decode($_GET['url'], TRUE);
                if (!is_string($this->original_url))
                        $this->logdie(2,__FUNCTION__,"Proxy URL rewriter error: url GET parameter is invalidly base64 encoded.");

                $this->log(1,__FUNCTION__,"Accessed URL {$this->original_url}");

                //
                // Get the client IP address.
                //
                if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                        $this->log(2,__FUNCTION__,"Proxy configuration error: X-Forwarded-For header not found");
                        return;
                }

                // get forwarded server ip, can be multiple ones if there is multiple proxies
                $this->client_ip = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
                if (strpos($this->client_ip,", ")) {
                    $cips_exploded=explode(", ",$this->client_ip);
                    $this->client_ip=$cips_exploded[count($cips_exploded)-1];   // get last ip in the array
                }

                if (preg_match('/^[0-9a-f:.]+$/', $this->client_ip) === 0) {
                        $this->log(2,__FUNCTION__,"Proxy error: invalid X-Forwarded-For header value: [{$this->client_ip}]");
                        return;
                }

                //
                // Parse the URL and make sure it belongs to a YouTube video.
                //
                $url = parse_url($this->original_url);
                if (!is_array($url) || !is_string($url['query'])) {
                        $this->logdie(2,__FUNCTION__,"Invalid URL");
                        return;
                }
                parse_str($url['query'], $this->parsed_url);
                if (!is_array($this->parsed_url)) {
                        $this->log(2,__FUNCTION__,"Invalid query string: [{$url['query']}]");
                        unset($this->cache_request);
                        return;
                }
                foreach (array('sver', 'itag', 'id') as $n) {
                        if (!is_string($this->parsed_url[$n]) || strlen($this->parsed_url[$n]) === 0) {
                                $this->log(2,__FUNCTION__,"Query parameter [$n] not found or empty");
                                unset($this->cache_request);
                                return;
                        }
                }
                $this->cache_request='id='.safe_filename($this->parsed_url['id']).'.itag='.safe_filename($this->parsed_url['itag']);
        }

        public function produce_cache_filename()
        {
                        if ($this->cache_request=="") {
                            $this->log(2,__FUNCTION__,"Uncachable: empty request given, original URL ({$this->original_url})");
                            return FALSE;
                            }
                        $this->cache_storage=choose_storage();
                        for ($c=1;$c<10;$c++) {
                            if (isset($this->settings["storage{$c}"]))
                                $storage[$c]=$this->settings["storage{$c}"];
                        }
                        $scount=count($storage);
                        $storageno=rand(1,$scount);
                        $this->cache_storage=$storage[$storageno];
                        
                        $subdir1=substr($this->cache_request,3,1);
                        $subdir2=substr($this->cache_request,4,1);
                        $subdir=$subdir1."/".$subdir1;
                        $this->cache_filename=$this->cache_storage."/{$subdir}/".$this->cache_request;
                        $this->temp_cache_filename = "{$this->cache_filename}." . uniqid(mt_rand() . '_', TRUE) . ".{$this->client_ip}.tmp";
                        $this->log(0,__FUNCTION__,"Cache: cache filename [{$this->cache_filename}], temp [{$this->temp_cache_filename}]");                        
        }

        public function send_dynamic_headers_to_client()
        {
                $now = time();
                $maxage = 365 * 24 * 3600;

                //
                // Allow the browser to cache the file.
                //
                foreach (array(
                        'Date: ' . gmdate('D, d M Y H:i:s') . ' GMT',
                        'Expires: ' . gmdate('D, d M Y H:i:s', $now + $maxage) . ' GMT',
                        'Cache-Control: public, max-age=' . $maxage
                ) as $h) {
                        header($h);
                        $this->log(0,__FUNCTION__,"Custom header > client: [$h]");
                }
        }

        // Deletes file from cache
        public function delete_from_cache($id,$request,$filename) {
            if (!empty($id))
                $find["id"]=(int)$id;
            if (!empty($request))
                $find["request"]=$request;
            if (!empty($filename))
                $find["storage"]=$filename;
            $video=$this->db->get_row("videos",$find);
            unlink($video["storage"]);
            $this->db->delete_row("videos",array("id"=>$video["id"]));
        }
        
        //
        // Send the cached file to the user's browser.
        // YouTube's servers are not used at all in this case.
        //
        // The first lines of the cache file are the static headers, separated 
        // from the file contents by an empty line.
        // Headers related to expiration time are generated dynamically.
        //
        public function send_cached_file($video)
        {
                $id=$video["id"];

                //
                // Open cache file.
                //
                bench("start");
                if (($fp = fopen($this->cache_filename, 'rb')) === FALSE) {
                        $this->log(2,__FUNCTION__,"Cannot open cache file: [{$this->cache_filename}]");
                        return FALSE;
                }
                // Log it.
                $this->log(1,__FUNCTION__,"Cache file opened for reading");
                // Insert visit into db.
                $this->add_visit($id,filesize($this->cache_filename));
                $this->file_access_time=bench();
                
                //
                // Read headers using fgets
                //
                $hs=unserialize($video["reply_headers"]);
/*
                $hs = array();
                while (!feof($fp)) {
                        if (($ln = fgets($fp)) === FALSE)
                                $this->logdie(2,__FUNCTION__,"Cannot read cache file: [{$this->cache_filename}]");
                        else if (($ln = rtrim($ln)) == '')
                                break;
                        else if (!preg_match('/^([^:]+): *(.*)$/', $ln, $mo))
                                $this->logdie(2,__FUNCTION__,"Invalid cached header in [{$this->cache_filename}]: [{$ln}]");
                        else
                                $hs[$mo[1]] = $mo[2];
                }
*/
                // Range request
                if (isset($this->client_request_headers['Range'])) {
                        $range = $this->client_request_headers['Range'];
                        if (!preg_match('/bytes[=\s]+([0-9]+)/', $range, $mo))
                                $this->log(2,__FUNCTION__,"Unsupported Range header value: [{$range}]");
                        else {
                                $firstbyte = $mo[1];
                                $size = $hs['Content-Length'];
                                $lastbyte = $size - $firstbyte - 1;
                                $hs['Content-Range'] = "bytes $firstbyte-$lastbyte/$size";
                                $hs['Content-Length'] -= $firstbyte;
                                header('HTTP/1.0 206 Partial Content');
                                if (fseek($fp, $firstbyte, SEEK_CUR))
                                        $this->log(2,__FUNCTION__,"Cannot seek to position $firstbyte: [{$this->cache_filename}]");
                        }
                }   // range
                // Set headers
                foreach ($hs as $n => $v) {
                        header("$n: $v");
                        $this->log(0,__FUNCTION__,"Cached header > client: [$n: $v]");
                }
                $this->send_dynamic_headers_to_client();

                //
                // Send content.
                //
                // 'fpassthru($fp)' seems to attempt to mmap the file, and hits the PHP memory limit.
                // As a workaround, use a 'feof / fread / echo' loop.
                //

                $transferred=0;$tcount=0;
                bench("start");
                while (!feof($fp)) {
                        if (($data = fread($fp, $this->chunksize)) === FALSE) {
                            $this->log(2,__FUNCTION__,"Cannot read cache file: [{$this->cache_filename}]");
                            fclose($fp);
                            return FALSE;
                        } else {
                            $transferred+=strlen($data);$tcount++;
                            if ($tcount==1) //first packet
                                if (substr($data,0,4)==chr(0x12).chr(00).chr(03).chr(0x4b)) //header missing, try to fix it
                                    $data="FLV".chr(0x01).chr(0x05).chr(00).chr(00).chr(00).chr(0x09).chr(00).chr(00).chr(00).chr(00).$data;
                            echo $data;
                        }
                }
                fclose($fp);
                $transfer_time=bench();
                if ($transfer_time<1) $transfer_time=1;
                $bytes_per_sec=ROUND($transferred/ROUND($transfer_time/1000));
                $this->troughput_local=$bytes_per_sec;
                $this->log(1,__FUNCTION__,"Served request {$this->cache_request} from cache, {$transferred} bytes transferred");
                return true;
        }

        public function get_client_request_headers()
        {
                foreach ($_SERVER as $n => $v) {
                        $this->log(0,__FUNCTION__,"\$_SERVER[$n] => [$v]");
                        if (strncmp($n, 'HTTP_', 5) === 0) {
                                // HTTP_USER_AGENT > USER_AGENT > USER AGENT > user agent > User Agent > User-Agent
                                $pn = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($n, 5)))));
                                // fix unneded range request (chrome only?)
                                // client_request_headers[Range] => [bytes=0-]
//                                if (($pn=="Range" && $v=="bytes=0-") || $pn=="Via" || $pn=="X-Forwarded-For") {
//                                    $this->log(0,__FUNCTION__,"skipped header [$pn] => [$v]"); 
//                                    unset($_SERVER[$n]);
//                                    } else {
                                    $this->client_request_headers[$pn] = $v;
                                    $this->log(0,__FUNCTION__,"copy client header [$pn] => [$v]");
  //                                  }
                        }
                }
        }

        public function connect_to_server($bindto="0:0")
        {
                //
                // Prepare the request headers to be sent to YouTube.
                //
                $hs = array();
                foreach ($this->client_request_headers as $n => $v) {
                        if (in_array($n, $this->allowed_request_headers)) {
                                $hs []= "$n: $v";
                                $this->log(0,__FUNCTION__,"Request header > server: [$n: $v]");
                        }
                }
                foreach ($this->custom_request_headers as $n => $v) {
                        $hs []= "$n: $v";
                        $this->log(0,__FUNCTION__,"Custom header > server: [$n: $v]");
                }

                //
                // Connect to YouTube and send the HTTP request.
                //
                $c = stream_context_create(
                        array(
                                'socket' => array(
                                        'bindto' => $bindto,
                                ),
                                'http' => array(
                                        'method' => 'GET',
                                        'header' => implode("\r\n", $hs),
                                        'max_redirects' => 100,
                                ),
                        )
                );
                $retry_counter=1;
                bench("start");
                while (($this->server_fp = fopen($this->original_url, 'rb', FALSE, $c)) === FALSE) {
                    $this->log(1,__FUNCTION__,"Cannot open URL, retry #{$retry_counter} URL:".$this->original_url);
                    $retry_counter++;
                    if ($retry_counter>$this->retry_limit)
                        $this->logdie(2,__FUNCTION__,"Reached maximum of retires ({$retry_counter}), killing script URL: ".$this->original_url);
                    };
                $this->connect_time=bench();
                stream_set_timeout($this->server_fp, 10);
        }

        public function get_server_reply_headers()
        {
                //
                // If a redirection happens, two sets of headers will be present.
                // Keep the last one only.
                //
                $m = stream_get_meta_data($this->server_fp);
                foreach ($m['wrapper_data'] as $h) {
                        if (preg_match('/^([^:]+): *(.+)[ \r\n]*$/', $h, $mo) !== 0) {
                                // USER-AGENT > USER AGENT > user agent > User Agent > User-Agent
                                $pn = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $mo[1]))));
                                $this->server_reply_headers[$pn] = $mo[2];
                                $this->log(0,__FUNCTION__,"server_reply_headers[$pn] = [{$mo[2]}]");
                        }
                        else if (!strncasecmp($h, 'HTTP/', 5)) {
                                $this->server_reply_headers = array();
                                $this->log(0,__FUNCTION__,"Server reply [$h]");
                        }
                        else {
                                $this->log(2,__FUNCTION__,"Program error: Unexpected value in stream_get_meta_data[wrapper_data]: [$h]");
                        }
                }
        }

        public function send_reply_headers_to_client()
        {
                $hs = $this->server_reply_headers;

                if (isset($hs['Content-Range'])) {
                        $h='HTTP/1.0 206 Partial Content';
                        header($h);
                        $this->log(0,__FUNCTION__,"Reply header > client: [{$h}]");
                        $h='Content-Range: ' . $hs['Content-Range'];
                        header($h);
                        $this->log(0,__FUNCTION__,"Reply header > client: [{$h}]");
                        $h='Accept-Ranges: bytes';
                        header($h);
                }
                
                foreach ($this->server_reply_headers as $n => $v) {
                        if (in_array($n, $this->cached_headers)) {
                                header("$n: $v");
                                $this->log(0,__FUNCTION__,"Reply header > client [{$n}: {$v}]");
                        }
                }
                $this->send_dynamic_headers_to_client();
        }

        //
        // Only video files should be cached.
        // Do not cache files of unknown type.
        //
        // The Content-Length header must be present or there will be no way
        // of knowing whether the download completed successfully.
        //
        public function is_cachable()
        {
                $h = $this->server_reply_headers;

                if (isset($h['Content-Range']) && substr($h['Content-Range'],0,strlen("bytes 0-"))!="bytes 0-") {
                        $this->log(1,__FUNCTION__,"Uncachable: Content-Range header is present: [{$h['Content-Range']}].");
                        return FALSE;
                }

                if (!isset($h['Content-Type'])) {
                        $this->log(1,__FUNCTION__,"Uncachable: No Content-Type header.");
                        return FALSE;
                }
                else if (strncasecmp($h['Content-Type'], 'video/', 6)) {
                        $this->log(1,__FUNCTION__,"Uncachable: Content-Type is not video: [{$h['Content-Type']}].");
                        return FALSE;
                }

                if (!isset($h['Content-Length'])) {
                        $this->log(1,__FUNCTION__,"Uncachable: No Content-Length header.");
                        return FALSE;
                }

                if ($this->cache_request=="") {
                        $this->log(2,__FUNCTION__,"EMPTY request, original url [{$this->original_url}]");
                        return FALSE;
                }
                
                if (isset($this->parsed_url['begin']) && (int)$this->parsed_url['begin']>0) {
                        //
                        // The user is not downloading the whole video, but seeking within it.
                        // TODO How to deal with this?
                        //      Maybe nginx's FLV module could help.
                        //
                        $this->log(1,__FUNCTION__,"Uncachable: begin is set: [{$this->parsed_url['begin']}]");
                        return FALSE;
                }
                
                if ($this->parsed_url['sver'] != '3') {
                        //
                        // Stream Version?
                        //
                        // All requests seem to have this field set to the number 3.
                        // If this ever changes, we should look at the new requests to make
                        // sure that they are still compatible with this script.
                        //
                        $this->log(2,__FUNCTION__,"Uncachable: sver is not 3: [{$this->parsed_url['sver']}]");
                        return FALSE;
                }
                
                return TRUE;
        }

        public function open_cache_file()
        {       
                if (($fp = fopen($this->temp_cache_filename, 'xb')) === FALSE)
                        $this->log(2,__FUNCTION__,"Cannot open temp cache file: [{$this->temp_cache_filename}]");
                else {
                        register_shutdown_function(array($this, 'close_cache_file'));
                        $this->cache_fp = $fp;
                        $this->log(1,__FUNCTION__,"Temporary cache file [{$this->temp_cache_filename}] opened for writing");
                }
        }

        public function write_reply_headers_to_cache_file()
        {
                if (!$this->cache_fp) return;

                $hs = array();
                $h = $this->server_reply_headers;
                foreach ($this->cached_headers as $n) {
                        if (isset($h[$n])) {
                                $hs []= "$n: {$h[$n]}";
                                $this->log(0,__FUNCTION__,"Reply header > cache file [$n: {$h[$n]}]");
                        }
                }
                $hs []= "\n"; // End with empty line.

                if (fwrite($this->cache_fp, implode("\n", $hs)) === FALSE) {
                        $this->log(2,__FUNCTION__,"Cannot write cache file: [{$this->cache_filename}]");
                        $this->stop_caching();
                        return;
                }

                $this->cache_header_size = ftell($this->cache_fp);
        }
        
        public function transfer_file()
        {
                $this->log(1,__FUNCTION__,"Beginning to transfer file content from the Internet");
                
                $this->dretry=0;$this->tsize=0;$this->tcount=0;
                bench("start");
                while (!feof($this->server_fp)) {
                        while (($data = fread($this->server_fp, $this->chunksize)) === FALSE) {
                                $this->dretry++;
                                $this->log(1,__FUNCTION__,"Cannot read URL request {$this->cache_request}, retry #{$dretry} ".$this->original_url);
                                $info = stream_get_meta_data($this->server_fp);
                                $info = implode(";",$info);
                                if ($this->dretry>$this->retry_limit)
                                    $this->logdie(2,__FUNCTION__,"Cannot read URL, metadata: ".$info);
                        }
                        $datalen=strlen($data);
                        $this->tsize+=$datalen;$this->tcount++;
                        
                        if ($this->tcount==1) { //first packet
                            $header=substr($data,0,20);
                            if (substr($header,0,4)==chr(0x12).chr(00).chr(03).chr(0x4b)) { //header missing, try to fix it
                                $data="FLV".chr(0x01).chr(0x05).chr(0x00).chr(0x00).chr(0x00).chr(0x09).chr(0x00).chr(0x00).chr(0x00).chr(0x00).$data;
                                $this->log(1,__FUNCTION__,"Fixing header:");
                                }
                            $header_printable=preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                            if (!in_array($header_printable,$this->known_formats))
                                $this->logdie(2,__FUNCTION__,"Invalid header for request {$this->cache_request}");
                        }
                        
                        // print data to client
                        echo $data;
                        
                        // hopefully remove stalled transfers
                        if (empty($data)) {
                            $this->logdie(2,__FUNCTION__,"Empty content reached at pos [{$this->tsize}] packet [{$this->tcount}]");
                        }
                            
                        
                        // To cache file, if cache file pointer is ok
                        if ($this->cache_fp) {
                                if (fwrite($this->cache_fp, $data) === FALSE) {
                                        $this->log(2,__FUNCTION__,"Cannot write cache file: [{$this->cache_filename}]");
                                        $this->stop_caching();
                                        return false;
                                }
                        if ($this->tcount/10==ROUND($this->tcount/10))  // update temp transfers every 10th packet, prevents query floods
                        $this->temporary_transfer($this->cache_request,$this->temp_cache_filename,"NOW()",$this->server_reply_headers['Content-Length'],$this->tsize,$this->tcount,$this->client_ip);
                        }   //if ($this->cache_fp) {
                }   //while (!feof($this->server_fp)) {
                $transfer_time=bench();
                if ($this->cache_fp)
                    $this->close_temporary_transfer($this->cache_request);
                $this->log(1,__FUNCTION__,"File content for request {$this->cache_request} fully transferred, {$this->tsize} bytes");
                if ($transfer_time<0) $transfer_time=1;
                $this->troughput_internet=ROUND($tsize/($transfer_time/1000));
        }

        //
        // Close 'cache_fp' if still opened.
        // Delete the temporary cache file.
        //
        public function stop_caching()
        {
                $this->log(1,__FUNCTION__,"Stopping file cache, deleting file pointer and temp file");
                if ($this->cache_fp) {
                        fclose($this->cache_fp);
                        $this->cache_fp = null;
                }
        
                if (file_exists($this->temp_cache_filename)) {
                        if (unlink($this->temp_cache_filename) === FALSE)
                                $this->log(2,__FUNCTION__,"Cannot delete temporary cache file: [{$this->temp_cache_filename}]");
                        else
                                $this->log(1,__FUNCTION__,"Temporary cache file deleted");
                }
        
                $this->cache_filename = $this->temp_cache_filename = null;
        }

        //
        // Shutdown function
        // Make sure the temporary cache file's content is safely stored on disk.
        // Rename the temporary file into the final cache file.
        //
        public function close_cache_file()
        {
                $this->log(1,__FUNCTION__,"Closing cache file, transferred {$this->tsize} bytes, {$this->tcount} buffers, {$this->dretry} retries");
                if (!$this->cache_fp) {
                        $this->log(1,__FUNCTION__,"URL not cached, file pointer empty");
                        return;
                }

                $cl = $this->server_reply_headers['Content-Length'];
                $sz = ftell($this->cache_fp) - $this->cache_header_size;
                if ($sz != $cl) {
                        $this->log(1,__FUNCTION__,"Not fully downloaded [$sz/$cl]");
                }
                else if (fflush($this->cache_fp) === FALSE || fclose($this->cache_fp) === FALSE) {
                        $this->log(2,__FUNCTION__,"Cannot close written cache file: [{$this->cache_filename}]");
                }
                else if (rename($this->temp_cache_filename, $this->cache_filename) === FALSE) {
                        $this->log(2,__FUNCTION__,"Cannot rename temporary cache file: [{$this->temp_cache_filename}] to [{$this->cache_filename}]");
                }
                else {
                        $this->add_video($sz);   // Add video to the db
                        $this->add_to_stats();   // Calc stats
                        $this->cache_fp = null;
                        $this->log(1,__FUNCTION__,"Close cached file: ".$this->cache_filename);
                }

                $this->close_temporary_transfer($this->cache_request);  // Delete from temporary
                $this->stop_caching();
        }

}

function char_to_hex($ch) {
        return sprintf('%2X', ord($ch));
        }

function safe_filename($fn) {
        return preg_replace_callback('/[^a-zA-Z0-9_-]/', 'char_to_hex', $fn);
        }

if (!$db || !$set)
    fatal_error("No DB instance or settings!");

// Instance cacher and run it
$cr = new YouTubeCacher();
$cr->run($db,$set);

?>