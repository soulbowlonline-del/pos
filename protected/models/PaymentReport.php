<?php

/**
 * Company: ToXSL Technologies Pvt. Ltd. < www.toxsl.com >
 * Author : Shiv Charan Panjeta < shiv@toxsl.com >
 */
 
/**
 * @property integer $id
 * @property integer $doc_no
 * @property string $chq_no
 * @property string $comp_code
 * @property string $house_bank
 * @property string $hb_acct
 * @property string $ben_acc_no
 * @property string $ref_no
 * @property double $amount
 * @property integer $vendor_id
 * @property string $run_date
 * @property string $inst_date
 * @property string $value_date
 * @property string $pay_type
 * @property string $pay_status
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BasePaymentReport');
class PaymentReport extends BasePaymentReport
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'doc_no',
					'pay_type',
					'chq_no',
					'comp_code',
					'house_bank',
					'hb_acct',
					'vendor_id',
					'ben_acc_no',
					'run_date',
					'inst_date',
					'value_date',
					'amount',
					'ref_no',
					'pay_status'
		
			);
		}
	
		if ($selected) {
			foreach ( $selected as $select ) {
	
				$columns [] = $select;
	
			}
		}
	
		return $columns;
	}
	public function setAllPayment($rows) {
		$output = 0;
		$count = count ( $rows );
	
		if ($count > 1) {
				
			$o = explode ( ',', $rows [0] );
			$arrays = array_flip ( $o );
			$set = true;
			$save = false;
		 	$transaction = Yii::app ()->db->beginTransaction ();
			try { 
			for ($i = 1; $i < $count; $i++) {
				$itemcat_values = explode(',', $rows[$i]);
				
				$report = new PaymentReport ();
				
				if (isset ( $arrays ['Doc No'] ) || isset ( $arrays ['ï»¿"Doc No"'] ) || isset ( $arrays ['¥éË"Doc No"'] )) {
						
					if (isset ( $arrays ['Doc No'] )) {
						$report->doc_no = $itemcat_values [$arrays ['Doc No']];
					} else if (isset ( $arrays ['ï»¿"Doc No"'] )) {
						$report->doc_no = $itemcat_values [$arrays ['ï»¿"Doc No"']];
					} else {
						$report->doc_no = $itemcat_values [$arrays ['¥éË"Doc No"']];
					}
				}
				if (isset ( $arrays ['Pay Type'] )) {
						
					$report->pay_type = $itemcat_values [$arrays ['Pay Type']];
				}
				if (isset ( $arrays ['Chq No'] )) {
						
					$report->chq_no = $itemcat_values [$arrays ['Chq No']];
				}
				if (isset ( $arrays ['Comp Code'] )) {
					
					$report->comp_code = $itemcat_values [$arrays ['Comp Code']];
				}
				if (isset ( $arrays ['House Bank'] )) {
						
					$report->house_bank = $itemcat_values [$arrays ['House Bank']];
				}
				if (isset ( $arrays ['HB Acct'] )) {
						
					$report->hb_acct = $itemcat_values [$arrays ['HB Acct']];
				}
				if (isset ( $arrays ['Vendor Name'] )) {
						
					$criteria = new CDbCriteria ();
					$criteria->compare ( 'name', $itemcat_values [$arrays ['Vendor Name']] );
					$vendor = Vendor::model ()->find ( $criteria );
					if ($vendor) {
						$report->vendor_id = $vendor->id;
					}
				}
	
				if (isset ( $arrays ['Bene Acct No'] )) {
						
					$report->ben_acc_no = $itemcat_values [$arrays ['Bene Acct No']];
				}
				if (isset ( $arrays ['Run Dt'] )) {
					
					$report->run_date = date ( 'Y-m-d', strtotime ( $itemcat_values [$arrays ['Run Dt']] ) );
				}
				if (isset ( $arrays ['Inst.Dt'] )) {
						
					$report->inst_date = date ( 'Y-m-d', strtotime ( $itemcat_values [$arrays ['Inst.Dt']] ) );
				}
				if (isset ( $arrays ['Value Dt'] )) {
						
					$report->value_date = date ( 'Y-m-d', strtotime ( $itemcat_values [$arrays ['Value Dt']] ) );
				}
				if (isset ( $arrays ['Amt'] )) {
						
					$report->amount = $itemcat_values [$arrays ['Amt']];
				}
				if (isset ( $arrays ['Status'] )) {
						
					$report->pay_status = $itemcat_values [$arrays ['Status']];
				}
				if (isset ( $arrays ['Trans.Ref.No'] )) {
						
					$report->ref_no = $itemcat_values [$arrays ['Trans.Ref.No']];
				}
				if (isset ( $arrays ['Vendor Name'] ) && isset ( $arrays ['Value Dt'] ) && isset ( $arrays ['Status'] ) && isset ( $arrays ['Amt'] )) {
				
					$criteria = new CDbCriteria ();
					$criteria->compare ( 'name', $itemcat_values [$arrays ['Vendor Name']] );
					$vendor = Vendor::model ()->find ( $criteria );
				
					if ($vendor) {
						$acc_no = $vendor->acc_no;
						if (isset ( $arrays ['Bene Acct No'] )) {
				
							if($itemcat_values [$arrays ['Bene Acct No']] == $acc_no){
								$bill = PurchaseBill::model()->findByAttributes(array('vendor_id'=>$vendor->id,
				'net_bill_amount'=>$itemcat_values [$arrays ['Amt']]
								));
								
								$chq_date = $bill->getChqDate();
								$value_date = date ( 'Y-m-d', strtotime ( $itemcat_values [$arrays ['Value Dt']] ) );
								
								if($bill){
									//if($itemcat_values [$arrays ['Status']] == 'L' && ($chq_date == $value_date)){
										//if($itemcat_values [$arrays ['Status']] == 'L' ){
										$bill->payment_done = PurchaseBill::PAYMENT_DONE;
										$bill->saveAttributes(array('payment_done'));
										$save = true;
										//}
									//}
								}
							}
						}
					}
				}
				 /* if($save == true){  */
				if ($report->save ()) {
				} else {
					print_R ( $report->getErrors () );
					exit ();
					$set = false;
				}
				/*  }else{
					$set = false;
				}  */
			}
				if ($set == true) {
					$transaction->commit ();
					return 1;
				}
 			} catch ( Exception $e ) {
 				$transaction->rollback ();
 			}
		}
		return $output;
	}
}