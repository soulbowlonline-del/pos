<?php

 
/**
 * @property integer $id
 * @property string $name
 * @property integer $qty
 * @property double $price
 * @property double $total
 * @property string $image_url
 * @property integer $type_id
 * @property string $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseOnlineOrderItem');
class OnlineOrderItem extends BaseOnlineOrderItem
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
			$json_entry ['name'] = isset ( $model->name ) ? $model->name : '';
			$json_entry ['barcode'] = isset ( $model->barcode ) ? $model->barcode : '';
			$json_entry ['qty'] = isset ( $model->qty ) ? $model->qty : '';
			$json_entry ['price'] = isset ( $model->price) ? $model->price : '';
			$json_entry ['total'] = isset ( $model->total) ? $model->total : '';
			$json_entry ['image_url'] = isset ( $model->image_url) ? $model->image_url : '';
		
			
		}
		return $json_entry;
	}
}