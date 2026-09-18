<?php
namespace app\controllers;

use app\components\Ui;
use app\models\EmpShift;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/EmpShiftController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class EmpShiftController extends BaseUiController {



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
		$model = new EmpShift;

		$this->performAjaxValidation($model, 'emp-shift-form');

		if (Yii::$app->request->post('EmpShift') !== null) {
			$model->load(Yii::$app->request->post());

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
		
		$this->performAjaxValidation($model, 'emp-shift-form');

		if (Yii::$app->request->post('EmpShift') !== null) {
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
		$dataProvider = new ActiveDataProvider(['query' => EmpShift::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => EmpShift::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new EmpShift(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (Yii::$app->request->get('EmpShift') !== null)
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
		$model = new EmpShift(['scenario' => 'search']);
		$this->updateMenuItems($model);
		
		if (Yii::$app->request->get('EmpShift') !== null)
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
		if ( $model == null ) $model = new EmpShift();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('empShift/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('empShift/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('empShift/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('empShift/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('empShift/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('empShift/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('empShift/create'),'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('empShift/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('empShift/admin'),'icon'=>'icon-wrench icon-white'];
					$this->menu[] = ['label'=>'Delete', 'url'=>'#', 'linkOptions' => ['submit' => ['delete', 'id' => $model->id], 
					'confirm'=>'Are you sure you want to delete this item?'],'icon'=>'icon-remove icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('empShift/create'),'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('empShift/update', ['id' => $model->id]), 'icon'=>'icon-edit icon-white'];				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}