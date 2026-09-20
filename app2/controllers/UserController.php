<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Item;
use app\models\ItemVendor;
use app\models\LoginForm;
use app\models\Mrs;
use app\models\MrsDetail;
use app\models\PurchaseBill;
use app\models\PurchaseOrder;
use app\models\Question;
use app\models\Session;
use app\models\Setting;
use app\models\User;
use app\models\UserPassword;
use app\models\UserQuestion;
use app\models\Vendor;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/UserController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class UserController extends BaseUiController {
	public $defaultAction = 'login';
	public $loginForm = null;
	public $caseSensitiveUsers = true;
	public $returnAdminUrl = null;
	// LoginType :
	const LOGIN_BY_USERNAME = 1;
	const LOGIN_BY_EMAIL = 2;
	const LOGIN_BY_LDAP = 32;
	const LOGIN_BY_CONTACT = 4;
	const STATE_ACTIVATE = 1;
	const STATE_DEACTIVATE = 0;
	
	// Allow login only by username by default.
	public $loginType = 1;
	public function actionSetSession(){
		if(isset($_POST['User']['session_id'])){
			Yii::$app->session['select_session_id'] = $_POST['User']['session_id'];
		}
		if(isset($_POST['User']['api_key']) && ($_POST['User']['api_key'] != '')){
			$id = 1;
			$setting = Setting::findOne($id);
			$setting->api_key = $_POST['User']['api_key'];
			$setting->saveAttributes(['api_key']);
		}
		if(isset($_POST['User']['ivr_username']) && ($_POST['User']['ivr_username'] != '')){
			$id = 1;
			$setting = Setting::findOne($id);
			$setting->ivr_username = $_POST['User']['ivr_username'];
			$setting->saveAttributes(['ivr_username']);
		}
		return $this->redirect(['dashboard']);
	}
	public function actionTerms() {
		return $this->render( 'terms' );
	}
	public function actionQuestion() {
		$model = new UserQuestion ();
		$set = true;
		if (isset ( $_POST ['UserQuestion'] )) {
			$question_ids = $_POST ['UserQuestion'] ['question_id'];
			$answer = $_POST ['UserQuestion'] ['answer'];
			if (! empty ( $question_ids ) && ! empty ( $answer ) && count ( $answer ) == 2 && count ( $question_ids ) == 2) {
				
				$result = array_combine ( $question_ids, $answer );
				if ($result) {
					foreach ( $result as $res => $val ) {
						if ($res != '' && $val == '') {
							$set = false;
						}
					}
					foreach ( $result as $res => $val ) {
						if ($set == true) {
							$model = UserQuestion::findOne( [
									'question_id' => $res,
									'create_user_id' => Yii::$app->user->id 
							] );
							if ($model == null)
								$model = new UserQuestion ();
							$model->question_id = $res;
							$model->answer = $val;
							$model->save ();
						}
					}
				}
			} else {
				Yii::$app->user->setFlash ( 'error', 'Please add answer to all questions' );
			}
			if ($set == false) {
				Yii::$app->user->setFlash ( 'error', 'Please add answer to all questions' );
			} else {
				Yii::$app->user->setFlash ( 'success', 'You have successfully submitted the answers' );
			}
		}
		
		return $this->render( 'question', [
				'model' => $model 
		] );
	}
	public function actionAjaxQuestion() {
		$option = '';
		$alreadypermissions = [];
		if (isset ( $_POST ['selected'] )) {
			
			$query = Question::find();
			$query->andWhere('id !=' . $_POST ['selected']);
			$query->orderBy(['title' => SORT_ASC]);
			$questions = $query->all();
			// $option .= '<option value="" id="ckbCheckAll">-Select-</option>';
			if ($questions) {
				$option .= "<select name='UserQuestion[question_id][]' id='UserQuestion_question_id2' class='form-control'>";
				
				foreach ( $questions as $question ) {
					$option .= "<option value=$question->id>$question->title</option>";
				}
				$option .= "</select>";
			} else {
				// $option .= '<option value="">-Select-</option>';
			}
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		echo $option;
	}
	public function actionAjaxuserQuestion($id) {
		$option = '';
		$alreadypermissions = [];
		if (isset ( $_POST ['selected'] )) {
				
			$query = Question::find();
			$query->andWhere('id !=' . $_POST ['selected']);
			if($id != null){
				$userques = UserQuestion::findAll(['create_user_id'=>$id]);
				if($userques){
					foreach($userques as $userque){
						$ids[] = $userque->question_id;
					}
				}
				if(!empty($ids))
					$query->andWhere(['id' => $ids]);
			}
				
			$query->orderBy(['title' => SORT_ASC]);
			$questions = $query->all();
			// $option .= '<option value="" id="ckbCheckAll">-Select-</option>';
			if ($questions) {
				$option .= "<select name='UserQuestion[question_id][]' id='UserQuestion_question_id2' class='form-control'>";
	
				foreach ( $questions as $question ) {
					$option .= "<option value=$question->id>$question->title</option>";
				}
				$option .= "</select>";
			} else {
				// $option .= '<option value="">-Select-</option>';
			}
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		echo $option;
	}
	
	public function actionInactivate() {
		if (isset ( $_POST ['Product'] )) {
			
			$id = $_POST ['Product'] ['merchant_id'];
			$model = $this->loadModel($id);
			
			// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
			
			$model->state_id = User::STATUS_INACTIVE;
			
			if ($model->save ()) {
				
				$products = Product::findAll( [
						'create_user_id' => $id,
						'state_id' => Product::STATE_APPROVE 
				] );
				
				if ($products) {
					foreach ( $products as $product ) {
						$product->state_id = Product::STATE_UNAPPROVE;
						$product->saveAttributes ( [
								'state_id' 
						] );
					}
				}
			}
		}
		return $this->redirect( [
				'/user/dashboard' 
		] );
	}
	public function actionToggle($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		$state_id = $model->state_id;
		if ($state_id == User::STATUS_INACTIVE) {
			$model->state_id = User::STATUS_ACTIVE;
		} else {
			$model->state_id = User::STATUS_INACTIVE;
		}
		
		if ($model->saveAttributes ( [
				'state_id' 
		] ));
		
		return $this->redirect( [
				'/user/admin'
				
		] );
	}
	public function actionDash() {
		$user = Yii::$app->user->model;
		$query = Item::find();
		$itemvendor_ids = [];
		$vendor = Vendor::findOne(['create_user_id'=>$user->id]);
		if($vendor){
			$itemvendors = ItemVendor::findAll(['vendor_id'=>$vendor->id]);
			if($itemvendors){
		
				foreach($itemvendors as $itemvendor){
					$itemvendor_ids[] = $itemvendor->item_detail_id;
				}
			}
		}
		$query->andWhere(['id' => $itemvendor_ids]);
	
		$items = $query->count();
	
		$vendor = Vendor::findOne(['create_user_id'=>$user->id]);
		$mrss =  Mrs::find()->where([
				'vendor_id' => $vendor->id
		])->count();
		$pos =  PurchaseOrder::find()->where([
				'vendor_id' => $vendor->id
		])->count();
		$grns =  PurchaseBill::find()->where([
				'vendor_id' => $vendor->id
		])->count();
		
		$model = new User(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		// $_GET['Product']['state_id'] = Product::STATE_UNAPPROVE;
		if (isset ( $_GET ['User'] ))
			$model->load($_GET, 'User');
		
			return $this->render( 'dash', [
					'items' => $items,
					'mrss' => $mrss,
					'pos' => $pos,
					'grns' => $grns,
					'model' => $model
			] );
	}
	public function actionDashboard() {
		ini_set ( 'max_execution_time', 30000 );
		$user = Yii::$app->user->model;
		$role_id = $user->role_id;
		if($role_id == 6){
		
			return $this->redirect( [
					'/user/dash'
			
			] );
		}
		
	$items = Item::find()->count();
		// $items = 1;
		$vendors = Vendor::find()->count();
		$orders = 0;
		$pendingorders = 0;
		
		$model = new User(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		// $_GET['Product']['state_id'] = Product::STATE_UNAPPROVE;
		if (isset ( $_GET ['User'] ))
			$model->load($_GET, 'User');
		
		return $this->render( 'dashboard', [
				'items' => $items,
				'vendors' => $vendors,
				'orders' => $orders,
				'pendingorders' => $pendingorders,
				'model' => $model 
		] );
	}
	
	
	public function actionAjax($type, $view, $id) {
		if (isset ( $id )) {
			$model = $this->loadModel($id);
			if ($model->hasAttribute ( $type )) {
				// $objects is never assigned in this action, so this call has
				// always failed - on PHP 8 with a warning-turned-500, and before
				// that with an empty provider. An empty array keeps the previous
				// effective behaviour without the error; what it should actually
				// provide needs someone who knows the intent of this screen.
				$objects = [];
				$dataProvider = new \yii\data\ArrayDataProvider(['allModels' => $objects]);
				return $this->renderPartial( '/' . $view . '/_list', [
						'dataProvider' => $dataProvider 
				] );
			}
		}
		Yii::$app->end();
	}
	public function actionView($id = null) {
		$user = Yii::$app->user->model;
		$role_id = $user->role_id;
		if($id==null || $role_id != 1){
		
			$id = Yii::$app->user->id;
		}
		
		$model = $this->loadModel($id);
		if (! ($model->checkPermission ( 'user/view' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
			
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		return $this->render( 'view', [
				'model' => $model 
		] );
	}
	
	// ---Passanger and Driver register
	public function actionCreate($role_id = null) {
		$model = new User ();
		if (! ($model->checkPermission ( 'user/create' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$arr = [];
		$model->scenario = 'create';
		if (isset ( $_POST ['User'] ['email'] )) {
			
			$user = User::getUserByEmail ( $model->email );
			$user1 = User::getUserByEmail ( $model->contact_no );
			$valid = true;
			if (! $user && ! $user1) {
				
				if (isset ( $_POST ['User'] )) {
					if ($role_id != null) {
						if ($role_id == User::ROLE_MERCHANT) {
							if (isset ( $_POST ['User'] ['store_id'] )) {
								$valid = true;
							} else {
								$valid = false;
							}
						}
					}
					if ($valid) {
						$model->load($_POST, 'User');
						$model->state_id = 1; // activates account set 1
						if ($role_id != null)
							$model->role_id = $role_id;
						if ($model->validate ( true )) {
							if ($model->setPassword ( $model->password, $model->password ) && $model->save ()) {
								if (isset ( $_POST ['User'] ['store_id'] )) {
									$store_ids = $_POST ['User'] ['store_id'];
									if ($store_ids != null) {
										foreach ( $store_ids as $store_id ) {
											$merchantstore = new MerchantStore ();
											$merchantstore->store_id = $store_id;
											$merchantstore->merchant_id = $model->id;
											$merchantstore->save ();
										}
									}
								}
								
								return $this->redirect( [
										'admin',
										'role_id' => $role_id 
								] );
								
								// $model->sendPassword();
							} else {
								$err = '';
								foreach ( $model->getErrors () as $error )
									$err .= implode ( ".", $error );
								$arr ['error'] = $err;
							}
						} else {
							$err = '';
							foreach ( $model->getErrors () as $error )
								$err .= implode ( ".", $error );
							$arr ['error'] = $err;
						}
					} else {
						$model->addError ( 'category_id', 'Please select category' );
					}
				} else {
					$model->addError ( 'contact_no', 'You have already registed with this number.Please try to recove it' );
				}
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'create', [
				'model' => $model,
				'role_id' => $role_id 
		] );
	}
	public function actionUpdate($id = null) {
		/*
		 * if( !(User::isLoggedIn ( $id)))
		 * {
		 * throw new ForbiddenHttpException('You are not allowed to access this page.');
		 *
		 * }
		 */
		 $user = Yii::$app->user->model;
		 $role_id = $user->role_id;
		 if ($id == null || $role_id != 1){
		 		
		 	$id = Yii::$app->user->id;
		 }
		
		$model = $this->loadModel($id);
		$model->scenario = 'update';
		if (! ($model->checkPermission ( 'user/update' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// $this->performAjaxValidation($model, 'user-form');
		$role_id = $model->role_id;
		if (isset ( $_POST ['User'] )) {
			$model->load($_POST, 'User');
			$model->saveUploadedFile ( $model, 'image_file' );
			if ($model->save ()) {
				
				return $this->redirect( [
						'view',
						'id' => $model->id 
				] );
			}
		}
		$model->password = null;
		
		$this->updateMenuItems ( $model );
		return $this->render( 'update', [
				'model' => $model,
				'role_id' => $role_id 
		] );
	}
	
	/* public function actionDelete($id) {
		$user = $this->loadModel($id);
		$role_id = $user->role_id;
		$this->loadModel($id)->delete ();
		
		return $this->redirect( array (
				'/user/admin/role_id/' . $role_id 
		) );
	} */

	
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new ActiveDataProvider(['query' => User::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => User::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', [
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionAdmin($role_id = null) {
		$model = new User(['scenario' => 'search']);
		if (! ($model->checkPermission ( 'user/admin' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		$this->updateMenuItems ( $model );
		if ($role_id != null) {
			$_GET ['User'] ['role_id'] = $role_id;
		}
		if (isset ( $_GET ['User'] ))
			$model->load($_GET, 'User');
		
		return $this->render( 'admin', [
				'model' => $model,
				'role_id' => $role_id 
		] );
	}
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new User();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$user = Yii::$app->user->model;
					$role_id = $user->role_id;
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ) . ' ' . $model->label (),
							'url' => Ui::to('user/view', ['id' => $model->id]),
							'visible' => $model->checkPermission ( "user/view" ) == "true" 
					];
					if($role_id == 1){
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Change Password' ),
							'url' => Ui::to('user/changepassword', ['id' => $model->id]) 
					];
					}else{
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'Change Password' ),
								'url' => Ui::to('user/changepassword', ['id' => $user->id])
						];
					}
				}
				break;
			case 'create' :
				{
					// $this->menu[] = array('label'=>'Manage' . ' ' . $model->label(2), 'url'=>array('admin'), 'visible'=> Yii::$app->user->isAdmin);
					// $this->menu[] = array('label'=>'List' . ' ' . $model->label(2), 'url'=>array('index'),'visible'=> Yii::$app->user->isAdmin);
				}
				break;
			case 'admin' :
				{
					// $this->menu[] = array('label'=>'Manage' . ' ' . $model->label(2), 'url'=>array('admin'), 'visible'=> Yii::$app->user->isAdmin);
					
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'visible' => $model->checkPermission ( "user/create" ) == "true" 
					];
				}
				break;
			// default:
			case 'view' :
				{
					// $this->menu[] = array('label'=>'List' . ' ' . $model->label(2), 'url'=>array('index'),'visible'=> Yii::$app->user->isAdmin);
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ) . ' ' . $model->label ( 2 ),
							'url' => [
									'admin' 
							],
							'visible' => $model->checkPermission ( "user/admin" ) == "true" 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Change Password' ),
							'url' => Ui::to('user/changepassword', ['id' => $model->id]) 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => Ui::to('user/update', ['id' => $model->id]),
							'visible' => $model->checkPermission ( "user/update" ) == "true" 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Security Questions' ),
							'url' => [
									'question' 
							] 
					];
				}
				break;
		}
	}
	public function actionActive() {
		if (isset ( $_GET ['term'] ) && ($keyword = trim ( $_GET ['term'] )) !== '') {
			$models = User::searchByName ( $keyword );
			$suggest = [];
			foreach ( $models as $model ) {
				$suggest [] = [
						'label' => $model->full_name, // label for dropdown list
						'value' => $model->username, // value for input field
						'id' => $model->id 
				] // return values from autocomplete
;
			}
			echo json_encode( $suggest );
		}
		Yii::$app->end();
	}
	public function actionChangePass($id = null, $expired = false) {
		$this->layout = 'column1';
	
		if(!$id)
			$id = Yii::$app->user->id;
			$passmodel = new UserPassword();
			$model = $this->loadModel($id);
			$model->scenario = 'changepassword';
			$this->updateMenuItems();
			//$this->performAjaxValidation($model, 'user-form');
	
			if (isset($_POST['User']) && isset($_POST['User']['password']))
			{
				if(($_POST['User']['password'] != '') && ($_POST['User']['password_2'] != ''))
				{
					if ($model->setPassword($_POST['User']['password'], $_POST['User']['password_2']))
					{
						$model->last_password_change = date("Y-m-d H:i:s");
						$model->saveAttributes(['last_password_change']);
						$passmodel->password = $_POST['User']['password'];
						$passmodel->create_time =  date("Y-m-d H:i:s");
						$passmodel->create_user_id =  $model->id;
						$passmodel->save();
						return $this->redirect(['view', 'id' => $model->id]);
	
					}
				}
				else {
					Yii::$app->user->setFlash('error','Please add password and confirm password .');
				}
			}
	
			$model->password = null; // empty it
			return $this->render('changepass', [
					'model' => $model,
			]);
	}
	public function actionChangePassword($id = null, $expired = false) {
		$user = Yii::$app->user->model;
		$role_id = $user->role_id;
		if(!$id || $role_id != 1){
		
		$id = Yii::$app->user->id;
		}
		$passmodel = new UserPassword();
		$model = $this->loadModel($id);
		$model->scenario = 'changepassword';
		$this->updateMenuItems();
		//$this->performAjaxValidation($model, 'user-form');

		if (isset($_POST['User']) && isset($_POST['User']['password']))
		{
		if(($_POST['User']['password'] != '') && ($_POST['User']['password_2'] != ''))
		{
			if ($model->setPassword($_POST['User']['password'], $_POST['User']['password_2']))
			{
			$model->last_password_change = date("Y-m-d H:i:s");
			$model->saveAttributes(['last_password_change']);
			$passmodel->password = $_POST['User']['password'];
			$passmodel->create_time =  date("Y-m-d H:i:s");
			$passmodel->create_user_id =  $model->id;
			$passmodel->save();
			return $this->redirect(['view', 'id' => $model->id]);

			}
		}
		else {
		Yii::$app->user->setFlash('error','Please add password and confirm password .');
		}
		}

		$model->password = null; // empty it
		return $this->render('changepassword', [
				'model' => $model,
		]);
	}
	public function actionPasswordExpired($id = null) {
	$model = new UserQuestion ();
		$set = true;
		if (isset ( $_POST ['UserQuestion'] )) {
			$question_ids = $_POST ['UserQuestion'] ['question_id'];
			$answer = $_POST ['UserQuestion'] ['answer'];
			if (! empty ( $question_ids ) && ! empty ( $answer ) && count ( $answer ) == 2 && count ( $question_ids ) == 2) {
				
				$result = array_combine ( $question_ids, $answer );
				if ($result) {
					foreach ( $result as $res => $val ) {
						if ($res != '' && $val == '') {
							$set = false;
						}
					}
					foreach ( $result as $res => $val ) {
						if ($set == true) {
							$umodel = UserQuestion::findOne( [
									'question_id' => $res,
									'answer' => $val,
									'create_user_id' => $id
							] );
							
						}
					}
					if(!empty($umodel)){
					$this->actionChangePass ( $id, $expired = true );
					exit;
					}else {
				Yii::$app->user->setFlash ( 'error', 'Please add answer to all questions' );
				
			}
				}
				//$this->actionChangePassword ( $id, $expired = true );
			} else {
				Yii::$app->user->setFlash ( 'error', 'Please add answer to all questions' );
			}
		
		} else if (isset ( $_POST ['User'] )){
			$this->actionChangePass ( $id, $expired = true );
		}
		
		return $this->render( 'answer', [
				'model' => $model,'id'=>$id
		] );
		
	}
	public function loginByEmail() {
		$user = User::findOne( [
				'email' => $this->loginForm->username 
		] );
		if($user == null){
		$user = User::findOne( [
				'username' => $this->loginForm->username
		] );
		}
		
		
		if ($user)
			return $this->authenticate ( $user );
		else
			return null;
		// throw new CException('The profile submodule must be enabled to allow login by Email');
	}
	public function loginByUsername() {
		if ($this->caseSensitiveUsers)
			$user = User::find()->where('username = :username', [
					':username' => $this->loginForm->username 
			])->orderBy(['id' => SORT_DESC])->one();
		else
			$user = User::find()->where('upper(username) = :username', [
					':username' => strtoupper ( $this->loginForm->username ) 
			])->orderBy(['id' => SORT_DESC])->one();
		if ($user)
			return $this->authenticate ( $user );
		else
			return null;
		// throw new CException('The profile submodule must be enabled to allow login by Email');
	}
	public function authenticate($user) {
		$identity = new UserIdentity ( $this->loginForm->username, $this->loginForm->password );
		$identity->authenticate ();
		
		
		
		switch ($identity->errorCode) {
			case UserIdentity::ERROR_NONE :
				$duration = $this->loginForm->rememberMe ? 3600 * 24 * 30 : 0; // 30 days
				Yii::$app->user->login ( $identity, $duration );
				return $user;
				break;
			case UserIdentity::ERROR_EMAIL_INVALID :
				$this->loginForm->addError ( "password", Yii::t ( 'app', 'Username or Password is incorrect' ) );
				break;
			case UserIdentity::ERROR_STATUS_INACTIVE :
				$this->loginForm->addError ( "password", Yii::t ( 'app', 'This account is not activated.' ) );
				break;
			case UserIdentity::ERROR_STATUS_BANNED :
				$this->loginForm->addError ( "password", Yii::t ( 'app', 'This account is blocked.' ) );
				break;
			case UserIdentity::ERROR_STATUS_REMOVED :
				$this->loginForm->addError ( 'password', Yii::t ( 'app', 'Your account has been deleted.' ) );
				break;
			case UserIdentity::ERROR_PASSWORD_EXPIRED :
				return $this->redirect( [
						'user/passwordexpired',
						'id' => $user->id 
				] );
				// $this->loginForm->addError('password', 'Your password has been expired. Please contact the administrator');
				break;
			
			case UserIdentity::ERROR_PASSWORD_INVALID :
				Yii::log ( Yii::t ( 'app', 'Password invalid for user {username} (Ip-Address: {ip})', [
						'{ip}' => Yii::$app->request->getUserHostAddress (),
						'{username}' => $this->loginForm->username 
				] ), 'error' );
				
				if (! $this->loginForm->hasErrors ())
					$this->loginForm->addError ( "password", Yii::t ( 'app', 'Username or Password is incorrect' ) );
				break;
				return false;
		}
	}
	public function actionLogin() {
		$this->layout = 'column1';
		$this->loginForm = new LoginForm ();
		$success = false;
		$action = 'login';
		$login_type = null;
		if (isset ( $_POST ['LoginForm'] )) {
			
			$this->loginForm->attributes = $_POST ['LoginForm'];
			// validate user input for the rest of login methods
			if ($this->loginForm->validate ()) {
				
				$success = $this->loginByEmail ();
				
				
				
				if ($success)
					$login_type = 'email';
			}
			
			
			
			$setting = Setting::find()->orderBy(['id' => SORT_DESC])->one();
			if($setting){
			$days = $setting->days;
			$create_time = $setting->create_time;
			}else{
				$days = '-1';
				$create_time = date('Y-m-d');
			}
			$create_date = date('Y-m-d',strtotime($create_time));
			$str_create_date = strtotime(date('Y-m-d'));
				
			$check_date = strtotime("+".$days." days", strtotime($create_date));
			
			
			
			if(($setting) && ($setting->check_val == Setting::SETTING_YES) && ($str_create_date <= $check_date)){
			
			
			/* if($server_output == 1){ */
			if ($success instanceof User) {
				$query = Session::find();
				$query->orderBy(['id' => SORT_DESC]);
				$query->limit(1);
				$latestSession = $query->one();
				if($latestSession){
				Yii::$app->session['select_session_id'] = $latestSession->id;
				}
				// cookie with login type for later flow control in app
				if ($login_type) {
					$cookie = new CHttpCookie ( 'login_type', serialize ( $login_type ) );
					$cookie->expire = time () + (3600 * 24 * 1);
					Yii::$app->request->cookies ['login_type'] = $cookie;
				}
				if ($success->checkPermission ( 'user/dashboard' )) {
					return $this->redirect( [
							'user/dashboard' 
					] );
				} else {
					return $this->redirect( [
							'user/view' 
					] );
				}
			} else {
				if (! $this->loginForm->hasErrors ())
					$this->loginForm->addError ( 'password', 'Login is not possible with the given credentials' );
			}
			/* }else{
				$this->loginForm->addError ( 'password', 'Login is not possible with the given credentials' );
			} */
		}else {
				if (! $this->loginForm->hasErrors ())
					return $this->redirect( [
							'user/dashboard' 
					] );
			}
		}
		// $this->updateMenuItems();
		
		return $this->render( 'login', [
				'model' => $this->loginForm,
				'loginType' => $this->loginType 
		] );
		/* $this->redirect(Yii::$app->homeUrl); */
	}
	public function redirectUser($user) {
		if ($user->last_action_time == NULL)
			return $this->redirect( [
					'welcome/newUser' 
			] );
		else {
			if (isset ( $_POST ) && isset ( $_POST ['returnUrl'] ))
				return $this->redirect( [
						$_POST ['returnUrl'] 
				] );
				
				/*
			 * if ($user->isAdmin && $this->returnAdminUrl)
			 * $this->redirect($this->returnAdminUrl);
			 *
			 * if ($user->isAdmin && $this->returnAdminUrl)
			 * $this->redirect($this->returnAdminUrl);
			 */
			/* if ($user->isPasswordExpired ())
				return $this->redirect( array (
						'user/passwordexpired',
						'id' => $user->id 
				) ); */
			
			return $this->redirect( Yii::$app->user->returnUrl );
			
		}
	}
	public function actionLogout() {
		// If the user is already logged out send them to returnLogoutUrl
		
		if (Yii::$app->user->isGuest)
			return $this->redirect( Yii::$app->homeUrl );
			
			// let's delete the login_type cookie
		$cookie = Yii::$app->request->cookies ['login_type'];
		if ($cookie) {
			$cookie->expire = time () - (3600 * 72);
			Yii::$app->request->cookies ['login_type'] = $cookie;
		}
		
		if ($user = User::findOne( Yii::$app->user->id )) {
			if($user->role_id ==1){
			//	$this->redirect (array('/backup/default/createBackup') );
			}
			$username = $user->full_name;
			$user->logout ();
			
			Yii::log ( Yii::t ( 'app', 'User {username} logged off', [
					'{username}' => $username 
			] ) );
			
			Yii::$app->user->logout ();
		}
		return $this->redirect( Yii::$app->homeUrl );
	}
	public function actionActivate($id, $key, $mode) {
		$model = $this->loadModel($id);
		
		if ($mode == 'recover')
			$model->state_id = User::STATUS_INACTIVE;
		
		$ret = $model->activate ( $model->email, $key );
		
		if ($mode == 'login') {
			
			if ($ret == 1) {
				Yii::$app->user->setFlash ( 'register', 'Congratulations! Your account is activated.' );
			} else if ($ret == - 2) {
				Yii::$app->user->setFlash ( 'register', 'Invalid activation key.' );
				return $this->redirect( [
						'login' 
				] );
			} else {
				Yii::$app->user->setFlash ( 'register', 'Your account is already activated.' );
			}
			return $this->redirect( [
					'create' 
			] );
		} else {
			if ($ret == 1) {
				$model->fakeLogin ();
				Yii::$app->user->setFlash ( 'recover', 'Please change your password.' );
				return $this->redirect( [
						'changepassword',
						'id' => $model->id 
				] );
			} else if ($ret == - 2) {
				Yii::$app->user->setFlash ( 'recover', 'Invalid activation key.' );
				return $this->redirect( [
						'login' 
				] );
			}
			return $this->render( 'recover', [
					'model' => $model 
			] );
		}
	}
	public function actionRecover() {
		$this->layout = 'column1';
		$model = new User ();
		
		$this->performAjaxValidation( $model, 'user-form' );
		
		if (isset ( $_POST ['User'] )) {
			$email = $_POST ['User'] ['email'];
			$user = User::findOne( [
					'email' => $email 
			] );
		
			if ($user) {
				$from = (Yii::$app->params['mail_email'] ?? null) ;
				$to      = $user->email;
				$subject = 'Your new password:';
				
				$view = $this->renderPartial ( '/mail/recover_password', [
						'user'=>$user
				], true );
				
				
				$user->mailsend ( $to, $from, $subject, $view );
			
				Yii::$app->user->setFlash ( 'recover', 'Please check your email to reset your password.' );
			} 

			elseif ($email == '') {
				
				// $model->addError('email', "Please enter the email.");
				Yii::$app->user->setFlash ( 'error', 'Please enter the email.' );
			} else {
				$model->addError ( 'email', "Email is not registered" );
				Yii::$app->user->setFlash ( 'error', 'Email is not registered.' );
			}
		}
		
		return $this->render( 'recover', [
				'model' => $model 
		] );
	}
	
	/* public function actionFaq() {
		return $this->render( 'faq' );
	}
	public function actionApply() {
		return $this->render( 'apply' );
	}
	public function actionCarrer() {
		return $this->render( 'carrer' );
	}
	public function actionDownloads() {
		return $this->render( 'downloads' );
	}
	public function actionAbout() {
		return $this->render( 'about' );
	}
	public function actionBlog() {
		return $this->render( 'blog' );
	}
	public function actionDriver() {
		return $this->render( 'driver' );
	}
	public function actionPassenger() {
		$user = Yii::$app->user->model;
		$journey = new Journey ();
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'passenger_id =' . Yii::$app->user->id );
		// $criteria->addCondition('state_id = 1');
		$dataProvider = new CActiveDataProvider ( 'Journey', array (
				'Criteria' => $criteria 
		) );
		return $this->render( 'passenger', array (
				'dataProvider' => $dataProvider,
				'journey' => $journey,
				'user' => $user 
		) );
	}
	public function actionHome() {
		return $this->redirect( array (
				'index' 
		) );
		
		$user = Yii::$app->user->model;
		$query = Journey::find();
		$journey = new Journey ();
		$bookdriver = new Driver ();
		
		$query->andWhere('passenger_id =' . Yii::$app->user->id);
		// $criteria->addCondition('state_id = 1');
		$dataProvider = new ActiveDataProvider(['query' => $query]);
		$query1 = Driver::find();
		$query1->andWhere('user_id =' . Yii::$app->user->id);
		$driver = $query1->all();
		// $passenger = Passenger::model()->findAll($criteria1);
		
		$gridDataProvider = new \yii\data\ArrayDataProvider(['allModels' => $driver]);
		
		$dataProvider1 = new CActiveDataProvider ( 'Dispatcher' );
		return $this->render( 'home', array (
				'user' => $user,
				'dispatcher' => $dataProvider1,
				'dataProvider' => $dataProvider,
				'journey' => $journey,
				'gridDataProvider' => $gridDataProvider,
				'bookdriver' => $bookdriver 
		) );
	} */
	
	

	
	public function actionReorder(){
		$query = MrsDetail::find();
		$query->andWhere('status ='.Mrs::STATUS_PENDING);
		$query->andWhere('type_id = 0');
		$query->limit(5);
		$query->orderBy(['id' => SORT_DESC]);
		$mrsdetails = $query->all();
	  
	   
	            if($mrsdetails){
	                foreach($mrsdetails as $mrsdetail){
	                    $item = Item::findOne($mrsdetail->item_id);
	                    if($item){
	                       
	                    $reorder_qty = $item->getReorderQtyNew();
	                    $max_qty = $item->getMaximumQty();
	                    $min_qty = $item->getMinimumQty();
	                    $mrsdetail->req_qty = $max_qty;
	                    $mrsdetail->approved_qty = $reorder_qty;
	                    $mrsdetail->min_qty =$min_qty;
						$mrsdetail->type_id = 1;
	                    if($mrsdetail->save()){
						}else{
							//print_r($mrsdetail->getErrors());exit;
						}
	                    }
	                }
	            }
	           
	}
	public function actionTimer(){
		$user = new User();
		$model = new PurchaseBill();
	
		$model->getConsignmentOptions();
		$user->addNewSessionName();
		
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
		return ['terms', 'ajax', 'index', 'active', 'changePass', 'activate', 'faq', 'apply', 'carrer', 'downloads', 'about', 'blog', 'driver'];
	}
}
