<?php
/**
 * @property integer $id
 * @property string $auth_code
 * @property string $device_token
 * @property integer $type_id
 * @property integer $create_user_id
 * @property string $create_time
 * @property string $update_time
 */
Yii::import('application.models._base.BaseAuthSession');
class AuthSession extends BaseAuthSession
{

	private static $session_expiration_days = 90;

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function scopes()
	{
		return array(
				'old' => array('condition'=> 'update_time < \''.date('Y-m-d H:i:s', strtotime('-30 day')) .'\''),
		);
	}

	public static function randomCode($count = 32) {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < $count; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass);; //turn the array into a string
	}
        public static function newSession($model)
	{
		//	self::logoutSession();
		self::deleteSession($model);
		self::deleteOldSession();
		$auth_session = new AuthSession();
		$auth_session->auth_code = self::randomCode();
		$auth_session->device_token = $model->device_token;
		$auth_session->type_id = $model->device_type;
		$auth_session->save();

		return $auth_session;
	}
	public static function deleteSession($model)
	{
		$auth_sessions = AuthSession::model()->findAllbyAttributes( array ( 'device_token' => $model->device_token));
		foreach ( $auth_sessions as $session)
		$session->delete();
	}
	public static function deleteOldSession()
	{
	/* 	$old = AuthSession::model()->old()->findAll();
		foreach ( $old as $session)
		$session->delete(); */
	}
	public static function authenticateSession($auth_code = null)
	{
		
		// just exit if login is not required.
		//$auth_code = 'UM2KdkCgZdEhtFatRB7ApoQXC67Ldk3Z';
		if ( !Yii::app()->user->isGuest ) return;

		if ( $auth_code == null)
		{
			$headers = getallheaders();
			$auth_code = isset($headers['auth_code']) ? $headers['auth_code'] : null;
			if ( $auth_code == null ) $auth_code = Yii::app()->request->getQuery('auth_code');
			// just exit if auth code is null
			if (  $auth_code == null ) return;

		}


		Yii::log ( CVarDumper::dumpAsString ( $auth_code ), CLogger::LEVEL_WARNING, '$$auth_code' );
		$auth_session = AuthSession::model()->findbyAttributes( array ( 'auth_code'=>$auth_code));
		Yii::log ( CVarDumper::dumpAsString ( $auth_session ), CLogger::LEVEL_WARNING, '$auth_session' );
		
		if ($auth_session)
		{
			$user = $auth_session->createUser;
			//Yii::log ( CVarDumper::dumpAsString ( $user ), CLogger::LEVEL_WARNING, '$$user' );
			$identity = new UserIdentity($user, $user);
			$identity->authenticateSession($user);

			switch($identity->errorCode) {
				case UserIdentity::ERROR_NONE:
					$duration = 3600*24*30; // 30 days
					Yii::app()->user->login($identity,$duration);
					Yii::log ( CVarDumper::dumpAsString ( Yii::app()->user->model ), CLogger::LEVEL_WARNING, '$$user' );
					$auth_session->save(); // update time is changed here
					return true;
					break;
				case UserIdentity::ERROR_STATUS_USER_DOES_NOT_EXIST:
					$user->addError('status', Yii::t('app','User doesnt exists.'));
					break;
			}
		}

/*		//if ( Yii::app()->module != null && Yii::app()->module->id == 'api')
		{
			$controller = Yii::app()->controller;
			$arr = array('controller'=>$controller->id, 'action'=>$controller->action->id,'status' =>'NOK');
				
			header('Content-type: application/json');
			echo json_encode($arr);
			Yii::app()->end();
		}*/
		return false;
	}
	public static function logoutSession()
	{
		// just exit if login is not required.
		/* if ( Yii::app()->user->isGuest ) return;

		foreach (Yii::app()->user->model->authSessions as $session)
		$session->delete(); */
	}
}
