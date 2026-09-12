<?php


 
/**
 * @property integer $id
 * @property integer $order_refund_id
 * @property integer $item_detail_id
 * @property integer $qty
 * @property double $price
 * @property integer $discount_id
 * @property double $discount_amt
 * @property integer $tax_id
 * @property double $tax_amt
 * @property double $order_discount
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseOrderRefundItem');
class OrderRefundItem extends BaseOrderRefundItem
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
	
	public function getOrderBillNo(){
		$bill_no = 0;
		$orderRefund = OrderRefund::model()->findByAttributes(array('id'=>$this->order_refund_id));
		if($orderRefund){
			$order = Order::model()->findByPK($orderRefund->order_id);
			if($order){
				return $order->getOrderBillNo();
			}
		}
		return $bill_no;
	}
	
	
	public function getOrderDate(){
		$bill_no = 0;
		$orderRefund = OrderRefund::model()->findByAttributes(array('id'=>$this->order_refund_id));
		if($orderRefund){
			$order = Order::model()->findByPK($orderRefund->order_id);
			if($order){
				return $order->bill_date;
			}
		}
		return $bill_no;
	}
	
	
	public function getOrderRefundNo(){
		$bill_no = 0;
		$orderRefund = $this->order_refund_id;
		if($orderRefund){
			
				return "R-".$orderRefund;
			
		}
		return $bill_no;
	}
	
	public function getColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
						'bill_no',
							'item_id',
							'item_detail_id',
							'qty',
							'price',
							'discount_amt',
							'tax_amt',
							'total_amt'
			);
		}
	
		if ($selected) {
			foreach ( $selected as $select ) {
				 if ($select == 'bill_no') {
					$columns [] = array (
							'label' => 'Bill No',
							'value' => function ($data) {
							return $data->getOrderBillNo();
							}
							);
					} else if ($select == 'item_id') {
						$columns [] = array (
								'label' => 'Item',
								'value' => function ($data) {
								return $data->getItemName ();
								}
								);
					}
				  else if ($select == 'item_detail_id') {
					$columns [] = array (
							'label' => 'Barcode',
							'value' => function ($data) {
							return isset ( $data->itemDetail ) ? $data->itemDetail->bar_code : "";
							}
							);
				} else if ($select == 'total_amt') {
					$columns [] = array (
							'label' => 'Total Amount',
							'value' => function ($data) {
							return $data->total_amt;
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