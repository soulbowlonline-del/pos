<?php


 
/**
 * @property integer $id
 * @property integer $item_detail_id
 * @property integer $discount_id
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemDiscount');
class ItemDiscount extends BaseItemDiscount
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}