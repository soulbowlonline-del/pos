<?php
class RolePermissionController extends GxController {
	public function filters() {
		return array (
				'accessControl' 
		);
	}
	public function accessRules() {
		return array (
				array (
						'allow',
						'actions' => array (
								//'index',
								//'view',
						/* 'download', 'thumbnail' */),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'index',
								'create',
								//'update',
								'search',
								'admin',
								'delete',
								'ajaxUpdate'
						),
						'users' => array (
								'@' 
						) 
				),
				/*array('allow', 
					'actions'=>array('admin','delete'),
					'expression'=>'Yii::app()->user->isAdmin',
					),*/
				array (
						'deny',
						'users' => array (
								'*' 
						) 
				) 
		);
	}
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionAjaxUpdate() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['role_id'] )) {
			
			$criteria = new CDbCriteria();
			$criteria->addCondition('status ='.UserRole::STATUS_ACTIVE);
			$criteria->order = 'title asc';
			$permissions = Permission::model()->findAll($criteria);
			//$option .= '<div class="row">';
			if ($permissions) {
				foreach ( $permissions as $permission ) {
					$rolepermission = RolePermission::model ()->findByAttributes ( array (
							'role_id' => $_POST ['role_id'],
							'permission_id' => $permission->id 
					) );
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
		$model = $this->loadModel ( $id, 'RolePermission' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionCreate() {
		$model = new RolePermission ();
		
		$this->performAjaxValidation ( $model, 'role-permission-form' );
		$set = true;
		if (isset ( $_POST ['RolePermission'] )) {
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
					Yii::app()->user->setFlash('success','Permissions are successfully assigned.');
					}else{
						Yii::app()->user->setFlash('error','Please try again.');
					}
					$this->redirect ( array (
							'admin' 
					) );
				}
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model 
		) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'RolePermission' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'role-permission-form' );
		
		if (isset ( $_POST ['RolePermission'] )) {
			$model->setAttributes ( $_POST ['RolePermission'] );
			
			if ($model->save ()) {
				$this->redirect ( array (
						'view',
						'id' => $model->id 
				) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'update', array (
				'model' => $model 
		) );
	}
	public function actionDelete($id) {
		$model = $this->loadModel ( $id, 'RolePermission' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'RolePermission' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'RolePermission' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	}
	public function actionSearch() {
		$model = new RolePermission ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['RolePermission'] )) {
			$model->setAttributes ( $_GET ['RolePermission'] );
			$this->renderPartial ( '_list', array (
					'dataProvider' => $model->search (),
					'model' => $model 
			) );
		}
		
		$this->renderPartial ( '_search', array (
				'model' => $model 
		) );
	}
	public function actionAdmin() {
		$model = new RolePermission ( 'search' );
		if (! ($model->checkPermission ( 'rolePermission/admin' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['RolePermission'] ))
			$model->setAttributes ( $_GET ['RolePermission'] );
		
		$this->render ( 'admin', array (
				'model' => $model 
		) );
	}
	/*
	 * protected function processActions($model = null)
	 * {
	 * parent::processActions($model);
	 * //$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new RolePermission ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
			case 'create' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					);
				}
				break;
			case 'index' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			case 'admin' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							//'visible'=> $model->checkPermission ("rolePermission/view")=="true",
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			default :
			case 'view' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					// $this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => array (
									'update',
									'id' => $model->id 
							),
							'icon' => 'icon-edit icon-white' 
					);
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
}