<?php


 
/**
 * @property integer $id
 * @property integer $code
 * @property string $name
 * @property string $email
 * @property integer $contact_no
 * @property integer $gender_id
 * @property string $date_of_birth
 * @property string $date_of_joining
 * @property string $permanent_address
 * @property string $temp_address
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $designation_id
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseEmp');
class Emp extends BaseEmp
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	
	public function getShiftOptions(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.Shift::STATUS_ACTIVE);
		$shifts = Shift::model()->findAll($criteria);
		if($shifts){
			foreach($shifts as $shift){
				$list[$shift->id] = $shift->title;
			}
		}
		return $list;
	}
	
	
	public function getRoleValues(){
		$string = '';
		$list = array();
		$role_ids = explode(',',$this->role_id);
		if(!empty($role_ids)){
			foreach($role_ids as $role_id){
				
					$list[] = $this->getRoleOptions($role_id);
				
			}
			if(!empty($list)){
				$string = implode(',',$list);
			}
		}
		return $string;
	}
	public function getShifts(){
		$shift_ids = array();
		$shifts = EmpShift::model()->findAllByAttributes(array('emp_id'=>$this->id));
		if($shifts){
			foreach($shifts as $shift){
				$shift_ids[] = $shift->shift_id;
			}
		}
		return $shift_ids;
	}
	
	public function removeShifts(){
		EmpShift::model()->deleteAllByAttributes(array('emp_id'=>$this->id));
		return true;
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
					$itemcat_values = explode(',', $rows[$i]);
	
	
					$emp = new Emp();
	
					if (isset($arrays['Code']) || isset($arrays['ï»¿"Code"']) || isset($arrays['¥éË"Code"'])) {
	
						if (isset($arrays['Code'])) {
							$emp->code = $itemcat_values[$arrays['Code']];
	
						} else if(isset($arrays['ï»¿"Code"'])) {
							$emp->code = $itemcat_values[$arrays['ï»¿"Code"']];
						}else{
							$emp->code = $itemcat_values[$arrays['¥éË"Code"']];
						}
					}
	
					if (isset($arrays['Name'])) {
	
						$emp->name =$itemcat_values[$arrays['Name']];
					}
	
					if (isset($arrays['Email'])) {
							
						$emp->email =$itemcat_values[$arrays['Email']];
					}
					if (isset($arrays['Date Of Birth'])) {
							
						$emp->date_of_birth = date('Y-m-d',strtotime($itemcat_values[$arrays['Date Of Birth']]));
					}
					if (isset($arrays['Date Of Joining'])) {
							
						$emp->date_of_joining = date('Y-m-d',strtotime($itemcat_values[$arrays['Date Of Joining']]));
					}
					if (isset($arrays['Gender'])) {
					
						if($itemcat_values[$arrays['Gender']] == 'Male'){
						$emp->gender_id = 0;
						}else if($itemcat_values[$arrays['Gender']] == 'Female'){
							$emp->gender_id = 1;
						}else{
							$emp->gender_id = 2;
						}
					}
					if (isset($arrays['Permanent Address'])) {
							
						$emp->permanent_address = $itemcat_values[$arrays['Permanent Address']];
					}
					if (isset($arrays['Temp Address'])) {
							
						$emp->temp_address = $itemcat_values[$arrays['Temp Address']];
					}
					if (isset($arrays['Contact No'])) {
							
						$emp->contact_no = $itemcat_values[$arrays['Contact No']];
					}
					
					if (isset($arrays['City'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['City']]);
						$city = City::model()->find($criteria);
						if($city){
							$emp->city_id =$city->id;
						}
							
					}
					if (isset($arrays['State'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['State']]);
						$state = State::model()->find($criteria);
						if($state){
							$emp->state_id =$state->id;
						}
							
					}
					if (isset($arrays['Country'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Country']]);
						$country = Country::model()->find($criteria);
						if($country){
							$emp->country_id =$country->id;
						}
							
					}
					if (isset($arrays['Temp City'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Temp City']]);
						$city = City::model()->find($criteria);
						if($city){
							$emp->temp_city_id =$city->id;
						}
							
					}
					if (isset($arrays['Temp State'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Temp State']]);
						$state = State::model()->find($criteria);
						if($state){
							$emp->temp_state_id =$state->id;
						}
							
					}
					if (isset($arrays['Temp Country'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Temp Country']]);
						$country = Country::model()->find($criteria);
						if($country){
							$emp->temp_country_id =$country->id;
						}
							
					}
					if (isset($arrays['Designation'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Designation']]);
						$designation = Designation::model()->find($criteria);
						if($designation){
							$emp->designation_id =$designation->id;
						}
							
					}
					if (isset($arrays['Shift'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Shift']]);
						$shift = Shift::model()->find($criteria);
						if($shift){
							$emp->shift_id =$shift->id;
						}
					
					}
					if ($emp->save()) {
						$empshift = new EmpShift();
						if (isset($arrays['Shift'])) {
							$criteria = new CDbCriteria();
							$criteria->compare('title',$itemcat_values[$arrays['Shift']]);
							$shift = Shift::model()->find($criteria);
							if($shift){
								$empshift->shift_id =$shift->id;
							}
								
						}
						$empshift->emp_id =$emp->id;
						$empshift->save();
							
						
							
					} else {
						print_R($emp->getErrors());
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