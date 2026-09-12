<?php

class ItemExpireItemController extends GxController {

	public function filters() {
		return array(
				'accessControl', 
				);
	}

	public function accessRules() {
		return array(
				array('allow',
					'actions'=>array(/*'index','view',  'download', 'thumbnail'*/),
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
		$model = $this->loadModel($id, 'ItemExpireItem');
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}

	public function actionCreate() 
	{
		$model = new ItemExpireItem;

		$this->performAjaxValidation($model, 'item-expire-item-form');

		if (isset($_POST['ItemExpireItem'])&&(isset($_POST['ItemExpireItem']['outlet_id']))&& (isset($_POST['ItemExpireItem']['vendor_id']))
				&& (isset($_POST['ItemExpireItem']['total_amt']))) {
			$itemExpire = ItemExpire::model()->findByAttributes(array('vendor_id'=>$_POST['ItemExpireItem']['vendor_id'],
					'outlet_id'=>$_POST['ItemExpireItem']['outlet_id'],'status'=>ItemExpire::STATUS_PENDING
			));
			if($itemExpire == null){
				$itemExpire = new ItemExpire();
				$amount = 0;
			}else{
				$amount = $itemExpire->total_amt;
			}
			$itemExpire->outlet_id = $_POST['ItemExpireItem']['outlet_id'];
			$itemExpire->vendor_id = $_POST['ItemExpireItem']['vendor_id'];
			$itemExpire->total_amt =$amount + ($_POST['ItemExpireItem']['qty'] * $_POST['ItemExpireItem']['sale_rate']);
			if($itemExpire->save()){
			$model->setAttributes($_POST['ItemExpireItem']);
			$model->item_expire_id = $itemExpire->id;
			$model->total_amt = $_POST['ItemExpireItem']['qty'] * $_POST['ItemExpireItem']['sale_rate'];
			if ($model->save()) {
				
					$this->redirect(array('item/expireStock','vendor_id'=>$itemExpire->vendor_id,
							'outlet_id'=>$itemExpire->outlet_id
					));
			}
			}
		}
		$this->updateMenuItems($model);
		$this->redirect(array('item/expireStock'));
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id, 'ItemExpireItem');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'item-expire-item-form');

		if (isset($_POST['ItemExpireItem'])) {
			$model->setAttributes($_POST['ItemExpireItem']);

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
		$vendor_id = '';
		$outlet_id = '';
		$model = $this->loadModel($id, 'ItemExpireItem');
		$itemExpire = ItemExpire::model()->findByPk($model->item_expire_id);
		if($itemExpire){
			$vendor_id = $itemExpire->vendor_id;
			$outlet_id = $itemExpire->outlet_id;
			$total_amt = ($itemExpire->total_amt)-($model->total_amt);
		}
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		
			$this->loadModel($id, 'ItemExpireItem')->delete();
			$itemExpire->total_amt = $total_amt;
			$itemExpire->saveAttributes(array('total_amt'));
			
				$this->redirect(array('item/expireStock','vendor_id'=>$vendor_id,'outlet_id'=>$outlet_id));
	
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('ItemExpireItem');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemExpireItem']))
		{
			$model->setAttributes($_GET['ItemExpireItem']);
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
		$model = new ItemExpireItem('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		
		if (isset($_GET['ItemExpireItem']))
			$model->setAttributes($_GET['ItemExpireItem']);

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
		if ( $model == null ) $model = new ItemExpireItem();
		
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