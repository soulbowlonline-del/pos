<?php


 
/**
 * @property integer $id
 * @property integer $item_detail_id
 * @property integer $tax_id
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemTax');
class ItemTax extends BaseItemTax
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getItemName() {
		$title = '';
		$item_detail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
		if ($item_detail) {
			$item = Item::model ()->findByPk ( $item_detail->item_id );
			if ($item) {
				$title = $item->title;
			}
		}
		return $title;
	}
	public function getColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'bar_code' ,
					'item',
					'sale_rate',
					'tax' ,
					'hrn_code',
					'cgst_per',
					'cgst_amt',
					'sgst_per' ,
					'sgst_amt',
					//'igst_per',
					//'igst_amt',
					'cess_per',
					'cess_amt',
			
			);
		}
	
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'bar_code') {
					$columns [] = array (
							'label' => 'Bar Code',
							'value' => function ($data) {
							return isset($data->itemDetail)?$data->itemDetail->bar_code:"";
							}
							);
				} else if ($select == 'item') {
					$columns [] = array (
							'label' => 'Item',
							'value' => function ($data) {
							return $data->getItemName();
							}
							);
				} else if ($select == 'sale_rate') {
					$columns [] = array (
							'label' => 'sale_rate',
							'value' => function ($data) {
							return $data->getSaleRate();
							}
							);
				} else if ($select == 'tax') {
					$columns [] = array (
							'label' => 'Tax',
							'value' => function ($data) {
							return isset($data->tax)?$data->tax->title:"";
							}
							);
				} else if ($select == 'hrn_code') {
					$columns [] = array (
							'label' => 'Total Tax(%age)',
							'value' => function ($data) {
							return isset($data->tax)?$data->tax->hrn_code:"";
							}
							);
				} else if ($select == 'cgst_per') {
					$columns [] = array (
							'label' => 'CGST(%age)',
							'value' => function ($data) {
							return isset($data->tax)?$data->tax->tax_val1:"";
							}
							);
				}  else if ($select == 'cgst_amt') {
					$columns [] = array (
							'label' => 'CGST Amount',
							'value' => function ($data) {
							return $data->getCgstAmt();
							}
							);
				} else if ($select == 'sgst_per') {
					$columns [] = array (
							'label' => 'SGST(%age)',
							'value' => function ($data) {
							return isset($data->tax)?$data->tax->tax_val2:"";
							}
							);
				}  else if ($select == 'sgst_amt') {
					$columns [] = array (
							'label' => 'SGST Amount',
							'value' => function ($data) {
							return $data->getSgstAmt();
							}
							);
				}
				/*else if ($select == 'igst_per') {
					$columns [] = array (
							'label' => 'IGST(%age)',
							'value' => function ($data) {
							return isset($data->tax)?$data->tax->tax_val4:"";
							}
							);
				}  else if ($select == 'igst_amt') {
					$columns [] = array (
							'label' => 'IGST Amount',
							'value' => function ($data) {
							return $data->getIgstAmt();
							}
							);			
				} */
				else if ($select == 'cess_per') {
					$columns [] = array (
							'label' => 'CESS(%age)',
							'value' => function ($data) {
							return isset($data->tax)?$data->tax->tax_val3:"";
							}
							);
				} else if ($select == 'cess_amt') {
					$columns [] = array (
							'label' => 'CESS Amount',
							'value' => function ($data) {
							return $data->getCessAmt();
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
	
	public function getCgstAmt(){
		$discount = 0;
		$item_detail = ItemDetail::model()->findByPk($this->item_detail_id);
		if($item_detail){
			$item = Item::model()->findByPk($item_detail->item_id);
			if($item){
				$price = $item_detail->getBasePrice();
				$tax = 	$this->tax->tax_val1;
				$discount = $price * $tax/100;
			}
		}
		return number_format($discount,2);
	
	}
	
	
		/*
		
		public function getIgstAmt(){
		$discount = 0;
		$item_detail = ItemDetail::model()->findByPk($this->item_detail_id);
		if($item_detail){
			$item = Item::model()->findByPk($item_detail->item_id);
			if($item){
				$price = $item_detail->getBasePrice();
				$tax = 	$this->tax->tax_val4;
				$discount = $price * $tax/100;
			}
		}
		return number_format($discount,2);
	
	}*/
	
	
	public function getSgstAmt(){
		$discount = 0;
		$item_detail = ItemDetail::model()->findByPk($this->item_detail_id);
		if($item_detail){
			$item = Item::model()->findByPk($item_detail->item_id);
			if($item){
				$price = $item_detail->getBasePrice();
				$tax = 	$this->tax->tax_val2;
				$discount = $price * $tax/100;
			}
		}
		return number_format($discount,2);
	
	}
	public function getCessAmt(){
		$discount = 0;
		$item_detail = ItemDetail::model()->findByPk($this->item_detail_id);
		if($item_detail){
			$item = Item::model()->findByPk($item_detail->item_id);
			if($item){
				$price = $item_detail->getBasePrice();
				$tax = 	$this->tax->tax_val3;
				$discount = $price * $tax/100;
			}
		}
		return number_format($discount,2);
	
	}
	public function getSaleRate(){
		$sale_rate = 0;
		$item_detail = ItemDetail::model()->findByPk($this->item_detail_id);
		if($item_detail){
			$item = Item::model()->findByPk($item_detail->item_id);
			$sale_rate = $item->sale_price;
		}
		return $sale_rate;
	
	}
	
	
}