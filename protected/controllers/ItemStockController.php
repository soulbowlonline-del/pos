<?php

class ItemStockController extends GxController {

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
					'actions'=>array('view','create','update', 'search','admin','delete','ajaxItems','ajaxVendors','ajaxItemDetail'),
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
		$model = $this->loadModel($id, 'ItemStock');
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}
	public function actionAjaxItemDetail() {
		$option = '';
		$alreadypermissions = array ();
		$response['outlet_id'] = '';
		$response['batch_number'] = '';
		if (isset ( $_POST ['item_detail_id'] )) {
			$itemDetail = ItemDetail::model()->findByPk( $_POST ['item_detail_id'] );
			$criteria = new CDbCriteria();
			$criteria->compare('item_detail_id', PostId::get('item_detail_id'));
	        $itemstock = ItemStock::model()->find($criteria);

	        if($itemDetail){
	        	$response['outlet_id'] = $itemDetail->outlet_id;
	        	$response['tax_id'] = $itemDetail->tax_id;
	        }else{
	        	$response['outlet_id'] = '';
	        	$response['tax_id'] = '';
	        }
		if($itemstock){
			$response['batch_number'] = $itemstock->batch_number;
		}else{
			$response['batch_number'] = '';
		}
		
		}
		echo json_encode($response);
		
	}
	public function actionAjaxItems() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
	  $item = Item::model()->findByPk( $_POST ['item_id'] );
			$criteria = new CDbCriteria();
			$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
			$criteria->compare('item_id', PostId::get('item_id'));
	
			$itemdetails = ItemDetail::model()->findAll($criteria);
			$option .= '<select class="form-control" id="ItemStock_item_detail_id" name="ItemStock[item_detail_id]" onChange="BarCodeData()"><option value="" id="ckbCheckAll">-Select-</option>';
			if ($itemdetails) {
				foreach ( $itemdetails as $itemdetail ) {
	
					$option .= '<option value="' . $itemdetail->id . '">' . $itemdetail->bar_code . '</option>';
	
				}
	
			} else {
				// $option .= '<option value="">-Select-</option>';
			}
			$option .= '</select>';
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		$response['option'] = $option;
		if($item){
		$response['mrp'] = $item->mrp;
		$response['base_price'] = $item->purchase_price;
		
		}else{
			$response['mrp'] = '0.00';
			$response['base_price'] = '0.00';
		}
		echo json_encode($response);
	}
	public function actionAjaxVendors() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
			$vendor_ids = array();
			$criteria = new CDbCriteria();
		    $criteria->compare('item_detail_id', PostId::get('item_id'));
	       $itemvendors = ItemVendor::model()->findAll($criteria);
	       if($itemvendors){
	       	foreach($itemvendors as $itemvendor){
	       		$vendor_ids[] = $itemvendor->vendor_id;
	       	}
	       }
	      
	       $criteria1 = new CDbCriteria();
	       $criteria1->addInCondition('id',$vendor_ids);
	       $vendors = Vendor::model()->findAll($criteria1);
	       
			$option .= '<select class="form-control" id="ItemStock_vendor_id" name="ItemStock[vendor_id]"><option value="" id="ckbCheckAlll">-Select-</option>';
			if ($vendors) {
				foreach ( $vendors as $vendor ) {
	
					$option .= '<option value="' . $vendor->id . '">' . $vendor->name . '</option>';
	
				}
	
			} else {
				// $option .= '<option value="">-Select-</option>';
			}
			$option .= '</select>';
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		echo $option;
	}
	public function actionCreate() 
	{
		$model = new ItemStock;
		$cal = false;

		$this->performAjaxValidation($model, 'item-stock-form');

		if (isset($_POST['ItemStock'])) {
			$model = ItemStock ::model()->findByAttributes(array('batch_number'=>$_POST['ItemStock']['batch_number'],'outlet_id'=>$_POST['ItemStock']['outlet_id'],
					'item_detail_id'=>$_POST['ItemStock']['item_detail_id'],'item_id'=>$_POST['ItemStock']['item_id']
			));
			if($model == null){
				$model = new ItemStock;
				$model->setAttributes($_POST['ItemStock']);
				if($_POST['ItemStock']['type_id'] == ItemStock::TYPE_ADDED){
					$model->purchase_qty = $model->purchase_qty + $_POST['ItemStock']['purchase_qty'] ;
					$model->balance_qty = $model->balance_qty +  $_POST['ItemStock']['purchase_qty'] ;
				}else{
					$model->purchase_qty = $model->purchase_qty - $_POST['ItemStock']['purchase_qty'] ;
					$model->balance_qty = $model->balance_qty -  $_POST['ItemStock']['purchase_qty'] ;
					$cal = true;
				}
				//$model->balance_qty = $_POST['ItemStock']['purchase_qty'];
				//$model->purchase_qty = $_POST['ItemStock']['purchase_qty'];
			}else{
				$model->setAttributes($_POST['ItemStock']);
			if($_POST['ItemStock']['type_id'] == ItemStock::TYPE_ADDED){
				$model->purchase_qty = $model->purchase_qty + $_POST['ItemStock']['purchase_qty'] ;
				$model->balance_qty = $model->balance_qty +  $_POST['ItemStock']['purchase_qty'] ;
			}else{
				$model->purchase_qty = $model->purchase_qty - $_POST['ItemStock']['purchase_qty'] ;
			$model->balance_qty = $model->balance_qty -  $_POST['ItemStock']['purchase_qty'] ;
			$cal = true;
			}
			}

			if ($model->save()) {
				$itemDetail = ItemDetail::model()->findByPk($model->item_detail_id);
			$itemDetail->update_time = date('Y-m-d H:i:s');
				$itemDetail->saveAttributes(array('update_time'));
				$item = Item::model()->findByPk($itemDetail->item_id);
				if($item){
					$item->update_time = date('Y-m-d H:i:s');
					$item->saveAttributes(array('update_time'));
				}
				$stock = new StockLog();
				$stock->item_detail_id = $model->item_detail_id;
				$stock->item_id = $model->item_id;
				if($itemDetail){
				if($_POST['ItemStock']['type_id'] == ItemStock::TYPE_ADDED){
					$stock->previous_qty = ($itemDetail->getStockQty())-( $_POST['ItemStock']['purchase_qty']);
				}else{
				$stock->previous_qty = ($itemDetail->getStockQty())+( $_POST['ItemStock']['purchase_qty']);
				}
					$stock->current_qty = $itemDetail->getStockQty();
			      }
				$stock->batch_no = $model->batch_number;
				$stock->Qty =  $_POST['ItemStock']['purchase_qty'];
				$stock->outlet_id = $model->outlet_id;
				$stock->vendor_id = $model->vendor_id;
				if($cal == true){
					$stock->type_id = StockLog::TYPE_SUBSTRACTED;
				$net_less = $model->isnetLessMin();
				if($net_less){
					$model->createMrs();
					$vendor = Vendor::model()->findByPk($stock->vendor_id);
					$email = '';
					if($vendor){
						$vendoruser = User::model()->findByAttributes(array('id'=>$vendor->create_user_id));
						if($vendoruser){
							$email = $vendoruser->email;
						}
					}
					if($email != ''){
						$from = Yii::app()->params['mail_email'] ;
						$to      = $email;
						$subject = 'A new MRS is added';
							
						$view = $this->renderPartial ( '/mail/mrs_added', array (
								'mrs'=>$stock
						), true );
							
							
						//$stock->mailsend ( $to, $from, $subject, $view );
					}
						
				}
				}else{
					$stock->type_id = StockLog::TYPE_ADDED;
				}
				$stock->save();
				
				
				if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('item/admin'));
			}
		}
		$this->updateMenuItems($model);
		$this->render('create', array( 'model' => $model));
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id, 'ItemStock');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'item-stock-form');

		if (isset($_POST['ItemStock'])) {
			$model->setAttributes($_POST['ItemStock']);

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
		$model = $this->loadModel($id, 'ItemStock');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'ItemStock')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('ItemStock');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemStock']))
		{
			$model->setAttributes($_GET['ItemStock']);
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
		$model = new ItemStock('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		
		if (isset($_GET['ItemStock']))
			$model->setAttributes($_GET['ItemStock']);

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
		if ( $model == null ) $model = new ItemStock();
		
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