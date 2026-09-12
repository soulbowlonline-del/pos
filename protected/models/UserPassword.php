<?php


 
/**
 * @property integer $id
 * @property string $password
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseUserPassword');
class UserPassword extends BaseUserPassword
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function setPassword($password,$password_2)
	{
		if ($password != '' && $password == $password_2 ) {
			$this->password = User::encrypt2($password);
			return $this->save(false,'password');
		}
		return false;
	}
}