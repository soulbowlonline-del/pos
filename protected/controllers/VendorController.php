<?php
class VendorController extends GxController {
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
								'index',
								'view', /* 'download', 'thumbnail' */),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'create',
								'update',
								'search',
								'admin',
								'delete',
								'item',
								'import'
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
				$rows = array ();
				$valued_rows = array ();
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
					Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
		}
		$this->render('import',array('model'=>$model));
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'Vendor' );
		if (! ($model->checkPermission ( 'vendor/view' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
			
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionCreate($id = null, $flash = false) {
		$password = '';
		if ($id != null) {
			$model = $this->loadModel ( $id, 'Vendor' );
			$usermodel = $this->loadModel ( $model->create_user_id, 'User' );
			$password = $usermodel->password;
		} else {
			$model = new Vendor ();
			$usermodel = new User ();
		}
		
		if (! ($model->checkPermission ( 'vendor/create' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation ( $model, 'vendor-form' );
		
		if (isset ( $_POST ['Vendor'] )) {
			$role = UserRole::model ()->findByAttributes ( array (
					'title' => 'Vendor' 
			) );
			
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
			
			$transaction = Yii::app()->db->beginTransaction();
			 try {
			if ($usermodel->save ()) {
				
				// echo "<pre>";print_r($_POST['Vendor']);die;
				
				$model->setAttributes ( $_POST ['Vendor'] );
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
							Yii::app()->interaktApi->createCustomer($data);
						}
					} catch (\Throwable $th) {
						//throw $th;
					}


					//echo "<pre>";print_r($model);die;
					$transaction->commit();
					Yii::app ()->user->setFlash ( 'success', 'Vendor info is saved sucessfully' );
					$this->redirect ( array (
							'create',
							'id' => $model->id,
							'flash' => true 
					) );
				}else{
					$transaction->rollback();
				}
			}else{
				//print_r($usermodel->getErrors());exit;
				$transaction->rollback();
			}
			  } catch (Exception $e) {
				 $transaction->rollback();
				 } 
		}
		$usermodel->password = null;
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model,
				'usermodel' => $usermodel,
				'id' => $model->id,
				'flash' => $flash 
		) );
	}
	public function actionItem($id = null) {
		if ($id != null) {
			$model = $this->loadModel ( $id, 'Vendor' );
		} else {
			$model = new Vendor ();
		}
		$itemvendor = new ItemVendor ();
		// if( !($model->checkPermission ('item/extra'))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		// $this->performAjaxValidation ( $model, 'item-vendor-form' );
		if (isset ( $_POST ['ItemVendor'] )) {
			if ($id != null) {
				// Item::RemoveVendors($model->id);
				$itemvendor->setAttributes ( $_POST ['ItemVendor'] );
				
				$itemvendor->vendor_id = $id;
				if ($itemvendor->save ()) {
					
					Yii::app ()->user->setFlash ( 'success', 'Vendor Product info is saved sucessfully' );
				}
			} else {
				Yii::app ()->user->setFlash ( 'error', 'Please add vendor info first' );
			}
		}
		
		$this->updateMenuItems ( $model );
		$this->render ( 'item', array (
				'model' => $model,
				'id' => $model->id,
				'vendor' => $itemvendor 
		) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'Vendor' );
		$usermodel = new User ();
		if (! ($model->checkPermission ( 'vendor/update' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'vendor-form' );
		
		if (isset ( $_POST ['Vendor'] )) {
			$model->setAttributes ( $_POST ['Vendor'] );
			if (isset ( $_POST ['Vendor']['is_cash'] )) {
				$model->is_cash = $_POST ['Vendor']['is_cash'];
			}
			if ($model->save ()) {
				$this->redirect ( array (
						'view',
						'id' => $model->id 
				) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'update', array (
				'model' => $model,
				'usermodel' => $usermodel 
		) );
	}
	public function actionDelete($id) {
		$model = $this->loadModel ( $id, 'Vendor' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'Vendor' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'Vendor' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	}
	public function actionSearch() {
		$model = new Job ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['Vendor'] )) {
			$model->setAttributes ( $_GET ['Vendor'] );
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
		$model = new Vendor ( 'search' );
		if (! ($model->checkPermission ( 'vendor/admin' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['Vendor'] ))
			$model->setAttributes ( $_GET ['Vendor'] );
		if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV ( $model->search (), array (
					
					'name',
					'tax_no',
					array (
							'label' => 'Outlet',
							'value' => function ($data) {
								return Vendor::getOutletName ( $data->outlet_id );
							} 
					),
					array (
							'label' => 'Is Local Vendor',
							'value' => function ($data) {
								return Vendor::getLocalVendorOptions ( $data->is_local_vendor );
							} 
					),
					array (
							'label' => 'Email',
							'value' => function ($data) {
								return Vendor::getVendorEmail ( $data->id );
							} 
					),
					array (
							'label' => 'Username',
							'value' => function ($data) {
								return Vendor::getVendorUsername ( $data->id );
							} 
					),
					
					'contact_person',
					'person_designation',
					'contact_no',
					'secondary_contact_no',
					'opening_balance',
					'payment_days',
					'primary_address',
					'secondary_address',
					array (
							'label' => 'State',
							'value' => function ($data) {
								return Vendor::getStateName ( $data->state_id );
							} 
					),
					array (
							'label' => 'city_id',
							'value' => function ($data) {
								return Vendor::getCityName ( $data->city_id );
							} 
					),
					array (
							'label' => 'Country',
							'value' => function ($data) {
								return Vendor::getCountryName ( $data->country_id );
							} 
					),
					array (
							'label' => 'status',
							'value' => function ($data) {
								return Vendor::getStatusOptions ( $data->status );
							} 
					),
					
					'remarks' 
			)
			 );
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
			$model = new Vendor ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "vendor/view" ) == "true",
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
							'visible' => $model->checkPermission ( "vendor/admin" ) == "true",
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
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Import' ),
							'url' => array (
									'import'
							),
							//'visible' => $model->checkPermission ( "vendor/import" ) == "true",
							'icon' => 'icon-plus icon-white'
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'visible' => $model->checkPermission ( "vendor/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			default :
			case 'view' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'visible' => $model->checkPermission ( "vendor/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
					// $this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'visible' => $model->checkPermission ( "vendor/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => array (
									'create',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "vendor/update" ) == "true",
							'icon' => 'icon-edit icon-white' 
					);
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
}