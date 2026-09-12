<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property double $amount
 * @property string $start_date
 * @property string $end_date
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseDiscount');
class Discount extends BaseDiscount
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getItemOptions($vendor_id=null){
		$item_ids = array();
		$role = UserRole::model()->findByAttributes(array('title'=>'Admin'));
		$user = Yii::app()->user->model;
		$criteria = new CDbCriteria();
		if($user->role_id != $role->id){
			$itemvendor_ids = array();
			$vendor = Vendor::model()->findByAttributes(array('create_user_id'=>$user->id));
			if($vendor){
				$itemvendors = ItemVendor::model()->findAllByAttributes(array('vendor_id'=>$vendor->id));
				if($itemvendors){
	
					foreach($itemvendors as $itemvendor){
						$itemvendor_ids[] = $itemvendor->item_detail_id;
					}
				}
			}
			$criteria->addInCondition('item_id', $itemvendor_ids);
		}
		$itemdetails = ItemDetail::model()->findAll($criteria);
		if($itemdetails != null)
		{
			foreach($itemdetails as $itemdetail)
			{
				$item = Item::model()->findByPk($itemdetail->item_id);
				$item_ids[$itemdetail->id] = $item->title.'('.$itemdetail->bar_code.')';
			}
		}
	
		return $item_ids;
	}
	public function setAllValues($rows) {
	
		$output = 0;
		$count = count($rows);
	
			
		if ($count > 1) {
	
			$o = explode(',', $rows[0]);
			$arrays = array_flip($o);
			$set = true;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				for ($i = 1; $i < $count; $i++) {
					$discount_values = explode(',', $rows[$i]);
	
	
					$discount = new Discount();
	
					if (isset($arrays['Title']) || isset($arrays['ï»¿"Title"']) || isset($arrays['¥éË"Title"'])) {
	
						if (isset($arrays['Title'])) {
							$discount->title = $discount_values[$arrays['Title']];
	
						} else if(isset($arrays['ï»¿"Title"'])) {
							$discount->title = $discount_values[$arrays['ï»¿"Title"']];
						}else{
							$discount->title = $discount_values[$arrays['¥éË"Title"']];
						}
					}
	
	
	
					if (isset($arrays['Amount'])) {
						$discount->amount =$discount_values[$arrays['Amount']];
					}
				
					if (isset($arrays['Start Date'])) {
						$discount->start_date = date('Y-m-d',strtotime($discount_values[$arrays['Start Date']]));
					}
					if (isset($arrays['End Date'])) {
						$discount->end_date =date('Y-m-d',strtotime($discount_values[$arrays['End Date']]));
					}
				
	
					if ($discount->save()) {
	
							
					} else {
						print_R($discount->getErrors());
						exit;
						$set = false;
					}
				}
				if ($set == true) {
					$transaction->commit();
					return 1;
				}
			} catch (Exception $e) {
				$transaction->rollback();
			}
		}
		return $output;
	}
	
	
	
	
	public function toArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
				
			$batch_no =  array();
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			$json_entry ['title'] = $model->title;
			$json_entry ['amount'] = $model->amount;
			$json_entry ['applicable_amt'] = $model->applicable_amt;
			$json_entry ['type_id'] = $model->type_id;
			
			$json_entry ['type_name'] = $model->getTypeOptions( $model->type_id);
			$json_entry ['discount_type'] = $model->discount_type;
			$json_entry ['discount_type_name'] = $model->getDiscountTypeOptions( $model->discount_type);
			$json_entry ['start_date'] = $model->start_date;
			$json_entry ['end_date'] = $model->end_date;	
			$json_entry ['start_time'] = $model->start_time;
			$json_entry ['end_time'] = $model->end_time;
		
				
				
			
		
			
	
		}
		return $json_entry;
	}
	
	public function getItemDetailIds(){
		$list = array();
		$itemDiscounts = ItemDiscount::model()->findAllByAttributes(array('discount_id'=>$this->id));
		if($itemDiscounts){
			foreach($itemDiscounts as $itemDiscount){
				$list[] = $itemDiscount->item_detail_id;
			}
		}
		
		return $list;
	}
	public function removeItemDetailIds(){
		
		$itemDiscounts = ItemDiscount::model()->deleteAllByAttributes(array('discount_id'=>$this->id));
		return true;
	}
	
}