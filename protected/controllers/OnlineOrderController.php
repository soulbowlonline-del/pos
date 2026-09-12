<?php
class OnlineOrderController extends GxController {
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
								'online','api' /* 'download', 'thumbnail' */),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'admin',
								'view',
								'update',
								//'hold',
								'pdf',
								'countOrders'
						),
						'users' => array (
								'@' 
						) 
				),
				/*array('allow', 
					'actions'=>array('admin','delete'),
					'expression'=>'Yii::app()->user->isAdmin',
					),*/
				array (
						'deny',
						'users' => array (
								'*' 
						) 
				) 
		);
		
	}
	public function actionCountOrders(){
		$count = OnlineOrder::model()->countByAttributes(array('type_id'=>OnlineOrder::TYPE_NEW));
		 echo $count;
	}
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionPdf($id)
	{
		$order = OnlineOrder::model()->findByPk($id);
		//$id  =2;
		$set = true;
		
	
			
		# mPDF
		$mPDF1 = Yii::app()->ePdf->mpdf();
	
		# You can easily override default constructor's params
		$mPDF1 = Yii::app()->ePdf->mpdf('', 'A4');
	
		# render (full page)
		//$mPDF1->WriteHTML($this->render('index', array(), true));
	
		# Load a stylesheet
		//$stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
		//$mPDF1->WriteHTML($stylesheet, 1);
	
		# renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML($this->renderPartial('_pdf',array('order'=>$order), true));
	
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
	
	
	
	}
	public function actionApi() {
		
		$key = "KjxyUSjh";
		$salt = "74BhEL1a28";
		$r = array('key' => $key ,'salt' => $salt);
		$qs= http_build_query($r);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $qs );
		curl_setopt($ch, CURLOPT_URL, "https://www.payumoney.com/payment/smsInvoice?
				customerName=Sonia&customerobileNumber=8847474669&amount=10
				&description=test&referenceId=2222");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('authorization:wQ3dHRked1Nqc2bywGrrMsNlyPYdXtKrDC+NAxekEdc='));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$out = curl_exec($ch);
		
		if (curl_errno($ch)) {
			$sad = curl_error($ch);
			throw new Exception($sad);
		}
		curl_close($ch);
		echo 'hello';
		print_r($out);
		exit;
	
	}
	public function actionOnline() {
		$order_id = '100008065';
		$criteria = new CDbCriteria();
		$criteria->order = 'order_id desc';
		$order = OnlineOrder::model()->find($criteria);
		if($order){
			$order_id = $order->order_id;
		}
		
		$ch = curl_init ();
	
		//curl_setopt ( $ch, CURLOPT_URL, "https://soulbowl.in/shell/apis/recentorders_demo.php" );
		curl_setopt ( $ch, CURLOPT_URL, "https://sect4.soulbowl.in/pos/getRecentOrderSoul.php" );
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		
		curl_setopt ( $ch, CURLOPT_POST, 1 );
	
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
				'skey' => '5a88131bac8400eff9ca87e9707b3c4a','orderid'=>$order_id
		) ) );
	
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	
		$server_output = curl_exec ( $ch );
	
		curl_close ( $ch );
		$response = json_decode ( $server_output, true );
		$set = true;
		if ($response ['error'] != true) {
			$resultorders = $response ['result'];
				
			if (! empty ( $resultorders )) {
	            $count = 0;
				foreach ( $resultorders as $result ) {
					
					$transaction = Yii::app ()->db->beginTransaction ();
					try {
	
						$onlineorder = OnlineOrder::model ()->findByAttributes ( array (
								'order_id' => $result ['order_id']
						) );
						if ($onlineorder == null) {
							$onlineorder = new OnlineOrder ();
						}
							
						if (isset ( $result ['order_from'] )) {
							$onlineorder->order_from = $result ['order_from'];
						}
						if (isset ( $result ['comment'] ) && ($result ['comment']  != '')) {
							$onlineorder->comment = $result ['comment'];
						}
						if (isset ( $result ['order_id'] )) {
							$onlineorder->order_id = $result ['order_id'];
						}
						if (isset ( $result ['order_date'] )) {
							$onlineorder->order_date = date ( 'Y-m-d H:i:s', strtotime ( $result ['order_date'] ) );
						}
						if (isset ( $result ['item_count'] )) {
							$onlineorder->item_count = $result ['item_count'];
						}
						if (isset ( $result ['grand_total'] )) {
							$onlineorder->grand_total = $result ['grand_total'];
						}
						if (isset ( $result ['deliveryslot'] )) {
							$onlineorder->delivery_slot = $result ['deliveryslot'];
						}
						if (isset ( $result ['payment_method'] )) {
							$onlineorder->payment_method = $result ['payment_method'];
						}
						if (isset ( $result ['shipping_method'] )) {
							$onlineorder->delivery_method = $result ['shipping_method'];
						}
						if (isset ( $result ['ship_name'] )) {
							$onlineorder->ship_name = $result ['ship_name'];
						}
						if (isset ( $result ['status'] )) {
							$onlineorder->status = $result ['status'];
						}
						if (isset ( $result ['shipping_address'] )) {
							$shipping = $result ['shipping_address'];
							if (isset ( $shipping ['firstname'] )) {
								$onlineorder->first_name = $shipping ['firstname'];
							}
							if (isset ( $shipping ['lastname'] )) {
								$onlineorder->last_name = $shipping ['lastname'];
							}
							if (isset ( $shipping ['street'] )) {
							    $onlineorder->street = implode ( ',', $shipping ['street'] );
							    if(isset($shipping ['home']) && ($shipping ['home'] != '')){
							        $onlineorder->street = $shipping ['home'].implode ( ',', $shipping ['street'] );
							    }
								
							}
							if (isset ( $shipping ['telephone'] )) {
								$onlineorder->telephone = $shipping ['telephone'];
							}
							if (isset ( $shipping ['mobile'] )) {
								$onlineorder->mobile = $shipping ['mobile'];
							}
							if (isset ( $shipping ['postcode'] )) {
								$onlineorder->zip_code = $shipping ['postcode'];
							}
							if (isset ( $shipping ['city'] )) {
								$onlineorder->city = $shipping ['city'];
							}
							if (isset ( $shipping ['country_name'] )) {
								$onlineorder->country = $shipping ['country_name'];
							}
						}
						if ($onlineorder->save ()) {
							$count = $count + 1;
							$criteria1 = new CDbCriteria ();
							$criteria1->compare ( "contact_no", $onlineorder->mobile );
							$criteria1->order = 'id asc';
							$customer = Customer::model ()->find ( $criteria1 );
								
							$criteria1 = new CDbCriteria ();
							$criteria1->compare ( "title", $onlineorder->city );
							$criteria1->order = 'id asc';
							$city = City::model ()->find ( $criteria1 );
								
							if ($customer == null) {
								$customer = new Customer ();
							}
							$customer->create_time= date('Y-m-d H:i:s');
							$customer->update_time= date('Y-m-d H:i:s');
							if ($onlineorder->last_name != '') {
								$customer->name = $onlineorder->first_name . ' ' . $onlineorder->last_name;
							} else {
								$customer->name = $onlineorder->first_name;
							}
							$customer->contact_no = $onlineorder->mobile;
							$customer->address = $onlineorder->street;
							$customer->zip_code = $onlineorder->zip_code;
							if(isset($result ['shipping_address']['email']) && ($result ['shipping_address']['email'] != '')){
							$customer->email = $result ['shipping_address']['email'];
							}
							if ($city) {
								$customer->city_id = $city->id;
								$customer->state_id = $city->state_id;
							
								$state = State::model ()->findByPk ( $city->state_id );
								if ($state) {
									$customer->country_id = $state->country_id;
								}
							
							}
								
							//$transaction = Yii::app ()->db->beginTransaction ();
							//try {
							$customer->save();
							
							
							$items = $result ['items'];
							foreach ( $items as $item ) {
								$orderitem = OnlineOrderItem::model ()->findByAttributes ( array (
										'order_id' => $onlineorder->id,
										'product_code'=>$item ['sku']
								) );
								if ($orderitem == null) {
									$orderitem = new OnlineOrderItem ();
								}
									
								if (isset ( $item ['name'] )) {
									$orderitem->name = $item ['name'];
								}
								if (isset ( $item ['barcode'] )) {
									$orderitem->barcode = $item ['barcode'];
								}
								if (isset ( $item ['qty'] )) {
									$orderitem->qty = $item ['qty'];
								}
								if (isset ( $item ['price'] )) {
									$orderitem->price = $item ['price'];
								}
								if (isset ( $item ['total'] )) {
									$orderitem->total = $item ['total'];
								}
								if (isset ( $item ['image_url'] )) {
									$orderitem->image_url = $item ['image_url'];
								}
								if (isset ( $item ['sku'] )) {
									$orderitem->product_code = $item ['sku'];
								}
								$orderitem->order_id = $onlineorder->id;
								if ($orderitem->save ()) {
								} else {
									$set = false;
									$errors1 = $orderitem->getErrors ();
									Yii::log ( CVarDumper::dumpAsString ( $errors1 ), CLogger::LEVEL_WARNING, '$errors1' );
								}
							}
						} else {
							$set = false;
							$errors = $onlineorder->getErrors ();
							Yii::log ( CVarDumper::dumpAsString ( $errors ), CLogger::LEVEL_WARNING, '$errors' );
						}
							
						if ($set == true) {
							$transaction->commit ();
						} else {
						 $transaction->rollback ();
						}
					} catch ( Exception $e ) {
						$transaction->rollback ();
					}
	
				}
				if($count > 0){
				$criteria1 = new CDbCriteria ();
				$criteria1->addNotInCondition ( "role_id" ,array('6','10'));
				$criteria1->order = 'id asc';
				$criteria1->addCondition ( 'device_token IS NOT NULL' );
				$users = User::model ()->findAll ( $criteria1 );
				if($users){
					$token = [];
					foreach($users as $user){
						$token[] = $user->device_token;
					}
					if($count == 1){
						$message["body"] ="$count New online order is added";
						$message["message"] ="$count New online order is added";
					}else{
					$message["body"] ="$count New online orders are added";
					$message["message"] ="$count New online orders are added";
					}
					$message["title"] = "New Order";
					$message["sound"] = "default";
					$message["type"] = 1;
					$message["id"] = 1;
						
						$result = $user->sendGCM($token, $message);
					
						
					
				}
			}
				//
			}
				
			//
		}
		/*
		 * echo '<pre>';
		 * print_r($response);
		 * exit;
		 */
	}
	public function actionHold($id) {
		$set = true;
		$model = $this->loadModel ( $id, 'OnlineOrder' );
		$criteria1 = new CDbCriteria ();
		$criteria1->compare( "title",$model->payment_method );
		$paymentmode = PaymentMode::model ()->find ( $criteria1 );
		if($paymentmode == null){
		$criteria1 = new CDbCriteria ();
		$criteria1->addCondition( "type_id = 0" );
		$criteria1->order = 'id asc';
		$paymentmode = PaymentMode::model ()->find ( $criteria1 );
		}
		$criteria2 = new CDbCriteria ();
		$criteria2->compare( "title",$model->delivery_method );
		$deliverymode = PaymentMode::model ()->find ( $criteria2 );
		if($deliverymode == null){
		$criteria2 = new CDbCriteria ();
		$criteria2->order = 'id asc';
		$criteria2->addCondition ( "type_id = 1" );
		$deliverymode = PaymentMode::model ()->find ( $criteria2 );
		}
		$criteria3 = new CDbCriteria ();
		$criteria3->order = 'id asc';
		$outlet = Outlet::model ()->find ( $criteria3 );
		$month = date ( 'm' );
		if ($month > 3) {
			$year = date ( 'Y' );
			$yearlast = $year + 1;
			$start_date = $year . '-04-01';
			$end_date = $yearlast . '-03-31';
		} else {
			$year = date ( 'Y' );
			$yearlast = $year - 1;
			$start_date = $yearlast . '-04-01';
			$end_date = $year . '-03-31';
		}
		if ($model) {
			$criteria1 = new CDbCriteria ();
			$criteria1->compare ( "contact_no", $model->telephone );
			$criteria1->order = 'id asc';
			$customer = Customer::model ()->find ( $criteria1 );
			
			$criteria1 = new CDbCriteria ();
			$criteria1->compare ( "title", $model->city );
			$criteria1->order = 'id asc';
			$city = City::model ()->find ( $criteria1 );
			
			if ($customer == null) {
				$customer = new Customer ();
			}
			$customer->create_time= date('Y-m-d H:i:s');
			$customer->update_time= date('Y-m-d H:i:s');
			if ($model->last_name != '') {
				$customer->name = $model->first_name . ' ' . $model->last_name;
			} else {
				$customer->name = $model->first_name;
			}
			$customer->contact_no = $model->telephone;
			$customer->address = $model->street;
			$customer->zip_code = $model->zip_code;
			if ($city) {
				$customer->city_id = $city->id;
				$customer->state_id = $city->state_id;
				
				$state = State::model ()->findByPk ( $city->state_id );
				if ($state) {
					$customer->country_id = $state->country_id;
				}
				
			}
			
			//$transaction = Yii::app ()->db->beginTransaction ();
			//try {
			if($customer->save()){
				
				$orderhold = OrderHold::model ()->findByAttributes ( array (
						'online_order_id' => $id
				) );
				if ($orderhold == null) {
					$orderhold = new OrderHold ();
				}
			
			$criteria = new CDbCriteria ();
			$criteria->order = 'bill_no desc';
			if ($start_date != '' && $end_date != '') {
				$criteria->addBetweenCondition ( 'date(create_time)', $start_date, $end_date );
			}
			$latestorder = Order::model ()->find ( $criteria );
			if ($latestorder) {
				$bill_no = $latestorder->bill_no + 1;
			} else {
				$bill_no = 1;
			}
			
			$orderhold->bill_no = $model->order_id;
			$orderhold->online_order_id = $id; // Onlineorderid in database
			$orderhold->qty = $model->item_count;
			$orderhold->total_amt = $model->grand_total;
			$orderhold->bill_date = date ( 'Y-m-d', strtotime ( $model->order_date ) );
			$orderhold->mode_of_payment = $paymentmode->id;
			$orderhold->mode_of_delivery = $deliverymode->id;
			$orderhold->outlet_id = $outlet->id;
			$orderhold->city_id = $customer->city_id; // Save customer
			$orderhold->state_id = $customer->state_id;
			$orderhold->country_id = $customer->country_id;
			$orderhold->customer_id = $customer->id;
			$orderhold->create_user_id = Yii::app ()->user->id;
			
			if ($orderhold->save ()) {
				$onlineitems = OnlineOrderItem::model()->findAllByAttributes ( array (
						'order_id' => $id 
				) );
				
				if ($onlineitems) {
					foreach ( $onlineitems as $onlineitem ) {
						$criteria5 = new CDbCriteria ();
						$criteria5->compare ( "item_code", $onlineitem->product_code );
						$item = Item::model ()->find ( $criteria5 );
						if ($item) {
							$criteria4 = new CDbCriteria ();
							$criteria4->compare ( "item_id", $item->id );
							$criteria4->compare ( "bar_code", $onlineitem->barcode );
							$itemdetail = ItemDetail::model ()->find ( $criteria4 );
							if($itemdetail == null){
								$barcode = $item->getItemBarcodes();
								$criteria4 = new CDbCriteria ();
								$criteria4->compare ( "item_id", $item->id );
								$criteria4->compare ( "bar_code", $barcode );
								$itemdetail = ItemDetail::model ()->find ( $criteria4 );
							}
							if ($itemdetail) {
								$orderItem = OrderHoldItem::model ()->findByAttributes ( array (
										'order_hold_id' => $orderhold->id,
										'item_id' => $itemdetail->item_id,
								) );
								if ($orderItem == null) {
									$orderItem = new OrderHoldItem ();
								}
							
								$orderItem->item_detail_id = $itemdetail->id;
								$orderItem->item_id = $itemdetail->item_id;
								$orderItem->qty = $onlineitem->qty;
								$orderItem->price = $onlineitem->price;
								$orderItem->sale_rate = $onlineitem->price;
								$orderItem->mrp = $item->mrp;
								$orderItem->total_amt = $onlineitem->total;
								$orderItem->order_hold_id = $orderhold->id;
								$orderItem->create_user_id = Yii::app ()->user->id;
								
								if ($orderItem->save ()) {
								}else{
									///$set = false;
									print_r($orderItem->getErrors());exit;
								}
							}
						}
					}
				}
			}else{
				//$set = false;
									print_r($orderhold->getErrors());exit;
								}
		}else{
			//$set = false;
			print_r($customer->getErrors());exit;
		}
		/* if ($set == true) {
			$model->status = OnlineOrder::STATUS_PROCESSING;
			$model->saveAttributes(array('status'));
			$transaction->commit ();
		} else {
			$transaction->rollback ();
		} */
		/* } catch ( Exception $e ) {
			$transaction->rollback ();
		} */
		}
		$this->redirect ( array (
				'admin'
		) );
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		// $this->processActions($model);
		/*
		 * $this->updateMenuItems($model);
		 * $this->render('view', array(
		 * 'model' => $model
		 * ));
		 */
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'OnlineOrder' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionCreate() {
		$model = new OnlineOrder ();
		
		$this->performAjaxValidation ( $model, 'online-order-form' );
		
		if (isset ( $_POST ['OnlineOrder'] )) {
			$model->setAttributes ( $_POST ['OnlineOrder'] );
			
			if ($model->save ()) {
				if (Yii::app ()->getRequest ()->getIsAjaxRequest ())
					Yii::app ()->end ();
				else
					$this->redirect ( array (
							'view',
							'id' => $model->id 
					) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model 
		) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'OnlineOrder' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'online-order-form' );
		
		if (isset ( $_POST ['OnlineOrder'] )) {
			$model->setAttributes ( $_POST ['OnlineOrder'] );
			
			if ($model->save ()) {
				$this->redirect ( array (
						'view',
						'id' => $model->id 
				) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'update', array (
				'model' => $model 
		) );
	}
	public function actionDelete($id) {
		$model = $this->loadModel ( $id, 'OnlineOrder' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'OnlineOrder' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'OnlineOrder' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	}
	public function actionSearch() {
		$model = new Job ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['OnlineOrder'] )) {
			$model->setAttributes ( $_GET ['OnlineOrder'] );
			$this->renderPartial ( '_list', array (
					'dataProvider' => $model->search (),
					'model' => $model 
			) );
		}
		
		$this->renderPartial ( '_search', array (
				'model' => $model 
		) );
	}
	public function actionAdmin() {
		$model = new OnlineOrder ( 'search' );
		$orders = OnlineOrder::model()->findAllByAttributes(array('type_id'=>OnlineOrder::TYPE_NEW));
		if($orders){
			foreach($orders as $order){
				$order->type_id = OnlineOrder::TYPE_OPEN;
				$order->saveAttributes(array('type_id'));
			}
		}
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		if (isset ( $_POST ['OnlineOrder'] ['start_date'] ) && ($_POST ['OnlineOrder'] ['start_date'] != '') && (isset ( $_POST ['OnlineOrder'] ['end_date'] )) && ($_POST ['OnlineOrder'] ['end_date'] != '')) {
			$_GET ['OnlineOrder'] ['start_date'] = $_POST ['OnlineOrder'] ['start_date'];
			$_GET ['OnlineOrder'] ['end_date'] = $_POST ['OnlineOrder'] ['end_date'];
			Yii::app ()->session ['onlineorder_start_date'] = $_POST ['OnlineOrder'] ['start_date'];
			Yii::app ()->session ['onlineorder_end_date'] = $_POST ['OnlineOrder'] ['end_date'];
		} else {
			if (! isset ( $_GET ['OnlineOrder_page'] )) {
				if (! $this->isExportRequest () && Yii::app ()->session ['onlineorder_start_date'] == '' && Yii::app ()->session ['onlineorder_end_date'] == '') {
					$_GET ['OnlineOrder'] ['start_date'] = date ( 'Y-m-d' );
					$_GET ['OnlineOrder'] ['end_date'] = date ( 'Y-m-d' );
					Yii::app ()->session ['onlineorder_start_date'] = date ( 'Y-m-d' );
					Yii::app ()->session ['onlineorder_end_date'] = date ( 'Y-m-d' );
				}
			}
		}
		if (Yii::app ()->session ['onlineorder_start_date'] != '' && Yii::app ()->session ['onlineorder_end_date'] != '') {
			$_GET ['OnlineOrder'] ['start_date'] = Yii::app ()->session ['onlineorder_start_date'];
			$_GET ['OnlineOrder'] ['end_date'] = Yii::app ()->session ['onlineorder_end_date'];
		}
		if (isset ( $_GET ['OnlineOrder'] ))
			$model->setAttributes ( $_GET ['OnlineOrder'] );
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->search (), array (
							
						'order_id',
						
						array (
								'label' => 'Customer Name',
								'value' => function ($data) {
								return $data->getCustomerName ();
								}
								),
								'telephone',
								'order_date',
								'grand_total',
									
								'payment_method',
								'delivery_method',
								array (
										'label' => 'Status',
										'value' => function ($data) {
										return OnlineOrder::getStatusOptions ( $data->status );
										}
										),
										'street',
			'delivery_boy',
			'delivery_telephone') )	
				;
			}
		$this->render ( 'admin', array (
				'model' => $model 
		) );
	}
	/*
	 * protected function processActions($model = null)
	 * {
	 * parent::processActions($model);
	 * //$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new OnlineOrder ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
			case 'create' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
				}
				break;
			case 'index' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			case 'admin' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					// $this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;
			default :
			case 'view' :
				{
					/*
					 * $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					 * $this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					 * $this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					 * 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					 * $this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					 */
					if($model->status == OnlineOrder::STATUS_PENDING){
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Hold' ),
							'url' => array (
									'hold',
									'id' => $model->id 
							),
							'icon' => 'icon-edit icon-white' 
					);
					}
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
}