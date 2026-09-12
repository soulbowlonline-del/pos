<?php


 
/**
 * @property integer $id
 * @property integer $user_id
 * @property string $computer_name
 * @property string $user_email
 * @property integer $item_id
 * @property string $bar_code
 * @property integer $is_coupon
 * @property integer $qty
 * @property integer $sale_rate
 * @property integer $base_price
 * @property integer $mrp
 * @property string $item_detail
 * @property datetime $created_at
 */
Yii::import('application.models._base.BaseScannedItems');
class ScannedItems extends BaseScannedItems
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function getScannedItemsColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'username',
					'user_email',
					'computer_name',
					'item',
					'bar_code',
					'quantity',
					'sale_rate',
					'base_price',
					'mrp',
					'created_at',
			);
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'username') {
					$columns [] = array (
							'label' => 'Username',
							'value' => function ($data) {
								return isset ( $data->createUser ) ? $data->createUser : "";
							} 
					);
				}
				else if ($select == 'user_email') {
					$columns [] = array (
							'label' => 'User Email',
							'value' => function ($data) {
								return $data->user_email;
							} 
					);
				}
				else if ($select == 'computer_name') {
					$columns [] = array (
							'label' => 'Computer Name',
							'value' => function ($data) {
								return $data->computer_name;
							} 
					);
				}
				else if ($select == 'item') {
					$columns [] = array (
							'label' => 'Item',
							'value' => function ($data) {
								return isset ( $data->getItemDetail ) ? $data->getItemDetail ? $data->getItemDetail->item : "":"";
							} 
					);
				} else if ($select == 'bar_code') {
					$columns [] = array (
							'label' => 'Bar Code',
							'value' => function ($data) {
								return $data->bar_code;
							} 
					);
				} else if ($select == 'quantity') {
					$columns [] = array (
							'label' => 'Quantity',
							'value' => function ($data) {
								return $data->qty;
							} 
					);
				} 
				else if ($select == 'sale_rate') {
					$columns [] = array (
							'label' => 'Sale Rate',
							'value' => function ($data) {
								return $data->sale_rate;
							} 
					);
				}
				else if ($select == 'base_price') {
					$columns [] = array (
							'label' => 'Base Price',
							'value' => function ($data) {
								return $data->base_price;
							} 
					);
				}
				else if ($select == 'mrp') {
					$columns [] = array (
							'label' => 'MRP',
							'value' => function ($data) {
								return $data->mrp;
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

}