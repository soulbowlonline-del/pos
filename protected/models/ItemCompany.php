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
Yii::import('application.models._base.BaseItemCompany');
class ItemCompany extends BaseItemCompany
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getCompanyOptions(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.ItemCompany::STATUS_ACTIVE);
		$criteria->order = 'title asc';
		if($this->id != ''){
			$criteria->addCondition('id !='.$this->id);
		}
		$categories = ItemCompany::model()->findAll($criteria);
		if($categories){
			foreach($categories as $category){
				$list[$category->id] = $category->title;
			}
		}
		return $list;
	}
	
	public function getParentValue($parent_id){
		$name = 'Not set';
		$category = ItemCompany::model()->findByPk($parent_id);
		if($category){
			$name =  $category->title;
		}
		return $name;
	}
	public static function getParentItemCompany($parent_id){
		$name = 'Not set';
		$category = ItemCompany::model()->findByPk($parent_id);
		if($category){
			$name =  $category->title;
		}
		return $name;
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
					$itemcomp_values = explode(',', $rows[$i]);
	
	
					$itemcompany = new ItemCompany();
						
					if (isset($arrays['Company']) || isset($arrays['ï»¿"Company"']) || isset($arrays['¥éË"Company"'])) {
	
						if (isset($arrays['Company'])) {
							$itemcompany->title = $itemcomp_values[$arrays['Company']];
								
						} else if(isset($arrays['ï»¿"Company"'])) {
							$itemcompany->title = $itemcomp_values[$arrays['ï»¿"Company"']];
						}else{
							$itemcompany->title = $itemcomp_values[$arrays['¥éË"Company"']];
						}
					}
	
						
	
					if (isset($arrays['Parent Company'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcomp_values[$arrays['Parent Company']]);
						$company = ItemCompany::model()->find($criteria);
						if($company){
							$itemcompany->parent_id =$company->id;
						}
	
					}
						
					if ($itemcompany->save()) {
	
							
					} else {
						print_R($itemcompany->getErrors());
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
	public function getCompWiseColumns($selectcolumns = array()){
		
		if(!empty($selectcolumns)){
			$selected = $selectcolumns;
		}else{
			$selected = array (
					'company' ,
					'amount',
	
	
			);
	
		}
	
		if($selected){
			foreach($selected as $select){
				if($select == 'company'){
					$columns[] = array (
							'label' => 'Company',
							'value' => function ($data) {
							return isset ( $data->title ) ? $data->title : "";
							}
							);
				}
				else if($select == 'amount'){
					$columns[] =array (
							'label' => 'Net Amount',
							'value' => function ($data) {
							return $data->getCompanyTotalAmount ();
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
	
	public function getCompanyItem_ids(){
		$item_ids = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('company_id ='.$this->id);
		$criteria->addCondition('status ='.Item::STATUS_ACTIVE);
		$items = Item::model()->findAll($criteria);
		if($items){
			foreach($items as $item){
				$item_ids[] = $item->id;
			}
		}
	
		return $item_ids;
	}
	
	public function getCompanyTotalAmount(){
		$item_ids = $this->getCompanyItem_ids();
		$total = 0;
		$criteria1 = new CDbCriteria();
		$criteria1->addInCondition('item_id', $item_ids);
		if((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')){
			$criteria1->addBetweenCondition('date(create_time)',Yii::app()->session['start_date'], Yii::app()->session['end_date']);
		}
		
		$orderitems = OrderItem::model()->findAll($criteria1);
	
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
					$criteria3->select = 'sum(total_amt) as total_amt';
					$criteria3->addCondition('item_id ='.$orderitem->item_id);
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