<?php

class transfers extends base {

    public function submenu() {
        $submenu=array();
        $submenu[0]["index"]["link"]="submenu=table";
        $submenu[0]["index"]["name"]="Table";
        $this->page_manager->submenu($submenu);
    }

    public function transfers_table() {
    $rows[]=array("id","request","filename","accessed","size","progress","pcount","ip","pid","% done","inactive");
   
    //$rows1=$this->db->get_rows("temporary",array(),"ORDER BY `request` asc");
    $rows1=$this->db->sql("select *,ROUND((100/size)*progress),TIMEDIFF(NOW(),`accessed`) from `temporary` ORDER BY `request` asc");

    $this->content.=rows2table(array_merge($rows,$rows1),true);
    }
    
    public function run() {
        $this->get_request();
        foreach ($this->requests as $request=>$value) {
            switch($request) {
                default:
            }
        }

        $submenu=$_REQUEST["submenu"];
        switch($submenu) {
            case "table":
            $this->transfers_table();
            break;
            default:
            header("Location: index.php?page=transfers&submenu=table");
            
        }

        $this->submenu();
        $this->page_manager->finish_script($this->content);

    }
}

?>