<?php


 
/**
 * @property integer $id
 * @property string $code
 * @property string $mrs_date
 * @property string $mrs_update_date
 * @property string $mrs_req_date
 * @property integer $status
 * @property integer $type_id
 * @property string $remarks
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 * @property integer $outlet_id
 * @property integer $mrs_id
 * @property integer $organization_id
 */
Yii::import('application.models._base.BaseMrn');
class Mrn extends BaseMrn
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}