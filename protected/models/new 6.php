<?php
/*Create MRS section*/
										$criteriaMrs = new CDbCriteria();
										$criteriaMrs->order = 'id desc';
										$criteriaMrs->limit = '1';
										$criteriaMrs->addCondition('vendor_id ='.$model->vendor_id);
										$vendorMRS = Mrs::model()->find($criteriaMrs);
								
								
									if($vendorMRS->id){
									$item = Item::model()->findByPk($item->id);
									$criteriaMrsD = new CDbCriteria();
									$criteriaMrsD->order = 'id desc';
									$criteriaMrsD->limit = '1';
									$criteriaMrsD->addCondition('mrs_id ='.$vendorMRS->id);
									$criteriaMrsD->addCondition('item_id ='.$item->id);
									$vendorMRSD = MrsDetail::model()->find($criteriaMrsD);
									
									
									if(empty($vendorMRSD)){
									
										/*Create MRS*/
										// $itemdetail = Item::model()->findByPk($item->item_id);
										$organization = Organization::model()->find();
										$itemdetail_ = ItemDetail::model()->findByPk($itemDetail->id);
										$tax='';
										$tax_id='';
										if($itemdetail_){
											$tax = Tax::model()->findByPk($itemdetail_->tax_id);
										$tax_id = $itemdetail_->tax_id;
										}
										Yii::log ( CVarDumper::dumpAsString ( $model->vendor_id ), CLogger::LEVEL_WARNING, '$mrs_vendor_id' );
										if($model->vendor_id != null){
										$mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$model->vendor_id,
										'outlet_id'=>$model->outlet_id
										));
										Yii::log ( CVarDumper::dumpAsString ( $mrs ), CLogger::LEVEL_WARNING, '$mrs_id' );
										
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
										
										
										if($min_qty >= $mrsItemStock->balance_qty){
											
											$updated = true;
										if($mrs == null){
										$updated = false;
										$mrs = new Mrs();
										}


										$mrs->code = 'ddd';
										$mrs->mrs_date = date('Y-m-d');
										$mrs->mrs_req_date = date('Y-m-d');
										$mrs->outlet_id = $model->outlet_id;
										$mrs->vendor_id = $model->vendor_id;
									
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
										// $type = Notification::TYPE_MRS;
										$model_id = $mrs->id;
										
										$itemdetail = ItemDetail::model ()->findByPk ( $itemDetail->id );
										$mrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$model->item_detail_id,
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

										$mrsdetail->item_detail_id = $itemDetail->id;
										$mrsdetail->item_id =$model->item_id;
										$mrsdetail->outlet_id = $mrs->outlet_id;
										$mrsdetail->mrp = $itemdetail->getItemDetailMrp();
										$mrsdetail->sale_rate = $itemdetail->getItemDetailSaleRate();
										$mrsdetail->mrs_id = $mrs->id;
										$mrsdetail->discount = "0.00";
										$mrsdetail->discount_amt = "0.00";
										$mrsdetail->other_charge = "0.00";
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

										} 
										
											
										}
										
										/*End Create MRS*/
												
									}
									
								}
							}
							
							}