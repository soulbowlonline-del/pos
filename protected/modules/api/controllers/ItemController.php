<?php
class ItemController extends GxController {
	public function filters() {
		return array (
				'accessControl'
		);
	}
	public function accessRules() {
		return array (
				array (
						'allow',
						'actions' => array (
								'getItem','list',
								'order','ordertest',
								'updateStock','getGRN',
								'getGRNItems','search','billupdate',/* 'download', 'thumbnail' */
								'barcode','adjust','adjustitemtozero', 'scannedItem', 'punchorder'
							),
						'users' => array (
								'*'
						)
				),
				array (
						'allow',
						'actions' => array (
								'create',
								'update',
								'search'
						)
						,
						'users' => array (
								'@'
						)
				),
				array (
						'allow',
						'actions' => array (
								'admin',
								'delete'
						),
						'expression' => 'Yii::app()->user->isAdmin'
				),
				array (
						'deny',
						'users' => array (
								'*'
						)
				)
		);
	}
	
	
	
	
	
	
		public function actionOrdertest() {
	 						$arr = array (
	 								'controller' => $this->id,
	 								'action' => $this->action->id,
	 								'status' => 'NOK'
	 						);

	 						$model = new Customer ();

	 						$headers = getallheaders ();
	 					
	 						$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
	 						//$loginid = 1;
	 						Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, '$_POST' );
	 						if ($loginid) {
	 							if (isset ( $_POST ['item_details'] ) && isset ( $_POST ['mode_of_payment'] ) && isset ( $_POST ['mode_of_delivery'] ) && isset ( $_POST ['status_id'] )) {
	 									
	 									
	 								$status = $_POST ['status_id'];
	 									
	 								if ($status == '1') {
	 									$order = new Order ();
	 								} elseif ($status == '2') {
	 									$order = new OrderHold ();
	 								} else {
	 									$arr ['message'] = 'Order status wrong';
	 									$this->sendJSONResponse ( $arr );
	 								}
	 									
	 								$set = true;
	 								
	 								 $transaction = Yii::app ()->db->beginTransaction ();
	 								try { 
	 									if(isset($_POST ['customer_id'])){
	 										$customer = Customer::model()->findByPk($_POST ['customer_id'] );
	 									}
	 									$month = date('m');
	 									if($month > 3){
	 										$year = date('Y');
	 										$yearlast = $year + 1;
	 										$start_date = $year.'-04-01';
	 										$end_date = $yearlast.'-03-31';
	 									}else{
	 										$year = date('Y');
	 										$yearlast = $year - 1;
	 										$start_date = $yearlast.'-04-01';
	 										$end_date = $year.'-03-31';
	 									}
	 									/* $criteria = new CDbCriteria();
	 									$criteria->order = 'bill_no desc';
	 									if($start_date != '' && $end_date != ''){
	 										$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
	 									}
	 									$latestorder = Order::model()->find($criteria);
	 									if($latestorder){
	 										$bill_no = $latestorder->bill_no + 1;
	 									}else{
	 										$bill_no = 1;
	 									}

	 									$order->bill_no = $bill_no; */
	 									$order->bill_date = date('Y-m-d');
	 									$order->mode_of_payment = $_POST ['mode_of_payment'];
	 									$order->mode_of_delivery = $_POST ['mode_of_delivery'];
	 									$order->total_amt = $_POST ['total_amt'];
	 									$order->discount_amt = $_POST ['discount_amt'];
	 									$order->outlet_id = $_POST ['outlet_id'];
	 									if(isset($_POST ['customer_id'])){
	 										$order->city_id = $customer->city_id;
	 										$order->state_id = $customer->state_id;
	 										$order->country_id = $customer->country_id;
	 										$order->customer_id = $customer->id;
	 									}
	 									if(isset($_POST ['online_order_id'])){
	 										$order->online_order_id = $_POST ['online_order_id'];
	 									}
	 									if(isset($_POST ['is_mobile'])){
	 										$order->is_mobile = $_POST ['is_mobile'];
	 									}
	 									
	 									$order->create_user_id = $loginid;
	 									if ($order->save ()) {
	 										if ($status == '1') {
	 											if(isset($_POST['credit_note_id'])){

	 												$creditnot = CreditNote::model()->findByAttributes(array('credit_number'=>$_POST['credit_note_id']));
	 												if($creditnot){
	 													$remain_amt = $creditnot->amt - $creditnot->amt_used;
	 													if(($remain_amt) >= ($order->total_amt)){
	 														$creditnot->amt_used = ($creditnot->amt_used) + $order->total_amt;
	 														$creditnot->save();
	 													}else{
	 														$set = false;
	 														$arr ['message'] = 'Credit note amount is less than total amount';
	 													}
	 												}else{
	 													$set = false;
	 													$arr ['message'] = 'Credit note is not found';
	 												}
	 											}
	 										}
	 										$item_arrays = json_decode ( $_POST ['item_details'] );
	 											
	 										if ($item_arrays) {
	 											foreach ( $item_arrays as $item_array ) {
	 													
	 												if ($status == '1') {
	 													$orderItem = new OrderItem ();
	 												} else {
	 													$orderItem = new OrderHoldItem ();
	 												}
	 												$criteria1 = new CDbCriteria ();
	 												$criteria1->compare ( "bar_code ", $item_array->bar_code);
	 												$itemdetail = ItemDetail::model ()->find ( $criteria1 );
	 													
	 												if($itemdetail){
	 														
	 													$orderItem->item_detail_id = $itemdetail->id;
	 													$orderItem->item_id = $itemdetail->item_id;
	 													$orderItem->qty = $item_array->qty;
	 													$orderItem->price = $orderItem->remove_format($item_array->base_price);
	 													if($item_array->discount_id != 0){
	 														$orderItem->discount_id = $item_array->discount_id;
	 															
	 														$orderItem->discount_amt = $item_array->discount_amt;
	 													}
	 													if($item_array->tax_id != 0){
	 														$orderItem->tax_id = $orderItem->getTaxValueID($item_array->tax_id);
	 														if ($status == '1') {
	 															$orderItem->original_tax = $item_array->tax_id;
	 														}
	 														$orderItem->tax_amount = $item_array->tax_amt;
	 													}
	 													$orderItem->sale_rate = $item_array->sale_rate;
	 													$orderItem->mrp = $item_array->mrp;
	 													$orderItem->total_amt = $item_array->total_amount;
	 														
	 													$orderItem->cgst_per = $item_array->cgst_per;
	 													$orderItem->sgst_per = $item_array->sgst_per;
	 													$orderItem->cess_per = $item_array->cess_per;
	 													$orderItem->igst_per = $item_array->igst_per;
	 													$orderItem->cgst_amt = $item_array->cgst_amt;
	 													$orderItem->sgst_amt = $item_array->sgst_amt;
	 													$orderItem->cess_amt = $item_array->cess_amount;
	 													$orderItem->igst_amt = $item_array->igst_amount;
	 													$orderItem->create_user_id = $loginid;
	 													if ($status == '1') {
	 														$orderItem->order_id = $order->id;
	 													}else{
	 														$orderItem->order_hold_id = $order->id;
	 													}
	 													Yii::log ( CVarDumper::dumpAsString ( $orderItem ), CLogger::LEVEL_WARNING, '$orderItem' );
	 														
	 													if ($orderItem->save ()) {

	 														if ($status == '1') {
	 															/*if(isset($_POST ['online_order_id'])){
	 															$online_order = OnlineOrder::model()->findByAttributes(array('id'=>$_POST['online_order_id']));
	 															if($online_order){
	 																$online_order->order_status = OnlineOrder::ORDERSTATUS_PACKED;
	 																$online_order->saveAttributes(array('order_status'));
	 																
	 																$order_id = $online_order->order_id;
	 																//$order_id = 100007417;
	 																$ch = curl_init ();
	 																
	 																curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api" );
	 																curl_setopt ( $ch, CURLOPT_POST, 1 );
	 																
	 																curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
	 																		'sKeY' => getenv('POS_SOULBOWL_KEY'),'order_id'=>$online_order->order_id,'action'=>'update_dispatch_status','status'=>'1'
	 																) ) );
	 																
	 																curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	 																
	 																$server_output = curl_exec ( $ch );
	 																
	 																curl_close ( $ch );
	 																$response = json_decode ( $server_output, true );
	 															}
	 															}*/
	 															$order->UpdateStock($item_array->qty,$itemdetail->id);
	 														}

	 													} else {
	 															
	 														//print_r($orderItem->getErrors());exit;
	 														$set = false;
	 													}
	 												}else{
	 													$set = false;
	 												}
	 													
	 													
	 											}
	 										}
	 											
	 										if ($set == true) {
	 											$transaction->commit ();
	 											$criteria = new CDbCriteria();
	 											$criteria->order = 'bill_no desc';
	 											if($start_date != '' && $end_date != ''){
	 												$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
	 											}
	 											$latestorder = Order::model()->find($criteria);
	 											if($latestorder){
	 												$bill_no = $latestorder->bill_no + 1;
	 											}else{
	 												$bill_no = 1;
	 											}
	 												
	 											$order->bill_no = $bill_no;
	 											$order->saveAttributes(array('bill_no'));
	 											if ($status == '1') {
												$data = array();
												
												$item_list = array();
												$itemorderitems = OrderItem::model()->findAllByAttributes(array('order_id'=>$order->id));
												if($itemorderitems){
												foreach($itemorderitems as $orderitem){
												$get_item = Item::model()->findByPk($orderitem->item_id);
													if($get_item){
													    $item_list[$get_item->item_code] = array('name'=>$get_item->title,'qty'=>$orderitem->qty,'price'=>($orderitem->qty *$orderitem->sale_rate));
													}
												}
												}
												$grand = $order->total_amt;
												$shipping = '0.00';
												/*if($order->total_amt >= 2000){
													$shipping = '0.00';
												}else{
													$shipping = '50.00';
													$grand = $order->total_amt + $shipping;
												}*/

												$data['items']= $item_list;
												$data['sub_total']= $order->total_amt;
												$data['grand_total']= $grand;
												$data['shipping']= $shipping;

												$item_str = '';
												$item_str = json_encode($data);
													Yii::log ( CVarDumper::dumpAsString ( $item_str ), CLogger::LEVEL_WARNING, '$item_str' );
						
												if(isset($_POST ['online_order_id'])){
													$date = date('Y-m-d H:i:s');
	 															$online_order = OnlineOrder::model()->findByAttributes(array('id'=>$_POST['online_order_id']));
	 															if($online_order){
	 																$online_order->order_status = OnlineOrder::ORDERSTATUS_PACKED;
	 																$online_order->saveAttributes(array('order_status'));
	 																
	 																$order_id = $online_order->order_id;
	 															//	$order_id = 100007417;
	 																$ch = curl_init ();
	 																
	 																curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api" );
	 																curl_setopt ( $ch, CURLOPT_POST, 1 );
	 																
	 																curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
	 																		'sKeY' => getenv('POS_SOULBOWL_KEY'),'order_id'=>$online_order->order_id,'action'=>'update_dispatch_status2','status'=>'1','data'=>$item_str,'date'=>$date
	 																) ) );
	 																
	 																curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	 																
	 																$server_output = curl_exec ( $ch );
	 																
	 																curl_close ( $ch );
	 																$response = json_decode ( $server_output, true );
	 															}
	 															}
													}
												
	 											$taxarr = array();
												if ($status == '1') {
												$order->SendSms();
												}
	 											$arr['status'] = 'OK';
												
	 											if ($status == '1') {
	 												$bill_prefix = 'B';
	 												$outlet = Outlet::model()->findByPk($order->outlet_id);
	 												if($outlet){
	 													if($outlet->bill_prefix != ''){
	 														$bill_prefix = $outlet->bill_prefix;
	 													}else{
	 														$bill_prefix = 'B';
	 													}
	 														
	 												}
	 												$arr['bill_no'] = $order->getOrderBillNo();
	 												$criteria = new CDbCriteria();
	 												$criteria->group = 'tax_id';
	 												// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
	 												// explicitly by the grouped columns to preserve the previous output order.
	 												$criteria->order = 'tax_id';
	 												$criteria->compare('order_id',$order->id);
	 												$itemms = OrderItem::model()->findAll($criteria);

	 												if($itemms){
	 													foreach($itemms as $itemmtax){
	 														$taxarr[] = $itemmtax->getTaxArray();
	 													}
	 												}
	 												$arr['taxes'] =$taxarr;
													
	 											}else{
	 												$arr['bill_no'] = '0';
	 											}
												
	 											$arr ['message'] = 'Order is saved Successfully';
	 										} else {
	 											$transaction->rollback ();
	 											$arr ['message'] = 'Try again';
	 										}
	 									
	 									}

	 									else {
	 											//print_r($order->getErrors());exit;
	 										$set = false;
	 									}
	 								 } catch ( Exception $e ) {
	 									$transaction->rollback ();
	 								} 
	 							}
	 						}
	 						$this->sendJSONResponse ( $arr );
	 						Yii::log ( CVarDumper::dumpAsString ( $this->sendJSONResponse ( $arr ) ), CLogger::LEVEL_WARNING, 'order_response' );
	 					}
	public function actionBillUpdate(){
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$purchaseBill = PurchaseBill::model()->findByPk('97');
		if($purchaseBill){
			$purchaseBill->status = 0;
			$purchaseBill->save();
		}
		$arr ['status'] = 'OK';
		$this->sendJSONResponse ( $arr );
	}
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionGetGRN() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);

		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if($loginid == ''){
			$loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
		}
		//$loginid = '1';
		if ($loginid) {
			$user = User::model()->findByPk($loginid);
			if($user){
				$emp = Emp::model()->findByPk($user->emp_id);
				if($emp){
					$outlet_id = $emp->outlet_id;
				}else{
					$outlet = Outlet::model()->find();
					if($outlet){
						$outlet_id = $outlet->id;
					}
				}
						
					// ORDER BY added: MySQL 8 no longer returns an implicit order, and
					// the Yii 2 port has to agree with this one. Same fix as the other
					// unordered API queries.
					$purchaseBills = PurchaseBill::model ()->findAllByAttributes ( array (
							'outlet_id' => $outlet_id,'status'=>PurchaseBill::STATUS_UNAPPROVED
					), array ( 'order' => 'id ASC' ) );
					if ($purchaseBills) {
						$json_list = array ();
						foreach ( $purchaseBills as $purchaseBill ) {
							$json_list[] = array('id'=>$purchaseBill->id);
						}

						$arr ['status'] = 'OK';

						$arr ['grns'] = $json_list;
					}
				
			}
		}

		$this->sendJSONResponse ( $arr );
	}
	public function actionGetGRNItems($id) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		Yii::log ( CVarDumper::dumpAsString ( $id ), CLogger::LEVEL_WARNING, '$id' );
		if ($id != null) {
			
			$purchaseBill = PurchaseBill::model ()->findByPk ( $id );
			// ORDER BY added - see actionGetGRN
			$purchaseBillDetails = PurchaseBillDetail::model ()->findAllByAttributes ( array (
					'purchase_bill_id' => $id
			), array ( 'order' => 'id ASC' ) );
			if ($purchaseBillDetails) {
				$json_list = array ();
				foreach ( $purchaseBillDetails as $purchaseBillDetail ) {
					$json_list[] = $purchaseBillDetail->toArray ();
				}

				$arr ['status'] = 'OK';

				$arr ['items'] = $json_list;
			}
		}

		$this->sendJSONResponse ( $arr );
	}
	public function actionUpdateStock() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		//$_POST ['stock_details'] = '[{"bar_code":"17859","qty":"7","purchase_bill_id":"2","entry_position":"1"}]';
		if (isset ( $_POST ['stock_details'] )){
			Yii::log ( CVarDumper::dumpAsString ( $_POST ['stock_details'] ), CLogger::LEVEL_WARNING, '$_POST' );
			$headers = getallheaders ();
			$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
			if($loginid == ''){
				$loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
			}
			//$loginid = '399';
			$stocks = json_decode($_POST ['stock_details'] );
			//	$loginid = 1;
			if ($loginid) {
				if($stocks){
					if(isset($stocks['0'])){
						$firststock = $stocks['0'];
						$getpurchaseBill = PurchaseBill::model ()->findByPk ( $firststock->purchase_bill_id );
							
						if($getpurchaseBill->status == PurchaseBill::STATUS_UNAPPROVED){
							$getpurchaseBill->status = PurchaseBill::STATUS_RECEIVED;
							$getpurchaseBill->save();
							foreach($stocks as $stock){
									
								if (isset ( $stock->bar_code ) && isset ( $stock->qty ) && isset ( $stock->purchase_bill_id )) {
									$code = $stock->bar_code ;
									$criteria = new CDbCriteria ();
									$criteria->compare ( "bar_code ", $code );
									$itemdetail = ItemDetail::model ()->find ( $criteria );
									$purchaseBill = PurchaseBill::model ()->findByPk ( $stock->purchase_bill_id );
									$purchaseBillDetail = PurchaseBillDetail::model ()->findByAttributes ( array (
											'purchase_bill_id' => $stock->purchase_bill_id ,
											'item_detail_id' => $itemdetail->id

									) );
									Yii::log ( CVarDumper::dumpAsString ( $loginid ), CLogger::LEVEL_WARNING, '$loginid' );
									Yii::log ( CVarDumper::dumpAsString ( $purchaseBillDetail ), CLogger::LEVEL_WARNING, '$purchaseBillDetail' );
									if(isset($stock->batch_no  )){
										$batch_no = $stock->batch_no;
									}else{
										$batch_no =  User::randomBarcode('5');
									}
									if ($itemdetail && $purchaseBillDetail) {
										$tax = Tax::model()->findByPk($purchaseBillDetail->tax_id);
										if($tax){
											$purchaseBillDetail->cgst_amt = ($stock->qty * $purchaseBillDetail->price)*($purchaseBillDetail->cgst_per/100);
											$purchaseBillDetail->sgst_amt = ($stock->qty * $purchaseBillDetail->price)*($purchaseBillDetail->sgst_per/100);
											$purchaseBillDetail->cess_amt = ($stock->qty * $purchaseBillDetail->price)*($purchaseBillDetail->cess_per/100);
											$purchaseBillDetail->igst_amt = ($stock->qty * $purchaseBillDetail->price)*($purchaseBillDetail->igst_per/100);
										}
											
										if($purchaseBillDetail->getGSTTrue($stock->purchase_bill_id) == true){
											$purchaseBillDetail->amount =($stock->qty*($purchaseBillDetail->price))+($purchaseBillDetail->cgst_amt)+($purchaseBillDetail->sgst_amt)+($purchaseBillDetail->cess_amt);
											$price_cgst = ($purchaseBillDetail->price  * $purchaseBillDetail->cgst_per)/100;
											$price_sgst =  ($purchaseBillDetail->price  * $purchaseBillDetail->sgst_per)/100;
											$price_cess = ($purchaseBillDetail->price  * $purchaseBillDetail->cess_per)/100;
											$calgst = $price_cgst+$price_sgst +$price_cess;
										}else{
											$purchaseBillDetail->amount =($stock->qty*$purchaseBillDetail->price)+($purchaseBillDetail->igst_amt);
											$price_igst = ($purchaseBillDetail->price  * $purchaseBillDetail->igst_per)/100;
											$calgst = $price_igst;
										}
										if($purchaseBillDetail->price != '0.00'){
											$margin = (($purchaseBillDetail->mrp)-($purchaseBillDetail->price + $calgst))*100/($purchaseBillDetail->price + $calgst);
											$purchaseBillDetail->margin = $margin;
										}
										$purchaseBillDetail->approved_qty = $stock->qty;
										$purchaseBillDetail->order = $stock->entry_position;
										$purchaseBillDetail->save();
											
										$model = ItemStock::model ()->findByAttributes ( array (
												'item_detail_id' => $itemdetail->id,
												'item_id' => $itemdetail->item_id,
												'batch_number' => $batch_no
										) );

										if ($model == null) {
											$model = new ItemStock ();
											$purchase =$stock->qty;
											$balance =$stock->qty;
										} else {
											$purchase = ($model->purchase_qty) + $stock->qty;
											$balance = ($model->balance_qty) + $stock->qty;
										}
										$model->batch_number = $batch_no;
										$model->item_detail_id = $itemdetail->id;
										$model->base_price = $purchaseBillDetail->price;
										$model->mrp = $purchaseBillDetail->mrp;
										$model->vendor_id = $purchaseBill->vendor_id;
										$model->outlet_id = $purchaseBillDetail->outlet_id;
										$model->tax_id = $purchaseBillDetail->tax_id;
										$model->item_id = $itemdetail->item_id;
										$model->purchase_qty = $purchase;
										$model->balance_qty = $balance;
										$model->create_user_id = $loginid;
										//if ($model->save ()) {
										/* $stocklog = new StockLog();
										 $stocklog->item_detail_id = $model->item_detail_id;
										 $stocklog->item_id = $model->item_id;
										 $stocklog->batch_no = $model->batch_number;
										 $stocklog->Qty =  $stock->qty;
										 $stocklog->outlet_id = $model->outlet_id;
										 $stocklog->vendor_id = $model->vendor_id;
										 $stocklog->type_id = StockLog::TYPE_ADDED;
										 $stocklog->save(); */
										$arr ['status'] = 'OK';
										//}
									}
								}
							}
								
						}else{
							$arr ['status'] = 'OK';
							$arr ['message'] = 'GRN is already received';
						}
					}
						
						
						
				}
			}
		}

		$this->sendJSONResponse ( $arr );
	}
	public function actionList() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$list = array();
		$criteria = new CDbCriteria ();
		$criteria->limit = '50';
		$count=ItemDetail::model()->count($criteria);
		$pages=new CPagination($count);
		$pages->pageSize=10;
		$pages->applyLimit($criteria);
		$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
		// Deterministic page contents; without an order the same page could
		// return different items between calls.
		$criteria->order = 'id ASC';
		$itemdetails=ItemDetail::model()->findAll($criteria);
		if($itemdetails){
			foreach($itemdetails as $itemdetail){
				$list[] = $itemdetail->toonlineArray();
			}
		}
		$arr ['status'] = 'OK';

		$arr ['item'] = $list;


		$this->sendJSONResponse ( $arr );
	}
	public function actiongetItem($code = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$price = null;
		if($code != null){
			$array = explode("!", $code, 2);
			if(isset($array[0])){
				$code = $array[0];
			}
			if(isset($array[1])){
				$price = $array[1];
			}
			$criteria = new CDbCriteria ();
			$criteria->compare ( "bar_code ", $code );
			$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
			// Deterministic item lookup; barcodes are not guaranteed unique.
			$criteria->order = 'id ASC';
			$itemdetail = ItemDetail::model ()->find ( $criteria );

			if ($itemdetail) {
					
				$json_list = array ();
					
				$json_list[] = $itemdetail->toArray ($price);
					
				$arr ['status'] = 'OK';
					
				$arr ['item'] = $json_list;
			}
		}else{
			$list = array();
			$criteria = new CDbCriteria ();
			$criteria->limit = '50';
			$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
			$criteria->order = 'id ASC';
			$itemdetails = ItemDetail::model ()->findAll($criteria);
			if($itemdetails){
				foreach($itemdetails as $itemdetail){
					$list[] = $itemdetail->toArray();
				}
			}
			$arr ['status'] = 'OK';

			$arr ['item'] = $list;
		}

		$this->sendJSONResponse ( $arr );
	}
		public function actionSearch($name = null,$rate=null,$title=null) {
		// Initialised up front: if rows match but none passes the stock
		// check below, this was read while undefined - a PHP 8 warning that
		// Yii 1 turns into a 500.
		$json_list = array(); // initialised
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);

		$criteria = new CDbCriteria ();
		$criteria->limit = '50';
		$criteria->with ='item';
		if($name != null && $title != null){
			$criteria->condition = "item.title LIKE :title AND item.title LIKE :title1 ";
			$criteria->params = array(':title' => trim($name) . '%',':title1' => '%'.trim($title) . '%');
				
		}else if($title != null){
			$criteria->condition = "item.title LIKE :title ";
				
			$criteria->params = array(':title' => '%'.trim($title) . '%');
		}
			
		else if($name != null){
			$criteria->condition = "item.title LIKE :title ";
				
			$criteria->params = array(':title' => trim($name) . '%');
		}
			
		if($rate != null){
			$criteria->compare ( "item.sale_price ", $rate,true );
		}
		$criteria->addCondition('item.status ='.Item::STATUS_ACTIVE);
		$criteria->addCondition('t.status ='.ItemDetail::STATUS_ACTIVE);
		// Deterministic search results. Without an order the LIMIT 50 took
		// whichever fifty rows MySQL happened to return, so the same search
		// could list different items between calls.
		$criteria->order = 't.id ASC';
		$itemdetails = ItemDetail::model ()->findAll( $criteria );
		Yii::log ( CVarDumper::dumpAsString ( $criteria ), CLogger::LEVEL_WARNING, '$criteria' );
	/* 	if ($items) {
				
			$json_list = array ();
			foreach($items as $item){
				$criteria = new CDbCriteria ();
				$criteria->limit = '50';
				$criteria->compare ( "item_id ", $item->id );
				$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
				$itemdetails = ItemDetail::model ()->findAll( $criteria ); */
				if($itemdetails){
					foreach($itemdetails as $itemdetail){
						$stock = $itemdetail->checkStock();
						if($stock > 0){
							$json_list[] = $itemdetail->toArray ();
						}
					}
			/*	}

			 } */
				
			$arr ['status'] = 'OK';
				
			$arr ['item'] = $json_list;
		}

		

		$this->sendJSONResponse ( $arr );
	}
	/*public function actionSearch($name = null,$rate=null,$title=null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);

		$criteria = new CDbCriteria ();
		$criteria->limit = '50';
		if($name != null && $title != null){
			$criteria->condition = "title LIKE :title AND title LIKE :title1 ";
			$criteria->params = array(':title' => trim($name) . '%',':title1' => '%'.trim($title) . '%');
				
		}else if($title != null){
			$criteria->condition = "title LIKE :title ";
				
			$criteria->params = array(':title' => '%'.trim($title) . '%');
		}
			
		else if($name != null){
			$criteria->condition = "title LIKE :title ";
				
			$criteria->params = array(':title' => trim($name) . '%');
		}
			
		if($rate != null){
			$criteria->compare ( "sale_price ", $rate,true );
		}
		$criteria->addCondition('status ='.Item::STATUS_ACTIVE);
		$items = Item::model ()->findAll( $criteria );
		Yii::log ( CVarDumper::dumpAsString ( $criteria ), CLogger::LEVEL_WARNING, '$criteria' );
		if ($items) {
				
			$json_list = array ();
			foreach($items as $item){
				$criteria = new CDbCriteria ();
				$criteria->limit = '50';
				$criteria->compare ( "item_id ", $item->id );
				$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
				$itemdetails = ItemDetail::model ()->findAll( $criteria );
				if($itemdetails){
					foreach($itemdetails as $itemdetail){
						$stock = $itemdetail->checkStock();
						if($stock > 0){
							$json_list[] = $itemdetail->toArray ();
						}
					}
				}

			}
				
			$arr ['status'] = 'OK';
				
			$arr ['item'] = $json_list;
		}


		$this->sendJSONResponse ( $arr );
	}*/
	/*
	 * public function actionOrder() {
	 * $arr = array (
	 * 'controller' => $this->id,
	 * 'action' => $this->action->id,
	 * 'status' => 'NOK'
	 * );
	 *
	 * $model = new Customer ();
	 *
	 *
	 * /* $_POST ['name'] = "sanjeev1";
	 * $_POST ['email'] = "sanjeev1";
	 * $_POST ['address'] = "sanjeev1@gmail.com";
	 * $_POST ['city_id'] = "1";
	 * $_POST ['state_id'] = "1";
	 * $_POST ['country_id'] = "1";
	 * $_POST ['zip_code'] = "160059";
	 * $_POST ['contact_no'] = "9997737333";
	 * $_POST ['item_id'] = "8";
	 * $_POST ['bill_no'] = "1";
	 * $_POST ['bill_date'] = "2017-11-28";
	 * $_POST ['mode_of_delivery'] ="1";
	 * $_POST ['mode_of_payment'] = "1";
	 * $_POST ['total_amt'] = "100";
	 * $_POST ['discount_amt'] = "100";
	 */

	/*
	 * if (isset ( $_POST ['item_details'] ) && isset ( $_POST ['bill_no'] )&& isset ( $_POST ['bill_date'] )
	 * && isset ( $_POST ['mode_of_payment'] )&& isset ( $_POST ['mode_of_delivery'] )
	 * && isset ( $_POST ['name'] ) && isset ( $_POST ['email'] ) && isset ( $_POST ['address'] )
	 * && isset ( $_POST ['city_id'] ) && isset ( $_POST ['state_id'] ) && isset ( $_POST ['country_id'] )
	 * && isset ( $_POST ['zip_code'] ) && isset ( $_POST ['contact_no'] ) && isset ( $_POST ['status_id'] )) {
	 * $user_id = 0;
	 *
	 * $status = $_POST ['status_id'] ;
	 *
	 * if($status == '1')
	 	* {
	 	* $order = new Order ();
	 	* }elseif($status == '2') {
	 	* $order = new OrderHold ();
	 	* }else{
	 	* $arr ['message'] = 'Order status wrong';
	 	* $this->sendJSONResponse ( $arr );
	 	* }
	 	*
	 	*
	 	* $set = true;
	 	* $transaction = Yii::app()->db->beginTransaction();
	 	* try {
	 	*
	 	* $model->name = $_POST ['name'];
	 	* $model->email = $_POST ['email'];
	 	* $model->address = $_POST ['address'];
	 	* $model->city_id = $_POST ['city_id'];
	 	* $model->state_id = $_POST ['state_id'];
	 	* $model->country_id = $_POST ['country_id'];
	 	* $model->zip_code = $_POST ['zip_code'];
	 	* $model->contact_no = $_POST ['contact_no'];
	 	* if (isset ( $_POST ['opening_balance'] ))
	 		* $model->opening_balance = $_POST ['opening_balance'];
	 		* if (isset ( $_POST ['credit_limit'] ))
	 			* $model->credit_limit = $_POST ['credit_limit'];
	 			* if (isset ( $_POST ['payment_days'] ))
	 				* $model->payment_days = $_POST ['payment_days'];
	 				* $user = Customer::getUserByEmail ( $model->email );
	 				* if (! $user) {
	 				*
	 				* $model->state_id = 1; // activates account set 1
	 				* if ($model->save ()) {
	 				*
	 				* $user_id = $model->id;
	 				* } else {
	 				* $err = '';
	 				* foreach ( $model->getErrors () as $error )
	 					* $err .= implode ( ".", $error );
	 					* $arr ['message'] = $err;
	 					* }
	 					* } else {
	 					* $user_id = $user->id;
	 					* }
	 					* if($user_id != 0){
	 					* $order->bill_no = $_POST ['bill_no'];
	 					* $order->bill_date = $_POST ['bill_date'];
	 					* $order->mode_of_payment = $_POST ['mode_of_payment'];
	 					* $order->mode_of_delivery = $_POST ['mode_of_delivery'];
	 					* $order->total_amt = $_POST ['total_amt'];
	 					* $order->discount_amt = $_POST ['discount_amt'];
	 					* $order->city_id = $_POST ['city_id'];
	 					* $order->state_id = $_POST ['state_id'];
	 					* $order->country_id = $_POST ['country_id'];
	 					* $order->customer_id = $user_id;
	 					* if($order->save())
	 						* {
	 						*
	 						*
	 						* $item_arrays = json_decode($_POST ['item_details']);
	 						*
	 						* if($item_arrays){
	 						* foreach($item_arrays as $item_array){
	 						*
	 						*
	 						* if($status == '1')
	 							* {
	 							* $orderItem = new OrderItem();
	 							* }else{
	 							* $orderItem = new OrderHoldItem();
	 							* }
	 							*
	 							* $orderItem->item_detail_id = $item_array['item_id'];
	 							* $orderItem->discount_id = $item_array['discount_id'];
	 							* $orderItem->discount_amt = $item_array['discount_amt'];
	 							* $orderItem->tax_id = $item_array['tax_id'];
	 							* $orderItem->tax_amount = $item_array['tax_amount'];
	 							* $orderItem->order_id = $order->id;
	 							* if($orderItem->save())
	 								* {
	 								*
	 								* }else{
	 								* $set = false;
	 								* }
	 								* }
	 								* }
	 								*
	 								* if ($set == true) {
	 								* $transaction->commit();
	 								* $arr ['message'] = 'Order is saved Successfully';
	 								* }else{
	 								* $transaction->rollback();
	 								* $arr ['message'] = 'Try again';
	 								* }
	 								*
	 								* }
	 					*
	 					* else {
	 					* $set = false;
	 					* }
	 					*
	 					* }
	 					*
	 					* } catch (Exception $e) {
	 					* $transaction->rollback();
	 					* }
	 					*
	 					*
	 					*
	 					* }
	 					* $this->sendJSONResponse ( $arr );
	 					* }
	 					*/
	 					public function actionOrder() {
	 						$arr = array (
	 								'controller' => $this->id,
	 								'action' => $this->action->id,
	 								'status' => 'NOK'
	 						);

	 						$model = new Customer ();

	 						// $_POST ['item_details'] = '[{"BARCODE":"7052418012699","ITEMDESC":"PIC","UNIT":"No","BOX":"0","QTY":1.0,"SALERATE":"22.00","MRP":"22.00","DISCOUNT":"1","DISCAMOUNT":0.0,"AMOUNT":0.0,"TAX":"5","TAXAmount":0.0}]';
	 						/*  $_POST ['item_details'] = '[{"bar_code":"8904141517576","base_price":"37.74","batch_numbers":"01249","box":0,"cess_amount":373.63,"cess_per":"2.00","cgst_amt":373.63,"cgst_per":"2.00","discount_amt":0,"discount_id":0.0,"discount_type":"1","discount_val":"0","igst_amount":0.0,"igst_per":0.0,"is_coupon":"0","item_desc":"Evergreen Copy new","item_id":"40603","item_name":"Evergreen Copy new","mrp":"40.00","qty":495,"sale_rate":"40.00","sgst_amt":373.63,"sgst_per":"2.00","stock_qty":"497.000","tax_amt":2.2644,"tax_id":"5","tax_percent":6.0,"total_amount":"19800.0","unit_name":["Unit","Box","Case","Keni","ML","NOS","PCS","PETI","TIN"]}]';
	 						$_POST ['customer_id'] = "51";
	 						 $_POST ['mode_of_delivery'] = "7";
	 						 $_POST ['mode_of_payment'] = "6";
	 						 $_POST ['total_amt'] = "19800";
	 						 $_POST ['discount_amt'] = "0.0";
	 						 $_POST ['discount_id'] = "0";
	 						 $_POST ['outlet_id'] = "5";
	 						 $_POST ['status_id'] = "1";  */
	 						$headers = getallheaders ();
	 					
	 						$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
	 						//$loginid = 1;
	 						Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, '$_POST' );
	 						if ($loginid) {
	 							if (isset ( $_POST ['item_details'] ) && isset ( $_POST ['mode_of_payment'] ) && isset ( $_POST ['mode_of_delivery'] ) && isset ( $_POST ['status_id'] )) {
	 									
	 									
	 								$status = $_POST ['status_id'];
	 									
	 								if ($status == '1') {
	 									$order = new Order ();
										$order->gross_total_amt = isset($_POST ['gross_total_amt']) ? $_POST ['gross_total_amt'] : 0;
	 								} elseif ($status == '2') {
	 									$order = new OrderHold ();
	 								} else {
	 									$arr ['message'] = 'Order status wrong';
	 									$this->sendJSONResponse ( $arr );
	 								}
	 									
	 								$set = true;
	 								
	 								 $transaction = Yii::app ()->db->beginTransaction ();
	 								try { 
	 									if(isset($_POST ['customer_id'])){
	 										$customer = Customer::model()->findByPk($_POST ['customer_id'] );
	 									}
	 									$month = date('m');
	 									if($month > 3){
	 										$year = date('Y');
	 										$yearlast = $year + 1;
	 										$start_date = $year.'-04-01';
	 										$end_date = $yearlast.'-03-31';
	 									}else{
	 										$year = date('Y');
	 										$yearlast = $year - 1;
	 										$start_date = $yearlast.'-04-01';
	 										$end_date = $year.'-03-31';
	 									}
	 									/* $criteria = new CDbCriteria();
	 									$criteria->order = 'bill_no desc';
	 									if($start_date != '' && $end_date != ''){
	 										$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
	 									}
	 									$latestorder = Order::model()->find($criteria);
	 									if($latestorder){
	 										$bill_no = $latestorder->bill_no + 1;
	 									}else{
	 										$bill_no = 1;
	 									}

	 									$order->bill_no = $bill_no; */
	 									$order->bill_date = date('Y-m-d');
	 									$order->mode_of_payment = $_POST ['mode_of_payment'];
	 									$order->mode_of_delivery = $_POST ['mode_of_delivery'];
	 									$order->total_amt = $_POST ['total_amt'];
	 									$order->discount_amt = $_POST ['discount_amt'];
	 									$order->outlet_id = $_POST ['outlet_id'];
	 									if(isset($_POST ['customer_id'])){
	 										$order->city_id = $customer->city_id;
	 										$order->state_id = $customer->state_id;
	 										$order->country_id = $customer->country_id;
	 										$order->customer_id = $customer->id;
	 									}
	 									if(isset($_POST ['online_order_id'])){
	 										$order->online_order_id = $_POST ['online_order_id'];
	 									}
	 									if(isset($_POST ['is_mobile'])){
	 										$order->is_mobile = $_POST ['is_mobile'];
	 									}
	 									
	 									$order->create_user_id = $loginid;
	 									if ($order->save ()) {
	 										if ($status == '1') {
	 											if(isset($_POST['credit_note_id'])){

	 												$creditnot = CreditNote::model()->findByAttributes(array('credit_number'=>$_POST['credit_note_id']));
	 												if($creditnot){
	 													$remain_amt = $creditnot->amt - $creditnot->amt_used;
	 													if(($remain_amt) >= ($order->total_amt)){
	 														$creditnot->amt_used = ($creditnot->amt_used) + $order->total_amt;
	 														$creditnot->save();
	 													}else{
	 														$set = false;
	 														$arr ['message'] = 'Credit note amount is less than total amount';
	 													}
	 												}else{
	 													$set = false;
	 													$arr ['message'] = 'Credit note is not found';
	 												}
	 											}
	 										}
	 										$item_arrays = json_decode ( $_POST ['item_details'] );
	 											
	 										if ($item_arrays) {
	 											foreach ( $item_arrays as $item_array ) {
	 													
	 												if ($status == '1') {
	 													$orderItem = new OrderItem ();
	 												} else {
	 													$orderItem = new OrderHoldItem ();
	 												}
	 												$criteria1 = new CDbCriteria ();
	 												$criteria1->compare ( "bar_code ", $item_array->bar_code);
	 												$itemdetail = ItemDetail::model ()->find ( $criteria1 );
	 													
	 												if($itemdetail){
	 														
	 													$orderItem->item_detail_id = $itemdetail->id;
	 													$orderItem->item_id = $itemdetail->item_id;
	 													$orderItem->qty = $item_array->qty;
	 													$orderItem->price = $orderItem->remove_format($item_array->base_price);
	 													if($item_array->discount_id != 0){
	 														$orderItem->discount_id = $item_array->discount_id;
	 															
	 														$orderItem->discount_amt = $item_array->discount_amt;
	 													}
	 													if($item_array->tax_id != 0){
	 														$orderItem->tax_id = $orderItem->getTaxValueID($item_array->tax_id);
	 														if ($status == '1') {
	 															$orderItem->original_tax = $item_array->tax_id;
	 														}
	 														$orderItem->tax_amount = $item_array->tax_amt;
	 													}
	 													$orderItem->sale_rate = $item_array->sale_rate;
	 													$orderItem->mrp = $item_array->mrp;
	 													$orderItem->total_amt = $item_array->total_amount;
	 														
	 													$orderItem->cgst_per = $item_array->cgst_per;
	 													$orderItem->sgst_per = $item_array->sgst_per;
	 													$orderItem->cess_per = $item_array->cess_per;
	 													$orderItem->igst_per = $item_array->igst_per;
	 													$orderItem->cgst_amt = $item_array->cgst_amt;
	 													$orderItem->sgst_amt = $item_array->sgst_amt;
	 													$orderItem->cess_amt = $item_array->cess_amount;
	 													$orderItem->igst_amt = $item_array->igst_amount;
	 													$orderItem->create_user_id = $loginid;
	 													if ($status == '1') {
	 														$orderItem->order_id = $order->id;
														$orderItem->status = '1';	
	 													}else{
	 														$orderItem->order_hold_id = $order->id;
	 													}
	 													Yii::log ( CVarDumper::dumpAsString ( $orderItem ), CLogger::LEVEL_WARNING, '$orderItem' );
	 														
	 													if ($orderItem->save ()) {

	 														if ($status == '1') {
	 															/*if(isset($_POST ['online_order_id'])){
	 															$online_order = OnlineOrder::model()->findByAttributes(array('id'=>$_POST['online_order_id']));
	 															if($online_order){
	 																$online_order->order_status = OnlineOrder::ORDERSTATUS_PACKED;
	 																$online_order->saveAttributes(array('order_status'));
	 																
	 																$order_id = $online_order->order_id;
	 																//$order_id = 100007417;
	 																$ch = curl_init ();
	 																
	 																curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api" );
	 																curl_setopt ( $ch, CURLOPT_POST, 1 );
	 																
	 																curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
	 																		'sKeY' => getenv('POS_SOULBOWL_KEY'),'order_id'=>$online_order->order_id,'action'=>'update_dispatch_status','status'=>'1'
	 																) ) );
	 																
	 																curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	 																
	 																$server_output = curl_exec ( $ch );
	 																
	 																curl_close ( $ch );
	 																$response = json_decode ( $server_output, true );
	 															}
	 															}*/
																// echo "reached here";
	 															$order->UpdateStock($item_array->qty,$itemdetail->id);
	 														}

	 													} else {
	 															
	 														//print_r($orderItem->getErrors());exit;
	 														$set = false;
	 													}
	 												}else{
	 													$set = false;
	 												}
	 													
	 													
	 											}
	 										}
	 											
	 										if ($set == true) {
	 											$transaction->commit ();
	 											$criteria = new CDbCriteria();
	 											$criteria->order = 'bill_no desc';
	 											if($start_date != '' && $end_date != ''){
	 												$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
	 											}
	 											$latestorder = Order::model()->find($criteria);
	 											if($latestorder){
	 												$bill_no = $latestorder->bill_no + 1;
	 											}else{
	 												$bill_no = 1;
	 											}
	 												
	 											$order->bill_no = $bill_no;
	 											$order->saveAttributes(array('bill_no'));
	 											if ($status == '1') {
												$data = array();
												
												$item_list = array();
												$itemorderitems = OrderItem::model()->findAllByAttributes(array('order_id'=>$order->id));
												if($itemorderitems){
												foreach($itemorderitems as $orderitem){
												$get_item = Item::model()->findByPk($orderitem->item_id);
													if($get_item){
													    $item_list[$get_item->item_code] = array('name'=>$get_item->title,'qty'=>$orderitem->qty,'price'=>($orderitem->qty *$orderitem->sale_rate));
													}
												}
												}
												$grand = $order->total_amt;
												$shipping = '0.00';
												/*if($order->total_amt >= 2000){
													$shipping = '0.00';
												}else{
													$shipping = '50.00';
													$grand = $order->total_amt + $shipping;
												}*/

												$data['items']= $item_list;
												$data['sub_total']= $order->total_amt;
												$data['grand_total']= $grand;
												$data['shipping']= $shipping;

												$item_str = '';
												$item_str = json_encode($data);
													Yii::log ( CVarDumper::dumpAsString ( $item_str ), CLogger::LEVEL_WARNING, '$item_str' );
						
												if(isset($_POST ['online_order_id'])){
													$date = date('Y-m-d H:i:s');
	 															$online_order = OnlineOrder::model()->findByAttributes(array('id'=>$_POST['online_order_id']));
	 															if($online_order){
	 																$online_order->order_status = OnlineOrder::ORDERSTATUS_PACKED;
	 																$online_order->saveAttributes(array('order_status'));
	 																
	 																$order_id = $online_order->order_id;
	 															//	$order_id = 100007417;
	 																$ch = curl_init ();
	 																
	 																//curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api" );
																	curl_setopt ( $ch, CURLOPT_URL, "http://sect4.soulbowl.in/deliveryoption/index/sendemailnotification" );
	 																curl_setopt ( $ch, CURLOPT_POST, 1 );
	 																
	 																curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
	 																		'sKeY' => getenv('POS_SOULBOWL_KEY'),'order_id'=>$online_order->order_id,'action'=>'update_dispatch_status2','status'=>'1','data'=>$item_str,'date'=>$date
	 																) ) );
	 																
	 																curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	 																
	 																$server_output = curl_exec ( $ch );
	 																
	 																curl_close ( $ch );
	 																$response = json_decode ( $server_output, true );
	 															}
	 															}
													}
												
	 											$taxarr = array();
												if ($status == '1') {
												$order->SendSms();
												}
	 											$arr['status'] = 'OK';
												$arr['order_id'] = $order->id;
												
	 											if ($status == '1') {
	 												$bill_prefix = 'B';
	 												$outlet = Outlet::model()->findByPk($order->outlet_id);
	 												if($outlet){
	 													if($outlet->bill_prefix != ''){
	 														$bill_prefix = $outlet->bill_prefix;
	 													}else{
	 														$bill_prefix = 'B';
	 													}
	 														
	 												}
	 												$arr['bill_no'] = $order->getOrderBillNo();
	 												$criteria = new CDbCriteria();
	 												//$criteria->group = 'tax_id';
													$criteria->select ='SUM(qty) AS qty,SUM(tax_amount) AS tax_amount, SUM(cgst_amt) AS cgst_amt, SUM(sgst_amt) AS sgst_amt, SUM(cess_amt) AS cess_amt, SUM(igst_amt) AS igst_amt,t.*';
													$criteria->with = 'item';
													$criteria->group = 'tax_id,item_id,item.hsn_code';
													// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
													// explicitly by the grouped columns to preserve the previous output order.
													$criteria->order = 'tax_id,item_id,item.hsn_code';
	 												$criteria->compare('order_id',$order->id);
	 												$itemms = OrderItem::model()->findAll($criteria);

	 												if($itemms){
	 													foreach($itemms as $itemmtax){
	 														$taxarr[] = $itemmtax->getTaxArray();
	 													}
	 												}
	 												$arr['taxes'] =$taxarr;
													
	 											}else{
	 												$arr['bill_no'] = '0';
	 											}
												
	 											$arr ['message'] = 'Order is saved Successfully';
	 										} else {
	 											$transaction->rollback ();
	 											$arr ['message'] = 'Try again';
	 										}
	 									
	 									}

	 									else {
	 											//print_r($order->getErrors());exit;
	 										$set = false;
	 									}
	 								 } catch ( Exception $e ) {
	 									$transaction->rollback ();
	 								} 
	 							}
	 						}
	 						$this->sendJSONResponse ( $arr );
	 						Yii::log ( CVarDumper::dumpAsString ( $this->sendJSONResponse ( $arr ) ), CLogger::LEVEL_WARNING, 'order_response' );
	 					}


	public function actionAdjust() {

		$arr = array (
			'controller' => $this->id,
			'action' => $this->action->id,
			'status' => 'NOK'
		);

		// echo "<pre>"; print_r($_POST); die;

		// $_POST ['formData'] = '';
		// $_POST ['qtyData'] = "";
		// $_POST ['remainData'] = "";
		// $_POST ['remarksData'] = "";
		if (isset ( $_POST ['itemdetail_id'] ) && isset ( $_POST ['qty'] ) && isset ( $_POST ['remain_qty'] ) && isset ( $_POST ['remark'] ) && isset ( $_POST ['original_item_id'] ) && isset ( $_POST ['user_id'] )) {

			$itemId = $_POST['original_item_id'];
			$userId = $_POST['user_id'];
			$posted ['formData'][$itemId] = $_POST['itemdetail_id'];
			$posted ['qtyData'][$itemId] = $_POST['qty'];//$_POST['qty'];
			$posted ['remainData'][$itemId] = $_POST['remain_qty'];//$_POST['remain_qty'];
			$posted ['remarksData'][$itemId] = !empty($_POST['remark']) ? $_POST['remark'] : " " ;

			
			// $posted = $_POST;
			
			if ($posted) {
				
				$formdata = $posted['formData'];
				$saleStatus = Yii::app()->params['saleStatus'];
				foreach ( $formdata as $key => $itemDetail ) {
					
					if (($posted ['formData'] [$key] != '') && ($posted ['qtyData'] [$key] != '')) {
						
						$criteria = new CDbCriteria ();
						$criteria->order = 'id asc';
						$criteria->limit = 1;
						$outlet_model = Outlet::model ()->find ( $criteria );
						if ($outlet_model) {
							$outlet = $outlet_model->id;
						}
						$itemDetail = ItemDetail::model ()->findByPk ( $posted ['formData'] [$key] );
						$current = '0.000';
						$adjusted = '0.000';
						$actual = '0.000';
						if ($itemDetail) {
							$itemStock = ItemStock::model ()->findByAttributes ( array (
									'item_detail_id' => $itemDetail->id,
									'outlet_id' => $outlet 
							) );
							
							/*for item vendor*/
								$ItemVendor = ItemVendor::model ()->findByAttributes ( array (
									'item_detail_id' => $itemDetail->item_id
								
							) );
							$itemStock->vendor_id=$ItemVendor->vendor_id;
							/*end item vendor*/
							$item = Item::model ()->findByPk ( $itemDetail->item_id );
							if ($saleStatus) { // sale on
								if($posted ['remainData'] [$key] > $posted ['qtyData'] [$key]){
									$posted ['type'] [$key] = ItemStock::TYPE_SUBSTRACT;
									$posted ['qtyData'] [$key] = $posted ['remainData'] [$key] - $posted ['qtyData'] [$key];
								}else{
										$posted ['type'] [$key] = ItemStock::TYPE_ADDED;
										$posted ['qtyData'] [$key] = $posted ['qtyData'] [$key] - $posted ['remainData'] [$key];
								}
							} else { // sale off
								$posted ['type'] [$key] = ItemStock::TYPE_ADDED;
							}
								
							if ($itemStock == null) {
								
								$itemStock = new ItemStock ();
								if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
									$itemStock->purchase_qty = $itemStock->purchase_qty + $posted ['qtyData'] [$key];
									$itemStock->balance_qty = $itemStock->balance_qty + $posted ['qtyData'] [$key];
								} else {
									// if($itemStock->balance_qty >= $posted['qtyData'][$key] ){
									$itemStock->purchase_qty = $posted ['qtyData'] [$key];
									$itemStock->balance_qty = $posted ['qtyData'] [$key];
									// }
								}
								$itemStock->batch_number = User::randomBarcode ( '5' );
								$itemStock->item_detail_id = $itemDetail->id;
								
								if ($item) {
									
									$itemStock->item_id = $item->id;
									$itemStock->mrp = $itemDetail->getItemDetailMrp ();
									$itemStock->base_price = $item->purchase_price;
									$itemStock->outlet_id = $outlet;
								}
							} else {
								if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
									$itemStock->purchase_qty = $itemStock->purchase_qty + $posted ['qtyData'] [$key];
									$itemStock->balance_qty = $itemStock->balance_qty + $posted ['qtyData'] [$key];
								} else {
									
									$itemStock->balance_qty = $itemStock->balance_qty - $posted ['qtyData'] [$key];
								}
							}
							$current = $item->getOutletTotalRemainingQuantity ( $itemDetail->id, $outlet );
							if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
								$actual = bcadd ( $current, $posted ['qtyData'] [$key], 3 );
							} else {
								if ($current == 0) {
									$actual = bcsub ( $current, $posted ['qtyData'] [$key], 3 );
								}
								
								if ($current < 0) {
									$bquantity = abs ( $current );
									$remain = bcadd ( $current, $posted ['qtyData'] [$key], 3 );
									$actual = '-' . $remain;
								}
								if ($current > 0) {
									if ($current > $posted ['qtyData'] [$key]) {
										$actual = bcsub ( $current, $posted ['qtyData'] [$key], 3 );
									} else {
										$actual = bcsub ( $posted ['qtyData'] [$key], $current, 3 );
									}
								}
								// $actual = $current - $posted ['qtyData'] [$key];
							}
							if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
								$adjusted = $posted ['qtyData'] [$key];
							} else {
								$adjusted = '-' . $posted ['qtyData'] [$key];
							}
						
							if ($itemStock->save ()) {
								$criteria = new CDbCriteria();
								$criteria->compare('status',MrsAdjust::STATUS_PENDING);
								$criteria->compare('item_id',$itemStock->item_id);
								$criteria->order = 'id desc';
								$mrsadjust = MrsAdjust::model()->find($criteria);
								if($mrsadjust){
									$mrsadjust->status = MrsAdjust::STATUS_DONE;
									$mrsadjust->saveAttributes(array('status'));
								}
								$itemDetail->update_time = date ( 'Y-m-d H:i:s' );
								$itemDetail->saveAttributes ( array (
										'update_time' 
								) );
								$item->update_time = date ( 'Y-m-d H:i:s' );
								$item->saveAttributes ( array (
										'update_time' 
								) );
								$log = new StockAdjustLog ();
								$log->date = date ( 'Y-m-d' );
								$log->item_detail_id = $itemDetail->id;
								$log->item_id = $item->id;
								$log->mrp = $itemDetail->getItemDetailMrp ();
								$log->outlet_id = $outlet;
								$log->current_stock = $current;
								$log->actual_stock = $actual;
								$log->adjusted = $adjusted;
								$log->create_user_id = $userId;
								$log->remarks = $posted ['remarksData'] [$key];
								if ($log->save ()) {
									$stocklog = new StockLog ();
									$stocklog->item_detail_id = $itemDetail->id;
									$stocklog->item_id = $item->id;
									$stocklog->batch_no = $itemStock->batch_number;
									if ($itemDetail) {
										$stocklog->current_qty = $itemDetail->getStockQty ();
										if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
											$stocklog->previous_qty = bcsub ( $itemDetail->getStockQty (), $posted ['qtyData'] [$key], 3 );
										} else {
											$stocklog->previous_qty = bcadd ( $itemDetail->getStockQty (), $posted ['qtyData'] [$key], 3 );
										}
										// $stocklog->previous_qty = $itemDetail->getStockQty();
									}
									$stocklog->Qty = $adjusted;
									$stocklog->outlet_id = $outlet;
									$stocklog->vendor_id = $itemStock->vendor_id;
									$stocklog->type_id = StockLog::TYPE_ADJUSTED;
									if ($stocklog->save ()) {
										
										$arr["message"] = "save successfully";		
										$arr['status']	= "OK";	

										$criteriaItemStock = new CDbCriteria();
										$criteriaItemStock->compare('item_id',$itemDetail->item_id);
										$criteriaItemStock->select ='SUM(balance_qty) AS balance_qty';
										$criteriaItemStock->group = 'item_id';
										$mrsItemStock = ItemStock::model()->find($criteriaItemStock);	
										
										
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
								}else{
									
										/*Create MRS section*/
										$criteriaMrs = new CDbCriteria();
										$criteriaMrs->order = 'id desc';
										$criteriaMrs->limit = '1';
										$criteriaMrs->addCondition('vendor_id ='.$itemStock->vendor_id);
										$vendorMRS = Mrs::model()->find($criteriaMrs);
								
								// echo"<pre>"; print_r($vendorMRS); die;
									if($vendorMRS->id){
									$item = Item::model()->findByPk($item->id);
									$criteriaMrsD = new CDbCriteria();
									$criteriaMrsD->order = 'id desc';
									$criteriaMrsD->limit = '1';
									$criteriaMrsD->addCondition('mrs_id ='.$vendorMRS->id);
									$criteriaMrsD->addCondition('item_id ='.$item->id);
									$vendorMRSD = MrsDetail::model()->find($criteriaMrsD);
									// echo"<pre>"; print_r($vendorMRSD); die;
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
										Yii::log ( CVarDumper::dumpAsString ( $itemStock->vendor_id ), CLogger::LEVEL_WARNING, '$mrs_vendor_id' );
										if($itemStock->vendor_id != null){
										$mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$itemStock->vendor_id,
										'outlet_id'=>$outlet
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
										$mrs->outlet_id = $outlet;
										$mrs->vendor_id = $itemStock->vendor_id;
									
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
										$mrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$itemStock->item_detail_id,
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
										$mrsdetail->item_id =$itemDetail->item_id;
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
											$arr["message"] = "save successfully";		
											$arr['status']	= "OK";	
										}else{
											$arr["message"] = "save time error";
										} 
										
											
										}
										
										/*End Create MRS*/
												
									}
									
								}
							}
							
							}	
									
								}
										
										
									
										
									
										
									} else {
										$arr["message"] = "second last else";
										//print_r ( $stocklog->getErrors () );
										//exit ();
									}
								} else {

									$arr["message"] = "last else";
									//print_r ( $log->getErrors () );
									//exit ();
								}
							}
						}
					}
				}
			}
		}

		$this->sendJSONResponse ( $arr );
	}

	public function actionBarcode(){
		$arr = array (
			'controller' => $this->id,
			'action' => $this->action->id,
			'status' => 'NOK'
		);

		
		//var_dump($_POST);die;
		//var_dump($barcode);
		if(!empty($_POST['barcode']) ){

			$criteria = new CDbCriteria ();
			$criteria->order = 'id asc';
			$criteria->limit = 1;
			$criteria->compare ( 'bar_code', $_POST['barcode'] );
			//print_r($criteria);
			//$itemDetail = ItemDetail::model ()->find ( $criteria );
			//var_dump($itemDetail);die;
			
			// ordered: the criteria built just above, with an order and a limit,
			// is discarded and this call has none - so the row returned was
			// whichever MySQL happened to give back for that bar code.
			$itemDetail = ItemDetail::model ()->findByAttributes ( array (
				'bar_code' => $_POST['barcode'],
			), array ( 'order' => 'id asc' ) );

			// echo "<pre>"; print_r($itemDetail->item->itemVendors[0]->vendor); die;

			if($itemDetail){
				$arr ['status'] = 'OK';
				$arr ['barcode_item'] = $itemDetail->toArray();
				$arr['barcode_item']['last_adjust_time'] = '';
				$arr['barcode_item']['last_adjust_by_username'] = '';
				if (count($itemDetail->stockAdjustLogs) > 0) {
					$arr['barcode_item']['last_adjust_time'] = $itemDetail->stockAdjustLogs[0]->create_time;
					if ($itemDetail->stockAdjustLogs[0]->createUser && $itemDetail->stockAdjustLogs[0]->createUser->username) {
						$arr['barcode_item']['last_adjust_by_username'] = $itemDetail->stockAdjustLogs[0]->createUser->username;
					}
				}

				$arr['barcode_item']['vendor_id'] = '';
				$arr['barcode_item']['vendor_name'] = '';
				if ($itemDetail->item && count($itemDetail->item->itemVendors) > 0 && $itemDetail->item->itemVendors[0]->vendor) {
					$arr['barcode_item']['vendor_id'] = $itemDetail->item->itemVendors[0]->vendor->id;
					$arr['barcode_item']['vendor_name'] = $itemDetail->item->itemVendors[0]->vendor->name;
				}

			}else{
				$arr ['message'] = 'item not found';
			}

			//$itemDetail = ItemDetail::model ()->findByBarCode ( $_POST['barcode'] );
			//print_r($itemDetail);die;
			
			//$arr ['barcode'] = $barcode;
		}else{
			
			$arr ['message'] = 'Please add barcode to url';
		}

		
		$this->sendJSONResponse ( $arr );
	}

	public function actionAdjustitemtozero(){
		$arr = array (
			'controller' => $this->id,
			'action' => $this->action->id,
			'status' => 'NOK'
		);

		if (isset ( $_POST ['itemdetail_id'] ) && isset ( $_POST ['user_id'] )) {
			$itemDetailId = $_POST ['itemdetail_id'];
			$userId = $_POST['user_id'];
			$itemDetail = ItemDetail::model ()->findByPk ( $itemDetailId );

			if ($itemDetail) {
					$criteria = new CDbCriteria ();
					$criteria->order = 'id asc';
					$criteria->limit = 1;
					$outlet_model = Outlet::model ()->find ( $criteria );
					if ($outlet_model) {
						$outlet = $outlet_model->id;
					}
					$item = Item::model ()->findByPk ( $itemDetail->item_id );
					$log = new StockAdjustLog ();
					$log->date = date ( 'Y-m-d' );
					$log->item_detail_id = $itemDetail->id;
					$log->item_id = $item->id;
					$log->mrp = $itemDetail->getItemDetailMrp ();
					$log->outlet_id = $outlet;
					$log->current_stock = 0;
					$log->actual_stock = 0;
					$log->adjusted = 0;
					$log->create_user_id = $userId;
					$log->remarks = 'Reset';
					if ($log->save ()) {
						$arr ['status'] = 'OK';
						$arr ['message'] = 'Stock adjusted to zero.';
					}
			} else {
				$arr ['message'] = 'Item not found with the provided ID.';
			}

		}else{
			$arr ['message'] = 'Please pass required parameters';
		}

		
		$this->sendJSONResponse ( $arr );
	}

	public function actionScannedItem() {
		$arr = array (
			'controller' => $this->id,
			'action' => $this->action->id,
			'status' => 'NOK'
		);

		if(!empty($_POST['items']) && count(json_decode($_POST['items'], true)) > 0 && $_POST['computer_name'] && $_POST['user_id'] ){

			$scannedItems = json_decode($_POST['items'], true);
			// echo "<pre>"; print_r($scannedItems); 
			// die;
			foreach ($scannedItems as $key => $item) {
				$model = new ScannedItems();
				$model->user_id = $_POST['user_id'];
				$model->computer_name = $_POST['computer_name'];
				$model->user_email = $_POST['user_email'];
				$model->item_id = $item['item_id'];
				$model->bar_code = $item['bar_code'];
				$model->is_coupon = $item['is_coupon'];
				$model->qty = $item['qty'];
				$model->sale_rate = $item['sale_rate'];
				$model->base_price = $item['base_price'];
				$model->mrp = $item['mrp'];
				$model->item_detail = json_encode($item);
				$model->created_at = date('Y-m-d H:i:s', strtotime($_POST['created_at']));
				$model->save();
			}

			$arr ['status'] = 'OK';

		} else {
			$arr ['message'] = 'No item provided or some details are missing';
		}



		$this->sendJSONResponse ( $arr );
	}

	public function actionPunchorder()
	{
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		
		try {

			$headers = getallheaders ();
	 					
			$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
			//$loginid = 1;
			Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, '$_POST' );
			if ($loginid) {

				if (isset ( $_POST ['item_details'] )) {
					$item_arrays = json_decode ( $_POST ['item_details'] );
					if (is_array ( $item_arrays ) && count ( $item_arrays ) > 0) {
						$drt = [];
						$totalSaleValue = 0;
						$netAmount = 0;
						foreach ( $item_arrays as $item ) {
							$itemDetail = ItemDetail::model ()->findByAttributes ( array (
								'bar_code' => $item->bar_code	,
							) );
							if ($itemDetail) {
								$itemDetail = $itemDetail->toArray();
								$newItem = [];
								$newItem['item_id'] = $itemDetail['item_id'];
								$newItem['bar_code'] = $itemDetail['bar_code'];
								$newItem['qty'] = $item->qty;
								$newItem['sale_rate'] = $itemDetail['sale_rate'];
								$newItem['base_price'] = $itemDetail['base_price'];
								$newItem['mrp'] = $itemDetail['mrp'];
								$newItem['tax_id'] = $itemDetail['tax_id'];
								$newItem['cgst_per'] = $itemDetail['cgst_per'];
								$newItem['sgst_per'] = $itemDetail['sgst_per'];
								$newItem['cess_per'] = $itemDetail['cess_per'];
								$newItem['igst_per'] = $itemDetail['igst_per'];
								$newItem['discount_id'] = $itemDetail['discount_id'];
								$newItem['discount_val'] = $itemDetail['discount_val'];
								$newItem['discount_amt'] = $itemDetail['discount_amt'];
								$newItem['discount_type'] = $itemDetail['discount_type'];
								$newItem['stock_qty'] = $itemDetail['stock_qty'];
								$newItem['product_name'] = $itemDetail['item_desc'];
								$newItem['hsn_code'] = $itemDetail['hsn_code'];
								$newItem['unit_name'] = $itemDetail['unit_name'];
								// $newItem['item_detail'] = json_encode($itemDetail);
								
								if (isset($_POST['apply_discount']) && $_POST['apply_discount'] && isset($_POST['discount_id']) && $_POST['discount_id'] > 0) {

									$discount = Discount::model()->findByPk($_POST['discount_id']);
									if ($discount) {
										$newItem['discount_id'] = $_POST['discount_id'];
										$newItem['discount_type'] = $discount->type_id;
										if ($discount->type_id == Discount::TYPE_PERCENTAGE) {
											$newItem['sale_rate'] = $newItem['sale_rate'] - ($newItem['sale_rate'] * floatval($discount->amount) / 100);
											$newItem['discount_amt'] = ($newItem['sale_rate'] * floatval($discount->amount) / 100);
										} else if ($discount->type_id == Discount::TYPE_AMOUNT) {
											$newItem['sale_rate'] = $newItem['sale_rate'] - floatval($discount->applicable_amt);
											$newItem['discount_amt'] = $discount->applicable_amt;
										}
									}
								} 
								$newItem['base_price'] = $this->getBasePrice($newItem['sale_rate'], $itemDetail['tax_percent']);
								$newItem['total_amount'] = round($newItem['sale_rate'] * $item->qty, 2);
								$newItem['tax_amt'] = round($this->getTaxAmount($newItem['base_price'], $itemDetail['tax_percent']) * $item->qty, 2);
								$newItem['tax_percent'] = $itemDetail['tax_percent'];
								$newItem['taxable_amount'] = round($newItem['base_price'] * $item->qty, 2);
								$newItem['sgst_amt'] = round($this->getTaxAmount($newItem['base_price'], $newItem['sgst_per']) * $item->qty, 2);
								$newItem['cgst_amt'] = round($this->getTaxAmount($newItem['base_price'], $newItem['cgst_per']) * $item->qty, 2);
								$newItem['cess_amount'] = round($this->getTaxAmount($newItem['base_price'], $newItem['cess_per']) * $item->qty, 2);
								$newItem['igst_amount'] = round($this->getTaxAmount($newItem['base_price'], $newItem['igst_per']) * $item->qty, 2);
								// $newItem['discount_amt'] = round(($newItem['sale_rate'] * $itemDetail['discount_val'] / 100) * $item->qty, 2);
								
								$totalSaleValue += round($newItem['mrp'] * $item->qty);
								$netAmount += $newItem['total_amount'];
								
								$drt[] = $newItem;
							}
						}

						$arr['item_details'] = $drt;
						$arr['totalSaleValue'] = round($totalSaleValue);
						$arr['netAmount'] = round($netAmount);
						$arr['saving'] = round($totalSaleValue - $netAmount);
					} else {
						$arr ['message'] = 'No item details provided';
						$this->sendJSONResponse ( $arr );
						return;
					}

					$billNo = $this->processOrder($loginid, $drt, $arr);

					if ($billNo) {
						$this->generateBillAndSend($arr, $billNo, $loginid);
						$arr['status'] = 'OK';
						$arr['bill_no'] = $billNo;
					}

				}
			}
			
		} catch (Exception $e) {
			$arr['message'] = 'Error processing order: ' . $e->getMessage();
			$arr['error_details'] = $e->getTraceAsString();
		}
		
		$this->sendJSONResponse($arr);
	}

	public function generateBillAndSend($billData, $billNo = null, $loginid = null) {

		$arr = [];
		$id = $_POST["customer_id"];
		$model = Customer::model ()->findByPk ( $id );
		$userDetail = User::model()->findByPk($loginid);

		if ($model && $model->contact_no) {
			// Set the upload directory path
			$uploadDir = Yii::getPathOfAlias('webroot') . '/uploadbills/'; // This points to the 'uploads' directory in the web root

			// Create the upload directory if it doesn't exist
			if (!is_dir($uploadDir)) {
					mkdir($uploadDir, 0777, true);
			}

			$fileName = $billNo.".pdf";
			$filePath = $uploadDir . $fileName;

			$mPDF1 = Yii::app()->ePdf->mpdf();
			
			# You can easily override default constructor's params
			$mPDF1 = Yii::app()->ePdf->mpdf('', 'A4');
			
			$htmlContent = $this->renderPartial('//item/_pdf', array(
							'billData' => $billData,
							'customer' => $model,
							'billNo' => $billNo,
							'username' => isset($userDetail) ? $userDetail->username : '',
					), true);
			
			$mPDF1->WriteHTML($htmlContent);
			
			$mPDF1->Output($filePath, 'F'); // 'F' means save to file
			
			Yii::app()->interaktApi->uploadFileToServer($filePath);

			$whatsapp_no = preg_replace("/[^0-9]/", "", $model->contact_no);

			$template = 'purchase_order';
			$fileNms = $fileName;
			$userId = isset($_POST['user_id']) ? $_POST['user_id'] : null;
			$computerName = isset($_POST['computer_name']) ? $_POST['computer_name'] : null;

			if (strpos(strtolower($fileNms), 'reprint') !== false) {
				$template = 'reprint_order';
			} else if (strpos(strtolower($fileNms), 'refund') !== false) {
				$template = 'refund_order';
			}
			$urlPdf =  'http://61.2.241.71/pos/whatapporder/' . $fileNms;
			//echo $urlPdf ;
			
			//echo $fileNms;
			$pdfName = str_replace('.pdf', '', $fileNms);
			$pdfName = str_replace(['_Reprint', '_Refund', '-Reprint', '-Refund'], '', $pdfName);
			$arr ['message'] = Yii::app()->interaktApi->sendApprovalOrderMessageNew($template,$whatsapp_no ,[$model->name, $pdfName], [$urlPdf], $fileNms, ['user_id' => $userId, 'computer_name' => $computerName]);
		} else {
			$arr ['message'] = "user not found";
		}

		return $arr;
	}

	public function processOrder($loginid, $itemDetails, $calData) {
		
		$bill_no = null;
		$arr = [];
		if (isset ( $_POST ['mode_of_payment'] ) && isset ( $_POST ['mode_of_delivery'] ) && isset ( $_POST ['status_id'] )) {
			
			$status = $_POST ['status_id'];
	 									
			if ($status == '1') {
				$order = new Order ();
			} elseif ($status == '2') {
				$order = new OrderHold ();
			} else {
				$arr ['message'] = 'Order status wrong';
				$this->sendJSONResponse ( $arr );
			}
				
			$set = true;
			
				$transaction = Yii::app ()->db->beginTransaction ();
			try { 
				if(isset($_POST ['customer_id'])){
					$customer = Customer::model()->findByPk($_POST ['customer_id'] );
				}
				$month = date('m');
				if($month > 3){
					$year = date('Y');
					$yearlast = $year + 1;
					$start_date = $year.'-04-01';
					$end_date = $yearlast.'-03-31';
				}else{
					$year = date('Y');
					$yearlast = $year - 1;
					$start_date = $yearlast.'-04-01';
					$end_date = $year.'-03-31';
				}
				
				$order->bill_date = date('Y-m-d');
				$order->mode_of_payment = $_POST ['mode_of_payment'];
				$order->mode_of_delivery = $_POST ['mode_of_delivery'];
				$order->total_amt = $calData['netAmount'];
				$order->discount_amt = $calData['saving'];
				$order->outlet_id = $_POST ['outlet_id'];
				if(isset($_POST ['customer_id'])){
					$order->city_id = $customer->city_id;
					$order->state_id = $customer->state_id;
					$order->country_id = $customer->country_id;
					$order->customer_id = $customer->id;
				}
				if(isset($_POST ['online_order_id'])){
					$order->online_order_id = $_POST ['online_order_id'];
				}
				// if(isset($_POST ['is_mobile'])){
				// 	$order->is_mobile = $_POST ['is_mobile'];
				// }

				$order->is_mobile = 1;
				
				$order->create_user_id = $loginid;
				if ($order->save ()) {
					if ($status == '1') {
						if(isset($_POST['credit_note_id'])){

							$creditnot = CreditNote::model()->findByAttributes(array('credit_number'=>$_POST['credit_note_id']));
							if($creditnot){
								$remain_amt = $creditnot->amt - $creditnot->amt_used;
								if(($remain_amt) >= ($order->total_amt)){
									$creditnot->amt_used = ($creditnot->amt_used) + $order->total_amt;
									$creditnot->save();
								}else {
									$set = false;
									$arr ['message'] = 'Credit note amount is less than total amount';
								}
							}else{
								$set = false;
								$arr ['message'] = 'Credit note is not found';
							}
						}
					}
					$item_arrays = $itemDetails;
						
					if ($item_arrays) {
						foreach ( $item_arrays as $item_array ) {
								
							if ($status == '1') {
								$orderItem = new OrderItem ();
							} else {
								$orderItem = new OrderHoldItem ();
							}
							$criteria1 = new CDbCriteria ();
							$criteria1->compare ( "bar_code ", $item_array['bar_code']);
							$itemdetail = ItemDetail::model ()->find ( $criteria1 );
								
							if($itemdetail){
									
								$orderItem->item_detail_id = $itemdetail->id;
								$orderItem->item_id = $itemdetail->item_id;
								$orderItem->qty = $item_array['qty'];
								$orderItem->price = $orderItem->remove_format($item_array['base_price']);
								if($item_array['discount_id'] != 0){
									$orderItem->discount_id = $item_array['discount_id'];

									$orderItem->discount_amt = $item_array['discount_amt'];
								}
								if($item_array['tax_id'] != 0){
									$orderItem->tax_id = $orderItem->getTaxValueID($item_array['tax_id']);
									if ($status == '1') {
										$orderItem->original_tax = $item_array['tax_id'];
									}
									$orderItem->tax_amount = $item_array['tax_amt'];
								}
								$orderItem->sale_rate = $item_array['mrp'];
								$orderItem->mrp = $item_array['mrp'];
								$orderItem->total_amt = $item_array['total_amount'];

								$orderItem->cgst_per = $item_array['cgst_per'];
								$orderItem->sgst_per = $item_array['sgst_per'];
								$orderItem->cess_per = $item_array['cess_per'];
								$orderItem->igst_per = $item_array['igst_per'];
								$orderItem->cgst_amt = $item_array['cgst_amt'];
								$orderItem->sgst_amt = $item_array['sgst_amt'];
								$orderItem->cess_amt = $item_array['cess_amount'];
								$orderItem->igst_amt = $item_array['igst_amount'];
								$orderItem->create_user_id = $loginid;
								if ($status == '1') {
									$orderItem->order_id = $order->id;
								$orderItem->status = '1';	
								}else{
									$orderItem->order_hold_id = $order->id;
								}
								Yii::log ( CVarDumper::dumpAsString ( $orderItem ), CLogger::LEVEL_WARNING, '$orderItem' );
									
								if ($orderItem->save ()) {

									if ($status == '1') {

										$order->UpdateStock($item_array['qty'],$itemdetail->id);
									}

								} else {
									$set = false;
								}
							}else{
								$set = false;
							}
								
								
						}
					}
						
					if ($set == true) {
						$transaction->commit ();
						$criteria = new CDbCriteria();
						$criteria->order = 'bill_no desc';
						if($start_date != '' && $end_date != ''){
							$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
						}
						$latestorder = Order::model()->find($criteria);
						if($latestorder){
							$bill_no = $latestorder->bill_no + 1;
						}else{
							$bill_no = 1;
						}
							
						$order->bill_no = $bill_no;
						$order->saveAttributes(array('bill_no'));
						if ($status == '1') {
						$data = array();
						
						$item_list = array();
						$itemorderitems = OrderItem::model()->findAllByAttributes(array('order_id'=>$order->id));
						if($itemorderitems){
						foreach($itemorderitems as $orderitem){
						$get_item = Item::model()->findByPk($orderitem->item_id);
							if($get_item){
									$item_list[$get_item->item_code] = array('name'=>$get_item->title,'qty'=>$orderitem->qty,'price'=>($orderitem->qty *$orderitem->sale_rate));
							}
						}
						}
						$grand = $order->total_amt;
						$shipping = '0.00';
						/*if($order->total_amt >= 2000){
							$shipping = '0.00';
						}else{
							$shipping = '50.00';
							$grand = $order->total_amt + $shipping;
						}*/

						$data['items']= $item_list;
						$data['sub_total']= $order->total_amt;
						$data['grand_total']= $grand;
						$data['shipping']= $shipping;

						$item_str = '';
						$item_str = json_encode($data);
							Yii::log ( CVarDumper::dumpAsString ( $item_str ), CLogger::LEVEL_WARNING, '$item_str' );

						if(isset($_POST ['online_order_id'])){
							$date = date('Y-m-d H:i:s');
										$online_order = OnlineOrder::model()->findByAttributes(array('id'=>$_POST['online_order_id']));
										if($online_order){
											$online_order->order_status = OnlineOrder::ORDERSTATUS_PACKED;
											$online_order->saveAttributes(array('order_status'));
											
											$order_id = $online_order->order_id;
										//	$order_id = 100007417;
											$ch = curl_init ();
											
											//curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api" );
											curl_setopt ( $ch, CURLOPT_URL, "http://sect4.soulbowl.in/deliveryoption/index/sendemailnotification" );
											curl_setopt ( $ch, CURLOPT_POST, 1 );
											
											curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
													'sKeY' => getenv('POS_SOULBOWL_KEY'),'order_id'=>$online_order->order_id,'action'=>'update_dispatch_status2','status'=>'1','data'=>$item_str,'date'=>$date
											) ) );
											
											curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
											
											$server_output = curl_exec ( $ch );
											
											curl_close ( $ch );
											$response = json_decode ( $server_output, true );
										}
										}
							}
						
						$taxarr = array();
						if ($status == '1') {
						$order->SendSms();
						}
						$arr['status'] = 'OK';
						$arr['order_id'] = $order->id;
						
						if ($status == '1') {
							$bill_prefix = 'B';
							$outlet = Outlet::model()->findByPk($order->outlet_id);
							if($outlet){
								if($outlet->bill_prefix != ''){
									$bill_prefix = $outlet->bill_prefix;
								}else{
									$bill_prefix = 'B';
								}
									
							}
							$arr['bill_no'] = $order->getOrderBillNo();
							$criteria = new CDbCriteria();
							//$criteria->group = 'tax_id';
							$criteria->select ='SUM(qty) AS qty,SUM(tax_amount) AS tax_amount, SUM(cgst_amt) AS cgst_amt, SUM(sgst_amt) AS sgst_amt, SUM(cess_amt) AS cess_amt, SUM(igst_amt) AS igst_amt,t.*';
							$criteria->with = 'item';
							$criteria->group = 'tax_id,item_id,item.hsn_code';
							// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
							// explicitly by the grouped columns to preserve the previous output order.
							$criteria->order = 'tax_id,item_id,item.hsn_code';
							$criteria->compare('order_id',$order->id);
							$itemms = OrderItem::model()->findAll($criteria);

							if($itemms){
								foreach($itemms as $itemmtax){
									$taxarr[] = $itemmtax->getTaxArray();
								}
							}
							$arr['taxes'] =$taxarr;
							
						}else{
							$arr['bill_no'] = '0';
						}
						
						$arr ['message'] = 'Order is saved Successfully';
					} else {
						$transaction->rollback ();
						$arr ['message'] = 'Try again';
					}
				
				}

				else {
						//print_r($order->getErrors());exit;
					$set = false;
				}
			} catch ( Exception $e ) {
					$transaction->rollback ();
			} 
		}
	 					
		return $bill_no;
	}

	
	/**
	 * Calculate base price from sale rate and tax percentage
	 */
	public function getBasePrice($saleRate, $taxPercent)
	{
		if ($taxPercent > 0) {
			return round($saleRate / (1 + ($taxPercent / 100)), 2);
		}
		return round($saleRate, 2);
	}

	public function getTaxAmount($baseprice, $taxPercent)
	{
			return $baseprice * ($taxPercent / 100);
	}
	

}