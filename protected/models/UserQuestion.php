<?php


 
/**
 * @property integer $id
 * @property integer $question_id
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseUserQuestion');
class UserQuestion extends BaseUserQuestion
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getQuestionOptions(){
		$list = array();
				$questions = Question::model()->findAllByAttributes(array('status'=>UserRole::STATUS_ACTIVE));
		if($questions){
			foreach($questions as $question){
				$list[$question->id] = $question->title;
			}
		}
		return $list;
	}
	
	public function getUserQuestionOptions($id){
		$list = array();
		$ids = array();
		$criteria = new CDbCriteria();
		if($id != null){
			$userques = UserQuestion::model()->findAllByAttributes(array('create_user_id'=>$id));
			if($userques){
				foreach($userques as $userque){
					$ids[] = $userque->question_id;
				}
			}
			if(!empty($ids))
			$criteria->addInCondition('id', $ids);
		}
		$questions = Question::model()->findAll($criteria);
		if($questions){
			foreach($questions as $question){
				$list[$question->id] = $question->title;
			}
		}
		return $list;
	}
}