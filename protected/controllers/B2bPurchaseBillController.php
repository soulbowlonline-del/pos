<?php

class B2bPurchaseBillController extends GxController {

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
					'actions'=>array('correctBill','view','create','update', 'search','admin','delete','checkConsignment','print','printBarcode','list','merge','import','printPDF','setRefrenceNo','printInvoivePdf','userWise','userWiseExport'),
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
	
	
	/* public function actionCorrectBill(){
		
		
		$date ='2022-01-21'; 
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
		echo 'kriti';
exit;
		
	} */
	
	
	public function actionSetRefrenceNo(){
		$purchasebills = PurchaseBill::model()->findAll();
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchasebill->grn_refrence_no = $purchasebill->id;
				$purchasebill->saveAttributes(array('grn_refrence_no'));
			}
		}
	}
	
	public function actionMerge(){
		$vendor_ids = array();
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$criteria = new CDbCriteria();
			//$criteria->addInCondition('id', $_POST['idList']);
			$criteria->addCondition('id ='.$_POST['idList']['0']);
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
	
		$model = $this->loadModel($id, 'B2bPurchaseBill');
		$billDetail = new B2bPurchaseBillDetail ( 'search' );
		$billDetail->unsetAttributes ();
		Yii::app()->session['billidList'] =  '';
		Yii::app()->session['bill_date_list'] =  '' ;
		Yii::app()->session['bill_expiry_val'] =  '';
		$_GET ['B2bPurchaseBillDetail']['purchase_bill_id'] = $id;
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
		$billDetail->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
		
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
				'pobill'=>$pobill,
				'id'=>$id
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
		$model = new B2bPurchaseBill;

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
		$model = $this->loadModel($id, 'B2bPurchaseBill');
		
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
			$this->loadModel($id, 'B2bPurchaseBill')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('B2bPurchaseBill');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	

	public function actionList()
	{
		$model = new PurchaseBill('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		//$model->status = PurchaseBill::STATUS_UNAPPROVED;
		if (isset($_GET['B2bPurchaseBill']))
			$model->setAttributes($_GET['B2bPurchaseBill']);
			
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
		if ( $model == null ) $model = new B2bPurchaseBill();
		
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
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('b2bpurchaseBillDetail/list'),'icon'=>'icon-wrench icon-white');
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
	public function actionPrintInvoivePdf($id)
	{
		//$id  =2;
		$set = true;
			// $model = $this->loadModel($id, 'B2bPurchaseBill');
		// $billDetail = new B2bPurchaseBillDetail ( 'search' );
		
		
		$po =$this->loadModel($id, 'B2bPurchaseBill');
		
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
	
			$criteria = new CDbCriteria();
			$criteria->addCondition('purchase_bill_id ='.$po->id);
			$billDetail = B2bPurchaseBillDetail::model()->findAll($criteria);
			
 		if ($billDetail)
		$bill = B2bPurchaseBill::model()->findByPk ( $po->id );
	if(!empty($bill)){
		
		
		$bill_prefix = 'B';
        $billno = $bill->id;

		//Grn number

		$billGrn = $bill->grn_refrence_no;

        $month = date('m', strtotime($bill->start_date));
        if ($month > 3) {
            $year = date('Y', strtotime($bill->start_date));
			$year= substr($year, -2);
            $yearlast = $year + 1;
			$yearlast= substr($yearlast, -2);
        } else {
            $year = date('Y', strtotime($bill->start_date));
            $year = $year - 1;
				$year= substr($year, -2);
            $yearlast = date('Y', strtotime($bill->start_date));
			$yearlast= substr($yearlast, -2);
        }

        $billyear = date('Y', strtotime($bill->start_date));
        $newyear = $billyear + 1;
        $outlet = Outlet::model()->findByPk($bill->outlet_id);
        if ($outlet) {
            if ($outlet->bill_prefix == '') {
                $bill_prefix = $outlet->bill_prefix;
            } else {
                $bill_prefix = 'B';
            }
        }
        
		//$billno = 'B2B ' . $year . '-' . $yearlast . '/' . $bill_prefix . '-' . $billno;
     
		$billno = 'B2B' . $year . '-' . $yearlast . '/' . $bill_prefix . '-' .$billGrn;
		
		$invoicedate=$bill->start_date;
	}
	
			$vendor = Vendor::model ()->findByPk ( $po->vendor_id );
			$state = State::model ()->findByPk ( $vendor->state_id );
			
			$email = '' ;
			if($vendor){
				$to_id = $vendor->create_user_id;
				$vendoruser = User::model()->findByAttributes(array('id'=>$vendor->create_user_id));
				if($vendoruser){
					$email = $vendoruser->email;
				}
			}
			
			// echo"<pre>"; print_r($vendor); die;
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
		
		$mPDF1->WriteHTML($this->renderPartial('_invoicepdf',array('billDetail'=>$billDetail,'po'=>$po,'poid'=>$id , 'vendor'=>$vendor , 'state'=>$state , 'invoicedate'=>$invoicedate, 'billno'=>$billno), true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
		
	
		}else{
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		}
	}
	public function actionUserWise()
    {
        $model = new B2bPurchaseBill('userwisesearch'); 
        $model->unsetAttributes();
        $this->updateMenuItems($model);
        $columns = array();
        if (isset($_POST['B2bPurchaseBill']['columns'])) {
            $columns = $_POST['B2bPurchaseBill']['columns'];
        }
		
        $_GET ['B2bPurchaseBill']['status'] = B2bPurchaseBill::STATUS_APPROVED;

        if (isset($_POST['B2bPurchaseBill']['start_date']) && ($_POST['B2bPurchaseBill']['start_date'] != '') && (isset($_POST['B2bPurchaseBill']['end_date'])) && ($_POST['B2bPurchaseBill']['end_date'] != '')) {
            $_GET['B2bPurchaseBill']['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            $_GET['B2bPurchaseBill']['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
            Yii::app()->session['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            Yii::app()->session['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
        } else {
            Yii::app()->session['start_date'] = date('Y-m-d');
            Yii::app()->session['end_date'] = date('Y-m-d');
            $_GET['B2bPurchaseBill']['start_date'] = date('Y-m-d');
            $_GET['B2bPurchaseBill']['end_date'] = date('Y-m-d');
        }
		
        if (isset($_POST['B2bPurchaseBill']['item_id'])) {
            $_GET['B2bPurchaseBill']['item_id'] = $_POST['B2bPurchaseBill']['item_id'];
            Yii::app()->session['item_id'] = $_POST['B2bPurchaseBill']['item_id'];
        }
		
        if (isset($_GET['B2bPurchaseBill']))
            $model->setAttributes($_GET['B2bPurchaseBill']);
        // $columns = $model->getUserwiseColumns($columns);
        if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
            // $this->exportCSV($model->userwisesearch(), $columns);
        }
		//echo  $model->sql;
        $this->render('userwise', array(
            'model' => $model
        ));
    }
	
	
	
	 public function actionUserWiseExport()
    {
        $model = new B2bPurchaseBill('userwisesearch');
        $model->unsetAttributes();
        $this->updateMenuItems($model);
        $columns = array();
        if (isset($_POST['B2bPurchaseBill']['columns'])) {
            $columns = $_POST['B2bPurchaseBill']['columns'];
        }
        // if (isset($_POST['B2bPurchaseBill']['item_id'])) {
            // $_GET['B2bPurchaseBill']['item_id'] = $_POST['B2bPurchaseBill']['item_id'];
            // Yii::app()->session['item_id'] = $_POST['B2bPurchaseBill']['item_id'];
        // }
        // if (isset($_POST['B2bPurchaseBill']['start_date']) && ($_POST['B2bPurchaseBill']['start_date'] != '') && (isset($_POST['B2bPurchaseBill']['end_date'])) && ($_POST['B2bPurchaseBill']['end_date'] != '')) {
            // $_GET['B2bPurchaseBill']['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            // $_GET['B2bPurchaseBill']['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
            // Yii::app()->session['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            // Yii::app()->session['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
        // }
        if (isset($_GET['B2bPurchaseBill']))
            $model->setAttributes($_GET['B2bPurchaseBill']);
        $columns = $model->getUserwiseColumns($columns);
        if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
            $this->exportCSV($model->userwisesearch(), $columns);
        }
    }
	
	
	public function actionUserwisePdf()
    {
        $set = true;

        // $login = Yii::app()->user->model;

      $model = new B2bPurchaseBill('userwisesearch'); 

        if (isset($_GET['B2bPurchaseBill']))
            $model->setAttributes($_GET['B2bPurchaseBill']);

        // mPDF
        $mPDF1 = Yii::app()->ePdf->mpdf();

        // You can easily override default constructor's params
        $mPDF1 = Yii::app()->ePdf->mpdf('', 'A4');

        // render (full page)
        // $mPDF1->WriteHTML($this->render('index', array(), true));

        // Load a stylesheet
        // $stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
        // $mPDF1->WriteHTML($stylesheet, 1);

        // renderPartial (only 'view' of current controller)
        $mPDF1->WriteHTML($this->renderPartial('_pdf', array(
            'model' => $model
        ), true));

        // Renders image
        // $mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
        $mPDF1->Output();

        /*
         * $html2pdf = Yii::app()->ePdf->HTML2PDF();
         * $html2pdf->WriteHTML($this->renderPartial('_pdf', array(), true));
         * $html2pdf->Output();
         */
        // Outputs ready PDF
        /*
         * $mPDF1->Output();
         * $PDF = Yii::app()->ePdf->mpdf();
         * $PDF = Yii::app()->ePdf->mpdf('', 'A4');
         * $PDF ->WriteHTML($this->render('_pdf', true));
         * $PDF ->Output();
         */
    }
}