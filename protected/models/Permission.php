<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property string $url
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BasePermission');
class Permission extends BasePermission
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}