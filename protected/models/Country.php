<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseCountry');
class Country extends BaseCountry
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function toArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			$json_entry ['title'] = isset ( $model->title ) ? $model->title : '';
		
		}
		return $json_entry;
	}
}