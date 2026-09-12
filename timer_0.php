<?php
//echo Yii::app()->createUrl('user/timer');
//echo Yii::app()->createUrl('item/addItems');
$ch = curl_init ();

curl_setopt ( $ch, CURLOPT_URL, "http:/daspos.com/user/timer" );

curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

$server_output = curl_exec ( $ch );

curl_close ( $ch );


$ch = curl_init ();

curl_setopt ( $ch, CURLOPT_URL, "http://daspos.com/item/addItems" );

curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

$server_output = curl_exec ( $ch );

curl_close ( $ch );
?>