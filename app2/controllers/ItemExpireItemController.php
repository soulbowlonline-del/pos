<?php
namespace app\controllers;

use app\components\Ui;
use app\models\ItemExpireItem;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/ItemExpireItemController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class ItemExpireItemController extends BaseUiController {



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
		$model = new ItemExpireItem;

		$this->performAjaxValidation($model, 'item-expire-item-form');

		if (Yii::$app->request->post('ItemExpireItem') !== null&&(isset($_POST['ItemExpireItem']['outlet_id']))&& (isset($_POST['ItemExpireItem']['vendor_id']))
				&& (isset($_POST['ItemExpireItem']['total_amt']))) {
			$itemExpire = ItemExpire::model()->findByAttributes(['vendor_id'=>$_POST['ItemExpireItem']['vendor_id'],
					'outlet_id'=>$_POST['ItemExpireItem']['outlet_id'],'status'=>ItemExpire::STATUS_PENDING
			]);
			if($itemExpire == null){
				$itemExpire = new ItemExpire();
				$amount = 0;
			}else{
				$amount = $itemExpire->total_amt;
			}
			$itemExpire->outlet_id = $_POST['ItemExpireItem']['outlet_id'];
			$itemExpire->vendor_id = $_POST['ItemExpireItem']['vendor_id'];
			$itemExpire->total_amt =$amount + ($_POST['ItemExpireItem']['qty'] * $_POST['ItemExpireItem']['sale_rate']);
			if($itemExpire->save()){
			$model->load(Yii::$app->request->post());
			$model->item_expire_id = $itemExpire->id;
			$model->total_amt = $_POST['ItemExpireItem']['qty'] * $_POST['ItemExpireItem']['sale_rate'];
			if ($model->save()) {
				
					return $this->redirect(['item/expireStock','vendor_id'=>$itemExpire->vendor_id,
							'outlet_id'=>$itemExpire->outlet_id
					]);
			}
			}
		}
		$this->updateMenuItems($model);
		return $this->redirect(['item/expireStock']);
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id);
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'item-expire-item-form');

		if (Yii::$app->request->post('ItemExpireItem') !== null) {
			$model->load(Yii::$app->request->post());

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
		$vendor_id = '';
		$outlet_id = '';
		$model = $this->loadModel($id);
		$itemExpire = ItemExpire::model()->findByPk($model->item_expire_id);
		if($itemExpire){
			$vendor_id = $itemExpire->vendor_id;
			$outlet_id = $itemExpire->outlet_id;
			$total_amt = ($itemExpire->total_amt)-($model->total_amt);
		}
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		
			$this->loadModel($id)->delete();
			$itemExpire->total_amt = $total_amt;
			$itemExpire->saveAttributes(['total_amt']);
			
				return $this->redirect(['item/expireStock','vendor_id'=>$vendor_id,'outlet_id'=>$outlet_id]);
	
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new ActiveDataProvider(['query' => ItemExpireItem::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            'sort' => ['defaultOrder' => ItemExpireItem::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new ItemExpireItem(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (Yii::$app->request->get('ItemExpireItem') !== null)
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
		$model = new ItemExpireItem(['scenario' => 'search']);
		$this->updateMenuItems($model);
		
		if (Yii::$app->request->get('ItemExpireItem') !== null)
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
		if ( $model == null ) $model = new ItemExpireItem();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('itemExpireItem/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemExpireItem/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemExpireItem/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemExpireItem/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemExpireItem/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemExpireItem/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemExpireItem/create'),'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemExpireItem/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemExpireItem/admin'),'icon'=>'icon-wrench icon-white'];
					$this->menu[] = ['label'=>'Delete', 'url'=>'#', 'linkOptions' => ['submit' => ['delete', 'id' => $model->id], 
					'confirm'=>'Are you sure you want to delete this item?'],'icon'=>'icon-remove icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemExpireItem/create'),'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('itemExpireItem/update', ['id' => $model->id]), 'icon'=>'icon-edit icon-white'];				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}