<?php
namespace app\controllers;

use app\components\Ui;
use app\models\PurchaseOrder;
use app\models\PurchaseOrderDetail;
use app\models\User;
use app\models\UserRole;
use app\models\Vendor;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/PurchaseOrderController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class PurchaseOrderController extends BaseUiController {


	public function actionIndex() {
		$model = new PurchaseOrder(['scenario' => 'search']);
		$role = UserRole::findOne( [
				'title' => 'Vendor'
		] );
		$loggedinuser = Yii::$app->user->model;
		if ($loggedinuser->role_id == $role->id) {
			$user = Vendor::findOne( [
					'create_user_id' => $loggedinuser->id
			] );
			if($user){
			$_GET ['PurchaseOrder']['vendor_id'] = $user->id;
			}
		} 
		$this->updateMenuItems ( $model );
		
		//$_GET ['PurchaseOrder']['status'] = PurchaseOrder::STATUS_APPROVED;
		if (isset ( $_GET ['PurchaseOrder'] ))
			$model->load($_GET, 'PurchaseOrder');
	
			return $this->render( 'list', [
					'model' => $model
			] );
	}
	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionPdf($id)
	{
		$encode_id = base64_decode($id);
		
		//$id  =2;
		$set = true;
		$po = $this->loadModel($encode_id);
		
	
			
				# mPDF
				$mPDF1 = Yii::$app->ePdf->mpdf();
	
				# You can easily override default constructor's params
				$mPDF1 = Yii::$app->ePdf->mpdf('', 'A4');
	
				# render (full page)
				//$mPDF1->WriteHTML($this->render('index', array(), true));
	
				# Load a stylesheet
				//$stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
				//$mPDF1->WriteHTML($stylesheet, 1);
	
				# renderPartial (only 'view' of current controller)
				$mPDF1->WriteHTML($this->renderPartial('_pdf',['po'=>$po,'poid'=>$encode_id], true));
	
				# Renders image
				//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
				$mPDF1->Output();
		
				
		
	}
	public function actionPrintPdf($id)
	{
		//$id  =2;
		$set = true;
		$po = $this->loadModel($id);
		$login = Yii::$app->user->model;
		$role = UserRole::findOne(['title'=>'Vendor']);
		if($role->id == $login->role_id){
			$loginvendor = Vendor::findOne(['create_user_id'=>$login->id]);
			if($loginvendor){
			if($po->vendor_id != $loginvendor->id){
				$set = false;
			}
			}else{
				$set = false;
			}
		}
	
		
		if($set == true){
		$model = new PurchaseOrderDetail(['scenario' => 'search']);
		$_GET['PurchaseOrderDetail']['purchase_order_id'] = $id;
		if (isset($_GET['PurchaseOrderDetail']))
			$model->load($_GET, 'PurchaseOrderDetail');
			$vendor = Vendor::findOne( $po->vendor_id );
			$email = '' ;
			if($vendor){
				$to_id = $vendor->create_user_id;
				$vendoruser = User::findOne(['id'=>$vendor->create_user_id]);
				if($vendoruser){
					$email = $vendoruser->email;
				}
			}
		# mPDF
		$mPDF1 = Yii::$app->ePdf->mpdf();
		
		# You can easily override default constructor's params
		$mPDF1 = Yii::$app->ePdf->mpdf('', 'A4');
		
		# render (full page)
		//$mPDF1->WriteHTML($this->render('index', array(), true));
		
		# Load a stylesheet
		//$stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
		//$mPDF1->WriteHTML($stylesheet, 1);
		
		# renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML($this->renderPartial('_pdf',['model'=>$model,'po'=>$po,'poid'=>$id], true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
		if($email != '' && ($role->id != $login->role_id)){
			$from = (Yii::$app->params['mail_email'] ?? null) ;
			$to      = $email;
			$subject = 'Your purchase order :';
		
			$view = $this->renderPartial ( '/mail/purchase_order_pdf', [
					'po'=>$po
			], true );
		
		
			//$purchaseorder->mailsend ( $to, $from, $subject, $view );
		}
		/* $html2pdf = Yii::$app->ePdf->HTML2PDF();
		$html2pdf->WriteHTML($this->renderPartial('_pdf', array(), true));
		$html2pdf->Output(); */
		# Outputs ready PDF
		/* $mPDF1->Output();
		$PDF = Yii::$app->ePdf->mpdf();
		$PDF = Yii::$app->ePdf->mpdf('', 'A4');
		$PDF ->WriteHTML($this->render('_pdf', true));
		$PDF ->Output(); */
		}else{
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		}
		
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('purchaseOrder/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}

	public function actionCreate() 
	{
		$model = new PurchaseOrder;

		$this->performAjaxValidation($model, 'purchase-order-form');

		if (isset($_POST['PurchaseOrder'])) {
			$model->load($_POST, 'PurchaseOrder');

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
		
		$this->performAjaxValidation($model, 'purchase-order-form');

		if (isset($_POST['PurchaseOrder'])) {
			$model->load($_POST, 'PurchaseOrder');

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

	/* public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new ActiveDataProvider(['query' => PurchaseOrder::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => PurchaseOrder::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	} */
	
	public function actionSearch()
	{
		$model = new PurchaseOrder(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['PurchaseOrder']))
		{
			$model->load($_GET, 'PurchaseOrder');
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
		$model = new PurchaseOrder(['scenario' => 'search']);
		if( !($model->checkPermission ('purchaseOrder/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems($model);
		
		if (isset($_GET['PurchaseOrder']))
			$model->load($_GET, 'PurchaseOrder');

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
		if ( $model == null ) $model = new PurchaseOrder();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					//$this->menu[] = array('label'=>'View' , 'url'=>array('view','id'=>$model->id),'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('purchaseOrder/admin'),'icon'=>'icon-wrench icon-white'];							
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('purchaseOrder/admin'),'icon'=>'icon-wrench icon-white'];							
					//$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;
			case 'admin':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					//$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('purchaseOrder/admin'),'icon'=>'icon-wrench icon-white'];
				//	$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
				//	'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
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