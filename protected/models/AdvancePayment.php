<?php


 
/**
 * @property integer $id
 * @property string $payment_date
 * @property double $payment
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $vendor_id
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseAdvancePayment');
class AdvancePayment extends BaseAdvancePayment
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	public function getAdvancePayVendorOptions(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->order = 'name asc';
		$criteria->addCondition('status ='.Vendor::STATUS_ACTIVE);
		$criteria->addCondition('is_advance_payment ='.Vendor::ADVANCE_PAYMENT);
		$vendors = Vendor::model()->findAll($criteria);
		if($vendors){
			foreach($vendors as $vendor){
				$list[$vendor->id] = $vendor->name;
			}
		}
		return $list;
	}
}