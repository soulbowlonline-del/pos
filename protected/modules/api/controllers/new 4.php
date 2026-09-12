<?php
	public function actionAjaxupdate($id) {
		// $act = $_GET['act'];
		$poIdAll = $_POST;
		
		foreach ( $poIdAll as $qty ) {
			if (isset ( $poIdAll ['qty'] ))
				$qtys = $poIdAll ['qty'];
		}
		
	
	
		if (count ( $qtys ) > 0) {
			
			$purchasebill = B2bPurchaseBill::model()->findByAttributes(array('id'=>$id, 'status'=>'0'));
			if($purchasebill){
				
				$oldstatus = $purchasebill->status;
			$vendor = Vendor::model ()->findByPk ( $purchasebill->vendor_id );
		
			if (isset ( $_POST ['credit_note_id'] ))
				$purchasebill->credit_note_id = $_POST ['credit_note_id'];
			if (isset ( $_POST ['credit_note_disc'] ))
				$purchasebill->credit_note_disc = $_POST ['credit_note_disc'];
			if (isset ( $_POST ['gross_amt'] ))
				$purchasebill->gross_amt = $_POST ['gross_amt'];
			if (isset ( $_POST ['total_discount'] ))
				$purchasebill->total_discount = $_POST ['total_discount'];
			if (isset ( $_POST ['tax_amount'] ))
				$purchasebill->tax_amount = $_POST ['tax_amount'];
			if (isset ( $_POST ['bill_amount'] ))
				$purchasebill->bill_amount = $_POST ['bill_amount'];
			if (isset ( $_POST ['bill_other_discount'] ))
				$purchasebill->bill_other_discount = $_POST ['bill_other_discount'];
			if (isset ( $_POST ['net_bill_amount'] ))
				$purchasebill->net_bill_amount = $_POST ['net_bill_amount'];
			if (isset ( $_POST ['bill_no'] ))
				$purchasebill->bill_no = $_POST ['bill_no'];
			if (isset ( $_POST ['bill_date'] ))
				$purchasebill->end_date = $_POST ['bill_date'];
			
			$purchasebill->start_date = date ( 'Y-m-d' );
			$purchasebill->payment_done = B2bPurchaseBill::PAYMENT_PENDING;
			if (isset ( $_POST ['status'] ))
			$purchasebill->status = $_POST ['status'];
			if ($vendor) {
				$purchasebill->payment_days = $vendor->payment_days;
			}
			if (isset ( $_POST ['is_consignment'] ))
				$purchasebill->is_consignment = $_POST ['is_consignment'];
				if (isset ( $_POST ['status'] )){
				$advancepay = $purchasebill->getAdvancePaymentValue ();
				}else{
					$advancepay = true;
				}
				Yii::log ( CVarDumper::dumpAsString ( $advancepay ), CLogger::LEVEL_WARNING, '$$advancepay' );
				$set = true;
				$transaction = Yii::app ()->db->beginTransaction ();
				try {
			
				if ($purchasebill->save ()) {
					
					if (isset ( $_POST ['status'] ) && ($oldstatus != B2bPurchaseBill::STATUS_APPROVED)){
					$msg = 'B2BPurchaseBill is updated';

					
					$vendor = Vendor::model ()->findByPk ( $purchasebill->vendor_id );
					if ($vendor) {
						$to_id = $vendor->create_user_id;
					} else {
						$to_id = $purchasebill->vendor_id;
					}
					$type = Notification::TYPE_PBILL;
					$model_id = $purchasebill->id;
					Notification::AddNotification ( $model_id, $msg, $type, $to_id );
					}
					if ($oldstatus != B2bPurchaseBill::STATUS_APPROVED){
					foreach ( $qtys as $key => $qty ) {
						
						$model = $this->loadModel ( $key, 'B2bPurchaseBillDetail' );
						$itemdetail = ItemDetail::model ()->findByPk ( $model->item_detail_id );
						$item = Item::model ()->findByPk ( $model->item_id );
						if($poIdAll ['qty'] [$key] == 0){
							if (isset ( $_POST ['status'] ) && ($_POST ['status'] == B2bPurchaseBill::STATUS_APPROVED)){
							$billstock = ItemStock::model ()->findByAttributes ( array (
									'item_detail_id' => $itemdetail->id,
									'item_id' => $itemdetail->item_id,
									'vendor_id' => $purchasebill->vendor_id
							) );
							 if($billstock){
							 	$billstock->outlet_id = $itemdetail->outlet_id;
							 	$billstock->vendor_id = $purchasebill->vendor_id;
							 	$billstock->mrp = $item->mrp;
							 	$billstock->base_price = $item->sale_price;
							 	$billstock->item_id = $item->id;
							 	$billstock->item_detail_id = $itemdetail->id;
							 	if ($billstock->save ()) {
								$billstock->createMrs();
							 	}else{
							 		Yii::log ( CVarDumper::dumpAsString ( $billstock->getErrors() ), CLogger::LEVEL_WARNING, 'error1' );
							 	}
								Yii::log ( CVarDumper::dumpAsString ( $billstock ), CLogger::LEVEL_WARNING, '$billstock' );
							}else{ 
							    if($billstock == null){
							    	$billstock = new ItemStock ();
							    	$batch_no = User::randomBarcode ( '5' );
							    	$billstock->batch_number = $batch_no;
							    	$billstock->balance_qty = 0;
							    	$billstock->purchase_qty = 0;
							    	$billstock->outlet_id = $itemdetail->outlet_id;
							    	$billstock->vendor_id = $purchasebill->vendor_id;
							    	$billstock->mrp = $item->mrp;
							    	$billstock->base_price = $item->sale_price;
							    	$billstock->item_id = $item->id;
							    	$billstock->item_detail_id = $itemdetail->id;
							    	if ($billstock->save ()) {
							    		$billstock->createMrs();
							    		Yii::log ( CVarDumper::dumpAsString ( $billstock ), CLogger::LEVEL_WARNING, '$billstock1' );
							    	}else{
							    		Yii::log ( CVarDumper::dumpAsString ( $billstock->getErrors() ), CLogger::LEVEL_WARNING, 'error2' );
							    	}
							    }
								
							} 
							}
						}
						$model->mrp = $_POST ['mrp'] [$key];
						
						if (isset ( $poIdAll ['mrp'] )) {
							$model->mrp = $poIdAll ['mrp'] [$key];
						}
						if (isset ( $poIdAll ['price'] )) {
							$model->price = $poIdAll ['price'] [$key];
						}
						if (isset ( $poIdAll ['salerate'] )) {
							$model->sale_rate = $poIdAll ['salerate'] [$key];
						}
						if (isset ( $poIdAll ['discount'] )) {
							$model->discount = $poIdAll ['discount'] [$key];
						}
						if (isset ( $poIdAll ['discount_amt'] )) {
							$model->discount_amt = $poIdAll ['discount_amt'] [$key];
						}
						if (isset ( $poIdAll ['discount1'] )) {
							$model->discount1 = $poIdAll ['discount1'] [$key];
						}
						if (isset ( $poIdAll ['discount_amt1'] )) {
							$model->discount_amt1 = $poIdAll ['discount_amt1'] [$key];
						}
						if (isset ( $poIdAll ['taxselectData'] )) {
							$criteria = new CDbCriteria ();
							$criteria->addCondition ( 'item_detail_id =' . $itemdetail->id );
							$itemtax = ItemTax::model ()->find ( $criteria );
							if ($itemtax) {
								$itemtax->tax_id = $poIdAll ['taxselectData'] [$key];
								$itemtax->saveAttributes ( array (
										'tax_id' 
								) );
							}
							$model->tax_id = $poIdAll ['taxselectData'] [$key];
						}
						if (isset ( $poIdAll ['cgstData'] )) {
							$model->cgst_per = $poIdAll ['cgstData'] [$key];
						}
						if (isset ( $poIdAll ['sgstData'] )) {
							$model->sgst_per = $poIdAll ['sgstData'] [$key];
						}
						if (isset ( $poIdAll ['cessData'] )) {
							$model->cess_per = $poIdAll ['cessData'] [$key];
						}
						if (isset ( $poIdAll ['cgstamtData'] )) {
							$model->cgst_amt = $poIdAll ['cgstamtData'] [$key];
						}
						if (isset ( $poIdAll ['sgstamtData'] )) {
							$model->sgst_amt = $poIdAll ['sgstamtData'] [$key];
						}
						if (isset ( $poIdAll ['cessamtData'] )) {
							$model->cess_amt = $poIdAll ['cessamtData'] [$key];
						}
						$tax_data = Tax::model()->findByPk($model->tax_id);
						
					
							if (isset ( $poIdAll ['igstData'] )) {
								// if($tax_data){
									// $model->igst_per = $tax_data->tax_val4;
									// $model->cess_per = $tax_data->tax_val3;
								// }else{
							$model->igst_per = $poIdAll ['igstData'] [$key];
							$model->igst_per = $poIdAll ['igstData'] [$key];
								// }
						}
						if (isset ( $poIdAll ['igstamtData'] )) {
							if($model->igst_per  > 0){
							$model->igst_amt = $poIdAll ['igstamtData'] [$key];
							}else{
								$model->igst_amt = 0;
							}
						}
					
						if (isset ( $poIdAll ['other_charge'] )) {
							$model->other_charge = $poIdAll ['other_charge'] [$key];
						}
						if (isset ( $poIdAll ['amount'] )) {
							$model->amount = $poIdAll ['amount'] [$key];
						}
						if (isset ( $poIdAll ['marginData'] )) {
							$model->margin = $poIdAll ['marginData'] [$key];
						}
						if (isset ( $poIdAll ['hsncode'] )) {
							if($poIdAll ['hsncode'] [$key] != ''){
							$model->hsn_code = $poIdAll ['hsncode'] [$key];
							}
						}
						if (isset ( $poIdAll ['qty'] )) {
							$model->approved_qty = $poIdAll ['qty'] [$key];
							$model->bal_qty = ($model->req_qty - $model->approved_qty);
						}
						$model->create_time = date('Y-m-d H:i:s');
						if ($model->save ()) {
							if (isset ( $_POST ['status'] ) && ($oldstatus != B2bPurchaseBill::STATUS_APPROVED)){
							if ($item) {
								// if ($item->mrp != $model->mrp) {
									// $item->sale_price = $model->mrp;
									
								// }
								// $item->update_time = date('Y-m-d H:i:s');
								// $item->mrp = $model->mrp;
								// if($model->hsn_code != '' && $model->hsn_code != 0){
								// $item->hsn_code = $model->hsn_code;
								// }
								// $item->purchase_price = $model->price;
								// $item->save ();
								// $itemdetail->mrp = $model->mrp;
								// $itemdetail->tax_id = $model->tax_id;
								// $itemdetail->update_time = date('Y-m-d H:i:s');
								
								// $itemdetail->saveAttributes ( array (
										// 'tax_id' ,'mrp','update_time'
								// ) );
							}
							
							
							$itemstock = ItemStock::model ()->findByAttributes ( array (
									'item_detail_id' => $itemdetail->id,
									'item_id' => $itemdetail->item_id,
									'vendor_id' => $purchasebill->vendor_id ,
									'type' => 'B2B'
							) );
							
							
							$qty = number_format($qty, 3, '.', '');
							if ($itemstock == null) {
								$batch_no = User::randomBarcode ( '5' );
								$itemstock = new ItemStock ();
								$purchase = '-'.$qty;
								$balance =  '-'.$qty;
								$itemstock->batch_number = $batch_no;
								$itemstock->type = 'B2B';
							} else {
								$purchase = ($itemstock->purchase_qty) - $qty;
								$balance = ($itemstock->balance_qty) - $qty;
							}
							
							
							$itemstock->item_detail_id = $itemdetail->id;
							$itemstock->base_price = $model->price;
							$itemstock->mrp = $model->mrp;
							$itemstock->vendor_id = $purchasebill->vendor_id;
							$itemstock->outlet_id = $model->outlet_id;
							$itemstock->tax_id = $model->tax_id;
							$itemstock->item_id = $itemdetail->item_id;
							$itemstock->purchase_qty = $purchase;
							$itemstock->balance_qty = $balance;
							$itemstock->create_user_id = Yii::app ()->user->id;
							if ($itemstock->save ()) {
								$remain = $item->getTotalRemainingQuantity();
								$min_qty = $item->min_qty;
								if($remain >$min_qty){
									$mrsdetails = MrsDetail::model()->findAllByAttributes(array('item_id'=>$item->id,
											'status'=>Mrs::STATUS_PENDING
									));
									Yii::log ( CVarDumper::dumpAsString ( $mrsdetails ), CLogger::LEVEL_WARNING, '$mrsdetails' );
									if($mrsdetails){
										foreach($mrsdetails as $mrsdetail){
											$mrs_id = $mrsdetail->mrs_id;
											$criteria1 = new CDbCriteria ();
											
											$criteria1->compare ( "mrs_id ", $mrsdetail->mrs_id );
												
											$mrsItems = MrsDetail::model ()->count ( $criteria1 );
											$mrs = Mrs::model()->findByPk($mrs_id);
											if(($mrs) && ($mrsdetail) && ($item->id == $mrsdetail->item_id) && 
											($mrs->status != Mrs::STATUS_DONE)){
												$mrsdetail->delete();
											}
												
											if($mrsItems == 1){
												$mrs = Mrs::model()->findByPk($mrs_id);
												if(($mrs) && ($item->id == $mrsdetail->item_id) && ($mrs->status != Mrs::STATUS_DONE))
												{
														
													$mrn = Mrn::model()->findByAttributes(array('mrs_id'=>$mrs->id));
													if(!$mrn){
														$mrs->delete();
													}
												}
											}
										}
									}
								}
								
								$stocklog = new StockLog ();
								$stocklog->item_detail_id = $itemstock->item_detail_id;
								$stocklog->item_id = $itemstock->item_id;
								$stocklog->batch_no = $itemstock->batch_number;
								if($itemdetail){
									$stocklog->current_qty = $itemdetail->getStockQty();
									$stocklog->previous_qty = ($itemdetail->getStockQty())+($qty);
									
								}
								$stocklog->Qty = $qty;
								$stocklog->outlet_id = $itemstock->outlet_id;
								$stocklog->vendor_id = $itemstock->vendor_id;
								$stocklog->type_id = StockLog::TYPE_B2B;
								if($stocklog->save ()){
									
								}else{
									$set = false;
									Yii::log ( CVarDumper::dumpAsString ( $stocklog->getErrors() ), CLogger::LEVEL_WARNING, 'error5' );
								}
							}else{
								$set = false;
								Yii::log ( CVarDumper::dumpAsString ( $itemstock->getErrors() ), CLogger::LEVEL_WARNING, 'error4' );
							}
							}
						} 

						else {
							Yii::log ( CVarDumper::dumpAsString ( $model->getErrors() ), CLogger::LEVEL_WARNING, 'error3' );
							$set = false;
							throw new Exception ( "Something went wrong", 500 );
						}
					}
					}
					echo 'Success';
				}else{
					$set = false;
					echo 'Failed';
				}
			
			if ($set == true) {
				$transaction->commit ();
					
			} else {
				$transaction->rollback ();
					
			}
			} catch ( Exception $e ) {
				
				
				echo $e; die;
				$transaction->rollback ();
			}
			}else{
				echo failed;
				
			}
		}
	}
