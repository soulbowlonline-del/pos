<?php
require_once __DIR__ . '/report-db.php';
$mysqli = pos_report_db();
// Check connection
if ($mysqli -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}

require_once __DIR__ . '/vendor/autoload.php';



		$list = array (
				"PCS-PIECES",
				"Box",
				"Case",
				"KGS-KILOGRAMS",
				"ML",
				"NOS",
				"PCS",
				"PETI",
				"TIN" 
		)
		;
		
		
$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

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
$last_date = "'".$current_year.'-'.$current_month.'-31'."'";
$query12 = "SELECT `tbl_order_item`.* FROM `tbl_order_item` INNER JOIN `tbl_item` ON  `tbl_order_item` .`item_id` = `tbl_item` . `id`  
 and `tbl_order_item`. `create_date` between $start_date and $last_date";
 

$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel
    ->getActiveSheet()->setCellValue('A1', 'HSN')
    ->setCellValue('B1', 'Description')
    ->setCellValue('C1', 'UQC')
    ->setCellValue('D1', 'Total Quantity')
    ->setCellValue('E1', 'Total Value')
	 ->setCellValue('F1', 'Rate')
    ->setCellValue('G1', 'Taxable Value')
    ->setCellValue('H1', 'Integrated Tax Amount')
    ->setCellValue('I1', 'Central Tax Amount')
	->setCellValue('J1', 'State/UT Tax Amount')
	  ->setCellValue('K1', 'Cess Amount');
$objPHPExcel->getActiveSheet()->setTitle('HSN');
		
if ($result14 = $mysqli -> query($query12)) {
	
	
	///echo '<pre>';
///print_R($result14->fetch_assoc());exit;


	$ii = 2;
    while($row14 = $result14->fetch_assoc()) {
		$item_id = $row14['item_id'];
		
		
$query13 = "SELECT * FROM `tbl_item` WHERE `id` = $item_id";
$result13 = $mysqli -> query($query13);
$row13 = $result13->fetch_array();


$tax = 0;
		$tax_id = $row14['tax_id'];
		
		$order_id_check = $row14['order_id'];
$query131 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
$result131 = $mysqli -> query($query131);
$row131 = $result131->fetch_array();
if($row131){
	$tax = $row131['tax_val1']+$row131['tax_val2']+$row131['tax_val3']+$row131['tax_val4'];
}

$unit_name = isset($list[$row13['unit']]) ? $list[$row13['unit']] : "";




$query13_net = "SELECT sum(`tbl_order_refund_item`.total_amt) as total_refund_amt, sum(`tbl_order_refund_item`.tax_amt) as total_refund_tax_amt,sum(`tbl_order_refund_item`.discount_amt) as total_discount_amt, `tbl_order_refund_item`.*  FROM `tbl_order_refund_item` INNER JOIN  `tbl_order_refund` WHERE `tbl_order_refund_item` . order_refund_id = `tbl_order_refund`.id 
 and `tbl_order_refund_item`.item_id =  $item_id  and `tbl_order_refund`.order_id = $order_id_check and `tbl_order_refund_item`.qty > 0 ";
 

$result13_net = $mysqli -> query($query13_net);
$row13_net = $result13_net->fetch_assoc();





if(!empty($row13_net) && $row13_net['tax_id'] != null )
{
	
	$order_tax_val = round (($row14['price'] * $row14['qty']) + $row14['tax_amount'],3);

	
	$val_net = round($row13_net['total_refund_amt']- $row13_net['total_discount_amt'],3);
	$total_value_e = $order_tax_val - $val_net;
	
	
	$tax_value_check = round ($row14['price'] * $row14['qty'],3);
	$taxable = round ( $row13_net['total_refund_amt'] - $row13_net['total_refund_tax_amt'],3);
	$tax_value_e = $tax_value_check  - $taxable ;
	
	
	
	$cgst = 0;
	$sgst = 0;
	$cess = 0;
	$igst = 0;
	
	
	
				$tax_id = $row13_net['tax_id'];
$query1311 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
$result1311 = $mysqli -> query($query1311);
$row1311 = $result1311->fetch_array();
if($row1311){
	$cgst = ($row13_net['price'] * $row13_net['qty'])  * $row1311['tax_val1']/100;
	$sgst = ($row13_net['price'] * $row13_net['qty']) * $row1311['tax_val2']/100;
	$cess = ($row13_net['price'] * $row13_net['qty']) * $row1311['tax_val3']/100;
	$igst = ($row13_net['price'] * $row13_net['qty']) * $row1311['tax_val4']/100;
	
}
	
	
	$igst_val_e = round($row14['igst_amt'],3) - round($igst,3);
	$cgst_val_e = round($row14['cgst_amt'],3)- round($cgst,3);
	$sgst_val_e = round($row14['sgst_amt'],3)- round($sgst,3);
	$cess_val_e = round($row14['cess_amt'],3)- round($cess,3);
	
	
}else{
	
	$total_value_e = round (($row14['price'] * $row14['qty']) + $row14['tax_amount'],3);
	
	
	$tax_value_e = round ($row14['price'] * $row14['qty'],3);
	
	$igst_val_e = round($row14['igst_amt'],3);
	$cgst_val_e = round($row14['cgst_amt'],3);
	$sgst_val_e = round($row14['sgst_amt'],3);
	$cess_val_e = round($row14['cess_amt'],3);
	
}



			 $objPHPExcel->getActiveSheet()->setCellValue('A'.$ii, $row14['id'] )
                   ->setCellValue('B'.$ii,  $row13['title'])
                   ->setCellValue('C'.$ii,  $unit_name)
                   ->setCellValue('D'.$ii,  $row14['qty'])
                   ->setCellValue('E'.$ii,  $total_value_e)
				   ->setCellValue('F'.$ii,  $tax)
                   ->setCellValue('G'.$ii,  $tax_value_e)
                   ->setCellValue('H'.$ii,  $igst_val_e)
                   ->setCellValue('I'.$ii,  $cgst_val_e)
				   ->setCellValue('J'.$ii,  $sgst_val_e)
				   ->setCellValue('K'.$ii,  $cess_val_e);
				   $ii++;
  }
  $result14 -> free_result();

}


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="GST.xls"');
header('Cache-Control: max-age=0');
$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xls');
$objWriter->save('php://output');
