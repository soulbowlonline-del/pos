<?php

/**
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $contact_person
 * @property string $person_designation
 * @property integer $contact_no
 * @property integer $secondary_contact_no
 * @property integer $whatsapp_no
 * @property string $primary_address
 * @property string $secondary_address
 * @property string $tax_no
 * @property integer $is_local_vendor
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $city_id
 * @property integer $state_id
 * @property integer $country_id
 * @property integer $outlet_id
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import ( 'application.models._base.BaseVendor' );
class Vendor extends BaseVendor {
	public $username;
	public $email;
	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}
	public static function getOutletName($outlet_id) {
		$outlet = Outlet::model ()->findByPk ( $outlet_id );
		if ($outlet) {
			return $outlet->title;
		}
		return '';
	}
	public static function getVendorEmail($id) {
		$vendor = Vendor::model ()->findByPk ( $id );
		if ($vendor) {
			$user = User::model ()->findByPk ( $vendor->create_user_id );
			if ($user)
				return $user->email;
		}
		return '';
	}
	public static function getVendorUsername($id) {
		$vendor = Vendor::model ()->findByPk ( $id );
		if ($vendor) {
			$user = User::model ()->findByPk ( $vendor->create_user_id );
			if ($user)
				return $user->username;
		}
		return '';
	}
	public static function getStateName($id) {
		$state = State::model ()->findByPk ( $id );
		if ($state) {
			
			if ($state)
				return $state->title;
		}
		return '';
	}
	public static function getCityName($id) {
		$state = City::model ()->findByPk ( $id );
		if ($state) {
			
			if ($state)
				return $state->title;
		}
		return '';
	}
	public static function getCountryName($id) {
		$state = Country::model ()->findByPk ( $id );
		if ($state) {
			
			if ($state)
				return $state->title;
		}
		return '';
	}
	public static function getDesignationName($id) {
		$designation = Designation::model ()->findByPk ( $id );
		if ($designation) {
			
			if ($designation)
				return $designation->title;
		}
		return '';
	}
	public static function getShiftName($id) {
		$empshift = EmpShift::model ()->findByAttributes ( array (
				'emp_id' => $id 
		) );
		if ($empshift) {
			$shift = Shift::model ()->findByAttributes ( array (
					'id' => $empshift->shift_id 
			) );
			if ($shift)
				return $shift->title;
		}
		return '';
	}
	public function setAllValues($rows) {
		$output = 0;
		$count = count ( $rows );
		
		if ($count > 1) {
			
			$o = explode ( ',', $rows [0] );
			$arrays = array_flip ( $o );
			$set = true;
			$transaction = Yii::app ()->db->beginTransaction ();
			try {
				for($i = 1; $i < $count; $i ++) {
					$itemcat_values = explode ( ',', $rows [$i] );
					
					$usermodel = null;
					
					$vendor = new Vendor ();
					
					if (isset ( $arrays ['Name'] ) || isset ( $arrays ['﻿"Name"'] ) || isset ( $arrays ['���"Name"'] )) {
						
						if (isset ( $arrays ['Name'] )) {
							$vendor->name = $itemcat_values [$arrays ['Name']];
						} else if (isset ( $arrays ['﻿"Name"'] )) {
							$vendor->name = $itemcat_values [$arrays ['﻿"Name"']];
						} else {
							$vendor->name = $itemcat_values [$arrays ['���"Name"']];
						}
					}
					
					if (isset ( $arrays ['Tax No'] )) {
						
						$vendor->tax_no = $itemcat_values [$arrays ['Tax No']];
					}
					
					if (isset ( $arrays ['Is Local Vendor'] )) {
						if ($itemcat_values [$arrays ['Is Local Vendor']] == 'Yes')
							$vendor->is_local_vendor = 1;
					} else {
						$vendor->is_local_vendor = 0;
					}
					
					if (isset ( $arrays ['Contact No'] )) {
						Yii::log ( CVarDumper::dumpAsString ( $itemcat_values [$arrays ['Contact No']] ), CLogger::LEVEL_WARNING, '$Contact No' );
						$vendor->contact_no = $itemcat_values [$arrays ['Contact No']];
					}
					if (isset ( $arrays ['Office Contact No'] )) {
						
						$vendor->secondary_contact_no = $itemcat_values [$arrays ['Office Contact No']];
					}
					if (isset ( $arrays ['Contact Person'] )) {
						
						$vendor->contact_person = $itemcat_values [$arrays ['Contact Person']];
					}
					
					if (isset ( $arrays ['Person Designation'] )) {
						
						$vendor->person_designation = $itemcat_values [$arrays ['Person Designation']];
					}
					if (isset ( $arrays ['Opening Balance'] )) {
						
						$vendor->opening_balance = $itemcat_values [$arrays ['Opening Balance']];
					}
					if (isset ( $arrays ['Payment Days'] )) {
						
						$vendor->payment_days = $itemcat_values [$arrays ['Payment Days']];
					}
					if (isset ( $arrays ['Primary Address'] )) {
						$address =  str_replace(";",",",$itemcat_values [$arrays ['Primary Address']]);
						$vendor->primary_address = $address;
					}
					if (isset ( $arrays ['Secondary Address'] )) {
						$address =  str_replace(";",",",$itemcat_values [$arrays ['Secondary Address']]);
						$vendor->secondary_address = $address;
					}
					Yii::log ( CVarDumper::dumpAsString ( $itemcat_values ), CLogger::LEVEL_WARNING, '$itemcat_values' );
					/*
					 * if (isset($arrays['Remarks'])) {
					 *
					 * $vendor->remarks = $itemcat_values[$arrays['Remarks']];
					 * }
					 */
					
					if (isset ( $arrays ['City'] )) {
						Yii::log ( CVarDumper::dumpAsString ($itemcat_values [$arrays ['City']]), CLogger::LEVEL_WARNING, '$city_id' );
						$criteria = new CDbCriteria ();
						$criteria->compare ( 'title', $itemcat_values [$arrays ['City']] );
						$city = City::model ()->find ( $criteria );
						if ($city) {
							$vendor->city_id = $city->id;
						}
					}
					Yii::log ( CVarDumper::dumpAsString ( $vendor->city_id ), CLogger::LEVEL_WARNING, '$vendor->city_id' );
					if (isset ( $arrays ['State'] )) {
						Yii::log ( CVarDumper::dumpAsString ( $itemcat_values [$arrays ['State']] ), CLogger::LEVEL_WARNING, '$State' );
						$criteria = new CDbCriteria ();
						$criteria->compare ( 'title', $itemcat_values [$arrays ['State']] );
						$state = State::model ()->find ( $criteria );
						if ($state) {
							$vendor->state_id = $state->id;
						}
					}
					if (isset ( $arrays ['Country'] )) {
					
						$criteria = new CDbCriteria ();
						$criteria->compare ( 'title', $itemcat_values [$arrays ['Country']] );
						$country = Country::model ()->find ( $criteria );
						if ($country) {
							$vendor->country_id = $country->id;
						}
					}
					
					$role = UserRole::model ()->findByAttributes ( array (
							'title' => 'Vendor' 
					) );
					if (isset ( $arrays ['Email'] )) {
						$criteria = new CDbCriteria ();
						if (isset ( $arrays ['Email'] )) {
							$criteria->compare ( 'email', $itemcat_values [$arrays ['Email']] );
						}
						$usermodel = User::model ()->find ( $criteria );
					}
					if ($usermodel == null) {
						if (isset ( $arrays ['Username'] )) {
							$criteria = new CDbCriteria ();
							if (isset ( $arrays ['Username'] )) {
								$criteria->compare ( 'username', $itemcat_values [$arrays ['Username']] );
							}
							$usermodel = User::model ()->find ( $criteria );
						}
					}
					if ($usermodel == null) {
						$usermodel = new User ();
					}
					$usermodel->full_name = $vendor->name;
					if (isset ( $arrays ['Password'] )) {
						
						$usermodel->password = md5 ( $itemcat_values [$arrays ['Password']] );
					}
					if (isset ( $arrays ['Email'] )) {
						
						$usermodel->email = $itemcat_values [$arrays ['Email']];
					}
					if (isset ( $arrays ['Username'] )) {
						
						$usermodel->username = $itemcat_values [$arrays ['Username']];
					}
					
					$usermodel->contact_no = $vendor->contact_no;
					$usermodel->address = $vendor->primary_address;
					$usermodel->country = $vendor->country_id;
					$usermodel->city = $vendor->city_id;
					$usermodel->state = $vendor->state_id;
					$usermodel->role_id = $role->id;
					$usermodel->state_id = 1;
					$usermodel->last_password_change = date ( "Y-m-d H:i:s" );
					
					if ($usermodel->save ()) {
						
						$vendordata = Vendor::model()->findByAttributes(array('create_user_id'=>$usermodel->id));
						if($vendordata != null){
							$vendor = $vendordata;
						}
						if (isset ( $arrays ['Outlet'] )) {
							$criteria = new CDbCriteria ();
							$criteria->compare ( 'title', $itemcat_values [$arrays ['Outlet']] );
							
							$outlet = Outlet::model ()->find ( $criteria );
							
							if ($outlet) {
								$vendor->outlet_id = $outlet->id;
							}else{
								$outlet = Outlet::model()->findByAttributes(array('status'=>Outlet::STATUS_ACTIVE));
								if($outlet){
									$vendor->outlet_id = $outlet->id;
								}
							}
						}else{
							$outlet = Outlet::model()->findByAttributes(array('status'=>Outlet::STATUS_ACTIVE));
							if($outlet){
								$vendor->outlet_id = $outlet->id;
							}
						}
						
						$vendor->create_user_id = $usermodel->id;
						Yii::log ( CVarDumper::dumpAsString ( $vendor ), CLogger::LEVEL_WARNING, '$vendor' );
						if ($vendor->save ()) {
						} else {
							print_R ( $vendor->getErrors () );
							exit ();
							$set = false;
						}
					} else {
						print_R ( $usermodel->getErrors () );
						exit ();
						$set = false;
					}
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
	public function getVendorWiseColumns($selectcolumns = array()){
		if(!empty($selectcolumns)){
			$selected = $selectcolumns;
		}else{
			$selected = array (
					'vendor' ,
					'amount',
	'purchase_amount'
	
			);
	
		}
	
		if($selected){
			foreach($selected as $select){
				if($select == 'vendor'){
					$columns[] = array (
							'label' => 'Vendor',
							'value' => function ($data) {
							return isset ( $data->name ) ? $data->name : "";
							}
							);
				}
				else if($select == 'amount'){
					$columns[] =array (
							'label' => 'Sale Amount',
							'value' => function ($data) {
							return $data->getVendorSaleTotalAmount ();
							}
							);
				}
				else if($select == 'purchase_amount'){
					$columns[] =array (
							'label' => 'Purchase Amount',
							'value' => function ($data) {
							return $data->getVendorPurchaseTotalAmount ();
							}
							);
				}
				else{
					$columns[] = $select;
				}
			}
		}
	
	
	
	
		return $columns;
	}
	public function getVendorItem_ids(){
		$item_ids = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('vendor_id ='.$this->id);
		$itemvendors = ItemVendor::model()->findAll($criteria);
		if($itemvendors){
			foreach($itemvendors as $itemvendor){
				$criteria = new CDbCriteria ();
				$criteria->order = 'id desc';
				$criteria->limit = '1';
				$criteria->addCondition ( 'item_detail_id =' . $itemvendor->item_detail_id );
				$selectvendor = ItemVendor::model ()->find( $criteria );
				if($selectvendor->vendor_id == $itemvendor->vendor_id){
				$item_ids[] = $itemvendor->item_detail_id;
				}
			}
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->id ), CLogger::LEVEL_WARNING, '$item_category_id' );
		Yii::log ( CVarDumper::dumpAsString ( $item_ids ), CLogger::LEVEL_WARNING, '$item_ids' );
		return $item_ids;
	}
	
	public function getVendorSaleTotalAmount(){
		$item_ids = $this->getVendorItem_ids();
		
		$total = 0;
		$criteria1 = new CDbCriteria();
		$criteria1->addInCondition('item_id', $item_ids);
		if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
			$criteria1->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
		}
		Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['vendor_end_date']), CLogger::LEVEL_WARNING, '$start_date' );
		Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['vendor_end_date']), CLogger::LEVEL_WARNING, '$end_date' );
		$orderitems = OrderItem::model()->findAll($criteria1);
		Yii::log ( CVarDumper::dumpAsString ( $orderitems), CLogger::LEVEL_WARNING, '$orderitems' );
		if($orderitems){
	
			foreach ($orderitems as $orderitem){
				$qty = $orderitem->qty;
				$refund = 0;
				$criteria = new CDbCriteria();
				$criteria->addCondition('order_id ='.$orderitem->order_id);
				if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
					$criteria->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
				}
				$orderRefund = OrderRefund::model()->find($criteria);
				if($orderRefund){
					$criteria3 = new CDbCriteria();
					$criteria3->addCondition('order_refund_id ='.$orderRefund->id);
					$criteria3->addCondition('item_detail_id ='.$orderitem->item_detail_id);
					if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
						$criteria3->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
					}
					$criteria3->addCondition('item_id ='.$orderitem->item_id);
					$criteria3->select = 'sum(total_amt) as total_amt';
					$orderRefundItem = OrderRefundItem::model()->find($criteria3);
					if($orderRefundItem){
						$refund = $refund + ($orderRefundItem->total_amt);
					}
					/* if($orderRefundItems){
					 foreach($orderRefundItems as $orderRefundItem){
					 $refund = $refund + ($orderRefundItem->total_amt);
					 }
	
	
						} */
	
				}
				$amt = ($orderitem->total_amt) - ($refund);
				$total = $total + $amt;
	
			}
		}
		return round($total);
	}
	public function getVendorSaleTaxAmount(){
		$item_ids = $this->getVendorItem_ids();
	
		$total = 0;
		$criteria1 = new CDbCriteria();
		$criteria1->addInCondition('item_id', $item_ids);
		if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
			$criteria1->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
		}
		Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['vendor_end_date']), CLogger::LEVEL_WARNING, '$start_date' );
		Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['vendor_end_date']), CLogger::LEVEL_WARNING, '$end_date' );
		$orderitems = OrderItem::model()->findAll($criteria1);
		Yii::log ( CVarDumper::dumpAsString ( $orderitems), CLogger::LEVEL_WARNING, '$orderitems' );
		if($orderitems){
	
			foreach ($orderitems as $orderitem){
				$qty = $orderitem->qty;
				$refund = 0;
				$criteria = new CDbCriteria();
				$criteria->addCondition('order_id ='.$orderitem->order_id);
				if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
					$criteria->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
				}
				$orderRefund = OrderRefund::model()->find($criteria);
				if($orderRefund){
					$criteria3 = new CDbCriteria();
					$criteria3->addCondition('order_refund_id ='.$orderRefund->id);
					$criteria3->addCondition('item_detail_id ='.$orderitem->item_detail_id);
					if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
						$criteria3->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
					}
					$criteria3->addCondition('item_id ='.$orderitem->item_id);
					$criteria3->select = 'sum(total_amt) as total_amt';
					$orderRefundItem = OrderRefundItem::model()->find($criteria3);
					if($orderRefundItem){
						$refund = $refund + ($orderRefundItem->tax_amt);
					}
					/* if($orderRefundItems){
					 foreach($orderRefundItems as $orderRefundItem){
					 $refund = $refund + ($orderRefundItem->total_amt);
					 }
	
	
					 } */
	
				}
				$amt = ($orderitem->tax_amount) - ($refund);
				$total = $total + $amt;
	
			}
		}
		return round($total);
	}
	public function getVendorPurchaseTotalAmount(){
		//$item_ids = $this->getVendorItem_ids();
		$amount = '0';
		$criteria1 = new CDbCriteria();
		//$criteria1->addInCondition('item_id', $item_ids);
		if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
			$criteria1->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
		}
		$criteria1->addCondition('vendor_id ='.$this->id);
		$criteria1->select = 'sum(net_bill_amount) as net_bill_amount';
		$orderitem = PurchaseBill::model()->find($criteria1);
		if($orderitem){
			$amount = $orderitem->net_bill_amount;
		}
		if($amount == ''){
			$amount = '0';
		}
		return $amount;
	}
	public function getVendorPurchaseTaxAmount(){
		//$item_ids = $this->getVendorItem_ids();
		$amount = '0';
		$criteria1 = new CDbCriteria();
		//$criteria1->addInCondition('item_id', $item_ids);
		if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
			$criteria1->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
		}
		$criteria1->addCondition('vendor_id ='.$this->id);
		$criteria1->select = 'sum(tax_amount) as tax_amount';
		$orderitem = PurchaseBill::model()->find($criteria1);
		if($orderitem){
			$amount = $orderitem->tax_amount;
		}
		if($amount == ''){
			$amount = '0';
		}
		return $amount;
	}
	/* public function getVendorPurchaseTaxAmount(){
		$item_ids = $this->getVendorItem_ids();
		$amount = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addInCondition('item_id', $item_ids);
		if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
			$criteria1->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
		}
		$criteria1->select = 'sum(cgst_amt+sgst_amt+cess_amt+igst_amt) as amount';
		$orderitem = PurchaseBillDetail::model()->find($criteria1);
		if($orderitem){
			$amount = $orderitem->amount;
		}
		if($amount == ''){
			$amount = '0';
		}
		return $amount;
	} */
	public function getVendorDiscountAmount(){
		
		$amount = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('vendor_id ='.$this->id);
		if((Yii::app()->session['vendor_start_date'] != '') && (Yii::app()->session['vendor_end_date'] != '')){
			$criteria1->addBetweenCondition('date(create_time)',Yii::app()->session['vendor_start_date'], Yii::app()->session['vendor_end_date']);
		}
		$criteria1->select = 'sum(total_discount+bill_other_discount) as total_discount';
		$orderitem = PurchaseBill::model()->find($criteria1);
		if($orderitem){
			$amount = $orderitem->total_discount;
		}
		if($amount == ''){
			$amount = '0';
		}
		return $amount;
	}
	
	public function getVendorPurchaseMargin()
	{
		$margin ='';
		$purchase_amt = $this->getVendorPurchaseTotalAmount();
		$sale_amt = $this->getVendorSaleTotalAmount();
		if($purchase_amt != 0){
		$margin = (($sale_amt - $purchase_amt)/$purchase_amt)*100;
		}
		return round($margin,2);
		
	}
	public function getCssClass()
	{
		$cssClass='';
	
		$purchase_amt = $this->getVendorPurchaseTotalAmount();
		$per_purchase_amt = (($this->getVendorPurchaseTotalAmount()) - (10/100) * ($this->getVendorPurchaseTotalAmount()));
		$sale_amt = $this->getVendorSaleTotalAmount();
		Yii::log ( CVarDumper::dumpAsString ( $this ), CLogger::LEVEL_WARNING, '$mrs' );
		if($sale_amt < $per_purchase_amt){
			$cssClass='mrsred';
		}
		return $cssClass;
	}
}