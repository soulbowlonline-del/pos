<?php

 
/**
 * @property integer $id
 * @property string $credit_number
 * @property double $amt
 * @property double $amt_used
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
Yii::import('application.models._base.BaseCreditNote');
class CreditNote extends BaseCreditNote
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}