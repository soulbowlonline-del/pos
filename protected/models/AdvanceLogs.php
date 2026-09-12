<?php

/**
 * @property integer $id
 * @property double $amount
 * @property integer $advance_payment_id
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
Yii::import('application.models._base.BaseAdvanceLogs');
class AdvanceLogs extends BaseAdvanceLogs
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}