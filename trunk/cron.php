<?php

require("init.php");

if (!$db)
    fatal_error("Database not instanced!");

$stats=$db->get_row("stats");
$rows=$db->sql("select count(*) as `cnt` from `temporary`");
$temps=$rows[0]["cnt"];
$lastmin=$db->sql("select count(*) as cnt from visits where `visit_date`>DATE_SUB(NOW(),INTERVAL 1 MINUTE)") or die("Mysql ERROR: ".$db->lasterror." sql ".$db->lastsql);
$lastmin=$lastmin[0]["cnt"];

$insert=array(
"time_inserted"=>"NOW()",
"last_minute_hits"=>$lastmin,
"connect_time"=>$stats["connect_time"],
"file_access_time"=>$stats["file_access_time"],
"transfers"=>$temps,
);

$db->insert_row("graphs",$insert);

$rows=$db->sql("SELECT * FROM `temporary` WHERE accessed < DATE_SUB(NOW(), INTERVAL 5 minute)") or die("Mysql ERROR: ".$db->lasterror."sql ".$db->lastsql);

$count=0;
foreach ($rows as $row) {
    $fname=$row["filename"];
    print "Delete file {$fname}\n";
    unlink($fname);
    $find=array(
    "id"=>$row["id"],
    );
    $db->delete_row("temporary",$find);
    $count++;
}

print "Done, {$count} file deleted!\n";

?>
