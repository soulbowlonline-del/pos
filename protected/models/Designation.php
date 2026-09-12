<?php

 
/**
 * @property integer $id
 * @property string $title
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseDesignation');
class Designation extends BaseDesignation
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
					$designation_values = explode(',', $rows[$i]);
	
					
					$designation = new Designation();
	
					if (isset($arrays['Title']) || isset($arrays['ï»¿"Title"']) || isset($arrays['¥éË"Title"'])) {
	
						if (isset($arrays['Title'])) {
							$designation->title = $designation_values[$arrays['Title']];
	
						} else if(isset($arrays['ï»¿"Title"'])) {
							$designation->title = $designation_values[$arrays['ï»¿"Title"']];
						}else{
							$designation->title = $designation_values[$arrays['¥éË"Title"']];
						}
					}
	

	
					if ($designation->save()) {
	
							
					} else {
						print_R($designation->getErrors());
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