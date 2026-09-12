<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property string $link
 * @property string $email
 * @property integer $contact_no
 * @property integer $secondary_contact_no
 * @property string $address
 * @property string $tax
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $city_id
 * @property integer $state_id
 * @property integer $country_id
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseOrganization');
class Organization extends BaseOrganization
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
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
					$organization_values = explode(',', $rows[$i]);
	
						
					$organization = new Organization();
	
					if (isset($arrays['Title']) || isset($arrays['ï»¿"Title"']) || isset($arrays['¥éË"Title"'])) {
	
						if (isset($arrays['Title'])) {
							$organization->title = $organization_values[$arrays['Title']];
	
						} else if(isset($arrays['ï»¿"Title"'])) {
							$organization->title = $organization_values[$arrays['ï»¿"Title"']];
						}else{
							$organization->title = $organization_values[$arrays['¥éË"Title"']];
						}
					}
	
	
					if (isset($arrays['Link'])) {
							
						$organization->link =$organization_values[$arrays['Link']];
					}
					if (isset($arrays['Email'])) {
							
						$organization->email =$organization_values[$arrays['Email']];
					}
					
					if (isset($arrays['Contact No'])) {
							
						$organization->contact_no =$organization_values[$arrays['Contact No']];
					}
						
					if (isset($arrays['Address'])) {
							
						$organization->address =$organization_values[$arrays['Address']];
					}
					if (isset($arrays['City'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$organization_values[$arrays['City']]);
						$city = City::model()->find($criteria);
						if($city){
							$organization->city_id =$city->id;
						}
							
					}
					if (isset($arrays['State'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$organization_values[$arrays['State']]);
						$state = State::model()->find($criteria);
						if($state){
							$organization->state_id =$state->id;
						}
							
					}
					if (isset($arrays['Country'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$organization_values[$arrays['Country']]);
						$country = Country::model()->find($criteria);
						if($country){
							$organization->country_id =$country->id;
						}
							
					}
					if ($organization->save()) {
	
							
					} else {
						print_R($organization->getErrors());
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
}