<?php


 
/**
 * @property integer $id
 * @property integer $req_qty
 * @property integer $bal_qty
 * @property integer $approved_qty
 * @property double $mrp
 * @property double $price
 * @property double $discount
 * @property double $discount_amt
 * @property double $vat
 * @property double $other_charge
 * @property double $amount
 * @property double $sale_rate
 * @property integer $status
 * @property integer $type_id
 * @property double $charge_amount
 * @property double $extra_charges
 * @property string $remarks
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 * @property integer $item_detail_id
 * @property integer $purchase_bill_id
 * @property integer $outlet_id
 */
Yii::import('application.models._base.BasePurchaseBillDetail');
class PurchaseBillDetail extends BasePurchaseBillDetail
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getPBillVendorOptions(){
		$list = [];
		$criteria = new CDbCriteria();
		$criteria->addCondition('status !='.PurchaseBill::STATUS_APPROVED);
		$mrss = PurchaseBill::model()->findAll($criteria);
		Yii::log ( CVarDumper::dumpAsString ( $mrss ), CLogger::LEVEL_WARNING, '$mrss' );
		if($mrss){
			foreach($mrss as $mrs){
				$create_time = date('d-m-Y',strtotime($mrs->create_time));
				$vendor = Vendor::model()->findByPk($mrs->vendor_id);
				if($vendor){
					//$list[$vendor->id] = $vendor->name.'('.$create_time.')';
					$list[$vendor->id] = $vendor->name;
				}
			}
		}
		asort($list);
			return $list;
		
	}
	public function getAllTaxOptions($id = null, $poid = null) {
		$taxType = null;
		$condition = array('status'=>Tax::STATUS_ACTIVE);
		if($poid) {
			$taxType = $this->getGstTrue($poid) == true ? 0 : 1;
			$condition['type_id'] = $taxType;
		}

		$list = array();
		$taxes = Tax::model()->findAllByAttributes($condition);
		if($taxes){
			foreach($taxes as $tax){
				$list[$tax->id] = $tax->title;
			}
		}
		return $list;
		if ($id == null)
			return $list;
			if (is_numeric ( $id ))
				return $list [$id];
				return $id;
	}
	
	public function toArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			$json_entry ['item'] = isset ( $model->item ) ? $model->item->title : '';
			$json_entry ['bar_code'] = isset ( $model->itemDetail ) ? $model->itemDetail->bar_code : '';
			$json_entry ['req_qty'] = isset ( $model->req_qty ) ? $model->req_qty : '';
			$json_entry ['mrp'] = isset ( $model->itemDetail ) ? $model->itemDetail->getItemDetailMrp() : '';
			$json_entry ['approved_qty'] = isset ( $model->approved_qty ) ? $model->approved_qty : '';
			$json_entry ['rec_qty'] = 0;
		}
		return $json_entry;
	}
	public function getPOBillOptions($id = null){
		$list = array();
		$user = Yii::app()->user->model;
		//$user = User::model()->findByPk($id);
		if($user){
			$role_id = $user->role_id;
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			
			if ($id != null) {
				if ($role_id == $role->id) {
					$criteria = new CDbCriteria();
					$criteria->addCondition('vendor_id ='.$id);
					$criteria->addCondition('status !='.PurchaseBill::STATUS_APPROVED);
					$polist = PurchaseBill::model()->findAll($criteria);
				}else{
					$criteria = new CDbCriteria();
					
					$criteria->addCondition('status !='.PurchaseBill::STATUS_APPROVED);
					$polist = PurchaseBill::model()->findAll($criteria);
				}
				if($polist){
					foreach($polist as $po){
						$list[$po->id] = $po->id;
					}
				}
			}
		}
		return $list;
	}
	public function getAllPOBillOptions($id = null){
		$list = array();
		$user = Yii::app()->user->model;
		if($user){
			$role_id = $user->role_id;
	
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			
			if ($id != null) {
				if ($role_id == $role->id) {
					$criteria = new CDbCriteria();
					$criteria->addCondition('vendor_id ='.$id);
					$criteria->addCondition('status !='.PurchaseBill::STATUS_APPROVED);
					$polist = PurchaseBill::model()->findAll($criteria);
					
				}else{
					$criteria = new CDbCriteria();
					
					$criteria->addCondition('status !='.PurchaseBill::STATUS_APPROVED);
					$polist = PurchaseBill::model()->findAll($criteria);
				}
				if($polist){
					foreach($polist as $po){
						$list[] = $po->id;
					}
				}
			}
		}
		return $list;
	}
	
	public function getVendorTAXNO(){
		$tax_no = '';
		if($this->purchaseBill){
			if($this->purchaseBill->vendor){
				$tax_no = $this->purchaseBill->vendor->tax_no;
			}
		}
		return $tax_no;
	}
	public function getColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			
		$selected = array (
					'date',
					'vendor',
					'hsn_code' ,
					'bill_no',
					'tax_no' ,
					'gst_per',
					'cgst_per',
					'sgst_per',
					'igst_per',
					'cess_per',
					'net_amount',
					'basic_value',
					'discount',
					'gst_amt' ,
					'cgst_amt',
					'sgst_amt',
					'igst_amt',
					'cess_amt',
					'grn_no' ,
					'scheme' ,
			);
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'date') {
					$columns [] = array (
							'label' => 'Date',
							'value' => function ($data) {
							return isset($data->purchaseBill)?$data->purchaseBill->end_date:"";
							}
							);
				} else if ($select == 'vendor') {
					$columns [] = array (
							'label' => 'Vendor',
							'value' => function ($data) {
							return isset ( $data->purchaseBill ) ? $data->purchaseBill->vendor: "";
							}
							);
				} else if ($select == 'hsn_code') {
					$columns [] = array (
							'label' => 'HSN Code',
							'value' => function ($data) {
							return isset($data->tax)?$data->tax->hrn_code:"";
							}
							);
				}else if ($select == 'bill_no') {
					$columns [] = array (
							'label' => 'Bill No',
							'value' => function ($data) {
							return isset ( $data->purchaseBill ) ? '"'.$data->purchaseBill->bill_no.'"': "";
							}
							);
				}  else if ($select == 'tax_no') {
					$columns [] = array (
							'label' => 'GST NO',
							'value' => function ($data) {
							return $data->getVendorTAXNO();
							}
							);
				}else if ($select == 'gst_per') {
					$columns [] = array (
							'label' => 'GST%',
							'value' => function ($data) {
							return $data->getTotalGstPer();
							}
							);
				}else if ($select == 'cgst_per') {
					$columns [] = array (
							'label' => 'CGST%',
							'value' => function ($data) {
							return $data->getTaxPercentage("cgst_per");
							}
							);
				} else if ($select == 'sgst_per') {
					$columns [] = array (
							'label' => 'SGST%',
							'value' => function ($data) {
							return $data->getTaxPercentage("sgst_per");
							}
							);
				} else if ($select == 'igst_per') {
					$columns [] = array (
							'label' => 'IGST%',
							'value' => function ($data) {
							return $data->getTaxIgstPercentage();
							}
							);
				} else if ($select == 'cess_per') {
					$columns [] = array (
							'label' => 'CESS%',
							'value' => function ($data) {
							return $data->getTaxPercentage("cess_per");
							}
							);
				} else if ($select == 'net_amount') {
					$columns [] = array (
							'label' => 'Net Amount',
							'value' => function ($data) {
							return isset ( $data->purchaseBill ) ? $data->purchaseBill->net_bill_amount: "";
						
							}
							);
					
				}else if ($select == 'basic_value') {
					$columns [] = array (
							'label' => 'Basic Value',
							'value' => function ($data) {
							return $data->getBasicAmount();
							}
							);
					
				}else if ($select == 'discount') {
					$columns [] = array (
							'label' => 'Discount',
							'value' => function ($data) {
							return $data->getMainDiscount();
							}
							);
				}else if ($select == 'gst_amt') {
					$columns [] = array (
							'label' => 'GST',
							'value' => function ($data) {
							return $data->getTotalGstAmt();
							}
							);
				}else if ($select == 'cgst_amt') {
					$columns [] = array (
							'label' => 'CGST',
							'value' => function ($data) {
							return $data->getCgstAmount();
							}
							);
				}else if ($select == 'sgst_amt') {
					$columns [] = array (
							'label' => 'SGST',
							'value' => function ($data) {
							return $data->getSgstAmount();
							}
							);
				}else if ($select == 'igst_amt') {
					$columns [] = array (
							'label' => 'IGST',
							'value' => function ($data) {
							return $data->getIgstAmount();
							}
							);
				}  else if ($select == 'cess_amt') {
					$columns [] = array (
							'label' => 'CESS Amount',
							'value' => function ($data) {
							return  $data->getCessAmount();
							}
							);
				}else if ($select == 'grn_no') {
					$columns [] = array (
							'label' => 'GRN NUMBER',
							'value' => function ($data) {
							return isset ( $data->purchaseBill ) ? 'Gr-'.$data->purchaseBill->grn_refrence_no: "";
							//return 'Gr-'.$data->purchase_bill_id;
							}
							);
				}else if ($select == 'scheme') {
					$columns [] = array (
							'label' => 'SCHEME AND DISCOUNT',
							'value' => function ($data) {
							return $data->getSchemeDiscount();
							}
							);
				}
				else {
					$columns [] = $select;
				}
			}
		}
	
		return $columns;
	}
	
	public function getTotalGstPer(){
		$data = $this;
		$cgst = $data->getTaxPercentage("cgst_per");
		$sgst = $data->getTaxPercentage("sgst_per");
		$cess = $data->getTaxPercentage("cess_per");
		$igst = $data->getTaxPercentage("igst_per");
		$total = $cgst + $sgst;
		return $total;
	}
	public function getTotalGstAmt(){
		$total = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$cgst = $detail->getTaxPercentage("cgst_amt");
				$sgst = $detail->getTaxPercentage("sgst_amt");
				$cess = $detail->getTaxPercentage("cess_amt");
				$igst = $detail->getTaxPercentage("igst_amt");
				$total = $total + ($cgst + $sgst);
			}
		}
		return $total;
	
	
	}
	public function getGstTrue($poid){
		$gst = true;
		if($poid){
			$mrs = PurchaseBill::model()->findByAttributes(array('id'=>$poid));
			if($mrs){
				$outlet = Outlet::model()->findByPk($mrs->outlet_id);
				if($outlet){
					$vendor = Vendor::model()->findByPk($mrs->vendor_id);
					if($vendor->state_id != $outlet->state_id){
						$gst = false;
					}
				}
			}
		}
		//Yii::log ( CVarDumper::dumpAsString ( $gst ), CLogger::LEVEL_WARNING, '$$gst' );
		return $gst;
	}
	
	// public function getTaxPercentage($col){
		// $checkgst = true;
		// if($col == 'igst_per' || $col == 'igst_amt' ){
		// $igst=array();
			// $checkgst = false;
				// $criteria1 = new CDbCriteria;
		// $criteria1->addCondition('id ='.$this->tax_id);
		// $details= Tax::model()->find($criteria1);
		// $igst=$details->tax_val4;
		// if($this->getIgstAmount()){		
			// return $igst;
			// }
		// }else{
			// $checkgst = false;
		// }
		// if($this->getGstTrue($this->purchase_bill_id) == $checkgst){
			// return $this->$col;
		// }else{
			// if($col == 'cess_per' || $col == 'cess_amt'){
				  // return $this->$col;
			
			// }else{
				
            // return '0.00';
			// }
			
		// }
		
		
	// }
	
	
	 public function getTaxIgstPercentage(){
		 
		 $criteria1 = new CDbCriteria;
		$criteria1->addCondition('id ='.$this->tax_id);
		$details= Tax::model()->find($criteria1);
		$igst=$details->tax_val4;
		 if($igst){		
			return $igst;
			}
		else{
			return '0.00';
		}
	 }
	
	    public function getTaxPercentage($col)
    {
        $checkgst = true;
	
        if ($col == 'igst_per' || $col == 'igst_amt') {
            $checkgst = false;
        }
        if ($this->getGstTrue($this->purchase_bill_id) == $checkgst) {
            return $this->$col;
        } else {
             if($col == 'cess_per' || $col == 'cess_amt'){
				  return $this->$col;
			}else{
				
            return '0.00';
			}
        }
    }
	public  function getDetailGstTrue(){
		$gst = false;

		Yii::log ( CVarDumper::dumpAsString ( $gst ), CLogger::LEVEL_WARNING, '$$gst' );
		
		return $gst;
	}
	
	public function getMainDiscount(){
		$amount = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$amount= $amount + $detail->discount_amt;
			}
		}
		return '0';
	}
	/* public function getSchemeDiscount(){
		$amount = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$amount= $amount + $detail->discount1;
			}
		}
		return $amount;
	} */
	public function getSchemeDiscount(){
		$amount = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$purchaseBill = PurchaseBill::model()->findByPk($purchase_bill_id);
		$criteria = new CDbCriteria();
		$criteria->addCondition('purchase_bill_id ='.$purchase_bill_id);
		$criteria->order = 'id desc';
		$criteria->limit = '1';
		$criteria->group = 'purchase_bill_id,tax_id';
		$detail = PurchaseBillDetail::model()->find($criteria);
		if($detail->id == $this->id){
			$amount = $purchaseBill->bill_other_discount;
		}
		return $amount;
	}
	public function getCgstAmount(){
		$amount = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$cgst = $detail->getTaxPercentage("cgst_amt");
				$amount= $amount + $cgst;
			}
		}
		return $amount;
	}
	public function getSgstAmount(){
		$amount = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$sgst = $detail->getTaxPercentage("sgst_amt");
				$amount= $amount + $sgst;
			}
		}
		return $amount;
	}
	public function getCessAmount(){
		$amount = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$cess = $detail->getTaxPercentage("cess_amt");
				$amount= $amount + $cess;
			}
		}
		return $amount;
	}
	public function getIgstAmount(){
		$amount = 0;
		$igstTaxes = array(2,3,16,17,18);
		if (in_array($this->tax_id, $igstTaxes)) {
		  $purchaseBill = PurchaseBill::model()->findByPk($this->purchase_bill_id);
			if ($purchaseBill) {
				return $purchaseBill->tax_amount;
			}
		}
		
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$igst = $detail->igst_amt;
				$amount= $amount + $igst;
			}
		}
		return $amount;
	}
	public function getNetAmount(){
		$amount = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$amount= $amount + $detail->amount;
			}
		}
		return $amount;
	}
	
	public function getBasicAmount(){
		$amount = 0;
		$purchase_bill_id = $this->purchase_bill_id;
		$details = PurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchase_bill_id,
				'tax_id'=>$this->tax_id
		));
		if($details){
			foreach($details as $detail){
				$amount= $amount + ((($detail->amount)) -(( $detail->getTaxPercentage("cgst_amt"))+( $detail->getTaxPercentage("sgst_amt"))+( $detail->getTaxPercentage("cess_amt"))+( $detail->getTaxPercentage("igst_amt"))));
				if($purchase_bill_id == '127'){
				Yii::log ( CVarDumper::dumpAsString ( $detail->getTaxPercentage("cgst_amt") ), CLogger::LEVEL_WARNING, '$detail->getCgstAmount()' );
				Yii::log ( CVarDumper::dumpAsString ( $detail->id ), CLogger::LEVEL_WARNING, '$detail' );
				Yii::log ( CVarDumper::dumpAsString ( $amount ), CLogger::LEVEL_WARNING, '$amount' );
				}
			}
		}
		return $amount;
	}
	
		public function toArray1($saleTax = false) {
		$model = $this;
		$bill = $this;
		$json_entry = null;
		if ($model) {
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			$json_entry ['Date'] = isset($model->purchaseBill)?$model->purchaseBill->end_date:"";
	$json_entry ['Vendor'] = isset ( $model->purchaseBill ) ? $model->purchaseBill->vendor->name: "";
	$json_entry ['HSN Code'] = isset($model->tax)?$model->tax->hrn_code:"";
	$json_entry ['Bill No'] = isset ( $model->purchaseBill ) ? $model->purchaseBill->bill_no: "";
	$json_entry ['GST NO'] = $model->getVendorTAXNO();
	$json_entry ['GST Rate'] = $model->getTotalGstPer();
	$json_entry ['CGST Rate'] =  $model->getTaxPercentage("cgst_per");
	$json_entry ['SGST Rate'] = $model->getTaxPercentage("sgst_per");
		
	$json_entry ['CESS Rate'] =   $model->getTaxPercentage("cess_per");
	$json_entry ['IGST Rate'] = $model->getTaxPercentage("igst_per");
	$json_entry ['Net Amount'] = isset ( $model->purchaseBill ) ? $model->purchaseBill->net_bill_amount: "";
	$json_entry ['Basic Value'] = $model->getBasicAmount();
	$json_entry ['Discount'] =  $model->getMainDiscount();
	$json_entry ['GST'] = $model->getTotalGstAmt();
		$json_entry ['CGST'] = $model->getCgstAmount();
	$json_entry ['SGST'] =   $model->getSgstAmount();
	$json_entry ['IGST'] = $model->getIgstAmount();
	$json_entry ['CESS'] = $model->getCessAmount();
	$json_entry ['GRN NUMBER'] =  isset ( $model->purchaseBill ) ? 'Gr-'.$model->purchaseBill->grn_refrence_no: "";
	$json_entry ['SCHEME AND DISCOUNT'] = isset ( $model->purchaseBill ) ? $model->getSchemeDiscount(): "";
			 // not needed for tally reports
			// if ($saleTax && $model->sale_tax_id > 0 && $model->tax_id != $model->sale_tax_id) {
			// 	$json_entry ['GST Rate'] = $model->sale_cgst_per + $model->sale_sgst_per;
			// 	$json_entry ['CGST Rate'] =  $model->sale_cgst_per;
			// 	$json_entry ['SGST Rate'] = $model->sale_sgst_per;
			// 	$json_entry ['CESS Rate'] =   $model->sale_cess_per;
			// 	$json_entry ['IGST Rate'] = $model->sale_igst_per;
			// 	$json_entry ['GST'] = $model->sale_cgst_amt + $model->sale_sgst_amt;
			// 	$json_entry ['CGST'] = $model->sale_cgst_amt;
			// 	$json_entry ['SGST'] =   $model->sale_sgst_amt;
			// 	$json_entry ['IGST'] = $model->sale_igst_amt;
			// 	$json_entry ['CESS'] = $model->sale_cess_amt;
			// }
	}
		return $json_entry;
	}
	
	public function gethsncode(){
		if($this->hsn_code != null ){
			return $this->hsn_code;
		}else{
			return $this->item->hsn_code;
		}
	}

	public static function getLatestSaleTaxIdByItemDetailId($itemDetailId) {
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_detail_id ='.$itemDetailId);
		$criteria->order = 'id desc';
		$criteria->limit = '1';
		$detail = PurchaseBillDetail::model()->find($criteria);
		if ($detail) {
			return $detail->sale_tax_id;
		}
		return 0;
	}
}