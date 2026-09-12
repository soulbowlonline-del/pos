<?php

/**
 * This is the model class for table "tbl_item_new_tax".
 *
 * The followings are the available columns in table 'tbl_item_new_tax':
 * @property integer $id
 * @property string $title
 * @property string $hsn_code
 * @property integer $tax
 */
class TblItemNewTax extends GxActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'tbl_item_new_tax';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('title, hsn_code, tax', 'required'),
			array('tax', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>255),
			array('hsn_code', 'length', 'max'=>255),
			array('id, title, hsn_code, tax', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'title' => 'Title',
			'hsn_code' => 'HSN Code',
			'tax' => 'Tax (%)',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('hsn_code',$this->hsn_code,true);
		$criteria->compare('tax',$this->tax);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return TblItemNewTax the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
