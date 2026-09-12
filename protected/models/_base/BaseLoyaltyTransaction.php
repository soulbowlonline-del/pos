<?php


abstract class BaseLoyaltyTransaction extends GxActiveRecord
{

  public function beforeValidate()
  {
    return parent::beforeValidate();
  }

  public static function model($className = __CLASS__)
  {
    return parent::model($className);
  }

  public function tableName()
  {
    return '{{loyalty_transactions}}';
  }

  public function defaultScope()
  {
      return [
          // 'order' => 't.id DESC',
      ];
  }

  public function relations()
  {
    return array(
      'customer' => array(self::BELONGS_TO, 'Customer', 'customer_id'),
      'order' => array(self::BELONGS_TO, 'Order', 'order_id'),
      'createdBy' => array(self::BELONGS_TO, 'User', 'created_by'),
    );
  }
}
