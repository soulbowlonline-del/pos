<?php
namespace app\models;

use yii\base\Model;

/**
 * Yii 1 port of protected/models/LoginForm.php.
 *
 * A CFormModel, so there is no table behind it - the login action reads the
 * posted values off it and authenticates them itself. Without this class
 * user/login answered 500: "Class app\controllers\LoginForm not found".
 *
 * Yii 1's rules() also assigned `$this->scenario = 'login'` as a side effect
 * of being called. None of the rules is scenario-dependent and nothing reads
 * the scenario back, so that is left out rather than reproduced.
 */
class LoginForm extends Model
{
    public $username;
    public $contact_no;
    public $password;
    public $rememberMe = true;
    public $email;

    public $device_token;
    public $device_type;
    public $lat = null;
    public $long = null;

    public $identifier;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            [['device_token', 'email', 'device_type', 'identifier'], 'safe'],
            ['device_type', 'integer'],
            ['rememberMe', 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Username or Email',
            'contact_no' => 'Contact Number',
            'rememberMe' => 'Remember me next time',
        ];
    }
}
