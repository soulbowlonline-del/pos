<?php


 
/**
 * @property integer $id
 * @property integer $emp_id
 * @property integer $shift_id
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseEmpShift');
class EmpShift extends BaseEmpShift
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}