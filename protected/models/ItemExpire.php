<?php

/**
 * Company: ToXSL Technologies Pvt. Ltd. < www.toxsl.com >
 * Author : Shiv Charan Panjeta < shiv@toxsl.com >
 */
 
/**
 * @property integer $id
 * @property integer $item_id
 * @property integer $item_detail_id
 * @property string $mrp
 * @property string $sale_rate
 * @property integer $free
 * @property integer $qty
 * @property string $total_amt
 * @property integer $vendor_id
 * @property integer $outlet_id
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemExpire');
class ItemExpire extends BaseItemExpire
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getColumns($selectcolumns = array()){
		if(!empty($selectcolumns)){
			$selected = $selectcolumns;
		}else{
			$selected = array (
					'vendor_id',
							'outlet_id',
					'date',
							'amount'
	
			);
				
		}
	
		if($selected){
			foreach($selected as $select){
				if($select == 'vendor_id'){
					$columns[] = array (
							'label' => 'Vendor',
							'value' => function ($data) {
							return isset ( $data->vendor ) ? $data->vendor : "";
							}
							);
				}else if($select == 'outlet_id'){
					$columns[] = array (
							'label' => 'Outlet',
							'value' => function ($data) {
							return isset ( $data->outlet ) ? $data->vendor : "";
							}
							);
				}else if($select == 'amount'){
					$columns[] = array (
							'label' => 'Amount',
							'value' => function ($data) {
							return isset ( $data->total_amt ) ? $data->total_amt : "";
							}
							);
				}else if($select == 'date'){
					$columns[] = array (
							'label' => 'Date',
							'value' => function ($data) {
							return isset ( $data->create_time ) ? date('Y-m-d',strtotime($data->create_time )) : "";
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
}