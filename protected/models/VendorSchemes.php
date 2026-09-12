<?php

 
/**
 * @property integer $id
 * @property integer $vendor_id
 * @property integer $item_id
 * @property integer $total_sale
 * @property string $start_date
 * @property string $end_date
 * @property double $discount
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseVendorSchemes');
class VendorSchemes extends BaseVendorSchemes
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getItems(){
		$string = '';
		$list = array();
		$item_ids = explode(',',$this->item_id);
		if(!empty($item_ids)){
			foreach($item_ids as $item_id){
				$item = Item::model()->findByPk($item_id);
				if($item){
				$list[] = $item->title;
				}
			}
			if(!empty($list)){
				$string = implode(',',$list);
			}
		}
		return $string;
	}
}