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
								'login',
								'profile',
								'recover',
								'picker',
								'deliveryboy' 
						)
						,
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
	public function actionLogin() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$model = new LoginForm ();
		// var_dump($_POST['LoginForm']);exit;
		
		/*
		 * $_POST['username']='sandeepk';
		 * $_POST['password']='1234';
		 */
		// $_POST['LoginForm']['device_token']='00000000000000000000';
		Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, '$authdta' );
		if (isset ( $_POST ['username'] ) && isset ( $_POST ['password'] )) {
			
			$model->username = $_POST ['username'];
			$model->password = $_POST ['password'];
			// validate user input and redirect to the previous page if valid
			
			if ($model->validate () && $this->authenticate ( $model )) {
				
				$user = User::model ()->findByPk ( Yii::app ()->user->id );
				Yii::log ( CVarDumper::dumpAsString ( $user ), CLogger::LEVEL_WARNING, '$$user' );
				// $arr ['id'] = Yii::app()->user->id;
				if(isset($_POST['deviceID'])){
				$user->device_token =$_POST['deviceID'];
				$user->saveAttributes(array('device_token'));
				}
				$arr ['status'] = 'OK';
				$arr ['message'] = 'you have successfully Login';
				$arr ['user_profile'] = $user->toArray ();
			} else {
				$err = '';
				foreach ( $model->getErrors () as $error )
					$err .= implode ( ".", $error );
				$arr ['message'] = $err;
				
				// $arr['post_data'] = $_POST;
			}
		} else {
			$arr ['message'] = 'No data posted';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function authenticate($user) {
		$identity = new UserIdentity ( $user->username, $user->password );
		$identity->authenticate ( true );
		
		switch ($identity->errorCode) {
			case UserIdentity::ERROR_NONE :
				$duration = $user->rememberMe ? 3600 * 24 * 30 : 0; // 30 days
				Yii::app ()->user->login ( $identity, $duration );
				return $user;
				break;
			case UserIdentity::ERROR_EMAIL_INVALID :
				$user->addError ( "password", Yii::t ( 'app', 'Username or Password is incorrect' ) );
				break;
			case UserIdentity::ERROR_STATUS_INACTIVE :
				$user->addError ( "status", Yii::t ( 'app', 'This account is not activated.' ) );
				break;
			case UserIdentity::ERROR_STATUS_BANNED :
				$user->addError ( "status", Yii::t ( 'app', 'This account is blocked.' ) );
				break;
			case UserIdentity::ERROR_STATUS_REMOVED :
				$user->addError ( 'status', Yii::t ( 'app', 'Your account has been deleted.' ) );
				break;
			case UserIdentity::ERROR_STATUS_USER_DOES_NOT_EXIST :
				$user->addError ( 'status', Yii::t ( 'app', 'User does not exist.' ) );
				break;
			case UserIdentity::ERROR_PASSWORD_INVALID :
				Yii::log ( Yii::t ( 'app', 'Password invalid for user {username} (Ip-Address: {ip})', array (
						'{ip}' => Yii::app ()->request->getUserHostAddress (),
						'{username}' => $user->username 
				) ), 'error' );
				
				if (! $user->hasErrors ())
					$user->addError ( "password", Yii::t ( 'app', 'Password is incorrect' ) );
				break;
		}
		return false;
	}
	/*
	 * public function actionLogin() {
	 * $arr = array (
	 * 'controller' => $this->id,
	 * 'action' => $this->action->id,
	 * 'status' => 'NOK'
	 * );
	 * $model = new LoginForm ();
	 * // var_dump($_POST['LoginForm']);exit;
	 * /* $_POST['username']='amank@outlinesystemsindia.com';
	 * $_POST['password']='admin';
	 */
	/*
	 * $_POST['LoginForm']['username']='amank@outlinesystemsindia.com';
	 * $_POST['LoginForm']['password']='admin';
	 * $_POST['LoginForm']['device_token']='admiasdsfsf34sdsf';
	 */
	/*
	 * if (isset ( $_POST ['username'] ) && (isset ( $_POST ['password'] ))) {
	 * $model->username = $_POST ['username'];
	 * $model->password = $_POST ['password'];
	 *
	 * $user = null;
	 *
	 * $user = User::model ()->findByAttributes ( array (
	 * 'email' => $model->username
	 * ) );
	 *
	 * if ($user == null) {
	 * $user = User::model ()->findByAttributes ( array (
	 * 'username' => $model->username
	 * ) );
	 * }
	 * // validate user input and redirect to the previous page if valid
	 * if ($user) {
	 *
	 * $arr ['status'] = 'OK';
	 * $arr ['message'] = 'you have successfully Login';
	 * $arr ['user_profile'] = $user->toArray ();
	 *
	 * } else {
	 * $arr ['message'] = 'No User found';
	 * }
	 *
	 * } else {
	 * $arr ['message'] = 'No Data posted';
	 * }
	 *
	 * $this->sendJSONResponse ( $arr );
	 * }
	 */
	public function actionProfile() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		if ($loginid) {
			
			$user = User::model ()->findByPK ( $loginid );
			if ($user) {
				
				$arr ['status'] = 'OK';
				
				$arr ['profile'] = $user->toArray ();
			}
		}
		$this->sendJSONResponse ( $arr );
	}
	
	/**
	 * Recover password
	 */
	public function actionRecover() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		
		$model = new User ();
		
		if (isset ( $_POST ['email'] )) {
			$email = $_POST ['email'];
			$user = User::model ()->findByAttributes ( array (
					'email' => $email 
			) );
			if ($user) {
				$user->sendPassword ();
				$arr ['status'] = 'OK';
				$arr ['message'] = 'Please check your email new password is sent to your email.';
			} elseif ($email == '') {
				$arr ['message'] = 'Please enter the email.';
			} else {
				$arr ['message'] = 'Email is not registered';
			}
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionPicker() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		//$loginid = 1;
		if ($loginid) {
			$role = UserRole::model ()->findByAttributes ( array (
					'title' => 'Admin' 
			) );
			
			if ($role) {
				$criteria = new CDbCriteria ();
				$criteria->addNotInCondition ( 'role_id' ,array('1','6'));
				$users = User::model ()->findAll($criteria);
				if ($users) {
					foreach ( $users as $user ) {
						$list[] = $user->toArray ();
					}
					$arr ['status'] = 'OK';
					
					$arr ['pickerlist'] = $list;
				}
			}
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionDeliveryboy() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
		$headers = getallheaders ();
		$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
		//$loginid = 1;
		if ($loginid) {
			$role = UserRole::model ()->findByAttributes ( array (
					'title' => 'Delivery Boy'
			) );
			if ($role) {
				$users = User::model ()->findAllByAttributes ( array (
						'role_id' => $role->id
				) );
				if ($users) {
						foreach ( $users as $user ) {
							$list[] = $user->toArray ();
						}
						$arr ['status'] = 'OK';
							
						$arr ['deliverylist'] = $list;
					
					
				}
			}
		}
		$this->sendJSONResponse ( $arr );
	}
}