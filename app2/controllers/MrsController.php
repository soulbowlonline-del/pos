<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Mrs;
use app\models\MrsDetail;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/MrsController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class MrsController extends BaseUiController {



	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionMerge(){
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$query = Mrs::find();
        $query->orderBy(['id' => SORT_DESC]);
			$query->andWhere(['id' => $_POST['idList']]);
			$query->andWhere('vendor_id ='.$_POST['vendor_id']);
			$mrs = $query->one();
			if($mrs){
				$query_2 = Mrs::find();
        $query_2->orderBy(['id' => SORT_DESC]);
				$query_2->andWhere(['id' => $_POST['idList']]);
				$query_2->andWhere('id !='.$mrs->id);
				$mrss = $query_2->all();
				if($mrss){
					foreach($mrss as $delmrs){
						$query1 = MrsDetail::find();
						$query1->andWhere('mrs_id ='.$delmrs->id);
						$mrsdetails = $query1->all();
						if($mrsdetails){
							foreach($mrsdetails as $mrsdetail){
								$mrsdetail->mrs_id = $mrs->id;
								$mrsdetail->save();
							}
						}
						$mrs->gross_amt = ($mrs->gross_amt) + ($delmrs->gross_amt);
						$mrs->total_discount = ($mrs->total_discount) + ($delmrs->total_discount);
						$mrs->tax_amount = ($mrs->tax_amount) + ($delmrs->tax_amount);
						$mrs->bill_amount = ($mrs->bill_amount) + ($delmrs->bill_amount);
						if($mrs->save()){
							$delmrs->delete();
						}
					}
				}
			}
		}
		
	
    }
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('mrs/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}

	public function actionCreate() 
	{
		$model = new Mrs;
		if( !($model->checkPermission ('mrs/create')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'mrs-form');

		if (Yii::$app->request->post('Mrs') !== null) {
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
		if( !($model->checkPermission ('mrs/update')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'mrs-form');

		if (Yii::$app->request->post('Mrs') !== null) {
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
		$dataProvider = new ActiveDataProvider(['query' => Mrs::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => Mrs::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new Mrs(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (Yii::$app->request->get('Mrs') !== null)
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
		$model = new Mrs(['scenario' => 'search']);
		$this->updateMenuItems($model);
		$model->status = Mrs::STATUS_PENDING;
		if (Yii::$app->request->get('Mrs') !== null)
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
		if ( $model == null ) $model = new Mrs();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('mrs/view', ['id'=>$model->id]),'visible'=> $model->checkPermission ("mrs/view")=="true",'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('mrs/admin'),'visible'=> $model->checkPermission ("mrs/admin")=="true",'icon'=>'icon-wrench icon-white'];							
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('mrs/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('mrs/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('mrs/create'),'visible'=> $model->checkPermission ("mrs/admin")=="true",'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
				//	$this->menu[] = array('label'=>'Manage', 'url'=>array('admin'),'visible'=> $model->checkPermission ("mrs/admin")=="true",'icon'=>'icon-wrench icon-white');
					//$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
				//	$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'visible'=> $model->checkPermission ("mrs/create")=="true",'icon'=>'icon-plus icon-white');
				//	$this->menu[] = array('label'=>'Update', 'url'=>array('update', 'id' => $model->id),'visible'=> $model->checkPermission ("mrs/update")=="true", 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}