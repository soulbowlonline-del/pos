<?php
class OrderItemController extends GxController {
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
								/* 'index',
								'view', 'download', 'thumbnail' */),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'b2bdetail',
								'b2bsales',
								'view',
								'create',
								'update',
								'search',
								'admin',
								'delete'
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
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'OrderItem' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionCreate() {
		$model = new OrderItem ();
		
		$this->performAjaxValidation ( $model, 'order-item-form' );
		
		if (isset ( $_POST ['OrderItem'] )) {
			$model->setAttributes ( $_POST ['OrderItem'] );
			
			if ($model->save ()) {
				if (Yii::app ()->getRequest ()->getIsAjaxRequest ())
					Yii::app ()->end ();
				else
					$this->redirect ( array (
							'view',
							'id' => $model->id 
					) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model 
		) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'OrderItem' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'order-item-form' );
		
		if (isset ( $_POST ['OrderItem'] )) {
			$model->setAttributes ( $_POST ['OrderItem'] );
			
			if ($model->save ()) {
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
		$model = $this->loadModel ( $id, 'OrderItem' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'OrderItem' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	/* public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'OrderItem' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	} */
	public function actionIndex($id = null) {
		$model = new OrderItem ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
	  if($id != null){
	  	$_GET ['OrderItem']['item_id'] = $id;
	  }
		if (isset ( $_GET ['OrderItem'] ))
		$model->setAttributes ( $_GET ['OrderItem'] );
			
	
			$this->render ( 'index', array (
					'model' => $model,'id'=>$id
			) );
	}
	public function actionSearch() {
		$model = new Job ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['OrderItem'] )) {
			$model->setAttributes ( $_GET ['OrderItem'] );
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
		$model = new OrderItem ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		$columns = array();
		if (isset ( $_POST ['OrderItem']['start_date'] ) &&( $_POST ['OrderItem']['start_date'] !='')&& (isset ( $_POST ['OrderItem']['end_date'] ))
				&& (  $_POST ['OrderItem']['end_date'] !='')){
					$_GET['OrderItem']['start_date'] = $_POST ['OrderItem']['start_date'] ;
					$_GET['OrderItem']['end_date'] = $_POST ['OrderItem']['end_date'] ;
					Yii::app()->session['order_item_start_date'] =  $_POST ['OrderItem']['start_date'] ;
					Yii::app()->session['order_item_end_date']  = $_POST ['OrderItem']['end_date'] ;
		
		}else{
			if(!isset($_GET['OrderItem_page'])){
			if (!$this->isExportRequest () && Yii::app()->session['order_item_start_date'] == '' && Yii::app()->session['order_item_end_date'] == '') {
				$_GET ['OrderItem'] ['start_date'] = date('Y-m-d');
				$_GET ['OrderItem'] ['end_date'] = date('Y-m-d');
			Yii::app()->session['order_item_start_date'] = date('Y-m-d') ;
			Yii::app()->session['order_item_end_date']  = date('Y-m-d') ;
			Yii::app()->session['order_item_item_id'] ='';
			}
			}
		}
		if (isset ( $_POST ['OrderItem']['min_amt'] ) &&( $_POST ['OrderItem']['min_amt'] !='')&& (isset ( $_POST ['OrderItem']['max_amt'] ))
				&& (  $_POST ['OrderItem']['max_amt'] !='')){
					$_GET['OrderItem']['min_amt'] = $_POST ['OrderItem']['min_amt'] ;
					$_GET['OrderItem']['max_amt'] = $_POST ['OrderItem']['max_amt'] ;
					Yii::app()->session['order_item_min_amt'] =  $_POST ['OrderItem']['min_amt'] ;
					Yii::app()->session['order_item_max_amt']  = $_POST ['OrderItem']['max_amt'] ;
		
		}
		if (isset ( $_GET ['OrderItem']['item_id'] ) &&( $_GET ['OrderItem']['item_id'] !='')){
					Yii::app()->session['order_item_item_id'] =  $_GET ['OrderItem']['item_id'] ;
					
		}else{
			
			
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_item_id'] =	'';
				
			}
			
		//Yii::app()->session['order_item_item_id'] =	'';
		}
		
		
		if (isset ( $_GET ['OrderItem']['customer_id'] ) &&( $_GET ['OrderItem']['customer_id'] !='')){
					Yii::app()->session['order_item_customer_id'] =  $_GET ['OrderItem']['customer_id'] ;
					
		}else{
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_customer_id'] =	'';
				
			}
		
		}
		
		
		
		//employee_id
		if (isset ( $_GET ['OrderItem']['create_user_id'] ) &&( $_GET ['OrderItem']['create_user_id'] !='')){
					Yii::app()->session['order_item_create_user_id'] =  $_GET ['OrderItem']['create_user_id'] ;
					
		}else{
			
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_create_user_id'] =	'';
				
			}
			
		//Yii::app()->session['order_item_create_user_id'] =	'';
		}
		
				
		
		if (isset ( $_POST ['OrderItem']['columns'] )){
			$columns = $_POST ['OrderItem']['columns'];
		}
		
		if (isset ( $_GET ['OrderItem'] ))
			$model->setAttributes ( $_GET ['OrderItem'] );
			
			$columns = $model->getColumns($columns);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->search (), $columns
						);
			}
	
		$this->render ( 'admin', array (
				'model' => $model 
		) );
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
			$model = new OrderItem ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
			case 'create' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					);
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
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			case 'admin' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			default :
			case 'view' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Delete' ),
							'url' => '#',
							'linkOptions' => array (
									'submit' => array (
											'delete',
											'id' => $model->id 
									),
									'confirm' => 'Are you sure you want to delete this item?' 
							),
							'icon' => 'icon-remove icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => array (
									'update',
									'id' => $model->id 
							),
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
	
	public function actionB2bdetail() {
		$model = new B2bPurchaseBillDetail ( 'b2bsearch' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		$columns = array();
		if (isset ( $_POST ['B2bPurchaseBillDetail']['start_date'] ) &&( $_POST ['B2bPurchaseBillDetail']['start_date'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['end_date'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['end_date'] !='')){
					$_GET['B2bPurchaseBillDetail']['start_date'] = $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					$_GET['B2bPurchaseBillDetail']['end_date'] = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
					Yii::app()->session['order_item_start_date'] =  $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					Yii::app()->session['order_item_end_date']  = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
		
		}else{
			if(!isset($_GET['B2bPurchaseBillDetail_page'])){
			if (!$this->isExportRequest () && Yii::app()->session['order_item_start_date'] == '' && Yii::app()->session['order_item_end_date'] == '') {
				$_GET ['B2bPurchaseBillDetail'] ['start_date'] = date('Y-m-d');
				$_GET ['B2bPurchaseBillDetail'] ['end_date'] = date('Y-m-d');
			Yii::app()->session['order_item_start_date'] = date('Y-m-d') ;
			Yii::app()->session['order_item_end_date']  = date('Y-m-d') ;
			Yii::app()->session['order_item_item_id'] ='';
			}
			}
		}
		if (isset ( $_POST ['B2bPurchaseBillDetail']['min_amt'] ) &&( $_POST ['B2bPurchaseBillDetail']['min_amt'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['max_amt'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['max_amt'] !='')){
					$_GET['B2bPurchaseBillDetail']['min_amt'] = $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					$_GET['B2bPurchaseBillDetail']['max_amt'] = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
					Yii::app()->session['order_item_min_amt'] =  $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					Yii::app()->session['order_item_max_amt']  = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
		
		}
		if (isset ( $_GET ['B2bPurchaseBillDetail']['item_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['item_id'] !='')){
					Yii::app()->session['order_item_item_id'] =  $_GET ['B2bPurchaseBillDetail']['item_id'] ;
					
		}else{
			
			
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_item_id'] =	'';
				
			}
			
	
		}
		
		
		if (isset ( $_GET ['B2bPurchaseBillDetail']['vendor_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['vendor_id'] !='')){
					Yii::app()->session['order_item_vendor_id'] =  $_GET ['B2bPurchaseBillDetail']['vendor_id'] ;
					
		}else{
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_vendor_id'] =	'';
				
			}
		
		}
		
		
		
		//employee_id
		if (isset ( $_GET ['B2bPurchaseBillDetail']['create_user_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['create_user_id'] !='')){
					Yii::app()->session['order_item_create_user_id'] =  $_GET ['B2bPurchaseBillDetail']['create_user_id'] ;
					
		}else{
			
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_create_user_id'] =	'';
				
			}
			
		//Yii::app()->session['order_item_create_user_id'] =	'';
		}
		
				
		
		if (isset ( $_POST ['B2bPurchaseBillDetail']['columns'] )){
			$columns = $_POST ['B2bPurchaseBillDetail']['columns'];
		}
		
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
			
			$columns = $model->getColumns($columns);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->b2bsearch (), $columns
						);
			}
	
		$this->render ( 'b2bdetail', array (
				'model' => $model 
		) );
	}
public function actionB2bsales() {
		$model = new B2bPurchaseBillDetail ( 'b2bsearch' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		$columns = array();
	//	$_GET['B2bPurchaseBillDetail']['status'] = PurchaseBill::STATUS_APPROVED;
		if (isset ( $_POST ['B2bPurchaseBillDetail']['start_date'] ) &&( $_POST ['B2bPurchaseBillDetail']['start_date'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['end_date'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['end_date'] !='')){
					$_GET['B2bPurchaseBillDetail']['start_date'] = $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					$_GET['B2bPurchaseBillDetail']['end_date'] = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
					Yii::app()->session['order_item_start_date'] =  $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					Yii::app()->session['order_item_end_date']  = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
		
		}else{
			if(!isset($_GET['B2bPurchaseBillDetail_page'])){
			if (!$this->isExportRequest () && Yii::app()->session['order_item_start_date'] == '' && Yii::app()->session['order_item_end_date'] == '') {
				$_GET ['B2bPurchaseBillDetail'] ['start_date'] = date('Y-m-d');
				$_GET ['B2bPurchaseBillDetail'] ['end_date'] = date('Y-m-d');
			Yii::app()->session['order_item_start_date'] = date('Y-m-d') ;
			Yii::app()->session['order_item_end_date']  = date('Y-m-d') ;
			Yii::app()->session['order_item_item_id'] ='';
			}
			}
		}
		if (isset ( $_POST ['B2bPurchaseBillDetail']['min_amt'] ) &&( $_POST ['B2bPurchaseBillDetail']['min_amt'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['max_amt'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['max_amt'] !='')){
					$_GET['B2bPurchaseBillDetail']['min_amt'] = $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					$_GET['B2bPurchaseBillDetail']['max_amt'] = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
					Yii::app()->session['order_item_min_amt'] =  $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					Yii::app()->session['order_item_max_amt']  = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
		
		}
		if (isset ( $_GET ['B2bPurchaseBillDetail']['item_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['item_id'] !='')){
					Yii::app()->session['order_item_item_id'] =  $_GET ['B2bPurchaseBillDetail']['item_id'] ;
					
		}else{
			
			
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_item_id'] =	'';
				
			}
			
	
		}
		
		
		if (isset ( $_GET ['B2bPurchaseBillDetail']['vendor_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['vendor_id'] !='')){
					Yii::app()->session['order_item_vendor_id'] =  $_GET ['B2bPurchaseBillDetail']['vendor_id'] ;
					
		}else{
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_vendor_id'] =	'';
				
			}
		
		}
		
		
		
		//employee_id
		if (isset ( $_GET ['B2bPurchaseBillDetail']['create_user_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['create_user_id'] !='')){
					Yii::app()->session['order_item_create_user_id'] =  $_GET ['B2bPurchaseBillDetail']['create_user_id'] ;
					
		}else{
			
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_create_user_id'] =	'';
				
			}
			
		//Yii::app()->session['order_item_create_user_id'] =	'';
		}
		
				
		
		if (isset ( $_POST ['B2bPurchaseBillDetail']['columns'] )){
			$columns = $_POST ['B2bPurchaseBillDetail']['columns'];
		}
		
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
			
			$columns = $model->getSalesColumns($columns);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->b2bsearch (), $columns
						);
			}
	
		$this->render ( 'b2bsales', array (
				'model' => $model 
		) );
	}
}