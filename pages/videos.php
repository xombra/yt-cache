<?php

class videos extends base {

    private $msg="";
    private $known_formats=array("FLVK","ftypmp42isom");

    public function submenu() {
        $submenu=array();
        $submenu[0]["index"]["link"]="submenu=table";
        $submenu[0]["index"]["name"]="Table";
        $this->page_manager->submenu($submenu);
    }

    public function check_video($videoid) {
        $rows=$this->db->get_rows('videos',array(),"LIMIT 1000");
        foreach ($rows as $row) {
            extract($row);
            $fh=fopen($storage,"rb");
            if ($fh) {
                $fheader=fread($fh,20);
                $fheader_hex=strToHex($fheader);
                $fheader_printable=preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fheader);
                if (in_array($fheader_printable,$this->known_formats))
                    $known="<font color='green'>[recognized]</font>"; else
                    $known="<font color='red'>[not recognized]</font>";
                $this->msg.="File {$storage}, request {$request}, header {$fheader_printable}, hex {$fheader_hex} {$known}<br>\n";
                fclose($fh);
            } else
            $this->msg.="File {$storage}, request {$request} not found!<br>\n";
        }
        }
        
    public function delete_video($videoid) {
        if (!$videoid || !is_int($videoid))
            fatal_error("Video id not existing or wrong format!");
        $row=$this->db->get_row('videos',array("id"=>$videoid));
            $storage=$row["storage"];
            if (file_exists($storage))
                unlink($storage);
        $this->db->sql("DELETE from `videos` WHERE `id`='{$_REQUEST["delvideo"]}'");
    }

    public function delete_all_videos() {
        $rows=$this->db->get_rows('videos',array(),"LIMIT 10000");
        $delcnt=0;$delfailed=0;
        foreach ($rows as $row) {
            $storage=$row["storage"];
            if (file_exists($storage)) {
                chmod($storage, 0666);
                if (!unlink($storage))
                    $delfailed++; else
                    $this->db->sql("DELETE FROM `videos` WHERE `id`=".$row['id']);
                $delcnt++;
            } else $delfailed++;
        }
        $this->msg="(deleted {$delcnt} videos, failed {$delfailed})";
        //$this->db->sql("TRUNCATE `videos`");
    }
    
    public function videos_table() {
    $rows=array("id","request","path","storage","ip","added","size","accessed","visits","enabled","reply");
    foreach ($rows as $row) {
        $$row="{$row}<a href='{$_SERVER["REQUEST_URI"]}&sort_by_asc={$row}'><img src='{$this->template_path}/images/down_arrow.gif' /></a><a href='{$_SERVER["REQUEST_URI"]}&sort_by_desc={$row}'><img src='{$this->template_path}/images/up_arrow.gif' /></a><a href='{$_SERVER["REQUEST_URI"]}&search={$row}'><img src='{$this->template_path}/images/edit.gif' /></a>";
    }
   $rows=array(array("$id","$request","$path","$storage","$ip","$added","$size","$accessed","$visits","$enabled","reply headers"));
   
   $sort_by_asc=$this->requests["sort_by_asc"];
   $sort_by_desc=$this->requests["sort_by_desc"];
   if (!empty($sort_by_asc))
    $sort_by_asc="order by {$sort_by_asc} asc";
   if (!empty($sort_by_desc))
    $sort_by_asc="order by {$sort_by_desc} desc";
   $rows1=$this->db->get_rows("videos",array(),"{$sort_by_asc} limit 100");
    foreach ($rows1 as $row) {
        $row["reply_headers"]=implode(";",unserialize($row["reply_headers"]));
        $row["storage"]="<a href='stream_local.php?filename={$row["storage"]}'>{$row["storage"]}</a>";
        $row["size"]=$row["size"]." <strong>(".number_readable($row["size"]).")</strong>";
        if ($row["enabled"]==1)
            $row["enabled"]="yes"; else
            $row["enabled"]="no";
        $row["id"]="<a href='index.php?page=videos&delvideo={$row["id"]}'>X</a> ".$row["id"];
        $rows2[]=$row;
    }
    $this->content.=rows2table(array_merge($rows,$rows2),true);
    }
    
    public function run() {
        $this->get_request();
        foreach ($this->requests as $request=>$value) {
            switch($request) {
                case "checkvideo":
                    $this->check_video((int)$value);
                break;
                case "delvideo":
                    $this->delete_video((int)$value);
                break;
                case "delvideos":
                    $this->delete_all_videos();
                break;
                default:
            }
        }
        $delvideos=$this->page_manager->button("Delete up to 10,000 videos","{$_SERVER["REQUEST_URI"]}&delvideos=1","bomb.gif");
        $checkvideos=$this->page_manager->button("Check all videos","{$_SERVER["REQUEST_URI"]}&checkvideo=1","check.gif");

        $submenu=$_REQUEST["submenu"];
        switch($submenu) {
            case "table":
            $this->videos_table();
            break;
            default:
            header("Location: index.php?page=videos&submenu=table");
            
        }

        $this->submenu();
        $this->content="{$this->msg}<br>\n".$this->content.$table."<br />".$delvideos.$checkvideos."<br />\n";
        $this->page_manager->finish_script($this->content);

    }
}

?>