<?php


 
/**
 * @property integer $id
 * @property integer $order_hold_id
 * @property integer $item_detail_id
 * @property integer $qty
 * @property double $price
 * @property integer $discount_id
 * @property double $discount_amt
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseOrderHoldItem');
class OrderHoldItem extends BaseOrderHoldItem
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function toArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$json_list = array ();
			$json_entry = array ();
			$item_detail = $model->itemDetail;
			$json_entry ['item_id'] = $item_detail->id;
			$json_entry ['bar_code'] = $item_detail->bar_code;
			$json_entry ['item_name'] = isset($item_detail->item)?$item_detail->item->title:"";
			$json_entry ['item_desc'] = isset($item_detail->item)?$item_detail->item->short_name:"";
			$json_entry ['unit_name'] = isset($model->item)?$model->item->getMeasurementTypeOptions($model->item->unit):"";
			$json_entry ['is_coupon'] = isset($model->item)?$model->item->is_coupon:"";
			$json_entry ['box'] = 0;
			//$json_entry ['item_detail_id'] = $model->item_detail_id;
			$json_entry ['qty'] = $model->qty;
			$json_entry ['stock_qty'] = $item_detail->getStockQty();
			$json_entry ['sale_rate'] =  $model->sale_rate;
			$json_entry ['base_price'] = $model->price;
			$json_entry ['mrp'] = $model->mrp;
			$json_entry ['batch_numbers'] = '';
			$item_stock = $item_detail->itemStock;
			if(!empty($item_stock))
			{
					
				$batch_no = $item_stock->batch_number;
				$json_entry ['batch_numbers'] =$batch_no;
			}
			$json_entry ['discount_id'] = $model->discount_id;
			$json_entry ['discount_val'] = isset($model->discount)?$model->discount->amount:"0";
			$json_entry ['discount_type'] = isset($model->discount)?$model->discount->type_id:"1";
			$json_entry ['discount_amt'] = $model->discount_amt;
			$json_entry ['tax_id'] = $model->tax_id;
			$json_entry ['tax_percent'] = $item_detail->getItemTaxPercent();
			$json_entry ['tax_amt'] = $model->tax_amount;
			
			$json_entry ['total_amount'] = $model->total_amt;
			$json_entry ['cgst_per'] = $model->cgst_per;
			$json_entry ['sgst_per'] = $model->sgst_per;
			$json_entry ['cess_per'] = $model->cess_per;
			$json_entry ['igst_per'] = $model->igst_per;
			$json_entry ['cgst_amt'] = $model->cgst_amt;
			$json_entry ['sgst_amt'] = $model->sgst_amt;
			$json_entry ['cess_amount'] = $model->cess_amt;
			$json_entry ['igst_amount'] = $model->igst_amt;
				
				
				
		}
		return $json_entry;
	}
	
	public function getCgstAmount(){
		$taxAmount = 0;
		if($this->tax_id != 0){
		$tax = Tax::model()->findByPk($this->tax_id);
		if($tax){
		
		$baseprice = $this->price;
		$discount_val = isset($this->discount)?$this->discount->amount:"0";
		$tax = $tax->tax_val1;
		$baseprice = $this->price - ($this->price *$discount_val/100);
		
		
		$taxAmount = ($baseprice) * $tax/100;
		}
		}
		return number_format($taxAmount,2);
	}
	public function getSgstAmount(){
		$taxAmount = 0;
		if($this->tax_id != 0){
			$tax = Tax::model()->findByPk($this->tax_id);
			if($tax){
	
				$baseprice = $this->price;
				$discount_val = isset($this->discount)?$this->discount->amount:"0";
				$tax = $tax->tax_val2;
				$baseprice = $this->price - ($this->price *$discount_val/100);
				$taxAmount = ($baseprice) * $tax/100;
			}
		}
		return number_format($taxAmount,2);
	}
	public function getCessAmount(){
		$taxAmount = 0;
		if($this->tax_id != 0){
			$tax = Tax::model()->findByPk($this->tax_id);
			if($tax){
	
				$baseprice = $this->price;
				$discount_val = isset($this->discount)?$this->discount->amount:"0";
				$tax = $tax->tax_val3;
				$baseprice = $this->price - ($this->price *$discount_val/100);
				$taxAmount = ($baseprice) * $tax/100;
			}
		}
		return number_format($taxAmount,2);
	}
	public function getIgstAmount(){
		$taxAmount = 0;
		if($this->tax_id != 0){
			$tax = Tax::model()->findByPk($this->tax_id);
			if($tax){
	
				$baseprice = $this->price;
				$discount_val = isset($this->discount)?$this->discount->amount:"0";
				$tax = $tax->tax_val4;
				$baseprice = $this->price - ($this->price *$discount_val/100);
				$taxAmount = ($baseprice) * $tax/100;
			}
		}
		return number_format($taxAmount,2);
	}
	public function getTaxValueID($tax_id){
		$tax = Tax::model()->findByPk($tax_id);
		if($tax){
			if($tax->tax_val1 == '0.00' && $tax->tax_val2 == '0.00'&& $tax->tax_val3 == '0.00' && $tax->tax_val4 != '0.00'){
				$val = $tax->tax_val4/2;
				$criteria = new CDbCriteria();
				$criteria->addCondition('tax_val1 ='.$val);
				$criteria->addCondition('tax_val2 ='.$val);
				$criteria->addCondition('tax_val3 = 0.00');
				$tax = Tax::model()->find($criteria);
				if($tax){
					return $tax->id;
				}
			}else{
				return $tax->id;
			}
		}else{
			return $tax->id;
		}
	}
}