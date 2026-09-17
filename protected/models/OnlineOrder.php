<?php

 
/**
 * @property integer $id
 * @property integer $order_id
 * @property integer $item_count
 * @property double $grand_total
 * @property string $first_name
 * @property string $last_name
 * @property string $street
 * @property string $city
 * @property string $telephone
 * @property string $zip_code
 * @property string $country
 * @property string $delivery_slot
 * @property string $ship_name
 * @property integer $type_id
 * @property string $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseOnlineOrder');
class OnlineOrder extends BaseOnlineOrder
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function toArray($val = false) {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$default_img = 'default.png';
			$json_entry = array ();
			$criteria1 = new CDbCriteria ();
			$criteria1->compare( "title",$model->payment_method );
			// compare() drops the condition when the title is NULL or '', so this
			// becomes "the first payment mode" rather than "no match". There was
			// no ORDER BY, leaving that row up to MySQL; ordered by id so both
			// stacks name the same one.
			$criteria1->order = 'id asc';
			$paymentmode = PaymentMode::model ()->find ( $criteria1 );
			
			$criteria2 = new CDbCriteria ();
			$criteria2->compare( "title",$model->delivery_method );
			$criteria2->order = 'id asc';
			$deliverymode = PaymentMode::model ()->find ( $criteria2 );
			$criteria1 = new CDbCriteria ();
			$criteria1->compare ( "contact_no", $model->mobile );
			$criteria1->order = 'id asc';
			$customer = Customer::model ()->find ( $criteria1 );
			$json_entry ['id'] = $model->id;
			if($customer){
				$json_entry ['customer_id'] = $customer->id;
			}else{
			$json_entry ['customer_id'] = '1';
			}
			$json_entry ['order_no'] = isset ( $model->order_id ) ? $model->order_id : '';
			$json_entry ['order_date'] = isset ( $model->order_date ) ? $model->order_date : '';
			$json_entry ['item_count'] = isset ( $model->item_count ) ? $model->item_count : '';
			$json_entry ['grand_total'] = isset ( $model->grand_total) ? $model->grand_total : '';
			$json_entry ['customer_name'] = $model->getCustomerName();
			$json_entry ['customer_contact_no'] = isset ( $model->mobile) ? $model->mobile : '';
			
			$json_entry ['last_name'] = isset ( $model->last_name) ? $model->last_name : '';
			$json_entry ['address'] = isset ( $model->street) ? $model->street : '';
			$json_entry ['city'] = isset ( $model->city) ? $model->city : '';
			$json_entry ['country'] = isset ( $model->country) ? $model->country : '';
			$json_entry ['mobile'] = isset ( $model->mobile ) ? $model->mobile : '';
			$json_entry ['payment_method'] = isset ( $model->payment_method ) ? $model->payment_method : '';
			$json_entry ['delivery_method'] = isset ( $model->delivery_method ) ? $model->delivery_method : '';
			if($paymentmode){
				$json_entry ['payment_method_id'] = $paymentmode->id;
			}else{
				$json_entry ['payment_method_id'] = '';
			}
			if($deliverymode){
				$json_entry ['delivery_method_id'] = $deliverymode->id;
			}else{
				$json_entry ['delivery_method_id'] = '';
			}
			
			$json_entry ['order_status'] = $model->order_status;
			$json_entry ['is_shipped'] = $model->is_shipped;
			$json_entry ['telephone'] = isset ( $model->telephone) ? $model->telephone : '';
			$json_entry ['order_from'] = isset ( $model->order_from) ? $model->order_from : '';
			$json_entry ['comment'] = isset ( $model->comment) ? $model->comment : '';
			$json_entry ['zip_code'] = isset ( $model->zip_code) ? $model->zip_code : '';
			$json_entry ['delivery_slot'] = isset ( $model->delivery_slot) ? $model->delivery_slot : '';
			$json_entry ['ship_name'] = isset ( $model->ship_name) ? $model->ship_name : '';
			$json_entry ['picker_id'] = isset ( $model->picker_id) ? $model->picker_id : '';
			$json_entry ['picker_name'] = isset ( $model->picker) ? $model->picker->full_name : '';
			$json_entry ['delivery_boy_name'] = isset ( $model->deliveryBoy) ? $model->deliveryBoy->full_name : '';
				
			$json_entry ['delivery_boy_id'] = isset ( $model->delivery_boy_id) ? $model->delivery_boy_id : '';
					     $neworder = Order::model()->findByAttributes(array('online_order_id'=>$model->id));
			if( $neworder != null){
				$json_entry ['discount_amt'] = $neworder->discount_amt;
			}else{
				$json_entry ['discount_amt'] = '0.00';
			}
			if($val == true){
		     $neworder = Order::model()->findByAttributes(array('online_order_id'=>$model->id));
			
			if($neworder == null){
			// the relation has no order; MySQL 8 does not sort implicitly
			$criteria0 = new CDbCriteria ();
			$criteria0->addCondition ( 'order_id = :oid' );
			$criteria0->params[':oid'] = $model->id;
			$criteria0->order = 'id asc';
			$order_items = OnlineOrderItem::model ()->findAll ( $criteria0 );
			if(!empty($order_items))
			{
				foreach ($order_items as $order_item)
				{
					$criteria5 = new CDbCriteria ();
					$criteria5->compare ( "item_code", $order_item->product_code );
					$criteria5->order = 'id asc';
					$item = Item::model ()->find ( $criteria5 );
					if ($item) {
						$criteria4 = new CDbCriteria ();
						$criteria4->compare ( "item_id", $item->id );
						$criteria4->compare ( "bar_code", $order_item->barcode );
						$criteria4->order = 'id asc';
						$itemdetail = ItemDetail::model ()->find ( $criteria4 );
						if($itemdetail == null){
							$barcode = $item->getItemBarcodes();
							$criteria4 = new CDbCriteria ();
							$criteria4->compare ( "item_id", $item->id );
							// getItemBarcodes() returns '' for an item with no active
							// detail row, and compare() then drops the condition - so
							// this falls back to the item's first detail, whatever it is
							$criteria4->compare ( "bar_code", $barcode );
							$criteria4->order = 'id asc';
							$itemdetail = ItemDetail::model ()->find ( $criteria4 );
						}
					if($itemdetail){
					$json_list [] = $itemdetail->toOnlineOrderArray($order_item);
					}
					}
					
				}
			}
			$json_entry ['order_items'] = $json_list;
			}else{
				$order_items = $neworder->orderItems;
				if(!empty($order_items))
				{
					foreach ($order_items as $order_item)
					{
						//if(isset($order_item->itemDetail)){
							$json_list [] = $order_item->toArray1();
						//}
							
					}
				}
				$json_entry ['order_items'] = $json_list;
			}
			}
		}
		return $json_entry;
	}
	
	public function getCustomerName(){
		if($this->first_name != '' && $this->last_name == ''){
		$name = $this->first_name;
		}else if($this->first_name != '' && $this->last_name != ''){
			$name = $this->first_name.' '.$this->last_name;
		}else{
			$name = '';
		}
		return $name;
	}
}