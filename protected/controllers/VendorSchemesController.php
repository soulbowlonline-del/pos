<?php

class VendorSchemesController extends GxController {

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
					'actions'=>array('view','create','update', 'search','admin','delete','add'),
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
		$model = $this->loadModel($id, 'VendorSchemes');
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}
	public function actionAdd($id = null)
	{
		$model = new VendorSchemes;
	
		$this->performAjaxValidation($model, 'vendor-schemes-form');
		if($id != null){
			$model->item_id =$id;
		}
		
		if (isset($_POST['VendorSchemes'])) {
			$model->setAttributes($_POST['VendorSchemes']);
			if(isset($_POST['VendorSchemes']['item_id'])){
				$model->item_id = implode(',',$_POST['VendorSchemes']['item_id']);
			}
			if ($model->save()) {
				Yii::app ()->user->setFlash ( 'success', 'Vendor Scheme is saved sucessfully' );
				/* if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
					else
						$this->redirect(array('view', 'id' => $model->id)); */
			}
		}
		// (string) for PHP 8.1: item_id is null on a model that has not been
		// saved, and explode() with null as the subject is deprecated - which
		// this application reports like any other error, so vendorSchemes/add
		// was a 500 on 8.3 and a working page on 5.6. explode(',', '') is
		// array(''), which is what explode(',', null) gave before.
		$model->item_id = explode(',', (string) $model->item_id);
		$this->updateMenuItems($model);
		$this->render('add', array( 'model' => $model,'id'=>$id));
	}
	public function actionCreate() 
	{
		$model = new VendorSchemes;
		$list = array();
		$this->performAjaxValidation($model, 'vendor-schemes-form');
		$items = Item::model()->findAll();
		if($items){
			foreach($items as $item){
				$list[] = $item->id;
			}
		}
		$model->item_id = $list;
		if (isset($_POST['VendorSchemes'])) {
			$model->setAttributes($_POST['VendorSchemes']);
if(isset($_POST['VendorSchemes']['item_id'])){
	$model->item_id = implode(',',$_POST['VendorSchemes']['item_id']);
}
			if ($model->save()) {
				Yii::app ()->user->setFlash ( 'success', 'Vendor Scheme is saved sucessfully' );
			/* 	if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('view', 'id' => $model->id)); */
			}
		}
		$this->updateMenuItems($model);
		$this->render('create', array( 'model' => $model));
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id, 'VendorSchemes');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'vendor-schemes-form');

		if (isset($_POST['VendorSchemes'])) {
			$model->setAttributes($_POST['VendorSchemes']);
			if(isset($_POST['VendorSchemes']['item_id'])){
				$model->item_id = implode(',',$_POST['VendorSchemes']['item_id']);
			}
			if ($model->save()) {
				$this->redirect(array('view', 'id' => $model->id));
			}
		}
		if($model->item_id != '')
		// (string) for PHP 8.1: item_id is null on a model that has not been
		// saved, and explode() with null as the subject is deprecated - which
		// this application reports like any other error, so vendorSchemes/add
		// was a 500 on 8.3 and a working page on 5.6. explode(',', '') is
		// array(''), which is what explode(',', null) gave before.
		$model->item_id = explode(',', (string) $model->item_id);
		$this->updateMenuItems($model);
		$this->render('update', array(
				'model' => $model,
				));
	}

	public function actionDelete($id) 
	{
		$model = $this->loadModel($id, 'VendorSchemes');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'VendorSchemes')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('VendorSchemes');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new VendorSchemes ('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['VendorSchemes']))
		{
			$model->setAttributes($_GET['VendorSchemes']);
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
		$model = new VendorSchemes('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		
		if (isset($_GET['VendorSchemes']))
			$model->setAttributes($_GET['VendorSchemes']);

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
		if ( $model == null ) $model = new VendorSchemes();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = array('label'=>Yii::t('app', 'View') , 'url'=>array('view','id'=>$model->id),'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');							
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
				//	$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
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