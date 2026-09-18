<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Permission;
use app\models\RolePermission;
use app\models\UserRole;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/RolePermissionController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class RolePermissionController extends BaseUiController {
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionAjaxUpdate() {
		$option = '';
		$alreadypermissions = [];
		if (isset ( $_POST ['role_id'] )) {
			
			$criteria = new CDbCriteria();
			$criteria->addCondition('status ='.UserRole::STATUS_ACTIVE);
			$criteria->order = 'title asc';
			$permissions = Permission::model()->findAll($criteria);
			//$option .= '<div class="row">';
			if ($permissions) {
				foreach ( $permissions as $permission ) {
					$rolepermission = RolePermission::findOne( [
							'role_id' => $_POST ['role_id'],
							'permission_id' => $permission->id 
					] );
					if ($rolepermission) {
						
						$option .= '<div class="col-md-3"><label class="checkbox-inline"><input type="checkbox" class="checkBox"  name="RolePermission[permission_id][]" value="' . $permission->id . '"  checked >' . $permission->title . '</label></div>';
					} else {
						$option .= '<div class="col-md-3"><label class="checkbox-inline"><input type="checkbox" class="checkBox" name="RolePermission[permission_id][]"  value="' . $permission->id  . '">' . $permission->title . '</label></div>';
					}
					}
			} else {
				// $option .= '<option value="">-Select-</option>';
			}
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		echo $option;
	}
	public function actionView($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		return $this->render( 'view', [
				'model' => $model 
		] );
	}
	public function actionCreate() {
		$model = new RolePermission ();
		
		$this->performAjaxValidation( $model, 'role-permission-form' );
		$set = true;
		if (Yii::$app->request->post('RolePermission') !== null) {
			if (isset ( $_POST ['RolePermission'] ['permission_id'] ) && isset ( $_POST ['RolePermission'] ['role_id'] )) {
				$model->deleteOldPermissions($_POST ['RolePermission'] ['role_id']);
				$permissions = $_POST ['RolePermission'] ['permission_id'];
				if ($permissions) {
					foreach ( $permissions as $permission ) {
						$model = new RolePermission ();
						$model->permission_id = $permission;
						$model->role_id = $_POST ['RolePermission'] ['role_id'];
						if(!$model->save ()){
							$set = false;
						}
					}
					if($set == true){
					Yii::$app->user->setFlash('success','Permissions are successfully assigned.');
					}else{
						Yii::$app->user->setFlash('error','Please try again.');
					}
					return $this->redirect( [
							'admin' 
					] );
				}
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'create', [
				'model' => $model 
		] );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation( $model, 'role-permission-form' );
		
		if (Yii::$app->request->post('RolePermission') !== null) {
			$model->load(Yii::$app->request->post());
			
			if ($model->save ()) {
				return $this->redirect( [
						'view',
						'id' => $model->id 
				] );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'update', [
				'model' => $model 
		] );
	}
	public function actionDelete($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		if (Yii::$app->request->isPost) {
			$this->loadModel($id)->delete ();
			
			if (! Yii::$app->request->isAjax)
				return $this->redirect( [
						'admin' 
				] );
		} else
			throw new BadRequestHttpException(Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new ActiveDataProvider(['query' => RolePermission::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            'sort' => ['defaultOrder' => RolePermission::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', [
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionSearch() {
		$model = new RolePermission(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (Yii::$app->request->get('RolePermission') !== null) {
			$model->load(Yii::$app->request->queryParams);
			return $this->renderPartial( '_list', [
					'dataProvider' => $model->search (),
					'model' => $model 
			] );
		}
		
		return $this->renderPartial( '_search', [
				'model' => $model 
		] );
	}
	public function actionAdmin() {
		$model = new RolePermission(['scenario' => 'search']);
		if (! ($model->checkPermission ( 'rolePermission/admin' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		$this->updateMenuItems ( $model );
		
		if (Yii::$app->request->get('RolePermission') !== null)
			$model->load(Yii::$app->request->queryParams);
		
		return $this->render( 'admin', [
				'model' => $model 
		] );
	}
	/*
	 * protected function processActions($model = null)
	 * {
	 * parent::processActions($model);
	 * //$this->actions [] = array('label'=>'Add Skill', 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new RolePermission ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ),
							'url' => Ui::to('rolePermission/view', ['id' => $model->id]),
							'icon' => 'icon-plus icon-white' 
					];
				}
			case 'create' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'List' ),
							'url' => [
									'index' 
							],
							'icon' => 'icon-th-list icon-white' 
					];
				}
				break;
			case 'index' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			case 'admin' :
				{
					// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							//'visible'=> $model->checkPermission ("rolePermission/view")=="true",
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			default :
			case 'view' :
				{
					// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					// $this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => Ui::to('rolePermission/update', ['id' => $model->id]),
							'icon' => 'icon-edit icon-white' 
					];
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
}