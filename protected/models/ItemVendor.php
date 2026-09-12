<?php


 
/**
 * @property integer $id
 * @property integer $item_detail_id
 * @property integer $vendor_id
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemVendor');
class ItemVendor extends BaseItemVendor
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}