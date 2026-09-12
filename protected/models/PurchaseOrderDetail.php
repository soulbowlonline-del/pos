<?php


 
/**
 * @property integer $id
 * @property integer $req_qty
 * @property integer $bal_qty
 * @property integer $status
 * @property integer $type_id
 * @property double $charge_amount
 * @property double $extra_charges
 * @property string $remarks
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 * @property integer $item_detail_id
 * @property integer $item_id
 * @property integer $purchase_order_id
 * @property integer $outlet_id
 */
Yii::import('application.models._base.BasePurchaseOrderDetail');
class PurchaseOrderDetail extends BasePurchaseOrderDetail
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getPOVendorOptions(){
		$list = [];
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.PurchaseOrder::STATUS_UNAPPROVED);
		$mrss = PurchaseOrder::model()->findAll($criteria);
		if($mrss){
			foreach($mrss as $mrs){
			   $create_time = date('d-m-Y',strtotime($mrs->create_time));
				$vendor = Vendor::model()->findByPk($mrs->vendor_id);
				if($vendor){
					$list[$vendor->id] = $vendor->name.'('.$create_time.')';
					//$list[$vendor->id] = $vendor->name;
				}
				
				
			}
		}
		asort($list);
		return $list;
	}
	public function getPOOptions($id = null){
		$list = array();
		$user = Yii::app()->user->model;
		if($user){
			$role_id = $user->role_id;
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			
			if ($id != null) {
				if ($role_id == $role->id) {
				$criteria = new CDbCriteria();
				$criteria->addCondition('vendor_id ='.$id);
				$criteria->addCondition('status ='.PurchaseOrder::STATUS_UNAPPROVED);
				$polist = PurchaseOrder::model()->findAll($criteria);
			}else{
				$criteria = new CDbCriteria();
				
				$criteria->addCondition('status ='.PurchaseOrder::STATUS_UNAPPROVED);
				$polist = PurchaseOrder::model()->findAll($criteria);
			}
			if($polist){
				foreach($polist as $po){
					$list[$po->id] = $po->id;
				}
			}
		}
		}
		return $list;
	}
	public function getAllPOOptions($id = null){
		$list = array();
		$user = Yii::app()->user->model;
		if($user){
			$role_id = $user->role_id;
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			
			if ($id != null) {
				if ($role_id == $role->id) {
			$criteria = new CDbCriteria();
			$criteria->addCondition('vendor_id ='.$id);
			$criteria->addCondition('status ='.PurchaseOrder::STATUS_UNAPPROVED);
			$polist = PurchaseOrder::model()->findAll($criteria);
			}else{
				$criteria = new CDbCriteria();
				
				$criteria->addCondition('status ='.PurchaseOrder::STATUS_UNAPPROVED);
				$polist = PurchaseOrder::model()->findAll($criteria);
			}
			if($polist){
				foreach($polist as $po){
					$list[] = $po->id;
				}
			}
		}
		}
		return $list;
	}
	
	public function getGstTrue($poid){
		$gst = true;
		if($poid){
			$mrs = PurchaseOrder::model()->findByAttributes(array('id'=>$poid));
			if($mrs){
				$outlet = Outlet::model()->findByPk($mrs->outlet_id);
				if($outlet){
					$vendor = Vendor::model()->findByPk($mrs->vendor_id);
					if($vendor->state_id != $outlet->state_id){
						$gst = false;
					}
				}
			}
		}
		return $gst;
	}
}