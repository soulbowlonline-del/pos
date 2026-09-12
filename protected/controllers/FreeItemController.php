<?php

class FreeItemController extends GxController {

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
					'actions'=>array('view','create','update', 'search','admin','delete','itemList'),
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
	public function actionItemList() {
		$user = Yii::app()->user->model;
		$vendor_arr = array ();
		
		$exist = [];
		$lists = [];
		$term = Yii::app ()->request->getQuery ( 'term' );
		
		$criteria = new CDbCriteria ();
		if($user->role_id != 1){
			$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'vendor_id =' . $user->id );
			$itemvendors = ItemVendor::model ()->findAll ( $criteria1 );
			if($itemvendors){
				foreach($itemvendors as $itemvendor){
				$exist[] = $itemvendor->item_detail_id;
				}
			}
			$criteria->addInCondition('id', $exist);
		}
		$criteria->compare ( 'bar_code', $term, true );
		$criteria->limit = '100';
	
		$criteria->addCondition ( 'status =' . Item::STATUS_ACTIVE );
		$itemdetails = ItemDetail::model ()->findAll ( $criteria );
		if ($itemdetails != null) {
			foreach ( $itemdetails as $itemdetail ) {
				$item = Item::model()->findByPk($itemdetail->item_id);
				if($item){
				$lists [] = array (
						'item_id' => $item->id,
						'bar_code' => $itemdetail->bar_code,
						'item_detail_id' => $itemdetail->id,
						'name' => $item->title,
						'category_id' => $item->category_id,
						'company_id' => $item->company_id
	
				);
				}
			}
		}
	
	
		echo json_encode ( $lists );
	}
	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id, 'FreeItem');
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}

	public function actionCreate() 
	{
		$model = new FreeItem;

		$this->performAjaxValidation($model, 'free-item-form');

		if (isset($_POST['FreeItem'])) {
			$model->setAttributes($_POST['FreeItem']);
            if(isset($_POST['FreeItem']['item_detail_id'])){
            	$item_detail = ItemDetail::model()->findByPk($_POST['FreeItem']['item_detail_id']);
            	if($item_detail){
            		$model->item_id = $item_detail->item_id;
            	}
            }
            $user = Yii::app()->user->model;
            $role = UserRole::model()->findByAttributes(array('title'=>'Admin'));
            if($user->role_id != $role->id ){
            	$model->status = FreeItem::STATUS_INACTIVE;
            }
            
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
		$model = $this->loadModel($id, 'FreeItem');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'free-item-form');

		if (isset($_POST['FreeItem'])) {
			$model->setAttributes($_POST['FreeItem']);

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
		$model = $this->loadModel($id, 'FreeItem');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'FreeItem')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('FreeItem');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['FreeItem']))
		{
			$model->setAttributes($_GET['FreeItem']);
			$this->renderPartial('_list', array(
					'dataProvider' => $model->search(),
					'model' => $model,
			));
		}
			
		$this->renderPartial('_search', array(
				'model' => $model,
		));
	}
	public function actionAdmin($id = null) 
	{
		$model = new FreeItem('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	    if($id != null){
	    	$_GET['FreeItem']['item_id'] = $id;
	    }
		if (isset($_GET['FreeItem']))
			$model->setAttributes($_GET['FreeItem']);

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
		if ( $model == null ) $model = new FreeItem();
		
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
					$this->menu[] = array('label'=>Yii::t('app', 'Manage Items'), 'url'=>array('item/admin'),'icon'=>'icon-wrench icon-white');
						}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage Items'), 'url'=>array('item/admin'),'icon'=>'icon-wrench icon-white');
						
					/* $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				
				 */}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}