<?php

class ItemCompanyController extends GxController {

	public function filters() {
		return array(
				'accessControl', 
				);
	}

	public function accessRules() {
		return array(
				array('allow',
					'actions'=>array(/*'index','view',  'download', 'thumbnail' */),
					'users'=>array('*'),
					),
				array('allow', 
					'actions'=>array('view','create','update', 'search','admin','delete','import','subcompany','subCreate','subView','subUpdate'),
					'users'=>array('@'),
					),
				/*array('allow', 
					'actions'=>array('admin','delete'),
					'expression'=>'Yii::app()->user->isAdmin',
					),*/
				array('deny', 
					'users'=>array('*'),
					),
				);
	}

	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionImport() {
		$model = new ItemCompany();
		if (isset ( $_FILES ['ItemCompany'] )) {
	
			$csvfile = $_FILES['ItemCompany']['tmp_name']['csv_file'];
			if($csvfile != ''){
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
	
				$itemCat = new ItemCompany();
				$result = $itemCat->setAllValues ( $valued_rows );
				if ($result == 1) {
					Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}}else{
				Yii::app ()->user->setFlash ( 'danger', 'Please upload File' );
			}
		}
		$this->render('import',array('model'=>$model));
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id, 'ItemCompany');
		if( !($model->checkPermission ('ItemCompany/view')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}
	public function actionsubView($id)
	{
		$model = $this->loadModel($id, 'ItemCompany');
		if( !($model->checkPermission ('ItemCompany/view')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		//$this->processActions($model);
		$this->updateMenuItems($model);
		$this->render('subview', array(
				'model' => $model
		));
	}
	public function actionCreate() 
	{
		$model = new ItemCompany;
		if( !($model->checkPermission ('ItemCompany/create')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'item-company-form');

		if (isset($_POST['ItemCompany'])) {
			$model->setAttributes($_POST['ItemCompany']);

			if ($model->save()) {
				$user = Yii::app()->user->model;
				$role = UserRole::model()->findByAttributes(array('title'=>'Admin'));
				if($user->role_id == $role->id ){
					$this->redirect(array('view', 'id' => $model->id));
				}else{
					$this->redirect(array('item/vendorCreate'));
				}
			}
		}
		$this->updateMenuItems($model);
		$this->render('create', array( 'model' => $model));
	}
	public function actionSubCreate()
	{
		$model = new ItemCompany;
		if( !($model->checkPermission ('ItemCompany/create')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		$this->performAjaxValidation($model, 'item-company-form');
	
		if (isset($_POST['ItemCompany'])) {
			$model->setAttributes($_POST['ItemCompany']);
	
			if ($model->save()) {
				$user = Yii::app()->user->model;
				$role = UserRole::model()->findByAttributes(array('title'=>'Admin'));
				if($user->role_id == $role->id ){
					$this->redirect(array('subView', 'id' => $model->id));
				}else{
					$this->redirect(array('item/vendorCreate'));
				}
			}
		}
		$this->updateMenuItems($model);
		$this->render('subcreate', array( 'model' => $model));
	}
	public function actionSubUpdate($id)
	{
		$model = $this->loadModel($id, 'ItemCompany');
		if( !($model->checkPermission ('ItemCompany/update')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		$this->performAjaxValidation($model, 'item-company-form');
	
		if (isset($_POST['ItemCompany'])) {
			$model->setAttributes($_POST['ItemCompany']);
	
			if ($model->save()) {
				$this->redirect(array('view', 'id' => $model->id));
			}
		}
		$this->updateMenuItems($model);
		$this->render('subupdate', array(
				'model' => $model,
		));
	}
	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id, 'ItemCompany');
		if( !($model->checkPermission ('ItemCompany/update')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'item-company-form');

		if (isset($_POST['ItemCompany'])) {
			$model->setAttributes($_POST['ItemCompany']);

			if ($model->save()) {
				$this->redirect(array('view', 'id' => $model->id));
			}
		}
		$this->updateMenuItems($model);
		$this->render('update', array(
				'model' => $model,
				));
	}

	public function actionDelete($id) 
	{
		$model = $this->loadModel($id, 'ItemCompany');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'ItemCompany')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('ItemCompany');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemCompany']))
		{
			$model->setAttributes($_GET['ItemCompany']);
			$this->renderPartial('_list', array(
					'dataProvider' => $model->search(),
					'model' => $model,
			));
		}
			
		$this->renderPartial('_search', array(
				'model' => $model,
		));
	}
	public function actionSubcompany()
	{
		$model = new ItemCompany('search');
		if( !($model->checkPermission ('itemCompany/admin')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemCompany']))
			$model->setAttributes($_GET['ItemCompany']);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->subsearch(), array (
							
						'id',
						'title',
						array(
								'label' => 'Parent Company',
								'value' => function ($data) {
								return ItemCompany::getParentItemCompany($data->parent_id);
								}),
									
								) );
			}
			$this->render('subcompany', array(
					'model' => $model,
			));
	}
	public function actionAdmin() 
	{
		$model = new ItemCompany('search');
		if( !($model->checkPermission ('itemCompany/admin')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		
		if (isset($_GET['ItemCompany']))
			$model->setAttributes($_GET['ItemCompany']);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->search(), array (
							
						'id',
						'title',
						array(
								'label' => 'Parent Company',
								'value' => function ($data) {
								return ItemCompany::getParentItemCompany($data->parent_id);
								}),
			
								) );
			}
		$this->render('admin', array(
			'model' => $model,
		));
	}
	/*protected function processActions($model = null)
	{
		parent::processActions($model);
		//$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	}*/
	protected function updateMenuItems($model = null)
	{	
		// create static model if model is null
		if ( $model == null ) $model = new ItemCompany();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = array('label'=>Yii::t('app', 'View') , 'url'=>array('view','id'=>$model->id),'visible'=> $model->checkPermission ("itemCompany/view")=="true",'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'visible'=> $model->checkPermission ("itemCompany/admin")=="true",'icon'=>'icon-wrench icon-white');							
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');							
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;
			case 'admin':
				{
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'visible'=> $model->checkPermission ("itemCompany/create")=="true",'icon'=>'icon-plus icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Import' ),
							'url' => array (
									'import'
							),
							'icon' => 'icon-plus icon-white'
					);
				}
				break;		
				case 'subcompany':
					{
						//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
						$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('subCreate'),'visible'=> $model->checkPermission ("itemCompany/create")=="true",'icon'=>'icon-plus icon-white');
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Import' ),
								'url' => array (
										'import'
								),
								'icon' => 'icon-plus icon-white'
						);
					}
					break;
					
					case 'subView':
						{
							//	$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
							$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('subCompany'),'visible'=> $model->checkPermission ("itemCompany/admin")=="true",'icon'=>'icon-wrench icon-white');
							//	$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
							//	'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
							//$this->menu[] = array('label'=>Yii::t('app', 'Add Category'), 'url'=>array('itemCompanyCategory/create', 'id' => $model->id),'visible'=> $model->checkPermission ("itemCompanyCategory/create")=="true",'icon'=>'icon-plus icon-white');
							$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('subCreate'),'visible'=> $model->checkPermission ("itemCompany/create")=="true",'icon'=>'icon-plus icon-white');
							$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('subUpdate', 'id' => $model->id),'visible'=> $model->checkPermission ("itemCompany/update")=="true", 'icon'=>'icon-edit icon-white');
						}
						break;
			default:
			case 'view':
				{
				//	$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'visible'=> $model->checkPermission ("itemCompany/admin")=="true",'icon'=>'icon-wrench icon-white');
				//	$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
				//	'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Add Category'), 'url'=>array('itemCompanyCategory/create', 'id' => $model->id),'visible'=> $model->checkPermission ("itemCompanyCategory/create")=="true",'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'visible'=> $model->checkPermission ("itemCompany/create")=="true",'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id),'visible'=> $model->checkPermission ("itemCompany/update")=="true", 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}