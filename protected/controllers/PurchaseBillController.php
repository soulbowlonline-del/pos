<?php

class PurchaseBillController extends GxController {

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
					'actions'=>array('correctBill','view','create','update', 'search','admin','delete','checkConsignment','print','printBarcode','list','merge','import','printPDF','setRefrenceNo'),
					'users'=>array('@'),
					),
				/*array('allow', 
					'actions'=>array('admin','delete'),
					'expression'=>'Yii::app()->user->isAdmin',
					),*/
				array('deny', 
					),
				);
	}
	
	public function actionCorrectBill(){
		
		/* 
		$date ='2022-01-11'; 
		$criteria = new CDbCriteria();
	$criteria->addCondition('cgst_per = 0.00');
	 $criteria->addCondition('date(create_time) = "' . $date . '"');
	 
			$bills = PurchaseBillDetail::model()->findAll($criteria);
			
			
		
			if(!empty($bills))
			{
				foreach($bills as $bill)
				{
					
					$sgst = $bill->sgst_per;
					
					$bill->cgst_per = $sgst;
					
					$bill->saveAttributes(array(
                    'cgst_per'
                ));
				
					
				}
			}
		
*/
		
	}
	public function actionSetRefrenceNo(){
		$purchasebills = PurchaseBill::model()->findAll();
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchasebill->grn_refrence_no = $purchasebill->id;
				$purchasebill->saveAttributes(array('grn_refrence_no'));
			}
		}
	}
	/*
	 public function actionMerge(){
	
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$criteria = new CDbCriteria();
			//$criteria->addInCondition('id', $_POST['idList']);
			$criteria->compare('id', PostId::get('idList', '0'));
			$bill = PurchaseBill::model()->find($criteria);
			
			if($bill){
				$return_tax = 0;
				$igst = false;
				$criteria1 = new CDbCriteria();
				$criteria1->addCondition('purchase_bill_id ='.$bill->id);
				$existbilldetail = PurchaseBillDetail::model()->find($criteria1);
				if($existbilldetail){
					$tax = Tax::model()->findByPk($existbilldetail->tax_id);
					if($tax->tax_val1 == '0.00' && $tax->tax_val2 == '0.00'&& $tax->tax_val3 == '0.00' && $tax->tax_val4 != '0.00'){
						$igst = true;
						$val = $tax->tax_val4/2;
						$criteria = new CDbCriteria();
						$criteria->addCondition('tax_val1 ='.$val);
						$criteria->addCondition('tax_val2 ='.$val);
						$criteria->addCondition('tax_val3 = 0.00');
						$tax = Tax::model()->find($criteria);
						if($tax){
							$return_tax = $tax->id;
						}
					}
				}
				$criteria = new CDbCriteria();
				$criteria->addInCondition('id', $_POST['idList']);
				$criteria->addCondition('id !='.$bill->id);
				$bills = PurchaseBill::model()->findAll($criteria);
				if($bills){
					foreach($bills as $delbill){
						$criteria1 = new CDbCriteria();
						$criteria1->addCondition('purchase_bill_id ='.$delbill->id);
						$billdetails = PurchaseBillDetail::model()->findAll($criteria1);
						if($billdetails){
							foreach($billdetails as $billdetail){
								if($igst == true){
									if($return_tax != 0){
									$billdetail->igst_per = $bill->cgst_per + $bill->sgst_per + $bill->cess_per;
									$billdetail->cgst_per = '0.00';
									$billdetail->sgst_per = '0.00';
									$billdetail->cess_per = '0.00';
									$billdetail->igst_amt = $bill->cgst_amt + $bill->sgst_amt + $bill->cess_amt;
									$billdetail->cgst_amt = '0.00';
									$billdetail->sgst_amt = '0.00';
									$billdetail->cess_amt = '0.00';
									$billdetail->tax_id = $return_tax;
									}
									$billdetail->purchase_bill_id = $bill->id;
									$billdetail->save();
								}else{
								$billdetail->purchase_bill_id = $bill->id;
								$billdetail->save();
								}
							}
						}
						$bill->vendor_id = $_POST['vendor_id'];
						$bill->gross_amt = ($bill->gross_amt) + ($delbill->gross_amt);
						$bill->total_discount = ($bill->total_discount) + ($delbill->total_discount);
						$bill->tax_amount = ($bill->tax_amount) + ($delbill->tax_amount);
						$bill->bill_amount = ($bill->bill_amount) + ($delbill->bill_amount);
						if($bill->save()){
							$delbill->delete();
						}
					}
				}else{
					$bill->vendor_id = $_POST['vendor_id'];
					
					$bill->save();
				}
			}
		}
	
	} 
	 */
	public function actionMerge(){
		$vendor_ids = array();
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$criteria = new CDbCriteria();
			//$criteria->addInCondition('id', $_POST['idList']);
			$criteria->compare('id', PostId::get('idList', '0'));
			$bill = PurchaseBill::model()->find($criteria);
			
			if($bill){
				$criteria = new CDbCriteria();
				$criteria->addInCondition('id', $_POST['idList']);
				$criteria->addCondition('id !='.$bill->id);
				$bills = PurchaseBill::model()->findAll($criteria);
				if($bills){
					$vendor_ids[] = $_POST['vendor_id'];
					foreach($bills as $delbill){
						$vendor_ids[] = $delbill->vendor_id;
						$criteria1 = new CDbCriteria();
						$criteria1->addCondition('purchase_bill_id ='.$delbill->id);
						$billdetails = PurchaseBillDetail::model()->findAll($criteria1);
						if($billdetails){
							foreach($billdetails as $billdetail){
								$billdetail->purchase_bill_id = $bill->id;
								$billdetail->save();
							}
						}
						$vendor_ids[] = $bill->vendor_id;
						$vendor_ids = array_unique($vendor_ids);
						if(!empty($vendor_ids)){
							$bill->original_vendor_id = implode(',',$vendor_ids);
						}
						$bill->vendor_id = $_POST['vendor_id'];
						$bill->gross_amt = ($bill->gross_amt) + ($delbill->gross_amt);
						$bill->total_discount = ($bill->total_discount) + ($delbill->total_discount);
						$bill->tax_amount = ($bill->tax_amount) + ($delbill->tax_amount);
						$bill->bill_amount = ($bill->bill_amount) + ($delbill->bill_amount);
						if($bill->save()){
							$delbill->delete();
						}
					}
				}else{
					$vendor_ids[] = $bill->vendor_id;
					if($bill->vendor_id != $_POST['vendor_id']){
					$vendor_ids[] = $_POST['vendor_id'];
					}
					if(!empty($vendor_ids)){
						$bill->original_vendor_id = implode(',',$vendor_ids);
					}
					
					$bill->vendor_id = $_POST['vendor_id'];
					
					$bill->save();
				}
			}
		}
	
	}
	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionCheckConsignment(){
		$model = new PurchaseBill;
		$model->getConsignmentOptions();
	}
	public function actionImport(){
		$model = new PaymentReport ();
		if (isset ( $_FILES ['PaymentReport'] )) {
				
			$csvfile = $_FILES ['PaymentReport'] ['tmp_name'] ['csv_file'];
			
			$handle = fopen ( $csvfile, 'r' );
			if (! $handle)
				die ( 'Cannot open uploaded file.' );
				$row_count = 0;
				$rows = array ();
				$valued_rows = array ();
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
				
				$paymentreport = new PaymentReport ();
				$result = $paymentreport->setAllPayment ( $valued_rows );
				if ($result == 1) {
					Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
		}
		$this->render ( 'import', array (
				'model' => $model
		) );
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id, 'PurchaseBill');
		$billDetail = new PurchaseBillDetail ( 'search' );
		$billDetail->unsetAttributes ();
		Yii::app()->session['billidList'] =  '';
		Yii::app()->session['bill_date_list'] =  '' ;
		Yii::app()->session['bill_expiry_val'] =  '';
		$_GET ['PurchaseBillDetail']['purchase_bill_id'] = $id;
		if (isset ( $_GET ['PurchaseBillDetail'] ))
		$billDetail->setAttributes ( $_GET ['PurchaseBillDetail'] );
		
		$pobill = new Bill('search');
		$pobill->unsetAttributes();
		//$this->updateMenuItems($pobill);
		$_GET['Bill']['po_id']= $model->purchase_order_id;
		if (isset($_GET['Bill']))
			$pobill->setAttributes($_GET['Bill']);
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model,'billDetail'=>$billDetail,
				'pobill'=>$pobill
		));
	}
	 public function actionPrintBarcode() {
		$model = new PurchaseBill;
		if(isset($_POST['PurchaseBill']['print_id']) && isset($_POST['PurchaseBill']['qty'])) {
			
			Yii::app()->session['print_id'] =  $_POST['PurchaseBill']['print_id'] ;
			Yii::app()->session['qty'] =  $_POST['PurchaseBill']['qty'] ;
			$model->setAttributes($_POST['PurchaseBill']);
			
		}
		$this->render('print', array( 'model' => $model));
	}  
	public function actionprintPDF() {
		$model = new PurchaseBill;
		
		if(isset($_POST['PurchaseBill']['print_id']) && isset($_POST['PurchaseBill']['qty'])) {
				
			Yii::app()->session['print_id'] =  $_POST['PurchaseBill']['print_id'] ;
			Yii::app()->session['qty'] =  $_POST['PurchaseBill']['qty'] ;
			$model->setAttributes($_POST['PurchaseBill']);
				
		}
		$mPDF1 = Yii::app()->ePdf->mpdf();
		
		# You can easily override default constructor's params
		$mPDF1 = Yii::app()->ePdf->mpdf('', 'A4');
		
		# render (full page)
		//$mPDF1->WriteHTML($this->render('index', array(), true));
		
		# Load a stylesheet
		//$stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
		//$mPDF1->WriteHTML($stylesheet, 1);
		
		# renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML($this->renderPartial('_printpdf',array('model'=>$model), true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
	} 
	public function actionPrint(){
	
		if(isset($_POST['billidList']) && isset($_POST['bill_date_list'])) {
			Yii::app()->session['print_id'] = '';
			Yii::app()->session['billidList'] =  $_POST['billidList'] ;
			Yii::app()->session['bill_date_list'] =  $_POST['bill_date_list'] ;
			Yii::app()->session['bill_expiry_val'] =  $_POST['bill_expiry_val'] ;
			//Yii::app()->session['print_id'] =  $_POST['billidList']['0'] ;
			if(isset($_POST['packing_date_list'])){
				Yii::app()->session['packing_date_list'] =  $_POST['packing_date_list'] ;
			}
			if(isset( $_POST['billidList']['0'] )){
			$criteria = new CDbCriteria();
			$criteria->compare('id', $_POST['billidList']['0']);
			$purchasebilldetail = PurchaseBillDetail::model()->find($criteria);
			Yii::app()->session['qty'] = $purchasebilldetail->approved_qty ;
			}
			echo 'success';
		}
		else{
			echo 'failed';
		}
	
	
	}
	public function actionCreate() 
	{
		$model = new PurchaseBill;

		$this->performAjaxValidation($model, 'purchase-bill-form');

		if (isset($_POST['PurchaseBill'])) {
			$model->setAttributes($_POST['PurchaseBill']);

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
		$model = $this->loadModel($id, 'PurchaseBill');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'purchase-bill-form');

		if (isset($_POST['PurchaseBill'])) {
			$model->setAttributes($_POST['PurchaseBill']);

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
		$model = $this->loadModel($id, 'PurchaseBill');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'PurchaseBill')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('PurchaseBill');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new PurchaseBill ('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['PurchaseBill']))
		{
			$model->setAttributes($_GET['PurchaseBill']);
			$this->renderPartial('_list', array(
					'dataProvider' => $model->search(),
					'model' => $model,
			));
		}
			
		$this->renderPartial('_search', array(
				'model' => $model,
		));
	}
	public function actionList()
	{
		$model = new PurchaseBill('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		//$model->status = PurchaseBill::STATUS_UNAPPROVED;
		if (isset($_GET['PurchaseBill']))
			$model->setAttributes($_GET['PurchaseBill']);
			
			$this->render('list', array(
					'model' => $model,
			));
	}
	public function actionAdmin() 
	{
		$model = new PurchaseBill('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		$columns = array ();
		if (isset ( $_POST ['PurchaseBill'] ['columns'] )) {
			$columns = $_POST ['PurchaseBill'] ['columns'];
		}
		$_GET['PurchaseBill']['status'] = PurchaseBill::STATUS_APPROVED;
		if (isset($_GET['PurchaseBill']))
			$model->setAttributes($_GET['PurchaseBill']);
			$columns = $model->getColumns ( $columns );
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->search (), $columns );
			}
		$this->render('admin', array(
			'model' => $model,
		));
	}
	/*protected function processActions($model = null)
	{
		parent::processActions($model);
		//$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	}*/
	protected function updateMenuItems($model = null)
	{	
		// create static model if model is null
		if ( $model == null ) $model = new PurchaseBill();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = array('label'=>Yii::t('app', 'View') , 'url'=>array('view','id'=>$model->id),'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');							
					$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
				}
				break;				
			case 'index':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');							
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;
			case 'admin':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('purchaseBillDetail/list'),'icon'=>'icon-wrench icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Print Barcode'), 'url'=>array('purchaseBill/print','id'=>$model->id),'icon'=>'icon-wrench icon-white');
				/* 	$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				 */
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}
}