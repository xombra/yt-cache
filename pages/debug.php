<?php

class debug extends base {

    public function submenu() {
        $submenu=array();
        $submenu[0]["index"]["link"]="submenu=table";
        $submenu[0]["index"]["name"]="Table";
        $this->page_manager->submenu($submenu);
    }
    
    private function clear_debug() {
        $this->db->sql("TRUNCATE `debug`");
    }
    
    public function debug_table($filter,$value) {
    $rows[]=(array("id","severity","function","message","date","PID"));
    if (!empty($filter)) {
        $find=array($filter=>$value);
        $rows1=$this->db->get_rows("debug",$find);
    } else
    $rows1=$this->db->get_rows("debug");
    foreach ($rows1 as $row) {
        $row["message"]=str_replace("&","<br /> ",$row["message"]);
        $row["message"]=str_replace("?","<br /> ",$row["message"]);
        $row["message"]="<a href='{$_SERVER["REQUEST_URI"]}&message={$row["message"]}'>{$row["message"]}</a>\n";
        $row["pid"]="<a href='{$_SERVER["REQUEST_URI"]}&pid={$row["pid"]}'>{$row["pid"]}</a>\n";
        $row["debug_date"]="<a href='{$_SERVER["REQUEST_URI"]}&debug_date={$row["debug_date"]}'>{$row["debug_date"]}</a>\n";
        $row["severity"]="<a href='{$_SERVER["REQUEST_URI"]}&severity={$row["severity"]}'>{$row["severity"]}</a>\n";
        $row["facility"]="<a href='{$_SERVER["REQUEST_URI"]}&facility={$row["facility"]}'>{$row["facility"]}</a>\n";
        $rows2[]=$row;
        }
    $table=rows2table(array_merge($rows,$rows2),true);
    $this->content.=$table;
    }
    
    public function run() {
        $this->get_request();
        foreach ($this->requests as $request=>$value) {
            switch($request) {
                case "clear_debug":
                    $this->clear_debug();
                break;
                case "severity":
                case "facility":
                case "message":
                case "debug_date":
                case "pid":
                    $req=$request;
                    $val=$value;
                break;
                default:

            }
        }
        $cleardebug=$this->page_manager->button("Clear debug","{$_SERVER["REQUEST_URI"]}&clear_debug=1","bomb.gif");
        
        $submenu=$_REQUEST["submenu"];
        switch($submenu) {
            case "table":
            $this->debug_table($req,$val);
            break;
            default:
            header("Location: index.php?page=debug&submenu=table");
            
        }
        
        $this->submenu();
        $this->content=$this->content."<br />\n".$cleardebug."<br />\n";
        $this->page_manager->finish_script($this->content);
    }
}

?>