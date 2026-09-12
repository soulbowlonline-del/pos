<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseNotification');
class Notification extends BaseNotification
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	public static function AddNotification($model_id,$msg,$type,$to_id){
		$notification = new Notification();
		$notification->model_id = $model_id;
		$notification->model_type = $type;
		$notification->to_id = $to_id;
		$notification->description = $msg;
		$notification->save();
	}
}