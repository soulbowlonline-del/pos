<?php

class StockAdjustLogController extends GxController {

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
					'actions'=>array('view','create','update', 'search','admin','delete'),
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
	public function actionView($id) 
	{
		$model = $this->loadModel($id, 'StockAdjustLog');
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}

	public function actionCreate() 
	{
		$model = new StockAdjustLog;

		$this->performAjaxValidation($model, 'stock-adjust-log-form');

		if (isset($_POST['StockAdjustLog'])) {
			$model->setAttributes($_POST['StockAdjustLog']);

			if ($model->save()) {
				if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('view', 'id' => $model->id));
			}
		}
		$this->updateMenuItems($model);
		$this->render('create', array( 'model' => $model));
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id, 'StockAdjustLog');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'stock-adjust-log-form');

		if (isset($_POST['StockAdjustLog'])) {
			$model->setAttributes($_POST['StockAdjustLog']);

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
		$model = $this->loadModel($id, 'StockAdjustLog');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'StockAdjustLog')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('StockAdjustLog');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new StockAdjustLog ('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['StockAdjustLog']))
		{
			$model->setAttributes($_GET['StockAdjustLog']);
			$this->renderPartial('_list', array(
					'dataProvider' => $model->search(),
					'model' => $model,
			));
		}
			
		$this->renderPartial('_search', array(
				'model' => $model,
		));
	}
	public function actionAdmin() 
	{
		$model = new StockAdjustLog('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		$columns = array();
		
		if (isset ( $_POST ['StockAdjustLog']['start_date'] ) &&( $_POST ['StockAdjustLog']['start_date'] !='')&& (isset ( $_POST ['StockAdjustLog']['end_date'] ))
				&& (  $_POST ['StockAdjustLog']['end_date'] !='')){
			$_GET['StockAdjustLog']['start_date'] = $_POST ['StockAdjustLog']['start_date'] ;
			$_GET['StockAdjustLog']['end_date'] = $_POST ['StockAdjustLog']['end_date'] ;
			Yii::app()->session['adjust_start_date'] =  $_POST ['StockAdjustLog']['start_date'] ;
			Yii::app()->session['adjust_end_date']  = $_POST ['StockAdjustLog']['end_date'] ;
			
		}
		if (isset ( $_GET ['StockAdjustLog'] ['create_user_id'] ) ) {
			$model->create_user_id = $_GET ['StockAdjustLog'] ['create_user_id'];
		}
		Yii::log ( CVarDumper::dumpAsString ( 	Yii::app()->session['adjust_start_date']  ), CLogger::LEVEL_WARNING, 'start_date' );
		Yii::log ( CVarDumper::dumpAsString ( 	Yii::app()->session['adjust_end_date']  ), CLogger::LEVEL_WARNING, 'endd_date' );
		
		if (isset ( $_POST ['StockAdjustLog']['columns'] )){
			$columns = $_POST ['StockAdjustLog']['columns'];
		}
		if (isset($_GET['StockAdjustLog']))
			$model->setAttributes($_GET['StockAdjustLog']);
			$columns = $model->getColumns($columns);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->search (), $columns
						);
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
		if ( $model == null ) $model = new StockAdjustLog();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = array('label'=>Yii::t('app', 'View') , 'url'=>array('view','id'=>$model->id),'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');							
					$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
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
					$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}