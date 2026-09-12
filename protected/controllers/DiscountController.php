<?php

class DiscountController extends GxController {

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
					'actions'=>array('view','create','update', 'search','admin','delete','import'),
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
		$model = new Discount();
		if (isset ( $_FILES ['Discount'] )) {
	
			$csvfile = $_FILES['Discount']['tmp_name']['csv_file'];
	
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
	
				$discount = new Discount();
				$result = $discount->setAllValues ( $valued_rows );
				if ($result == 1) {
					Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
		}
		$this->render('import',array('model'=>$model));
	}
	public function actionView($id) 
	{
		
		$model = $this->loadModel($id, 'Discount');
		if( !($model->checkPermission ('discount/view')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}

	public function actionCreate() 
	{
		$model = new Discount;
		if( !($model->checkPermission ('discount/create')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'discount-form');
	
		if (isset($_POST['Discount'])) {
			
			$model->setAttributes($_POST['Discount']);
           $model->start_date = date('Y-m-d',strtotime($model->start_date));
           $model->end_date = date('Y-m-d',strtotime($model->end_date));
           if(isset($_POST['Discount']['is_time_dependent'] ) && ($_POST['Discount']['is_time_dependent'] == Discount::TIME_DEPENDENT)){
           		 $model->start_time = date('H:i:s',strtotime($model->start_time));
           $model->end_time = date('H:i:s',strtotime($model->end_time));
           }else{
           	$model->start_time = '';
           	$model->end_time = '';
           }
			if ($model->save()) {
				if(isset($_POST['Discount']['item_detail_id'])){
					$items = $_POST['Discount']['item_detail_id'];
					if($items){
						foreach($items as $item){
							$item_discount = ItemDiscount::model()->findByAttributes(array('item_detail_id'=>$item));
							if($item_discount == null){
								$item_discount = new ItemDiscount();
							}
							$item_discount->item_detail_id = $item;
							$item_discount->discount_id = $model->id;
							$item_discount->save();
						}
					}
					
				}
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
		$model = $this->loadModel($id, 'Discount');
		if( !($model->checkPermission ('discount/update')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'discount-form');

		if (isset($_POST['Discount'])) {
			$model->setAttributes($_POST['Discount']);
			$model->start_date = date('Y-m-d',strtotime($model->start_date));
			$model->end_date = date('Y-m-d',strtotime($model->end_date));
			
			if(isset($_POST['Discount']['is_time_dependent'] ) && ($_POST['Discount']['is_time_dependent'] == Discount::TIME_DEPENDENT)){
				$model->start_time = date('H:i:s',strtotime($model->start_time));
				$model->end_time = date('H:i:s',strtotime($model->end_time));
			}else{
				$model->start_time = '';
				$model->end_time = '';
			}
			if ($model->save()) {
				if(isset($_POST['Discount']['discount_type']) && ($_POST['Discount']['discount_type'] == Discount::DISCOUNT_ORDER)){
					$model->removeItemDetailIds();
				}
				if(isset($_POST['Discount']['item_detail_id'])){
					$model->removeItemDetailIds();
					$items = $_POST['Discount']['item_detail_id'];
					if($items){
						foreach($items as $item){
							$item_discount = ItemDiscount::model()->findByAttributes(array('item_detail_id'=>$item));
							if($item_discount == null){
								$item_discount = new ItemDiscount();
							}
							$item_discount->item_detail_id = $item;
							$item_discount->discount_id = $model->id;
							$item_discount->save();
						}
					}
						
				}
				$this->redirect(array('view', 'id' => $model->id));
			}
		}
		$model->item_detail_id = $model->getItemDetailIds();
		$this->updateMenuItems($model);
		$this->render('update', array(
				'model' => $model,
				));
	}

	public function actionDelete($id) 
	{
		$model = $this->loadModel($id, 'Discount');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'Discount')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('Discount');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['Discount']))
		{
			$model->setAttributes($_GET['Discount']);
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
		$model = new Discount('search');
		if( !($model->checkPermission ('discount/admin')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		
		if (isset($_GET['Discount']))
			$model->setAttributes($_GET['Discount']);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->search (), array (
						//'id',
						'title',
						'amount',
						'start_date',
						'end_date',
				)
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
		if ( $model == null ) $model = new Discount();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = array('label'=>Yii::t('app', 'View') , 'url'=>array('view','id'=>$model->id),'visible'=> $model->checkPermission ("discount/view")=="true",'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'visible'=> $model->checkPermission ("discount/admin")=="true",'icon'=>'icon-wrench icon-white');							
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'visible'=> $model->checkPermission ("discount/create")=="true",'icon'=>'icon-th-list icon-white');	
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
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'visible'=> $model->checkPermission ("discount/create")=="true",'icon'=>'icon-plus icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Import' ),
							'url' => array (
									'import'
							),
							'icon' => 'icon-plus icon-white'
					);
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'visible'=> $model->checkPermission ("discount/admin")=="true",'icon'=>'icon-wrench icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'visible'=> $model->checkPermission ("discount/create")=="true",'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id),'visible'=> $model->checkPermission ("discount/update")=="true", 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}