<?php
namespace app\controllers;

use app\components\Ui;
use app\models\CreditNote;
use app\models\ItemReturn;
use app\models\ItemReturnItem;
use app\models\PurchaseBill;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/ItemReturnController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class ItemReturnController extends BaseUiController {



	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
		$itemReturnItem = new ItemReturnItem(['scenario' => 'search']);
		$_GET ['ItemReturnItem']['return_id'] = $id;
		if (isset ( $_GET ['ItemReturnItem'] ))
			$itemReturnItem->load($_GET, 'ItemReturnItem');
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model,'itemReturnItem'=>$itemReturnItem
		]);
	}

	public function actionCreate() 
	{
		$model = new ItemReturn;

		$this->performAjaxValidation($model, 'item-return-form');

		if (isset($_POST['ItemReturn'])) {
			$model->load($_POST, 'ItemReturn');

			if ($model->save()) {
				if (Yii::$app->request->isAjax)
					Yii::$app->end();
				else
					return $this->redirect(['view', 'id' => $model->id]);
			}
		}
		$this->updateMenuItems($model);
		return $this->render('create', [ 'model' => $model]);
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id);
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'item-return-form');
           $old_credit_note = $model->credit_note_no;
		if (isset($_POST['ItemReturn'])) {
			$model->load($_POST, 'ItemReturn');

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
		$dataProvider = new ActiveDataProvider(['query' => ItemReturn::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => ItemReturn::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new ItemReturn(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemReturn']))
		{
			$model->load($_GET, 'ItemReturn');
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
		$model = new ItemReturn(['scenario' => 'search']);
		$this->updateMenuItems($model);
		$columns = [];
		if (isset ( $_POST ['ItemReturn']['columns'] )){
			$columns = $_POST ['ItemReturn']['columns'];
		}
		if (isset($_GET['ItemReturn']))
			$model->load($_GET, 'ItemReturn');
			$columns = $model->getColumns($columns);
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search (), $columns
						);
			}
		return $this->render('admin', [
			'model' => $model,
		]);
	}

	public function actionList()
	{
		$model = new ItemReturn(['scenario' => 'search']);
		$this->updateMenuItems($model);
		//$model->status = PurchaseBill::STATUS_UNAPPROVED;
		// if (isset($_GET['ItemReturn']))
			// $model->load($_GET, 'ItemReturn');
			
			return $this->render('list', [
					'model' => $model,
			]);
	}

	public function actionMerge(){
		$vendor_ids = [];

		
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$query = ItemReturn::find();
			//$criteria->addInCondition('id', $_POST['idList']);
			$query->andWhere('id ='.$_POST['idList']['0']);
			$returnItem = $query->one();
			
			if($returnItem){
				$query_2 = ItemReturn::find();
				$query_2->andWhere(['id' => $_POST['idList']]);
				$query_2->andWhere('id !='.$returnItem->id);
				$bills = $query_2->all();
				if($bills){
					$vendor_ids[] = $_POST['vendor_id'];
					foreach($bills as $delbill){
						$vendor_ids[] = $delbill->vendor_id;
						$query1 = ItemReturnItem::find();
						$query1->andWhere('return_id ='.$delbill->id);
						$billdetails = $query1->all();
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
		//$this->actions [] = array('label'=>'Add Skill', 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	}*/
	protected function updateMenuItems($model = null)
	{	
		// create static model if model is null
		if ( $model == null ) $model = new ItemReturn();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('itemReturn/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemReturn/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemReturn/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemReturn/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemReturn/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemReturn/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemReturn/create'),'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemReturn/admin'),'icon'=>'icon-wrench icon-white'];
					//$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					//$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					//$this->menu[] = array('label'=>'Update', 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}