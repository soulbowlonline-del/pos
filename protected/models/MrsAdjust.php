<?php


 
/**
 * @property integer $id
 * @property integer $item_id
 * @property integer $item_detail_id
 * @property integer $mrs_detail_id
 * @property string $qty
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 */
Yii::import('application.models._base.BaseMrsAdjust');
class MrsAdjust extends BaseMrsAdjust
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}