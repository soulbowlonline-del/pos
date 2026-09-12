<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property integer $item_id
 * @property integer $item_detail_id
 * @property integer $item_category_id
 * @property integer $item_company_id
 * @property integer $qty
 * @property integer $stock_qty
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseFreeItem');
class FreeItem extends BaseFreeItem
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getItemOptions($vendor_id = null){
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
				Yii::log ( CVarDumper::dumpAsString ( $itemdetail ), CLogger::LEVEL_WARNING, '$itemdetail' );
				$item = Item::model()->findByPk($itemdetail->item_id);
				if($item){
				$item_ids[$itemdetail->id] = $item->title.'('.$itemdetail->bar_code.')';
				}
			}
		}
	
		return $item_ids;
	}
}