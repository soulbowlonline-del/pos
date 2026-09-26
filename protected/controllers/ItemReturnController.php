<?php

class ItemReturnController extends GxController {

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
		$model = $this->loadModel($id, 'ItemReturn');
		$itemReturnItem = new ItemReturnItem ( 'search' );
		$itemReturnItem->unsetAttributes ();
		$_GET ['ItemReturnItem']['return_id'] = $id;
		if (isset ( $_GET ['ItemReturnItem'] ))
			$itemReturnItem->setAttributes ( $_GET ['ItemReturnItem'] );
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model,'itemReturnItem'=>$itemReturnItem
		));
	}

	public function actionCreate() 
	{
		$model = new ItemReturn;

		$this->performAjaxValidation($model, 'item-return-form');

		if (isset($_POST['ItemReturn'])) {
			$model->setAttributes($_POST['ItemReturn']);

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
		$model = $this->loadModel($id, 'ItemReturn');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'item-return-form');
           $old_credit_note = $model->credit_note_no;
		if (isset($_POST['ItemReturn'])) {
			$model->setAttributes($_POST['ItemReturn']);

			if ($model->save()) {
				if($old_credit_note  != $model->credit_note_no && $model->credit_note_no != 0){
				$creditnote = new CreditNote();
  					$creditnote->credit_number = $model->credit_note_no;
  					$creditnote->amt = $model->total_amt;
  					if($creditnote->save()){
  						$model->credit_note_id = $creditnote->id;
  						$model->save();
  					}
				}
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
		$model = $this->loadModel($id, 'ItemReturn');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'ItemReturn')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('ItemReturn');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemReturn']))
		{
			$model->setAttributes($_GET['ItemReturn']);
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
		$model = new ItemReturn('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		$columns = array();
		if (isset ( $_POST ['ItemReturn']['columns'] )){
			$columns = $_POST ['ItemReturn']['columns'];
		}
		if (isset($_GET['ItemReturn']))
			$model->setAttributes($_GET['ItemReturn']);
			$columns = $model->getColumns($columns);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->search (), $columns
						);
			}
		$this->render('admin', array(
			'model' => $model,
		));
	}

	public function actionList()
	{
		$model = new ItemReturn('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		//$model->status = PurchaseBill::STATUS_UNAPPROVED;
		// if (isset($_GET['ItemReturn']))
			// $model->setAttributes($_GET['ItemReturn']);
			
			$this->render('list', array(
					'model' => $model,
			));
	}

	public function actionMerge(){
		$vendor_ids = array();

		
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$criteria = new CDbCriteria();
			//$criteria->addInCondition('id', $_POST['idList']);
			$criteria->compare('id', PostId::get('idList', '0'));
			$returnItem = ItemReturn::model()->find($criteria);
			
			if($returnItem){
				$criteria = new CDbCriteria();
				$criteria->addInCondition('id', $_POST['idList']);
				$criteria->addCondition('id !='.$returnItem->id);
				$bills = ItemReturn::model()->findAll($criteria);
				if($bills){
					$vendor_ids[] = $_POST['vendor_id'];
					foreach($bills as $delbill){
						$vendor_ids[] = $delbill->vendor_id;
						$criteria1 = new CDbCriteria();
						$criteria1->addCondition('return_id ='.$delbill->id);
						$billdetails = ItemReturnItem::model()->findAll($criteria1);
						if($billdetails){
							foreach($billdetails as $billdetail){
								$billdetail->return_id = $returnItem->id;
								$billdetail->save();
							}
						}
						$vendor_ids[] = $returnItem->vendor_id;
						$vendor_ids = array_unique($vendor_ids);
						if(!empty($vendor_ids)){
							$returnItem->original_vendor_id = implode(',',$vendor_ids);
						}
						$returnItem->vendor_id = $_POST['vendor_id'];
						$returnItem->gross_amt = ($returnItem->gross_amt) + ($delbill->gross_amt);
						$returnItem->discount_amt = ($returnItem->discount_amt) + ($delbill->discount_amt);
						$returnItem->tax_amt = ($returnItem->tax_amt) + ($delbill->tax_amt);
						$returnItem->total_amt = ($returnItem->total_amt) + ($delbill->total_amt);
						if($returnItem->save()){
							$delbill->delete();
						}
					}
				}else{
					$vendor_ids[] = $returnItem->vendor_id;
					if($returnItem->vendor_id != $_POST['vendor_id']){
					$vendor_ids[] = $_POST['vendor_id'];
					}
					if(!empty($vendor_ids)){
						$returnItem->original_vendor_id = implode(',',$vendor_ids);
					}
					
					$returnItem->vendor_id = $_POST['vendor_id'];
					
					$returnItem->save();
				}
			}
		}
	
	}

	/*protected function processActions($model = null)
	{
		parent::processActions($model);
		//$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	}*/
	protected function updateMenuItems($model = null)
	{	
		// create static model if model is null
		if ( $model == null ) $model = new ItemReturn();
		
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