<?php
$mysqli = new mysqli("localhost","root","welcome@100","pos");
// Check connection
if ($mysqli -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}

require_once __DIR__ . '/vendor/autoload.php';



$running_month = date('m'); 

if($running_month == '01')
{
	$current_month = '12';
	$current_year = date('Y') - 1;
}else{
	$current_month = date('m') - 1;
$current_year = date('Y');
}

$start_date = "'".$current_year.'-'.$current_month.'-01'."'";
//$last_date = "'".date('Y-m-d')."'";
$last_date = "'".$current_year.'-' .$current_month.'-31'."'";
$query12 = "SELECT * FROM `tbl_item`";


		
if ($result14 = $mysqli -> query($query12)) {
	$ii = 2;
    while($row14 = $result14->fetch_assoc()) {
		$item_id = $row14['id'];
$query13 = "SELECT * FROM `tbl_stock_log` WHERE `id` = $item_id";
$result13 = $mysqli -> query($query13);
$row13 = $result13->fetch_array();

echo"<pre>"; print_r($row13); 
  }
  die;


}