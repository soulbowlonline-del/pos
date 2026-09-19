<?php
namespace app\controllers;

use app\components\Criteria;
use app\components\Ui;
use app\models\City;
use app\models\Customer;
use app\models\Item;
use app\models\ItemDetail;
use app\models\OnlineOrder;
use app\models\OnlineOrderItem;
use app\models\Order;
use app\models\OrderHold;
use app\models\OrderHoldItem;
use app\models\Outlet;
use app\models\PaymentMode;
use app\models\State;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/OnlineOrderController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class OnlineOrderController extends BaseUiController {
	public function actionCountOrders(){
		$count = OnlineOrder::model()->countByAttributes(['type_id'=>OnlineOrder::TYPE_NEW]);
		 echo $count;
	}
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionPdf($id)
	{
		$order = OnlineOrder::findOne($id);
		//$id  =2;
		$set = true;
		
	
			
		# mPDF
		$mPDF1 = Yii::$app->ePdf->mpdf();
	
		# You can easily override default constructor's params
		$mPDF1 = Yii::$app->ePdf->mpdf('', 'A4');
	
		# render (full page)
		//$mPDF1->WriteHTML($this->render('index', array(), true));
	
		# Load a stylesheet
		//$stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
		//$mPDF1->WriteHTML($stylesheet, 1);
	
		# renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML($this->renderPartial('_pdf',['order'=>$order], true));
	
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
	
	
	
	}
	public function actionApi() {
		
		$key = "KjxyUSjh";
		$salt = "74BhEL1a28";
		$r = ['key' => $key ,'salt' => $salt];
		$qs= http_build_query($r);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $qs );
		curl_setopt($ch, CURLOPT_URL, "https://www.payumoney.com/payment/smsInvoice?
				customerName=Sonia&customerobileNumber=8847474669&amount=10
				&description=test&referenceId=2222");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['authorization:wQ3dHRked1Nqc2bywGrrMsNlyPYdXtKrDC+NAxekEdc=']);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$out = curl_exec($ch);
		
		if (curl_errno($ch)) {
			$sad = curl_error($ch);
			throw new \Exception($sad);
		}
		curl_close($ch);
		echo 'hello';
		print_r($out);
		exit;
	
	}
	public function actionOnline() {
		$order_id = '100008065';
		$query = OnlineOrder::find();
		$query->orderBy(['order_id' => SORT_DESC]);
		$order = $query->one();
		if($order){
			$order_id = $order->order_id;
		}
		
		$ch = curl_init ();
	
		//curl_setopt ( $ch, CURLOPT_URL, "https://soulbowl.in/shell/apis/recentorders_demo.php" );
		curl_setopt ( $ch, CURLOPT_URL, "https://sect4.soulbowl.in/pos/getRecentOrderSoul.php" );
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		
		curl_setopt ( $ch, CURLOPT_POST, 1 );
	
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( [
				'skey' => '5a88131bac8400eff9ca87e9707b3c4a','orderid'=>$order_id
		] ) );
	
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
					
					$transaction = Yii::$app->db->beginTransaction ();
					try {
	
						$onlineorder = OnlineOrder::findOne( [
								'order_id' => $result ['order_id']
						] );
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
							$query1 = Customer::find();
							Criteria::compare($query1, "contact_no", $onlineorder->mobile);
							$query1->orderBy(['id' => SORT_ASC]);
							$customer = $query1->one();
								
							$query1_2 = City::find();
							Criteria::compare($query1_2, "title", $onlineorder->city);
							$query1_2->orderBy(['id' => SORT_ASC]);
							$city = $query1_2->one();
								
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
							
								$state = State::findOne( $city->state_id );
								if ($state) {
									$customer->country_id = $state->country_id;
								}
							
							}
								
							//$transaction = Yii::$app->db->beginTransaction ();
							//try {
							$customer->save();
							
							
							$items = $result ['items'];
							foreach ( $items as $item ) {
								$orderitem = OnlineOrderItem::findOne( [
										'order_id' => $onlineorder->id,
										'product_code'=>$item ['sku']
								] );
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
									Yii::warning( var_export($errors1, true), '$errors1');
								}
							}
						} else {
							$set = false;
							$errors = $onlineorder->getErrors ();
							Yii::warning( var_export($errors, true), '$errors');
						}
							
						if ($set == true) {
							$transaction->commit ();
						} else {
						 $transaction->rollback ();
						}
					} catch ( \Exception $e ) {
						$transaction->rollback ();
					}
	
				}
				if($count > 0){
				$query1_3 = User::find();
				$query1_3->andWhere(['not in', "role_id", ['6','10']]);
				$query1_3->orderBy(['id' => SORT_ASC]);
				$query1_3->andWhere('device_token IS NOT NULL');
				$users = $query1_3->all();
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
		$model = $this->loadModel($id);
		$query1 = PaymentMode::find();
        $query1->orderBy(['id' => SORT_DESC]);
		Criteria::compare($query1, "title", $model->payment_method);
		$paymentmode = $query1->one();
		if($paymentmode == null){
		$query1_2 = PaymentMode::find();
		$query1_2->andWhere("type_id = 0");
		$query1_2->orderBy(['id' => SORT_ASC]);
		$paymentmode = $query1_2->one();
		}
		$query2 = PaymentMode::find();
        $query2->orderBy(['id' => SORT_DESC]);
		Criteria::compare($query2, "title", $model->delivery_method);
		$deliverymode = $query2->one();
		if($deliverymode == null){
		$query2_2 = PaymentMode::find();
		$query2_2->orderBy(['id' => SORT_ASC]);
		$query2_2->andWhere("type_id = 1");
		$deliverymode = $query2_2->one();
		}
		$query3 = Outlet::find();
		$query3->orderBy(['id' => SORT_ASC]);
		$outlet = $query3->one();
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
			$query1_3 = Customer::find();
			Criteria::compare($query1_3, "contact_no", $model->telephone);
			$query1_3->orderBy(['id' => SORT_ASC]);
			$customer = $query1_3->one();
			
			$query1_4 = City::find();
			Criteria::compare($query1_4, "title", $model->city);
			$query1_4->orderBy(['id' => SORT_ASC]);
			$city = $query1_4->one();
			
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
				
				$state = State::findOne( $city->state_id );
				if ($state) {
					$customer->country_id = $state->country_id;
				}
				
			}
			
			//$transaction = Yii::$app->db->beginTransaction ();
			//try {
			if($customer->save()){
				
				$orderhold = OrderHold::findOne( [
						'online_order_id' => $id
				] );
				if ($orderhold == null) {
					$orderhold = new OrderHold ();
				}
			
			$query = Order::find();
			$query->orderBy(['bill_no' => SORT_DESC]);
			if ($start_date != '' && $end_date != '') {
				$query->andWhere(['between', 'date(create_time)', $start_date, $end_date]);
			}
			$latestorder = $query->one();
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
			$orderhold->create_user_id = Yii::$app->user->id;
			
			if ($orderhold->save ()) {
				$onlineitems = OnlineOrderItem::findAll( [
						'order_id' => $id 
				] );
				
				if ($onlineitems) {
					foreach ( $onlineitems as $onlineitem ) {
						$query5 = Item::find();
						Criteria::compare($query5, "item_code", $onlineitem->product_code);
						$item = $query5->one();
						if ($item) {
							$query4 = ItemDetail::find();
							Criteria::compare($query4, "item_id", $item->id);
							Criteria::compare($query4, "bar_code", $onlineitem->barcode);
							$itemdetail = $query4->one();
							if($itemdetail == null){
								$barcode = $item->getItemBarcodes();
								$query4_2 = ItemDetail::find();
								Criteria::compare($query4_2, "item_id", $item->id);
								Criteria::compare($query4_2, "bar_code", $barcode);
								$itemdetail = $query4_2->one();
							}
							if ($itemdetail) {
								$orderItem = OrderHoldItem::findOne( [
										'order_hold_id' => $orderhold->id,
										'item_id' => $itemdetail->item_id,
								] );
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
								$orderItem->create_user_id = Yii::$app->user->id;
								
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
		/* } catch ( \Exception $e ) {
			$transaction->rollback ();
		} */
		}
		return $this->redirect( [
				'admin'
		] );
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// $this->processActions($model);
		/*
		 * $this->updateMenuItems($model);
		 * $this->render('view', array(
		 * 'model' => $model
		 * ));
		 */
	}
	public function actionView($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		return $this->render( 'view', [
				'model' => $model 
		] );
	}
	public function actionCreate() {
		$model = new OnlineOrder ();
		
		$this->performAjaxValidation( $model, 'online-order-form' );
		
		if (isset ( $_POST ['OnlineOrder'] )) {
			$model->load($_POST, 'OnlineOrder');
			
			if ($model->save ()) {
				if (Yii::$app->request->isAjax)
					Yii::$app->end();
				else
					return $this->redirect( [
							'view',
							'id' => $model->id 
					] );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'create', [
				'model' => $model 
		] );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation( $model, 'online-order-form' );
		
		if (isset ( $_POST ['OnlineOrder'] )) {
			$model->load($_POST, 'OnlineOrder');
			
			if ($model->save ()) {
				return $this->redirect( [
						'view',
						'id' => $model->id 
				] );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'update', [
				'model' => $model 
		] );
	}
	public function actionDelete($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		if (Yii::$app->request->isPost) {
			$this->loadModel($id)->delete ();
			
			if (! Yii::$app->request->isAjax)
				return $this->redirect( [
						'admin' 
				] );
		} else
			throw new BadRequestHttpException(Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new ActiveDataProvider(['query' => OnlineOrder::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => OnlineOrder::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', [
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionSearch() {
		$model = new OnlineOrder(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['OnlineOrder'] )) {
			$model->load($_GET, 'OnlineOrder');
			return $this->renderPartial( '_list', [
					'dataProvider' => $model->search (),
					'model' => $model 
			] );
		}
		
		return $this->renderPartial( '_search', [
				'model' => $model 
		] );
	}
	public function actionAdmin() {
		$model = new OnlineOrder(['scenario' => 'search']);
		$orders = OnlineOrder::findAll(['type_id'=>OnlineOrder::TYPE_NEW]);
		if($orders){
			foreach($orders as $order){
				$order->type_id = OnlineOrder::TYPE_OPEN;
				$order->saveAttributes(['type_id']);
			}
		}
		$this->updateMenuItems ( $model );
		if (isset ( $_POST ['OnlineOrder'] ['start_date'] ) && ($_POST ['OnlineOrder'] ['start_date'] != '') && (isset ( $_POST ['OnlineOrder'] ['end_date'] )) && ($_POST ['OnlineOrder'] ['end_date'] != '')) {
			$_GET ['OnlineOrder'] ['start_date'] = $_POST ['OnlineOrder'] ['start_date'];
			$_GET ['OnlineOrder'] ['end_date'] = $_POST ['OnlineOrder'] ['end_date'];
			Yii::$app->session ['onlineorder_start_date'] = $_POST ['OnlineOrder'] ['start_date'];
			Yii::$app->session ['onlineorder_end_date'] = $_POST ['OnlineOrder'] ['end_date'];
		} else {
			if (! isset ( $_GET ['OnlineOrder_page'] )) {
				if (! $this->isExportRequest() && Yii::$app->session ['onlineorder_start_date'] == '' && Yii::$app->session ['onlineorder_end_date'] == '') {
					$_GET ['OnlineOrder'] ['start_date'] = date ( 'Y-m-d' );
					$_GET ['OnlineOrder'] ['end_date'] = date ( 'Y-m-d' );
					Yii::$app->session ['onlineorder_start_date'] = date ( 'Y-m-d' );
					Yii::$app->session ['onlineorder_end_date'] = date ( 'Y-m-d' );
				}
			}
		}
		if (Yii::$app->session ['onlineorder_start_date'] != '' && Yii::$app->session ['onlineorder_end_date'] != '') {
			$_GET ['OnlineOrder'] ['start_date'] = Yii::$app->session ['onlineorder_start_date'];
			$_GET ['OnlineOrder'] ['end_date'] = Yii::$app->session ['onlineorder_end_date'];
		}
		if (isset ( $_GET ['OnlineOrder'] ))
			$model->load($_GET, 'OnlineOrder');
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search (), [
							
						'order_id',
						
						[
								'label' => 'Customer Name',
								'value' => function ($data) {
								return $data->getCustomerName ();
								}
								],
								'telephone',
								'order_date',
								'grand_total',
									
								'payment_method',
								'delivery_method',
								[
										'label' => 'Status',
										'value' => function ($data) {
										return OnlineOrder::getStatusOptions ( $data->status );
										}
										],
										'street',
			'delivery_boy',
			'delivery_telephone'] )	
				;
			}
		return $this->render( 'admin', [
				'model' => $model 
		] );
	}
	/*
	 * protected function processActions($model = null)
	 * {
	 * parent::processActions($model);
	 * //$this->actions [] = array('label'=>'Add Skill', 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new OnlineOrder ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ),
							'url' => Ui::to('onlineOrder/view', ['id' => $model->id]),
							'icon' => 'icon-plus icon-white' 
					];
				}
			case 'create' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
				}
				break;
			case 'index' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			case 'admin' :
				{
					// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					// $this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;
			default :
			case 'view' :
				{
					/*
					 * $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					 * $this->menu[] = array('label'=>'Manage', 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					 * $this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					 * 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					 * $this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					 */
					if($model->status == OnlineOrder::STATUS_PENDING){
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Hold' ),
							'url' => Ui::to('onlineOrder/hold', ['id' => $model->id]),
							'icon' => 'icon-edit icon-white' 
					];
					}
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}

	/**
	 * Actions Yii 1's accessRules() refuses to a signed-in user.
	 *
	 * Read from accessRules() when this file was generated, not enforced by
	 * duplicating the rules: only actions refused outright are listed, and a
	 * rule decided by a role or an expression is left out.
	 */
	public function deniedActions()
	{
		return ['hold', 'create', 'delete', 'index', 'search'];
	}
}
