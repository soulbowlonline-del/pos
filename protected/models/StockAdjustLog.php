<?php

 
/**
 * @property integer $id
 * @property string $date
 * @property integer $item_detail_id
 * @property integer $item_id
 * @property double $mrp
 * @property integer $current_stock
 * @property integer $actual_stock
 * @property integer $adjusted
 * @property integer $outlet_id
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
Yii::import('application.models._base.BaseStockAdjustLog');
class StockAdjustLog extends BaseStockAdjustLog
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getColumns($selectcolumns = array()){
		if(!empty($selectcolumns)){
			$selected = $selectcolumns;
		}else{
			$selected = array (
					'date',
			    'remarks',
					'username',
					'item_id',
					'item_detail_id',
					'outlet_id',
					'mrp',
					'current_stock',
					'actual_stock',
					'adjusted',
					'amount'
	
			);
				
		}
	
		if($selected){
			foreach($selected as $select){
				if($select == 'username'){
					$columns[] = array (
							'label' => 'Username',
							'value' => function ($data) {
							return isset ( $data->createUser ) ? $data->createUser : "";
							}
							);
				}
				else if($select == 'item_id'){
					$columns[] = array (
							'label' => 'Item',
							'value' => function ($data) {
							return isset ( $data->item ) ? $data->item : "";
							}
							);
				}
				else if($select == 'remarks'){
				    $columns[] = array (
				        'label' => 'Remarks',
				        'value' => function ($data) {
				        return isset ( $data->remarks ) ? $data->remarks : "";
				        }
				        );
				}
				else if($select == 'item_detail_id'){
					$columns[] =array (
							'label' => 'Bar Code',
							'value' => function ($data) {
							return  isset ( $data->itemDetail ) ? $data->itemDetail : "";
							}
							);
				}
				else if($select == 'outlet_id'){
					$columns[] = array (
							'label' => 'Outlet',
							'value' => function ($data) {
							return  isset ( $data->outlet ) ? $data->outlet : "";
							}
							);
				}
				else if($select == 'amount'){
					$columns[] = array (
							'label' => 'Amount',
							'value' => function ($data) {
							return  $data->getAdjustedAmount();
							}
							);
				}
				else{
					$columns[] = $select;
				}
			}
		}
	
		/* 	$columns[] =
	
		array (
			
		'bill_no',
		'bill_date',
		array (
		'label' => 'Customer',
		'value' => function ($data) {
		return isset ( $data->customer ) ? $data->customer : "";
		}
		),
			
		'total_amt',
		'discount_amt',
		'paid_amt',
		array (
		'label' => 'Mode Of Payment',
		'value' => function ($data) {
		return Order::getPaymentTypeOptions ( $data->mode_of_payment );
		}
		),
		array (
		'label' => 'Mode Of Delivery',
		'value' => function ($data) {
		return Order::getDeliveryTypeOptions ( $data->mode_of_delivery );
		}
		),
		array (
		'label' => 'Order Type',
		'value' => function ($data) {
		return Order::getTypeOptions ( $data->type_id );
		}
		),
			
			
		array (
		'label' => 'Outlet',
		'value' => function ($data) {
		return isset ( $data->outlet ) ? $data->outlet : "";
		}
		)
		)*/
	
	
		return $columns;
	}
	
	public function getAdjustedAmount(){
		$amount = '0.00';
		$price = isset($this->item)?$this->item->purchase_price:$this->mrp;
		$amount = ($this->adjusted) * ($price);
		return $amount;
	}

	public function getUserNameById()
	{
		$user = User::model()->active()->findByAttributes(array ( 'id'=>$this->create_user_id));
		// print_r($user->full_name); die;
		return $user ? $user->full_name : '';
	}
}