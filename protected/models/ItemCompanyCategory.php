<?php


 
/**
 * @property integer $id
 * @property string $title
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $company_id
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemCompanyCategory');
class ItemCompanyCategory extends BaseItemCompanyCategory
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getCompanyOptions(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.ItemCategory::STATUS_ACTIVE);
		$criteria->order = 'title asc';
		if($this->id != ''){
			$criteria->addCondition('id !='.$this->id);
		}
		$categories = ItemCompany::model()->findAll($criteria);
		if($categories){
			foreach($categories as $category){
				$list[$category->id] = $category->title;
			}
		}
		return $list;
	}
}