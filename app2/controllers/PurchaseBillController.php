<?php
namespace app\controllers;

use app\components\Criteria;
use app\components\Ui;
use app\models\Bill;
use app\models\PaymentReport;
use app\models\PurchaseBill;
use app\models\PurchaseBillDetail;
use app\models\Tax;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/PurchaseBillController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class PurchaseBillController extends BaseUiController {


	
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
		$purchasebills = PurchaseBill::find()->all();
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchasebill->grn_refrence_no = $purchasebill->id;
				$purchasebill->saveAttributes(['grn_refrence_no']);
			}
		}
	}
	/*
	 public function actionMerge(){
	
		if(isset($_POST['idList']) && isset($_POST['vendor_id'])){
			$query = PurchaseBill::find();
        $query->orderBy(['id' => SORT_DESC]);
			//$criteria->addInCondition('id', $_POST['idList']);
			$query->andWhere('id ='.$_POST['idList']['0']);
			$bill = $query->one();
			
			if($bill){
				$return_tax = 0;
				$igst = false;
				$query1 = PurchaseBillDetail::find();
				$query1->andWhere('purchase_bill_id ='.$bill->id);
				$existbilldetail = $query1->one();
				if($existbilldetail){
					$tax = Tax::findOne($existbilldetail->tax_id);
					if($tax->tax_val1 == '0.00' && $tax->tax_val2 == '0.00'&& $tax->tax_val3 == '0.00' && $tax->tax_val4 != '0.00'){
						$igst = true;
						$val = $tax->tax_val4/2;
						$query_2 = Tax::find();
						$query_2->andWhere('tax_val1 ='.$val);
						$query_2->andWhere('tax_val2 ='.$val);
						$query_2->andWhere('tax_val3 = 0.00');
						$tax = $query_2->one();
						if($tax){
							$return_tax = $tax->id;
						}
					}
				}
				$query_3 = PurchaseBill::find();
        $query_3->orderBy(['id' => SORT_DESC]);
				$query_3->andWhere(['id' => $_POST['idList']]);
				$query_3->andWhere('id !='.$bill->id);
				$bills = $query_3->all();
				if($bills){
					foreach($bills as $delbill){
						$query1_2 = PurchaseBillDetail::find();
						$query1_2->andWhere('purchase_bill_id ='.$delbill->id);
						$billdetails = $query1_2->all();
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
		$billDetail = new PurchaseBillDetail(['scenario' => 'search']);
		Yii::$app->session['billidList'] =  '';
		Yii::$app->session['bill_date_list'] =  '' ;
		Yii::$app->session['bill_expiry_val'] =  '';
		$_GET ['PurchaseBillDetail']['purchase_bill_id'] = $id;
		if (isset ( $_GET ['PurchaseBillDetail'] ))
		$billDetail->load($_GET, 'PurchaseBillDetail');
		
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
				'pobill'=>$pobill
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
	public function actionPrintPdf() {
		$model = new PurchaseBill;
		
		if(isset($_POST['PurchaseBill']['print_id']) && isset($_POST['PurchaseBill']['qty'])) {
				
			Yii::$app->session['print_id'] =  $_POST['PurchaseBill']['print_id'] ;
			Yii::$app->session['qty'] =  $_POST['PurchaseBill']['qty'] ;
			$model->load($_POST, 'PurchaseBill');
				
		}
		$mPDF1 = new \Mpdf\Mpdf(['tempDir' => Yii::getAlias('@runtime')]);
		
		# You can easily override default constructor's params
		$mPDF1 = new \Mpdf\Mpdf(['format' => 'A4', 'tempDir' => Yii::getAlias('@runtime')]);
		
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
		$model = new PurchaseBill;

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
		$dataProvider = new ActiveDataProvider(['query' => PurchaseBill::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => PurchaseBill::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new PurchaseBill(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['PurchaseBill']))
		{
			$model->load($_GET, 'PurchaseBill');
			return $this->renderPartial('_list', [
					'dataProvider' => $model->search(),
					'model' => $model,
			]);
		}
			
		return $this->renderPartial('_search', [
				'model' => $model,
		]);
	}
	public function actionList()
	{
		$model = new PurchaseBill(['scenario' => 'search']);
		$this->updateMenuItems($model);
		//$model->status = PurchaseBill::STATUS_UNAPPROVED;
		if (isset($_GET['PurchaseBill']))
			$model->load($_GET, 'PurchaseBill');
			
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
		if ( $model == null ) $model = new PurchaseBill();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('purchaseBill/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('purchaseBill/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('purchaseBill/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('purchaseBill/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('purchaseBill/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('purchaseBill/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('purchaseBill/create'),'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Manage', 'url'=>['purchaseBillDetail/list'],'icon'=>'icon-wrench icon-white'];
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
}