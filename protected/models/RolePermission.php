<?php

 
/**
 * @property integer $id
 * @property integer $role_id
 * @property integer $permission_id
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseRolePermission');
class RolePermission extends BaseRolePermission
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function getRoleOptions(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.UserRole::STATUS_ACTIVE);
		$criteria->order = 'title asc';
		$roles = UserRole::model()->findAll($criteria);
	
		if($roles){
			foreach($roles as $role){
				$list[$role->id] = $role->title;
			}
		}
		return $list;
	}
	
	public function getPermissionOptions(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.UserRole::STATUS_ACTIVE);
		$criteria->order = 'title asc';
		$permissions = Permission::model()->findAll($criteria);
	
		if($permissions){
			foreach($permissions as $permission){
				$list[$permission->id] = $permission->title;
			}
		}
		return $list;
	}
	
	public function deleteOldPermissions($role_id){
		$rolepermissions = RolePermission::model()->deleteAllByAttributes(array('role_id'=>$role_id));
		return true;
	}
}