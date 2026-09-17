<?php

class MrnController extends GxController {

	public function filters() {
		return array(
				'accessControl', 
				);
	}

	public function accessRules() {
		return array(
				array('allow',
					'actions'=>array(/*'index','view',  'download', 'thumbnail' */),
					'users'=>array('*'),
					),
				array('allow', 
					'actions'=>array('create','update', 'search','admin','delete','approve','printPdf'),
					'users'=>array('@'),
					),
				/*array('allow', 
					'actions'=>array('admin','delete'),
					'expression'=>'Yii::app()->user->isAdmin',
					),*/
				array('deny', 
					'users'=>array('*'),
					),
				);
	}

	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id, 'Mrn');
		if( !($model->checkPermission ('mrn/view')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}
	public function actionApprove($id)
	{
		$model = $this->loadModel($id, 'Mrn');
	if($model){
		$po = new PurchaseOrder();
		$po->code = $model->code;
		$po->start_date = date('Y-m-d');
		$po->vendor_id = $model->vendor_id;
		$po->outlet_id = $model->outlet_id;
		$po->organization_id = $model->organization_id;
		$po->mrn_id = $id;
		if($po->save()){
			$mrnDetails = MrnDetail::model()->findAllByAttributes(array('mrn_id'=>$id));
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
			$model->saveAttributes(array('status'));
		}
	}
		
		$this->redirect(array('admin'));
	}
	public function actionCreate() 
	{
		$model = new Mrn;

		$this->performAjaxValidation($model, 'mrn-form');

		if (isset($_POST['Mrn'])) {
			$model->setAttributes($_POST['Mrn']);

			if ($model->save()) {
				if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('view', 'id' => $model->id));
			}
		}
		$this->updateMenuItems($model);
		$this->render('create', array( 'model' => $model));
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id, 'Mrn');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'mrn-form');

		if (isset($_POST['Mrn'])) {
			$model->setAttributes($_POST['Mrn']);

			if ($model->save()) {
				$this->redirect(array('view', 'id' => $model->id));
			}
		}
		$this->updateMenuItems($model);
		$this->render('update', array(
				'model' => $model,
				));
	}

	public function actionDelete($id) 
	{
		$model = $this->loadModel($id, 'Mrn');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'Mrn')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('Mrn');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Mrn ('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['Mrn']))
		{
			$model->setAttributes($_GET['Mrn']);
			$this->renderPartial('_list', array(
					'dataProvider' => $model->search(),
					'model' => $model,
			));
		}
			
		$this->renderPartial('_search', array(
				'model' => $model,
		));
	}
	public function actionAdmin() 
	{
		$model = new Mrn('search');
		if( !($model->checkPermission ('mrn/admin')))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		
		if (isset($_GET['Mrn']))
			$model->setAttributes($_GET['Mrn']);

		$this->render('admin', array(
			'model' => $model,
		));
	}
	
	
	
		#Generate PDF
	public function actionPrintPdf($id)
	{
		//$id  =2;
		//echo $id; die ;
 		$set = true;
		$po = $this->loadModel($id, 'Mrn');
		$login = Yii::app()->user->model;
		$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
		if($role->id == $login->role_id){
			$loginvendor = Vendor::model()->findByAttributes(array('create_user_id'=>$login->id));
			if($loginvendor){
			if($po->vendor_id != $loginvendor->id){
				$set = false;
			}
			}else{
				$set = false;
			}
		}

		
		if($set == true){
		$model = new MrnDetail('search');
		$_GET['PurchaseOrderDetail']['purchase_order_id'] = $id;
		if (isset($_GET['PurchaseOrderDetail']))
			$model->setAttributes($_GET['PurchaseOrderDetail']);
			$vendor = Vendor::model ()->findByPk ( $po->vendor_id );
			$email = '' ;
			// if($vendor){
				// $to_id = $vendor->create_user_id;
				// $vendoruser = User::model()->findByAttributes(array('id'=>$vendor->create_user_id));
				// if($vendoruser){
					// $email = $vendoruser->email;
				// }
			// }
			
			
		# mPDF
		$mPDF1 = Yii::app()->ePdf->mpdf();
		
		# You can easily override default constructor's params
		$mPDF1 = Yii::app()->ePdf->mpdf('', 'A4');
		
		# render (full page)
		//$mPDF1->WriteHTML($this->render('index', array(), true));
		
		# Load a stylesheet
		//$stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
		//$mPDF1->WriteHTML($stylesheet, 1);
		
		# renderPartial (only 'view' of current controller)
		
	
		$mPDF1->WriteHTML($this->renderPartial('_pdf',array('model'=>$model,'mrn'=>$po,'poid'=>$id), true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
		
		
		// -------email---------
		// if($email != '' && ($role->id != $login->role_id)){
			// $from = Yii::app()->params['mail_email'] ;
			// $to      = $email;
			// $subject = 'Your purchase order :';
		
			// $view = $this->renderPartial ( '/mail/purchase_order_pdf', array (
					// 'po'=>$po
			// ), true );
		
		
			//$purchaseorder->mailsend ( $to, $from, $subject, $view );
	//	}
		/* $html2pdf = Yii::app()->ePdf->HTML2PDF();
		$html2pdf->WriteHTML($this->renderPartial('_pdf', array(), true));
		$html2pdf->Output(); */
		# Outputs ready PDF
		/* $mPDF1->Output();
		$PDF = Yii::app()->ePdf->mpdf();
		$PDF = Yii::app()->ePdf->mpdf('', 'A4');
		$PDF ->WriteHTML($this->render('_pdf', true));
		$PDF ->Output(); */
		}else{
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		}
		
	}
	
	
	/*protected function processActions($model = null)
	{
		parent::processActions($model);
		//$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	}*/
	protected function updateMenuItems($model = null)
	{	
		// create static model if model is null
		if ( $model == null ) $model = new Mrn();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
				//	$this->menu[] = array('label'=>Yii::t('app', 'View') , 'url'=>array('view','id'=>$model->id),'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');							
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');							
				//	$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;
			case 'admin':
				{
				//	$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
				//	$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					//'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
				//	$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				//	$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}