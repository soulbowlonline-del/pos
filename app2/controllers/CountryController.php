<?php
namespace app\controllers;

use app\components\Ui;
use app\models\City;
use app\models\Country;
use app\models\State;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/CountryController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class CountryController extends BaseUiController {



	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	
	public function actionGetStates() {
		$option = '';
		if (isset ( $_POST ['country'] )) {
	
			$states = State::findAll(['country_id'=>$_POST ['country']]);
	
			if ($states) {
				
	
				foreach ( $states as $state ) {
	
						
	
					$option .= '<option value="' . $state->id . '">' . $state->title . '</option>';
	
						
				}
				
	
					
			} else {
				//$option .= '<option value="">-Select-</option>';
			}
		} else {
			//$option .= '<option value="">-Select-</option>';
		}
		echo $option;
	}
	public function actionGetCities() {
		$option = '';
		if (isset ( $_POST ['state'] )) {
	
			$cities = City::findAll(['state_id'=>$_POST ['state']]);
	
			if ($cities) {
	
	
				foreach ( $cities as $city ) {
	
	
	
					$option .= '<option value="' . $city->id . '">' . $city->title . '</option>';
	
	
				}
	
	
					
			} else {
				//$option .= '<option value="">-Select-</option>';
			}
		} else {
			//$option .= '<option value="">-Select-</option>';
		}
		echo $option;
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('country/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}

	public function actionCreate() 
	{
		$model = new Country;

		$this->performAjaxValidation($model, 'country-form');
		if( !($model->checkPermission ('country/create')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		if (Yii::$app->request->post('Country') !== null) {
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
		if( !($model->checkPermission ('country/update')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'country-form');

		if (Yii::$app->request->post('Country') !== null) {
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
		$dataProvider = new ActiveDataProvider(['query' => Country::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            'sort' => ['defaultOrder' => Country::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new Country(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (Yii::$app->request->get('Country') !== null)
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
		$model = new Country(['scenario' => 'search']);
		if( !($model->checkPermission ('country/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems($model);
		
		if (Yii::$app->request->get('Country') !== null)
			$model->load(Yii::$app->request->queryParams);
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search (), [
							
						'title',
					
				]
						);
			}
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
		if ( $model == null ) $model = new Country();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('country/view', ['id'=>$model->id]),'visible'=> $model->checkPermission ("country/view")=="true",'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('country/admin'),'visible'=> $model->checkPermission ("country/admin")=="true",'icon'=>'icon-wrench icon-white'];							
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('country/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('country/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
				//	$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('country/create'),'visible'=> $model->checkPermission ("country/create")=="true",'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('country/admin'),'visible'=> $model->checkPermission ("country/admin")=="true",'icon'=>'icon-wrench icon-white'];
					//$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('country/create'),'visible'=> $model->checkPermission ("country/create")=="true",'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('country/update', ['id' => $model->id]),'visible'=> $model->checkPermission ("country/update")=="true", 'icon'=>'icon-edit icon-white'];				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}