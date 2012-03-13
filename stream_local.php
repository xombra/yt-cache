<?php

$filename=$_REQUEST["filename"];

$fh=fopen($filename,"rb");
if (!$fh)
    die("Cannot open <strong>{$filename}</strong>!");

$now = time();
$maxage = 365 * 24 * 3600;
$fs=filesize($filename);

$headers=array(
'Content-Type: video/x-flv',
'Content-Length: '.$fs,
'Last-Modified: Wed, 22 Jun 2011 14:23:28 GMT',
'Server: gvs 1.0',
'Date: ' . gmdate('D, d M Y H:i:s') . ' GMT',
'Expires: ' . gmdate('D, d M Y H:i:s',
$now + $maxage) . ' GMT',
'Cache-Control: public, max-age=' . $maxage
);

foreach ($headers as $h) {
header($h);
}

while (!feof($fh)) {
$buffer=fread($fh,131072);
echo $buffer;
}

fclose($fh);

?>