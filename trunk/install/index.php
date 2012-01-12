<?php

    require_once("settings.inc");    

$override=$_REQUEST["override"];

function check_system($pi) {
$result=true;
    if (strstr(strtolower($pi['General']['System']),"windows")) {
        print "<li>System: <font color=red>{$pi['General']['System']}</font></li>";
        $result=false;
        } else {
        print "<li>System: <font color=green>{$pi['General']['System']}</font></li>";
        }
    if (strstr(strtolower($pi['Core']['PHP Version']),"5.3.")) {
        print "<li>PHP version: <font color=green>{$pi['Core']['PHP Version']}</font></li>";
        } else {
        print "<li>PHP version: <font color=red>{$pi['Core']['PHP Version']}</font></li>";
        $result=false;
        }
    if (strstr(strtolower($pi['General']['Server API']),"2.0")) {
        print "<li>Server API version: <font color=green>{$pi['General']['Server API']}</font></li>";
        } else {
        print "<li>Server API version: <font color=red>{$pi['General']['Server API']}</font></li>";
        $result=false;
        }
    if (strstr(strtolower($pi['Core']['safe_mode']['master']),"off")) {
        print "<li>Safe mode: <font color=green>{$pi['Core']['safe_mode']['master']}</font></li>";
        } else {
        print "<li>Safe mode: <font color=red>{$pi['Core']['safe_mode']['master']}</font></li>";
        $result=false;
        }
    if (strstr(strtolower($pi['Core']['expose_php']['master']),"off")) {
        print "<li>Expose PHP: <font color=green>{$pi['Core']['expose_php']['master']}</font></li>";
        } else {
        print "<li>Expose PHP: <font color=red>{$pi['Core']['expose_php']['master']}</font></li>";
        $result=false;
        }
    if (substr(strtolower($pi['Apache Environment']['REQUEST_URI']),1,7)=="install") {
        print "<li>Path: <font color=green>{$pi['Apache Environment']['REQUEST_URI']}</font></li>";
        } else {
        print "<li>Path: <font color=red>{$pi['Apache Environment']['REQUEST_URI']}</font></li>";
        $result=false;
        }
    if (substr(strtolower($pi['gd']['GD Support']),0)=="enabled") {
        print "<li>GD Support: <font color=green>{$pi['gd']['GD Support']}</font></li>";
        } else {
        print "<li>GD Support: <font color=red>{$pi['gd']['GD Support']}</font></li>";
        $result=false;
        }
    if (substr(strtolower($pi['mysql']['Client API version']),0)) {
        print "<li>Mysql version: <font color=green>{$pi['mysql']['Client API version']}</font></li>";
        } else {
        print "<li>Mysql version: <font color=red>{$pi['mysql']['Client API version']}</font></li>";
        $result=false;
        }
    unset($squid_config_file);
    if (file_exists("/etc/squid/squid.conf"))
        $squid_config_file="/etc/squid/squid.conf";
    else
    if (file_exists("/usr/local/etc/squid/squid.conf"))
        $squid_config_file="/usr/local/etc/squid/squid.conf";
    else
    if (file_exists("/usr/local/squid/etc/squid/squid.conf"))
        $squid_config_file="/usr/local/squid/etc/squid/squid.conf";    
    if (!$squid_config_file) {
        print "<li>SQUID config: <font color=red>Not found!</font></li>";
        $result=false;
    } else
    print "<li>SQUID config found: <font color=green>{$squid_config_file}</font></li>";
    $squid_config=file($squid_config_file);
    if (!$squid_config) {
        print "<li>SQUID config file: <font color=red>denied! change permission with command chmod 664 {$squid_config_file}</font></li>";
        $result=false;
    }
    foreach($squid_config as $line=>$sq) {
        if (strstr($sq,"url_rewrite_program /var/www/cache.rb")) {
            print "<li>SQUID config: <font color=green>SQUID: url_rewrite_program /var/www/cache.rb!</font></li>";
            $ok1=true;
            } else
        if (strstr($sq,"url_rewrite_host_header off")) {
            print "<li>SQUID config: <font color=green>SQUID: url_rewrite_host_header off!</font></li>";
            $ok2=true;
            } else
        if (strstr($sq,"cache deny to_localhost")) {
            print "<li>SQUID config: <font color=green>SQUID: cache deny to_localhost!</font></li>";
            $ok3=true;
            } else
        if (strstr($sq,"header_access Server deny to_localhost")) {
            print "<li>SQUID config: <font color=green>SQUID: header_access Server deny to_localhost!</font></li>";
            $ok4=true;
            }
    }
    if (!$ok1) {
        print "<li>SQUID config: <font color=red>SQUID: url_rewrite_program /var/www/cache.rb!</font></li>";
        $result=false;
        }
    if (!$ok2) {
        print "<li>SQUID config: <font color=red>SQUID: url_rewrite_host_header off!</font></li>";
        $result=false;
        }
    if (!$ok3) {
        print "<li>SQUID config: <font color=red>SQUID: cache deny to_localhost!</font></li>";
        $result=false;
        }
    if (!$ok4) {
        print "<li>SQUID config: <font color=red>SQUID: header_access Server deny to_localhost!</font></li>";
        $result=false;
        }        
    return $result;
}

function phpinfo_array()
{
    ob_start();
    phpinfo();
    $info_arr = array();
    $info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
    $cat = "General";
    foreach($info_lines as $line)
    {
        // new cat?
        preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
        if(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
        {
            $info_arr[trim($cat)][trim($val[1])] = trim($val[2]);
        }
        elseif(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
        {
            $info_arr[$cat][$val[1]] = array("local" => $val[2], "master" => $val[3]);
        }
    }
    return $info_arr;
}

/*
    if (file_exists($config_file_path)) {        
		header("location: ".$application_start_file);
        exit;
	}
*/

$pi=phpinfo_array();
//var_dump($pi);die;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>Installation Guide</title>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
	<link rel="stylesheet" type="text/css" href="img/styles.css">
</head>
<BODY text=#000000 vLink=#2971c1 aLink=#2971c1 link=#2971c1 bgColor=#ffffff>
    
<TABLE align="center" width="70%" cellSpacing=0 cellPadding=2 border=0>
<TBODY>
<TR>
    <TD class=text vAlign=top>
        <H2>New Installation of <?=$application_name;?>!</H2>
        
        Follow the wizard to setup your database.<BR><BR>
        <TABLE width="100%" cellSpacing=0 cellPadding=0 border=0>
        <TBODY>
        <TR>
            <TD>
                <TABLE width="100%" cellSpacing=0 cellPadding=0 border=0>
                <TBODY>
                <TR>
                    <TD></TD>
                    <TD align=middle>
                        <TABLE width="100%" cellSpacing=0 cellPadding=0 border=0>
                        <TBODY>
                        <TR>
                            <TD class=text align=left>
								<b>Getting System Info</b>
                            </TD>
                        </TR>
                        <tr><td>&nbsp;</td></tr>
                        <tr>
                            <TD class=text align=left>
                                <UL>
                                <?php
                                $pass=check_system($pi);
                                ?>
								</UL>
							</TD>
                        </TR>
                        <tr><td>&nbsp;</td></tr>
                        <?php
                            if ($pass || $override) {
                        ?>	
						<TR>
                            <TD class=text align=left>
								Click on Start button to continue.
							</TD>
						</TR>
                        <?php
                        }
                        ?>
                        </TBODY>
                        </TABLE>
						<br />
                        <?php
                            if ($pass || $override) {
                        ?>						
                        <table width="100%" border="0" cellspacing="0" cellpadding="2" class="main_text">
                        <tr>
                            <td colspan=2 align='left'>
                                <input type="button" class="form_button" value="Start" name="submit" title="Click to start installation" onclick="document.location.href='install.php'">
                            </td>
                        </table>
                            <?php
                            }
                            ?>
					</TD>
                    <TD></TD>
                </TR>
                </TBODY>
                </TABLE>

            </TD>
        </TR>
        </TBODY>
        </TABLE>

        <? include_once("footer.php"); ?>        
    </TD>
</TR>
</TBODY>
</TABLE>
                  
</body>
</html>

