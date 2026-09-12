<?php


 
/**
 * @property integer $id
 * @property string $bill_no
 * @property string $image_file1
 * @property string $image_file2
 * @property string $image_file3
 * @property integer $type_id
 * @property integer $status
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $po_id
 */
Yii::import('application.models._base.BaseBill');
class Bill extends BaseBill
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getNewImage($image)
	{
		if($image != ''){
		return CHtml::link('Download Image',array('user/download','file'=>$image));
		}
		return '';
			
	}
}