<?php

class page_manager {

    public $template=null;      // template engine instance
    public $template_path=null;
    public $userid=null;
    public $logins=null;
    public $page;
    public $users;
    public $session_userid;
    public $mainmenu;
    public $leftmenu;
    public $rightmenu;
    public $topmenu;
    public $submenu;
    public $webpage_name;
    public $odd;
    
    public function __construct($template,$path,$webpage_name) {
        if (!$template)
            $this->template=new template; else
            $this->template=$template;
        $this->template_path=$path;
        $this->template->set_template_path($this->template_path);
        $this->webpage_name=$webpage_name;
    }

    public function clean($str, $encode_ent = false) {
 	  $str  = @trim($str);
 	  if($encode_ent) {
 	  	$str = htmlentities($str);
 	  }
 	  if(version_compare(phpversion(),'4.3.0') >= 0) {
 		if(get_magic_quotes_gpc())
 			$str = stripslashes($str);
 		if (function_exists("mysql_real_escape_string"))
 			$str = mysql_real_escape_string($str);
 		else 
 			$str = addslashes($str);
 	}
 	else
 		if(!get_magic_quotes_gpc())
 			$str = addslashes($str);
 	return $str;
    }

    public function create_bar($title,$value,$max,$width=300,$onlyperc=true) {
        $perc=ROUND(($value*100)/$max);
        if ($perc>100)
            $perc=100;
        $wcell1=($width/100)*$perc;
        $wcell2=($width/100)*(100-$perc);
        if ($onlyperc)
            $printval="{$perc}%"; else
            $printval="{$value}({$perc}%)";
        $output.="<table border=0 cellpadding=0 cellspacing=2 frame=void ><tr><td>{$title}</td><td width={$wcell1}px style='background-color: #888888'><center><strong>{$printval}</strong></center></td><td width={$wcell2} style='background-color: #dddddd'></td></tr></table>";
        return $output;
    }

    public function create_graph($width=600,$height=200,$data,$title,$xaxis,$yaxis,$type="bar") {
        require_once ('jpgraph/jpgraph.php');
        require_once ('jpgraph/jpgraph_line.php');
        require_once ('jpgraph/jpgraph_bar.php');
        require_once ('jpgraph/jpgraph_pie.php');

        // Create a graph instance
        if ($type=="bar" || $type=="line")
            $graph = new Graph($width,$height); else
        if ($type=="pie")
            $graph = new PieGraph($width,$height);
 
        // Specify what scale we want to use,
        // int = integer scale for the X-axis
        // int = integer scale for the Y-axis
        $graph->SetScale('intint');
        $graph->SetMarginColor("lightblue:1.1");
        $graph->SetShadow();
        $graph->SetMargin(60,20,10,40);
 
        // Box around plotarea
        $graph->SetBox(); 
 
        // No frame around the image
        $graph->SetFrame(false);

        // Setup a title for the graph
        $graph->title->Set($title);
        $graph->title->SetMargin(8);
        $graph->title->SetColor("darkred");

        // Setup the X and Y grid
        $graph->ygrid->SetFill(true,'#DDDDDD@0.5','#BBBBBB@0.5');
        $graph->ygrid->SetLineStyle('dashed');
        $graph->ygrid->SetColor('gray');
        $graph->xgrid->Show();
        $graph->xgrid->SetLineStyle('dashed');
        $graph->xgrid->SetColor('gray');

        // Setup titles and X-axis labels, if it's array, first row is title
        if (is_array($xaxis)) {
            $graph->xaxis->title->Set($xaxis[0]);
            $xaxis=array_slice($xaxis,1,count($xaxis)-1);
            $graph->xaxis->SetTickLabels($xaxis);    
        } else
        $graph->xaxis->title->Set($xaxis);  // no array, just show name

        // Setup Y-axis title
        $graph->yaxis->title->SetMargin(10);
        $graph->yaxis->title->Set($yaxis);
        
        if ($type=="bar") {
            $plot=new BarPlot($data);
            $plot->SetWidth(0.6);
            $fcol='#440000';
            $tcol='#FF9090';
            $plot->SetFillGradient("navy:0.9","navy:1.85",GRAD_LEFT_REFLECTION);
            //$plot->SetColor("black");
 
            // Set line weigth to 0 so that there are no border
            // around each bar
            $plot->SetWeight(0);
            // Add the plot to the graph
            $graph->Add($plot);
        } else
        if ($type=="line") {
            $plot=new LinePlot($data);
            $plot->SetFillColor('skyblue@0.5');
            $plot->SetColor('navy@0.7');
            $plot->mark->SetType(MARK_SQUARE);
            $plot->mark->SetColor('blue@0.5');
            $plot->mark->SetFillColor('lightblue');
            $plot->mark->SetSize(5);
              // Add the plot to the graph
            $graph->Add($plot);
        } else
        if ($type=="pie") {
            $plot=new PiePlot($data);
            $plot->SetCenter(0.5,0.55);
            $plot->SetSize(0.2);
 
            // Enable and set policy for guide-lines
            $plot->SetGuideLines();
            $plot->SetGuideLinesAdjust(1.4);
 
            // Setup the labels
            $plot->SetLabelType(PIE_VALUE_PER);    
            $plot->value->Show();            
            //$plot->value->SetFont(FF_ARIAL,FS_NORMAL,9);    
            $plot->value->SetFormat('%2.1f%%');        
            $plot->ExplodeSlice( 1 );
            $plot->SetGuideLines(true);
            $graph->SetMarginColor("white");
            
            $plot->SetLegends($xaxis);

              // Add the plot to the graph
            $graph->Add($plot);
        } else
        die($type." is not known graph type");
        
        // Display the graph
        $fn=strtolower($title);
        $fn=str_replace(" ","",$fn);
        $filename_relative="site/web_app/images/dynamic/{$fn}.jpg";
        $filename_full=__DIR__."/".$filename_relative;
        $graph->Stroke($filename_full);
        $imglink="<img src='{$filename_relative}' title='{$title}' />\n";
        return $imglink;
    }

    public function text_title($text) {
        return '<h2 class="title">'.$text.'</h2><br>'."\n";
    }

    public function button($name,$link,$img="cross,png") {
        $link_parts=explode("?",$link);
        $vars="";
        if ($link_parts[1]) {
            $link_args=explode("&",$link_parts[1]);
            foreach ($link_args as $link_arg) {
                $link_arg=explode("=",$link_arg);
                $vars.="<input type='hidden' name='{$link_arg[0]}' id='{$link_arg[0]}' value='{$link_arg[1]}' />\n";
            }
        }
        $output="<form action=\"{$link}\" class=\"form\" method='POST'>\n";
        if ($vars)
            $output.=$vars;
        $output.='<button class="button" type="submit"><img src="'.$this->template_path.'images/icons/'.$img.'" alt="'.$name.'" /> '.$name.'</button>'."\n";
        $output.="</form>\n";
        return $output;
    }

    public function table($header,$data) {
        $output="<table class='table'>\n";
        if (!empty($header)) {
            $output.="<tr>\n";
            foreach ($header as $row) {
                $output.="<td>{$row}</td>";
                }
            $output.="</tr>\n";
            }
            
        foreach ($data as $row) {
            if (!$this->odd) {
                $this->odd=true;
                $class="odd";
            } else {
                $this->odd=false;
                $class="even";
            }
            $output.="<tr class='{$class}'>\n";
            foreach ($row as $row1) {
                $output.="<td>{$row1}</td>";
                }
            $output.="</tr>\n";
            }
        $output.="</table>\n";
        return $output;
    }

    public function menus($menus) {
        $this->mainmenu='<ul class="wat-cf">';
        foreach ($menus as $menu=>$value) {
            if ($this->page===$menu)
                $class="active"; else
                $class="first";
            if (!empty($value["icon"]))
                $img_link="<img src='{$this->template_path}/{$value["icon"]}' /><br />"; else
                $img_link="";
            $this->mainmenu.='<li class="'.$class.'"><a href="'.$value['link'].'">'.$img_link.$value['name'].'</a></li>';
        }
        $this->mainmenu.='</ul>';
    }

    public function rightmenu() {
    $out='<ul class="wat-cf">
          <li><a href="index.php?page=profile">Profile</a></li>
          <li><a class="logout" href="index.php?page=logout">Logout</a></li>
        </ul>';
    return $out;
    }
    
    public function submenu($submenu) {
        $this->submenu='<div class="secondary-navigation"><ul class="wat-cf">'."\n";
        $active_submenu=strtolower($_GET["submenu"]);
        foreach ($submenu as $sm)
        foreach ($sm as $menu=>$value) {
            if ($active_submenu===strtolower($value["name"]))
                $class=' class="active first"'; else
                $class='';
            $uri=$_SERVER["REQUEST_URI"];
            $uria=explode("&",$uri);
            $uri_base=$uria[0];
            $this->submenu.=' <li'.$class.'><a href="'.$uri_base."&".$value['link'].'">'.$value['name'].'</a></li>'."\n";
            
        }
    $this->submenu.='</ul></div>'."\n";
    }

    public function getuserinfo($id) {
        if (empty($id)) {
            $id=$this->userid;
        }
    }

    public function checklogin($username,$password) {
        foreach ($this->users as $user=>$value) {
            if ($username===$user && $password===$value["password"]) {
                $this->session_userid=$value["id"];
                return TRUE;
                }
        }
        return FALSE;
    }

    public function session($func) {
        $username=$this->clean($_POST["username"]);
        $password=$this->clean($_POST["password"]);
        session_start();
        $this->userid=(int)$_SESSION['SESS_USER_ID'];

        // Log out user, delete session
        if ($func=="logout") {
            unset($_SESSION['SESS_USER_ID']);
            session_write_close();
            $this->page="login";
            return true;
        }

        if (!empty($username) && !empty($password)) {   // user try to login
            if ($this->checklogin($username,$password)) {   // login ok?
                //Regenerate session ID to prevent session fixation attacks
                session_regenerate_id();
                $_SESSION['SESS_USER_ID']=$this->session_userid;
                session_write_close();
                return true;
                } else {    // login not ok!
                $this->tags=array(
                "formaction"=>"index.php?page=index",
                "message"=>"wrong username or password!",
                "TEMPLATE_PATH"=>$this->template_path,
                );
                // Display login page
                $this->template->get_page("login");
                $this->template->set_pageexpiration(PAGE_EXPIRATION_NOW);
                $this->template->quickrender($this->tags) or die("error rendering login page");
                die();
                }
        } else
        if (empty($this->userid)) {    // user not logged in?
            $this->tags=array(
            "formaction"=>"index.php?page=index",
            "message"=>"type username and password",
            "TEMPLATE_PATH"=>$this->template_path,
            );
            $this->template->get_page("login");
            $this->template->set_pageexpiration(PAGE_EXPIRATION_NOW);
            $this->template->quickrender($this->tags) or die("error rendering login page");
            die();
        } else
        return TRUE;    // user logged, found id
    }

    public function load($page,$users) {
        // Special logout page, replace with index
        if ($page=="logout") {
            $this->session("logout");
            $page="login";
        }
        $this->page=$page;
        $this->users=$users;
        $this->session();   // check session
        if (empty($page))
            $page="index";
        $this->template->get_page("index");
    }

    public function finish_script($content) {
        
        $this->tags=array(
            "mainmenu"=>$this->mainmenu,
            "rightmenu"=>$this->rightmenu(),
            "submenu"=>$this->submenu,
            "title"=>strip_tags($this->webpage_name)."-".$this->page,
            "webpage_name"=>$this->webpage_name,
            "TEMPLATE_PATH"=>$this->template_path,
            "content"=>$content,
            );
        $this->template->set_pageexpiration(PAGE_EXPIRATION_NOW);
        $this->template->quickrender($this->tags);
    }

}

?>