<?php

 
/**
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $computer_name
 * @property string $user_email
 * @property integer $item_id
 * @property string $bar_code
 * @property integer $is_coupon
 * @property integer $qty
 * @property integer $sale_rate
 * @property integer $base_price
 * @property integer $mrp
 * @property string $item_detail
 * @property datetime $created_at	
 */
abstract class BaseScannedItems extends GxActiveRecord {

	public $start_date;
	public $end_date;
	public $columns;
	const STATUS_ACTIVE = 0;
	const STATUS_INACTIVE = 1;
	public static function getStatusOptions($id = null)
	{
		$list = array("Active","InActive");
		if ($id == null )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
	}
	public static function getTypeOptions($id = null)
	{
		$list = array("TYPE1","TYPE2","TYPE3");
		if ($id == null )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
	}
 	
	
	public function beforeValidate()
	{
		return parent::beforeValidate();
	}

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{scanned_items}}';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'ScannedItem|ScannedItems', $n);
	}

	public static function representingColumn() {
		return 'computer_name';
	}

	public function rules() {
		return array(
			array('computer_name, user_id, item_id', 'required'),
			array('user_id, item_id', 'numerical', 'integerOnly'=>true),
			array('computer_name', 'length', 'max'=>255),
			array('is_coupon, created_at', 'safe'),
			array('is_coupon', 'default', 'setOnEmpty' => true, 'value' => null),
			array('computer_name, bar_code, user_id, created_at', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'createUser' => array(self::BELONGS_TO, 'User', 'user_id'),
			'getItemDetail' => array(self::BELONGS_TO, 'ItemDetail', 'item_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			// 'id' => Yii::t('app', 'ID'),
			// 'title' => Yii::t('app', 'Title'),
			// 'type_id' => Yii::t('app', 'Type'),
			// 'state_id' => null,
			// 'status' => Yii::t('app', 'Status'),
			// 'create_time' => Yii::t('app', 'Create Time'),
			// 'update_time' => Yii::t('app', 'Update Time'),
			// 'create_user_id' => null,
			// 'updated_by' => null,
			// 'createUser' => null,
			// 'state' => null,
			// 'updatedBy' => null,
			// 'orders' => null,
			// 'orderHolds' => null,
			// 'orderRefunds' => null,
			// 'organizations' => null,
			// 'outlets' => null,
			// 'vendors' => null,
		);
	}

	public function scannedItemsearch() {
		$criteria = new CDbCriteria;
		//  if(($this->start_date != '' && $this->start_date != null) && ($this->end_date != '' && $this->end_date != null)){
		// 	$criteria->addBetweenCondition('DATE(created_at)', $this->start_date, $this->end_date);
		// }
		if((Yii::app()->session['scanned_start_date'] != '') && (Yii::app()->session['scanned_end_date'] != '')){
			$criteria->addBetweenCondition('DATE(created_at)',Yii::app()->session['scanned_start_date'], Yii::app()->session['scanned_end_date']);
		} 

		// $criteria->select ='t.*, sum(total_amt) as total_amt ';
		// $criteria->group = 'user_id';
		$criteria->compare('id', $this->id);
		$criteria->compare('user_id', $this->user_id);
		$criteria->compare('computer_name', $this->computer_name);
		$criteria->compare('user_email', $this->user_email);
		$criteria->compare('item_id', $this->item_id);
		$criteria->compare('bar_code', $this->bar_code);
		$criteria->compare('is_coupon', $this->is_coupon);
		$criteria->compare('qty', $this->qty); 
	 	$criteria->compare('sale_rate', $this->sale_rate);
		$criteria->compare('base_price', $this->base_price);
		$criteria->compare('mrp', $this->mrp); 
		$criteria->compare('item_detail', $this->item_detail);
		$criteria->compare('created_at', $this->created_at, true);
		
		return new CActiveDataProvider($this, array(
				'criteria' => $criteria,
				'pagination' => array (
						'pageSize' => '20'
				),
				'sort'=>array(
						'defaultOrder'=>'id DESC',
				),
		));
	}

	public function scannedItemsSearchExport() {
		$criteria = new CDbCriteria;
		//  if(($this->start_date != '' && $this->start_date != null) && ($this->end_date != '' && $this->end_date != null)){
		// 	$criteria->addBetweenCondition('DATE(created_at)', $this->start_date, $this->end_date);
		// }
		if((Yii::app()->session['scanned_start_date'] != '') && (Yii::app()->session['scanned_end_date'] != '')){
			$criteria->addBetweenCondition('DATE(created_at)',Yii::app()->session['scanned_start_date'], Yii::app()->session['scanned_end_date']);
		} 

		// $criteria->select ='t.*, sum(total_amt) as total_amt ';
		// $criteria->group = 'user_id';
		$criteria->compare('id', $this->id);
		$criteria->compare('user_id', $this->user_id);
		$criteria->compare('computer_name', $this->computer_name);
		$criteria->compare('user_email', $this->user_email);
		$criteria->compare('item_id', $this->item_id);
		$criteria->compare('bar_code', $this->bar_code);
		$criteria->compare('is_coupon', $this->is_coupon);
		$criteria->compare('qty', $this->qty); 
	 	$criteria->compare('sale_rate', $this->sale_rate);
		$criteria->compare('base_price', $this->base_price);
		$criteria->compare('mrp', $this->mrp); 
		$criteria->compare('item_detail', $this->item_detail);
		$criteria->compare('created_at', $this->created_at, true);
		
		return new CActiveDataProvider($this, array(
				'criteria' => $criteria,
				'pagination' => array (
						'pageSize' => '20'
				),
				'sort'=>array(
						'defaultOrder'=>'id DESC',
				),
		));
	}
}