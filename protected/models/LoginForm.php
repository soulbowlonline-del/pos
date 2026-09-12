<?php

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'SiteController'.
 */
class LoginForm extends CFormModel
{
	public $username;
	public $contact_no;
	public $password;
	public $rememberMe = true;
    public $email;
	private $_identity;

	public $device_token;
	public $device_type;
	public $lat = null;
	public $long = null;
	
	public $identifier;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		if(!isset($this->scenario))
		$this->scenario = 'login';

		return array(
		// username and password are required
		array('username, password', 'required'),

				array('device_token,email, device_type,identifier', 'safe'),
				array('device_type', 'numerical', 'integerOnly'=>true),
		// rememberMe needs to be a boolean
		array('rememberMe', 'boolean'),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array(
			'username'=>'Username or Email',
			'contact_no'=>'Contact Number',
			'rememberMe'=>'Remember me next time',
		);
	}
}
