<?php

class access extends base {
    
    public function submenu() {
        $submenu=array();
        $submenu[0]["index"]["link"]="submenu=table";
        $submenu[0]["index"]["name"]="Table";
        $this->page_manager->submenu($submenu);
    }
    
    private function clear_visits() {
            $this->db->sql("TRUNCATE `visits`");
        }

    public function access_table() {
        $rows[]=(array("id","ip","date","request","vid"));
        $rows1=$this->db->get_rows("visits",array(),"LIMIT 100");
       foreach ($rows1 as $row) {
           $row["request"]=str_replace("&","<br /> ",$row["request"]);
           $row["visit_date"]=str_replace(" ","<br />",$row["visit_date"]);
           //$row["request"]=str_replace("?","<br /> ",$row["request"]);
           $rows2[]=$row;
      }
        $table=rows2table(array_merge($rows,$rows2),true);
        $this->content.=$table;
    }

    public function run() {
        // Get action
        $this->get_request();
        foreach ($this->requests as $request=>$value) {
            switch($request) {
                case "clear_visits":
                    $this->clear_visits();
                break;
                default:
            }
        }
        $clearcache=$this->page_manager->button("Clear table","{$_SERVER["REQUEST_URI"]}&clear_visits=1","bomb.gif");

        $submenu=$_REQUEST["submenu"];
        switch($submenu) {
            case "table":
            $this->access_table();
            break;
            default:
            header("Location: index.php?page=access&submenu=table");
            
        }
        
        $this->submenu();
        $this->content=$this->content."<br />\n".$clearcache."<br />\n";
        $this->page_manager->finish_script($this->content);
    }
    
}

?>