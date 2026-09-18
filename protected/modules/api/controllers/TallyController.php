<?php
class TallyController extends GxController {
	public function filters() {
		return array (
				'accessControl' 
		);
	}
	public function accessRules() {
		return array (
				array (
						'allow',
						'actions' => array (
								'paymentreport',
								'stockreturn',
								'cashsale',
								'b2btaxwise',
								'b2bsales',
						),
						'users' => array (
								'*' 
						) 
				),
				
				array (
						'deny',
						'users' => array (
								'*' 
						) 
				) 
		);
	}
	
	public function actionCashsale($date = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$headers = getallheaders ();
		//$skey = isset ( $headers ['skey'] ) ? $headers ['skey'] : null;;
	//if($skey != null){
	//	if($skey == 'b3V0bGluZUAxMjM='){
		$json_list  = array();
		$i =1;
		$current_month = date('m')-1;
		if(date('m')  == '01'){
			$current_month = 12;
		}
		if($current_month == 12){
			$year =  date('Y')-1;
		}else{
			$year =  date('Y');
		}
		$total_days = date("t", mktime(0,0,0, date("n") - 1));
		
		// for($i = 1; $i <= $total_days; $i++){
			// $date = "'".$year.'-'.$current_month.'-'.$i."'";
		$query16 = "SELECT * FROM `tbl_order_item` WHERE `create_date` = '".$date."' group by `tax_id` order by `tax_id` ";
		$items = Yii::app()->db->createCommand($query16)->queryAll();
		
		if($items){
			foreach($items as $item){
				$tax_id = $item['tax_id'];
				$query17 = "SELECT sum(`price` * `qty`) as total FROM `tbl_order_item` WHERE `create_date` = '".$date."'  and `tax_id` = $tax_id ";
		$totaltaxable = Yii::app()->db->createCommand($query17)->queryRow();

		
		$query19 = "SELECT sum(`price` * `qty`) as total FROM `tbl_order_refund_item` WHERE date(`create_time`) ='".$date."' and `tax_id` = $tax_id ";
		//$query20 = "SELECT sum(`tax_amt`) as tax FROM `tbl_order_refund_item` WHERE date(`create_time`)  = $date and `tax_id` = $tax_id ";
		
	
$row19 = Yii::app()->db->createCommand($query19)->queryRow();
//$row20 = Yii::app()->db->createCommand($query20)->queryRow();
//$refundtaxable = $row19['total'] - $row20['tax'];
$refundtaxable = $row19['total'];
$taxable = $totaltaxable['total'] - $refundtaxable;

		$query13 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
$row13 = Yii::app()->db->createCommand($query13)->queryRow();
// The five figures below are only assigned inside this branch. Before
// this line they were left from the previous iteration, or undefined
// on the first one - and an order item carrying tax_id = 0 (there are
// 32, across 23 dates) has no tbl_tax row and sorts first, so on PHP 8
// the undefined reads took the whole report down with a 500. Cleared
// per iteration, which also stops a row that finds no tax row from
// reporting the previous row's GST as its own.
$cgst = $sgst = $cess = $igst = $gst = $total_amt = null;
if($row13){
	$cgst = $taxable * ($row13['tax_val1'] * 0.01);
	$sgst = $taxable * ($row13['tax_val2'] * 0.01);
	$cess = $taxable * ($row13['tax_val3'] * 0.01);
	$igst = $taxable * ($row13['tax_val4'] * 0.01);
	$tax = $row13['tax_val1']+$row13['tax_val2']+$row13['tax_val4'];
	$gst = ($taxable * ($tax * 0.01)) + ($taxable * ($item['cess_per'] * 0.01));
	$total_amt = $taxable + $gst;
}
		
		
		
			$json_list [] = array(
			'Bill Date'=>$date,
			'Taxable'=>$taxable,
			'Gst'=>$gst ,
			'Cgst_per'=>$item['cgst_per'],
			'Sgst_per'=>$item['sgst_per'],
			 'Cess_per'=>$item['cess_per'],
			 // 'Cess_per' => $row13['tax_val3'] ,
			'Igst_per'=>$item['igst_per'],
			'Cgst'=>$cgst,
			'Sgst'=>$sgst,
			'Cess'=>$taxable * ($item['cess_per'] * 0.01),
			'Igst'=>$igst,
			'Amount'=>$total_amt 
			);
		}}
		// }
				
			if (! empty ($json_list )) {
				$arr ['status'] = 'OK';
	
				$arr ['grouptax'] = $json_list;
			} else {
				$arr ['message'] = 'data not available';
			}
		//}else{
		//	$arr ['error'] = 'skey is authenticated';
		//}
	//}else{
		//$arr ['error'] = 'skey is required';
	//}
		
		$this->sendJSONResponse ( $arr );
	}
	
	/*public function actionStockreturn($id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$headers = getallheaders ();
		//$skey = isset ( $headers ['skey'] ) ? $headers ['skey'] : null;;
	//if($skey != null){
	//	if($skey == 'b3V0bGluZUAxMjM='){
		
		$criteria = new CDbCriteria;
		
		$criteria->order='id asc';
		$criteria->limit = '50';
		$criteria->addCondition('id >'.$id);
		
		$returnitems = ItemReturnItem::model ()->findAll ( $criteria );	
				
				
			if (! empty ($returnitems)) {
				foreach ( $returnitems as $returnitem ) {
					$json_list [] = $returnitem->toTallyArray ();
				}
	
				$arr ['status'] = 'OK';
	
				$arr ['orders'] = $json_list;
			} else {
				$arr ['message'] = 'data not available';
			}
		//}else{
		//	$arr ['error'] = 'skey is authenticated';
		//}
	//}else{
		//$arr ['error'] = 'skey is required';
	//}
		
		$this->sendJSONResponse ( $arr );
	}*/
	public function actionStockreturn($date = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$headers = getallheaders ();
		//$skey = isset ( $headers ['skey'] ) ? $headers ['skey'] : null;;
	//if($skey != null){
	//	if($skey == 'b3V0bGluZUAxMjM='){
		
		
		$json_list = [];
		$criteria = new CDbCriteria;
		//$criteria->alias = 'tr';
		$criteria->with='itemReturn';
		$criteria->order='t.id asc';
		
		$criteria->addCondition('t.type_id = 0 ');
		
		$criteria->addCondition('date(itemReturn.grn_save_date) = "'.$date.'"');
		
		$criteria->addCondition('itemReturn.total_amt != 0');
		
		$returnitems = ItemReturnItem::model ()->findAll ( $criteria );	
			
		if (! empty ($returnitems)) {
				foreach ( $returnitems as $returnitem ) {
					
					
			$json_list [] = $returnitem->toTallyArray (); 
			
			
				/*	$bill_no = isset ( $returnitem->itemReturn ) ? $returnitem->itemReturn->bill_no: "";
					
					if($bill_no != ""){
	$purchasebill = PurchaseBill::model()->findByAttributes(array('bill_no'=>$bill_no));
	if($purchasebill){
		
		 $grn_date = date("Y-m-d",strtotime( $purchasebill->start_date ));
		 
		 if(strtotime($grn_date) == strtotime($date) )
		 {
			$json_list [] = $returnitem->toTallyArray (); 
		 }
	}
} */	
				}
	
				$arr ['status'] = 'OK';
	
				$arr ['orders'] = $json_list;
			} else {
				$arr ['message'] = 'data not available';
			}
		//}else{
		//	$arr ['error'] = 'skey is authenticated';
		//}
	//}else{
		//$arr ['error'] = 'skey is required';
	//}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionPaymentreport($date = null,$id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		//$headers = getallheaders ();
		//$skey = isset ( $headers ['skey'] ) ? $headers ['skey'] : null;;
	//if($skey != null){
	//	if($skey == 'b3V0bGluZUAxMjM='){
		$purchase_bill_ids = array();
		// Initialised up front: it is only assigned inside the id filter
		// below, so a filter matching nothing left it undefined - a warning
		// on PHP 8, which Yii 1 escalates into a 500.
		$json_list = array(); // initialised
		
		$criteria1 = new CDbCriteria;
		
		$criteria1->addCondition('start_date = "'.$date.'"');
		
		$criteria1->addCondition('status ='.PurchaseBill::STATUS_APPROVED);
		$purchasebills= PurchaseBill::model()->findAll($criteria1);
		
	//	Yii::log ( CVarDumper::dumpAsString ( $purchasebills ), CLogger::LEVEL_WARNING, '$mrss' );
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchase_bill_ids[] = $purchasebill->id;
			}
	
		}
		$criteria = new CDbCriteria;
		
		$criteria->group = 'purchase_bill_id,tax_id';
		$criteria->order='approved_qty desc';
		//$criteria->limit = '50';
		if($id != null){
		//$criteria->addCondition('id >'.$id);
		}
		$criteria->addInCondition('purchase_bill_id', $purchase_bill_ids);
	
		$billitems = PurchaseBillDetail::model ()->findAll ( $criteria );	
				
				
			if (! empty ( $billitems )) {
				foreach ( $billitems as $billitem ) {
					if($id != null){
						if($billitem->id > $id){
							$json_list [] = $billitem->toArray1 (true);
						}
					}else{
					$json_list [] = $billitem->toArray1 (true);
					}
				}
	
				$arr ['status'] = 'OK';
	
				$arr ['orders'] = $json_list;
			} else {
				$arr ['message'] = 'data not available';
			}
		//}else{
		//	$arr ['error'] = 'skey is authenticated';
		//}
	//}else{
		//$arr ['error'] = 'skey is required';
	//}
		
		$this->sendJSONResponse ( $arr );
	}
	
		 public function actionB2btaxwise($date = null)
    {
        $arr = array(
            'controller' => $this->id,
            'action' => $this->action->id,
            'status' => 'NOK'
        );
        	$headers = getallheaders ();

        $json_list = array();
        $i = 1;
        $current_month = date('m') - 1;
        if (date('m') == '01') {
            $current_month = 12;
        }
        if ($current_month == 12) {
            $year = date('Y') - 1;
        } else {
            $year = date('Y');
        }
        $total_days = date("t", mktime(0, 0, 0, date("n") - 1));

        // for ($i = 1; $i <= $total_days; $i ++) {
            // $date = "'" . $year . '-' . $current_month . '-' . $i . "'";
			   
				$criteria = new CDbCriteria ();
			
				$criteria->addCondition('date(create_time) = "'.$date.'"');
				$criteria->group = 'tax_id';
				// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
				// explicitly by the grouped columns to preserve the previous output order.
				$criteria->order = 'tax_id';
			
			$items = B2bPurchaseBillDetail::model()->findAll($criteria);

            $query16 = "SELECT * FROM `tbl_b2bpurchase_bill_detail`  Where date(create_time) = '".$date."' group by `tax_id`,`purchase_bill_id` order by `tax_id`,`purchase_bill_id` ";
            $items = Yii::app()->db->createCommand($query16)->queryAll();

            if ($items) {
                foreach ($items as $item) {
                    $tax_id = $item['tax_id'];
                    $purchase_bill_id = $item['purchase_bill_id'];
				
                    $query17 = "SELECT sum(`price` * `approved_qty`) as total FROM `tbl_b2bpurchase_bill_detail` WHERE date(`create_time`) = '".$date."' and `tax_id` = '".$tax_id."' and   `purchase_bill_id` = '".$purchase_bill_id."' ";
                    $totaltaxable = Yii::app()->db->createCommand($query17)->queryRow();

                    $query19 = "SELECT sum(`price` * `qty`) as total FROM `tbl_order_refund_item` WHERE date(`create_time`) = '".$date."' and `tax_id` = '".$tax_id."' ";
                    // $query20 = "SELECT sum(`tax_amt`) as tax FROM `tbl_order_refund_item` WHERE date(`create_time`) = $date and `tax_id` = $tax_id ";

                    $row19 = Yii::app()->db->createCommand($query19)->queryRow();
                    // $row20 = Yii::app()->db->createCommand($query20)->queryRow();
                    // $refundtaxable = $row19['total'] - $row20['tax'];
                    $refundtaxable = $row19['total'];
                    // $taxable = $totaltaxable['total'] - $refundtaxable;
                    $taxable = $totaltaxable['total'];

                    $query13 = "SELECT * FROM `tbl_tax` WHERE `id` = $tax_id";
                    $row13 = Yii::app()->db->createCommand($query13)->queryRow();
                    // The five figures below are only assigned inside this branch. Before
                    // this line they were left from the previous iteration, or undefined
                    // on the first one - and an order item carrying tax_id = 0 (there are
                    // 32, across 23 dates) has no tbl_tax row and sorts first, so on PHP 8
                    // the undefined reads took the whole report down with a 500. Cleared
                    // per iteration, which also stops a row that finds no tax row from
                    // reporting the previous row's GST as its own.
                    $cgst = $sgst = $cess = $igst = $gst = $total_amt = null;
                    if ($row13) {
                        $tax = $row13['tax_val1'] + $row13['tax_val2']  + $row13['tax_val4'];
                        $cgst = $taxable * ($row13['tax_val1'] * 0.01);
                        $sgst = $taxable * ($row13['tax_val2'] * 0.01);
                        $cess = $taxable * ($row13['tax_val3'] * 0.01);
                        $igst = $taxable * ($row13['tax_val4'] * 0.01);
						$gst = ($taxable * ($tax * 0.01)) + ($taxable * ($item['cess_per'] * 0.01));
                        
                        $total_amt = $taxable + $gst;
                    }
$bill = B2bPurchaseBill::model()->findByPk($item['purchase_bill_id']);
$detail = B2bPurchaseBillDetail::model()->findByPk($item['id']);
	$vendor = Vendor::model()->findByPk($bill['vendor_id']);	
	$vendorName="";
	if(!empty($vendor)){
		
		$vendorName=$vendor->name;
	}
	 $_billno ="";
	if(!empty($bill->start_date)){
		 $month = date('m', strtotime($bill->start_date));
        if ($month > 3) {
            $year = date('Y', strtotime($bill->start_date));
			$year= substr($year, -2);
            $yearlast = $year + 1;
			$yearlast= substr($yearlast, -2);
        } else {
            $year = date('Y', strtotime($bill->start_date));
            $year = $year - 1;
				$year= substr($year, -2);
            $yearlast = date('Y', strtotime($bill->start_date));
			$yearlast= substr($yearlast, -2);
        }

        $billyear = date('Y', strtotime($bill->start_date));
        $newyear = $billyear + 1;
        // Defaulted: assigned only inside the if below, so a missing outlet
        // left it undefined - a PHP 8 warning, and therefore a 500.
        $bill_prefix = 'B'; // default
        $outlet = Outlet::model()->findByPk($item['outlet_id']);
        if ($outlet) {
            if ($outlet->bill_prefix == '') {
                $bill_prefix = $outlet->bill_prefix;
            } else {
                $bill_prefix = 'B';
            }
        }
        $_billno = 'B2B ' . $year . '-' . $yearlast . '/' . $bill_prefix . '-' . $bill->id;
		
		
	}
	
       
		
                    $json_list[] = array(
                        'Bill_No' => $_billno,
                        'bill_date' => $date,
                        'customer' => $vendorName,
                        'taxable' => $taxable,
                        'gst' => $gst,
                        'cgst_per' => $item['cgst_per'],
                        'Sgst_per' => $item['sgst_per'],
                       'cess_per'=>$item['cess_per'],
                        // 'Cess_per' => $row13['tax_val3'] ,
                        'igst_per' => $item['igst_per'],
                        'cgst' => $cgst,
                        'sgst' => $sgst,
                       'cess'=>$taxable * ($item['cess_per'] * 0.01),
                        'igst' => $igst,
                        'amount' => $total_amt
                    );
                }
            }
        // }

        if (! empty($json_list)) {
            $arr['status'] = 'OK';

            $arr['grouptax'] = $json_list;
        } else {
            $arr['message'] = 'data not available';
        }
       

        $this->sendJSONResponse($arr);
    }
	
	 public function actionb2bsales($date = null)
    {
        $arr = array(
            'controller' => $this->id,
            'action' => $this->action->id,
            'status' => 'NOK'
        );
		
      	$headers = getallheaders ();

        $json_list = array();
        $i = 1;
        $current_month = date('m') - 1;
        if (date('m') == '01') {
            $current_month = 12;
        }
        if ($current_month == 12) {
            $year = date('Y') - 1;
        } else {
            $year = date('Y');
        }
        $total_days = date("t", mktime(0, 0, 0, date("n") - 1));

   
$criteria = new CDbCriteria ();
			$criteria1 = new CDbCriteria();
// Bound, not concatenated: $date arrives from the URL. And with no date
// at all this built date(start_date) = "", which MySQL 5.7 warned about
// and MySQL 8 rejects outright (error 1525) - the same fault that took
// tally/cashsale down. An empty date can match nothing, so say so.
if ($date === null || $date === '') {
	$arr['message'] = 'data not available';
	$this->sendJSONResponse($arr);
	return;
}
$criteria1->addCondition('date(start_date) = :d');
$criteria1->params[':d'] = $date;
$criteria1->order = 'id asc';
$orders = B2bPurchaseBill::model()->findAll($criteria1);
$purchase_bill_ids = array();
            if ($orders) {
                foreach ($orders as $_orders) {
                 $purchase_bill_ids[] = $_orders->id;  
                }
				// die;
            }
				$criteria->addInCondition('purchase_bill_id', $purchase_bill_ids);
			
			// no ORDER BY in the original; MySQL 8 does not sort implicitly
			$criteria->order = 'id asc';
			$B2bPurchaseBillDetail = B2bPurchaseBillDetail::model()->findAll($criteria);
			
			
			foreach($B2bPurchaseBillDetail as $_B2bPurchaseBillDetail){
				 $json_list[] = array(
                        
                        'id' => $_B2bPurchaseBillDetail->id,
                        'billdate' => $_B2bPurchaseBillDetail->getOrderBillDate(),
                        'billno' => $_B2bPurchaseBillDetail->getOrderBillNo(),
                        'customer' => $_B2bPurchaseBillDetail->getVendorName(),
                        'state' => $_B2bPurchaseBillDetail->getStateName(),
                        'statecode' => $_B2bPurchaseBillDetail->purchaseBill->vendor->state_id ,
                        'itemname' => $_B2bPurchaseBillDetail->getItemName(),
					
                        'hsn_code' => $_B2bPurchaseBillDetail->hsn_code,
                       'qty' => $_B2bPurchaseBillDetail->approved_qty,
                        'barcode' => $_B2bPurchaseBillDetail->itemDetail->bar_code,
                        'employee' => $_B2bPurchaseBillDetail->createduser(),
                        'mrp' => $_B2bPurchaseBillDetail->mrp,
                        'taxable' => $_B2bPurchaseBillDetail->getsaleTaxableAmount() - ($_B2bPurchaseBillDetail->discount_amt1 + $_B2bPurchaseBillDetail->discount_amt),
                        'tax_per' => $_B2bPurchaseBillDetail->getTotalGstPer(),
                        'cgst_per' => $_B2bPurchaseBillDetail->getTaxPercentage("cgst_per"),
                        'sgst_per' => $_B2bPurchaseBillDetail->getTaxPercentage("sgst_per"),
                        'igst_per' => $_B2bPurchaseBillDetail->getTaxPercentage("igst_per"),
                        'cess_per' => $_B2bPurchaseBillDetail->getTaxPercentage("cess_per"),
						
                       'cgst_amt' => $_B2bPurchaseBillDetail->cgst_amt,
                        'sgst_amt' => $_B2bPurchaseBillDetail->sgst_amt,
                        'igst_amt' => $_B2bPurchaseBillDetail->igst_amt,
                        'cess_amt' => $_B2bPurchaseBillDetail->cess_amt,
                        'totalamount' => $_B2bPurchaseBillDetail->amount,
                        // 'employee' => $_B2bPurchaseBillDetail->createduser(),
                        // 'employee' => $_B2bPurchaseBillDetail->createduser(),
                        // 'employee' => $_B2bPurchaseBillDetail->createduser(),
                        // 'id' => $_B2bPurchaseBillDetail->id,
                        // 'id' => $_B2bPurchaseBillDetail->id,
                        // 'id' => $_B2bPurchaseBillDetail->id,
                        // 'id' => $_B2bPurchaseBillDetail->id,
                        // 'id' => $_B2bPurchaseBillDetail->id,
                        // 'id' => $_B2bPurchaseBillDetail->id,
                        // 'id' => $_B2bPurchaseBillDetail->id,
                        // 'id' => $_B2bPurchaseBillDetail->id,
                        // 'id' => $_B2bPurchaseBillDetail->id
                    );
			}
        // }

        if (! empty($json_list)) {
            $arr['status'] = 'OK';

            $arr['sales'] = $json_list;
        } else {
            $arr['message'] = 'data not available';
        }
       

        $this->sendJSONResponse($arr);
    }
	
}