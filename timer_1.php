<?php

$ch = curl_init ();

curl_setopt ( $ch, CURLOPT_URL, "http://localhost:8091/pos_sect4/onlineOrder/online" );

curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

$server_output = curl_exec ( $ch );

curl_close ( $ch );


?>