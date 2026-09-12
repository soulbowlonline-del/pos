<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseUserRole');
class UserRole extends BaseUserRole
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}