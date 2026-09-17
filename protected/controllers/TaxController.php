<?php
class TaxController extends GxController {
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
								/*'index',
								'view',  'download', 'thumbnail' */),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'view',
								'create',
								'update',
								'search',
								'admin',
								'delete',
								'import'
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
	public function actionImport() {
		$model = new Tax();
		if (isset ( $_FILES ['Tax'] )) {
	
			$csvfile = $_FILES['Tax']['tmp_name']['csv_file'];
	
			$handle = fopen ( $csvfile, 'r' );
			if (! $handle)
				die ( 'Cannot open uploaded file.' );
				$row_count = 0;
				$rows = array ();
				$valued_rows = array ();
				// Read the file as csv
				while ( ($data = fgetcsv ( $handle, 1000, "," )) !== FALSE ) {
					$row_count ++;
					foreach ( $data as $key => $value ) {
							
						$data [$key] = $value;
					}
	
					if (count ( array_flip ( array_flip ( $data ) ) ) != 1) {
						$rows = implode ( ",", $data );
						if (! empty ( $rows )) {
							$valued_rows [] = $rows;
						}
					}
				}
	
				$tax = new Tax();
				$result = $tax->setAllValues ( $valued_rows );
				if ($result == 1) {
					Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
		}
		$this->render('import',array('model'=>$model));
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'Tax' );
		if (! ($model->checkPermission ( 'tax/view' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
			
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionCreate() {
		$model = new Tax ();
		if (! ($model->checkPermission ( 'tax/create' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation ( $model, 'tax-form' );
		
		if (isset ( $_POST ['Tax'] )) {
			$model->setAttributes ( $_POST ['Tax'] );
			
			if ($model->save ()) {
				if (Yii::app ()->getRequest ()->getIsAjaxRequest ())
					Yii::app ()->end ();
				else
					$this->redirect ( array (
							'view',
							'id' => $model->id 
					) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model 
		) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'Tax' );
		if (! ($model->checkPermission ( 'tax/update' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'tax-form' );
		
		if (isset ( $_POST ['Tax'] )) {
			$model->setAttributes ( $_POST ['Tax'] );
			
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
		$model = $this->loadModel ( $id, 'Tax' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'Tax' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'Tax' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	}
	public function actionSearch() {
		$model = new Tax ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['Tax'] )) {
			$model->setAttributes ( $_GET ['Tax'] );
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
		$model = new Tax ( 'search' );
		if (! ($model->checkPermission ( 'tax/admin' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		$columns = array ();
		if (isset ( $_POST ['Tax'] ['columns'] )) {
			$columns = $_POST ['Tax'] ['columns'];
		}
		if (isset ( $_GET ['Tax'] ))
			$model->setAttributes ( $_GET ['Tax'] );
			$columns = $model->getColumns ( $columns );
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->search (), $columns );
			}
		
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
			$model = new Tax ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "tax/view" ) == "true",
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
							'visible' => $model->checkPermission ( "tax/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
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
							'visible' => $model->checkPermission ( "tax/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Import' ),
							'url' => array (
									'import'
							),
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
							'visible' => $model->checkPermission ( "tax/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
					// $this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'visible' => $model->checkPermission ( "tax/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => array (
									'update',
									'visible' => $model->checkPermission ( "tax/update" ) == "true",
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