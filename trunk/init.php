<?php

require "config.php";
require "phprd/sql/sql.php";
require "phprd/template/template.php";

function fatal_error($error) {
    print ($error);
    die;
}

function bench($mode) {
    static $bench_microsec_time;
    if ($mode=="start")
        $bench_microsec_time=microtime(true); else {
        $start=$bench_microsec_time;
        $bench_microsec_time=0;
        return ROUND((microtime(true)-$start)*1000);
        }
}

function strToHex($string)
{
    $hex='';
    for ($i=0; $i < strlen($string); $i++)
    {
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}

function number_readable($number,$unit="bytes") {
if ($unit=="bytes") {
    if ($number>1000000000)
        $number=ROUND($number/1000000000)."GB"; else
    if ($number>1000000)
        $number=ROUND($number/1000000)."MB"; else
    if ($number>1000)
        $number=ROUND($number/1000)."KB";
    } else
if ($unit=="decimals") {
    $number=number_format($number,0,".",",");
    }
return $number;
}

function choose_storage() {
    global $storage;
    $scount=count($storage);
    $storageno=rand(1,$scount);
    return $storage[$storageno];
}

function rows2table($rows,$markheader=false) {
$table="\n<table class='greentable'>\n";
if (!is_array($rows) || empty($rows))
	return $table."<tr>\r<td>NO DATA!</td>\r</tr>\r</table>\n";

$oddrow=true; $current_row=0;
foreach($rows as $row) {
	$cr=count($row);
	$current_row++;
	$table.="<tr class='greentable'>\n";
	$columnno=0;
	foreach ($row as $r) {
		$columnno++;
//		if ($columnno<$columnlimit)
			if ($current_row==1 && $markheader)
			$table.="<th class='greentable'>{$r}</th>\n"; else
			$table.="<td class='greentable'>{$r}</td>\n";
	}
	$table.="</tr>\n";
	if ($oddrow) $oddrow=false; else $oddrow=true;
}
$table.="</table>\n";

return $table;
}

$db=new mysql;
$tmpl=new template;

$dbconn=$db->connect_array($db_connection);
//retry 1
if (!$dbconn) {
    sleep(1);
    $dbconn=$db->connect_array($db_connection);
}
//retry 2
if (!$dbconn) {
    sleep(1);
    $dbconn=$db->connect_array($db_connection);
}

if (!$dbconn)
    die("Error in connecting to DB: ".$db->lasterror);
 
$template_path="site/web_app/";
define("HTML_EOL","<br />".PHP_EOL);

set_time_limit(3600 * 24);  // limit execution time
//setlocale(LC_TIME, "C");  // set locale
$basedir = dirname(__FILE__);   // get script basedir

// get settings from db
$settings=$db->get_rows("settings");
$storageno=1;
foreach ($settings as $setting) {
    $set[$setting["setting"]]=$setting["value"];
    extract($set);
    // generating storage array
    if (strstr($setting["setting"],"storage")) {
        $storage[$storageno]=$setting["value"];
        $storageno++;
    }
}

?>