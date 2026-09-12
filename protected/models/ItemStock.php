<?php


 
/**
 * @property integer $id
 * @property integer $item_detail_id
 * @property integer $item_id
 * @property string $batch_number
 * @property integer $qty
 * @property double $base_price
 * @property double $mrp
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $tax_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemStock');
class ItemStock extends BaseItemStock
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	public function isnetLessMin()
	{
		
		$result = 0;
		$purchasebill_ids = array();
		$item = Item::model()->findByPk($this->item_id);
		
		$mrnDetail = MrnDetail::model()->findByAttributes(array('item_id'=>$this->item_id,'item_detail_id'=>$this->item_detail_id,'status'=>MrnDetail::STATUS_PENDING));
		$poDetail = PurchaseOrderDetail::model()->findByAttributes(array('item_id'=>$this->item_id,'item_detail_id'=>$this->item_detail_id,'status'=>PurchaseOrderDetail::STATUS_PENDING));
		
		$criteria = new CDbCriteria();
		$criteria->addCondition('status !='.PurchaseBill::STATUS_APPROVED);
		$purchasebills = PurchaseBill::model()->findAll($criteria);
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchasebill_ids[] = $purchasebill->id;
			}
		}
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('item_id ='.$this->item_id);
		$criteria1->addCondition('item_detail_id ='.$this->item_detail_id);
		$criteria1->addInCondition('purchase_bill_id',$purchasebill_ids);
		$purchasebills = PurchaseBillDetail::model()->findAll($criteria1);
		
		$itemstocks= ItemStock::model()->findAllByAttributes(array('item_detail_id'=>$this->item_detail_id,'item_id'=>$this->item_id,'outlet_id'=>$this->outlet_id));
		
		foreach($itemstocks as $itemstock)
		{
			$result = $result+($itemstock->balance_qty);
				
		}
	  
		$net_qty = $result;
		if($net_qty > $item->min_qty){
			return false;
		}
		return true;
		/* if(($mrnDetail == null) && ($poDetail == null) && ($purchasebills == null)){
		return true;
		}else{
			return false;
		} */
	}
	public function createB2bMrs(){
		$organization = Organization::model()->find();
		$outlet =  Outlet::model()->find();
		if($outlet){
		$this->outlet_id = $outlet->id;
		}
		$item = Item::model()->findByPk($this->item_id);
	
		$vendor_id = null;
		/* if($this->vendor_id != 0){
		$vendor_id =  $this->vendor_id;
		}else{
		if($item != null){ */
				$criteria = new CDbCriteria();
				$criteria->order = 'id desc';
				$criteria->addCondition('item_detail_id ='.$item->id);
				$vendor =  ItemVendor::model()->find($criteria);
				if($vendor){
					$vendor_id = $vendor->vendor_id;
				}
				$tax = Tax::model()->findByPk($this->tax_id);
				$tax_id = $this->tax_id;
				$itemdetail = Item::model()->findByPk($this->item_detail_id);
				
				if($itemdetail){
				    $tax_id = $itemdetail->tax_id;
				    $tax = Tax::model()->findByPk($tax_id);
				}else{
				    $tax = Tax::model()->findByPk($this->tax_id);
				    $tax_id = $this->tax_id;
				}
				
				
			/* }
		} */
		
		Yii::log ( CVarDumper::dumpAsString ( $vendor_id ), CLogger::LEVEL_WARNING, '$mrs_vendor_id' );
	if($vendor_id != null){
		$mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$vendor_id,
				'outlet_id'=>$this->outlet_id
		));
		Yii::log ( CVarDumper::dumpAsString ( $mrs ), CLogger::LEVEL_WARNING, '$mrs_id' );
		// $criteria = new CDbCriteria();
		// $criteria->addCondition('item_id ='.$this->item_id);
		// $criteria->order = 'id desc';
		// $itemstock = ItemStock::model()->find($criteria);
		// if($itemstock){
			// $reorder_qty = 10/100*$itemstock;
		// }else{
			// $reorder_qty = $item->reorder_qty;
		// }
		/* if(($item->min_qty != '') && ($item->max_qty != '')){
			$reorder_qty = ($item->max_qty) - ($item->min_qty);
		}else{
		$reorder_qty = 10;
		} */
		if($item->reorder_qty != ''){
			//$reorder_qty = $item->getReorderQty();
			$reorder_qty = $item->reorder_qty;
		}else{
			$reorder_qty = 10;
		}
		if($item->max_qty != ''){
			$max_qty = $item->max_qty;
			//$max_qty = $item->getMaximumQty();
		}else{
			$max_qty = 10;
		}
		if($item->min_qty != ''){
			$min_qty = $item->min_qty;
			//$min_qty = $item->getMinimumQty();
		}else{
			$min_qty = 10;
		}
		$updated = true;
		if($mrs == null){
			$updated = false;
			$mrs = new Mrs();
		}
		
	  
		$mrs->code = 'ddd';
		$mrs->mrs_date = date('Y-m-d');
		$mrs->mrs_req_date = date('Y-m-d');
		$mrs->outlet_id = $this->outlet_id;
		
		$mrs->vendor_id = $vendor_id;
	
		Yii::log ( CVarDumper::dumpAsString ( $mrs->vendor_id ), CLogger::LEVEL_WARNING, '$mrs->vendor_id' );
		//$mrs->tax_id = $this->tax_id;
		
		$mrs->organization_id = $organization->id;
		if($mrs->save()){
			$vendor = Vendor::model()->findByPk($mrs->vendor_id);
			
			if($updated){
			$msg = 'MRS is updated';
			}else{
			$msg = 'A new MRS is added';
			}
			$to_id = $vendor->create_user_id;
			$type = Notification::TYPE_MRS;
			$model_id = $mrs->id;
			Notification::AddNotification($model_id,$msg,$type,$to_id);
			$itemdetail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
			$mrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$this->item_detail_id,
					'mrs_id'=>$mrs->id
			));
			if($mrsdetail == null){
				$mrsdetail = new MrsDetail();
			}
			$mrsdetail->price = $item->purchase_price;
			$mrsdetail->req_qty = $max_qty;
			$mrsdetail->approved_qty = $reorder_qty;
			$mrsdetail->min_qty =$min_qty;
			if($tax){
				$mrsdetail->cgst_per = $tax->tax_val1;
				$mrsdetail->sgst_per = $tax->tax_val2;
				$mrsdetail->cess_per = $tax->tax_val3;
				$mrsdetail->igst_per = $tax->tax_val4;
				$mrsdetail->cgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val1/100);
				$mrsdetail->sgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val2/100);
				$mrsdetail->cess_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val3/100);
				$mrsdetail->igst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val4/100);
				$mrsdetail->tax_id = $tax->id;
			}
			
			$mrsdetail->item_detail_id = $this->item_detail_id;
			$mrsdetail->item_id = $this->item_id;
			$mrsdetail->outlet_id = $mrs->outlet_id;
			$mrsdetail->mrp = $itemdetail->getItemDetailMrp();
			$mrsdetail->sale_rate = $itemdetail->getItemDetailSaleRate();
			$mrsdetail->mrs_id = $mrs->id;
			if($mrsdetail->getGSTTrue($mrs->id) == true){
			$mrsdetail->amount =($reorder_qty*($mrsdetail->price))+($mrsdetail->cgst_amt)+($mrsdetail->sgst_amt)+($mrsdetail->cess_amt);
			 $price_cgst = ($mrsdetail->price  * $mrsdetail->cgst_per)/100;
			 $price_sgst = ($mrsdetail->price  * $mrsdetail->sgst_per)/100;
			 $price_cess = ($mrsdetail->price  * $mrsdetail->cess_per)/100;
			 $calgst = $price_cgst+$price_sgst +$price_cess;
			}else{
				$mrsdetail->amount =($reorder_qty*$mrsdetail->price)+($mrsdetail->igst_amt);
				$price_igst = ($mrsdetail->price  * $mrsdetail->igst_per)/100;
				$calgst = $price_igst;
			}
			if($mrsdetail->price != '0.00' && $mrsdetail->price != null){
			$margin = (($mrsdetail->mrp)-($mrsdetail->price + $calgst))*100/($mrsdetail->price + $calgst);
			$mrsdetail->margin = $margin;
			}
			
			if($mrsdetail->save()){
				
			}else{
				print_r($mrsdetail->getErrors());exit;
			}
			
		}else{
			print_r($mrs->getErrors());exit;
		}
	}
		
		return true;
	}
	
	
	
	public function createMrs(){
		$organization = Organization::model()->find();
		$outlet =  Outlet::model()->find();
		if($outlet){
		$this->outlet_id = $outlet->id;
		}
		$item = Item::model()->findByPk($this->item_id);
	
		$vendor_id = null;
		/* if($this->vendor_id != 0){
		$vendor_id =  $this->vendor_id;
		}else{
		if($item != null){ */
				$criteria = new CDbCriteria();
				$criteria->order = 'id desc';
				$criteria->addCondition('item_detail_id ='.$item->id);
				$vendor =  ItemVendor::model()->find($criteria);
				if($vendor){
					$vendor_id = $vendor->vendor_id;
				}
				$tax = Tax::model()->findByPk($this->tax_id);
				$tax_id = $this->tax_id;
				$itemdetail = Item::model()->findByPk($this->item_detail_id);
				
				if($itemdetail){
				    $tax_id = $itemdetail->tax_id;
				    $tax = Tax::model()->findByPk($tax_id);
				}else{
				    $tax = Tax::model()->findByPk($this->tax_id);
				    $tax_id = $this->tax_id;
				}
				
				
			/* }
		} */
		Yii::log ( CVarDumper::dumpAsString ( $tax_id ), CLogger::LEVEL_WARNING, '$mrs_tax_id' );
		Yii::log ( CVarDumper::dumpAsString ( $vendor_id ), CLogger::LEVEL_WARNING, '$mrs_vendor_id' );
	if($vendor_id != null){
		$mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$vendor_id,
				'outlet_id'=>$this->outlet_id
		));
		Yii::log ( CVarDumper::dumpAsString ( $mrs ), CLogger::LEVEL_WARNING, '$mrs_id' );
		// $criteria = new CDbCriteria();
		// $criteria->addCondition('item_id ='.$this->item_id);
		// $criteria->order = 'id desc';
		// $itemstock = ItemStock::model()->find($criteria);
		// if($itemstock){
			// $reorder_qty = 10/100*$itemstock;
		// }else{
			// $reorder_qty = $item->reorder_qty;
		// }
		/* if(($item->min_qty != '') && ($item->max_qty != '')){
			$reorder_qty = ($item->max_qty) - ($item->min_qty);
		}else{
		$reorder_qty = 10;
		} */
		if($item->reorder_qty != ''){
			//$reorder_qty = $item->getReorderQty();
			$reorder_qty = $item->reorder_qty;
		}else{
			$reorder_qty = 10;
		}
		if($item->max_qty != ''){
			$max_qty = $item->max_qty;
			//$max_qty = $item->getMaximumQty();
		}else{
			$max_qty = 10;
		}
		if($item->min_qty != ''){
			$min_qty = $item->min_qty;
			//$min_qty = $item->getMinimumQty();
		}else{
			$min_qty = 10;
		}
		$updated = true;
		if($mrs == null){
			$updated = false;
			$mrs = new Mrs();
		}
		
	  
		$mrs->code = 'ddd';
		$mrs->mrs_date = date('Y-m-d');
		$mrs->mrs_req_date = date('Y-m-d');
		$mrs->outlet_id = $this->outlet_id;
		
		$mrs->vendor_id = $vendor_id;
	
		Yii::log ( CVarDumper::dumpAsString ( $mrs->vendor_id ), CLogger::LEVEL_WARNING, '$mrs->vendor_id' );
		//$mrs->tax_id = $this->tax_id;
		
		$mrs->organization_id = $organization->id;
		if($mrs->save()){
			$vendor = Vendor::model()->findByPk($mrs->vendor_id);
			
			if($updated){
			$msg = 'MRS is updated';
			}else{
			$msg = 'A new MRS is added';
			}
			$to_id = $vendor->create_user_id;
			$type = Notification::TYPE_MRS;
			$model_id = $mrs->id;
			Notification::AddNotification($model_id,$msg,$type,$to_id);
			$itemdetail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
			$mrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$this->item_detail_id,
					'mrs_id'=>$mrs->id
			));
			if($mrsdetail == null){
				$mrsdetail = new MrsDetail();
			}
			$mrsdetail->price = $item->purchase_price;
			$mrsdetail->req_qty = $max_qty;
			$mrsdetail->approved_qty = $reorder_qty;
			$mrsdetail->min_qty =$min_qty;
			if($tax){
				$mrsdetail->cgst_per = $tax->tax_val1;
				$mrsdetail->sgst_per = $tax->tax_val2;
				$mrsdetail->cess_per = $tax->tax_val3;
				$mrsdetail->igst_per = $tax->tax_val4;
				$mrsdetail->cgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val1/100);
				$mrsdetail->sgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val2/100);
				$mrsdetail->cess_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val3/100);
				$mrsdetail->igst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val4/100);
				$mrsdetail->tax_id = $tax->id;
			}
			
			$mrsdetail->item_detail_id = $this->item_detail_id;
			$mrsdetail->item_id = $this->item_id;
			$mrsdetail->outlet_id = $mrs->outlet_id;
			$mrsdetail->mrp = $itemdetail->getItemDetailMrp();
			$mrsdetail->sale_rate = $itemdetail->getItemDetailSaleRate();
			$mrsdetail->mrs_id = $mrs->id;
			if($mrsdetail->getGSTTrue($mrs->id) == true){
			$mrsdetail->amount =($reorder_qty*($mrsdetail->price))+($mrsdetail->cgst_amt)+($mrsdetail->sgst_amt)+($mrsdetail->cess_amt);
			 $price_cgst = ($mrsdetail->price  * $mrsdetail->cgst_per)/100;
			 $price_sgst = ($mrsdetail->price  * $mrsdetail->sgst_per)/100;
			 $price_cess = ($mrsdetail->price  * $mrsdetail->cess_per)/100;
			 $calgst = $price_cgst+$price_sgst +$price_cess;
			}else{
				$mrsdetail->amount =($reorder_qty*$mrsdetail->price)+($mrsdetail->igst_amt);
				$price_igst = ($mrsdetail->price  * $mrsdetail->igst_per)/100;
				$calgst = $price_igst;
			}
			if($mrsdetail->price != '0.00' && $mrsdetail->price != null){
			$margin = (($mrsdetail->mrp)-($mrsdetail->price + $calgst))*100/($mrsdetail->price + $calgst);
			$mrsdetail->margin = $margin;
			}
			
			if($mrsdetail->save()){
				Yii::log ( CVarDumper::dumpAsString ( $mrsdetail ), CLogger::LEVEL_WARNING, '$mrsdetail_a' );
			}else{
				print_r($mrsdetail->getErrors());exit;
			}
			
		}else{
			print_r($mrs->getErrors());exit;
		}
	}
		
		return true;
	}
}