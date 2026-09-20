<?php
namespace app\controllers;

use app\components\Ui;
use app\models\ItemCompany;
use app\models\UserRole;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/ItemCompanyController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class ItemCompanyController extends BaseUiController {



	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionImport() {
		$model = new ItemCompany();
		if (isset ( $_FILES ['ItemCompany'] )) {
	
			$csvfile = $_FILES['ItemCompany']['tmp_name']['csv_file'];
			if($csvfile != ''){
			$handle = fopen ( $csvfile, 'r' );
			if (! $handle)
				die ( 'Cannot open uploaded file.' );
				$row_count = 0;
				$rows = [];
				$valued_rows = [];
				// Read the file as csv
				while ( ($data = fgetcsv ( $handle, 1000, "," )) !== FALSE ) {
					$row_count ++;
					foreach ( $data as $key => $value ) {
							
						$data [$key] = $value;
					}
	
					if (count ( array_flip ( array_flip ( $data ) ) ) != 1) {
						$rows = implode ( ",", $data );
						if (! empty ( $rows )) {
							$valued_rows [] = $rows;
						}
					}
				}
	
				$itemCat = new ItemCompany();
				$result = $itemCat->setAllValues ( $valued_rows );
				if ($result == 1) {
					Yii::$app->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::$app->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}}else{
				Yii::$app->user->setFlash ( 'danger', 'Please upload File' );
			}
		}
		return $this->render('import',['model'=>$model]);
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('ItemCompany/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}
	public function actionSubView($id)
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('ItemCompany/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		//$this->processActions($model);
		$this->updateMenuItems($model);
		return $this->render('subview', [
				'model' => $model
		]);
	}
	public function actionCreate() 
	{
		$model = new ItemCompany;
		if( !($model->checkPermission ('ItemCompany/create')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'item-company-form');

		if (isset($_POST['ItemCompany'])) {
			$model->load($_POST, 'ItemCompany');

			if ($model->save()) {
				$user = Yii::$app->user->model;
				$role = UserRole::findOne(['title'=>'Admin']);
				if($user->role_id == $role->id ){
					return $this->redirect(['view', 'id' => $model->id]);
				}else{
					return $this->redirect(['item/vendorCreate']);
				}
			}
		}
		$this->updateMenuItems($model);
		return $this->render('create', [ 'model' => $model]);
	}
	public function actionSubCreate()
	{
		$model = new ItemCompany;
		if( !($model->checkPermission ('ItemCompany/create')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		$this->performAjaxValidation($model, 'item-company-form');
	
		if (isset($_POST['ItemCompany'])) {
			$model->load($_POST, 'ItemCompany');
	
			if ($model->save()) {
				$user = Yii::$app->user->model;
				$role = UserRole::findOne(['title'=>'Admin']);
				if($user->role_id == $role->id ){
					return $this->redirect(['subView', 'id' => $model->id]);
				}else{
					return $this->redirect(['item/vendorCreate']);
				}
			}
		}
		$this->updateMenuItems($model);
		return $this->render('subcreate', [ 'model' => $model]);
	}
	public function actionSubUpdate($id)
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('ItemCompany/update')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		$this->performAjaxValidation($model, 'item-company-form');
	
		if (isset($_POST['ItemCompany'])) {
			$model->load($_POST, 'ItemCompany');
	
			if ($model->save()) {
				return $this->redirect(['view', 'id' => $model->id]);
			}
		}
		$this->updateMenuItems($model);
		return $this->render('subupdate', [
				'model' => $model,
		]);
	}
	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('ItemCompany/update')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'item-company-form');

		if (isset($_POST['ItemCompany'])) {
			$model->load($_POST, 'ItemCompany');

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
		$dataProvider = new ActiveDataProvider(['query' => ItemCompany::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => ItemCompany::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new ItemCompany(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemCompany']))
		{
			$model->load($_GET, 'ItemCompany');
			return $this->renderPartial('_list', [
					'dataProvider' => $model->search(),
					'model' => $model,
			]);
		}
			
		return $this->renderPartial('_search', [
				'model' => $model,
		]);
	}
	public function actionSubcompany()
	{
		$model = new ItemCompany(['scenario' => 'search']);
		if( !($model->checkPermission ('itemCompany/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemCompany']))
			$model->load($_GET, 'ItemCompany');
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->subsearch(), [
							
						'id',
						'title',
						[
								'label' => 'Parent Company',
								'value' => function ($data) {
								return ItemCompany::getParentItemCompany($data->parent_id);
								}],
									
								] );
			}
			return $this->render('subcompany', [
					'model' => $model,
			]);
	}
	public function actionAdmin() 
	{
		$model = new ItemCompany(['scenario' => 'search']);
		if( !($model->checkPermission ('itemCompany/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems($model);
		
		if (isset($_GET['ItemCompany']))
			$model->load($_GET, 'ItemCompany');
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search(), [
							
						'id',
						'title',
						[
								'label' => 'Parent Company',
								'value' => function ($data) {
								return ItemCompany::getParentItemCompany($data->parent_id);
								}],
			
								] );
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
		if ( $model == null ) $model = new ItemCompany();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('itemCompany/view', ['id'=>$model->id]),'visible'=> $model->checkPermission ("itemCompany/view")=="true",'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemCompany/admin'),'visible'=> $model->checkPermission ("itemCompany/admin")=="true",'icon'=>'icon-wrench icon-white'];							
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemCompany/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCompany/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCompany/create'),'visible'=> $model->checkPermission ("itemCompany/create")=="true",'icon'=>'icon-plus icon-white'];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Import' ),
							'url' => [
									'import'
							],
							'icon' => 'icon-plus icon-white'
					];
				}
				break;		
				case 'subcompany':
					{
						//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
						$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCompany/subCreate'),'visible'=> $model->checkPermission ("itemCompany/create")=="true",'icon'=>'icon-plus icon-white'];
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'Import' ),
								'url' => [
										'import'
								],
								'icon' => 'icon-plus icon-white'
						];
					}
					break;
					
					case 'subView':
						{
							//	$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
							$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemCompany/subCompany'),'visible'=> $model->checkPermission ("itemCompany/admin")=="true",'icon'=>'icon-wrench icon-white'];
							//	$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
							//	'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
							//$this->menu[] = array('label'=>'Add Category', 'url'=>array('itemCompanyCategory/create', 'id' => $model->id),'visible'=> $model->checkPermission ("itemCompanyCategory/create")=="true",'icon'=>'icon-plus icon-white');
							$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCompany/subCreate'),'visible'=> $model->checkPermission ("itemCompany/create")=="true",'icon'=>'icon-plus icon-white'];
							$this->menu[] = ['label'=>'Update', 'url' => Ui::to('itemCompany/subUpdate', ['id' => $model->id]),'visible'=> $model->checkPermission ("itemCompany/update")=="true", 'icon'=>'icon-edit icon-white'];
						}
						break;
			default:
			case 'view':
				{
				//	$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemCompany/admin'),'visible'=> $model->checkPermission ("itemCompany/admin")=="true",'icon'=>'icon-wrench icon-white'];
				//	$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
				//	'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					//$this->menu[] = array('label'=>'Add Category', 'url'=>array('itemCompanyCategory/create', 'id' => $model->id),'visible'=> $model->checkPermission ("itemCompanyCategory/create")=="true",'icon'=>'icon-plus icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCompany/create'),'visible'=> $model->checkPermission ("itemCompany/create")=="true",'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('itemCompany/update', ['id' => $model->id]),'visible'=> $model->checkPermission ("itemCompany/update")=="true", 'icon'=>'icon-edit icon-white'];				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}