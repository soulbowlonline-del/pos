<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property double $tax_val1
 * @property double $tax_val2
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseTax');
class Tax extends BaseTax
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public static function getHsnCodeList(){
		$list = [];
		$taxes = Tax::model()->findAllByAttributes(array('status'=>Tax::STATUS_ACTIVE));
		if($taxes){
			foreach($taxes as $tax){
				$list[$tax->id] = $tax->hrn_code;
			}
		}
		return $list;
	}
	public function setAllValues($rows) {
	
		$output = 0;
		$count = count($rows);
	
			
		if ($count > 1) {
	
			$o = explode(',', $rows[0]);
			$arrays = array_flip($o);
			$set = true;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				for ($i = 1; $i < $count; $i++) {
					$tax_values = explode(',', $rows[$i]);
	
	
					$tax = new Tax();
	
					if (isset($arrays['Title']) || isset($arrays['ï»¿"Title"']) || isset($arrays['¥éË"Title"'])) {
	
						if (isset($arrays['Title'])) {
							$tax->title = $tax_values[$arrays['Title']];
	
						} else if(isset($arrays['ï»¿"Title"'])) {
							$tax->title = $tax_values[$arrays['ï»¿"Title"']];
						}else{
							$tax->title = $tax_values[$arrays['¥éË"Title"']];
						}
					}
	
	
	
					if (isset($arrays['Total Tax(%age)'])) {
						$tax->hrn_code =$tax_values[$arrays['Hrn Code']];
					}
					if (isset($arrays['CGST (%age)'])) {
						$tax->tax_val1 =$tax_values[$arrays['CGST (%age)']];
					}
					if (isset($arrays['SGST (%age)'])) {
						$tax->tax_val2 =$tax_values[$arrays['SGST (%age)']];
					}
					if (isset($arrays['CESS (%age)'])) {
						$tax->tax_val3 =$tax_values[$arrays['CESS (%age)']];
					}
	
					if ($tax->save()) {
	
							
					} else {
						print_R($tax->getErrors());
						exit;
						$set = false;
					}
				}
				if ($set == true) {
					$transaction->commit();
					return 1;
				}
			} catch (Exception $e) {
				$transaction->rollback();
			}
		}
		return $output;
	}
	public function getColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'title' ,
					'hrn_code' ,
					'tax_val1' ,
					'tax_val2' ,
					'tax_val3' ,
			
			);
		}
	
		if ($selected) {
			foreach ( $selected as $select ) {
				
					$columns [] = $select;
				
			}
		}
	
		return $columns;
	}
	public function getPBillAmount($poid,$id){
		$amount = '0.00';
		$purchaseBillDetails = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$poid,
				'tax_id'=>$id
		));
		if($purchaseBillDetails){
			foreach($purchaseBillDetails as $purchaseBillDetail){
				if($purchaseBillDetail->is_free == 0){
				$amount = $amount + (($purchaseBillDetail->approved_qty * $purchaseBillDetail->price)-($purchaseBillDetail->discount_amt+$purchaseBillDetail->discount_amt1));
				}
			}
				
		}
		return $amount;
	
	}
	public function getPBillCgstAmount($poid,$id,$col){
		$amount = '0.00';
		$purchaseBillDetails = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$poid,
				'tax_id'=>$id
		)); 
		if($purchaseBillDetails){
			foreach($purchaseBillDetails as $purchaseBillDetail){
				if($purchaseBillDetail->getGSTTrue($poid) == true && $purchaseBillDetail->is_free == 0){
					$amount = $amount + $purchaseBillDetail->$col;
				}else{
					 $amount = $amount + $purchaseBillDetail->$col;
				}
			}
			
		}
		return $amount;
		
	}
	public function getChangePBillCgstAmount($poid,$id,$col,$detail_id,$tax_id,$purchase_bill_ids){
		//echo '<pre>';
//print_R($purchase_bill_ids);exit;
		//	$array = json_decode(json_encode($purchase_bill_ids), true);
		$amount = 0;
		if(!empty($purchase_bill_ids)){
			foreach($purchase_bill_ids as $purchase_bill){
				$array = json_decode(($purchase_bill), true);
				if(isset($array['cgst']) && ($col == 'cgst_amt')&&($array['tax_id'] == $id)&& ($array['is_free'] == 0)){
					$amount = $amount + $array['cgst'];
				}else if(isset($array['sgst']) && ($col == 'sgst_amt')&&($array['tax_id'] == $id)){
					$amount = $amount + $array['sgst'];
				}else if(isset($array['cess']) && ($col == 'cess_amt')&&($array['tax_id'] == $id)){
					$amount = $amount + $array['cess'];
				}else if(isset($array['igst']) && ($col == 'igst_amt')&&($array['tax_id'] == $id)){
					$amount = $amount + $array['igst'];
				}
			}
		}
		return $amount;
	
	
	}
	public function getChangePBillAmount($poid,$id,$col,$detail_id,$tax_id,$purchase_bill_ids){
	//	$array = json_decode(json_encode($purchase_bill_ids), true);
		$amount = 0;
		if(!empty($purchase_bill_ids)){
			foreach($purchase_bill_ids as $purchase_bill){
				$array = json_decode(($purchase_bill), true);
				if($array['tax_id'] == $id){
					$amount = $amount + (($array['qty'] * $array['price'])-($array['discount']+$array['discount1']));
				}
			}
		}
		return $amount;
		
	
	}
	
	
	  public function getPB2bBillCgstAmount($poid, $id, $col)
    {
        $amount = '0.00';
        $purchaseBillDetails = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $poid,
            'tax_id' => $id
        ));
        if ($purchaseBillDetails) {
            foreach ($purchaseBillDetails as $purchaseBillDetail) {
                if ($purchaseBillDetail->getGSTTrue($poid) == true && $purchaseBillDetail->is_free == 0) {
                    $amount = $amount + $purchaseBillDetail->$col;
                 
           }else{
					 $amount = $amount + $purchaseBillDetail->$col;
				}
        }
		}
        return $amount;
    }
	
	public function getPB2bBillAmount($poid, $id)
    {
        $amount = '0.00';
        $purchaseBillDetails = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $poid,
            'tax_id' => $id
        ));
        if ($purchaseBillDetails) {
            foreach ($purchaseBillDetails as $purchaseBillDetail) {
                if ($purchaseBillDetail->is_free == 0) {
					$amount = $amount + (($purchaseBillDetail->approved_qty * $purchaseBillDetail->price) - ($purchaseBillDetail->discount_amt + $purchaseBillDetail->discount_amt1));
		// $amount = $amount + ((($detail->amount)) - (($detail->getTaxPercentage("cgst_amt")) + ($detail->getTaxPercentage("sgst_amt")) + ($detail->getTaxPercentage("cess_amt")) + ($detail->getTaxPercentage("igst_amt"))));
                }
            }
        }
        return $amount;
    }
}