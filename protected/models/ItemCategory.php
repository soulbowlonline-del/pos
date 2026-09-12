<?php

 
/**
 * @property integer $id
 * @property string $title
 * @property integer $parent_id
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemCategory');
class ItemCategory extends BaseItemCategory
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	public function getCategoryOptions(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.ItemCategory::STATUS_ACTIVE);
		$criteria->order = 'title asc';
		if($this->id != ''){
			$criteria->addCondition('id !='.$this->id);
		}
		$categories = ItemCategory::model()->findAll($criteria);
		if($categories){
			foreach($categories as $category){
				$list[$category->id] = $category->title;
			}
		}
		return $list;
	}
	
	public static function getParentItemCat($parent_id){
		$name = 'Not set';
		$category = ItemCategory::model()->findByPk($parent_id);
		if($category){
			$name =  $category->title;
		}
		return $name;
	}
	public function getParentValue($parent_id){
		$name = 'Not set';
		$category = ItemCategory::model()->findByPk($parent_id);
		if($category){
			$name =  $category->title;
		}
		return $name;
	}
	
	public function getDeptWiseColumns($selectcolumns = array()){
		if(!empty($selectcolumns)){
			$selected = $selectcolumns;
		}else{
			$selected = array (
					'category' ,
					'amount',
						
	
			);
	
		}
	
		if($selected){
			foreach($selected as $select){
				if($select == 'category'){
					$columns[] = array (
							'label' => 'Category',
							'value' => function ($data) {
							return isset ( $data->title ) ? $data->title : "";
							}
							);
				}
				else if($select == 'amount'){
					$columns[] =array (
							'label' => 'Net Amount',
							'value' => function ($data) {
							return $data->getCategoryTotalAmount ();
							}
							);
				}
	
				else{
					$columns[] = $select;
				}
			}
		}
	
	
	
	
		return $columns;
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
					$itemcat_values = explode(',', $rows[$i]);
						
	
					$itemcategory = new ItemCategory();
					
					if (isset($arrays['Category']) || isset($arrays['ï»¿"Category"']) || isset($arrays['¥éË"Category"'])) {
						
						if (isset($arrays['Category'])) {
							$itemcategory->title = $itemcat_values[$arrays['Category']];
							
						} else if(isset($arrays['ï»¿"Category"'])) {
							$itemcategory->title = $itemcat_values[$arrays['ï»¿"Category"']];
						}else{
							$itemcategory->title = $itemcat_values[$arrays['¥éË"Category"']];
						}
					}
				
					
	
					if (isset($arrays['Parent Category'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Parent Category']]);
						$category = ItemCategory::model()->find($criteria);
						if($category){
							$itemcategory->parent_id =$category->id;
						}
	
					}
					
					if ($itemcategory->save()) {
	
					
					} else {
						print_R($itemcategory->getErrors());
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
	
	public function getCategoryItem_ids(){
		$item_ids = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('category_id ='.$this->id);
		$criteria->addCondition('status ='.Item::STATUS_ACTIVE);
		$items = Item::model()->findAll($criteria);
		if($items){
			foreach($items as $item){
				$item_ids[] = $item->id;
			}
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->id ), CLogger::LEVEL_WARNING, '$item_category_id' );
		Yii::log ( CVarDumper::dumpAsString ( $item_ids ), CLogger::LEVEL_WARNING, '$item_ids' );
		return $item_ids;
	}

	public function getCategoryTotalAmount(){
		$item_ids = $this->getCategoryItem_ids();
		Yii::log ( CVarDumper::dumpAsString ($item_ids), CLogger::LEVEL_WARNING, '$$item_ids' );
		$total = 0;
		$criteria1 = new CDbCriteria();
		$criteria1->addInCondition('item_id', $item_ids);
		if((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')){
			$criteria1->addBetweenCondition('date(create_time)',Yii::app()->session['start_date'], Yii::app()->session['end_date']);
		}
		Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['start_date']), CLogger::LEVEL_WARNING, '$start_date' );
		Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['end_date']), CLogger::LEVEL_WARNING, '$end_date' );
		$orderitems = OrderItem::model()->findAll($criteria1);
		Yii::log ( CVarDumper::dumpAsString ( $orderitems), CLogger::LEVEL_WARNING, '$orderitems' );
		if($orderitems){
				
			foreach ($orderitems as $orderitem){
				$qty = $orderitem->qty;
				$refund = 0;
				$criteria = new CDbCriteria();
				$criteria->addCondition('order_id ='.$orderitem->order_id);
				if((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')){
					$criteria->addBetweenCondition('date(create_time)',Yii::app()->session['start_date'], Yii::app()->session['end_date']);
				}
				$orderRefund = OrderRefund::model()->find($criteria);
				if($orderRefund){
					$criteria3 = new CDbCriteria();
					$criteria3->addCondition('order_refund_id ='.$orderRefund->id);
					$criteria3->addCondition('item_detail_id ='.$orderitem->item_detail_id);
					if((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')){
						$criteria3->addBetweenCondition('date(create_time)',Yii::app()->session['start_date'], Yii::app()->session['end_date']);
					}
					$criteria3->addCondition('item_id ='.$orderitem->item_id);
					$criteria3->select = 'sum(total_amt) as total_amt';
					$orderRefundItem = OrderRefundItem::model()->find($criteria3);
					if($orderRefundItem){
						$refund = $refund + ($orderRefundItem->total_amt);
					}
					/* if($orderRefundItems){
						foreach($orderRefundItems as $orderRefundItem){
							$refund = $refund + ($orderRefundItem->total_amt);
						}
						
						
					} */
	
				}
				$amt = ($orderitem->total_amt) - ($refund);
				$total = $total + $amt;
	
			}
		}
		return round($total);
	}
}