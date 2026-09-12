<?php

 
/**
 * @property integer $id
 * @property string $check_val
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 */
Yii::import('application.models._base.BaseSetting');
class Setting extends BaseSetting
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
			$json_entry ['days'] = $model->days;
			$json_entry ['date'] = date('d/m/Y',strtotime($model->create_time));
			$json_entry ['current_date'] = date('d/m/Y');
		}
		return $json_entry;
	}
}