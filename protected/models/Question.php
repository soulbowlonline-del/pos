<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseQuestion');
class Question extends BaseQuestion
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	
}