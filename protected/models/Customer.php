<?php


 
/**
 * @property integer $id
 * @property string $name
 * @property string $email
 * @property string $fax
 * @property string $address
 * @property integer $city_id
 * @property integer $state_id
 * @property integer $country_id
 * @property integer $zip_code
 * @property double $opening_balance
 * @property integer $credit_limit
 * @property integer $payment_days
 * @property integer $contact_no
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseCustomer');
class Customer extends BaseCustomer
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function toArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			$json_entry ['name'] = isset ( $model->name ) ? $model->name : '';
/* 			$json_entry ['email'] = isset ( $model->email ) ? $model->email : '';
			$json_entry ['fax'] = isset ( $model->fax ) ? $model->fax : '';
			$json_entry ['address'] = isset ( $model->address ) ? $model->address : '';
			$json_entry ['city'] = isset ( $model->city ) ? $model->city->title : '';
			$json_entry ['state'] = isset ( $model->state ) ? $model->state->title : '';
			$json_entry ['country'] = isset ( $model->country ) ? $model->country->title : '';
			$json_entry ['zip_code'] = isset ( $model->zip_code ) ? $model->zip_code : '';
			$json_entry ['opening_balance'] = isset ( $model->opening_balance ) ? $model->opening_balance : '';
			$json_entry ['credit_limit'] = isset ( $model->credit_limit ) ? $model->credit_limit : '';
			$json_entry ['payment_days'] = isset ( $model->payment_days ) ? $model->payment_days : '';
 */			$json_entry ['contact_no'] = isset ( $model->contact_no ) ? $model->contact_no : '';
			$json_entry ['loyalty'] = CustomerLoyalty::getOrCreateCustomerLoyalty($model->id)->asArray();
		/* 	$json_entry ['status'] = isset ( $model->status ) ? $model->status : '';
			$json_entry ['type_id'] = isset ( $model->type_id ) ? $model->type_id : '';
			$json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
			$json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
			$json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : ''; */
	
	
		}
		return $json_entry;
	}
	public function toArray1() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			$json_entry ['name'] = isset ( $model->name ) ? $model->name : '';
			$json_entry ['contact_no'] = isset ( $model->contact_no ) ? $model->contact_no : '';
			$json_entry ['is_enable_wa'] = isset ( $model->is_enable_wa ) ? $model->is_enable_wa : 0;
			$json_entry ['loyalty'] = CustomerLoyalty::getOrCreateCustomerLoyalty($model->id)->asArray();
			
	
	
		}
		return $json_entry;
	}
	public static function getUserByEmail($name)
	{
		$user = Customer::model()->findByAttributes(array('email'=>$name));
		return $user;
	}
	public static function getUserByContactNo($contact_no)
	{
		$duser = null;
		$user = Customer::model()->findByAttributes(array('contact_no'=>$contact_no));
		
		if($user)
		{
		if($user->contact_no != null){
			return $user;
		}
		}
		return $duser;
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
					$itemcat_values = explode(',', $rows[$i]);
	
	
					$customer= new Customer();
	
					if (isset($arrays['Name']) || isset($arrays['﻿"Name"']) || isset($arrays['���"Name"'])) {
	
						if (isset($arrays['Name'])) {
							$customer->name = $itemcat_values[$arrays['Name']];
	
						} else if(isset($arrays['﻿"Name"'])) {
							$customer->name = $itemcat_values[$arrays['﻿"Name"']];
						}else{
							$customer->name = $itemcat_values[$arrays['���"Name"']];
						}
					}
	
					if (isset($arrays['Opening Balance'])) {
	
						$customer->opening_balance =$itemcat_values[$arrays['Opening Balance']];
					}
						
					if (isset($arrays['Credit Limit'])) {
							
						$customer->credit_limit =$itemcat_values[$arrays['Credit Limit']];
					}
					if (isset($arrays['Payment Days'])) {
							
						$customer->payment_days =$itemcat_values[$arrays['Payment Days']];
					}
					if (isset($arrays['Email'])) {
							
						$customer->email = $itemcat_values[$arrays['Email']];
					}
					if (isset($arrays['Fax'])) {
							
						$customer->fax = $itemcat_values[$arrays['Fax']];
					}
					if (isset($arrays['Contact No'])) {
							
						$customer->contact_no = $itemcat_values[$arrays['Contact No']];
					}
					if (isset($arrays['Address'])) {
							
						$customer->address = $itemcat_values[$arrays['Address']];
					}
					if (isset($arrays['Zip Code'])) {
							
						$customer->zip_code = $itemcat_values[$arrays['Zip Code']];
					}
					if (isset($arrays['City'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['City']]);
						$city = City::model()->find($criteria);
						if($city){
							$customer->city_id =$city->id;
						}
					
					}
					if (isset($arrays['State'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['State']]);
						$state = State::model()->find($criteria);
						if($state){
							$customer->state_id =$state->id;
						}
							
					}
					if (isset($arrays['Country'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Country']]);
						$country = Country::model()->find($criteria);
						if($country){
							$customer->country_id =$country->id;
						}
							
					}
					
					if ($customer->save()) {
	
							
					} else {
						print_R($customer->getErrors());
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
	
	public function getColumns($selectcolumns = array()){
		if(!empty($selectcolumns)){
			$selected = $selectcolumns;
		}else{
		$selected = array('name',
				'opening_balance',
				'credit_limit',
				'payment_days',
				'email',
				'fax',
				'contact_no',
				'address','State', 'City','Country','zip_code');
		}
		
		if($selected){
			foreach($selected as $select){
				if($select == 'State'){
					$columns[] = array (
							
						'label' => 'State',
						'value' => function ($data) {
						return Vendor::getStateName ( $data->state_id );
						}
						);
				}
				else if($select == 'City'){
					$columns[] =array (
								'label' => 'City',
								'value' => function ($data) {
								return Vendor::getCityName ( $data->city_id );
								}
								);
				}
				else if($select == 'Country'){
					$columns[] = array (
										'label' => 'Country',
										'value' => function ($data) {
										return Vendor::getCountryName ( $data->country_id );
										}
										);
				}
				else{
					$columns[] = $select;
				}
				}
			}
		
	/* 	$columns[] =
					
				'name',
				'opening_balance',
				'credit_limit',
				'payment_days',
					
				'email',
				'fax',
				'contact_no',
				'address',
				array (
							
						'label' => 'State',
						'value' => function ($data) {
						return Vendor::getStateName ( $data->state_id );
						}
						),
						array (
								'label' => 'City',
								'value' => function ($data) {
								return Vendor::getCityName ( $data->city_id );
								}
								),
								array (
										'label' => 'Country',
										'value' => function ($data) {
										return Vendor::getCountryName ( $data->country_id );
										}
										),
										'zip_code' */
												
		
		return $columns;
	}

	
}