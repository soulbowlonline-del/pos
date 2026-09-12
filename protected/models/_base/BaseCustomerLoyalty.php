<?php


abstract class BaseCustomerLoyalty extends GxActiveRecord
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
    return '{{customer_loyalty}}';
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
      'customer' => array(self::BELONGS_TO, 'Customer', 'customer_id', 'together' => false),
      'transactions' => array(self::HAS_MANY, 'LoyaltyTransaction', 'customer_id'),
    );
  }
}
