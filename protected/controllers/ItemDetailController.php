<?php
class ItemDetailController extends GxController {
	public function filters() {
		return array (
				'accessControl' 
		);
	}
	public function accessRules() {
		return array (
				array (
						'allow',
						'actions' => array (
								/*'index',
								'view',  'download', 'thumbnail' */),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'view',
								'create',
								'update',
								'search',
								'admin',
								'delete',
								'Add',
								'print',
								'barcodes',
								'makeInactive'
						),
						'users' => array (
								'@' 
						) 
				),
				/*array('allow', 
					'actions'=>array('admin','delete'),
					'expression'=>'Yii::app()->user->isAdmin',
					),*/
				array (
						'deny',
						'users' => array (
								'*' 
						) 
				) 
		);
	}
	public function actionMakeInactive(){
		$items = Item::model()->findAll();
		if($items){
			foreach($items as $item){
				
				if($item->getTotalRemainingQuantity() == 0){
					$end_date = date('Y-m-d');
					$start_date = date('Y-m-d', strtotime('-3 months', strtotime($end_date)));
					
					$criteria = new CDbCriteria();
					$criteria->addBetweenCondition ( 'create_date', $start_date, $end_date );
					$criteria->addCondition('item_id ='.$item->id);
					$orderitems = OrderItem::model()->find($criteria);
					$count = 0;
					if($orderitems ==null){
						$count = $count+1;
						$criteria1 = new CDbCriteria();
						$criteria1->addCondition('item_id ='.$item->id);
						$details = ItemDetail::model()->findAll($criteria1);
						if($details){
							foreach($details as $detail){
								$detail->status = ItemDetail::STATUS_INACTIVE;
								$detail->saveAttributes(array('status'));
							}
						}
						$item->status = Item::STATUS_INACTIVE;
						$item->saveAttributes(array('status'));
						echo 'id'.$item->title.'name'.$item->title;
						echo '<br>';
					}
					echo $count;
					//$itemDetail->company_bar_code = 1;
					//$itemDetail->saveAttributes(array('company_bar_code'));
				}
			}
		}
	}
	/*public function actionBarcodes(){
		$itemDetails = ItemDetail::model()->findAll();
		if($itemDetails){
			foreach($itemDetails as $itemDetail){
				$strlen = strlen($itemDetail->bar_code);
				if($strlen <11){
					$itemDetail->company_bar_code = 1;
					$itemDetail->saveAttributes(array('company_bar_code'));
				}
			}
		}
	}*/
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'ItemDetail' );
		if (! ($model->checkPermission ( 'ItemDetail/view' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
			
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionAdd() {
		$model = new ItemDetail ();
		if (! ($model->checkPermission ( 'ItemDetail/create' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation ( $model, 'item-detail-form' );
		
		if (isset ( $_POST ['ItemDetail'] )) {
			if (isset ( $_POST ['ItemDetail']['outlet_id'])) {
			$outlet_ids = $_POST ['ItemDetail'] ['outlet_id'];
			$set = true;
			foreach ( $outlet_ids as $outlet_id ) {
				$model = ItemDetail::model ()->findByAttributes ( array (
						'bar_code' => $_POST ['ItemDetail'] ['bar_code'],
						'outlet_id' => $_POST ['ItemDetail'] ['outlet_id'],
						'item_id' => $_POST ['ItemDetail'] ['item_id'],
				) );
				if($model == null){
					$model = new ItemDetail();
				}
				$model->setAttributes ( $_POST ['ItemDetail'] );
				$model->outlet_id = $outlet_id;
				if(isset($_POST ['ItemDetail']['company_bar_code'])){
					if($_POST ['ItemDetail']['company_bar_code'] == ItemDetail::IS_NOT_COMPANY){
					$model->company_bar_code = ItemDetail::IS_COMPANY;
					}else{
						$model->company_bar_code = ItemDetail::IS_NOT_COMPANY;
					}
				}else{
					$model->company_bar_code = ItemDetail::IS_NOT_COMPANY;
				}
				$model->update_time = date('Y-m-d H:i:s');
				// $model->item_id = $model->id;
				if ($model->save ()) {
					if (isset ( $_POST ['ItemDetail'] ['tax_id'] )) {
						$itemtax = new ItemTax ();
						$itemtax->item_detail_id = $model->id;
						$itemtax->tax_id = $_POST ['ItemDetail'] ['tax_id'];
						$itemtax->save ();
					}
				}else{
					$set = false;
				}
			}
			if($set == true){
				$this->redirect(array('admin'));
			}else{
				Yii::app ()->user->setFlash ( 'error', 'There is some error. Please try again' );
			}
			
			
				Yii::app ()->user->setFlash ( 'success', 'Subitem Info is saved sucessfully' );
			}else{
				Yii::app ()->user->setFlash ( 'error', 'Please select Outlet' );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'add', array (
				'model' => $model,
				'id' => '' 
		) );
	}
	public function actionCreate($id = null) {
		if($id != null)
		$item = $this->loadModel ( $id, 'Item' );
		$model = new ItemDetail ();
		if (! ($model->checkPermission ( 'ItemDetail/create' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation ( $model, 'item-detail-form' );
		
		if (isset ( $_POST ['ItemDetail'] )) {
			if (isset ( $_POST ['ItemDetail']['outlet_id'])) {
			$outlet_ids = $_POST ['ItemDetail'] ['outlet_id'];
			$set = true;
			foreach ( $outlet_ids as $outlet_id ) {
				$model = ItemDetail::model ()->findByAttributes ( array (
						'bar_code' => $_POST ['ItemDetail'] ['bar_code'],
						'outlet_id' => $_POST ['ItemDetail'] ['outlet_id'],
						'item_id' => $_POST ['ItemDetail'] ['item_id'],
				) );
				if($model == null){
					$model = new ItemDetail();
				}
			
				$model->setAttributes ( $_POST ['ItemDetail'] );
				$model->outlet_id = $outlet_id;
				if(isset($_POST ['ItemDetail']['company_bar_code'])){
					if($_POST ['ItemDetail']['company_bar_code'] == ItemDetail::IS_NOT_COMPANY){
						$model->company_bar_code = ItemDetail::IS_COMPANY;
					}else{
						$model->company_bar_code = ItemDetail::IS_NOT_COMPANY;
					}
				}else{
					$model->company_bar_code = ItemDetail::IS_NOT_COMPANY;
				}
				$model->update_time = date('Y-m-d H:i:s');
				// $model->item_id = $model->id;
				if ($model->save ()) {
					$batch_no =  User::randomBarcode('5');
					/* foreach ( $outlet_ids as $outlet_id ) {
						$itemstock = ItemStock ::model()->findByAttributes(array('batch_number'=>$batch_no,'outlet_id'=>$outlet_id,
							'vendor_id'=>'0'
					));
					if($itemstock == null){
						$itemstock = new ItemStock;
					}
						
						$itemstock->balance_qty = $_POST['ItemDetail']['open_stock_qty'];
						$itemstock->purchase_qty = $_POST['ItemDetail']['open_stock_qty'];
						$itemstock->outlet_id = $outlet_id;
						$itemstock->vendor_id = 0;
						if($_POST ['ItemDetail']['item_id']){
							$item = $this->loadModel ( $_POST ['ItemDetail']['item_id'], 'Item' );
						}
						if($_POST ['ItemDetail']['mrp'] != ''){
							$itemstock->mrp = $_POST ['ItemDetail']['mrp'];
						}else{
							$itemstock->mrp = $item->mrp;
						}
						$itemstock->base_price = $item->sale_price;
						$itemstock->batch_number = $batch_no;
						$itemstock->item_id = $item->id;
						$itemstock->item_detail_id = $model->id;
						if($itemstock->save()){
							
						}else{
							print_r($itemstock->getErrors());exit;
						}
					} */
					
					
					if (isset ( $_POST ['ItemDetail'] ['tax_id'] )) {
						$itemtax = new ItemTax ();
						$itemtax->item_detail_id = $model->id;
						$itemtax->tax_id = $_POST ['ItemDetail'] ['tax_id'];
						$itemtax->save ();
					}
				}else{
					$set = false;
				}
			}
			
			if($set == true){
				Yii::app ()->user->setFlash ( 'success', 'Subitem Info is saved sucessfully' );
			}else{
				Yii::app ()->user->setFlash ( 'error', 'There is some error. Please try again' );
			}
				/*
				 * if (Yii::app()->getRequest()->getIsAjaxRequest())
				 * Yii::app()->end();
				 * else
				 * $this->redirect(array('view', 'id' => $model->id));
				 */
			}else{
				Yii::app ()->user->setFlash ( 'error', 'Pleas select Outlet' );
			}
			
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model,
				'id' => $id 
		) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'ItemDetail' );
		$item = $this->loadModel ( $model->item_id, 'Item' );
		if (! ($model->checkPermission ( 'ItemDetail/update' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'item-detail-form' );
		
		if (isset ( $_POST ['ItemDetail'] )) {
			$model->setAttributes ( $_POST ['ItemDetail'] );
			if(isset($_POST ['ItemDetail']['company_bar_code'])){
				$model->company_bar_code = $_POST ['ItemDetail']['company_bar_code'];
			}
			$model->update_time = date('Y-m-d H:i:s');
			
			if ($model->save ()) {
				$batch_no =  User::randomBarcode('5');
				$outlet_ids = $_POST ['ItemDetail'] ['outlet_id'];
				foreach ( $outlet_ids as $outlet_id ) {
					$itemstock = ItemStock ::model()->findByAttributes(array('outlet_id'=>$outlet_id,
							'vendor_id'=>'0','item_detail_id'=>$model->id,
					));
					if($itemstock == null){
						$itemstock = new ItemStock;
					}
				
					$itemstock->balance_qty = $_POST['ItemDetail']['open_stock_qty'];
					$itemstock->purchase_qty = $_POST['ItemDetail']['open_stock_qty'];
					$itemstock->outlet_id = $outlet_id;
					$itemstock->vendor_id = 0;
					if($_POST ['ItemDetail']['mrp'] != ''){
						$itemstock->mrp = $_POST ['ItemDetail']['mrp'];
					}else{
						$itemstock->mrp = $item->mrp;
					}
					$itemstock->base_price = $item->sale_price;
					$itemstock->batch_number = $batch_no;
					$itemstock->item_id = $item->id;
					$itemstock->item_detail_id = $model->id;
					if($model->status == ItemDetail::STATUS_ACTIVE){
					if($itemstock->save()){
							
					}else{
						print_r($itemstock->getErrors());exit;
					}
					}else{
						/* $itemstock = ItemStock ::model()->findByAttributes(array('outlet_id'=>$outlet_id,
								'vendor_id'=>'0','item_detail_id'=>$model->id,
						)); */
						//if($itemstock)
						//$itemstock->delete();
					}
				}
					
					
				if (isset ( $_POST ['ItemDetail'] ['tax_id'] )) {
					$itemtax = ItemTax ::model()->findByAttributes(array('item_detail_id'=>$model->id,'tax_id'=>$_POST ['ItemDetail'] ['tax_id']
						
					));
					if($itemtax == null){
						$itemtax = new ItemStock;
					}
					$itemtax = new ItemTax ();
					$itemtax->item_detail_id = $model->id;
					$itemtax->tax_id = $_POST ['ItemDetail'] ['tax_id'];
					$itemtax->save ();
				}
				$this->redirect ( array (
						'view',
						'id' => $model->id 
				) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'update', array (
				'model' => $model 
		) );
	}
	public function actionDelete($id) {
		$model = $this->loadModel ( $id, 'ItemDetail' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'ItemDetail' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'ItemDetail' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	}
	public function actionSearch() {
		$model = new Job ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['ItemDetail'] )) {
			$model->setAttributes ( $_GET ['ItemDetail'] );
			$this->renderPartial ( '_list', array (
					'dataProvider' => $model->search (),
					'model' => $model 
			) );
		}
		
		$this->renderPartial ( '_search', array (
				'model' => $model 
		) );
	}
	public function actionAdmin() {
		
		$model = new ItemDetail ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['ItemDetail'] ))
			$model->setAttributes ( $_GET ['ItemDetail'] );
		if (isset ( $_GET ['id'] )) {
			$item = $this->loadModel ( $_GET ['id'], 'Item' );
			$model->item_id = $item->title;
		}
		$this->render ( 'admin', array (
				'model' => $model 
		) );
	}
	public function actionPrint(){
	
		if(isset($_POST['idList']) && isset($_POST['date_list'])) {
			Yii::app()->session['idList'] =  $_POST['idList'] ;
			Yii::app()->session['date_list'] =  $_POST['date_list'] ;
			if(isset($_POST['packing_date_list'])){
			Yii::app()->session['packing_date_list'] =  $_POST['packing_date_list'] ;
			}
			Yii::app()->session['expiry_val'] =  $_POST['expiry_val'] ;
			echo 'success';
		}
		else{
			echo 'failed';
		}
	
		
	}
	/*
	 * protected function processActions($model = null)
	 * {
	 * parent::processActions($model);
	 * //$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new ItemDetail ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "itemDetail/view" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
				}
			case 'create' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'add' 
							),
							'visible' => $model->checkPermission ( "itemDetail/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
				}
				break;
			case 'index' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'add' 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			case 'admin' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'add' 
							),
							'visible' => $model->checkPermission ( "itemDetail/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			default :
			case 'view' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'visible' => $model->checkPermission ( "itemDetail/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
					// $this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'add' 
							),
							'visible' => $model->checkPermission ( "itemDetail/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => array (
									'update',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "itemDetail/update" ) == "true",
							'icon' => 'icon-edit icon-white' 
					);
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
}