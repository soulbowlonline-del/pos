<?php

 
/**
 * @property integer $id
 * @property string $title
 * @property integer $status
 * @property integer $type_id
 * @property integer $country_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseState');
class State extends BaseState
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
	public static function getCountryName($id){
		$name = '';
		$country = Country::model()->findByPk($id);
		if($country){
			$name = $country->title;
		}
		return $name;
	}
	public static function getStateName($id){
		$name = '';
		$state = State::model()->findByPk($id);
		if($state){
			$name = $state->title;
		}
		return $name;
	}
	public static function getStateCountryName($id){
		$name = '';
		$state = State::model()->findByPk($id);
		if($state){
			$name = isset($state->country)?$state->country:'';
		}
		return $name;
	}
	
	
}