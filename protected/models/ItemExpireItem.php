<?php

/**
 * Company: ToXSL Technologies Pvt. Ltd. < www.toxsl.com >
 * Author : Shiv Charan Panjeta < shiv@toxsl.com >
 */
 
/**
 * @property integer $id
 * @property integer $item_id
 * @property integer $item_detail_id
 * @property string $mrp
 * @property string $sale_rate
 * @property integer $free
 * @property integer $qty
 * @property string $total_amt
 * @property integer $vendor_id
 * @property integer $outlet_id
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $item_expire_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemExpireItem');
class ItemExpireItem extends BaseItemExpireItem
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}