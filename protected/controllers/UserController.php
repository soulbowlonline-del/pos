<?php
class UserController extends GxController {
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
	public function filters() {
		return array (
				'accessControl',
				array (
						'COutputCache + PageCache',
						'duration' => 30,
						'varyByParam' => array (
								'id' 
						) 
				) 
		);
	}
	public function accessRules() {
		return array (
				array (
						'allow', // allow all users to perform 'index' and 'view' actions
						'actions' => array (
								'deleteAssets',
								//'registration',
								
								//'driver',
								'download',
								//'blog',
							     'recover',
								'login',
								//'terms',
								//'Faq',
								//'apply',
								//'carrer',
								//'about',
								'thumbnail',
								'passwordexpired',
								'ajaxuserquestion','timer','reorder',
								'setSession'
						),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow', // allow authenticated user to perform 'create' and 'update' actions
						'actions' => array (
								'create',
								'ajaxquestion',
								'toggle',
								'inactivate',
								'delete',
								//'index',
								'reset',
								'question',
								'dashboard',
								'admin',
								'dash',
								'view',
								'Empty',
								'home',
								'setLocation',
								'update',
								'updateDriver',
								'logout',
								//'index',
								'passenger',
								'changePassword',
								'clearHistory',
								'clearFavorite',
								'test',
								'clear',
								'show' 
						),
						'users' => array (
								'@' 
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
	public function actionSetSession(){
		if(isset($_POST['User']['session_id'])){
			Yii::app()->session['select_session_id'] = $_POST['User']['session_id'];
		}
		if(isset($_POST['User']['api_key']) && ($_POST['User']['api_key'] != '')){
			$id = 1;
			$setting = Setting::model()->findByPk($id);
			$setting->api_key = $_POST['User']['api_key'];
			$setting->saveAttributes(array('api_key'));
		}
		if(isset($_POST['User']['ivr_username']) && ($_POST['User']['ivr_username'] != '')){
			$id = 1;
			$setting = Setting::model()->findByPk($id);
			$setting->ivr_username = $_POST['User']['ivr_username'];
			$setting->saveAttributes(array('ivr_username'));
		}
		$this->redirect(array('dashboard'));
	}
	public function actionTerms() {
		$this->render ( 'terms' );
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
							$model = UserQuestion::model ()->findByAttributes ( array (
									'question_id' => $res,
									'create_user_id' => Yii::app ()->user->id 
							) );
							if ($model == null)
								$model = new UserQuestion ();
							$model->question_id = $res;
							$model->answer = $val;
							$model->save ();
						}
					}
				}
			} else {
				Yii::app ()->user->setFlash ( 'error', 'Please add answer to all questions' );
			}
			if ($set == false) {
				Yii::app ()->user->setFlash ( 'error', 'Please add answer to all questions' );
			} else {
				Yii::app ()->user->setFlash ( 'success', 'You have successfully submitted the answers' );
			}
		}
		
		$this->render ( 'question', array (
				'model' => $model 
		) );
	}
	public function actionAjaxQuestion() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['selected'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->compare('id', '<>' . PostId::get('selected'));
			$criteria->order = 'title asc';
			$questions = Question::model ()->findAll ( $criteria );
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
		$alreadypermissions = array ();
		if (isset ( $_POST ['selected'] )) {
				
			$criteria = new CDbCriteria ();
			$criteria->compare('id', '<>' . PostId::get('selected'));
			if($id != null){
				$userques = UserQuestion::model()->findAllByAttributes(array('create_user_id'=>$id));
				if($userques){
					foreach($userques as $userque){
						$ids[] = $userque->question_id;
					}
				}
				if(!empty($ids))
					$criteria->addInCondition('id', $ids);
			}
				
			$criteria->order = 'title asc';
			$questions = Question::model ()->findAll ( $criteria );
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
			$model = $this->loadModel ( $id, 'User' );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
			
			$model->state_id = User::STATUS_INACTIVE;
			
			if ($model->save ()) {
				
				$products = Product::model ()->findAllByAttributes ( array (
						'create_user_id' => $id,
						'state_id' => Product::STATE_APPROVE 
				) );
				
				if ($products) {
					foreach ( $products as $product ) {
						$product->state_id = Product::STATE_UNAPPROVE;
						$product->saveAttributes ( array (
								'state_id' 
						) );
					}
				}
			}
		}
		$this->redirect ( array (
				'/user/dashboard' 
		) );
	}
	public function actionToggle($id) {
		$model = $this->loadModel ( $id, 'User' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		$state_id = $model->state_id;
		if ($state_id == User::STATUS_INACTIVE) {
			$model->state_id = User::STATUS_ACTIVE;
		} else {
			$model->state_id = User::STATUS_INACTIVE;
		}
		
		if ($model->saveAttributes ( array (
				'state_id' 
		) ));
		
		$this->redirect ( array (
				'/user/admin'
				
		) );
	}
	public function actionDash() {
		$user = Yii::app()->user->model;
		$criteria = new CDbCriteria();
		$itemvendor_ids = array();
		$vendor = Vendor::model()->findByAttributes(array('create_user_id'=>$user->id));
		if($vendor){
			$itemvendors = ItemVendor::model()->findAllByAttributes(array('vendor_id'=>$vendor->id));
			if($itemvendors){
		
				foreach($itemvendors as $itemvendor){
					$itemvendor_ids[] = $itemvendor->item_detail_id;
				}
			}
		}
		$criteria->addInCondition('id', $itemvendor_ids);
	
		$items = Item::model ()->count($criteria);
	
		$vendor = Vendor::model ()->findByAttributes(array('create_user_id'=>$user->id));
		$mrss =  Mrs::model ()->countByAttributes ( array (
				'vendor_id' => $vendor->id
		) );
		$pos =  PurchaseOrder::model ()->countByAttributes ( array (
				'vendor_id' => $vendor->id
		) );
		$grns =  PurchaseBill::model ()->countByAttributes ( array (
				'vendor_id' => $vendor->id
		) );
		
		$model = new User ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		// $_GET['Product']['state_id'] = Product::STATE_UNAPPROVE;
		if (isset ( $_GET ['User'] ))
			$model->setAttributes ( $_GET ['User'] );
		
			$this->render ( 'dash', array (
					'items' => $items,
					'mrss' => $mrss,
					'pos' => $pos,
					'grns' => $grns,
					'model' => $model
			) );
	}
	public function actionDashboard() {
		ini_set ( 'max_execution_time', 30000 );
		$user = Yii::app()->user->model;
		$role_id = $user->role_id;
		if($role_id == 6){
		
			$this->redirect ( array (
					'/user/dash'
			
			) );
		}
		
	$items = Item::model ()->count();
		// $items = 1;
		$vendors = Vendor::model ()->count();
		$orders = 0;
		$pendingorders = 0;
		
		$model = new User ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		// $_GET['Product']['state_id'] = Product::STATE_UNAPPROVE;
		if (isset ( $_GET ['User'] ))
			$model->setAttributes ( $_GET ['User'] );
		
		$this->render ( 'dashboard', array (
				'items' => $items,
				'vendors' => $vendors,
				'orders' => $orders,
				'pendingorders' => $pendingorders,
				'model' => $model 
		) );
	}
	
	
	public function actionAjax($type, $view, $id) {
		if (isset ( $id )) {
			$model = $this->loadModel ( $id, 'User' );
			if ($model->hasAttribute ( $type )) {
				$dataProvider = new CArrayDataProvider ( $objects );
				$this->renderPartial ( '/' . $view . '/_list', array (
						'dataProvider' => $dataProvider 
				) );
			}
		}
		Yii::app ()->end ();
	}
	public function actionView($id = null) {
		$user = Yii::app()->user->model;
		$role_id = $user->role_id;
		if($id==null || $role_id != 1){
		
			$id = Yii::app()->user->id;
		}
		
		$model = $this->loadModel ( $id, 'User' );
		if (! ($model->checkPermission ( 'user/view' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
			
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	
	// ---Passanger and Driver register
	public function actionCreate($role_id = null) {
		$model = new User ();
		if (! ($model->checkPermission ( 'user/create' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$arr = array ();
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
						$model->setAttributes ( $_POST ['User'] );
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
								
								$this->redirect ( array (
										'admin',
										'role_id' => $role_id 
								) );
								
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
		$this->render ( 'create', array (
				'model' => $model,
				'role_id' => $role_id 
		) );
	}
	public function actionUpdate($id = null) {
		/*
		 * if( !(User::isLoggedIn ( $id)))
		 * {
		 * throw new CHttpException(403, Yii::t('thescout','You are not allowed to access this page.'));
		 *
		 * }
		 */
		 $user = Yii::app()->user->model;
		 $role_id = $user->role_id;
		 if ($id == null || $role_id != 1){
		 		
		 	$id = Yii::app ()->user->id;
		 }
		
		$model = $this->loadModel ( $id, 'User' );
		$model->scenario = 'update';
		if (! ($model->checkPermission ( 'user/update' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// $this->performAjaxValidation($model, 'user-form');
		$role_id = $model->role_id;
		if (isset ( $_POST ['User'] )) {
			$model->setAttributes ( $_POST ['User'] );
			$model->saveUploadedFile ( $model, 'image_file' );
			if ($model->save ()) {
				
				$this->redirect ( array (
						'view',
						'id' => $model->id 
				) );
			}
		}
		$model->password = null;
		
		$this->updateMenuItems ( $model );
		$this->render ( 'update', array (
				'model' => $model,
				'role_id' => $role_id 
		) );
	}
	
	/* public function actionDelete($id) {
		$user = $this->loadModel ( $id, 'User' );
		$role_id = $user->role_id;
		$this->loadModel ( $id, 'User' )->delete ();
		
		$this->redirect ( array (
				'/user/admin/role_id/' . $role_id 
		) );
	} */

	
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'User' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	}
	public function actionAdmin($role_id = null) {
		$model = new User ( 'search' );
		if (! ($model->checkPermission ( 'user/admin' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		if ($role_id != null) {
			$_GET ['User'] ['role_id'] = $role_id;
		}
		if (isset ( $_GET ['User'] ))
			$model->setAttributes ( $_GET ['User'] );
		
		$this->render ( 'admin', array (
				'model' => $model,
				'role_id' => $role_id 
		) );
	}
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = User::model ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$user = Yii::app()->user->model;
					$role_id = $user->role_id;
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ) . ' ' . $model->label (),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "user/view" ) == "true" 
					);
					if($role_id == 1){
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Change Password' ),
							'url' => array (
									'changepassword',
									'id' => $model->id 
							) 
					);
					}else{
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Change Password' ),
								'url' => array (
										'changepassword',
										'id' => $user->id
								)
						);
					}
				}
				break;
			case 'create' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'Manage') . ' ' . $model->label(2), 'url'=>array('admin'), 'visible'=> Yii::app()->user->isAdmin);
					// $this->menu[] = array('label'=>Yii::t('app', 'List') . ' ' . $model->label(2), 'url'=>array('index'),'visible'=> Yii::app()->user->isAdmin);
				}
				break;
			case 'admin' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'Manage') . ' ' . $model->label(2), 'url'=>array('admin'), 'visible'=> Yii::app()->user->isAdmin);
					
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'visible' => $model->checkPermission ( "user/create" ) == "true" 
					);
				}
				break;
			// default:
			case 'view' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'List') . ' ' . $model->label(2), 'url'=>array('index'),'visible'=> Yii::app()->user->isAdmin);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ) . ' ' . $model->label ( 2 ),
							'url' => array (
									'admin' 
							),
							'visible' => $model->checkPermission ( "user/admin" ) == "true" 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Change Password' ),
							'url' => array (
									'changepassword',
									'id' => $model->id 
							) 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => array (
									'update',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "user/update" ) == "true" 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Security Questions' ),
							'url' => array (
									'question' 
							) 
					);
				}
				break;
		}
	}
	public function actionActive() {
		if (isset ( $_GET ['term'] ) && ($keyword = trim ( $_GET ['term'] )) !== '') {
			$models = User::searchByName ( $keyword );
			$suggest = array ();
			foreach ( $models as $model ) {
				$suggest [] = array (
						'label' => $model->full_name, // label for dropdown list
						'value' => $model->username, // value for input field
						'id' => $model->id 
				) // return values from autocomplete
;
			}
			echo CJSON::encode ( $suggest );
		}
		Yii::app ()->end ();
	}
	public function actionChangePass($id = null, $expired = false) {
		$this->layout = 'column1';
	
		if(!$id)
			$id = Yii::app()->user->id;
			$passmodel = new UserPassword();
			$model = $this->loadModel($id, 'User');
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
						$model->saveAttributes(array('last_password_change'));
						$passmodel->password = $_POST['User']['password'];
						$passmodel->create_time =  date("Y-m-d H:i:s");
						$passmodel->create_user_id =  $model->id;
						$passmodel->save();
						$this->redirect(array('view', 'id' => $model->id));
	
					}
				}
				else {
					Yii::app()->user->setFlash('error','Please add password and confirm password .');
				}
			}
	
			$model->password = null; // empty it
			$this->render('changepass', array(
					'model' => $model,
			));
	}
	public function actionChangePassword($id = null, $expired = false) {
		$user = Yii::app()->user->model;
		$role_id = $user->role_id;
		if(!$id || $role_id != 1){
		
		$id = Yii::app()->user->id;
		}
		$passmodel = new UserPassword();
		$model = $this->loadModel($id, 'User');
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
			$model->saveAttributes(array('last_password_change'));
			$passmodel->password = $_POST['User']['password'];
			$passmodel->create_time =  date("Y-m-d H:i:s");
			$passmodel->create_user_id =  $model->id;
			$passmodel->save();
			$this->redirect(array('view', 'id' => $model->id));

			}
		}
		else {
		Yii::app()->user->setFlash('error','Please add password and confirm password .');
		}
		}

		$model->password = null; // empty it
		$this->render('changepassword', array(
				'model' => $model,
		));
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
							$umodel = UserQuestion::model ()->findByAttributes ( array (
									'question_id' => $res,
									'answer' => $val,
									'create_user_id' => $id
							) );
							
						}
					}
					if(!empty($umodel)){
					$this->actionChangePass ( $id, $expired = true );
					exit;
					}else {
				Yii::app ()->user->setFlash ( 'error', 'Please add answer to all questions' );
				
			}
				}
				//$this->actionChangePassword ( $id, $expired = true );
			} else {
				Yii::app ()->user->setFlash ( 'error', 'Please add answer to all questions' );
			}
		
		} else if (isset ( $_POST ['User'] )){
			$this->actionChangePass ( $id, $expired = true );
		}
		
		$this->render ( 'answer', array (
				'model' => $model,'id'=>$id
		) );
		
	}
	public function loginByEmail() {
		$user = User::model ()->findByAttributes ( array (
				'email' => $this->loginForm->username 
		) );
		if($user == null){
		$user = User::model ()->findByAttributes ( array (
				'username' => $this->loginForm->username
		) );
		}
		
		
		if ($user)
			return $this->authenticate ( $user );
		else
			return null;
		// throw new CException('The profile submodule must be enabled to allow login by Email');
	}
	public function loginByUsername() {
		if ($this->caseSensitiveUsers)
			$user = User::model ()->find ( 'username = :username', array (
					':username' => $this->loginForm->username 
			) );
		else
			$user = User::model ()->find ( 'upper(username) = :username', array (
					':username' => strtoupper ( $this->loginForm->username ) 
			) );
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
				Yii::app ()->user->login ( $identity, $duration );
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
				$this->redirect ( array (
						'user/passwordexpired',
						'id' => $user->id 
				) );
				// $this->loginForm->addError('password', Yii::t('app','Your password has been expired. Please contact the administrator'));
				break;
			
			case UserIdentity::ERROR_PASSWORD_INVALID :
				Yii::log ( Yii::t ( 'app', 'Password invalid for user {username} (Ip-Address: {ip})', array (
						'{ip}' => Yii::app ()->request->getUserHostAddress (),
						'{username}' => $this->loginForm->username 
				) ), 'error' );
				
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
			
			
			
			$setting = Setting::model()->find();
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
				$criteria = new CDbCriteria();
				$criteria->order = 'id desc';
				$criteria->limit = 1;
				$latestSession = Session::model()->find($criteria);
				if($latestSession){
				Yii::app()->session['select_session_id'] = $latestSession->id;
				}
				// cookie with login type for later flow control in app
				if ($login_type) {
					$cookie = new CHttpCookie ( 'login_type', serialize ( $login_type ) );
					$cookie->expire = time () + (3600 * 24 * 1);
					Yii::app ()->request->cookies ['login_type'] = $cookie;
				}
				if ($success->checkPermission ( 'user/dashboard' )) {
					$this->redirect ( array (
							'user/dashboard' 
					) );
				} else {
					$this->redirect ( array (
							'user/view' 
					) );
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
					$this->redirect ( array (
							'user/dashboard' 
					) );
			}
		}
		// $this->updateMenuItems();
		
		$this->render ( 'login', array (
				'model' => $this->loginForm,
				'loginType' => $this->loginType 
		) );
		/* $this->redirect(Yii::app()->homeUrl); */
	}
	public function redirectUser($user) {
		if ($user->last_action_time == NULL)
			$this->redirect ( array (
					'welcome/newUser' 
			) );
		else {
			if (isset ( $_POST ) && isset ( $_POST ['returnUrl'] ))
				$this->redirect ( array (
						$_POST ['returnUrl'] 
				) );
				
				/*
			 * if ($user->isAdmin && $this->returnAdminUrl)
			 * $this->redirect($this->returnAdminUrl);
			 *
			 * if ($user->isAdmin && $this->returnAdminUrl)
			 * $this->redirect($this->returnAdminUrl);
			 */
			/* if ($user->isPasswordExpired ())
				$this->redirect ( array (
						'user/passwordexpired',
						'id' => $user->id 
				) ); */
			
			$this->redirect ( Yii::app ()->user->returnUrl );
			
		}
	}
	public function actionLogout() {
		// If the user is already logged out send them to returnLogoutUrl
		
		if (Yii::app ()->user->isGuest)
			$this->redirect ( Yii::app ()->homeUrl );
			
			// let's delete the login_type cookie
		$cookie = Yii::app ()->request->cookies ['login_type'];
		if ($cookie) {
			$cookie->expire = time () - (3600 * 72);
			Yii::app ()->request->cookies ['login_type'] = $cookie;
		}
		
		if ($user = User::model ()->findByPk ( Yii::app ()->user->id )) {
			if($user->role_id ==1){
			//	$this->redirect (array('/backup/default/createBackup') );
			}
			$username = $user->full_name;
			$user->logout ();
			
			Yii::log ( Yii::t ( 'app', 'User {username} logged off', array (
					'{username}' => $username 
			) ) );
			
			Yii::app ()->user->logout ();
		}
		$this->redirect ( Yii::app ()->homeUrl );
	}
	public function actionActivate($id, $key, $mode) {
		$model = $this->loadModel ( $id, 'User' );
		
		if ($mode == 'recover')
			$model->state_id = User::STATUS_INACTIVE;
		
		$ret = $model->activate ( $model->email, $key );
		
		if ($mode == 'login') {
			
			if ($ret == 1) {
				Yii::app ()->user->setFlash ( 'register', 'Congratulations! Your account is activated.' );
			} else if ($ret == - 2) {
				Yii::app ()->user->setFlash ( 'register', 'Invalid activation key.' );
				$this->redirect ( array (
						'login' 
				) );
			} else {
				Yii::app ()->user->setFlash ( 'register', 'Your account is already activated.' );
			}
			$this->redirect ( array (
					'create' 
			) );
		} else {
			if ($ret == 1) {
				$model->fakeLogin ();
				Yii::app ()->user->setFlash ( 'recover', 'Please change your password.' );
				$this->redirect ( array (
						'changepassword',
						'id' => $model->id 
				) );
			} else if ($ret == - 2) {
				Yii::app ()->user->setFlash ( 'recover', 'Invalid activation key.' );
				$this->redirect ( array (
						'login' 
				) );
			}
			$this->render ( 'recover', array (
					'model' => $model 
			) );
		}
	}
	public function actionRecover() {
		$this->layout = 'column1';
		$model = new User ();
		
		$this->performAjaxValidation ( $model, 'user-form' );
		
		if (isset ( $_POST ['User'] )) {
			$email = $_POST ['User'] ['email'];
			$user = User::model ()->findByAttributes ( array (
					'email' => $email 
			) );
		
			if ($user) {
				$from = Yii::app()->params['mail_email'] ;
				$to      = $user->email;
				$subject = 'Your new password:';
				
				$view = $this->renderPartial ( '/mail/recover_password', array (
						'user'=>$user
				), true );
				
				
				$user->mailsend ( $to, $from, $subject, $view );
			
				Yii::app ()->user->setFlash ( 'recover', 'Please check your email to reset your password.' );
			} 

			elseif ($email == '') {
				
				// $model->addError('email', "Please enter the email.");
				Yii::app ()->user->setFlash ( 'error', 'Please enter the email.' );
			} else {
				$model->addError ( 'email', "Email is not registered" );
				Yii::app ()->user->setFlash ( 'error', 'Email is not registered.' );
			}
		}
		
		$this->render ( 'recover', array (
				'model' => $model 
		) );
	}
	
	/* public function actionFaq() {
		$this->render ( 'faq' );
	}
	public function actionApply() {
		$this->render ( 'apply' );
	}
	public function actionCarrer() {
		$this->render ( 'carrer' );
	}
	public function actionDownloads() {
		$this->render ( 'downloads' );
	}
	public function actionAbout() {
		$this->render ( 'about' );
	}
	public function actionBlog() {
		$this->render ( 'blog' );
	}
	public function actionDriver() {
		$this->render ( 'driver' );
	}
	public function actionPassenger() {
		$user = Yii::app ()->user->model;
		$journey = new Journey ();
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'passenger_id =' . Yii::app ()->user->id );
		// $criteria->addCondition('state_id = 1');
		$dataProvider = new CActiveDataProvider ( 'Journey', array (
				'Criteria' => $criteria 
		) );
		$this->render ( 'passenger', array (
				'dataProvider' => $dataProvider,
				'journey' => $journey,
				'user' => $user 
		) );
	}
	public function actionHome() {
		$this->redirect ( array (
				'index' 
		) );
		
		$user = Yii::app ()->user->model;
		$criteria = new CDbCriteria ();
		$journey = new Journey ();
		$bookdriver = new Driver ();
		
		$criteria->addCondition ( 'passenger_id =' . Yii::app ()->user->id );
		// $criteria->addCondition('state_id = 1');
		$dataProvider = new CActiveDataProvider ( 'Journey', array (
				'criteria' => $criteria 
		) );
		$criteria1 = new CDbCriteria ();
		$criteria1->addCondition ( 'user_id =' . Yii::app ()->user->id );
		$driver = Driver::model ()->findAll ( $criteria1 );
		// $passenger = Passenger::model()->findAll($criteria1);
		
		$gridDataProvider = new CArrayDataProvider ( $driver );
		
		$dataProvider1 = new CActiveDataProvider ( 'Dispatcher' );
		$this->render ( 'home', array (
				'user' => $user,
				'dispatcher' => $dataProvider1,
				'dataProvider' => $dataProvider,
				'journey' => $journey,
				'gridDataProvider' => $gridDataProvider,
				'bookdriver' => $bookdriver 
		) );
	} */
	
	

	
	public function actionReorder(){
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.Mrs::STATUS_PENDING);
		$criteria->addCondition('type_id = 0');
		$criteria->limit = '5';
		$criteria->order = 'id desc';
		$mrsdetails = MrsDetail::model()->findAll($criteria);
	  
	   
	            if($mrsdetails){
	                foreach($mrsdetails as $mrsdetail){
	                    $item = Item::model()->findByPk($mrsdetail->item_id);
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
}