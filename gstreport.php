<?php
$mysqli = new mysqli("localhost", "root", "Pal@mrit18", "pos_live");
// Check connection
if ($mysqli->connect_errno) {
	echo "Failed to connect to MySQL: " . $mysqli->connect_error;
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




$list = array(
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

if ($running_month == '01') {
	$current_month = '12';
	$current_year = date('Y') - 1;
} else {
	$current_month = date('m') - 1;
	$current_year = date('Y');
}

if (isset($_GET['from']) && isset($_GET['to'])) {
	$start_date = "'" . $_GET['from'] . "'";
	$last_date = "'" . $_GET['to'] . "'";
} else {
	$start_date = "'" . $current_year . '-' . $current_month . '-01' . "'";
	$last_date = "'" . $current_year . '-' . $current_month . '-31' . "'";
}

$query12 = "SELECT * FROM `tbl_order_item` WHERE `create_date` between $start_date and $last_date  AND status='1'";


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
	->setCellValue('K1', 'Cess Amount')
	->setCellValue('L1', 'Date');

$objPHPExcel->getActiveSheet()->setTitle('HSN');

if ($result14 = $mysqli->query($query12)) {

	$ii = 2;
	while ($row14 = $result14->fetch_assoc()) {
		$item_id = $row14['item_id'];
		$query13 = "SELECT * FROM `tbl_item` WHERE `id` = $item_id";
		$result13 = $mysqli->query($query13);
		$row13 = $result13->fetch_array();

		$tax = 0;
		$tax_id = $row14['tax_id'];
		$query131 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
		$result131 = $mysqli->query($query131);
		$row131 = $result131->fetch_array();
		if ($row131) {
			$tax = $row131['tax_val1'] + $row131['tax_val2'] + $row131['tax_val3'] + $row131['tax_val4'];
		}



		$unit_name = isset($list[$row13['unit']]) ? $list[$row13['unit']] : "";


		$objPHPExcel->getActiveSheet()->setCellValue('A' . $ii, $row13['hsn_code'])
			->setCellValue('B' . $ii, $row13['title'])
			->setCellValue('C' . $ii, $unit_name)
			->setCellValue('D' . $ii, $row14['qty'])
			->setCellValue('E' . $ii, ($row14['price'] * $row14['qty']) + $row14['tax_amount'])
			->setCellValue('F' . $ii, $tax)
			->setCellValue('G' . $ii, $row14['price'] * $row14['qty'])
			->setCellValue('H' . $ii, $row14['igst_amt'])
			->setCellValue('I' . $ii, $row14['cgst_amt'])
			->setCellValue('J' . $ii, $row14['sgst_amt'])
			->setCellValue('K' . $ii, $row14['cess_amt'])
			->setCellValue('L' . $ii, $row14['create_date']);
		$ii++;
	}
	$result14->free_result();

}
$query15 = "SELECT * FROM `tbl_order_refund_item` WHERE date(create_time) between $start_date and $last_date and `qty` > 0 ";
$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(1);
$objPHPExcel
	->getActiveSheet()->setCellValue('A1', 'HSN Code')
	->setCellValue('B1', 'Note/Refund Voucher Number')
	->setCellValue('C1', 'Note/Refund Voucher date')
	->setCellValue('D1', 'Document Type')
	->setCellValue('E1', 'Place Of Supply')
	->setCellValue('F1', 'Note/Refund Voucher Value')
	->setCellValue('G1', 'Applicable % of Tax Rate')
	->setCellValue('H1', 'Rate')
	->setCellValue('I1', 'Taxable Value')
	->setCellValue('J1', 'CGST Amount')
	->setCellValue('K1', 'SGST Amount')
	->setCellValue('L1', 'Cess Amount')
	->setCellValue('M1', 'IGST Amount');

$objPHPExcel->getActiveSheet()->setTitle('cdnur');

if ($result15 = $mysqli->query($query15)) {

	// echo"<pre>"; print_r($result15); die;
	$ii = 2;
	while ($row15 = $result15->fetch_assoc()) {

		$item_id1 = $row15['item_id'];
		$query1311 = "SELECT * FROM `tbl_item` WHERE `id` = $item_id1";
		$result1311 = $mysqli->query($query1311);
		$row1311 = $result1311->fetch_array();



		$tax = 0;
		$cess = 0;
		$taxable = $row15['total_amt'] - $row15['tax_amt'];
		$tax_id = $row15['tax_id'];
		$query13 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
		$result13 = $mysqli->query($query13);
		$row13 = $result13->fetch_array();
		if ($row13) {
			$tax = $row13['tax_val1'] + $row13['tax_val2'] + $row13['tax_val3'] + $row13['tax_val4'];
			$cgst = ($row15['price'] * $row15['qty']) * $row13['tax_val1'] / 100;
			$sgst = ($row15['price'] * $row15['qty']) * $row13['tax_val2'] / 100;
			$cess = ($row15['price'] * $row15['qty']) * $row13['tax_val3'] / 100;
			$igst = ($row15['price'] * $row15['qty']) * $row13['tax_val4'] / 100;

		}

		$objPHPExcel->getActiveSheet()->setCellValue('A' . $ii, $row1311['hsn_code'])
			->setCellValue('B' . $ii, $row15['order_refund_id'])
			->setCellValue('C' . $ii, $row15['create_time'])
			->setCellValue('D' . $ii, 'credit note')
			->setCellValue('E' . $ii, 'chd')
			->setCellValue('F' . $ii, $row15['total_amt'] - $row15['discount_amt'])
			->setCellValue('G' . $ii, '100')
			->setCellValue('H' . $ii, $tax)
			->setCellValue('I' . $ii, $taxable)
			->setCellValue('J' . $ii, $cgst)
			->setCellValue('K' . $ii, $sgst)
			->setCellValue('L' . $ii, $cess)
			->setCellValue('M' . $ii, $igst);
		$ii++;
	}
	$result15->free_result();

}
$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(2);
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
$objPHPExcel->getActiveSheet()->setTitle('NET-HSN');

if ($result14 = $mysqli->query($query12)) {


	///echo '<pre>';
///print_R($result14->fetch_assoc());exit;


	$ii = 2;
	while ($row14 = $result14->fetch_assoc()) {
		$item_id = $row14['item_id'];


		$query13 = "SELECT * FROM `tbl_item` WHERE `id` = $item_id";
		$result13 = $mysqli->query($query13);
		$row13 = $result13->fetch_array();


		$tax = 0;
		$tax_id = $row14['tax_id'];

		$order_id_check = $row14['order_id'];
		$query131 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
		$result131 = $mysqli->query($query131);
		$row131 = $result131->fetch_array();
		if ($row131) {
			$tax = $row131['tax_val1'] + $row131['tax_val2'] + $row131['tax_val3'] + $row131['tax_val4'];
		}

		$unit_name = isset($list[$row13['unit']]) ? $list[$row13['unit']] : "";




		$query13_net = "SELECT sum(`tbl_order_refund_item`.total_amt) as total_refund_amt, sum(`tbl_order_refund_item`.tax_amt) as total_refund_tax_amt,sum(`tbl_order_refund_item`.discount_amt) as total_discount_amt, `tbl_order_refund_item`.*  FROM `tbl_order_refund_item` INNER JOIN  `tbl_order_refund` WHERE `tbl_order_refund_item` . order_refund_id = `tbl_order_refund`.id 
 and `tbl_order_refund_item`.item_id =  $item_id  and `tbl_order_refund`.order_id = $order_id_check and `tbl_order_refund_item`.qty > 0 ";


		$result13_net = $mysqli->query($query13_net);
		$row13_net = $result13_net->fetch_assoc();





		if (!empty($row13_net) && $row13_net['tax_id'] != null) {

			$order_tax_val = round(($row14['price'] * $row14['qty']) + $row14['tax_amount'], 3);


			$val_net = round($row13_net['total_refund_amt'] - $row13_net['total_discount_amt'], 3);
			$total_value_e = $order_tax_val - $val_net;


			$tax_value_check = round($row14['price'] * $row14['qty'], 3);
			$taxable = round($row13_net['total_refund_amt'] - $row13_net['total_refund_tax_amt'], 3);
			$tax_value_e = $tax_value_check - $taxable;



			$cgst = 0;
			$sgst = 0;
			$cess = 0;
			$igst = 0;



			$tax_id = $row13_net['tax_id'];
			$query1311 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
			$result1311 = $mysqli->query($query1311);
			$row1311 = $result1311->fetch_array();
			if ($row1311) {
				$cgst = ($row13_net['price'] * $row13_net['qty']) * $row1311['tax_val1'] / 100;
				$sgst = ($row13_net['price'] * $row13_net['qty']) * $row1311['tax_val2'] / 100;
				$cess = ($row13_net['price'] * $row13_net['qty']) * $row1311['tax_val3'] / 100;
				$igst = ($row13_net['price'] * $row13_net['qty']) * $row1311['tax_val4'] / 100;

			}


			$igst_val_e = round($row14['igst_amt'], 3) - round($igst, 3);
			$cgst_val_e = round($row14['cgst_amt'], 3) - round($cgst, 3);
			$sgst_val_e = round($row14['sgst_amt'], 3) - round($sgst, 3);
			$cess_val_e = round($row14['cess_amt'], 3) - round($cess, 3);


		} else {

			$total_value_e = round(($row14['price'] * $row14['qty']) + $row14['tax_amount'], 3);


			$tax_value_e = round($row14['price'] * $row14['qty'], 3);

			$igst_val_e = round($row14['igst_amt'], 3);
			$cgst_val_e = round($row14['cgst_amt'], 3);
			$sgst_val_e = round($row14['sgst_amt'], 3);
			$cess_val_e = round($row14['cess_amt'], 3);

		}



		$objPHPExcel->getActiveSheet()->setCellValue('A' . $ii, $row13['hsn_code'])
			->setCellValue('B' . $ii, $row13['title'])
			->setCellValue('C' . $ii, $unit_name)
			->setCellValue('D' . $ii, $row14['qty'])
			->setCellValue('E' . $ii, $total_value_e)
			->setCellValue('F' . $ii, $tax)
			->setCellValue('G' . $ii, $tax_value_e)
			->setCellValue('H' . $ii, $igst_val_e)
			->setCellValue('I' . $ii, $cgst_val_e)
			->setCellValue('J' . $ii, $sgst_val_e)
			->setCellValue('K' . $ii, $cess_val_e);
		$ii++;
	}
	$result14->free_result();

}

$query15 = "SELECT item_id, tax_id, SUM(qty) as qty, SUM(qty * price) as qtyPrice, SUM(tax_amount) as tax_amount, SUM(igst_amt) as igst_amt, SUM(cgst_amt) as cgst_amt, SUM(sgst_amt) as sgst_amt, SUM(cess_amt) as cess_amt, GROUP_CONCAT(DISTINCT TRIM(IF(order_id = '', NULL, order_id)) SEPARATOR ',') as order_id FROM `tbl_order_item` WHERE `create_date` between $start_date and $last_date  AND status='1' GROUP BY item_detail_id";

$objPHPExcel->createSheet();
$objPHPExcel->setActiveSheetIndex(3);
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

$objPHPExcel->getActiveSheet()->setTitle('HSN Summary');

if ($result14 = $mysqli->query($query15)) {

	$ii = 2;
	while ($row14 = $result14->fetch_assoc()) {
		$item_id = $row14['item_id'];


		$query13 = "SELECT * FROM `tbl_item` WHERE `id` = $item_id";
		$result13 = $mysqli->query($query13);
		$row13 = $result13->fetch_array();


		$tax = 0;
		$tax_id = $row14['tax_id'];

		$order_id_check = rtrim($row14['order_id'], ',');
		$query131 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
		$result131 = $mysqli->query($query131);
		$row131 = $result131->fetch_array();
		if ($row131) {
			$tax = $row131['tax_val1'] + $row131['tax_val2'] + $row131['tax_val3'] + $row131['tax_val4'];
		}

		$unit_name = isset($list[$row13['unit']]) ? $list[$row13['unit']] : "";

		$query13_net = "SELECT sum(`tbl_order_refund_item`.total_amt) as total_refund_amt, sum(`tbl_order_refund_item`.tax_amt) as total_refund_tax_amt,sum(`tbl_order_refund_item`.discount_amt) as total_discount_amt, SUM(`tbl_order_refund_item`.qty * `tbl_order_refund_item`.price) as qtyPrice, `tbl_order_refund_item`.*  FROM `tbl_order_refund_item` INNER JOIN  `tbl_order_refund` WHERE `tbl_order_refund_item` . order_refund_id = `tbl_order_refund`.id 
 and `tbl_order_refund_item`.item_id =  $item_id  and `tbl_order_refund`.order_id IN ($order_id_check) and `tbl_order_refund_item`.qty > 0 group by `tbl_order_refund_item`.item_id";

		// echo $query13_net . "<br>";
		$result13_net = $mysqli->query($query13_net);
		
		$row13_net = $result13_net->fetch_assoc();
		if (!empty($row13_net) && $row13_net['tax_id'] != null) {

			$order_tax_val = round(($row14['qtyPrice']) + $row14['tax_amount'], 3);


			$val_net = round($row13_net['total_refund_amt'] - $row13_net['total_discount_amt'], 3);
			$total_value_e = $order_tax_val - $val_net;


			$tax_value_check = round($row14['qtyPrice'], 3);
			$taxable = round($row13_net['total_refund_amt'] - $row13_net['total_refund_tax_amt'], 3);
			$tax_value_e = $tax_value_check - $taxable;

			$cgst = 0;
			$sgst = 0;
			$cess = 0;
			$igst = 0;

			$tax_id = $row13_net['tax_id'];
			$query1311 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
			$result1311 = $mysqli->query($query1311);
			$row1311 = $result1311->fetch_array();
			if ($row1311) {
				$cgst = ($row13_net['qtyPrice']) * $row1311['tax_val1'] / 100;
				$sgst = ($row13_net['qtyPrice']) * $row1311['tax_val2'] / 100;
				$cess = ($row13_net['qtyPrice']) * $row1311['tax_val3'] / 100;
				$igst = ($row13_net['qtyPrice']) * $row1311['tax_val4'] / 100;

			}


			$igst_val_e = round($row14['igst_amt'], 3) - round($igst, 3);
			$cgst_val_e = round($row14['cgst_amt'], 3) - round($cgst, 3);
			$sgst_val_e = round($row14['sgst_amt'], 3) - round($sgst, 3);
			$cess_val_e = round($row14['cess_amt'], 3) - round($cess, 3);


		} else {

			$total_value_e = round(($row14['qtyPrice']) + $row14['tax_amount'], 3);


			$tax_value_e = round($row14['qtyPrice'], 3);

			$igst_val_e = round($row14['igst_amt'], 3);
			$cgst_val_e = round($row14['cgst_amt'], 3);
			$sgst_val_e = round($row14['sgst_amt'], 3);
			$cess_val_e = round($row14['cess_amt'], 3);

		}

		$objPHPExcel->getActiveSheet()->setCellValue('A' . $ii, $row13['hsn_code'])
			->setCellValue('B' . $ii, $row13['title'])
			->setCellValue('C' . $ii, $unit_name)
			->setCellValue('D' . $ii, $row14['qty'])
			->setCellValue('E' . $ii, $total_value_e)
			->setCellValue('F' . $ii, $tax)
			->setCellValue('G' . $ii, $tax_value_e)
			->setCellValue('H' . $ii, $igst_val_e)
			->setCellValue('I' . $ii, $cgst_val_e)
			->setCellValue('J' . $ii, $sgst_val_e)
			->setCellValue('K' . $ii, $cess_val_e);
		$ii++;
	}
	$result14->free_result();

}


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="GST.xls"');
header('Cache-Control: max-age=0');
$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xls');
$objWriter->save('php://output');
