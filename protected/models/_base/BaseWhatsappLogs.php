<?php

 
/**
 *
 * @property integer $id
 * @property string $number
 * @property string $message_id
 * @property string $message
 * @property string $template_name
 * @property integer $status
 * @property integer $computer_name
 * @property integer $user_id
 * @property string $created_at
 *
 */
abstract class BaseWhatsappLogs extends GxActiveRecord {

	public $start_date;
	public $end_date;
	public $columns;
	public function beforeValidate()
	{
		return parent::beforeValidate();
	}

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{whatsapp_logs}}';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Whatsapp Logs|Whatsapp Logs', $n);
	}

	public static function representingColumn() {
		return 'create_time';
	}

	public function rules() {
		return array(
			array('number, message', 'required'),
			array('id', 'numerical', 'integerOnly'=>true),
			array('status, message_id, template_name, created_at', 'safe'),
			array('id, number, message, type_id, status, message_id, template_name, created_at', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'createUser' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			// 'id' => Yii::t('app', 'ID'),
			// 'amount' => Yii::t('app', 'Amount'),
			// 'advance_payment_id' => null,
			// 'type_id' => Yii::t('app', 'Type'),
			// 'status' => Yii::t('app', 'Status'),
			// 'create_time' => Yii::t('app', 'Create Time'),
			// 'update_time' => Yii::t('app', 'Update Time'),
			// 'advancePayment' => null,
		);
	}

	public function search() {
		
		$criteria = new CDbCriteria;

		if((Yii::app()->session['whatsapp_start_date'] != '') && (Yii::app()->session['whatsapp_end_date'] != '')){
			$criteria->addBetweenCondition('DATE(created_at)',Yii::app()->session['whatsapp_start_date'], Yii::app()->session['whatsapp_end_date']);
		} 

		$criteria->compare('id', $this->id);
		$criteria->compare('number', $this->number);
		$criteria->compare('template_name', $this->template_name);
		$criteria->compare('message_id', $this->message_id);
		$criteria->compare('message', $this->message);
		$criteria->compare('status', $this->status);
		$criteria->compare('user_id', $this->user_id);
		$criteria->compare('computer_name', $this->computer_name);
		$criteria->compare('created_at', $this->created_at, true);
		$criteria->order = 'id desc';
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

	public function searchExport() {
		
		$criteria = new CDbCriteria;

		if((Yii::app()->session['whatsapp_start_date'] != '') && (Yii::app()->session['whatsapp_end_date'] != '')){
			$criteria->addBetweenCondition('DATE(created_at)',Yii::app()->session['whatsapp_start_date'], Yii::app()->session['whatsapp_end_date']);
		} 

		$criteria->compare('id', $this->id);
		$criteria->compare('number', $this->number);
		$criteria->compare('template_name', $this->template_name);
		$criteria->compare('message_id', $this->message_id);
		$criteria->compare('message', $this->message);
		$criteria->compare('status', $this->status);
		$criteria->compare('user_id', $this->user_id);
		$criteria->compare('computer_name', $this->computer_name);
		$criteria->compare('created_at', $this->created_at, true);
		$criteria->order = 'id desc';
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort'=>array(
						'defaultOrder'=>'id DESC',
				),
		));
	}
}