#!/usr/bin/php -q
<?php

set_time_limit (120);
$web_server_path="http://10.5.0.150/youtube.php";

while ( $input = fgets(STDIN) ) {
  // URL <SP> client_ip "/" fqdn <SP> user <SP> method [<SP> kvpairs]<NL>
  $original_input=$input;
  $input_array=explode(" ",$input);
  $iurl=$input_array[0];
  $ifqdn=$input_array[1];
  $iuser=$input_array[2];
  $imethod=$input_array[3];
  $ikvpairs=$input_array[4];  // optional
  
  if(strstr($iurl,"youtube.com/videoplayback?") && strtolower($imethod)=="get") {
        $url_encoded=base64_encode($iurl);
        echo $web_server_path."?url={$url_encoded}\n"; // URL of my web server
  } else
        echo $original_input; // empty line means no re-write by Squid.
}
?>
