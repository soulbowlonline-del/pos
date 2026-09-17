<?php
$mysqli = new mysqli("localhost","root","welcome@100","pos");
// Check connection
if ($mysqli -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}
// $query = 'SELECT
// `tbl_i_form`.`id` as id,
// `tbl_i_form`.`ekharid_id` as IformNo,
// `tbl_i_form`.`no_of_bags` as TotalBags,
// `tbl_i_form`.`total_amount` as Amount,
// `tbl_i_form`.`weight` as TotalWeight,
// `tbl_agency`.`name` as Agency,
// `tbl_mandi`.`name` as Mandi,
// `tbl_district`.`name` as District,
// `tbl_commodity`.`name` as Commodity
// FROM `tbl_i_form`
// inner join tbl_agency on tbl_agency.id = tbl_i_form.`agency_id`
// inner join tbl_mandi on tbl_mandi.id = tbl_i_form.`mandi_id`
// inner join tbl_district on tbl_district.id = tbl_i_form.`district_id`
// inner join tbl_commodity on tbl_commodity.id = tbl_i_form.`commodity_id`
// where `tbl_i_form`.session_id = 7 and `tbl_i_form`.`ekharid_status` = 1';

require_once __DIR__ . '/vendor/autoload.php';

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

$query16 = "SELECT * FROM `tbl_order_item` WHERE `create_date` between $start_date and $last_date group by `tax_id` ";
$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel
    ->getActiveSheet()->setCellValue('A1', 'Type')
    ->setCellValue('B1', 'Place Of Supply')
    ->setCellValue('C1', 'Rate')
    ->setCellValue('D1', 'Taxable Value')
	 ->setCellValue('E1', 'CGST Amount')
	  ->setCellValue('F1', 'SGST Amount')
    ->setCellValue('G1', 'Cess Amount')
	 ->setCellValue('H1', 'IGST Amount')
    ->setCellValue('I1', 'E-Commerce GSTIN');
	
$objPHPExcel->getActiveSheet()->setTitle('b2cs');
		
if ($result16 = $mysqli -> query($query16)) {
	$ii = 2;
    while($row16 = $result16->fetch_assoc()) {
		/*$tax = 0;
		$cess = 0;
	
		$taxable = $row15['total_amt'] - $row15['tax_amt'];*/
		$tax_id = $row16['tax_id'];
		$query17 = "SELECT sum(`price` * `qty`) as total FROM `tbl_order_item` WHERE `create_date` between $start_date and $last_date and `tax_id` = $tax_id ";
		$query18 = "SELECT sum(`tax_amount`) as tax FROM `tbl_order_item` WHERE `create_date` between $start_date and $last_date and `tax_id` = $tax_id ";
		
		

		$result17 = $mysqli -> query($query17);
$row17 = $result17->fetch_array();
$result18 = $mysqli -> query($query18);
$row18 = $result18->fetch_array();
		
		$totaltaxable = $row17['total'] ;	
		$query19 = "SELECT sum(`total_amt`) as total FROM `tbl_order_refund_item` WHERE `create_time` between $start_date and $last_date and `tax_id` = $tax_id ";
		$query20 = "SELECT sum(`tax_amt`) as tax FROM `tbl_order_refund_item` WHERE `create_time` between $start_date and $last_date and `tax_id` = $tax_id ";
		
	
		$result19 = $mysqli -> query($query19);
$row19 = $result19->fetch_array();
$result20 = $mysqli -> query($query20);
$row20 = $result20->fetch_array();
$refundtaxable = $row19['total'] - $row20['tax'];	

///echo $totaltaxable;
//echo 'refund'.$refundtaxable;exit;

$taxable = $totaltaxable - $refundtaxable;

$query13 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
$result13 = $mysqli -> query($query13);
$row13 = $result13->fetch_array();
if($row13){
	$tax = $row13['tax_val1']+$row13['tax_val2']+$row13['tax_val3']+$row13['tax_val4'];
	$cgst = $taxable * $row13['tax_val1']/100;
	$sgst = $taxable * $row13['tax_val2']/100;
	$cess = $taxable * $row13['tax_val3']/100;
	$igst = $taxable * $row13['tax_val4']/100;
}

$amount_with_tax = $taxable + $cgst + $sgst + $cess + $igst;

			 $objPHPExcel->getActiveSheet()->setCellValue('A'.$ii,  'OE')
                   ->setCellValue('B'.$ii,  '04-Chandigarh')
                   ->setCellValue('C'.$ii,  $tax)
                   ->setCellValue('D'.$ii,  $taxable)
				    ->setCellValue('E'.$ii,  $cgst)
					 ->setCellValue('F'.$ii,  $sgst)
                   ->setCellValue('G'.$ii,  $cess)
				   ->setCellValue('H'.$ii,  $igst)
                   ->setCellValue('I'.$ii,  '');
				   
				 
				   $ii++;
  }
  $result16 -> free_result();

}

$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(1);
$objPHPExcel
    ->getActiveSheet()->setCellValue('A1', 'Nature of Document')
    ->setCellValue('B1', 'Sr. No. To')
    ->setCellValue('C1', 'Sr. No. From')
    ->setCellValue('D1', 'Total Number')
	 ->setCellValue('E1', 'Cancelled');
$objPHPExcel->getActiveSheet()->setTitle('docs');
		
		$query21 = "SELECT * FROM `tbl_order` WHERE `bill_date` between $start_date and $last_date ORDER BY `id` ASC limit 1";

		$query22 = "SELECT * FROM `tbl_order` WHERE `bill_date` between $start_date and $last_date ORDER BY `id` DESC limit 1";
		$result21 = $mysqli -> query($query21);
$row21 = $result21->fetch_array();
$result22 = $mysqli -> query($query22);
$row22 = $result22->fetch_array();
		
	$total_bills = $row22['bill_no'] - $row21['bill_no'];


			 $objPHPExcel->getActiveSheet()->setCellValue('A2',  'No. of bills')
                   ->setCellValue('B2',  $row22['bill_no'])
                   ->setCellValue('C2',  $row21['bill_no'])
                   ->setCellValue('D2',  $total_bills)
				    ->setCellValue('E2',  '0');
				$query23 = "SELECT * FROM `tbl_order_refund` WHERE `create_time` between $start_date and $last_date ORDER BY `id` ASC limit 1";

		$query24 = "SELECT * FROM `tbl_order_refund` WHERE `create_time` between $start_date and $last_date ORDER BY `id` DESC limit 1";
		$result23 = $mysqli -> query($query23);
$row23 = $result23->fetch_array();
$result24 = $mysqli -> query($query24);
$row24 = $result24->fetch_array();
		
	$total_bills = $row24['id'] - $row23['id'];
	

			 $objPHPExcel->getActiveSheet()->setCellValue('A3',  'No. of bills')
                   ->setCellValue('B3',  $row24['id'])
                   ->setCellValue('C3',  $row23['id'])
                   ->setCellValue('D3',  $total_bills)
				    ->setCellValue('E3',  '0');
				  
				  
  
  $result22 -> free_result();


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="GST.xls"');
header('Cache-Control: max-age=0');
$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xls');
$objWriter->save('php://output');
