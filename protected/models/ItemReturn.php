<?php

/**
 * Company: ToXSL Technologies Pvt. Ltd. < www.toxsl.com >
 * Author : Shiv Charan Panjeta < shiv@toxsl.com >
 */
 
/**
 * @property integer $id
 * @property double $discount_amt
 * @property double $other_charge
 * @property string $total_amt
 * @property integer $vendor_id
 * @property integer $outlet_id
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $credit_note_id
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemReturn');
class ItemReturn extends BaseItemReturn
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getColumns($selectcolumns = array()){
		if(!empty($selectcolumns)){
			$selected = $selectcolumns;
		}else{
			$selected = array (
					'gross_amt',
							'discount_amt',
							'total_amt',
							'vendor_id',
							'outlet_id',
							'credit_note_id'
	
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
							return isset ( $data->outlet ) ? $data->outlet : "";
							}
							);
				}else if($select == 'credit_note_id'){
					$columns[] = array (
							'label' => 'Credit Note',
							'value' => function ($data) {
							return isset ( $data->creditNote ) ? $data->creditNote : "";
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