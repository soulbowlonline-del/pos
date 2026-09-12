<?php


 
/**
 * @property integer $id
 * @property integer $qty
 * @property double $discount
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
 * @property integer $order_id
 * @property integer $customer_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseOrderRefund');
class OrderRefund extends BaseOrderRefund
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function defaultScope()
	{
		return array();
	}
	
	public function getRefundTotalAmount(){
		$total_amt = 0;
		$orderRefundItems = OrderRefundItem::model()->findAllByAttributes(array('order_refund_id'=>$this->id));
		if($orderRefundItems){
			foreach($orderRefundItems as $orderRefundItem){
				$total_amt = $total_amt + (((($orderRefundItem->qty)*($orderRefundItem->price)) -  (($orderRefundItem->qty)*($orderRefundItem->discount_amt))) + (($orderRefundItem->qty)*($orderRefundItem->tax_amt)));
			}
		}
		return $total_amt;
	}
}