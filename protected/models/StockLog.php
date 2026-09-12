<?php


 
/**
 * @property integer $id
 * @property integer $item_detail_id
 * @property integer $item_id
 * @property string $batch_no
 * @property integer $Qty
 * @property integer $outlet_id
 * @property integer $vendor_id
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
Yii::import('application.models._base.BaseStockLog');
class StockLog extends BaseStockLog
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}