<?php

/**
 * statistics
 * 
 * @package youtube cache
 * @author dr4g0n
 * @copyright 2011
 * @version $Id$
 * @access public
 */
class statistics extends base {
    
    private $odd=false;
    
    public function submenu() {
        $submenu=array();
        $submenu[0]["index"]["link"]="submenu=stats";
        $submenu[0]["index"]["name"]="Stats";
        $submenu[1]["index"]["link"]="submenu=graphs";
        $submenu[1]["index"]["name"]="Graphs";
        $this->page_manager->submenu($submenu);
    }

    private function clear_stats() {
        $this->db->sql("UPDATE `stats` SET `miss`=0, `hit`=0, `localtraffic`=0, `internettraffic`=0, `connect_time`=0, `file_access_time`=0, `troughput_internet`=0, `troughput_local`=0");
    }
    
    private function addtostats($line) {
        if ($line=="START")
            $this->content.="<table class='table'>\n"; else
        if ($line=="END")
            $this->content.="</table>\n"; else {
        $data_array=explode(": ",$line);
        if (!$this->odd) {
            $this->odd=true;
            $class="odd";
        } else {
            $this->odd=false;
            $class="even";            
        }
        $this->content.="<tr class='{$class}'><td>{$data_array[0]}</td><td><strong>{$data_array[1]}</strong></td></tr>\n";
        }
    }
    
    private function gengraphs() {
        $rows=$this->db->sql("SELECT * FROM `graphs` ORDER BY `id` DESC LIMIT 24");  // last 24 hours
        foreach ($rows as $row) {
            $id[]=$row["id"];
            $time_inserted[]=$row["time_inserted"];
            $last_minute_hits[]=$row["last_minute_hits"];
            $connect_time[]=$row["connect_time"];
            $file_access_time[]=$row["file_access_time"];
            $transfers[]=$row["transfers"];
        }
        if (empty($id)) {
            $img1="No data for graphs!<br>\n";
            $img2="No data for graphs!<br>\n";
            $img3="No data for graphs!<br>\n";
            $img4="No data for graphs!<br>\n";
            } else {
            $img1=$this->page_manager->create_graph(600,200,$last_minute_hits,"Last minute hits",array("Last hours"),"No of hits","line");
            $img2=$this->page_manager->create_graph(600,200,$connect_time,"Connect time(ms)",array("Last hours"),"ms","line");
            $img3=$this->page_manager->create_graph(600,200,$file_access_time,"File access time(ms)",array("Last hours"),"ms","line");
            $img4=$this->page_manager->create_graph(600,200,$transfers,"Transfers",array("Last hours"),"No","line");
        }
            $row=$this->db->get_row("stats");
            $data=array($row["miss"],$row["hit"]);
            $img5=$this->page_manager->create_graph(300,300,$data,"Misses & hits",array("","misses","hits"),"","pie");
            $data=array($row["localtraffic"],$row["internettraffic"]);
            $img6=$this->page_manager->create_graph(300,300,$data,"Traffic",array("","local traffic","internet traffic"),"","pie");
            $data=array($row["troughput_internet"],$row["troughput_local"]);
            $img7=$this->page_manager->create_graph(300,300,$data,"Throughput",array("","troughput internet","troughput local"),"","pie");
            $data=array($row["connect_time"],$row["file_access_time"]);
            $img8=$this->page_manager->create_graph(300,300,$data,"Access latency",array("","http connect time","file access time"),"","pie");

        $img1comment="* Last minute hit (last 60 seconds) is the access from cache for every one hour.<br />The greater the graph is, the more cached content is served.\n";
        $img2comment="* Connect time is the needed time for server to connect to video servers.<br />The greater the graph, the slower response from video servers and slower caching/playing.\n";
        $img3comment="* File access time is the time needed to open the cache file from disk.<br />The more cached videos you have this value is bigger.<br />Around 2ms is great, if it starts to become bigger than connect time, videos will start slower than without cache (but it will be faster when file is opened).\n";
        $img4comment="* Transfers are the current amount of transfer every hour.<br />Transfers are not pulling from cache but from the internet instead (caching video to file).<br />If this value is big it means more misses or simply more video serving (from cache+internet).\n";
        $img5comment="* Misses and hits is the misses from cache (videos that needs to be downloaded from internet to local cache) and hits from cache (video served directly from file).\n";
        $img6comment="* Traffic is the cumulative amount of data transferred so far. Local traffic means amount of data server from local cache and internet traffic means data served from internet.\n";
        $img7comment="* Throughput is current bandwidth used to serve videos.<br />Throughput internet is the bandwidth from the internet, and throughput local is the local cache serving bandwidth\n";
        $img8comment="* Access latency is the time needed to access video content.<br />Http connect time is the time needed to connect to video servers (in case there is no video in cache), and file access time is the time needed to start serving from local cache.\n";
        $this->content.="<table width=100%>\n";
        $this->content.="<tr>\n";
        $this->content.="<td width=50%>{$img1}<br />{$img1comment}</td>\n";
        $this->content.="<td width=50%>{$img2}<br />{$img2comment}</td>\n";
        $this->content.="</tr>\n";
        $this->content.="<tr>\n";
        $this->content.="<td width=50%>{$img3}<br />{$img3comment}</td>\n";
        $this->content.="<td width=50%>{$img4}<br />{$img4comment}</td>\n";
        $this->content.="</tr>\n";
        $this->content.="<tr>\n";
        $this->content.="<td width=50%>{$img5}&nbsp{$img6}<br />{$img5comment}<br />{$img6comment}</td>\n";
        $this->content.="<td width=50%>{$img7}&nbsp{$img8}<br />{$img7comment}<br />{$img8comment}</td>\n";
        $this->content.="</tr>\n";
        $this->content.="</table>\n";
    }
    
    private function getstats() {
    $row=$this->db->get_row("stats");
    extract($row);
    $efficiency_size=ROUND(($localtraffic*100)/$internettraffic);
    $efficiency_hit=ROUND(($hit*100)/$miss);
    $localtraffic=number_readable($localtraffic);
    $internettraffic=number_readable($internettraffic);
    $troughput_internet=number_readable($troughput_internet)."/sec";
    $troughput_local=number_readable($troughput_local)."/sec";
    
    $res=$this->db->sql("SELECT SUM(size) as size_sum, path FROM `videos` GROUP by path");
    foreach ($res as $res1)
        $space[$res1["path"]]=$res1["size_sum"];    
    
    $this->addtostats("START");
    $this->addtostats("Miss: ".number_readable($miss,"decimals"));
    $this->addtostats("Hit: ".number_readable($hit,"decimals"));
    $this->addtostats("Local traffic: {$localtraffic}");
    $this->addtostats("Internet traffic: {$internettraffic}");
    $this->addtostats("Connect time: {$connect_time}ms");
    $this->addtostats("File access time: {$file_access_time}ms");
    $this->addtostats("Internet troughput: {$troughput_internet}");
    $this->addtostats("Local troughput: {$troughput_local}");
    $vcount=$this->db->sql("select count(*) as cnt from videos");
    $count=$vcount[0]["cnt"];
    $this->addtostats("Cached videos: ".number_readable($count,"decimals"));
    $totalsum=$this->db->sql("SELECT SUM(size) as totalsum FROM `videos`");
    $totalsum=$totalsum[0]["totalsum"];
    $totalsum=number_readable($totalsum);
    $this->addtostats("Total video size: {$totalsum}");
    $this->addtostats("Size Efficiency: {$efficiency_size}%");
    $this->addtostats("Hit Efficiency: {$efficiency_hit}%");
    $lastday=$this->db->sql("select count(*) as cnt from visits where `visit_date`>DATE_SUB(NOW(),INTERVAL 1 DAY)");
    $lastday=$lastday[0]["cnt"];
    $this->addtostats("Last day hits: ".number_readable($lastday,"decimals"));
    $lasthr=$this->db->sql("select count(*) as cnt from visits where `visit_date`>DATE_SUB(NOW(),INTERVAL 1 HOUR)");
    $lasthr=$lasthr[0]["cnt"];
    $this->addtostats("Last hour hits: ".number_readable($lasthr,"decimals"));    
    $lastmin=$this->db->sql("select count(*) as cnt from visits where `visit_date`>DATE_SUB(NOW(),INTERVAL 1 MINUTE)");
    $lastmin=$lastmin[0]["cnt"];
    $this->addtostats("Last minute hits: ".number_readable($lastmin,"decimals"));
    $totalsize=0; $bars="";
    foreach ($space as $spath=>$ssize) {
        $stotal=disk_total_space($spath);
        $bars.=$this->page_manager->create_bar($spath,$ssize,$stotal,600,true);
        $perc=ROUND(($ssize*100)/$stotal);
        $totalsize+=$stotal;
        $ssize=number_readable($ssize);
        $stotal=number_readable($stotal);
        $this->addtostats("Store path: <em>{$spath}</em> size <strong>{$ssize} ({$perc}%)</strong> total <strong>{$stotal}</strong>");
        }   // storage paths
    $this->addtostats("Total storage size: <strong>".number_readable($totalsize)."</strong>");
    $this->addtostats("END");
    $this->content.=$bars."<br />\n";
    }

    public function run() {
        $this->get_request();
        foreach ($this->requests as $request=>$value) {
            switch($request) {
                case "clear_stats":
                $this->clear_stats();
                break;
                default:
            }
        }
        
        $submenu=$_REQUEST["submenu"];
        switch($submenu) {
            case "graphs":
            $this->gengraphs();
            break;
            case "stats":
            $serverstatus=$this->page_manager->button("Server Status","/server-status","excl.gif");
            $clearstats=$this->page_manager->button("Clear stats","{$_SERVER["REQUEST_URI"]}&clear_stats=1","bomb.gif");
            $this->getstats();
            $this->content=$this->content."<br />\n".$serverstatus.$clearstats."<br />\n";
            break;
            default:
            header("Location: index.php?page=statistics&submenu=stats");
            
        }
        
        $this->submenu();
        $this->page_manager->finish_script($this->content);

    }
}

?>