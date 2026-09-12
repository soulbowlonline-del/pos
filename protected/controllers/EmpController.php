<?php
class EmpController extends GxController {
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
								/*'index',
								'view',  'download', 'thumbnail' */),
						'users' => array (
								'*'
						)
				),
				array (
						'allow',
						'actions' => array (
								'view',
								'create',
								'update',
								'search',
								'admin',
								'delete',
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
		$model = new Emp();
		if (isset ( $_FILES ['Emp'] )) {

			$csvfile = $_FILES['Emp']['tmp_name']['csv_file'];

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

				$emp = new Emp();
				$result = $emp->setAllValues ( $valued_rows );
				if ($result == 1) {
					Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
		}
		$this->render('import',array('model'=>$model));
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'Emp' );
		if (! ($model->checkPermission ( 'emp/view' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
				
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
				
			// $this->processActions($model);
			$this->updateMenuItems ( $model );
			$this->render ( 'view', array (
					'model' => $model
			) );
	}
	public function actionCreate() {
		$model = new Emp ();
		$usermodel = new User();
		$model->scenario = 'create';
		if (! ($model->checkPermission ( 'emp/create' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );

			$this->performAjaxValidation ( $model, 'emp-form' );

			if (isset ( $_POST ['Emp'] )) {
					
				$model->setAttributes ( $_POST ['Emp'] );
				$user = User::getUserByEmail ( $model->email );
				$user1 = User::getUserByName ( $model->username );
				if (isset ( $_POST ['Emp']['role_id'] ) && ($_POST ['Emp']['role_id'] != '')) {
					$model->role_id = implode(',', $_POST ['Emp']['role_id']);
				}
				if (isset ( $_POST ['Emp']['outlet_id'] )&& ($_POST ['Emp']['outlet_id'] != '')) {
					$model->outlet_id = $_POST ['Emp']['outlet_id'];
				}
				if (! $user && ! $user1) {
					if ($model->save ()) {
						if (isset ( $_POST ['Emp'] ['shift_id'] ) && ($_POST ['Emp']['shift_id'] != '')) {
							$shift_ids = $_POST ['Emp'] ['shift_id'];
							if ($shift_ids) {
								foreach ( $shift_ids as $shift_id ) {
									$empshift = new EmpShift ();
									$empshift->emp_id = $model->id;
									$empshift->shift_id = $shift_id;
									$empshift->save ();
								}
							}
						}
						$role = UserRole::model ()->findByAttributes ( array (
								'title' => 'Employee'
						) );

						$usermodel->full_name = $_POST ['Emp'] ['name'];

						if(isset($_POST ['Emp'] ['password']) &&  ($_POST ['Emp'] ['password'] != '')){
							$usermodel->password = md5 ( $_POST ['Emp'] ['password'] );
						}else{
							$usermodel->password = $password;
						}
						$usermodel->username = $_POST ['Emp'] ['username'];
						$usermodel->email = $_POST ['Emp'] ['email'];
						$usermodel->contact_no = $_POST ['Emp'] ['contact_no'];
						$usermodel->gender = $_POST ['Emp'] ['gender_id'];
						$usermodel->address = $_POST ['Emp'] ['permanent_address'];
							
						$usermodel->country = $_POST ['Emp'] ['country_id'];
						$usermodel->city = $_POST ['Emp'] ['city_id'];
						$usermodel->state = $_POST ['Emp'] ['state_id'];
						$usermodel->date_of_birth =  date ( "Y-m-d",strtotime($_POST ['Emp'] ['date_of_birth']));
						$usermodel->role_id = $role->id;
						$usermodel->state_id = 1;
						$usermodel->emp_id = $model->id;
						$usermodel->last_password_change = date ( "Y-m-d H:i:s" );
						$usermodel->save ();
							

							

						if (Yii::app ()->getRequest ()->getIsAjaxRequest ())
							Yii::app ()->end ();
							else
								$this->redirect ( array (
										'view',
										'id' => $model->id
								) );
					}
						
				}else{
						
					if ($user) {
						$model->addError ( 'email', 'Email already exists' );
					}else{
						$model->addError ( 'username', 'Username already exists' );
					}
						
				}
			}
			$this->updateMenuItems ( $model );
			$this->render ( 'create', array (
					'model' => $model
			) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'Emp' );
		$usermodel = User::model()->findByAttributes(array('emp_id'=>$model->id));
		if($usermodel){
			$password = $usermodel->password;
			$model->username = $usermodel->username;
		}
		if (! ($model->checkPermission ( 'emp/update' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
				
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

			$this->performAjaxValidation ( $model, 'emp-form' );

			if (isset ( $_POST ['Emp'] )) {
				$model->setAttributes ( $_POST ['Emp'] );
				if (isset ( $_POST ['Emp']['role_id'] )) {
					$model->role_id = implode(',', $_POST ['Emp']['role_id']);
				}
				if (isset ( $_POST ['Emp']['outlet_id'] )) {
					$model->outlet_id = $_POST ['Emp']['outlet_id'];
				}
				if ($model->save ()) {
					if (isset ( $_POST ['Emp'] ['shift_id'] )) {
						$model->removeShifts ();
						$shift_ids = $_POST ['Emp'] ['shift_id'];
						if ($shift_ids) {
							foreach ( $shift_ids as $shift_id ) {
								$empshift = new EmpShift ();
								$empshift->emp_id = $model->id;
								$empshift->shift_id = $shift_id;
								$empshift->save ();
							}
						}
					}
						
					if($usermodel){
						$usermodel->full_name = $_POST ['Emp'] ['name'];

						if(isset($_POST ['Emp'] ['password']) &&  ($_POST ['Emp'] ['password'] != '')){
							$usermodel->password = md5 ( $_POST ['Emp'] ['password'] );
						}else{
							$usermodel->password = $password;
						}
						if (isset ( $_POST ['Emp'] ['username'] )) {
							$usermodel->username = $_POST ['Emp'] ['username'];
						}
						if (isset ( $_POST ['Emp'] ['email'] )) {
							$usermodel->email = $_POST ['Emp'] ['email'];
						}
						if (isset ( $_POST ['Emp'] ['contact_no'] )) {
							$usermodel->contact_no = $_POST ['Emp'] ['contact_no'];
						}
						if (isset ( $_POST ['Emp'] ['gender_id'] )) {
							$usermodel->gender = $_POST ['Emp'] ['gender_id'];
						}
						if (isset ( $_POST ['Emp'] ['permanent_address'] )) {
							$usermodel->address = $_POST ['Emp'] ['permanent_address'];
						}
						if (isset ( $_POST ['Emp'] ['country_id'] )) {
							$usermodel->country = $_POST ['Emp'] ['country_id'];
						}
						if (isset ( $_POST ['Emp'] ['city_id'] )) {
							$usermodel->city = $_POST ['Emp'] ['city_id'];
						}
						if (isset ( $_POST ['Emp'] ['state_id'] )) {
							$usermodel->state = $_POST ['Emp'] ['state_id'];
						}
						$usermodel->date_of_birth =  date ( "Y-m-d",strtotime($_POST ['Emp'] ['date_of_birth']));
							
						$usermodel->last_password_change = date ( "Y-m-d H:i:s" );
						if($usermodel->save ()){
							$this->redirect ( array (
									'view',
									'id' => $model->id
							) );
						}else{
							$err = '';
							foreach( $usermodel->getErrors() as $error)
								$err .= implode( ",",$error);
								Yii::app ()->user->setFlash ( 'error', $err );
						}
					}

				}
			}
			$this->updateMenuItems ( $model );
			$model->shift_id = $model->getShifts ();
			$this->render ( 'update', array (
					'model' => $model
			) );
	}
	public function actionDelete($id) {
		$model = $this->loadModel ( $id, 'Emp' );

		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'Emp' )->delete ();
				
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin'
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'Emp' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider
		) );
	}
	public function actionSearch() {
		$model = new Job ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );

		if (isset ( $_GET ['Emp'] )) {
			$model->setAttributes ( $_GET ['Emp'] );
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
		$model = new Emp ( 'search' );
		if (! ($model->checkPermission ( 'emp/admin' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );

			$model->unsetAttributes ();
			$this->updateMenuItems ( $model );

			if (isset ( $_GET ['Emp'] ))
				$model->setAttributes ( $_GET ['Emp'] );
				if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
					$this->exportCSV ( $model->search (), array (
								
							'code',
							'name',
							'email',
							'contact_no',
							'date_of_birth',
							array (
									'label' => 'Gender',
									'value' => function ($data) {
									return Emp::getGenderOptions ( $data->gender_id );
									}
									),
									'permanent_address',
									array (
											'label' => 'State',
											'value' => function ($data) {
											return Vendor::getStateName ( $data->state_id );
											}
											),
											array (
													'label' => 'City',
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
															'temp_address',
															array (
																	'label' => 'Temp State',
																	'value' => function ($data) {
																	return Vendor::getStateName ( $data->temp_state_id );
																	}
																	),
																	array (
																			'label' => 'Temp City',
																			'value' => function ($data) {
																			return Vendor::getCityName ( $data->temp_city_id );
																			}
																			),
																			array (
																					'label' => 'Temp Country',
																					'value' => function ($data) {
																					return Vendor::getCountryName ( $data->temp_country_id );
																					}
																					),
																					array (
																							'label' => 'Designation',
																							'value' => function ($data) {
																							return Vendor::getDesignationName ( $data->designation_id );
																							}
																							),
																							array (
																									'label' => 'Shift',
																									'value' => function ($data) {
																									return Vendor::getShiftName ( $data->shift_id );
																									}
																									),
																									'date_of_joining'
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
			$model = new Emp ();

			switch ($this->action->id) {
				case 'update' :
					{
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'View' ),
								'url' => array (
										'view',
										'id' => $model->id
								),
								'visible' => $model->checkPermission ( "emp/view" ) == "true",
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
								'visible' => $model->checkPermission ( "emp/admin" ) == "true",
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
								'visible' => $model->checkPermission ( "emp/admin" ) == "true",
								'icon' => 'icon-wrench icon-white'
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Create' ),
								'url' => array (
										'create'
								),
								'visible' => $model->checkPermission ( "emp/create" ) == "true",
								'icon' => 'icon-plus icon-white'
						);
					}
					break;
				case 'admin' :
					{
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Import' ),
								'url' => array (
										'import'
								),
								//'visible' => $model->checkPermission ( "vendor/import" ) == "true",
								'icon' => 'icon-plus icon-white'
						);
						// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Create' ),
								'url' => array (
										'create'
								),
								'visible' => $model->checkPermission ( "emp/create" ) == "true",
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
								'visible' => $model->checkPermission ( "emp/admin" ) == "true",
								'icon' => 'icon-wrench icon-white'
						);
						// $this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
						// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Create' ),
								'url' => array (
										'create'
								),
								'visible' => $model->checkPermission ( "emp/create" ) == "true",
								'icon' => 'icon-plus icon-white'
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Update' ),
								'url' => array (
										'update',
										'id' => $model->id
								),
								'visible' => $model->checkPermission ( "emp/update" ) == "true",
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