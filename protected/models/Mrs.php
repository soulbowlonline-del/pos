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
 * @property integer $organization_id
 */
Yii::import('application.models._base.BaseMrs');
class Mrs extends BaseMrs
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getMrsVendorOptions(){
		$list = [];
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.Mrs::STATUS_PENDING);
		$mrss = Mrs::model()->findAll($criteria);
		if($mrss){
			foreach($mrss as $mrs){
				$vendor = Vendor::model()->findByPk($mrs->vendor_id);
				if($vendor){
					$list[$vendor->id] = $vendor->name;
				}
			}
		}
		Yii::log ( CVarDumper::dumpAsString ( $list ), CLogger::LEVEL_WARNING, '$list1' );
		asort($list);
		Yii::log ( CVarDumper::dumpAsString ( $list ), CLogger::LEVEL_WARNING, '$list2' );
		return $list;
	}
	public function AssignMrs($id){
       $existmrsdetail = MrsDetail::model()->findByPk($id);
		$organization = Organization::model()->find();
		$item = Item::model()->findByPk($this->item_id);
		$mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$this->vendor_id,
				'outlet_id'=>$this->outlet_id
		));
		if($mrs == null){
			$mrs = new Mrs();
		}
	
		$mrs->code = 'ddd';
		$mrs->mrs_date = date('Y-m-d');
		$mrs->mrs_req_date = date('Y-m-d');
		$mrs->outlet_id = $this->outlet_id;
		$mrs->vendor_id = $this->vendor_id;
		$mrs->organization_id = $organization->id;
		if($mrs->save()){
			$mrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$existmrsdetail->item_detail_id,
					'mrs_id'=>$mrs->id
			));
			
			if($mrsdetail == null){
				$mrsdetail = new MrsDetail();
			}
				
			$mrsdetail->req_qty = $item->reorder_qty;
			$mrsdetail->item_detail_id = $existmrsdetail->item_detail_id;
			$mrsdetail->item_id = $existmrsdetail->item_id;
			$mrsdetail->outlet_id = $existmrsdetail->outlet_id;
			$mrsdetail->mrs_id = $mrs->id;
			if($mrsdetail->save()){
				
			}else{
				print_r($mrsdetail->getErrors());exit;
			}
				
		}
		return true;
	}
}