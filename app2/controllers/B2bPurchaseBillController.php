<?php
namespace app\controllers;

use app\components\Ui;
use app\models\B2bPurchaseBill;
use app\models\B2bPurchaseBillDetail;
use app\models\Bill;
use app\models\Outlet;
use app\models\PaymentReport;
use app\models\PurchaseBill;
use app\models\PurchaseBillDetail;
use app\models\State;
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
 * Yii 2 port of protected/controllers/B2bPurchaseBillController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class B2bPurchaseBillController extends BaseUiController {


	
	
	/* public function actionCorrectBill(){
		
		
		$date ='2022-01-21'; 
		$query = PurchaseBillDetail::find();
	$query->andWhere('cgst_per = 0.00');
	 $query->andWhere('date(create_time) = "' . $date . '"');
	 
			$bills = $query->all();
			
			
		
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
		$purchasebills = PurchaseBill::find()->all();
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchasebill->grn_refrence_no = $purchasebill->id;
				$purchasebill->saveAttributes(['grn_refrence_no']);
			}
		}
	}
	
	public function actionMerge(){
		$vendor_ids = [];
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$query = PurchaseBill::find();
        $query->orderBy(['id' => SORT_DESC]);
			//$criteria->addInCondition('id', $_POST['idList']);
			$query->andWhere('id ='.$_POST['idList']['0']);
			$bill = $query->one();
			
			if($bill){
				$query_2 = PurchaseBill::find();
        $query_2->orderBy(['id' => SORT_DESC]);
				$query_2->andWhere(['id' => $_POST['idList']]);
				$query_2->andWhere('id !='.$bill->id);
				$bills = $query_2->all();
				if($bills){
					$vendor_ids[] = $_POST['vendor_id'];
					foreach($bills as $delbill){
						$vendor_ids[] = $delbill->vendor_id;
						$query1 = PurchaseBillDetail::find();
						$query1->andWhere('purchase_bill_id ='.$delbill->id);
						$billdetails = $query1->all();
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
				
				$paymentreport = new PaymentReport ();
				$result = $paymentreport->setAllPayment ( $valued_rows );
				if ($result == 1) {
					Yii::$app->user->setFlash ( 'success', 'File is successfully uploaded' );
				} else {
					Yii::$app->user->setFlash ( 'danger', 'File getting problem! please upload again' );
				}
		}
		return $this->render( 'import', [
				'model' => $model
		] );
	}
	public function actionView($id) 
	{
	
		$model = $this->loadModel($id);
		$billDetail = new B2bPurchaseBillDetail(['scenario' => 'search']);
		Yii::$app->session['billidList'] =  '';
		Yii::$app->session['bill_date_list'] =  '' ;
		Yii::$app->session['bill_expiry_val'] =  '';
		$_GET ['B2bPurchaseBillDetail']['purchase_bill_id'] = $id;
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
		$billDetail->load($_GET, 'B2bPurchaseBillDetail');
		
		$pobill = new Bill(['scenario' => 'search']);
		//$this->updateMenuItems($pobill);
		$_GET['Bill']['po_id']= $model->purchase_order_id;
		if (isset($_GET['Bill']))
			$pobill->load($_GET, 'Bill');
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model,'billDetail'=>$billDetail,
				'pobill'=>$pobill,
				'id'=>$id
		]);
	}
	 public function actionPrintBarcode() {
		$model = new PurchaseBill;
		if(isset($_POST['PurchaseBill']['print_id']) && isset($_POST['PurchaseBill']['qty'])) {
			
			Yii::$app->session['print_id'] =  $_POST['PurchaseBill']['print_id'] ;
			Yii::$app->session['qty'] =  $_POST['PurchaseBill']['qty'] ;
			$model->load($_POST, 'PurchaseBill');
			
		}
		return $this->render('print', [ 'model' => $model]);
	}  
	public function actionprintPDF() {
		$model = new PurchaseBill;
		
		if(isset($_POST['PurchaseBill']['print_id']) && isset($_POST['PurchaseBill']['qty'])) {
				
			Yii::$app->session['print_id'] =  $_POST['PurchaseBill']['print_id'] ;
			Yii::$app->session['qty'] =  $_POST['PurchaseBill']['qty'] ;
			$model->load($_POST, 'PurchaseBill');
				
		}
		$mPDF1 = Yii::$app->ePdf->mpdf();
		
		# You can easily override default constructor's params
		$mPDF1 = Yii::$app->ePdf->mpdf('', 'A4');
		
		# render (full page)
		//$mPDF1->WriteHTML($this->render('index', array(), true));
		
		# Load a stylesheet
		//$stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
		//$mPDF1->WriteHTML($stylesheet, 1);
		
		# renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML($this->renderPartial('_printpdf',['model'=>$model], true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
	} 
	public function actionPrint(){
	
		if(isset($_POST['billidList']) && isset($_POST['bill_date_list'])) {
			Yii::$app->session['print_id'] = '';
			Yii::$app->session['billidList'] =  $_POST['billidList'] ;
			Yii::$app->session['bill_date_list'] =  $_POST['bill_date_list'] ;
			Yii::$app->session['bill_expiry_val'] =  $_POST['bill_expiry_val'] ;
			//Yii::$app->session['print_id'] =  $_POST['billidList']['0'] ;
			if(isset($_POST['packing_date_list'])){
				Yii::$app->session['packing_date_list'] =  $_POST['packing_date_list'] ;
			}
			if(isset( $_POST['billidList']['0'] )){
			$query = PurchaseBillDetail::find();
			Criteria::compare($query, 'id', $_POST['billidList']['0']);
			$purchasebilldetail = $query->one();
			Yii::$app->session['qty'] = $purchasebilldetail->approved_qty ;
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
			$model->load($_POST, 'PurchaseBill');

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
		
		$this->performAjaxValidation($model, 'purchase-bill-form');

		if (isset($_POST['PurchaseBill'])) {
			$model->load($_POST, 'PurchaseBill');

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
		$model = $this->loadModel($id, PurchaseBill::class);
		
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
		$dataProvider = new ActiveDataProvider(['query' => B2bPurchaseBill::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => B2bPurchaseBill::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	

	public function actionList()
	{
		$model = new PurchaseBill(['scenario' => 'search']);
		$this->updateMenuItems($model);
		//$model->status = PurchaseBill::STATUS_UNAPPROVED;
		if (isset($_GET['B2bPurchaseBill']))
			$model->load($_GET, 'B2bPurchaseBill');
			
			return $this->render('list', [
					'model' => $model,
			]);
	}
	public function actionAdmin() 
	{
		$model = new PurchaseBill(['scenario' => 'search']);
		$this->updateMenuItems($model);
		$columns = [];
		if (isset ( $_POST ['PurchaseBill'] ['columns'] )) {
			$columns = $_POST ['PurchaseBill'] ['columns'];
		}
		$_GET['PurchaseBill']['status'] = PurchaseBill::STATUS_APPROVED;
		if (isset($_GET['PurchaseBill']))
			$model->load($_GET, 'PurchaseBill');
			$columns = $model->getColumns ( $columns );
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search (), $columns );
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
		if ( $model == null ) $model = new B2bPurchaseBill();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('b2bPurchaseBill/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('b2bPurchaseBill/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('b2bPurchaseBill/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('b2bPurchaseBill/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('b2bPurchaseBill/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('b2bPurchaseBill/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('b2bPurchaseBill/create'),'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url'=>['b2bpurchaseBillDetail/list'],'icon'=>'icon-wrench icon-white'];
					//$this->menu[] = array('label'=>'Print Barcode', 'url'=>array('purchaseBill/print','id'=>$model->id),'icon'=>'icon-wrench icon-white');
				/* 	$this->menu[] = array('label'=>'Delete', 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>'Update', 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				 */
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
			// $model = $this->loadModel($id);
		// $billDetail = new B2bPurchaseBillDetail(['scenario' => 'search']);
		
		
		$po =$this->loadModel($id);
		
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
	
			$query = B2bPurchaseBillDetail::find();
			$query->andWhere('purchase_bill_id ='.$po->id);
			$billDetail = $query->all();
			
 		if ($billDetail)
		$bill = B2bPurchaseBill::findOne( $po->id );
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
        $outlet = Outlet::findOne($bill->outlet_id);
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
	
			$vendor = Vendor::findOne( $po->vendor_id );
			$state = State::findOne( $vendor->state_id );
			
			$email = '' ;
			if($vendor){
				$to_id = $vendor->create_user_id;
				$vendoruser = User::findOne(['id'=>$vendor->create_user_id]);
				if($vendoruser){
					$email = $vendoruser->email;
				}
			}
			
			// echo"<pre>"; print_r($vendor); die;
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
		
		$mPDF1->WriteHTML($this->renderPartial('_invoicepdf',['billDetail'=>$billDetail,'po'=>$po,'poid'=>$id , 'vendor'=>$vendor , 'state'=>$state , 'invoicedate'=>$invoicedate, 'billno'=>$billno], true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
		
	
		}else{
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		}
	}
	public function actionUserWise()
    {
        $model = new B2bPurchaseBill(['scenario' => 'userwisesearch']);
        $this->updateMenuItems($model);
        $columns = [];
        if (isset($_POST['B2bPurchaseBill']['columns'])) {
            $columns = $_POST['B2bPurchaseBill']['columns'];
        }
		
        $_GET ['B2bPurchaseBill']['status'] = B2bPurchaseBill::STATUS_APPROVED;

        if (isset($_POST['B2bPurchaseBill']['start_date']) && ($_POST['B2bPurchaseBill']['start_date'] != '') && (isset($_POST['B2bPurchaseBill']['end_date'])) && ($_POST['B2bPurchaseBill']['end_date'] != '')) {
            $_GET['B2bPurchaseBill']['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            $_GET['B2bPurchaseBill']['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
            Yii::$app->session['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            Yii::$app->session['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
        } else {
            Yii::$app->session['start_date'] = date('Y-m-d');
            Yii::$app->session['end_date'] = date('Y-m-d');
            $_GET['B2bPurchaseBill']['start_date'] = date('Y-m-d');
            $_GET['B2bPurchaseBill']['end_date'] = date('Y-m-d');
        }
		
        if (isset($_POST['B2bPurchaseBill']['item_id'])) {
            $_GET['B2bPurchaseBill']['item_id'] = $_POST['B2bPurchaseBill']['item_id'];
            Yii::$app->session['item_id'] = $_POST['B2bPurchaseBill']['item_id'];
        }
		
        if (isset($_GET['B2bPurchaseBill']))
            $model->load($_GET, 'B2bPurchaseBill');
        // $columns = $model->getUserwiseColumns($columns);
        if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
            // $this->exportCSV($model->userwisesearch(), $columns);
        }
		//echo  $model->sql;
        return $this->render('userwise', [
            'model' => $model
        ]);
    }
	
	
	
	 public function actionUserWiseExport()
    {
        $model = new B2bPurchaseBill(['scenario' => 'userwisesearch']);
        $this->updateMenuItems($model);
        $columns = [];
        if (isset($_POST['B2bPurchaseBill']['columns'])) {
            $columns = $_POST['B2bPurchaseBill']['columns'];
        }
        // if (isset($_POST['B2bPurchaseBill']['item_id'])) {
            // $_GET['B2bPurchaseBill']['item_id'] = $_POST['B2bPurchaseBill']['item_id'];
            // Yii::$app->session['item_id'] = $_POST['B2bPurchaseBill']['item_id'];
        // }
        // if (isset($_POST['B2bPurchaseBill']['start_date']) && ($_POST['B2bPurchaseBill']['start_date'] != '') && (isset($_POST['B2bPurchaseBill']['end_date'])) && ($_POST['B2bPurchaseBill']['end_date'] != '')) {
            // $_GET['B2bPurchaseBill']['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            // $_GET['B2bPurchaseBill']['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
            // Yii::$app->session['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            // Yii::$app->session['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
        // }
        if (isset($_GET['B2bPurchaseBill']))
            $model->load($_GET, 'B2bPurchaseBill');
        $columns = $model->getUserwiseColumns($columns);
        if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
            $this->exportCSV($model->userwisesearch(), $columns);
        }
    }
	
	
	public function actionUserwisePdf()
    {
        $set = true;

        // $login = Yii::$app->user->model;

      $model = new B2bPurchaseBill(['scenario' => 'userwisesearch']); 

        if (isset($_GET['B2bPurchaseBill']))
            $model->load($_GET, 'B2bPurchaseBill');

        // mPDF
        $mPDF1 = Yii::$app->ePdf->mpdf();

        // You can easily override default constructor's params
        $mPDF1 = Yii::$app->ePdf->mpdf('', 'A4');

        // render (full page)
        // $mPDF1->WriteHTML($this->render('index', array(), true));

        // Load a stylesheet
        // $stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
        // $mPDF1->WriteHTML($stylesheet, 1);

        // renderPartial (only 'view' of current controller)
        $mPDF1->WriteHTML($this->renderPartial('_pdf', [
            'model' => $model
        ], true));

        // Renders image
        // $mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
        $mPDF1->Output();

        /*
         * $html2pdf = Yii::$app->ePdf->HTML2PDF();
         * $html2pdf->WriteHTML($this->renderPartial('_pdf', array(), true));
         * $html2pdf->Output();
         */
        // Outputs ready PDF
        /*
         * $mPDF1->Output();
         * $PDF = Yii::$app->ePdf->mpdf();
         * $PDF = Yii::$app->ePdf->mpdf('', 'A4');
         * $PDF ->WriteHTML($this->render('_pdf', true));
         * $PDF ->Output();
         */
    }
}