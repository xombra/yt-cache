<?php

require("init.php");
require("page_manager.php");

class base {
    public $db;
    private $tags;
    public $requests;
    public $settings;
    public $page_manager=null;
    public $users_array;
    public $template_path=null;
    public $webpage_name=null;
    
    public function get_request() {
        $this->requests=$_REQUEST;
    }
    
    public function setup_page(&$template,&$path,&$page,&$menus,$webpage_name) {
        $this->webpage_name=$webpage_name;
        $this->template_path=$path;
        $this->page_manager=new page_manager(&$template,$path,$webpage_name);
        $this->page_manager->load($page,$this->users_array);
        $this->page_manager->menus($menus);
    }
    
    public function load_users() {
        $rows=$this->db->get_rows("users");
        foreach ($rows as $row) {
            $id=$row["id"];
            $username=$row["username"];
            $password=$row["password"];
            $ulevel=(int)$row["ulevel"];
            $this->users_array[$username]["id"]=$id;
            $this->users_array[$username]["password"]=$password;
            $this->users_array[$username]["ulevel"]=$ulevel;
        }
    }
    
}

if (!$db)
    fatal_error("Database not instanced!");

$page=$_REQUEST["page"];
if (empty($page))   // default page
    $page="index";

$reqfile="pages/".$page.".php";
if (!file_exists($reqfile))
    fatal_error("Page template file {$reqfile} does not exist!"); else
    require($reqfile);

$menus=array();
$menus["index"]["link"]="index.php?page=index";
$menus["index"]["name"]="Main";
$menus["index"]["icon"]="images/home.png";
$menus["index"]["access"]=0;

$menus["access"]["link"]="index.php?page=access";
$menus["access"]["name"]="Access";
$menus["access"]["icon"]="images/user.png";
$menus["access"]["access"]=0;

$menus["debug"]["link"]="index.php?page=debug";
$menus["debug"]["name"]="Debug";
$menus["debug"]["icon"]="images/bug.png";
$menus["debug"]["access"]=0;

$menus["statistics"]["link"]="index.php?page=statistics";
$menus["statistics"]["name"]="Statistics";
$menus["statistics"]["icon"]="images/statistics.png";
$menus["statistics"]["access"]=0;

$menus["videos"]["link"]="index.php?page=videos";
$menus["videos"]["name"]="Videos";
$menus["videos"]["icon"]="images/movie.png";
$menus["videos"]["access"]=0;

$menus["transfers"]["link"]="index.php?page=transfers";
$menus["transfers"]["name"]="Transfers";
$menus["transfers"]["icon"]="images/forward.png";
$menus["transfers"]["access"]=0;

$menus["settings"]["link"]="index.php?page=settings";
$menus["settings"]["name"]="Settings";
$menus["settings"]["icon"]="images/options.png";
$menus["settings"]["access"]=0;

$webpage="Video Cache";
$webpage_colors=array("red","yellow","white","green","blue");
$color=rand(0,count($webpage_colors));
$webpage_name.="<font color='".$webpage_colors[$color]."'>".$webpage."</font>\n";

$instance=new $page;
$instance->settings=$set;
$instance->db=$db;
$instance->load_users();
$instance->setup_page($tmpl,$template_path,$page,$menus,$webpage_name);
$instance->run();

?>