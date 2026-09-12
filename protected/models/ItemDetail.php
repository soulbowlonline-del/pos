<?php


 
/**
 * @property integer $id
 * @property integer $item_id
 * @property string $bar_code
 * @property integer $open_stock_qty
 * @property integer $reorder_qty
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $tax_id
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemDetail');
class ItemDetail extends BaseItemDetail
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	protected function beforeDelete()
	{
		ItemDiscount::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		ItemTax::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		ItemStock::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		OrderItem::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		OrderHoldItem::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		OrderRefundItem::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		StockLog::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		StockAdjustLog::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		MrsDetail::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		MrnDetail::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		PurchaseOrderDetail::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		PurchaseBillDetail::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		ItemReturnItem::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		ItemExpireItem::model()->deleteAllByAttributes(array ('item_detail_id'=>$this->id));
		return parent::beforeDelete();
	}
	
	public function toArray2() {
		$model = $this;
		$json_entry = null;
		if ($model) {
				
			$batch_no =  array();
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['item_id'] = $model->id;
			$json_entry ['bar_code'] = $model->bar_code;
			$json_entry ['item_name'] = isset($model->item)?$model->item->title:"";
			$json_entry ['item_desc'] = isset($model->item)?$model->item->short_name:"";
			//  $json_entry ['unit_id'] = isset($model->item)?$model->item->unit:"";
			$json_entry ['unit_name'] = isset($model->item)?$model->item->getMeasurementTypeOptions($model->item->unit):"";
			$json_entry ['is_coupon'] = isset($model->item)?$model->item->is_coupon:"";
			$json_entry ['is_return'] = false;
			
			$json_entry ['qty'] = 1;
			$json_entry ['stock_qty'] = $model->getStockQty();
			$json_entry ['sale_rate'] = $model->getItemDetailSaleRate();
			$json_entry ['base_price'] = $model->getBasePrice();
			$json_entry ['mrp'] = $model->getItemDetailMrp();
	
			$json_entry ['batch_numbers'] = '';
			$item_stock = $model->itemStock;
			if(!empty($item_stock))
			{
	
				$batch_no = $item_stock->batch_number;
				$json_entry ['batch_numbers'] =$batch_no;
			}
			$json_entry ['discount_id'] = 0;
			$json_entry ['discount_val'] = 0;
			$json_entry ['discount_type'] = 1;
			$json_entry ['discount_amt'] = 0;
			$json_entry ['tax_id'] = $model->getItemTax();
			$json_entry ['tax_percent'] = $model->getItemTaxPercent();
			$json_entry ['tax_amt'] = $model->getItemTaxAmount();
				
				
			if($model->item)
			{
				$check_discount = $model->item->is_discount;
				if($check_discount == 1)
				{
					if(!empty($model->itemDiscount))
					{
						$discount_id = $model->itemDiscount->discount_id;
	
	
						$current_date = date('Y-m-d');
						$current_time = date('H:i');
	
						$complete_date =  date('Y-m-d H:i');
	
						$criteria_even = new CDbCriteria ();
							
						$criteria_even->addCondition ( 'id =' .$discount_id );
	
						$criteria_even->addCondition( "start_date  <= '$current_date' AND end_date >= '$current_date'");
						//	$criteria_even->condition = "start_time  >= '$current_time' AND end_time <= '$current_time'";
						$discount_data = Discount::model()->find($criteria_even);
						if(!empty($discount_data))
						{
								
							$start_datetime = $discount_data->start_date .' ' . $discount_data->start_time;
							$end_datetime = $discount_data->end_date .' ' . $discount_data->end_time;
								
							if ((strtotime($complete_date) > strtotime($start_datetime)) && (strtotime($complete_date) < strtotime($end_datetime)))
							{
								$json_entry ['discount_val'] = $discount_data->amount;
								$json_entry ['discount_type'] = $discount_data->type_id;
								$json_entry ['discount_id'] = $discount_data->id;
								$json_entry ['discount_amt'] = $this->getItemDiscountAmount();
							}
								
	
								
						}
					}
				}
			}
			$json_entry ['total_amount'] = $this->getTotalAmount();
			$json_entry ['cgst_amt'] = $model->getCgstAmount();
			$json_entry ['sgst_amt'] = $model->getSgstAmount();
			$json_entry ['cess_amount'] = $model->getCessAmount();
			$json_entry ['igst_amount'] = $model->getIgstAmount();
			/* $json_entry ['id'] = $model->id;
				$json_entry ['bar_code'] = isset ( $model->bar_code ) ? $model->bar_code : '';
				$json_entry ['open_stock_qty'] = isset ( $model->open_stock_qty ) ? $model->open_stock_qty : '';
				$json_entry ['reorder_qty'] = isset ( $model->reorder_qty ) ? $model->reorder_qty : '';
				$json_entry ['outlet'] =  isset ( $model->outlet ) ? $model->outlet->title : '';
				$json_entry ['tax'] = isset ( $model->tax ) ? $model->tax->title : '';
				$json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
				$json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
				$json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : '';
	
				if(isset($model->item))
				{
	
				$json_entry ['item'] = $model->item->toArray();
	
				}  */
	
		}
		return $json_entry;
	}
	public function toArray1($id,$state,$box=1) {
		if($state == 1){
		$orderitem = OrderHoldItem::model()->findByPk($id);
		}else{
			$orderitem = OrderItem::model()->findByPk($id);
		}
		
		$model = $this;
		$json_entry = null;
		if ($model) {
				
			$batch_no =  array();
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['item_id'] = $model->id;
			$json_entry ['bar_code'] = $model->bar_code;
			$json_entry ['item_name'] = isset($model->item)?$model->item->title:"";
			$json_entry ['item_desc'] = isset($model->item)?$model->item->short_name:"";
			//  $json_entry ['unit_id'] = isset($model->item)?$model->item->unit:"";
			$json_entry ['unit_name'] = isset($model->item)?$model->item->getMeasurementTypeOptions($model->item->unit):"";
			$json_entry ['is_coupon'] = isset($model->item)?$model->item->is_coupon:"";
			if($box == 0){
			$json_entry ['box'] = 0;
			}else{
				$json_entry ['is_return'] = 0;
			}
			if($orderitem){
				/* $qty = $orderitem->qty;
				if($state == 2){
					$orderrefund = OrderRefund::model()->findByAttributes(array('order_id'=>$orderitem->order_id));
					if($orderrefund){
						$orderrefunditem = OrderRefundItem::model()->findByAttributes(array('item_detail_id'=>$model->id,
								'item_id'=>$model->item_id,'order_refund_id'=>$orderrefund->id,
						));
						if($orderrefunditem){
							$qty = $qty - $orderrefunditem->qty;
						}
					}
				} */
			$json_entry ['qty'] = $orderitem->qty;
			}
			else{
				$json_entry ['qty'] = 1;
			}
			$json_entry ['stock_qty'] = $model->getStockQty();
			/* if($orderitem){
				$item_mrp  = isset($model->item)?$model->item->mrp:"0";
				$orderprice = number_format($orderitem->price,2);
				$ordertax = number_format($orderitem->tax_amount,2);
				$sale_after = round($orderprice + $ordertax);
				if($sale_after >$item_mrp){
					$sale_after = $item_mrp;
				}
				$json_entry ['sale_rate'] = $sale_after;
			}
			else{ */
				$json_entry ['sale_rate'] = $model->getItemDetailSaleRate();
			/* } */
			
			if($orderitem){
				$json_entry ['base_price'] = $orderitem->price;
			}
			else{
				$json_entry ['base_price'] = $model->getBasePrice();
			}
			
			$json_entry ['mrp'] = $model->getItemDetailMrp();
	
			$json_entry ['batch_numbers'] = '';
			$item_stock = $model->itemStock;
			if(!empty($item_stock))
			{
	
				$batch_no = $item_stock->batch_number;
				$json_entry ['batch_numbers'] =$batch_no;
			}
			if($orderitem){
				$json_entry ['discount_id'] = $orderitem->discount_id;
				$json_entry ['discount_val'] = isset($orderitem->discount)?$orderitem->discount->amount:"0";
				$json_entry ['discount_type'] = isset($orderitem->discount)?$orderitem->discount->type_id:"1";
				$json_entry ['discount_amt'] = $orderitem->discount_amt;
				$json_entry ['tax_id'] = $orderitem->tax_id;
				$json_entry ['tax_percent'] = $model->getItemTaxPercent();
				$json_entry ['tax_amt'] = $orderitem->tax_amount;
			//	$baseprice = $model->getBasePrice() - ($model->getBasePrice()*$json_entry ['discount_val']/100);
				$baseprice = ($orderitem->price) - ($orderitem->discount_amt);
				$total = $baseprice + $orderitem->tax_amount;
				$total= number_format((float)$total,2);
				$json_entry ['total_amount'] = $total;
				$json_entry ['cgst_amt'] = $orderitem->getCgstAmount();
				$json_entry ['sgst_amt'] = $orderitem->getSgstAmount();
				$json_entry ['cess_amount'] = $orderitem->getCessAmount();
				$json_entry ['igst_amount'] = $orderitem->getIgstAmount();
				$json_entry ['cgst_per'] = $model->getCgstPercent();
				$json_entry ['sgst_per'] = $model->getSgstPercent();
				$json_entry ['cess_per'] = $model->getCessPercent();
				$json_entry ['igst_per'] = $model->getIgstPercent();
			}
			else{
				if($model->item)
				{
					$check_discount = $model->item->is_discount;
					if($check_discount == 1)
					{
						if(!empty($model->itemDiscount))
						{
							$discount_id = $model->itemDiscount->discount_id;
				
				
							$current_date = date('Y-m-d');
							$current_time = date('H:i');
				
							$complete_date =  date('Y-m-d H:i');
				
							$criteria_even = new CDbCriteria ();
								
							$criteria_even->addCondition ( 'id =' .$discount_id );
				
							$criteria_even->addCondition( "start_date  <= '$current_date' AND end_date >= '$current_date'");
							//	$criteria_even->condition = "start_time  >= '$current_time' AND end_time <= '$current_time'";
							$discount_data = Discount::model()->find($criteria_even);
							if(!empty($discount_data))
							{
				
								$start_datetime = $discount_data->start_date .' ' . $discount_data->start_time;
								$end_datetime = $discount_data->end_date .' ' . $discount_data->end_time;
				
								if ((strtotime($complete_date) > strtotime($start_datetime)) && (strtotime($complete_date) < strtotime($end_datetime)))
								{
									$json_entry ['discount_val'] = $discount_data->amount;
									$json_entry ['discount_type'] = $discount_data->type_id;
									$json_entry ['discount_id'] = $discount_data->id;
									$json_entry ['discount_amt'] = $this->getItemDiscountAmount();
								}
				
				
				
							}
						}
					}
				}
				$json_entry ['tax_id'] = $model->getItemTax();
				$json_entry ['tax_percent'] = $model->getItemTaxPercent();
				
				$json_entry ['tax_amt'] = $model->getItemTaxAmount();
				
				$json_entry ['cgst_amt'] = $model->getCgstAmount();
				$json_entry ['sgst_amt'] = $model->getSgstAmount();
				$json_entry ['cess_amount'] = $model->getCessAmount();
				$json_entry ['igst_amount'] = $model->getIgstAmount();
				$json_entry ['cgst_per'] = $model->getCgstPercent();
				$json_entry ['sgst_per'] = $model->getSgstPercent();
				$json_entry ['cess_per'] = $model->getCessPercent();
				$json_entry ['igst_per'] = $model->getIgstPercent();
			}
		
			
				
				
			
		
			
			/* $json_entry ['id'] = $model->id;
				$json_entry ['bar_code'] = isset ( $model->bar_code ) ? $model->bar_code : '';
				$json_entry ['open_stock_qty'] = isset ( $model->open_stock_qty ) ? $model->open_stock_qty : '';
				$json_entry ['reorder_qty'] = isset ( $model->reorder_qty ) ? $model->reorder_qty : '';
				$json_entry ['outlet'] =  isset ( $model->outlet ) ? $model->outlet->title : '';
				$json_entry ['tax'] = isset ( $model->tax ) ? $model->tax->title : '';
				$json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
				$json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
				$json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : '';
	
				if(isset($model->item))
				{
	
				$json_entry ['item'] = $model->item->toArray();
	
				}  */
	
		}
		return $json_entry;
	}
	
	public function toonlineArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
				
			$batch_no =  array();
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['Code'] = isset($model->item)?$model->item->item_code:"";
			$json_entry ['Name'] = isset($model->item)?$model->item->title:"";
			$json_entry ['Short_x0020_Name'] = isset($model->item)?$model->item->short_name:"";
			$json_entry ['Barcode'] = $model->bar_code;
			$tax_id = $model->getItemTax();
			$tax = Tax::model()->findByPk($tax_id);
			if($tax){
			$json_entry ['Tax'] = $tax->title;
			}
			if($model->mrp != '0.00'){
				$json_entry ['MRP'] = isset($model->mrp)?$model->mrp:"";
			}else{
			$json_entry ['MRP'] = isset($model->item)?$model->item->mrp:"";
			}
			$json_entry ['PRICE'] = isset($model->item)?$model->item->sale_price:"";
			$json_entry ['OPStock'] = $model->getStockQty();
			$json_entry ['Pur_x0020_Price'] = isset($model->item)?$model->item->purchase_price:"";
			$json_entry ['Pur_x0020_Value'] = isset($model->item)?$model->item->purchase_price:"";
			$json_entry ['Weight'] = isset($model->item)?$model->item->weight:"";
			$json_entry ['Department'] = isset($model->item)?$model->getCategory():"";
			$json_entry ['Company'] = isset($model->item)?$model->getCompany():"";
			$json_entry ['Sub_x0020_Category'] = isset($model->item)?$model->getSubcategory():"";
			$json_entry ['ProdEx1'] = '';
			$json_entry ['ProdEx2'] = '';
			$json_entry ['ProdEx3'] = '';
			$json_entry ['ProdEx4'] = '';
			$json_entry ['Active'] = $model->getStatusOptions($model->status);
	
		}
		return $json_entry;
	}
	
	public function getCategory(){
		$title = '';
		$item = Item::model()->findByPk($this->item_id);
		if($item){
			if($item->category){
				$title = $item->category->title;
			}
		}
		return $title;
	}
	public function getCompany(){
		$title = '';
		$item = Item::model()->findByPk($this->item_id);
		if($item){
			if($item->company){
				$title = $item->company->title;
			}
		}
		return $title;
	}
	public function getSubcategory(){
		$title = '';
		$item = Item::model()->findByPk($this->item_id);
		if($item){
			if($item->subcategory){
				$title = $item->subcategory->title;
			}
		}
		return $title;
	}
	public function toOnlineOrderArray($order_item) {
		$price = null;
		$model = $this;
		$json_entry = null;
		if ($model) {
				
			$batch_no =  array();
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['item_id'] = $model->id;
			$json_entry ['bar_code'] = $model->bar_code;
			$json_entry ['item_name'] = isset($model->item)?$model->item->title:"";
			$json_entry ['item_desc'] = isset($model->item)?$model->item->short_name:"";
			//  $json_entry ['unit_id'] = isset($model->item)?$model->item->unit:"";
			$json_entry ['unit_name'] = isset($model->item)?$model->item->getMeasurementTypeOptions($model->item->unit):"";
			$json_entry ['is_coupon'] = isset($model->item)?$model->item->is_coupon:"";
			$json_entry ['box'] = 0;
			$json_entry ['qty'] = number_format($order_item->qty,'2','.','');
			$json_entry ['stock_qty'] = $model->getStockQty();
			$json_entry ['total_remain'] = isset($model->item)?$model->item->getTotalRemainingQuantity():"0.000";
			
			if($price != null){
				$json_entry ['sale_rate'] = $price;
			}else{
				$json_entry ['sale_rate'] = $model->getItemDetailSaleRate();
			}
			if($price != null){
				$json_entry ['base_price'] = $model->getBasePrice($price);
	
			}else{
				$json_entry ['base_price'] = $model->getBasePrice();
			}
				
			$json_entry ['mrp'] = $model->getItemDetailMrp();
	
			$json_entry ['batch_numbers'] = '';
			$item_stock = $model->itemStock;
			if(!empty($item_stock))
			{
	
				$batch_no = $item_stock->batch_number;
				$json_entry ['batch_numbers'] =$batch_no;
			}
			$json_entry ['discount_id'] = 0;
			$json_entry ['discount_val'] = 0;
			$json_entry ['discount_type'] = 1;
			$json_entry ['discount_amt'] = 0;
			$json_entry ['tax_id'] = $model->getItemTax();
			$qty =  number_format($order_item->qty,'2','.','');
			$json_entry ['tax_percent'] = $model->getItemTaxPercent();
			$json_entry ['tax_amt'] = $qty * $model->getItemTaxAmount($price);
				
				
			if($model->item)
			{
				$check_discount = $model->item->is_discount;
				if($check_discount == 1)
				{
					if(!empty($model->itemDiscount))
					{
						$discount_id = $model->itemDiscount->discount_id;
	
	
						$current_date = date('Y-m-d');
						$current_time = date('H:i');
	
						$complete_date =  date('Y-m-d H:i');
	
						$criteria_even = new CDbCriteria ();
							
						$criteria_even->addCondition ( 'id =' .$discount_id );
	
						$criteria_even->addCondition( "start_date  <= '$current_date' AND end_date >= '$current_date'");
						//	$criteria_even->condition = "start_time  >= '$current_time' AND end_time <= '$current_time'";
						$discount_data = Discount::model()->find($criteria_even);
						if(!empty($discount_data))
						{
								
							$start_datetime = $discount_data->start_date .' ' . $discount_data->start_time;
							$end_datetime = $discount_data->end_date .' ' . $discount_data->end_time;
								
							if ((strtotime($complete_date) > strtotime($start_datetime)) && (strtotime($complete_date) < strtotime($end_datetime)))
							{
								$json_entry ['discount_val'] = $discount_data->amount;
								$json_entry ['discount_type'] = $discount_data->type_id;
								$json_entry ['discount_id'] = $discount_data->id;
								$json_entry ['discount_amt'] = $this->getItemDiscountAmount();
							}
								
	
								
						}
					}
				}
			}
			$total_amount = $order_item->qty * $model->getItemDetailSaleRate();
			$json_entry ['total_amount'] = number_format($total_amount,'2','.','');
			$json_entry ['cgst_amt'] = $qty * $model->getCgstAmount($price);
			$json_entry ['sgst_amt'] = $qty * $model->getSgstAmount($price);
			$json_entry ['cess_amount'] = $qty * $model->getCessAmount($price);
			$json_entry ['igst_amount'] = $qty * $model->getIgstAmount();
			$json_entry ['cgst_per'] = $model->getCgstPercent();
			$json_entry ['sgst_per'] = $model->getSgstPercent();
			$json_entry ['cess_per'] = $model->getCessPercent();
			$json_entry ['igst_per'] = $model->getIgstPercent();
				
			/* $json_entry ['id'] = $model->id;
				$json_entry ['bar_code'] = isset ( $model->bar_code ) ? $model->bar_code : '';
				$json_entry ['open_stock_qty'] = isset ( $model->open_stock_qty ) ? $model->open_stock_qty : '';
				$json_entry ['reorder_qty'] = isset ( $model->reorder_qty ) ? $model->reorder_qty : '';
				$json_entry ['outlet'] =  isset ( $model->outlet ) ? $model->outlet->title : '';
				$json_entry ['tax'] = isset ( $model->tax ) ? $model->tax->title : '';
				$json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
				$json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
				$json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : '';
	
				if(isset($model->item))
				{
	
				$json_entry ['item'] = $model->item->toArray();
	
				}  */
	
		}
		return $json_entry;
	}
	public function toArray($price = null) {
		$model = $this;
		$json_entry = null;
		if ($model) {
			
			$batch_no =  array();
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['item_id'] = $model->id;
			$json_entry ['bar_code'] = $model->bar_code;
			$json_entry ['item_name'] = isset($model->item)?$model->item->title:"";
			
			$json_entry ['hsn_code'] = isset($model->item)?$model->item->hsn_code : "";
			
			$json_entry ['item_desc'] = isset($model->item)?$model->item->short_name:"";
		  //  $json_entry ['unit_id'] = isset($model->item)?$model->item->unit:"";
			$json_entry ['unit_name'] = isset($model->item)?$model->item->getMeasurementTypeOptions($model->item->unit):"";
			$json_entry ['is_coupon'] = isset($model->item)?$model->item->is_coupon:"";
			$json_entry ['box'] = 0;
			$json_entry ['qty'] = 1;
			$json_entry ['stock_qty'] = $model->getStockQty();
			if($price != null){
				$json_entry ['sale_rate'] = $price;
			}else{
				$json_entry ['sale_rate'] = $model->getItemDetailSaleRate();
			}
			if($price != null){
				$json_entry ['base_price'] = $model->getBasePrice($price);
				
			}else{
				$json_entry ['base_price'] = $model->getBasePrice();
			}
			
			$json_entry ['mrp'] = $model->getItemDetailMrp();
		
			$json_entry ['batch_numbers'] = '';
			$item_stock = $model->itemStock;
			if(!empty($item_stock))
			{
				
					$batch_no = $item_stock->batch_number;
					$json_entry ['batch_numbers'] =$batch_no;
			}
			$json_entry ['discount_id'] = 0;
			$json_entry ['discount_val'] = 0;
			$json_entry ['discount_type'] = 1;
			$json_entry ['discount_amt'] = 0;
			$json_entry ['tax_id'] = $model->getItemTax();
			$json_entry ['tax_percent'] = $model->getItemTaxPercent();
			$json_entry ['tax_amt'] = $model->getItemTaxAmount($price);
			
			
			if($model->item)
			{
				$check_discount = $model->item->is_discount;
				if($check_discount == 1)
				{
					if(!empty($model->itemDiscount))
					{
						$discount_id = $model->itemDiscount->discount_id;
						
						
						$current_date = date('Y-m-d');
						$current_time = date('H:i');
						
						$complete_date =  date('Y-m-d H:i');
						
						$criteria_even = new CDbCriteria ();
					
						$criteria_even->addCondition ( 'id =' .$discount_id );
						
						$criteria_even->addCondition( "start_date  <= '$current_date' AND end_date >= '$current_date'");
					//	$criteria_even->condition = "start_time  >= '$current_time' AND end_time <= '$current_time'";
						$discount_data = Discount::model()->find($criteria_even);
						if(!empty($discount_data))
						{
							
							$start_datetime = $discount_data->start_date .' ' . $discount_data->start_time;
							$end_datetime = $discount_data->end_date .' ' . $discount_data->end_time;
							
							if ((strtotime($complete_date) > strtotime($start_datetime)) && (strtotime($complete_date) < strtotime($end_datetime)))
							{
								$json_entry ['discount_val'] = $discount_data->amount;
								$json_entry ['discount_type'] = $discount_data->type_id;
								$json_entry ['discount_id'] = $discount_data->id;
								$json_entry ['discount_amt'] = $this->getItemDiscountAmount();
							}
							
						
							
						}
					}
				}
			}
			$json_entry ['total_amount'] = $this->getTotalAmount($price);
			$json_entry ['cgst_amt'] = $model->getCgstAmount($price);
			$json_entry ['sgst_amt'] = $model->getSgstAmount($price);
			$json_entry ['cess_amount'] = $model->getCessAmount($price);
			$json_entry ['igst_amount'] = $model->getIgstAmount();
			$json_entry ['cgst_per'] = $model->getCgstPercent();
			$json_entry ['sgst_per'] = $model->getSgstPercent();
			$json_entry ['cess_per'] = $model->getCessPercent();
			$json_entry ['igst_per'] = $model->getIgstPercent();
			
			/* $json_entry ['id'] = $model->id;
			$json_entry ['bar_code'] = isset ( $model->bar_code ) ? $model->bar_code : '';
			$json_entry ['open_stock_qty'] = isset ( $model->open_stock_qty ) ? $model->open_stock_qty : '';
			$json_entry ['reorder_qty'] = isset ( $model->reorder_qty ) ? $model->reorder_qty : '';
			$json_entry ['outlet'] =  isset ( $model->outlet ) ? $model->outlet->title : '';
			$json_entry ['tax'] = isset ( $model->tax ) ? $model->tax->title : '';
	        $json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
			$json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
			$json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : '';
		
			 if(isset($model->item))
			{
				
					$json_entry ['item'] = $model->item->toArray();
				
			}  */

			$json_entry ['itemdetail_id'] = $model->id;
			$json_entry ['original_item_id'] = $model->item_id ;
				
		}
		return $json_entry;
	}
	
	public function getItemDiscountAmount($price = null){
		$discount_amt = 0;
		$model = $this;
		if($model->item)
		{
			$check_discount = $model->item->is_discount;
			if($check_discount == 1)
			{
				if(!empty($model->itemDiscount))
				{
					$discount_id = $model->itemDiscount->discount_id;
		
		
					$current_date = date('Y-m-d');
					$current_time = date('H:i');
		
					$complete_date =  date('Y-m-d H:i');
		
					$criteria_even = new CDbCriteria ();
						
					$criteria_even->addCondition ( 'id =' .$discount_id );
		
					$criteria_even->addCondition( "start_date  <= '$current_date' AND end_date >= '$current_date'");
					//	$criteria_even->condition = "start_time  >= '$current_time' AND end_time <= '$current_time'";
					$discount_data = Discount::model()->find($criteria_even);
					if(!empty($discount_data))
					{
							
						$start_datetime = $discount_data->start_date .' ' . $discount_data->start_time;
						$end_datetime = $discount_data->end_date .' ' . $discount_data->end_time;
							
						if ((strtotime($complete_date) > strtotime($start_datetime)) && (strtotime($complete_date) < strtotime($end_datetime)))
						{
							$discount_amt = $discount_data->amount;
							$discount_type = $discount_data->type_id;
							if($discount_type == Discount::TYPE_PERCENTAGE){
								$base_price  = $this->getBasePrice($price);
								$discount_amt = $base_price * $discount_amt/100;
							}
						}
							
		
							
					}
				}
			}
		}
		return $discount_amt;
	}
	public function getItemTax(){
		$val = 0;
		/* $itemtax = ItemTax::model()->findByAttributes(array('item_detail_id'=>$this->id));
		if($itemtax){ */
		$tax = $this->getItemDetailTax();
			if($tax){
				$val = $tax->id;
			}
		/* } */
		return $val;
	}
	public function getItemTaxPercent(){
		$val = 0;
		/* $itemtax = ItemTax::model()->findByAttributes(array('item_detail_id'=>$this->id));
		if($itemtax){ */
		$tax = $this->getItemDetailTax();
			if($tax){
				$val = $tax->tax_val1 + $tax->tax_val2 + $tax->tax_val3 +  $tax->tax_val4;
			}
		/* } */
		return $val;
	}

	public function getItemDetailTax() {
		$saleTaxId = PurchaseBillDetail::getLatestSaleTaxIdByItemDetailId($this->id);
		$taxId = $this->tax_id;
		if ($saleTaxId && $saleTaxId != $this->tax_id) {
			$taxId = $saleTaxId;
		}
		// $taxId = $this->tax_id;
		return Tax::model()->findByAttributes(array('id'=>$taxId));
	}

	public function getCgstPercent(){
		$val = 0;
		/* $itemtax = ItemTax::model()->findByAttributes(array('item_detail_id'=>$this->id));
		if($itemtax){ */
			$tax = $this->getItemDetailTax();
			if($tax){
				$val = $tax->tax_val1;
				if(($val == '0.00') && ($tax->tax_val4 != '0.00')){
					$val = ($tax->tax_val4)/2;
				}
			}
		/* } */
		return $val;
	}
	public function getCgstAmount($price = null){
		$taxAmount = 0;
		$baseprice = $this->getBasePrice($price);
		$baseprice = str_replace(",", "", $baseprice);
		$discount_amt = $this->getItemDiscountAmount($price);
		$tax = $this->getCgstPercent();
		$taxAmount = ($baseprice - $discount_amt) * $tax/100;
		Yii::log ( CVarDumper::dumpAsString ( $taxAmount ), CLogger::LEVEL_WARNING, 'cgsttaxAmount' );
		Yii::log ( CVarDumper::dumpAsString ( $baseprice ), CLogger::LEVEL_WARNING, 'cgstbaseprice' );
		Yii::log ( CVarDumper::dumpAsString ( $discount_amt ), CLogger::LEVEL_WARNING, 'cgstdiscount_amt' );
		//return number_format($taxAmount,2);
		return $taxAmount;
	}
	public function getSgstPercent(){
		$val = 0;
		/* $itemtax = ItemTax::model()->findByAttributes(array('item_detail_id'=>$this->id));
		if($itemtax){ */
		$tax = $this->getItemDetailTax();
			if($tax){
				$val = $tax->tax_val1;
				if(($val == '0.00') && ($tax->tax_val4 != '0.00')){
					$val = ($tax->tax_val4)/2;
				}
					
			}
		/* } */
		return $val;
	}
	public function getSgstAmount($price = null){
		$taxAmount = 0;
		$baseprice = $this->getBasePrice($price);
		$baseprice = str_replace(",", "", $baseprice);
		$discount_amt = $this->getItemDiscountAmount($price);
		$tax = $this->getSgstPercent();
		$taxAmount = ($baseprice - $discount_amt) * $tax/100;
	//	return number_format($taxAmount,2);
		return $taxAmount;
	}
	public function getCessPercent(){
		$val = 0;
		/* $itemtax = ItemTax::model()->findByAttributes(array('item_detail_id'=>$this->id));
		if($itemtax){ */
		$tax = $this->getItemDetailTax();
			if($tax){
				$val = $tax->tax_val3;
			}
		/* } */
		return $val;
	}
	public function getCessAmount($price=null){
		$taxAmount = 0;
		$baseprice = $this->getBasePrice($price);
		$baseprice = str_replace(",", "", $baseprice);
		$discount_amt = $this->getItemDiscountAmount($price);
		$tax = $this->getCessPercent();
		$taxAmount = ($baseprice - $discount_amt) * $tax/100;
		//return number_format($taxAmount,2);
		return $taxAmount;
	}
	public function getIgstPercent(){
		$val = 0;
	/* 	$itemtax = ItemTax::model()->findByAttributes(array('item_detail_id'=>$this->id));
		if($itemtax){ */
		$tax = $this->getItemDetailTax();
			if($tax){
				//$val = $tax->tax_val4;
				$val = 0;
			}
		/* } */
		return $val;
	}
	public function getParentCompanys(){
		$list = array();
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'parent_id IS  NULL');
		$criteria->order = 'title asc';
		$criteria->addCondition ( 'status ='.ItemCompany::STATUS_ACTIVE);
		$cats = ItemCompany::model ()->findAll ( $criteria);
		if($cats){
			foreach($cats as $cat){
				$list[$cat->id] = $cat->title;
			}
		}
		return $list;
	}
	public function getIgstAmount($price = null){
		$taxAmount = 0;
		$baseprice = $this->getBasePrice($price);
		$baseprice = str_replace(",", "", $baseprice);
		$discount_amt = $this->getItemDiscountAmount($price);
		$tax = $this->getIgstPercent();
		$taxAmount = ($baseprice - $discount_amt) * $tax/100;
		//return number_format($taxAmount,2);
		return $taxAmount;
	}
	public function getBasePrice($price = null){
		$tax = $this->getItemTaxPercent();
		if($price != null){
			$baseprice = (($price)*100)/(100+$tax);
		}else{
		$baseprice = (($this->getItemDetailSaleRate())*100)/(100+$tax);
		}
		$baseprice = str_replace(",", "", $baseprice);
		
		return  round($baseprice,2) ; //number_format($baseprice,2);
	}
	public function getItemTaxAmount($price=null){
		$taxAmount = 0;
		$baseprice = $this->getBasePrice($price);
		$baseprice = str_replace(",", "", $baseprice);
		$discount_amt = $this->getItemDiscountAmount($price);
		$tax = $this->getItemTaxPercent();
		$taxAmount = ($baseprice - $discount_amt) * $tax/100;
		
		Yii::log ( CVarDumper::dumpAsString ( $taxAmount ), CLogger::LEVEL_WARNING, 'totaltaxAmount' );
		Yii::log ( CVarDumper::dumpAsString ( $baseprice ), CLogger::LEVEL_WARNING, 'totalbaseprice' );
		Yii::log ( CVarDumper::dumpAsString ( $discount_amt ), CLogger::LEVEL_WARNING, 'totaldiscount_amt' );
		//return number_format($taxAmount,2);
		return $taxAmount;
	}
	
	public function getStockQty(){
		$total = 0;

		$remaining_quantity = '0.000';
		$add_quantity = '0.000';
		$sub_quantity = '0.000';
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->item_id);
		$criteria->order =  'id asc';
		$criteria->addCondition ( "balance_qty > 0.000");
		$criteria->addCondition('item_detail_id IS NOT NULL');
		$stocks = ItemStock::model()->findAll($criteria);
		
		if(!empty($stocks))
		{
			foreach ($stocks as $stock)
			{
				$add_quantity = ($add_quantity) + ($stock->balance_qty);
		
			}
		}
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('item_id ='.$this->item_id);
		$criteria1->order =  'id asc';
		$criteria1->addCondition ( "balance_qty < 0.000");
		$criteria1->addCondition('item_detail_id IS NOT NULL');
		$stocks = ItemStock::model()->findAll($criteria1);
		
		if(!empty($stocks))
		{
			foreach ($stocks as $stock)
			{
				$sub_quantity = ($sub_quantity) + abs($stock->balance_qty);
		
			}
		}
		$remaining_quantity = bcsub($add_quantity,$sub_quantity,3);
		
		return $remaining_quantity;
		/* $criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->item_id);
		$criteria->select = 'sum(balance_qty) as balance_qty';
		$stock = ItemStock::model()->find($criteria); */
		
		/* if($stock){
			$total = $stock->balance_qty;
		} */
		
	}
	
	public function calculateLockedStockQty($rows)
	{
			$add = 0;
			$sub = 0;

			foreach ($rows as $row) {
					if ($row[0] > 0) {
							$add += $row[0];
					} else {
							$sub += abs($row[0]);
					}
			}

			return bcsub($add, $sub, 3);
	}

	public function getTotalAmount($price=null){
		$totalAmount = 0;
		
		$baseprice = $this->getBasePrice($price);
		$baseprice = str_replace(",", "", $baseprice);
		$discount_amt = $this->getItemDiscountAmount();
		$tax = $this->getItemTaxAmount($price);
		$totalAmount = ($baseprice - $discount_amt) + $tax;
		$total = number_format((float)$totalAmount,2);
		if($price != null){
			$sale_rate = $price;
		}else{
		$sale_rate =  $this->getItemDetailSaleRate();
		}
		if($total > $sale_rate){
			$total = $sale_rate;
		}
		return $total;
	}
	
	public function getItemDetailMrp(){
		$mrp = $this->mrp;
		if($mrp == '0.00' || $mrp== null){
		$item = $this->item;
		if(isset($item)){
			$mrp = $item->mrp;
		}
		}
		return $mrp;
	}
	public function getItemDetailSaleRate(){
		$mrp = $this->mrp;
		if(isset($this->item)){
			$item = $this->item;
			if(isset($item)){
				$mrp = $item->sale_price;
			}
		}
		return $mrp;
	}
	public function defaultScope()
	{
		return array();
	}
	public function checkStock()
	{
	
		$remaining_quantity = 0;
		
		
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
		$criteria->addCondition('id ='.$this->id);
		$item_detail = ItemDetail::model()->find($criteria);
		
	
		
		if($item_detail){
			$criteria1 = new CDbCriteria();
			$criteria1->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
			$criteria1->addCondition('item_id ='.$item_detail->item_id);
			$criteria1->order = 'id asc';
			$criteria1->limit = '1';
			$first_item_detail = ItemDetail::model()->find($criteria1);
			if($first_item_detail->id != $item_detail->id){
			$stocks = ItemStock::model()->findAllByAttributes(array('item_detail_id'=>$item_detail->id));
			if(!empty($stocks))
			{
				foreach ($stocks as $stock)
				{
					$remaining_quantity += $stock->balance_qty;
				}
			}
			}else{
				$remaining_quantity = 2;
				return $remaining_quantity;
			}
	
			
		}
		if($remaining_quantity < 0)
		{
			$remaining_quantity =0;
		}
		return $remaining_quantity;
	}
	
	public function getItemPrintDetails() {
		$bill_detail_ids = array ();
		$list = array ();
		if (isset ( Yii::app ()->session ['idList'] ) && (Yii::app ()->session ['idList'] != '')) {
			$criteria = new CDbCriteria ();
			$criteria->addInCondition ( 'id', Yii::app ()->session ['idList'] );
			$itemdetails= ItemDetail::model ()->findAll ( $criteria );
			if ($itemdetails) {
				foreach ( $itemdetails as $itemdetail ) {
					$list [$itemdetail->id] = isset ( $itemdetail->item ) ? $itemdetail->bar_code.'('.$itemdetail->item.')' : "";
				}
			}
		}
		return $list;
	}
}