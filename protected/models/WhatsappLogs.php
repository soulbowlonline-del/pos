<?php

/**
 * @property integer $id
 * @property string $number
 * @property string $message_id
 * @property string $message
 * @property string $template_name
 * @property integer $status
 * @property integer $computer_name
 * @property integer $user_id
 * @property string $created_at
 */
Yii::import('application.models._base.BaseWhatsappLogs');
class WhatsappLogs extends BaseWhatsappLogs
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function getWhatapplogsColumns($selectcolumns = array()){
		if(!empty($selectcolumns)){
			$selected = $selectcolumns;
		}else{
			$selected = array (
					'username',
					'computer_name' ,
					'number',
					'template_name',
					'message_id',
					'message',
					'created_at',
			);
				
		}
	
		if($selected){
			foreach($selected as $select){
				if($select == 'username'){
					$columns[] = array (
							'label' => 'Username',
							'value' => function ($data) {
							return isset ( $data->createUser ) ? $data->createUser : "";
							}
							);
				}
				else if($select == 'computer_name'){
					$columns[] =array (
							'label' => 'Computer Name',
							'value' => function ($data) {
							return $data->computer_name;
							}
							);
				}else if($select == 'number'){
					$columns[] =array (
							'label' => 'Number',
							'value' => function ($data) {
							return $data->number;
							}
							);
				}else if($select == 'template_name'){
					$columns[] =array (
							'label' => 'Template Name',
							'value' => function ($data) {
							return $data->template_name;
							}
							);
				}
				else if($select == 'message_id'){
					$columns[] =array (
							'label' => 'Message ID',
							'value' => function ($data) {
							return $data->message_id;
							}
							);
				}
				else if($select == 'message'){
					$columns[] =array (
							'label' => 'Message',
							'value' => function ($data) {
							return $data->message;
							}
							);
				}
				else if($select == 'created_at'){
					$columns[] =array (
							'label' => 'Created At',
							'value' => function ($data) {
							return $data->created_at;
							}
							);
				}
				
				else{
					$columns[] = $select;
				}
			}
		}
	
		
	
	
		return $columns;
	}
}