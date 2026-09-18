<?php
namespace app\controllers;

use app\components\Ui;
use app\models\OrderItem;
use app\models\PurchaseBill;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/OrderItemController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class OrderItemController extends BaseUiController {
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionView($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		return $this->render( 'view', [
				'model' => $model 
		] );
	}
	public function actionCreate() {
		$model = new OrderItem ();
		
		$this->performAjaxValidation( $model, 'order-item-form' );
		
		if (Yii::$app->request->post('OrderItem') !== null) {
			$model->load(Yii::$app->request->post());
			
			if ($model->save ()) {
				if (Yii::$app->request->isAjax)
					Yii::$app->end();
				else
					return $this->redirect( [
							'view',
							'id' => $model->id 
					] );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'create', [
				'model' => $model 
		] );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation( $model, 'order-item-form' );
		
		if (Yii::$app->request->post('OrderItem') !== null) {
			$model->load(Yii::$app->request->post());
			
			if ($model->save ()) {
				return $this->redirect( [
						'view',
						'id' => $model->id 
				] );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'update', [
				'model' => $model 
		] );
	}
	public function actionDelete($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		if (Yii::$app->request->isPost) {
			$this->loadModel($id)->delete ();
			
			if (! Yii::$app->request->isAjax)
				return $this->redirect( [
						'admin' 
				] );
		} else
			throw new BadRequestHttpException(Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	/* public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new ActiveDataProvider(['query' => OrderItem::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            'sort' => ['defaultOrder' => OrderItem::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	} */
	public function actionIndex($id = null) {
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
	  if($id != null){
	  	$_GET ['OrderItem']['item_id'] = $id;
	  }
		if (Yii::$app->request->get('OrderItem') !== null)
		$model->load(Yii::$app->request->queryParams);
			
	
			return $this->render( 'index', [
					'model' => $model,'id'=>$id
			] );
	}
	public function actionSearch() {
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (Yii::$app->request->get('OrderItem') !== null) {
			$model->load(Yii::$app->request->queryParams);
			return $this->renderPartial( '_list', [
					'dataProvider' => $model->search (),
					'model' => $model 
			] );
		}
		
		return $this->renderPartial( '_search', [
				'model' => $model 
		] );
	}
	
	public function actionAdmin() {
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['OrderItem']['start_date'] ) &&( $_POST ['OrderItem']['start_date'] !='')&& (isset ( $_POST ['OrderItem']['end_date'] ))
				&& (  $_POST ['OrderItem']['end_date'] !='')){
					$_GET['OrderItem']['start_date'] = $_POST ['OrderItem']['start_date'] ;
					$_GET['OrderItem']['end_date'] = $_POST ['OrderItem']['end_date'] ;
					Yii::$app->session['order_item_start_date'] =  $_POST ['OrderItem']['start_date'] ;
					Yii::$app->session['order_item_end_date']  = $_POST ['OrderItem']['end_date'] ;
		
		}else{
			if(!isset($_GET['OrderItem_page'])){
			if (!$this->isExportRequest() && Yii::$app->session['order_item_start_date'] == '' && Yii::$app->session['order_item_end_date'] == '') {
				$_GET ['OrderItem'] ['start_date'] = date('Y-m-d');
				$_GET ['OrderItem'] ['end_date'] = date('Y-m-d');
			Yii::$app->session['order_item_start_date'] = date('Y-m-d') ;
			Yii::$app->session['order_item_end_date']  = date('Y-m-d') ;
			Yii::$app->session['order_item_item_id'] ='';
			}
			}
		}
		if (isset ( $_POST ['OrderItem']['min_amt'] ) &&( $_POST ['OrderItem']['min_amt'] !='')&& (isset ( $_POST ['OrderItem']['max_amt'] ))
				&& (  $_POST ['OrderItem']['max_amt'] !='')){
					$_GET['OrderItem']['min_amt'] = $_POST ['OrderItem']['min_amt'] ;
					$_GET['OrderItem']['max_amt'] = $_POST ['OrderItem']['max_amt'] ;
					Yii::$app->session['order_item_min_amt'] =  $_POST ['OrderItem']['min_amt'] ;
					Yii::$app->session['order_item_max_amt']  = $_POST ['OrderItem']['max_amt'] ;
		
		}
		if (isset ( $_GET ['OrderItem']['item_id'] ) &&( $_GET ['OrderItem']['item_id'] !='')){
					Yii::$app->session['order_item_item_id'] =  $_GET ['OrderItem']['item_id'] ;
					
		}else{
			
			
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_item_id'] =	'';
				
			}
			
		//Yii::$app->session['order_item_item_id'] =	'';
		}
		
		
		if (isset ( $_GET ['OrderItem']['customer_id'] ) &&( $_GET ['OrderItem']['customer_id'] !='')){
					Yii::$app->session['order_item_customer_id'] =  $_GET ['OrderItem']['customer_id'] ;
					
		}else{
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_customer_id'] =	'';
				
			}
		
		}
		
		
		
		//employee_id
		if (isset ( $_GET ['OrderItem']['create_user_id'] ) &&( $_GET ['OrderItem']['create_user_id'] !='')){
					Yii::$app->session['order_item_create_user_id'] =  $_GET ['OrderItem']['create_user_id'] ;
					
		}else{
			
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_create_user_id'] =	'';
				
			}
			
		//Yii::$app->session['order_item_create_user_id'] =	'';
		}
		
				
		
		if (isset ( $_POST ['OrderItem']['columns'] )){
			$columns = $_POST ['OrderItem']['columns'];
		}
		
		if (Yii::$app->request->get('OrderItem') !== null)
			$model->load(Yii::$app->request->queryParams);
			
			$columns = $model->getColumns($columns);
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search (), $columns
						);
			}
	
		return $this->render( 'admin', [
				'model' => $model 
		] );
	}
	/*
	 * protected function processActions($model = null)
	 * {
	 * parent::processActions($model);
	 * //$this->actions [] = array('label'=>'Add Skill', 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new OrderItem ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ),
							'url' => Ui::to('orderItem/view', ['id' => $model->id]),
							'icon' => 'icon-plus icon-white' 
					];
				}
			case 'create' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'List' ),
							'url' => [
									'index' 
							],
							'icon' => 'icon-th-list icon-white' 
					];
				}
				break;
			case 'index' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			case 'admin' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'List' ),
							'url' => [
									'index' 
							],
							'icon' => 'icon-th-list icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			default :
			case 'view' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'List' ),
							'url' => [
									'index' 
							],
							'icon' => 'icon-th-list icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Delete' ),
							'url' => '#',
							'linkOptions' => [
									'submit' => [
											'delete',
											'id' => $model->id 
									],
									'confirm' => 'Are you sure you want to delete this item?' 
							],
							'icon' => 'icon-remove icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => Ui::to('orderItem/update', ['id' => $model->id]),
							'icon' => 'icon-edit icon-white' 
					];
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
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['B2bPurchaseBillDetail']['start_date'] ) &&( $_POST ['B2bPurchaseBillDetail']['start_date'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['end_date'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['end_date'] !='')){
					$_GET['B2bPurchaseBillDetail']['start_date'] = $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					$_GET['B2bPurchaseBillDetail']['end_date'] = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
					Yii::$app->session['order_item_start_date'] =  $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					Yii::$app->session['order_item_end_date']  = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
		
		}else{
			if(!isset($_GET['B2bPurchaseBillDetail_page'])){
			if (!$this->isExportRequest() && Yii::$app->session['order_item_start_date'] == '' && Yii::$app->session['order_item_end_date'] == '') {
				$_GET ['B2bPurchaseBillDetail'] ['start_date'] = date('Y-m-d');
				$_GET ['B2bPurchaseBillDetail'] ['end_date'] = date('Y-m-d');
			Yii::$app->session['order_item_start_date'] = date('Y-m-d') ;
			Yii::$app->session['order_item_end_date']  = date('Y-m-d') ;
			Yii::$app->session['order_item_item_id'] ='';
			}
			}
		}
		if (isset ( $_POST ['B2bPurchaseBillDetail']['min_amt'] ) &&( $_POST ['B2bPurchaseBillDetail']['min_amt'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['max_amt'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['max_amt'] !='')){
					$_GET['B2bPurchaseBillDetail']['min_amt'] = $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					$_GET['B2bPurchaseBillDetail']['max_amt'] = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
					Yii::$app->session['order_item_min_amt'] =  $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					Yii::$app->session['order_item_max_amt']  = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
		
		}
		if (isset ( $_GET ['B2bPurchaseBillDetail']['item_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['item_id'] !='')){
					Yii::$app->session['order_item_item_id'] =  $_GET ['B2bPurchaseBillDetail']['item_id'] ;
					
		}else{
			
			
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_item_id'] =	'';
				
			}
			
	
		}
		
		
		if (isset ( $_GET ['B2bPurchaseBillDetail']['vendor_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['vendor_id'] !='')){
					Yii::$app->session['order_item_vendor_id'] =  $_GET ['B2bPurchaseBillDetail']['vendor_id'] ;
					
		}else{
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_vendor_id'] =	'';
				
			}
		
		}
		
		
		
		//employee_id
		if (isset ( $_GET ['B2bPurchaseBillDetail']['create_user_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['create_user_id'] !='')){
					Yii::$app->session['order_item_create_user_id'] =  $_GET ['B2bPurchaseBillDetail']['create_user_id'] ;
					
		}else{
			
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_create_user_id'] =	'';
				
			}
			
		//Yii::$app->session['order_item_create_user_id'] =	'';
		}
		
				
		
		if (isset ( $_POST ['B2bPurchaseBillDetail']['columns'] )){
			$columns = $_POST ['B2bPurchaseBillDetail']['columns'];
		}
		
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
			
			$columns = $model->getColumns($columns);
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->b2bsearch (), $columns
						);
			}
	
		return $this->render( 'b2bdetail', [
				'model' => $model 
		] );
	}
public function actionB2bsales() {
		$model = new B2bPurchaseBillDetail ( 'b2bsearch' );
		$this->updateMenuItems ( $model );
		$columns = [];
	//	$_GET['B2bPurchaseBillDetail']['status'] = PurchaseBill::STATUS_APPROVED;
		if (isset ( $_POST ['B2bPurchaseBillDetail']['start_date'] ) &&( $_POST ['B2bPurchaseBillDetail']['start_date'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['end_date'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['end_date'] !='')){
					$_GET['B2bPurchaseBillDetail']['start_date'] = $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					$_GET['B2bPurchaseBillDetail']['end_date'] = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
					Yii::$app->session['order_item_start_date'] =  $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					Yii::$app->session['order_item_end_date']  = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
		
		}else{
			if(!isset($_GET['B2bPurchaseBillDetail_page'])){
			if (!$this->isExportRequest() && Yii::$app->session['order_item_start_date'] == '' && Yii::$app->session['order_item_end_date'] == '') {
				$_GET ['B2bPurchaseBillDetail'] ['start_date'] = date('Y-m-d');
				$_GET ['B2bPurchaseBillDetail'] ['end_date'] = date('Y-m-d');
			Yii::$app->session['order_item_start_date'] = date('Y-m-d') ;
			Yii::$app->session['order_item_end_date']  = date('Y-m-d') ;
			Yii::$app->session['order_item_item_id'] ='';
			}
			}
		}
		if (isset ( $_POST ['B2bPurchaseBillDetail']['min_amt'] ) &&( $_POST ['B2bPurchaseBillDetail']['min_amt'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['max_amt'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['max_amt'] !='')){
					$_GET['B2bPurchaseBillDetail']['min_amt'] = $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					$_GET['B2bPurchaseBillDetail']['max_amt'] = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
					Yii::$app->session['order_item_min_amt'] =  $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					Yii::$app->session['order_item_max_amt']  = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
		
		}
		if (isset ( $_GET ['B2bPurchaseBillDetail']['item_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['item_id'] !='')){
					Yii::$app->session['order_item_item_id'] =  $_GET ['B2bPurchaseBillDetail']['item_id'] ;
					
		}else{
			
			
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_item_id'] =	'';
				
			}
			
	
		}
		
		
		if (isset ( $_GET ['B2bPurchaseBillDetail']['vendor_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['vendor_id'] !='')){
					Yii::$app->session['order_item_vendor_id'] =  $_GET ['B2bPurchaseBillDetail']['vendor_id'] ;
					
		}else{
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_vendor_id'] =	'';
				
			}
		
		}
		
		
		
		//employee_id
		if (isset ( $_GET ['B2bPurchaseBillDetail']['create_user_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['create_user_id'] !='')){
					Yii::$app->session['order_item_create_user_id'] =  $_GET ['B2bPurchaseBillDetail']['create_user_id'] ;
					
		}else{
			
			if ($this->isExportRequest()) {
				
			}else{
				Yii::$app->session['order_item_create_user_id'] =	'';
				
			}
			
		//Yii::$app->session['order_item_create_user_id'] =	'';
		}
		
				
		
		if (isset ( $_POST ['B2bPurchaseBillDetail']['columns'] )){
			$columns = $_POST ['B2bPurchaseBillDetail']['columns'];
		}
		
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
			
			$columns = $model->getSalesColumns($columns);
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->b2bsearch (), $columns
						);
			}
	
		return $this->render( 'b2bsales', [
				'model' => $model 
		] );
	}
}