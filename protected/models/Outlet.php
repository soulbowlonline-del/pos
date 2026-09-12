<?php

 
/**
 * @property integer $id
 * @property string $title
 * @property string $email
 * @property integer $contact_no
 * @property integer $secondary_contact_no
 * @property string $address
 * @property string $tax_no
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $city_id
 * @property integer $state_id
 * @property integer $country_id
 * @property integer $organization_id
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseOutlet');
class Outlet extends BaseOutlet
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}