<?php

class index extends base {
    
    private $content;
    
    public function submenu() {
        $submenu=array();
        $submenu[0]["index"]["link"]="submenu=overview";
        $submenu[0]["index"]["name"]="Overview";
        $submenu[1]["index"]["link"]="submenu=memory";
        $submenu[1]["index"]["name"]="Memory";
        $submenu[2]["index"]["link"]="submenu=disks";
        $submenu[2]["index"]["name"]="Disks";
        $submenu[3]["index"]["link"]="submenu=system";
        $submenu[3]["index"]["name"]="System";
        $submenu[4]["index"]["link"]="submenu=network";
        $submenu[4]["index"]["name"]="Network";
        $this->page_manager->submenu($submenu);
    }

    public function findos() {
        $version=$this->sysinfo("version");
        $verion_lower=strtolower($version);
        if (strstr($verion_lower,"ubuntu"))
            $this->content.="<img src='{$this->template_path}/images/os/ubuntu.png' /> <strong><em>Ubuntu</em></strong><br>\n"; else
        if (strstr($verion_lower,"arch"))
            $this->content.="<img src='{$this->template_path}/images/os/arch.png' /> <strong><em>Arch Linux</em></strong><br>\n"; else
        if (strstr($verion_lower,"centos"))
            $this->content.="<img src='{$this->template_path}/images/os/centos.png' /> <strong><em>Cent OS</em></strong><br>\n"; else
        if (strstr($verion_lower,"debian"))
            $this->content.="<img src='{$this->template_path}/images/os/debian.png' /> <strong><em>Debian</em></strong><br>\n"; else
        if (strstr($verion_lower,"dragonfly"))
            $this->content.="<img src='{$this->template_path}/images/os/dragonfly.png' /> <strong><em>Dragonfly</em></strong><br>\n"; else
        if (strstr($verion_lower,"fedora"))
            $this->content.="<img src='{$this->template_path}/images/os/fedora.png' /> <strong><em>Fedora</em></strong><br>\n"; else
        if (strstr($verion_lower,"freebsd"))
            $this->content.="<img src='{$this->template_path}/images/os/freebsd.png' /> <strong><em>Free BSD</em></strong><br>\n"; else
        if (strstr($verion_lower,"gentoo"))
            $this->content.="<img src='{$this->template_path}/images/os/gentoo.png' /> <strong><em>Gentoo</em></strong><br>\n"; else
        if (strstr($verion_lower,"mandrake"))
            $this->content.="<img src='{$this->template_path}/images/os/mandrake.png' /> <strong><em>Mandrake</em></strong><br>\n"; else
        if (strstr($verion_lower,"mint"))
            $this->content.="<img src='{$this->template_path}/images/os/mint.png' /> <strong><em>Mint</em></strong><br>\n"; else
        if (strstr($verion_lower,"openbsd"))
            $this->content.="<img src='{$this->template_path}/images/os/openbsd.png' /> <strong><em>OpenBSD</em></strong><br>\n"; else
        if (strstr($verion_lower,"redhat"))
            $this->content.="<img src='{$this->template_path}/images/os/redhat.png' /> <strong><em>Red Hat</em></strong><br>\n"; else
        if (strstr($verion_lower,"slackware"))
            $this->content.="<img src='{$this->template_path}/images/os/slackware.png' /> <strong><em>Slackware</em></strong><br>\n";
        $this->content.="Linux version: <strong>{$version}</strong><br/>";
    }

    public function sysinfo($show) {
        $output="";
        switch ($show) {
            case "disks":
                $output.=$this->page_manager->text_title("Disk statistics");
                $rows=array();
                foreach(file('/proc/diskstats') as $info) {
                        if (strstr($info," sd")) {
                            $row=explode(" ",$info);
                            $ndx=array_search("",$row);
                            while ($ndx!==false) {
                                unset($row[$ndx]);
                                $ndx=array_search("",$row);
                                }
                            $row=array_slice($row,2);
                            $row2=array();
                            foreach ($row as $row1) {
                                if ((int)$row1>0)
                                    $row2[]=number_readable($row1,"decimals"); else
                                    $row2[]=$row1;
                            }
                            $rows[]=$row2;
                            }
                    }
 
                $header=array("device","reads issued","reads merged","sectors read","milliseconds spent reading","writes completed","writes merged","sectors written","milliseconds spent writing","I/Os currently in progress","milliseconds spent doing I/Os","weighted # of milliseconds spent doing I/Os");
                $output.=$this->page_manager->table($header,$rows);
                $output.=$this->page_manager->text_title("Disk partitions");
                foreach(file('/proc/partitions') as $info) {
                        if (strstr($info," sd")) {
                            $info=explode(" ",$info);
                            $filtered=array();
                            foreach ($info as $inf) {
                                if ($inf!="")
                                    $filtered[]=$inf;
                            }

                            $final[]=$filtered;
                            }
                    }
                $header=array("major","minor","# blocks","partition");
                $output.=$this->page_manager->table($header,$final);
                $output.=$this->page_manager->text_title("Disk space");
                exec("df -h | grep '/dev/sd'",$dfinfo);
                $final=array();
                foreach($dfinfo as $info) {
                            $info=explode(" ",$info);
                            $filtered=array();
                            foreach ($info as $inf) {
                                if ($inf!="")
                                    $filtered[]=$inf;
                            }

                            $final[]=$filtered;
                }
                $header=array("device","space","used","free","percent used","mount");
                $output.=$this->page_manager->table($header,$final);
                $output.=$this->page_manager->text_title("Disk Info");
                foreach(file('/proc/scsi/scsi') as $info) {
                    $output.=$info.HTML_EOL;
                }
                break;                
            case "version":
                foreach(file('/proc/version') as $info) {
                    $output.=$info;
                    }
            break;
            case "system":
            $output.=$this->page_manager->text_title("CPU");
                foreach(file('/proc/cpuinfo') as $info) {
                    $var=strtok($info, ':');
                    $val=strtok('');
                    if (strstr($var,"model name") || strstr($var,"cpu MHz") || strstr($var,"cache size") || strstr($var,"bogomips"))
                        $output.=$info.HTML_EOL;
                    }
            $output.=$this->page_manager->text_title("Loads");
                foreach(file('/proc/loadavg') as $ri)
                    $loadavg=$ri;
                $loadavg=explode(" ",$loadavg);
                $output.="Average 1 minute: <strong>{$loadavg[0]}</strong>".HTML_EOL;
                $output.="Average 5 minute: <strong>{$loadavg[1]}</strong>".HTML_EOL;
                $output.="Average 15 minute: <strong>{$loadavg[2]}</strong>".HTML_EOL;
                $output.="Running process/Total process: <strong>{$loadavg[3]}</strong>".HTML_EOL;
                $output.="Last running process: <strong>{$loadavg[4]}</strong>".HTML_EOL;
                foreach(file('/proc/uptime') as $ri)
                    $uptime=$ri;
                $uptime=explode(" ",$uptime);
                $uptime1=(int)$uptime[0];
                $uptime2=(int)$uptime[1];
                $online['days']=FLOOR($uptime1/(24*60*60));
                $online['hours']=FLOOR(($uptime1-($online['days']*(24*60*60)))/(60*60));
                $online['minutes']=FLOOR(($uptime1-($online['days']*(24*60*60)+$online['hours']*(60*60)) )/(60));
                $output.="Server online: <strong>{$online['days']}</strong> days <strong>{$online['hours']}</strong> hours <strong>{$online['minutes']}</strong> minutes".HTML_EOL;
                $online['days']=FLOOR($uptime2/(24*60*60));
                $online['hours']=FLOOR(($uptime2-($online['days']*(24*60*60)))/(60*60));
                $online['minutes']=FLOOR(($uptime2-($online['days']*(24*60*60)+$online['hours']*(60*60)) )/(60));
                $output.="Server idle time: <strong>{$online['days']}</strong> days <strong>{$online['hours']}</strong> hours <strong>{$online['minutes']}</strong> minutes".HTML_EOL;
            $output.=$this->page_manager->text_title("Filesystem");
                foreach(file('/proc/sys/fs/file-nr') as $ri)
                    $filenr=$ri;
                $filenr=explode(chr(9),$filenr);
                $output.="Allocated file handles: {$filenr[0]}".HTML_EOL;
                $output.="Free file handles: {$filenr[1]}".HTML_EOL;
                $output.="Maximum file handles: {$filenr[2]}".HTML_EOL;
                
            break;
            case "memory":
            $output.=$this->page_manager->text_title("Memory");
                foreach(file('/proc/meminfo') as $ri) {
                    $data_array[]=array_merge(explode(": ",$ri),array("","","","",""));
                    //$output.=$ri.HTML_EOL;
                    }
                $output.=$this->page_manager->table(array(),$data_array);
                //$perc_usage=100 - round(($m['MemFree'] + $m['Buffers'] + $m['Cached']) / $m['MemTotal'] * 100);
            break;
            case "network":
            $output.=$this->page_manager->text_title("Interfaces");
                foreach(file('/proc/net/dev') as $info) {
                            $row=explode(" ",$info);
                            $ndx=array_search("",$row);
                            while ($ndx!==false) {
                                unset($row[$ndx]);
                                $ndx=array_search("",$row);
                                }
                            $rows1[]=$row;
                    }
                sleep(1);
                foreach(file('/proc/net/dev') as $info) {
                            $row=explode(" ",$info);
                            $ndx=array_search("",$row);
                            while ($ndx!==false) {
                                unset($row[$ndx]);
                                $ndx=array_search("",$row);
                                }
                            $rows2[]=$row;
                    }
                    // remove info
                    unset($rows1[0]);
                    unset($rows1[1]);
                    unset($rows1[2]);
                    unset($rows2[0]);
                    unset($rows2[1]);
                    unset($rows2[2]);
                    $header=array("device","recv bytes","recv packets","recv errs","recv drop","recv fifo","recv frame","recv compressed","recv multicast", "transmit bytes", "transmit packets", "transmit errs", "transmit drop", "transmit fifo", "transmit colls", "transmit carrier", "transmit compressed");
                    $output.=$this->page_manager->table($header,$rows2);
                    $output.=$this->page_manager->text_title("Transfer");
                    $devcount=3;
                    while ($rows1[$devcount][2]) {
                        $dev1[$devcount-3]=$rows1[$devcount][2];
                        $rx_bytes1[$devcount-3]=$rows1[$devcount][3];
                        $rx_packets1[$devcount-3]=$rows1[$devcount][4];
                        $tx_bytes1[$devcount-3]=$rows1[$devcount][38];
                        $tx_packets1[$devcount-3]=$rows1[$devcount][39];
                        $devcount++;                        
                    }
                    $devcount=3;
                    while ($rows2[$devcount][2]) {
                        $dev2[$devcount-3]=$rows2[$devcount][2];
                        $rx_bytes2[$devcount-3]=$rows2[$devcount][3];
                        $rx_packets2[$devcount-3]=$rows2[$devcount][4];
                        $tx_bytes2[$devcount-3]=$rows2[$devcount][38];
                        $tx_packets2[$devcount-3]=$rows2[$devcount][39];
                        $devcount++;                        
                    }
                    for ($dev=0;$dev<=$devcount-4;$dev++) {
                        $rx_bytes[$dev]=number_readable($rx_bytes2[$dev]-$rx_bytes1[$dev]);
                        $rx_packets[$dev]=$rx_packets2[$dev]-$rx_packets1[$dev];
                        $tx_bytes[$dev]=number_readable($tx_bytes2[$dev]-$tx_bytes1[$dev]);
                        $tx_packets[$dev]=$tx_packets2[$dev]-$tx_packets1[$dev];
                        $data[]=array($dev1[$dev],$rx_bytes[$dev],$rx_packets[$dev],$tx_bytes[$dev],$tx_packets[$dev]);
                    }
                    $header=array("device","rx per sec","rx packets per sec","tx per sec","tx packets per sec");
                    $output.=$this->page_manager->table($header,$data);
                    //var_dump($dev1);die;
                    
            break;
            default:
        }
        return $output;
    }
    
    public function overview() {
        $this->content="";
        $this->content.=$this->page_manager->text_title("PHP");
        if (PHP_MAJOR_VERSION>=5)
            $ver="ok"; else
            $ver="upgrade to version 5";
        $this->content.="Version: <strong>".PHP_VERSION." [{$ver}]</strong><br/>";
        if (PHP_OS!="linux")
            $os="ok"; else
            $os="this script is tested only on linux";
        $this->content.="OS: <strong>".PHP_OS." [{$os}]</strong><br/>";
        if (PHP_DEBUG===0)
            $debug="no"; else
            $debug="yes";
        $this->content.="Debug: <strong>{$debug}</strong><br/>";
        $mem=ini_get("memory_limit");
        $this->content.="Memory limit: <strong>{$mem}</strong><br/>";
        $expose=ini_get("expose_php");
        $this->content.="Expose: <strong>{$expose}</strong><br/>";
        $safemode=ini_get("safe_mode");
        $this->content.="Safe mode: <strong>{$safemode}</strong><br/>";
        $this->content.=$this->page_manager->text_title("Software");
        $this->findos();
        $mysql=mysql_get_server_info();
        $this->content.="MYSQL Version: <strong>{$mysql}</strong><br/>";
        $apache = apache_get_version();
        $this->content.="APACHE Version: <strong>{$apache}</strong><br/>";
        $this->content.=$this->page_manager->text_title("Videos ");
        $vcount=$this->db->sql("SELECT COUNT(*) as `cnt` from `videos`");
        $vcount=(int)$vcount[0]["cnt"];
        $this->content.="Cached videos: <strong>{$vcount}</strong><br/>";
        $vtsize=$this->db->sql("SELECT SUM(size) as `vtsize` from `videos`");
        $vtsize=number_readable((int)$vtsize[0]["vtsize"]);
        $this->content.="Videos total size: <strong>{$vtsize}</strong><br/>";
    }
    
    public function run() {
        $submenu=$_REQUEST["submenu"];
        switch($submenu) {
            case "disks":
            $this->content=$this->sysinfo($submenu);
            break;
            case "memory":
            $this->content=$this->sysinfo($submenu);
            break;
            case "system":
            $this->content=$this->sysinfo($submenu);
            break;
            case "network":
            $this->content=$this->sysinfo($submenu);
            break;
            case "overview":
            $this->overview();
            break;
            default:
            header("Location: index.php?page=index&submenu=overview");
            
        }
        
        $this->submenu();
        $this->page_manager->finish_script($this->content);
    }
}

?>