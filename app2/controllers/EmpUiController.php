<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Emp;
use app\models\User;
use app\models\UserRole;
use app\models\Vendor;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/EmpController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class EmpUiController extends BaseUiController {
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

				$emp = new Emp();
				$result = $emp->setAllValues ( $valued_rows );
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
		if (! ($model->checkPermission ( 'emp/view' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
				
			// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
				
			// $this->processActions($model);
			$this->updateMenuItems ( $model );
			return $this->render( 'view', [
					'model' => $model
			] );
	}
	public function actionCreate() {
		$model = new Emp ();
		$usermodel = new User();
		$model->scenario = 'create';
		if (! ($model->checkPermission ( 'emp/create' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );

			$this->performAjaxValidation( $model, 'emp-form' );

			if (Yii::$app->request->post('Emp') !== null) {
					
				$model->load(Yii::$app->request->post());
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
						$role = UserRole::findOne( [
								'title' => 'Employee'
						] );

						$usermodel->full_name = $_POST ['Emp'] ['name'];

						if(isset($_POST ['Emp'] ['password']) &&  ($_POST ['Emp'] ['password'] != '')){
							$usermodel->password = md5 ( $_POST ['Emp'] ['password'] );
						}
						// password left unset when none is supplied. The else branch
						// here assigned $password, which is never defined in this
						// scope: on PHP 5.6 that wrote an empty password and left the
						// account unusable, and on PHP 8 it raises a warning that
						// Yii 1 turns into a 500 before the user is saved at all.
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
							

							

						if (Yii::$app->request->isAjax)
							Yii::$app->end();
							else
								return $this->redirect( [
										'view',
										'id' => $model->id
								] );
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
			return $this->render( 'create', [
					'model' => $model
			] );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		$usermodel = User::findOne(['emp_id'=>$model->id]);
		if($usermodel){
			$password = $usermodel->password;
			$model->username = $usermodel->username;
		}
		if (! ($model->checkPermission ( 'emp/update' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
				
			// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');

			$this->performAjaxValidation( $model, 'emp-form' );

			if (Yii::$app->request->post('Emp') !== null) {
				$model->load(Yii::$app->request->post());
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
							return $this->redirect( [
									'view',
									'id' => $model->id
							] );
						}else{
							$err = '';
							foreach( $usermodel->getErrors() as $error)
								$err .= implode( ",",$error);
								Yii::$app->user->setFlash ( 'error', $err );
						}
					}

				}
			}
			$this->updateMenuItems ( $model );
			$model->shift_id = $model->getShifts ();
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
		$dataProvider = new ActiveDataProvider(['query' => Emp::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => Emp::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', [
				'dataProvider' => $dataProvider
		] );
	}
	public function actionSearch() {
		$model = new Emp(['scenario' => 'search']);
		$this->updateMenuItems ( $model );

		if (Yii::$app->request->get('Emp') !== null) {
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
		$model = new Emp(['scenario' => 'search']);
		if (! ($model->checkPermission ( 'emp/admin' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			$this->updateMenuItems ( $model );

			if (Yii::$app->request->get('Emp') !== null)
				$model->load(Yii::$app->request->queryParams);
				if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
					$this->exportCSV( $model->search (), [
								
							'code',
							'name',
							'email',
							'contact_no',
							'date_of_birth',
							[
									'label' => 'Gender',
									'value' => function ($data) {
									return Emp::getGenderOptions ( $data->gender_id );
									}
									],
									'permanent_address',
									[
											'label' => 'State',
											'value' => function ($data) {
											return Vendor::getStateName ( $data->state_id );
											}
											],
											[
													'label' => 'City',
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
															'temp_address',
															[
																	'label' => 'Temp State',
																	'value' => function ($data) {
																	return Vendor::getStateName ( $data->temp_state_id );
																	}
																	],
																	[
																			'label' => 'Temp City',
																			'value' => function ($data) {
																			return Vendor::getCityName ( $data->temp_city_id );
																			}
																			],
																			[
																					'label' => 'Temp Country',
																					'value' => function ($data) {
																					return Vendor::getCountryName ( $data->temp_country_id );
																					}
																					],
																					[
																							'label' => 'Designation',
																							'value' => function ($data) {
																							return Vendor::getDesignationName ( $data->designation_id );
																							}
																							],
																							[
																									'label' => 'Shift',
																									'value' => function ($data) {
																									return Vendor::getShiftName ( $data->shift_id );
																									}
																									],
																									'date_of_joining'
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
			$model = new Emp ();

			switch ($this->action->id) {
				case 'update' :
					{
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'View' ),
								'url' => Ui::to('emp/view', ['id' => $model->id]),
								'visible' => $model->checkPermission ( "emp/view" ) == "true",
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
								'visible' => $model->checkPermission ( "emp/admin" ) == "true",
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
								'visible' => $model->checkPermission ( "emp/admin" ) == "true",
								'icon' => 'icon-wrench icon-white'
						];
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'Create' ),
								'url' => [
										'create'
								],
								'visible' => $model->checkPermission ( "emp/create" ) == "true",
								'icon' => 'icon-plus icon-white'
						];
					}
					break;
				case 'admin' :
					{
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'Import' ),
								'url' => [
										'import'
								],
								//'visible' => $model->checkPermission ( "vendor/import" ) == "true",
								'icon' => 'icon-plus icon-white'
						];
						// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'Create' ),
								'url' => [
										'create'
								],
								'visible' => $model->checkPermission ( "emp/create" ) == "true",
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
								'visible' => $model->checkPermission ( "emp/admin" ) == "true",
								'icon' => 'icon-wrench icon-white'
						];
						// $this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
						// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'Create' ),
								'url' => [
										'create'
								],
								'visible' => $model->checkPermission ( "emp/create" ) == "true",
								'icon' => 'icon-plus icon-white'
						];
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'Update' ),
								'url' => Ui::to('emp/update', ['id' => $model->id]),
								'visible' => $model->checkPermission ( "emp/update" ) == "true",
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