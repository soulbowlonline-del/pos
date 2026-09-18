<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Item;
use app\models\VendorSchemes;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/VendorSchemesController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class VendorSchemesController extends BaseUiController {



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
	public function actionAdd($id = null)
	{
		$model = new VendorSchemes;
	
		$this->performAjaxValidation($model, 'vendor-schemes-form');
		if($id != null){
			$model->item_id =$id;
		}
		
		if (Yii::$app->request->post('VendorSchemes') !== null) {
			$model->load(Yii::$app->request->post());
			if(isset($_POST['VendorSchemes']['item_id'])){
				$model->item_id = implode(',',$_POST['VendorSchemes']['item_id']);
			}
			if ($model->save()) {
				Yii::$app->user->setFlash ( 'success', 'Vendor Scheme is saved sucessfully' );
				/* if (Yii::$app->request->isAjax)
					Yii::$app->end();
					else
						return $this->redirect(array('view', 'id' => $model->id)); */
			}
		}
		$model->item_id = explode(',',$model->item_id);
		$this->updateMenuItems($model);
		return $this->render('add', [ 'model' => $model,'id'=>$id]);
	}
	public function actionCreate() 
	{
		$model = new VendorSchemes;
		$list = [];
		$this->performAjaxValidation($model, 'vendor-schemes-form');
		$items = Item::find()->all();
		if($items){
			foreach($items as $item){
				$list[] = $item->id;
			}
		}
		$model->item_id = $list;
		if (Yii::$app->request->post('VendorSchemes') !== null) {
			$model->load(Yii::$app->request->post());
if(isset($_POST['VendorSchemes']['item_id'])){
	$model->item_id = implode(',',$_POST['VendorSchemes']['item_id']);
}
			if ($model->save()) {
				Yii::$app->user->setFlash ( 'success', 'Vendor Scheme is saved sucessfully' );
			/* 	if (Yii::$app->request->isAjax)
					Yii::$app->end();
				else
					return $this->redirect(array('view', 'id' => $model->id)); */
			}
		}
		$this->updateMenuItems($model);
		return $this->render('create', [ 'model' => $model]);
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id);
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'vendor-schemes-form');

		if (Yii::$app->request->post('VendorSchemes') !== null) {
			$model->load(Yii::$app->request->post());
			if(isset($_POST['VendorSchemes']['item_id'])){
				$model->item_id = implode(',',$_POST['VendorSchemes']['item_id']);
			}
			if ($model->save()) {
				return $this->redirect(['view', 'id' => $model->id]);
			}
		}
		if($model->item_id != '')
		$model->item_id = explode(',',$model->item_id);
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
		$dataProvider = new ActiveDataProvider(['query' => VendorSchemes::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            'sort' => ['defaultOrder' => VendorSchemes::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new VendorSchemes(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (Yii::$app->request->get('VendorSchemes') !== null)
		{
			$model->load(Yii::$app->request->queryParams);
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
		$model = new VendorSchemes(['scenario' => 'search']);
		$this->updateMenuItems($model);
		
		if (Yii::$app->request->get('VendorSchemes') !== null)
			$model->load(Yii::$app->request->queryParams);

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
		if ( $model == null ) $model = new VendorSchemes();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('vendorSchemes/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('vendorSchemes/admin'),'icon'=>'icon-wrench icon-white'];							
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('vendorSchemes/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('vendorSchemes/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
				//	$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('vendorSchemes/create'),'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('vendorSchemes/admin'),'icon'=>'icon-wrench icon-white'];
					//$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('vendorSchemes/create'),'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('vendorSchemes/update', ['id' => $model->id]), 'icon'=>'icon-edit icon-white'];				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}