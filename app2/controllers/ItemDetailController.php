<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemStock;
use app\models\ItemTax;
use app\models\OrderItem;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/ItemDetailController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class ItemDetailController extends BaseUiController {
	public function actionMakeInactive(){
		$items = Item::find()->all();
		if($items){
			foreach($items as $item){
				
				if($item->getTotalRemainingQuantity() == 0){
					$end_date = date('Y-m-d');
					$start_date = date('Y-m-d', strtotime('-3 months', strtotime($end_date)));
					
					$query = OrderItem::find();
					$query->andWhere(['between', 'create_date', $start_date, $end_date]);
					$query->andWhere('item_id ='.$item->id);
					$orderitems = $query->one();
					$count = 0;
					if($orderitems ==null){
						$count = $count+1;
						$query1 = ItemDetail::find();
						$query1->andWhere('item_id ='.$item->id);
						$details = $query1->all();
						if($details){
							foreach($details as $detail){
								$detail->status = ItemDetail::STATUS_INACTIVE;
								$detail->updateAttributes(['status']);
							}
						}
						$item->status = Item::STATUS_INACTIVE;
						$item->updateAttributes(['status']);
						echo 'id'.$item->title.'name'.$item->title;
						echo '<br>';
					}
					echo $count;
					//$itemDetail->company_bar_code = 1;
					//$itemDetail->updateAttributes(array('company_bar_code'));
				}
			}
		}
	}
	/*public function actionBarcodes(){
		$itemDetails = ItemDetail::find()->all();
		if($itemDetails){
			foreach($itemDetails as $itemDetail){
				$strlen = strlen($itemDetail->bar_code);
				if($strlen <11){
					$itemDetail->company_bar_code = 1;
					$itemDetail->updateAttributes(array('company_bar_code'));
				}
			}
		}
	}*/
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionView($id) {
		$model = $this->loadModel($id);
		if (! ($model->checkPermission ( 'ItemDetail/view' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
			
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		return $this->render( 'view', [
				'model' => $model 
		] );
	}
	public function actionAdd() {
		$model = new ItemDetail ();
		if (! ($model->checkPermission ( 'ItemDetail/create' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation( $model, 'item-detail-form' );
		
		if (isset ( $_POST ['ItemDetail'] )) {
			if (isset ( $_POST ['ItemDetail']['outlet_id'])) {
			$outlet_ids = $_POST ['ItemDetail'] ['outlet_id'];
			$set = true;
			foreach ( $outlet_ids as $outlet_id ) {
				$model = ItemDetail::findOne( [
						'bar_code' => $_POST ['ItemDetail'] ['bar_code'],
						'outlet_id' => $_POST ['ItemDetail'] ['outlet_id'],
						'item_id' => $_POST ['ItemDetail'] ['item_id'],
				] );
				if($model == null){
					$model = new ItemDetail();
				}
				$model->load($_POST, 'ItemDetail');
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
				return $this->redirect(['admin']);
			}else{
				Yii::$app->user->setFlash ( 'error', 'There is some error. Please try again' );
			}
			
			
				Yii::$app->user->setFlash ( 'success', 'Subitem Info is saved sucessfully' );
			}else{
				Yii::$app->user->setFlash ( 'error', 'Please select Outlet' );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'add', [
				'model' => $model,
				'id' => '' 
		] );
	}
	public function actionCreate($id = null) {
		if($id != null)
		$item = $this->loadModel($id, Item::class);
		$model = new ItemDetail ();
		if (! ($model->checkPermission ( 'ItemDetail/create' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation( $model, 'item-detail-form' );
		
		if (isset ( $_POST ['ItemDetail'] )) {
			if (isset ( $_POST ['ItemDetail']['outlet_id'])) {
			$outlet_ids = $_POST ['ItemDetail'] ['outlet_id'];
			$set = true;
			foreach ( $outlet_ids as $outlet_id ) {
				$model = ItemDetail::findOne( [
						'bar_code' => $_POST ['ItemDetail'] ['bar_code'],
						'outlet_id' => $_POST ['ItemDetail'] ['outlet_id'],
						'item_id' => $_POST ['ItemDetail'] ['item_id'],
				] );
				if($model == null){
					$model = new ItemDetail();
				}
			
				$model->load($_POST, 'ItemDetail');
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
							$item = $this->loadModel($_POST ['ItemDetail']['item_id'], Item::class);
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
				Yii::$app->user->setFlash ( 'success', 'Subitem Info is saved sucessfully' );
			}else{
				Yii::$app->user->setFlash ( 'error', 'There is some error. Please try again' );
			}
				/*
				 * if (Yii::$app->request->isAjax)
				 * Yii::$app->end();
				 * else
				 * $this->redirect(array('view', 'id' => $model->id));
				 */
			}else{
				Yii::$app->user->setFlash ( 'error', 'Pleas select Outlet' );
			}
			
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'create', [
				'model' => $model,
				'id' => $id 
		] );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		$item = $this->loadModel($model->item_id, Item::class);
		if (! ($model->checkPermission ( 'ItemDetail/update' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation( $model, 'item-detail-form' );
		
		if (isset ( $_POST ['ItemDetail'] )) {
			$model->load($_POST, 'ItemDetail');
			if(isset($_POST ['ItemDetail']['company_bar_code'])){
				$model->company_bar_code = $_POST ['ItemDetail']['company_bar_code'];
			}
			$model->update_time = date('Y-m-d H:i:s');
			
			if ($model->save ()) {
				$batch_no =  User::randomBarcode('5');
				$outlet_ids = $_POST ['ItemDetail'] ['outlet_id'];
				foreach ( $outlet_ids as $outlet_id ) {
					$itemstock = ItemStock ::model()->findByAttributes(['outlet_id'=>$outlet_id,
							'vendor_id'=>'0','item_detail_id'=>$model->id,
					]);
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
					$itemtax = ItemTax ::model()->findByAttributes(['item_detail_id'=>$model->id,'tax_id'=>$_POST ['ItemDetail'] ['tax_id']
						
					]);
					if($itemtax == null){
						$itemtax = new ItemStock;
					}
					$itemtax = new ItemTax ();
					$itemtax->item_detail_id = $model->id;
					$itemtax->tax_id = $_POST ['ItemDetail'] ['tax_id'];
					$itemtax->save ();
				}
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
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new ActiveDataProvider(['query' => ItemDetail::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => ItemDetail::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', [
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionSearch() {
		$model = new ItemDetail(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['ItemDetail'] )) {
			$model->load($_GET, 'ItemDetail');
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
		
		$model = new ItemDetail(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['ItemDetail'] ))
			$model->load($_GET, 'ItemDetail');
		if (isset ( $_GET ['id'] )) {
			$item = $this->loadModel($_GET ['id'], Item::class);
			$model->item_id = $item->title;
		}
		return $this->render( 'admin', [
				'model' => $model 
		] );
	}
	public function actionPrint(){
	
		if(isset($_POST['idList']) && isset($_POST['date_list'])) {
			Yii::$app->session['idList'] =  $_POST['idList'] ;
			Yii::$app->session['date_list'] =  $_POST['date_list'] ;
			if(isset($_POST['packing_date_list'])){
			Yii::$app->session['packing_date_list'] =  $_POST['packing_date_list'] ;
			}
			Yii::$app->session['expiry_val'] =  $_POST['expiry_val'] ;
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
	 * //$this->actions [] = array('label'=>'Add Skill', 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new ItemDetail ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ),
							'url' => Ui::to('itemDetail/view', ['id' => $model->id]),
							'visible' => $model->checkPermission ( "itemDetail/view" ) == "true",
							'icon' => 'icon-plus icon-white' 
					];
				}
			case 'create' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'add' 
							],
							'visible' => $model->checkPermission ( "itemDetail/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					];
					// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
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
									'add' 
							],
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			case 'admin' :
				{
					// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'add' 
							],
							'visible' => $model->checkPermission ( "itemDetail/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			default :
			case 'view' :
				{
					// $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'visible' => $model->checkPermission ( "itemDetail/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					];
					// $this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'add' 
							],
							'visible' => $model->checkPermission ( "itemDetail/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => Ui::to('itemDetail/update', ['id' => $model->id]),
							'visible' => $model->checkPermission ( "itemDetail/update" ) == "true",
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
}