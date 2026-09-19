<?php
namespace app\controllers;

use app\components\Ui;
use app\models\ItemCategory;
use app\models\UserRole;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/ItemCategoryController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class ItemCategoryController extends BaseUiController {



	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionImport() {
		$model = new ItemCategory();
		if (isset ( $_FILES ['ItemCategory'] )) {
		
			$csvfile = $_FILES['ItemCategory']['tmp_name']['csv_file'];
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
				
				$itemCat = new ItemCategory();
				$result = $itemCat->setAllValues ( $valued_rows );
				if ($result == 1) {
					Yii::$app->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::$app->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
			}else{
				Yii::$app->user->setFlash ( 'danger', 'Please upload File' );
			}
		}
		return $this->render('import',['model'=>$model]);
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('itemCategory/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}
	public function actionSubview($id)
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('itemCategory/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		//$this->processActions($model);
		$this->updateMenuItems($model);
		return $this->render('subview', [
				'model' => $model
		]);
	}
	public function actionCreate() 
	{
		$model = new ItemCategory;
		if( !($model->checkPermission ('itemCategory/create')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'item-category-form');

		if (isset($_POST['ItemCategory'])) {
			$model->load($_POST, 'ItemCategory');

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
		$model = new ItemCategory;
		if( !($model->checkPermission ('itemCategory/create')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		$this->performAjaxValidation($model, 'item-category-form');
	
		if (isset($_POST['ItemCategory'])) {
			$model->load($_POST, 'ItemCategory');
	
			if ($model->save()) {
				$user = Yii::$app->user->model;
				$role = UserRole::findOne(['title'=>'Admin']);
				if($user->role_id == $role->id ){
					return $this->redirect(['subview', 'id' => $model->id]);
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
		if( !($model->checkPermission ('itemCategory/update')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
	
		$this->performAjaxValidation($model, 'item-category-form');
	
		if (isset($_POST['ItemCategory'])) {
			$model->load($_POST, 'ItemCategory');
	
			if ($model->save()) {
				return $this->redirect(['subview', 'id' => $model->id]);
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
		if( !($model->checkPermission ('itemCategory/update')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'item-category-form');

		if (isset($_POST['ItemCategory'])) {
			$model->load($_POST, 'ItemCategory');

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
		$dataProvider = new ActiveDataProvider(['query' => ItemCategory::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => ItemCategory::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new ItemCategory(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemCategory']))
		{
			$model->load($_GET, 'ItemCategory');
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
		$model = new ItemCategory(['scenario' => 'search']);
		if( !($model->checkPermission ('itemCategory/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems($model);
		
		if (isset($_GET['ItemCategory']))
			$model->load($_GET, 'ItemCategory');
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search(), [
							
						'id',
						'title',
						[
								'label' => 'Parent Category',
								'value' => function ($data) {
								return ItemCategory::getParentItemCat($data->parent_id);
								}],
						
				] );
			}
		return $this->render('admin', [
			'model' => $model,
		]);
	}
	public function actionSubcategory()
	{
		$model = new ItemCategory(['scenario' => 'search']);
		if( !($model->checkPermission ('itemCategory/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemCategory']))
			$model->load($_GET, 'ItemCategory');
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->subcatsearch(), [
							
						'id',
						'title',
						[
								'label' => 'Parent Category',
								'value' => function ($data) {
								return ItemCategory::getParentItemCat($data->parent_id);
								}],
	
								] );
			}
			return $this->render('subcategory', [
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
		if ( $model == null ) $model = new ItemCategory();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('itemCategory/view', ['id'=>$model->id]),'visible'=> $model->checkPermission ("itemCategory/view")=="true",'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemCategory/admin'),'visible'=> $model->checkPermission ("itemCategory/admin")=="true",'icon'=>'icon-wrench icon-white'];							
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemCategory/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCategory/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCategory/create'),'visible'=> $model->checkPermission ("itemCategory/create")=="true",'icon'=>'icon-plus icon-white'];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Import' ),
							'url' => [
									'import'
							],
							'icon' => 'icon-plus icon-white'
					];
				}
				break;
				case 'subcategory':
					{
						//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
						$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCategory/subCreate'),'visible'=> $model->checkPermission ("itemCategory/create")=="true",'icon'=>'icon-plus icon-white'];
						$this->menu [] = [
								'label' => Yii::t ( 'app', 'Import' ),
								'url' => [
										'import'
								],
								'icon' => 'icon-plus icon-white'
						];
					}
					break;
					case 'subview':
						{
							//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
							$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemCategory/subcategory'),'visible'=> $model->checkPermission ("itemCategory/admin")=="true",'icon'=>'icon-wrench icon-white'];
							//$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
							//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
							$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCategory/subCreate'),'visible'=> $model->checkPermission ("itemCategory/create")=="true",'icon'=>'icon-plus icon-white'];
							$this->menu[] = ['label'=>'Update', 'url' => Ui::to('itemCategory/subUpdate', ['id' => $model->id]),'visible'=> $model->checkPermission ("itemCategory/update")=="true", 'icon'=>'icon-edit icon-white'];
						}
						break;
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemCategory/admin'),'visible'=> $model->checkPermission ("itemCategory/admin")=="true",'icon'=>'icon-wrench icon-white'];
					//$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemCategory/create'),'visible'=> $model->checkPermission ("itemCategory/create")=="true",'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('itemCategory/update', ['id' => $model->id]),'visible'=> $model->checkPermission ("itemCategory/update")=="true", 'icon'=>'icon-edit icon-white'];				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}