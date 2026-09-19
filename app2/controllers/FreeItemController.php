<?php
namespace app\controllers;

use app\components\Criteria;
use app\components\Ui;
use app\models\FreeItem;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemVendor;
use app\models\UserRole;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/FreeItemController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class FreeItemController extends BaseUiController {


	public function actionItemList() {
		$user = Yii::$app->user->model;
		$vendor_arr = [];
		
		$exist = [];
		$lists = [];
		$term = Yii::$app->request->get( 'term' );
		
		$query = ItemDetail::find();
		if($user->role_id != 1){
			$query1 = ItemVendor::find();
        $query1->orderBy(['id' => SORT_DESC]);
			$query1->andWhere('vendor_id =' . $user->id);
			$itemvendors = $query1->all();
			if($itemvendors){
				foreach($itemvendors as $itemvendor){
				$exist[] = $itemvendor->item_detail_id;
				}
			}
			$query->andWhere(['id' => $exist]);
		}
		Criteria::compare($query, 'bar_code', $term, true);
		$query->limit(100);
	
		$query->andWhere('status =' . Item::STATUS_ACTIVE);
		$itemdetails = $query->all();
		if ($itemdetails != null) {
			foreach ( $itemdetails as $itemdetail ) {
				$item = Item::findOne($itemdetail->item_id);
				if($item){
				$lists [] = [
						'item_id' => $item->id,
						'bar_code' => $itemdetail->bar_code,
						'item_detail_id' => $itemdetail->id,
						'name' => $item->title,
						'category_id' => $item->category_id,
						'company_id' => $item->company_id
	
				];
				}
			}
		}
	
	
		echo json_encode ( $lists );
	}
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

	public function actionCreate() 
	{
		$model = new FreeItem;

		$this->performAjaxValidation($model, 'free-item-form');

		if (isset($_POST['FreeItem'])) {
			$model->load($_POST, 'FreeItem');
            if(isset($_POST['FreeItem']['item_detail_id'])){
            	$item_detail = ItemDetail::findOne($_POST['FreeItem']['item_detail_id']);
            	if($item_detail){
            		$model->item_id = $item_detail->item_id;
            	}
            }
            $user = Yii::$app->user->model;
            $role = UserRole::findOne(['title'=>'Admin']);
            if($user->role_id != $role->id ){
            	$model->status = FreeItem::STATUS_INACTIVE;
            }
            
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
		
		$this->performAjaxValidation($model, 'free-item-form');

		if (isset($_POST['FreeItem'])) {
			$model->load($_POST, 'FreeItem');

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
		$dataProvider = new ActiveDataProvider(['query' => FreeItem::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => FreeItem::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new FreeItem(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['FreeItem']))
		{
			$model->load($_GET, 'FreeItem');
			return $this->renderPartial('_list', [
					'dataProvider' => $model->search(),
					'model' => $model,
			]);
		}
			
		return $this->renderPartial('_search', [
				'model' => $model,
		]);
	}
	public function actionAdmin($id = null) 
	{
		$model = new FreeItem(['scenario' => 'search']);
		$this->updateMenuItems($model);
	    if($id != null){
	    	$_GET['FreeItem']['item_id'] = $id;
	    }
		if (isset($_GET['FreeItem']))
			$model->load($_GET, 'FreeItem');

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
		if ( $model == null ) $model = new FreeItem();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('freeItem/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('freeItem/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('freeItem/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('freeItem/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('freeItem/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					$this->menu[] = ['label'=>'Manage Items', 'url'=>['item/admin'],'icon'=>'icon-wrench icon-white'];
						}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = ['label'=>'Manage Items', 'url'=>['item/admin'],'icon'=>'icon-wrench icon-white'];
						
					/* $this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>'Manage', 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>'Update', 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				
				 */}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}