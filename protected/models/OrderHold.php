<?php


 
/**
 * @property integer $id
 * @property integer $bill_no
 * @property string $bill_date
 * @property string $mode_of_payment
 * @property string $mode_of_delivery
 * @property integer $qty
 * @property double $discount_amt
 * @property double $total_amt
 * @property double $paid_amt
 * @property integer $status
 * @property integer $type_id
 * @property integer $city_id
 * @property integer $state_id
 * @property integer $country_id
 * @property string $address
 * @property string $note
 * @property string $create_time
 * @property string $update_time
 * @property integer $customer_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseOrderHold');
class OrderHold extends BaseOrderHold
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function toArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$json_list = array();
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			
			$json_entry ['mode_of_payment'] =  isset($model->modePayment)?$model->modePayment->title:'';
			$json_entry ['mode_of_delivery'] = isset($model->modeDelivery)?$model->modeDelivery->title:'';
			$json_entry ['qty'] = $model->qty;
			$json_entry ['discount_amt'] = $model->discount_amt;
			$json_entry ['total_sale'] = $model->total_amt;
			$json_entry ['total_amt'] = ($model->total_amt)+($model->discount_amt);
			$json_entry ['paid_amt'] = $model->paid_amt;
			$json_entry ['status'] = $model->status;
			$json_entry ['type_id'] = $model->type_id;
			$json_entry ['city_id'] = $model->city_id;
			$json_entry ['state_id'] = $model->state_id;
			$json_entry ['country_id'] = $model->country_id;
			$json_entry ['address'] = $model->address;
			$json_entry ['note'] = $model->note;
			$json_entry ['create_time'] = $model->create_time;
			$json_entry ['customer_id'] = $model->customer_id;
				
			$order_items = $model->orderItems;
			if(!empty($order_items))
			{
				foreach ($order_items as $order_item)
				{
					/* if(isset($order_item->itemDetail)){
						$json_list [] = $order_item->itemDetail->toArray1($order_item->id,1);
					} */
					$json_list [] = $order_item->toArray();
				
				}
			}
			$json_entry ['order_items'] = $json_list;
	
		}
		return $json_entry;
	}
	public function toArray1() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$json_list = array();
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			$json_entry ['create_time'] = $model->create_time;
	
		}
		return $json_entry;
	}
	protected function beforeDelete()
	{
		OrderHoldItem::model()->deleteAllByAttributes(array ('order_hold_id'=>$this->id));
	
		return parent::beforeDelete();
	}
	
}