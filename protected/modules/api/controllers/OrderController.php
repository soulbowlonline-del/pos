<?php
class OrderController extends GxController {
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
								'search',
								'refund',
								'get',
								'discount',
								'list',
								'modes',
								'online',
								'getOnlineOrder',
								'orderUpdate',
								'reprint',
								'getLastOrder',
								'assignOrder',
								'getAssignList',
								'completeOrder',
								'shipOrder',
						    'cancelOrder',
								'getDescriptionByGrn',
								'getDescriptionByBillId'
						),
						'users' => array (
								'*' 
						) 
				),
				
				array (
						'deny',
						'users' => array (
								'*' 
						) 
				) 
		);
	}
	public function actionGetAssignList($status = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if ($loginid == '') {
			$loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
		}
		// $loginid = '1';
		if ($loginid) {
			// $start_date = '2020-05-22';
			if (isset ( $_POST ['start_date'] ) && ($_POST ['start_date'] != '') && isset ( $_POST ['end_date'] ) && ($_POST ['end_date'] != '')) {
				$start_date = date ( 'Y-m-d', strtotime ( $_POST ['start_date'] ) );
				$end_date = date ( 'Y-m-d', strtotime ( $_POST ['end_date'] ) );
			} else {
				$start_date = '2020-05-21';
				$end_date = date ( 'Y-m-d' );
			}
				
			$json_list = array ();
			$criteria = new CDbCriteria ();
			$criteria->addBetweenCondition ( 'date(order_date)', $start_date, $end_date );
			if ($status == 3) {
				$criteria->addCondition ( 'order_status =' . $status );
			} else {

				$criteria->addCondition ( 'is_shipped =' . OnlineOrder::ORDER_SHIPPED );
					
				$criteria->addCondition ( 'order_status !=' . OnlineOrder::ORDERSTATUS_COMPLETED );
			}
			$criteria->addCondition ( 'delivery_boy_id =' . $loginid);
				
			
			$orders = OnlineOrder::model ()->findAll ( $criteria );
				
			if (! empty ( $orders )) {
				foreach ( $orders as $order ) {
					$json_list [] = $order->toArray ();
				}
	
				$arr ['status'] = 'OK';
	
				$arr ['orders'] = $json_list;
			} else {
				$arr ['message'] = 'Online Order not available';
			}
		} else {
			$arr ['message'] = 'Please login';
		}
		$this->sendJSONResponse ( $arr );
	}
		
	public function actionCancelOrder($id = null) {
	    $arr = array (
	        'controller' => $this->id,
	        'action' => $this->action->id,
	        'status' => 'NOK'
	    );
	    $headers = getallheaders ();
	    $loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
	    if ($loginid == '') {
	        $loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
	    }
	    
	    if ($loginid) {
	        $onlineorder = OnlineOrder::model ()->findByPk ( $id );
	        if ($onlineorder) {
	            $onlineorder->order_status = OnlineOrder::ORDERSTATUS_CANCELLED;
	            
	            if ($onlineorder->save ()) {
	                
	                
	                $arr ['status'] = 'OK';
	                $arr ['message'] = 'Order is cancelled successfully';
	                $arr ['assigned'] = $onlineorder->toArray ();
	            }
	          
	            
	        } else {
	            $arr ['message'] = 'Online Order not available';
	        }
	    } else {
	        $arr ['message'] = 'Please login';
	    }
	    $this->sendJSONResponse ( $arr );
	}
	
	public function actionShipOrder($id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if ($loginid == '') {
			$loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
		}
		//$loginid = '520';
		//$loginid = 1;
		/*  $loginid = '1';
		 $_POST ['picker_id'] = 521;
		 $_POST ['delivery_boy_id'] = 520; */
		if ($loginid) {
			$onlineorder = OnlineOrder::model ()->findByPk ( $id );
			if ($onlineorder) {
				$onlineorder->is_shipped =  OnlineOrder::ORDER_SHIPPED;
				$onlineorder->order_status = OnlineOrder::ORDERSTATUS_SHIPPED;
	            $order_id = $onlineorder->order_id;
	            //$order_id = 100007417;
				Yii::log ( CVarDumper::dumpAsString ( $order_id ), CLogger::LEVEL_WARNING, '$order_id' );
				$ch = curl_init ();
				
				curl_setopt ( $ch, CURLOPT_URL, "http://sect4.soulbowl.in/deliveryoption/index/sendemailnotificationdelivery" );
				curl_setopt ( $ch, CURLOPT_POST, 1 );
				
				curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
						'sKeY' => 'f$*@g644^@cghjku853c$','order_id'=>$onlineorder->order_id,'action'=>'update_dispatch_status','status'=>'2'
				) ) );
				
				curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
				
				$server_output = curl_exec ( $ch );
				
				curl_close ( $ch );
				$response = json_decode ( $server_output, true );
				Yii::log ( CVarDumper::dumpAsString ( $response ), CLogger::LEVEL_WARNING, '$response' );
				
				//if($response['success'] == 1){
					if ($onlineorder->save ()) {
						
						
						if($onlineorder->delivery_boy_id != '' && $onlineorder->delivery_boy_id != null){
						$criteria1 = new CDbCriteria ();
						$criteria1->addCondition ( 'id ='.$onlineorder->delivery_boy_id );
						$criteria1->addCondition ( 'device_token IS NOT NULL' );
						$boy = User::model ()->find( $criteria1 );
						if($boy){
							$token = [];
								$token[] = $boy->device_token;
							
							$message["body"] ="A new order is assigned";
							$message["message"] ="A new order is assigned";
							$message["title"] = "New Order";
							$message["sound"] = "default";
							$message["type"] = 1;
							$message["id"] = 1;
						
							$result = $boy->sendGCM($token, $message);
						}
						}
						$arr ['status'] = 'OK';
						$arr ['message'] = 'Order is shipped successfully';
						$arr ['assigned'] = $onlineorder->toArray ();
					}
				/* }else{
					$arr ['error'] = 'Soulbowl api error';
				} */
				
			} else {
				$arr ['message'] = 'Online Order not available';
			}
		} else {
			$arr ['message'] = 'Please login';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionCompleteOrder($id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if ($loginid == '') {
			$loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
		}
		//$loginid = '520';
		/*  $loginid = '1';
		 $_POST ['picker_id'] = 521;
		 $_POST ['delivery_boy_id'] = 520; */
		if ($loginid) {
			$loginuser = User::model ()->findByPk ( $loginid );
			$onlineorder = OnlineOrder::model ()->findByPk ( $id );
			if ($onlineorder) {
				if ($onlineorder->delivery_boy_id == $loginid) {
					$onlineorder->order_status =  OnlineOrder::ORDERSTATUS_COMPLETED;
					$order_id = $onlineorder->order_id;
					//$order_id = 100007417;
					$ch = curl_init ();
					
					//curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api" );
					curl_setopt ( $ch, CURLOPT_URL, "http://sect4.soulbowl.in/deliveryoption/index/sendemailnotificationcomplete" );
				
					curl_setopt ( $ch, CURLOPT_POST, 1 );
					
					curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
							'sKeY' => 'f$*@g644^@cghjku853c$','order_id'=>$onlineorder->order_id,'action'=>'update_dispatch_status','status'=>'3'
					) ) );
					
					curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
					
					$server_output = curl_exec ( $ch );
					
					curl_close ( $ch );
					$response = json_decode ( $server_output, true );
					//if($response['success'] == 1){
	
				if ($onlineorder->save ()) {
					$criteria1 = new CDbCriteria ();
					$criteria1->addCondition ( "role_id = 7"  );
					$criteria1->order = 'id asc';
					$criteria1->addCondition ( 'device_token IS NOT NULL' );
					$users = User::model ()->findAll ( $criteria1 );
					if($users){
						$token = [];
						foreach($users as $user){
							$token[] = $user->device_token;
						}
						$order_id = $onlineorder->order_id;
							
						$message["body"] ="$order_id order is completed";
						$message["message"] ="$order_id order is completed";
						$message["title"] = "New Order";
						$message["sound"] = "default";
						$message["type"] = 1;
						$message["id"] = 1;
					
						$result = $loginuser->sendGCM($token, $message);
					}
					$arr ['status'] = 'OK';
					$arr ['message'] = 'Order is completed successfully';
					$arr ['assigned'] = $onlineorder->toArray ();
				}
					/* }else{
						$arr ['error'] = 'Soulbowl api error';
					} */
				} else {
				$arr ['message'] = 'Online Order not assigned to you';
			}
			} else {
				$arr ['message'] = 'Online Order not available';
			}
		} else {
			$arr ['message'] = 'Please login';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionAssignOrder($id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if ($loginid == '') {
			$loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
		}
		/*  $loginid = '1';
		 $_POST ['picker_id'] = 521;
		 $_POST ['delivery_boy_id'] = 520; */
		if ($loginid) {
			$onlineorder = OnlineOrder::model ()->findByPk ( $id );
			if ($onlineorder) {
				if (isset ( $_POST ['picker_id'] ) && ($_POST ['picker_id'] != '')) {
					$onlineorder->picker_id = $_POST ['picker_id'];
				}
				if (isset ( $_POST ['delivery_boy_id'] ) && ($_POST ['delivery_boy_id'] != '')) {
					$onlineorder->delivery_boy_id = $_POST ['delivery_boy_id'];
				}
				
				if ($onlineorder->save ()) {
					
					$arr ['status'] = 'OK';
					
					$arr ['assigned'] = $onlineorder->toArray ();
				}
			} else {
				$arr ['message'] = 'Online Order not available';
			}
		} else {
			$arr ['message'] = 'Please login';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionReprint($id) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$order = Order::model ()->findByPk ( $id );
		
		if ($order) {
			//$order->SendSms();
			$arr ['status'] = 'OK';
			$bill_prefix = 'B';
			$outlet = Outlet::model ()->findByPk ( $order->outlet_id );
			if ($outlet) {
				if ($outlet->bill_prefix != '') {
					$bill_prefix = $outlet->bill_prefix;
				} else {
					$bill_prefix = 'B';
				}
			}
			$arr ['bill_no'] = $bill_prefix . '-' . $order->bill_no;
			$arr ['bill_date'] = date ( 'd-m-Y', strtotime ( $order->bill_date ) );
			$arr['is_mobile'] = $order->is_mobile;
			$criteria = new CDbCriteria ();
			//$criteria->group = 'tax_id';
			$criteria->select ='SUM(qty) AS qty,SUM(tax_amount) AS tax_amount, SUM(cgst_amt) AS cgst_amt, SUM(sgst_amt) AS sgst_amt, SUM(cess_amt) AS cess_amt, SUM(igst_amt) AS igst_amt,t.*';
			$criteria->with = 'item';
			$criteria->group = 'tax_id,item_id,item.hsn_code';
			// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
			// explicitly by the grouped columns to preserve the previous output order.
			$criteria->order = 'tax_id,item_id,item.hsn_code';
			$criteria->compare ( 'order_id', $order->id );
			$itemms = OrderItem::model ()->findAll ( $criteria );
			
			if ($itemms) {
				foreach ( $itemms as $itemmtax ) {
					$taxarr [] = $itemmtax->getTaxArray ();
				}
			}
			$arr ['taxes'] = $taxarr;
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionOnline($status = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if ($loginid == '') {
			$loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
		}
	 $loginid = '1';
		if ($loginid) {
			
			// $start_date = '2020-05-22';
			if (isset ( $_POST ['start_date'] ) && ($_POST ['start_date'] != '') && isset ( $_POST ['end_date'] ) && ($_POST ['end_date'] != '')) {
				$start_date = date ( 'Y-m-d', strtotime ( $_POST ['start_date'] ) );
				$end_date = date ( 'Y-m-d', strtotime ( $_POST ['end_date'] ) );
			} else {
				$start_date = '2021-05-14';
				$end_date = date ( 'Y-m-d' );
			}
			
			$json_list = array ();
			$criteria = new CDbCriteria ();
			$criteria->addBetweenCondition ( 'date(order_date)', $start_date, $end_date );
			if ($status != null && $status != 0 && $status != '') {
				if($status == 2 ){
					$criteria->addInCondition( 'order_status',array('1','2') );
					
				}else{
				$criteria->addCondition ( 'order_status =' . $status );
				}
			} else {
				$criteria->addCondition ( 'order_status =' . OnlineOrder::ORDERSTATUS_PENDING );
			}
			
			$orders = OnlineOrder::model ()->findAll ( $criteria );
			
			if (! empty ( $orders )) {
				foreach ( $orders as $order ) {
					$json_list [] = $order->toArray ();
				}
				
				$arr ['status'] = 'OK';
				
				$arr ['orders'] = $json_list;
			} else {
				$arr ['message'] = 'Online Order not available';
			}
		} else {
			$arr ['message'] = 'Please login';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionGetOnlineOrder($id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if ($loginid == '') {
			$loginid = isset ( $headers ['login_id'] ) ? $headers ['login_id'] : null;
		}
		$loginid = '1';
		if ($loginid) {
			if ($id != null) {
				$order = OnlineOrder::model ()->findByPk ( $id );
				
				if (! empty ( $order )) {
					
					$arr ['status'] = 'OK';
					
					$arr ['order'] = $order->toArray ( true );
				} else {
					$arr ['message'] = 'Order not available';
				}
			}
		} else {
			$arr ['message'] = 'Please login';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionOrderUpdate($id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		
		if ($id != null) {
			$order = OnlineOrder::model ()->findByPk ( $id );
			
			if (! empty ( $order )) {
				$order->status = OnlineOrder::STATUS_COMPLETED;
				if ($order->save ()) {
					$arr ['status'] = 'OK';
					
					$arr ['message'] = 'Order is completed successfully';
				}
			} else {
				$arr ['message'] = 'Order not available';
			}
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionModes($type = 0) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'type_id =' . $type );
		$modes = PaymentMode::model ()->findAll ( $criteria );
		if (! empty ( $modes )) {
			foreach ( $modes as $mode ) {
				$json_list [] = array (
						'id' => $mode->id,
						'title' => $mode->title 
				);
			}
			
			$arr ['status'] = 'OK';
			
			$arr ['modes'] = $json_list;
		} else {
			$arr ['message'] = 'Order not available';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionList() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		/*
		 * $_POST ['start_date']= '2018-07-01';
		 * $_POST ['end_date'] = '2018-07-03';
		 */
		if (isset ( $_POST ['start_date'] ) && isset ( $_POST ['end_date'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->order = 'id asc';
			$criteria->addBetweenCondition ( 'bill_date', $_POST ['start_date'], $_POST ['end_date'] );
			$orders = Order::model ()->findAll ( $criteria );
			
			if (! empty ( $orders )) {
				
				foreach ( $orders as $order ) {
					$json_list [] = $order->toArray1 ();
				}
				
				$arr ['status'] = 'OK';
				
				$arr ['orders'] = $json_list;
			} else {
				$arr ['message'] = 'No data to display';
			}
		} else {
			$arr ['message'] = 'No data posted';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionGetLastOrder() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		$criteria = new CDbCriteria ();
		$criteria->order = 'id desc';
		$criteria->limit = '1';
		$order = Order::model ()->find ( $criteria );
		if ($order) {
			
			$arr ['status'] = 'OK';
			
			$arr ['orders'] [] = $order->toArray1 ();
		} else {
			$arr ['message'] = 'No order found';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionSearch() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if ($loginid) {
			$user = User::model ()->findByPk ( $loginid );
			if ($user) {
				$emp = Emp::model ()->findByPk ( $user->emp_id );
				if ($emp) {
					if (isset ( $_POST ['bill_date'] ) || isset ( $_POST ['bill_no'] ) || isset ( $_POST ['customer_id'] )) {
						$criteria = new CDbCriteria ();
						if (isset ( $_POST ['bill_date'] ))
							$criteria->compare ( "bill_date ", $_POST ['bill_date'] );
						if (isset ( $_POST ['bill_no'] ))
							$criteria->compare ( "bill_no ", $_POST ['bill_no'] );
						if (isset ( $_POST ['customer_id'] ))
							$criteria->compare ( "customer_id ", $_POST ['customer_id'] );
						$orders = OrderItem::model ()->findAll ( $criteria );
						$json_list = array ();
						if ($orders) {
							foreach ( $orders as $order ) {
								$json_list [] = $order->toArray ();
							}
							$arr ['status'] = 'OK';
							
							$arr ['orders'] = $json_list;
						} else {
							$arr ['message'] = 'No order found';
						}
					} else {
						$arr ['message'] = 'No data posted';
					}
				} else {
					$arr ['message'] = 'No employee found';
				}
			} else {
				$arr ['message'] = 'No User found';
			}
		} else {
			$arr ['message'] = 'Please Login First';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionRefund() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		
		/*
		 * $_POST ['item_details'] = '[{"bar_code":"8901058856446","qty":"2","total_amt":20,"is_return":"2","total_sale":20}]';
		 * $_POST ['order_id'] = 369952;
		 * $_POST ['type_id'] = 1;
		 */
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if (isset ( $_POST ['item_details'] ) && isset ( $_POST ['order_id'] ) && isset ( $_POST ['type_id'] )) {
			$item_arrays = json_decode ( $_POST ['item_details'] );
			if ($item_arrays) {
				$total_amount = 0;
				$set = true;
				$transaction = Yii::app ()->db->beginTransaction ();
				try {
					foreach ( $item_arrays as $item_array ) {
						$criteria1 = new CDbCriteria ();
						$criteria1->compare ( "bar_code ", $item_array->bar_code );
						$itemdetail = ItemDetail::model ()->find ( $criteria1 );
						
						if ($itemdetail) {
							$criteria = new CDbCriteria ();
							
							$criteria->compare ( "item_detail_id ", $itemdetail->id );
							$criteria->compare ( "order_id ", $_POST ['order_id'] );
							$criteria->addCondition ( 'qty >= "' . $item_array->is_return . '" ' );
							
							$orderitem = OrderItem::model ()->find ( $criteria );
							$json_list = array ();
							if ($orderitem) {
								$order = Order::model ()->findByPk ( $orderitem->order_id );
								
								$item = Item::model ()->findByPk ( $itemdetail->item_id );
								
								$refundmodel = OrderRefund::model ()->findByAttributes ( array (
										'order_id' => $order->id 
								) );
								if ($refundmodel == null) {
									$refundmodel = new OrderRefund ();
								}
								$refundmodel->qty = $item_array->is_return;
								$refundmodel->type_id = $_POST ['type_id'];
								$refundmodel->city_id = $order->city_id;
								$refundmodel->state_id = $order->state_id;
								$refundmodel->country_id = $order->country_id;
								$refundmodel->address = $order->address;
								$refundmodel->note = $order->note;
								$refundmodel->customer_id = $order->customer_id;
								$refundmodel->order_id = $orderitem->order_id;
								
								if ($refundmodel->save ()) {
									
									$refunditem = OrderRefundItem::model ()->findByAttributes ( array (
											'order_refund_id' => $refundmodel->id,
											'item_detail_id' => $itemdetail->id,
											'item_id' => $itemdetail->item_id 
									) );
									if ($refunditem == null) {
										$refunditem = new OrderRefundItem ();
									}
									$refunditem->order_refund_id = $refundmodel->id;
									$refunditem->item_detail_id = $itemdetail->id;
									$refunditem->item_id = $itemdetail->item_id;
									$refunditem->qty = $item_array->is_return;
									$refunditem->total_amt = $item_array->total_sale;
									$refunditem->price = $orderitem->price;
									$refunditem->discount_id = $orderitem->discount_id;
									$refunditem->discount_amt = (($orderitem->discount_amt) / ($orderitem->qty)) * ($item_array->is_return);
									$refunditem->tax_id = $orderitem->tax_id;
									$refunditem->tax_amt = (($orderitem->tax_amount) / ($orderitem->qty)) * ($item_array->is_return);
									$refunditem->order_discount = $orderitem->order_discount;
									$refunditem->create_user_id = $loginid;
									if ($refunditem->save ()) {
										
										$model = ItemStock::model ()->findByAttributes ( array (
												'item_detail_id' => $itemdetail->id,
												'item_id' => $itemdetail->item_id 
										), array (
												'order' => 'id DESC' 
										) );
										if ($model) {
											$purchase = ($model->purchase_qty) + $item_array->is_return;
											$balance = ($model->balance_qty) + $item_array->is_return;
											
											$model->purchase_qty = $purchase;
											$model->balance_qty = $balance;
											
											if ($model->save ()) {
												$stocklog = new StockLog ();
												$stocklog->item_detail_id = $model->item_detail_id;
												$stocklog->item_id = $model->item_id;
												$stocklog->batch_no = $model->batch_number;
												if ($itemdetail) {
													$stocklog->current_qty = $itemdetail->getStockQty ();
													$stocklog->previous_qty = ($itemdetail->getStockQty ()) - ($item_array->is_return);
												}
												$stocklog->Qty = $item_array->is_return;
												$stocklog->outlet_id = $model->outlet_id;
												$stocklog->vendor_id = $model->vendor_id;
												$stocklog->type_id = StockLog::TYPE_REFUND;
												if ($stocklog->save ()) {
												} else {
													$set = false;
												}
												$remain = $item->getTotalRemainingQuantity ();
												$min_qty = $item->min_qty;
												if ($remain > $min_qty) {
													$mrsdetails = MrsDetail::model ()->findAllByAttributes ( array (
															'item_id' => $item->id,
															'status' => Mrs::STATUS_PENDING 
													) );
													Yii::log ( CVarDumper::dumpAsString ( $mrsdetails ), CLogger::LEVEL_WARNING, '$mrsdetails' );
													if ($mrsdetails) {
														foreach ( $mrsdetails as $mrsdetail ) {
															$mrs_id = $mrsdetail->mrs_id;
															$mrs_item_id = $mrsdetail->item_id;
															$criteria1 = new CDbCriteria ();
															
															$criteria1->compare ( "mrs_id ", $mrsdetail->mrs_id );
															
															$mrsItems = MrsDetail::model ()->count ( $criteria1 );
															if ($mrsdetail && $item->id == $mrsdetail->item_id) {
																$mrsdetail->delete ();
															}
															
															if ($mrsItems == 1) {
																$mrs = Mrs::model ()->findByPk ( $mrs_id );
																if ($mrs && $item->id == $mrs_item_id) {
																	
																	$mrn = Mrn::model ()->findByAttributes ( array (
																			'mrs_id' => $mrs->id 
																	) );
																	if (! $mrn) {
																		$mrs->delete ();
																	}
																}
															}
														}
													}
												}
											} else {
												$set = false;
											}
										}
									} else {
										$set = false;
									}
									
									$total_amount = $total_amount + ($refunditem->total_amt);
								} else {
									$set = false;
								}
								
								$arr ['data'] = $json_list;
							} else {
								$set = false;
								$arr ['message'] = 'No order found';
							}
						} else {
							$set = false;
							$arr ['message'] = 'No Item found';
						}
					}
					if ($set == true) {
						$transaction->commit ();
						// Adjust loyalty points proportional to the refunded amount:
						// deduct earned points and restore any redeemed points for returned items.
						if (isset($_POST ['order_id'])) {
							LoyaltyService::processRefundDeductPoints($_POST ['order_id'], $total_amount);
						}
						if ($_POST ['type_id'] == 2) {
							$creditnote = new CreditNote ();
							$creditnote->credit_number = User::randomBarcode ( '11' );
							$creditnote->amt = $total_amount;
							$creditnote->save ();
							$arr ['credit_amt'] = $total_amount;
						}
						$refundmodel->total_amt = $total_amount;
						$refundmodel->saveAttributes ( array (
								'total_amt' 
						) );
						$arr ['status'] = 'OK';
						$arr ['refund_date'] = date ( "Y-m-d" );
						$arr ['refund_amount'] = $total_amount;
					} else {
						$transaction->rollback ();
					}
				} catch ( Exception $e ) {
					$transaction->rollback ();
				}
			}
		} else {
			$arr ['message'] = 'No data posted';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	/*
	 * public function actionRefund() {
	 * $arr = array (
	 * 'controller' => $this->id,
	 * 'action' => $this->action->id,
	 * 'status' => 'NOK'
	 * );
	 *
	 * if (isset ( $_POST ['bar_code'] ) && isset ( $_POST ['qty'] ) && isset ( $_POST ['order_id']) && isset ( $_POST ['type_id'] )) {
	 * $criteria1 = new CDbCriteria ();
	 * $criteria1->compare ( "bar_code ", $_POST ['bar_code'] );
	 * $itemdetail = ItemDetail::model ()->find ( $criteria1 );
	 *
	 * if($itemdetail){
	 * $criteria = new CDbCriteria ();
	 *
	 * $criteria->compare ( "item_detail_id ", $itemdetail->id );
	 * $criteria->compare ( "order_id ", $_POST ['order_id'] );
	 * $criteria->addCondition('qty >= "'.$_POST ['qty'].'" ');
	 *
	 * $orderitem = OrderItem::model ()->findAll ( $criteria );
	 * $json_list = array();
	 * if ($orderitem) {
	 * $order = Order::model()->findByPk($orderitem[0]->order_id);
	 *
	 * $item = Item::model()->findByPk($itemdetail->item_id);
	 *
	 * $refundmodel = OrderRefund::model()->findByAttributes(array('order_id'=>$order->id));
	 * if($refundmodel == null){
	 * $refundmodel = new OrderRefund();
	 * }
	 * $refundmodel->qty = $_POST['qty'];
	 * $refundmodel->type_id = $_POST['type_id'];
	 * $refundmodel->city_id = $order->city_id;
	 * $refundmodel->state_id = $order->state_id;
	 * $refundmodel->country_id = $order->country_id;
	 * $refundmodel->address = $order->address;
	 * $refundmodel->note = $order->note;
	 * $refundmodel->customer_id = $order->customer_id;
	 * $refundmodel->order_id = $orderitem[0]->order_id;
	 *
	 *
	 * if($refundmodel->save()){
	 *
	 * $refunditem = OrderRefundItem::model()->findByAttributes(array('order_refund_id'=>$refundmodel->id,
	 * 'item_detail_id'=>$itemdetail->id,'item_id'=>$itemdetail->item_id
	 * ));
	 * if($refunditem == null){
	 * $refunditem = new OrderRefundItem();
	 * }
	 * $refunditem->order_refund_id= $refundmodel->id;
	 * $refunditem->item_detail_id= $itemdetail->id;
	 * $refunditem->item_id= $itemdetail->item_id;
	 * $refunditem->qty= $_POST['qty'];
	 * $refunditem->price= $orderitem[0]->price;
	 * $refunditem->discount_id= $orderitem[0]->discount_id;
	 * $refunditem->discount_amt= $orderitem[0]->discount_amt;
	 * $refunditem->tax_id= $orderitem[0]->tax_id;
	 * $refunditem->tax_amt= $orderitem[0]->tax_amount;
	 * $refunditem->order_discount= $orderitem[0]->order_discount;
	 * $refunditem->save();
	 *
	 * $model = ItemStock::model ()->findByAttributes (
	 * array (
	 * 'item_detail_id' => $itemdetail->id,
	 * 'item_id' => $itemdetail->item_id,
	 * ),
	 * array(
	 * 'order' => 'id DESC',
	 * ));
	 * if($model){
	 * $purchase = ($model->purchase_qty) + $_POST ['qty'];
	 * $balance = ($model->balance_qty) + $_POST ['qty'];
	 *
	 * $model->purchase_qty = $purchase;
	 * $model->balance_qty = $balance;
	 *
	 * $model->save ();
	 * }
	 *
	 * $total_amount = (($orderitem[0]->price * $_POST ['qty']) + ($orderitem[0]->tax_amount * $_POST ['qty']) - ($orderitem[0]->discount_amt * $_POST ['qty']) - ($orderitem[0]->order_discount * $_POST ['qty']));
	 * if($_POST['type_id'] == 2){
	 * $creditnote = new CreditNote();
	 * $creditnote->credit_number = User::randomBarcode('11');
	 * $creditnote->amt = $total_amount;
	 * $creditnote->save();
	 * $json_list['credit_amt'] = $total_amount;
	 * }
	 * }
	 * $refundmodel->total_amt = $refundmodel->getRefundTotalAmount();
	 * $refundmodel->saveAttributes(array('total_amt'));
	 * $arr ['status'] = 'OK';
	 * $json_list ['item_name'] = $item->title;
	 * $json_list ['refund_date'] = date("Y-m-d");
	 * $json_list ['refund_amount'] = $total_amount;
	 *
	 * $arr ['data'] = $json_list;
	 * } else {
	 * $arr ['message'] = 'No order found';
	 * }
	 * } else {
	 * $arr ['message'] = 'No Item found';
	 * }
	 * } else {
	 * $arr ['message'] = 'No data posted';
	 * }
	 * $this->sendJSONResponse ( $arr );
	 * }
	 */
	public function actionGet($id) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		$order = Order::model ()->findByPk ( $id );
		if (! empty ( $order )) {
			
			$arr ['status'] = 'OK';
			
			$arr ['order'] = $order->toArray ();
		} else {
			$arr ['message'] = 'Order not available';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionDiscount() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'start_date <= ' . '"' . date ( 'Y-m-d' ) . '"' );
		$criteria->addCondition ( 'end_date >= ' . '"' . date ( 'Y-m-d' ) . '"' );
		$criteria->addCondition ( 'discount_type =' . Discount::DISCOUNT_ORDER );
		$criteria->addCondition ( 'status =' . Discount::STATUS_ACTIVE );
		$discounts = Discount::model ()->findAll ( $criteria );
		
		if (! empty ( $discounts )) {
			
			$arr ['status'] = 'OK';
			foreach ( $discounts as $discount ) {
				if (($discount->start_time == '00:00:00') && ($discount->end_time == '00:00:00')) {
					$json_list [] = $discount->toArray ();
				} else {
					if (($discount->start_time <= date ( 'H:i:s' )) && ($discount->end_time >= date ( 'H:i:s' ))) {
						$json_list [] = $discount->toArray ();
					}
				}
			}
			if (! empty ( $json_list )) {
				$arr ['discountList'] = $json_list;
			} else {
				$arr ['message'] = 'Discount not available';
			}
		} else {
			$arr ['message'] = 'Discount not available';
		}
		
		$this->sendJSONResponse ( $arr );
	}

	/**
	 * Get description by Bill ID
	 * Searches in tbl_order and returns bill date and bill number
	 */
	public function actionGetDescriptionByBillId() {
		$arr = array(
			'controller' => $this->id,
			'action' => $this->action->id,
			'status' => 'NOK'
		);
		
		try {
			$billId = isset($_POST['bill_id']) ? $_POST['bill_id'] : (isset($_GET['bill_id']) ? $_GET['bill_id'] : null);
			
			if (empty($billId)) {
				$arr['message'] = 'Bill ID is required';
				$this->sendJSONResponse($arr);
				return;
			}
			
			// Search in tbl_order - handle both numeric ID and bill number formats
			$criteria = new CDbCriteria();
			
			// If billId is numeric, search by id, otherwise extract number and search by bill_no
			if (is_numeric($billId)) {
				$criteria->addCondition('bill_no = :bill_id');
				$criteria->order = 'id DESC';
				$criteria->params = array(':bill_id' => $billId);
			} else {
				// Handle formats like "B-40840" - extract only the number part for searching
				$billNumber = preg_replace('/[^0-9]/', '', $billId); // Remove all non-numeric characters
				$criteria->addCondition('bill_no = :bill_no');
				$criteria->order = 'id DESC'; // In case of multiple matches, get the latest one
				$criteria->params = array(':bill_no' => $billNumber);
			}
			
			$order = Order::model()->find($criteria);
			
			if ($order) {
				// Format: Bill Number - Bill Date
				$billDate = date('d-m-Y', strtotime($order->bill_date));
				$description = "Bill No: {$order->bill_no} - Date: {$billDate}";
				
				$arr['status'] = 'OK';
				$arr['description'] = $description;
				$arr['bill_number'] = $order->bill_no;
				$arr['bill_date'] = $billDate;
			} else {
				$arr['message'] = 'Bill not found with ID: ' . $billId;
			}
			
		} catch (Exception $e) {
			$arr['message'] = 'Error: ' . $e->getMessage();
		}
		
		$this->sendJSONResponse($arr);
	}
	
	/**
	 * Get description by GRN Number
	 * Searches in tbl_purchase_bill and tbl_purchase_bill_detail
	 * Returns all items with format: item name, approved qty, mrp, price, amount and date
	 */
	public function actionGetDescriptionByGrn() {
		$arr = array(
			'controller' => $this->id,
			'action' => $this->action->id,
			'status' => 'NOK'
		);
		
		try {
			$grnNumber = isset($_POST['grn_number']) ? $_POST['grn_number'] : (isset($_GET['grn_number']) ? $_GET['grn_number'] : null);
			
			if (empty($grnNumber)) {
				$arr['message'] = 'GRN Number is required';
				$this->sendJSONResponse($arr);
				return;
			}
			
			// Search in tbl_purchase_bill
			$criteria = new CDbCriteria();
			$criteria->addCondition('id = :grn_number');
			$criteria->params = array(':grn_number' => $grnNumber);
			
			$purchaseBill = PurchaseBill::model()->find($criteria);
			
			if ($purchaseBill) {
				// Get purchase bill details
				$detailCriteria = new CDbCriteria();
				$detailCriteria->addCondition('purchase_bill_id = :bill_id');
				$detailCriteria->params = array(':bill_id' => $purchaseBill->id);
				$detailCriteria->with = array('item'); // Assuming relationship exists
				
				$purchaseDetails = PurchaseBillDetail::model()->findAll($detailCriteria);
				
				if (!empty($purchaseDetails)) {
					$description = "GRN: {$grnNumber}\n";
					$description .= "Date: " . date('d-m-Y', strtotime($purchaseBill->create_time)) . "\n\n";
					$description .= "Items:\n";
					
					foreach ($purchaseDetails as $detail) {
						$productName = isset($detail->item) ? $detail->item->short_name : 'Product ID: ' . $detail->product_id;
						$approvedQty = isset($detail->approved_qty) ? $detail->approved_qty : $detail->qty;
						$mrp = isset($detail->mrp) ? number_format($detail->mrp, 2) : '0.00';
						$price = isset($detail->unit_price) ? number_format($detail->unit_price, 2) : (isset($detail->price) ? number_format($detail->price, 2) : '0.00');
						$amount = isset($detail->amount) ? number_format($detail->amount, 2) : ($approvedQty * $price);
						
						$description .= "- {$productName}, Qty: {$approvedQty}, MRP: ₹{$mrp}, Price: ₹{$price}, Amount: ₹{$amount}\n";
					}
					
					$arr['status'] = 'OK';
					$arr['description'] = $description;
					$arr['grn_number'] = $grnNumber;
					$arr['bill_date'] = date('d-m-Y', strtotime($purchaseBill->create_time));
					$arr['item_count'] = count($purchaseDetails);
				} else {
					$arr['message'] = 'No items found for GRN: ' . $grnNumber;
				}
			} else {
				$arr['message'] = 'GRN not found: ' . $grnNumber;
			}
			
		} catch (Exception $e) {
			$arr['message'] = 'Error: ' . $e->getMessage();
		}
		
		$this->sendJSONResponse($arr);
	}

	

}