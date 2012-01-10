<?php

$filename=$_REQUEST["filename"];
$fh=fopen($filename,"rb");
if (!$fh)
    die("Cannot open <strong>{$filename}</strong>!");

$hs=unserialize('a:4:{i:0;s:25:"Content-Type: video/x-flv";i:1;s:24:"Content-Length: ";i:2;s:44:"Last-Modified: Wed, 22 Jun 2011 14:23:28 GMT";i:3;s:15:"Server: gvs 1.0";}');

foreach ($hs as $n) {
    header("$n");
}

$now = time();
$maxage = 365 * 24 * 3600;

foreach (array('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT','Expires: ' . gmdate('D, d M Y H:i:s', $now + $maxage) . ' GMT','Cache-Control: public, max-age=' . $maxage) as $h)
    header($h);

while (!feof($fh)) {
$buffer=fread($fh,131072);
echo $buffer;
}

fclose($fh);

?>