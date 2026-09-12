<?php

class UserIdentity extends CUserIdentity {
	public $id;
	public $user;
	const ERROR_EMAIL_INVALID	= 3;
	const ERROR_STATUS_INACTIVE	= 4;
	const ERROR_STATUS_BANNED	= 5;
	const ERROR_STATUS_REMOVED	= 6;
	const ERROR_STATUS_USER_DOES_NOT_EXIST = 7;
	const ERROR_PASSWORD_EXPIRED = 8;

	public $ldapOptions = array(
			'ldap_host' => '',
			'ldap_port' => '',
			'ldap_basedn' => '',
			'ldap_protocol' => '',
			'ldap_autocreate' => '',
			'ldap_tls' => '',
			'ldap_transfer_attr' => '',
			'ldap_transfer_pw' => '');



	public function authenticateSession($user)
	{
		
		if(!$user)
		return self::ERROR_STATUS_USER_DOES_NOT_EXIST;
		$this->id = $user->id;
		$this->setState('id', $user->id);
		$this->username = $user->full_name;
		$this->user = $user;
		$this->errorCode=self::ERROR_NONE;
		//Yii::log ( CVarDumper::dumpAsString ( $this ), CLogger::LEVEL_WARNING, '$$this' );
		return!$this->errorCode = self::ERROR_NONE;
	}

	public function authenticate($loginByEmail = true)
	{
		$user =  null;
		
			$user = User::model()->findByAttributes(array('email' => $this->username));
		
		if($user == null)
		{
			$user = User::model()->findByAttributes(array('username' => $this->username));
		}

		if(!$user)
		return $this->errorCode = self::ERROR_STATUS_USER_DOES_NOT_EXIST;
		else  if(!User::validate_password($this->password,$user->password))
		{
			$this->errorCode=self::ERROR_PASSWORD_INVALID;
		}
		else if($user->state_id == User::STATUS_INACTIVE)
		$this->errorCode=self::ERROR_STATUS_INACTIVE;
		else if($user->state_id == User::STATUS_BANNED)
		$this->errorCode=self::ERROR_STATUS_BANNED;
		else if($user->state_id == User::STATUS_REMOVED);
		//else if ($user->isPasswordExpired()){
		//$this->errorCode=self::ERROR_PASSWORD_EXPIRED;
			//$this->redirect(array('user/passwordexpired','id'=>$user->id));
			//}
		else {
			$this->id = $user->id;
			$this->setState('id', $user->id);
			$this->username = $user->full_name;
			$this->errorCode=self::ERROR_NONE;
		}
		return !$this->errorCode;
	}

	/**
	 * @return integer the ID of the user record
	 */
	public function getId()
	{
		return $this->id;
	}

	public function getRoles()
	{
		return $this->Role;
	}

}
