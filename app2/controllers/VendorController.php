<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Item;
use app\models\UserRole;
use app\models\Vendor;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/VendorController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class VendorController extends BaseUiController {
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionImport() {
		$model = new Vendor();
		if (isset ( $_FILES ['Vendor'] )) {
	
			$csvfile = $_FILES['Vendor']['tmp_name']['csv_file'];
	
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
	
				$vendor = new Vendor();
				$result = $vendor->setAllValues ( $valued_rows );
				if ($result == 1) {
					Yii::$app->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::$app->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
		}
		return $this->render('import',['model'=>$model]);
	}
	public function actionView($id) {
		$model = $this->loadModel($id);
		if (! ($model->checkPermission ( 'vendor/view' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
			
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		return $this->render( 'view', [
				'model' => $model 
		] );
	}
	public function actionCreate($id = null, $flash = false) {
		$password = '';
		if ($id != null) {
			$model = $this->loadModel($id);
			$usermodel = $this->loadModel($model->create_user_id);
			$password = $usermodel->password;
		} else {
			$model = new Vendor ();
			$usermodel = new User ();
		}
		
		if (! ($model->checkPermission ( 'vendor/create' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation( $model, 'vendor-form' );
		
		if (Yii::$app->request->post('Vendor') !== null) {
			$role = UserRole::findOne( [
					'title' => 'Vendor' 
			] );
			
			$usermodel->setAttributes ( $_POST ['User'] );

			if(isset($_POST ['Vendor'] ['name'])){
			$usermodel->full_name = $_POST ['Vendor'] ['name'];
			}
		
			if(isset($_POST ['User'] ['password']) &&  ($_POST ['User'] ['password'] != '')){
				
			$usermodel->password = md5 ( $_POST ['User'] ['password'] );
			}else{
				$usermodel->password = $password;
			}
			
			if(isset($_POST ['Vendor'] ['contact_no'])){
			$usermodel->contact_no = $_POST ['Vendor'] ['contact_no'];
			}
			if(isset($_POST ['Vendor'] ['primary_address'])){
			$usermodel->address = $_POST ['Vendor'] ['primary_address'];
			}
			if(isset($_POST ['Vendor'] ['country_id'])){
			$usermodel->country = $_POST ['Vendor'] ['country_id'];
			}
			if(isset($_POST ['Vendor'] ['city_id'])){
			$usermodel->city = $_POST ['Vendor'] ['city_id'];
			}
			if(isset($_POST ['Vendor'] ['state_id'])){
			$usermodel->state = $_POST ['Vendor'] ['state_id'];
			}
			$usermodel->role_id = $role->id;
			$usermodel->state_id = 1;
			$usermodel->last_password_change = date ( "Y-m-d H:i:s" );
			
			$transaction = Yii::$app->db->beginTransaction();
			 try {
			if ($usermodel->save ()) {
				
				// echo "<pre>";print_r($_POST['Vendor']);die;
				
				$model->load(Yii::$app->request->post());
				if (isset ( $_POST ['Vendor']['is_cash'] )) {
					$model->is_cash = $_POST ['Vendor']['is_cash'];
				}
				if(isset($_POST ['Vendor']['is_advance_payment'])){
					$model->is_advance_payment = $_POST ['Vendor']['is_advance_payment'];
				}
				if(isset($_POST ['Vendor']['bank_name'])){
					$model->bank_name = $_POST ['Vendor']['bank_name'];
				}
				if(isset($_POST ['Vendor']['acc_no'])){
					$model->acc_no = $_POST ['Vendor']['acc_no'];
				}
				$model->outlet_id = implode ( ",", $_POST ['Vendor'] ['outlet_id'] );
				$model->create_user_id = $usermodel->id;

				if(isset($_POST ['Vendor'] ['whatsapp_no']) && $_POST ['Vendor'] ['whatsapp_no'] != ''){
					$model->whatsapp_no = preg_replace("/[^0-9]/", "", $_POST ['Vendor']['whatsapp_no']);
				}
				
				if ($model->save ()) {

					try {
						if ($model->whatsapp_no != '') {
							$data = [
								"phoneNumber" => $model->whatsapp_no,
								"countryCode" => "+91",
								"traits" => [
									"name" => $model->name,
									"email" => $model->contact_email
								],
								"tags" => ["Added By POS"]
							];
							Yii::$app->interaktApi->createCustomer($data);
						}
					} catch (\Throwable $th) {
						//throw $th;
					}


					//echo "<pre>";print_r($model);die;
					$transaction->commit();
					Yii::$app->user->setFlash ( 'success', 'Vendor info is saved sucessfully' );
					return $this->redirect( [
							'create',
							'id' => $model->id,
							'flash' => true 
					] );
				}else{
					$transaction->rollback();
				}
			}else{
				//print_r($usermodel->getErrors());exit;
				$transaction->rollback();
			}
			  } catch (\Exception $e) {
				 $transaction->rollback();
				 } 
		}
		$usermodel->password = null;
		$this->updateMenuItems ( $model );
		return $this->render( 'create', [
				'model' => $model,
				'usermodel' => $usermodel,
				'id' => $model->id,
				'flash' => $flash 
		] );
	}
	public function actionItem($id = null) {
		if ($id != null) {
			$model = $this->loadModel($id);
		} else {
			$model = new Vendor ();
		}
		$itemvendor = new ItemVendor ();
		// if( !($model->checkPermission ('item/extra'))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// $this->performAjaxValidation( $model, 'item-vendor-form' );
		if (isset ( $_POST ['ItemVendor'] )) {
			if ($id != null) {
				// Item::RemoveVendors($model->id);
				$itemvendor->setAttributes ( $_POST ['ItemVendor'] );
				
				$itemvendor->vendor_id = $id;
				if ($itemvendor->save ()) {
					
					Yii::$app->user->setFlash ( 'success', 'Vendor Product info is saved sucessfully' );
				}
			} else {
				Yii::$app->user->setFlash ( 'error', 'Please add vendor info first' );
			}
		}
		
		$this->updateMenuItems ( $model );
		return $this->render( 'item', [
				'model' => $model,
				'id' => $model->id,
				'vendor' => $itemvendor 
		] );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		$usermodel = new User ();
		if (! ($model->checkPermission ( 'vendor/update' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation( $model, 'vendor-form' );
		
		if (Yii::$app->request->post('Vendor') !== null) {
			$model->load(Yii::$app->request->post());
			if (isset ( $_POST ['Vendor']['is_cash'] )) {
				$model->is_cash = $_POST ['Vendor']['is_cash'];
			}
			if ($model->save ()) {
				return $this->redirect( [
						'view',
						'id' => $model->id 
				] );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'update', [
				'model' => $model,
				'usermodel' => $usermodel 
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
		$dataProvider = new ActiveDataProvider(['query' => Vendor::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            'sort' => ['defaultOrder' => Vendor::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', [
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionSearch() {
		$model = new Vendor(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (Yii::$app->request->get('Vendor') !== null) {
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
		$model = new Vendor(['scenario' => 'search']);
		if (! ($model->checkPermission ( 'vendor/admin' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		$this->updateMenuItems ( $model );
		
		if (Yii::$app->request->get('Vendor') !== null)
			$model->load(Yii::$app->request->queryParams);
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->search (), [
					
					'name',
					'tax_no',
					[
							'label' => 'Outlet',
							'value' => function ($data) {
								return Vendor::getOutletName ( $data->outlet_id );
							} 
					],
					[
							'label' => 'Is Local Vendor',
							'value' => function ($data) {
								return Vendor::getLocalVendorOptions ( $data->is_local_vendor );
							} 
					],
					[
							'label' => 'Email',
							'value' => function ($data) {
								return Vendor::getVendorEmail ( $data->id );
							} 
					],
					[
							'label' => 'Username',
							'value' => function ($data) {
								return Vendor::getVendorUsername ( $data->id );
							} 
					],
					
					'contact_person',
					'person_designation',
					'contact_no',
					'secondary_contact_no',
					'opening_balance',
					'payment_days',
					'primary_address',
					'secondary_address',
					[
							'label' => 'State',
							'value' => function ($data) {
								return Vendor::getStateName ( $data->state_id );
							} 
					],
					[
							'label' => 'city_id',
							'value' => function ($data) {
								return Vendor::getCityName ( $data->city_id );
							} 
					],
					[
							'label' => 'Country',
							'value' => function ($data) {
								return Vendor::getCountryName ( $data->country_id );
							} 
					],
					[
							'label' => 'status',
							'value' => function ($data) {
								return Vendor::getStatusOptions ( $data->status );
							} 
					],
					
					'remarks' 
			]
			 );
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
			$model = new Vendor ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ),
							'url' => Ui::to('vendor/view', ['id' => $model->id]),
							'visible' => $model->checkPermission ( "vendor/view" ) == "true",
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
							'visible' => $model->checkPermission ( "vendor/admin" ) == "true",
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
							'visible' => $model->checkPermission ( "vendor/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			default :
			case 'view' :
				{
					// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'visible' => $model->checkPermission ( "vendor/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					];
					// $this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'visible' => $model->checkPermission ( "vendor/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => Ui::to('vendor/create', ['id' => $model->id]),
							'visible' => $model->checkPermission ( "vendor/update" ) == "true",
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