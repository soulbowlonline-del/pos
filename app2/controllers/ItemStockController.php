<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemStock;
use app\models\ItemVendor;
use app\models\StockLog;
use app\models\User;
use app\models\Vendor;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/ItemStockController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class ItemStockController extends BaseUiController {



	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
	
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}
	public function actionAjaxItemDetail() {
		$option = '';
		$alreadypermissions = [];
		$response['outlet_id'] = '';
		$response['batch_number'] = '';
		if (isset ( $_POST ['item_detail_id'] )) {
			$itemDetail = ItemDetail::findOne( $_POST ['item_detail_id'] );
			$query = ItemStock::find();
        $query->orderBy(['id' => SORT_DESC]);
			$query->andWhere(['item_detail_id' => \app\components\PostId::get('item_detail_id')]);
	        $itemstock = $query->one();

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
		$alreadypermissions = [];
		if (isset ( $_POST ['item_id'] )) {
	  $item = Item::findOne( $_POST ['item_id'] );
			$query = ItemDetail::find();
			$query->andWhere('status ='.ItemDetail::STATUS_ACTIVE);
			$query->andWhere(['item_id' => \app\components\PostId::get('item_id')]);
	
			$itemdetails = $query->all();
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
		$alreadypermissions = [];
		if (isset ( $_POST ['item_id'] )) {
			$vendor_ids = [];
			$query = ItemVendor::find();
        $query->orderBy(['id' => SORT_DESC]);
		    $query->andWhere(['item_detail_id' => \app\components\PostId::get('item_id')]);
	       $itemvendors = $query->all();
	       if($itemvendors){
	       	foreach($itemvendors as $itemvendor){
	       		$vendor_ids[] = $itemvendor->vendor_id;
	       	}
	       }
	      
	       $query1 = Vendor::find();
        $query1->orderBy(['id' => SORT_DESC]);
	       $query1->andWhere(['id' => $vendor_ids]);
	       $vendors = $query1->all();
	       
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
			$model = ItemStock ::model()->findByAttributes(['batch_number'=>$_POST['ItemStock']['batch_number'],'outlet_id'=>$_POST['ItemStock']['outlet_id'],
					'item_detail_id'=>$_POST['ItemStock']['item_detail_id'],'item_id'=>$_POST['ItemStock']['item_id']
			]);
			if($model == null){
				$model = new ItemStock;
				$model->load($_POST, 'ItemStock');
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
				$model->load($_POST, 'ItemStock');
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
				$itemDetail = ItemDetail::findOne($model->item_detail_id);
			$itemDetail->update_time = date('Y-m-d H:i:s');
				$itemDetail->updateAttributes(['update_time']);
				$item = Item::findOne($itemDetail->item_id);
				if($item){
					$item->update_time = date('Y-m-d H:i:s');
					$item->updateAttributes(['update_time']);
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
					$vendor = Vendor::findOne($stock->vendor_id);
					$email = '';
					if($vendor){
						$vendoruser = User::findOne(['id'=>$vendor->create_user_id]);
						if($vendoruser){
							$email = $vendoruser->email;
						}
					}
					if($email != ''){
						$from = (Yii::$app->params['mail_email'] ?? null) ;
						$to      = $email;
						$subject = 'A new MRS is added';
							
						$view = $this->renderPartial ( '/mail/mrs_added', [
								'mrs'=>$stock
						], true );
							
							
						//$stock->mailsend ( $to, $from, $subject, $view );
					}
						
				}
				}else{
					$stock->type_id = StockLog::TYPE_ADDED;
				}
				$stock->save();
				
				
				if (Yii::$app->request->isAjax)
					Yii::$app->end();
				else
					return $this->redirect(['item/admin']);
			}
		}
		$this->updateMenuItems($model);
		return $this->render('create', [ 'model' => $model]);
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id);
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'item-stock-form');

		if (isset($_POST['ItemStock'])) {
			$model->load($_POST, 'ItemStock');

			if ($model->save()) {
				return $this->redirect(['view', 'id' => $model->id]);
			}
		}
		$this->updateMenuItems($model);
		return $this->render('update', [
				'model' => $model,
				]);
	}

	public function actionDelete($id) 
	{
		$model = $this->loadModel($id);
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		if (Yii::$app->request->isPost) {
			$this->loadModel($id)->delete();

			if (!Yii::$app->request->isAjax)
				return $this->redirect(['admin']);
		} else
			throw new BadRequestHttpException('Your request is invalid.');
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new ActiveDataProvider(['query' => ItemStock::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => ItemStock::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new ItemStock(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemStock']))
		{
			$model->load($_GET, 'ItemStock');
			return $this->renderPartial('_list', [
					'dataProvider' => $model->search(),
					'model' => $model,
			]);
		}
			
		return $this->renderPartial('_search', [
				'model' => $model,
		]);
	}
	public function actionAdmin() 
	{
		$model = new ItemStock(['scenario' => 'search']);
		$this->updateMenuItems($model);
		
		if (isset($_GET['ItemStock']))
			$model->load($_GET, 'ItemStock');

		return $this->render('admin', [
			'model' => $model,
		]);
	}
	/*protected function processActions($model = null)
	{
		parent::processActions($model);
		//$this->actions [] = array('label'=>'Add Skill', 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	}*/
	protected function updateMenuItems($model = null)
	{	
		// create static model if model is null
		if ( $model == null ) $model = new ItemStock();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('itemStock/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemStock/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemStock/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemStock/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemStock/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemStock/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemStock/create'),'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemStock/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemStock/admin'),'icon'=>'icon-wrench icon-white'];
					$this->menu[] = ['label'=>'Delete', 'url'=>'#', 'linkOptions' => ['submit' => ['delete', 'id' => $model->id], 
					'confirm'=>'Are you sure you want to delete this item?'],'icon'=>'icon-remove icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemStock/create'),'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('itemStock/update', ['id' => $model->id]), 'icon'=>'icon-edit icon-white'];				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}