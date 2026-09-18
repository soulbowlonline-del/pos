<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Customer;
use app\models\Order;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/CustomerController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class CustomerUiController extends BaseUiController {
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	/* public function actionDuplicate() {
		$query = Customer::find();
		$query->groupBy('email');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['email' => SORT_ASC]);
		$customers = $query->all();
		
	} */
	
	
		public function actionSendEmailCustom() {
	    
	    $query = Customer::find();
	    $query->andWhere('id =5836');
	    $customer = $query->one();
	  
	  
	 
	    $from = 'marketing@soulbowl.in' ;
	    $to  = $customer->email;
	    
	    $subject = 'Test Mail Kriti from POS';
	    
	    $view = $this->renderPartial ( '/mail/customer_email', [
	        'customer'=>$customer,
	        'message'=>'Test Mail Kriti from POS',
	        'image'=>''
	    ], true );
	    
	 $set =    $customer->customermailsend ( $to, $from, $subject, $view,'','','');
	    
	 print_R($set);exit; 
	   
	}
	
	public function actionGetCustomerAddress() {
		$query = Customer::find();
		//$criteria->addCondition('email = ""');
		$customers = $query->all();
		//echo '<pre>';
		//print_r($customers);exit;
		if($customers){
			foreach($customers as $customer){
				$email = $customer->email;
				//$contact_no = '9582229404';
				if($email != ''){
					$ch = curl_init ();
					$query = http_build_query([
							'store_id' => '1','website_id'=>'1','action'=>'address_by_email','email'=>$email
					] );
	
					curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api?$query" );
	
	
					curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	
					$server_output = curl_exec ( $ch );
	
					curl_close ( $ch );
					$response = json_decode ( $server_output, true );
					if($response['success'] == 1 && (isset($response['phone']) && $response['phone'] != null)){
						$query = Customer::find();
						Criteria::compare($query, 'title', $response['city']);
						$city = $query->all();
						$customer->address = $response['address'];
						$customer->contact_no = trim($response['phone']);
						$customer->zip_code = $response['postcode'];
						if($city){
							$customer->city_id = $city->id;
							$customer->state_id = $city->state_id;
						}
						//echo '<pre>';
						//print_r($customer);
						//$customer->saveAttributes(array('address','contact_no','zip_code'));
						 if($customer->save()){
								
						}else{
							//echo '<pre>';
							//print_r($customer);
							//print_r($customer->getErrors());exit;
						} 
					}
				}
			}
		}
	}
	public function actionGetCustomerEmail() {
	$query = Customer::find();
	//$criteria->addCondition('email = ""');
	$customers = $query->all();
	//echo '<pre>';
	//print_r($customers);exit;
	if($customers){
		foreach($customers as $customer){
			$contact_no = $customer->contact_no;
			//$contact_no = '9582229404';
			if($contact_no != ''){
	$ch = curl_init ();
	$query = http_build_query([
			'store_id' => '1','website_id'=>'1','action'=>'email_by_phone','phone'=>$contact_no
	] );
	
	curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api?$query" );
	
	
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	
	$server_output = curl_exec ( $ch );
	
	curl_close ( $ch );
	$response = json_decode ( $server_output, true );
	if($response['success'] == 1){
		
		$customer->email = $response['email'];
		
		if($customer->save()){
			
		}else{
			
			print_r($customer->getErrors());exit;
		}
	}
	}
		}
	}
	}
	public function actionView($id) {
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('customer/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		return $this->render( 'view', [
				'model' => $model 
		] );
	}
	public function actionImport() {
		$model = new Customer();
		if (isset ( $_FILES ['Customer'] )) {
	
			$csvfile = $_FILES['Customer']['tmp_name']['csv_file'];
	
			$handle = fopen ( $csvfile, 'r' );
			if (! $handle)
				die ( 'Cannot open uploaded file.' );
				$row_count = 0;
				$rows = [];
				$valued_rows = [];
				// Read the file as csv
				while ( ($data = fgetcsv ( $handle, 1000, "," )) !== FALSE ) {
					$row_count ++;
					foreach ( $data as $key => $value ) {
							
						$data [$key] = $value;
					}
	
					if (count ( array_flip ( array_flip ( $data ) ) ) != 1) {
						$rows = implode ( ",", $data );
						if (! empty ( $rows )) {
							$valued_rows [] = $rows;
						}
					}
				}
	
				$customer = new Customer();
				$result = $customer->setAllValues ( $valued_rows );
				if ($result == 1) {
					Yii::$app->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::$app->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
		}
		return $this->render('import',['model'=>$model]);
	}
	
	public function actionSendEmail() {
		$model = new Customer ();
		$is_email = true;
		$current_date = date('Y-m-d');
		$criteria2 = new CDbCriteria();
		$criteria2->addCondition('email !=""');
		$countcustomers = Customer::model()->count($criteria2);
		$days = $countcustomers/1900;
		$remainder = $countcustomers%1900;
		if($remainder != 0){
			$days = $days+1;
		}
		$before_date = date('Y-m-d',(strtotime ( "-2 day" , strtotime ( $current_date) ) ));
		
		$criteria3 = new CDbCriteria();
		$criteria3->addCondition('email_date <="'.$before_date.'"');
		$criteria3->addCondition('is_email ='.Customer::Is_Email_sent);
		$oldcustomers = Customer::model()->findAll($criteria3);
		if($oldcustomers){
			foreach($oldcustomers as $oldcustomer){
				$oldcustomer->is_email = Customer::Is_Email_pending;
				$oldcustomer->saveAttributes(['is_email']);
			}
		}
		
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('email !=""');
		$criteria1->addCondition('is_email ='.Customer::Is_Email_pending);
		$emailcustomers = Customer::model()->count($criteria1);
		
		if($emailcustomers == 0){
			$is_email = false;
		}else{
			$criteria1 = new CDbCriteria();
			$criteria1->addCondition('email !=""');
			$criteria1->compare('email_date',$current_date);
			$criteria1->addCondition('is_email ='.Customer::Is_Email_sent);
			$emailcustomers = Customer::model()->count($criteria1);
			
			if($emailcustomers >= 1900){
				$is_email = false;
			}
		}
		
		
		
		
		$this->performAjaxValidation( $model, 'customer-form' );
	
		if (Yii::$app->request->post('Customer') !== null){
		if($is_email == true){
		if (isset ($_POST ['Customer']['subject'] ) && ($_POST ['Customer']['subject'] != '') &&
				isset ($_POST ['Customer']['message'] ) && ($_POST ['Customer']['message'] != '')) {
					$image = false;
					$attachment = '';
					$type = '';
					$name = '';
					if(isset($_FILES['Customer']['name']['attach_file']) && ($_FILES['Customer']['name']['attach_file'] != '')){
				$image = true;
				/* $attachment = $_FILES['Customer']['name']['attach_file'];*/
				$type = $_FILES['Customer']['type']['attach_file'];
				$name = $_FILES['Customer']['name']['attach_file']; 
				$model->saveUploadedFile($model, 'attach_file');
				$attachment = $model->attach_file;
			}
			$criteria = new CDbCriteria();
			$criteria->addCondition('email !=""');
			$criteria->addCondition('is_email ='.Customer::Is_Email_pending);
			$criteria->order = 'id desc';
			$criteria->limit = '1900';
			$customers = Customer::model()->findAll($criteria);
			
			if($customers){
				foreach($customers as $customer){
					$customer->is_email =  Customer::Is_Email_sent;
					$customer->email_date = date('Y-m-d');
					$customer->saveAttributes(['is_email','email_date']);
					$from = 'marketing@soulbowl.in' ;
			
					$to  = $customer->email;
					//$to  = 'soniag@outlinesystemsindia.com';
					$subject = $_POST ['Customer']['subject'];
					
					$view = $this->renderPartial ( '/mail/customer_email', [
							'customer'=>$customer,
							'message'=>$_POST ['Customer']['message'],
							'image'=>$image
					], true );
					
					
					$customer->customermailsend ( $to, $from, $subject, $view,$attachment,$type,$name);
				}
				if($attachment != ''){
					$filename = dirname(__FILE__).'/../../wdir/uploads/'.$attachment;
					if ( file_exists( $filename)) unlink( $filename );
				} 
					
				Yii::$app->user->setFlash ( 'success', 'Email has been sent successfully' );
			}
			
		}else{
			Yii::$app->user->setFlash ( 'error', 'Please fill subject and message' );
		}
		}else{
			Yii::$app->user->setFlash ( 'error', 'Email already sent to the customers' );
		}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'sendemail', [
				'model' => $model 
		] );
	}
	public function actionCreate() {
		$model = new Customer();
		if( !($model->checkPermission ('customer/update')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		$this->performAjaxValidation( $model, 'customer-form' );
	
		if (Yii::$app->request->post('Customer') !== null) {
			$model->load(Yii::$app->request->post());
				
			if ($model->save ()) {
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
		if( !($model->checkPermission ('customer/update')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation( $model, 'customer-form' );
		
		if (Yii::$app->request->post('Customer') !== null) {
			$model->load(Yii::$app->request->post());
			
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
		if( !($model->checkPermission ('customer/delete')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		$query = Order::find();
		$query->andWhere('customer_id ='.$id);
		$orders = $query->count();
		if($orders){
			foreach($orders as $order){
				$order->customer_id = 1;
				$order->saveAttributes(['customer_id']);
			}
		}
		//if (Yii::$app->request->isPost) {
			$this->loadModel($id)->delete ();
			
			if (! Yii::$app->request->isAjax)
				return $this->redirect( [
						'admin' 
				] );
		/* } else
			throw new BadRequestHttpException(Yii::t ( 'app', 'Your request is invalid.' ) ); */
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new ActiveDataProvider(['query' => Customer::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => Customer::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', [
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionSearch() {
		$model = new Customer(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (Yii::$app->request->get('Customer') !== null) {
			$model->load(Yii::$app->request->queryParams);
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
		$model = new Customer(['scenario' => 'search']);
		if( !($model->checkPermission ('customer/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems ( $model );
		$columns = [];
		$user = Yii::$app->user->model;
		if (isset ( $_POST ['Customer']['columns'] )){
			$columns = $_POST ['Customer']['columns'];
		}
		if (Yii::$app->request->get('Customer') !== null)
			$model->load(Yii::$app->request->queryParams);
		$columns = $model->getColumns($columns);
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->search (), $columns
			 );
		}
		return $this->render( 'admin', [
				'model' => $model 
		] );
	}

	public function actionWhatsapplogs() {
		$model = new WhatsappLogs ( 'search' );
		$this->updateMenuItems ( $model );

		$columns = [];
		if (isset ( $_POST ['WhatsappLogs'] ['columns'] )) {
			$columns = $_POST ['WhatsappLogs'] ['columns'];
		}
		if (isset ( $_POST ['WhatsappLogs'] ['start_date'] ) && ($_POST ['WhatsappLogs'] ['start_date'] != '')) {
			Yii::$app->session ['whatsapp_start_date'] = $_POST ['WhatsappLogs'] ['start_date'];
			$model->start_date = $_GET ['WhatsappLogs'] ['start_date'] = $_POST ['WhatsappLogs'] ['start_date'];
		} else {
			if(!isset(Yii::$app->session ['whatsapp_start_date']) || (Yii::$app->session ['whatsapp_start_date'] == '')){
			Yii::$app->session ['whatsapp_start_date'] = date('Y-m-d', strtotime('first day of this month'));
			$model->start_date = $_GET ['WhatsappLogs'] ['start_date'] = date('Y-m-d', strtotime('first day of this month'));
			}else{
				$model->start_date = Yii::$app->session ['whatsapp_start_date'];
			$_GET ['WhatsappLogs'] ['start_date'] = Yii::$app->session ['whatsapp_start_date'];
			}
		}
		if (isset ( $_POST ['WhatsappLogs'] ['end_date'] ) && ($_POST ['WhatsappLogs'] ['end_date'] != '')) {
			Yii::$app->session ['whatsapp_end_date'] = $_POST ['WhatsappLogs'] ['end_date'];
			$model->end_date = $_GET ['WhatsappLogs'] ['end_date'] = $_POST ['WhatsappLogs'] ['end_date'];
		} else {
			if(!isset(Yii::$app->session ['whatsapp_end_date']) || (Yii::$app->session ['whatsapp_end_date'] == '')){
			$model->end_date = Yii::$app->session ['whatsapp_end_date'] = date ( 'Y-m-d' );
			$_GET ['WhatsappLogs'] ['end_date'] = date ( 'Y-m-d' );
			}else{
				$model->end_date = Yii::$app->session ['whatsapp_end_date'];
				$_GET ['WhatsappLogs'] ['end_date'] = Yii::$app->session ['whatsapp_end_date'];
			}
		}

		if (isset ( $_GET ['WhatsappLogs'] )) {
			$model->setAttributes ( $_GET ['WhatsappLogs'] );
		}
		$columns = $model->getWhatapplogsColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->searchExport(), $columns );
		}

		return $this->render( 'whatsapplogs', [
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
			$model = new Customer ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ),
							'url' => Ui::to('customer/view', ['id' => $model->id]),
							'visible'=> $model->checkPermission ("customer/update")=="true",
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
							'visible'=> $model->checkPermission ("customer/create")=="true",
							'icon' => 'icon-wrench icon-white' 
					];
					
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
				/* 	$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					); */
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'SendEmail' ),
							'url' => [
									'sendEmail'
							],
							//'visible' => $model->checkPermission ( "vendor/import" ) == "true",
							'icon' => 'icon-plus icon-white'
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Import' ),
							'url' => [
									'import'
							],
							//'visible' => $model->checkPermission ( "vendor/import" ) == "true",
							'icon' => 'icon-plus icon-white'
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'visible'=> $model->checkPermission ("customer/create")=="true",
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			default :
			case 'view' :
				{
					/* $this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					); */
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'visible'=> $model->checkPermission ("customer/admin")=="true",
							'icon' => 'icon-wrench icon-white' 
					];
				/* 	$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Delete' ),
							'url' => '#',
							'linkOptions' => array (
									'submit' => array (
											'delete',
											'id' => $model->id 
									),
									'confirm' => 'Are you sure you want to delete this item?' 
							),
							'icon' => 'icon-remove icon-white' 
					); */
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'visible'=> $model->checkPermission ("customer/create")=="true",
							'icon' => 'icon-plus icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => Ui::to('customer/update', ['id' => $model->id]),
							'visible'=> $model->checkPermission ("customer/update")=="true",
							'icon' => 'icon-edit icon-white' 
					];
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
}