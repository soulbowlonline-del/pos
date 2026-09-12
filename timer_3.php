<?php
$ch = curl_init ();

curl_setopt ( $ch, CURLOPT_URL, "http://192.168.100.16:8091/pos_sect4/user/reorder" );

curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

$server_output = curl_exec ( $ch );
var_dump( $server_output) ;

echo curl_getinfo($ch) . '<br/>' . PHP_EOL;
echo curl_errno($ch) . '<br/>'. PHP_EOL;
echo curl_error($ch) . '<br/>'. PHP_EOL;

curl_close ( $ch );

echo "end";
curl_close ( $ch );

?>