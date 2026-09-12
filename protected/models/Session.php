<?php


 
/**
 * @property integer $id
 * @property string $name
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 */
Yii::import('application.models._base.BaseSession');
class Session extends BaseSession
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}