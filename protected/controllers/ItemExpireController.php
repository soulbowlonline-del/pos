<?php

class ItemExpireController extends GxController {

	public function filters() {
		return array(
				'accessControl', 
				);
	}

	public function accessRules() {
		return array(
				array('allow',
					'actions'=>array(/* 'index','view', 'download', 'thumbnail' */),
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
		$model = $this->loadModel($id, 'ItemExpire');
		$itemExpireItem = new ItemExpireItem ( 'search' );
		$itemExpireItem->unsetAttributes ();
		$_GET ['ItemExpireItem']['item_expire_id'] = $id;
		if (isset ( $_GET ['ItemExpireItem'] ))
			$itemExpireItem->setAttributes ( $_GET ['ItemExpireItem'] );
			//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
			//$this->processActions($model);
			$this->updateMenuItems($model);
			$this->render('view', array(
					'model' => $model,'itemExpireItem'=>$itemExpireItem
			));
	}
	

	public function actionCreate() 
	{
		$model = new ItemExpire;

		$this->performAjaxValidation($model, 'item-expire-form');

		if (isset($_POST['ItemExpire'])) {
			$model->setAttributes($_POST['ItemExpire']);
			$set = true;
			$transaction = Yii::app ()->db->beginTransaction ();
			
			try { 
			if($model->save()){
				$itemDetail = ItemDetail::model()->findByPk($model->item_detail_id);
				if($itemDetail){
					$itemStock = ItemStock::model()->findByAttributes(array('item_detail_id'=>$itemDetail->id,
							'outlet_id'=> $model->outlet_id));
					$item = Item::model()->findByPk($itemDetail->item_id);
					$current = $itemStock->balance_qty;
					
					if($itemStock != null){
				           $itemStock->balance_qty = ($itemStock->balance_qty) - ($model->qty);
						
					
					
						
					if($itemStock->save()){
						$log = new StockLog();
						
						$log->item_detail_id = $itemDetail->id;
						$log->item_id = $item->id;
						$log->batch_no = $itemStock->batch_number;
						$log->current_qty = $itemDetail->getStockQty();
						$log->previous_qty = ($itemDetail->getStockQty())+($model->qty);
						$log->Qty = $model->qty;
						$log->outlet_id = $model->outlet_id;
						$log->vendor_id = $model->vendor_id;
						$log->type_id = StockLog::TYPE_EXPIRED;
					
						if($log->save()){
							
						}else{
							print_r($log->getErrors());exit;
						}
					}else{
						$set = false;
					}
					}else{
						$set = false;
					}
						
				}
				if($set == true){
					$transaction->commit ();
				$this->redirect(array('item/expireStock','vendor_id'=>$model->vendor_id,'outlet_id'=>$model->outlet_id,'set'=>$set));
				}else{
					$transaction->rollback ();
					$this->redirect(array('item/expireStock','vendor_id'=>$model->vendor_id,'outlet_id'=>$model->outlet_id,'set'=>$set));
				}
			}else{
				print_r($model->getErrors());exit;
			}
		 	} catch ( Exception $e ) {
				$transaction->rollback ();
			} 
			
		}
		$this->redirect(array('item/expireStock'));
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id, 'ItemExpire');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'item-expire-form');

		if (isset($_POST['ItemExpire'])) {
			$model->setAttributes($_POST['ItemExpire']);

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
		$model = $this->loadModel($id, 'ItemExpire');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'ItemExpire')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('ItemExpire');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new ItemExpire ('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemExpire']))
		{
			$model->setAttributes($_GET['ItemExpire']);
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
		$model = new ItemExpire('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		$columns = array();
		if (isset ( $_POST ['ItemExpire']['columns'] )){
			$columns = $_POST ['ItemExpire']['columns'];
		}
		if (isset($_GET['ItemExpire']))
			$model->setAttributes($_GET['ItemExpire']);
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
		if ( $model == null ) $model = new ItemExpire();
		
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
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}