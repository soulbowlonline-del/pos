<?php

class MrsController extends GxController {

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
					'actions'=>array('create','update', 'search','admin','delete','merge'),
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
	public function actionMerge(){
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$criteria = new CDbCriteria();
			$criteria->addInCondition('id', $_POST['idList']);
			$criteria->compare('vendor_id', PostId::get('vendor_id'));
			$mrs = Mrs::model()->find($criteria);
			if($mrs){
				$criteria = new CDbCriteria();
				$criteria->addInCondition('id', $_POST['idList']);
				$criteria->addCondition('id !='.$mrs->id);
				$mrss = Mrs::model()->findAll($criteria);
				if($mrss){
					foreach($mrss as $delmrs){
						$criteria1 = new CDbCriteria();
						$criteria1->addCondition('mrs_id ='.$delmrs->id);
						$mrsdetails = MrsDetail::model()->findAll($criteria1);
						if($mrsdetails){
							foreach($mrsdetails as $mrsdetail){
								$mrsdetail->mrs_id = $mrs->id;
								$mrsdetail->save();
							}
						}
						$mrs->gross_amt = ($mrs->gross_amt) + ($delmrs->gross_amt);
						$mrs->total_discount = ($mrs->total_discount) + ($delmrs->total_discount);
						$mrs->tax_amount = ($mrs->tax_amount) + ($delmrs->tax_amount);
						$mrs->bill_amount = ($mrs->bill_amount) + ($delmrs->bill_amount);
						if($mrs->save()){
							$delmrs->delete();
						}
					}
				}
			}
		}
		
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id, 'Mrs');
		if( !($model->checkPermission ('mrs/view')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}

	public function actionCreate() 
	{
		$model = new Mrs;
		if( !($model->checkPermission ('mrs/create')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'mrs-form');

		if (isset($_POST['Mrs'])) {
			$model->setAttributes($_POST['Mrs']);

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
		$model = $this->loadModel($id, 'Mrs');
		if( !($model->checkPermission ('mrs/update')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'mrs-form');

		if (isset($_POST['Mrs'])) {
			$model->setAttributes($_POST['Mrs']);

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
		$model = $this->loadModel($id, 'Mrs');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'Mrs')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('Mrs');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['Mrs']))
		{
			$model->setAttributes($_GET['Mrs']);
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
		$model = new Mrs('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		$model->status = Mrs::STATUS_PENDING;
		if (isset($_GET['Mrs']))
			$model->setAttributes($_GET['Mrs']);

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
		if ( $model == null ) $model = new Mrs();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = array('label'=>Yii::t('app', 'View') , 'url'=>array('view','id'=>$model->id),'visible'=> $model->checkPermission ("mrs/view")=="true",'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'visible'=> $model->checkPermission ("mrs/admin")=="true",'icon'=>'icon-wrench icon-white');							
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
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'visible'=> $model->checkPermission ("mrs/admin")=="true",'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
				//	$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'visible'=> $model->checkPermission ("mrs/admin")=="true",'icon'=>'icon-wrench icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
				//	$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'visible'=> $model->checkPermission ("mrs/create")=="true",'icon'=>'icon-plus icon-white');
				//	$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id),'visible'=> $model->checkPermission ("mrs/update")=="true", 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}