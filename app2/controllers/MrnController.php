<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Mrn;
use app\models\MrnDetail;
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
 * Yii 2 port of protected/controllers/MrnController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class MrnController extends BaseUiController {



	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
		if( !($model->checkPermission ('mrn/view')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}
	public function actionApprove($id)
	{
		$model = $this->loadModel($id);
	if($model){
		$po = new PurchaseOrder();
		$po->code = $model->code;
		$po->start_date = date('Y-m-d');
		$po->vendor_id = $model->vendor_id;
		$po->outlet_id = $model->outlet_id;
		$po->organization_id = $model->organization_id;
		$po->mrn_id = $id;
		if($po->save()){
			$mrnDetails = MrnDetail::findAll(['mrn_id'=>$id]);
			if($mrnDetails){
				foreach($mrnDetails as $mrnDetail){
					$podetail = new PurchaseOrderDetail();
					$podetail->req_qty = $mrnDetail->req_qty;
					$podetail->bal_qty = $mrnDetail->bal_qty;
					$podetail->remarks = $mrnDetail->remarks;
					$podetail->item_id = $mrnDetail->item_id;
					$podetail->item_detail_id = $mrnDetail->item_detail_id;
					$podetail->outlet_id = $mrnDetail->outlet_id;
					$podetail->purchase_order_id = $po->id;
					$podetail->save();
				}
			}
			$model->status = Mrn::STATUS_APPROVED;
			$model->updateAttributes(['status']);
		}
	}
		
		return $this->redirect(['admin']);
	}
	public function actionCreate() 
	{
		$model = new Mrn;

		$this->performAjaxValidation($model, 'mrn-form');

		if (isset($_POST['Mrn'])) {
			$model->load($_POST, 'Mrn');

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
		
		$this->performAjaxValidation($model, 'mrn-form');

		if (isset($_POST['Mrn'])) {
			$model->load($_POST, 'Mrn');

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
		$dataProvider = new ActiveDataProvider(['query' => Mrn::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => Mrn::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new Mrn(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['Mrn']))
		{
			$model->load($_GET, 'Mrn');
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
		$model = new Mrn(['scenario' => 'search']);
		if( !($model->checkPermission ('mrn/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems($model);
		
		if (isset($_GET['Mrn']))
			$model->load($_GET, 'Mrn');

		return $this->render('admin', [
			'model' => $model,
		]);
	}
	
	
	
		#Generate PDF
	public function actionPrintPdf($id)
	{
		//$id  =2;
		//echo $id; die ;
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
		$model = new MrnDetail(['scenario' => 'search']);
		$_GET['PurchaseOrderDetail']['purchase_order_id'] = $id;
		if (isset($_GET['PurchaseOrderDetail']))
			$model->load($_GET, 'PurchaseOrderDetail');
			$vendor = Vendor::findOne( $po->vendor_id );
			$email = '' ;
			// if($vendor){
				// $to_id = $vendor->create_user_id;
				// $vendoruser = User::findOne(array('id'=>$vendor->create_user_id));
				// if($vendoruser){
					// $email = $vendoruser->email;
				// }
			// }
			
			
		# mPDF
		$mPDF1 = new \Mpdf\Mpdf(['tempDir' => Yii::getAlias('@runtime')]);
		
		# You can easily override default constructor's params
		$mPDF1 = new \Mpdf\Mpdf(['format' => 'A4', 'tempDir' => Yii::getAlias('@runtime')]);
		
		# render (full page)
		//$mPDF1->WriteHTML($this->render('index', array(), true));
		
		# Load a stylesheet
		//$stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
		//$mPDF1->WriteHTML($stylesheet, 1);
		
		# renderPartial (only 'view' of current controller)
		
	
		$mPDF1->WriteHTML($this->renderPartial('_pdf',['model'=>$model,'mrn'=>$po,'poid'=>$id], true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
		
		
		// -------email---------
		// if($email != '' && ($role->id != $login->role_id)){
			// $from = (Yii::$app->params['mail_email'] ?? null) ;
			// $to      = $email;
			// $subject = 'Your purchase order :';
		
			// $view = $this->renderPartial ( '/mail/purchase_order_pdf', array (
					// 'po'=>$po
			// ), true );
		
		
			//$purchaseorder->mailsend ( $to, $from, $subject, $view );
	//	}
		/* $html2pdf = Yii::$app->ePdf->HTML2PDF();
		$html2pdf->WriteHTML($this->renderPartial('_pdf', array(), true));
		$html2pdf->Output(); */
		# Outputs ready PDF
		/* $mPDF1->Output();
		$PDF = new \Mpdf\Mpdf(['tempDir' => Yii::getAlias('@runtime')]);
		$PDF = new \Mpdf\Mpdf(['format' => 'A4', 'tempDir' => Yii::getAlias('@runtime')]);
		$PDF ->WriteHTML($this->render('_pdf', true));
		$PDF ->Output(); */
		}else{
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
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
		if ( $model == null ) $model = new Mrn();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
				//	$this->menu[] = array('label'=>'View' , 'url'=>array('view','id'=>$model->id),'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('mrn/admin'),'icon'=>'icon-wrench icon-white'];							
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('mrn/admin'),'icon'=>'icon-wrench icon-white'];							
				//	$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;
			case 'admin':
				{
				//	$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
				//	$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('mrn/admin'),'icon'=>'icon-wrench icon-white'];
					//$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
				//	$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				//	$this->menu[] = array('label'=>'Update', 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}