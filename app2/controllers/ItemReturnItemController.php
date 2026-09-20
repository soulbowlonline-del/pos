<?php
namespace app\controllers;

use app\components\Criteria;
use app\components\Ui;
use app\models\CreditNote;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemReturn;
use app\models\ItemReturnItem;
use app\models\ItemStock;
use app\models\ItemTax;
use app\models\ItemVendor;
use app\models\Mrs;
use app\models\MrsDetail;
use app\models\Notification;
use app\models\Organization;
use app\models\PurchaseBill;
use app\models\StockLog;
use app\models\Tax;
use app\models\Vendor;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/ItemReturnItemController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class ItemReturnItemController extends BaseUiController {


public function actionReport($id = null) {
		$model = new ItemReturnItem(['scenario' => 'search']);
		
		$this->updateMenuItems ( $model );
		$columns = [];
		Yii::warning( var_export($_POST, true), '$_POST');
		if (isset ( $_POST ['ItemReturnItem'] ['tally_start_date'] ) && ($_POST ['ItemReturnItem'] ['tally_start_date'] != '') && (isset ( $_POST ['ItemReturnItem'] ['tally_end_date'] )) && ($_POST ['ItemReturnItem'] ['tally_end_date'] != '')) {
			$_GET ['ItemReturnItem'] ['tally_start_date'] = $_POST ['ItemReturnItem'] ['tally_start_date'];
		$_GET ['ItemReturnItem'] ['tally_end_date'] = $_POST ['ItemReturnItem'] ['tally_end_date'];
			Yii::$app->session ['returnitem_start_date'] = date('Y-m-d',strtotime($_POST ['ItemReturnItem'] ['tally_start_date']));
			Yii::$app->session ['returnitem_end_date'] = date('Y-m-d',strtotime($_POST ['ItemReturnItem'] ['tally_end_date']));
		}
		Yii::warning( var_export(Yii::$app->session ['returnitem_start_date'], true), 'returnsession');
		if (isset ( $_POST ['ItemReturnItem'] ['columns'] )) {
			$columns = $_POST ['ItemReturnItem'] ['columns'];
		}
		if (isset ( $_GET ['ItemReturnItem'] ))
			$model->load($_GET, 'ItemReturnItem');
		$columns = $model->getColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->reportsearch (), $columns );
		}
		
		return $this->render( 'report', [
				'model' => $model 
		] );
	}
	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionAjaxTax() {
		$option = '';
		$cgst = 0.00;
		$sgst = 0.00;
		$cess = 0.00;
		$igst = 0.00;
		$mrp = 0.00;
		$sale_rate = 0.00;
		$price = 0.00;
		$alreadypermissions = [];
		$tax = null;
		$attr = "";
		$item = null;
		$msg = 'Inactive';
		if (isset ( $_POST ['item_detail_id'] ) && isset($_POST ['vendor_id'])) {
				
			$itemdetail = ItemDetail::findOne( ['bar_code'=>$_POST ['item_detail_id'],
					'status'=>ItemDetail::STATUS_ACTIVE
			]);
			if($itemdetail){
				$item = Item::findOne( $itemdetail->item_id);
				$tax = Tax::findOne($itemdetail->tax_id);
			}else{
				$itemTax = ItemTax::findOne( [
						'item_detail_id' => $_POST ['item_detail_id']
				] );
				if ($itemTax) {
					$tax = Tax::findOne( $itemTax->tax_id );
				}
			}
			
			if($item != null){
				$query = ItemVendor::find();
				$query->orderBy(['id' => SORT_DESC]);
				$query->andWhere('item_detail_id ='.$item->id);
				$vendor =  $query->one();
				if($vendor->vendor_id == $_POST ['vendor_id'] ){
					$msg = 'success';
				}else{
					$msg = 'failed';
				}
				$mrp = $itemdetail->getItemDetailMrp();
				$sale_rate = $itemdetail->getItemDetailSaleRate();
				$price = $item->purchase_price;
			}
				
			if ($tax != null && ($itemdetail)) {
				$cgst = $tax->tax_val1;
				$sgst = $tax->tax_val2;
				$cess = $tax->tax_val3;
				$igst = $tax->tax_val4;
				$attr = $itemdetail->getCompanyBarcode($itemdetail->id);
			}
				
			$taxes = Tax::find()->all();
			$option .= '<select class="form-control" id="ItemReturnItem_item_detaill_list_id" name="ItemReturnItem[tax_id]"><option value="" id="ckbCheckAll">-Select-</option>';
			if ($taxes) {
				foreach ( $taxes as $taxx ) {
					$selected = '';
					if($tax != null){
						if ($taxx->id == $tax->id) {
							$selected = 'selected';
						}
					}
					$option .= '<option value="' . $taxx->id . '"  selected="' . $selected . '">' . $taxx->title . '</option>';
				}
			} else {
				// $option .= '<option value="">-Select-</option>';
			}
			$option .= '</select>';
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		$data ['options'] = $option;
		$data ['cgst'] = $cgst;
		$data ['sgst'] = $sgst;
		$data ['cess'] = $cess;
		$data ['igst'] = $igst;
		$data ['mrp'] = $mrp;
		$data ['sale_rate'] = $sale_rate;
		if($itemdetail){
			$data ['item_detail_id'] = $itemdetail->id;
		}
		if($item){
			$data ['item_id'] = $item->id;
			$data ['item_title'] = $item->title;
		}
		if($tax != null){
			$data ['tax_id'] = $tax->id;
		}else{
			$data ['tax_id'] = 0;
		}
		$data ['price'] = $price;
		$data ['attr'] = $attr;
		$data ['msg'] = $msg;
		echo json_encode ( $data );
	}
	
	
	public function actionAjaxbillno() {
		$bill_no = '';
		if (isset ( $_POST ['grn_no'] )) {
				
			$query = PurchaseBill::find();
        $query->orderBy(['id' => SORT_DESC]);
			$query->andWhere('grn_refrence_no =' . $_POST ['grn_no']);
			
				
			$bill = $query->one();
			if($bill){
				$bill_no = $bill->bill_no;
			}
		}
		echo $bill_no;
	}

	public function actionAjaxCreditBillNo() {
		if (isset ( $_POST ['credit_note_no'] )) {
			$month = date('m');
			if($month > 3){
				$year = date('Y');
				$yearlast = $year + 1;
				$start_date = $year.'-04-01';
				$end_date = $yearlast.'-03-31';
			}else{
				$year = date('Y');
				$yearlast = $year - 1;
				$start_date = $yearlast.'-04-01';
				$end_date = $year.'-03-31';
			}
			$query = ItemReturn::find();
			// $criteria->addCondition ( 'vendor_id =' . $bill->vendor_id );
			if($start_date != '' && $end_date != ''){
				$query->andWhere(['between', 'date(create_time)', $start_date, $end_date]);
			}
			Criteria::compare($query, 'credit_note_no', $_POST ['credit_note_no']);
			$bill = $query->all();
			if (!$bill) {
				echo 'Success';
			} else {
				echo 'Fail';
			}
		} else {
			echo 'Fail';
		}
	}

	public function actionAjaxTaxTable() {
		if (isset ( $_POST ['tax_id'] ) && isset ( $_POST ['id'] ) && isset ( $_POST ['return_item_ids'] )) {
			
			$return_item_ids = $_POST ['return_item_ids'];
			$model = new ItemReturnItem();

			return $this->renderPartial( '_tax', [
				  'returnid' => $_POST ['id'],
					'tax_id' => $_POST ['tax_id'],
					'return_item_ids' => $return_item_ids,
					'model' => $model, 
			]
			 );
		}
	}

	public function actionAjaxItems() {
		$option = '';
		$alreadypermissions = [];
		if (isset ( $_POST ['item_id'] )) {
				
			$query = ItemDetail::find();
			$query->andWhere('status =' . ItemDetail::STATUS_ACTIVE);
			$query->andWhere('item_id =' . $_POST ['item_id']);
				
			$itemdetails = $query->all();
			$option .= '<select class="form-control" onChange="checkTaxes()" id="ItemReturnItem_item_detaill_id" name="ItemReturnItem[item_detail_id]"><option value="" id="ckbCheckAll">-Select-</option>';
			if ($itemdetails) {
				foreach ( $itemdetails as $itemdetail ) {
					$stock = $itemdetail->checkStock();
					if($stock > 0){
						$option .= '<option value="' . $itemdetail->id . '">' . $itemdetail->bar_code . '</option>';
					}
				}
			} else {
				// $option .= '<option value="">-Select-</option>';
			}
			$option .= '</select>';
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		echo $option;
	}
	public function actionView($id) 
	{
		$model = $this->loadModel($id);
	
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}

	public function actionCreate() 
	{
		$model = new ItemReturnItem;

		//$this->performAjaxValidation($model, 'item-return-item-form');
       // echo '<pre>';
       // print_r($_POST['ItemReturnItem']);exit;
		if (isset($_POST['ItemReturnItem'])) {
			
			$itemdetail = ItemDetail::findOne( ['bar_code'=>$_POST['ItemReturnItem'] ['bar_code'],
					'status'=>ItemDetail::STATUS_ACTIVE
			]);
			$item = Item::findOne($_POST['ItemReturnItem']['item_id']);
			if($item && $itemdetail){
				$_POST['ItemReturnItem']['item_detail_id'] = $itemdetail->id;
			$barcodeQty = $item->getBarCodeTotalRemainingQuantity();
			
			$itemreturn = ItemReturn::findOne(['status'=>ItemReturn::STATUS_PENDING,'vendor_id'=>$_POST['ItemReturnItem']['vendor_id'],
					'outlet_id'=>$_POST['ItemReturnItem']['outlet_id']
			]);
			if($itemreturn == null){
				$itemreturn = new ItemReturn();
				$itemreturn->vendor_id = $_POST['ItemReturnItem']['vendor_id'];
				$itemreturn->outlet_id = $_POST['ItemReturnItem']['outlet_id'];
				if($itemreturn->save()){
					echo 'success';
				}else{
					print_r($itemreturn->getErrors());exit;
				}
			
			}
			$model = ItemReturnItem::findOne(['item_id'=>$_POST['ItemReturnItem']['item_id'],
					'item_detail_id'=>$itemdetail->id,'return_id'=>$itemreturn->id,
			]);
			if($model == null){
				$model = new ItemReturnItem;
			}
			$model->load($_POST, 'ItemReturnItem');
			
			$model->return_id = $itemreturn->id;
			if ($model->save()) {
				echo 'success';
			}else{
				print_r($model->getErrors());exit;
			}
			
			}
		}
		
	}
  public function actionAjaxupdate(){
  
  	$returns = $_POST;
  	$qtys = $returns['qty'];
  	foreach ( $qtys as $key => $qty ) {
  		$model = $this->loadModel($key);
  		$returnmodel = $this->loadModel($model->return_id, ItemReturn::class);
  		if(isset($_POST['gross_amt'])){
  		$returnmodel->gross_amt = $_POST['gross_amt'];
  		}
  		if(isset($_POST['total_discount'])){
  			$returnmodel->discount_amt = $_POST['total_discount'];
  		}
  		if(isset($_POST['tax_amount'])){
  			$returnmodel->tax_amt = $_POST['tax_amount'];
  		}
		
  		if(isset($_POST['bill_amount'])){
  			$returnmodel->total_amt = $_POST['bill_amount'];
  		}
		if(isset($_POST['credit_note_no'])){
  		$returnmodel->credit_note_no = $_POST['credit_note_no'];
  		}
		if(isset($_POST['credit_note_date'])){
  		$returnmodel->credit_note_date = date('Y-m-d',strtotime($_POST['credit_note_date']));
  		}
		
  		if(isset($_POST['invoice_no'])){
  			$returnmodel->invoice_no = $_POST['invoice_no'];
  		}
  		if(isset($_POST['bill_no'])){
  			$returnmodel->bill_no = $_POST['bill_no'];
  		}
  		if(isset($_POST['grn_no'])){
  			$returnmodel->grn_no = $_POST['grn_no'];
  		}
  		$returnmodel->status = ItemReturn::STATUS_DONE;
		$returnmodel->grn_save_date = date('Y-m-d H:i:s');
		
  		if($returnmodel->save()){
  		if (isset ( $returns ['qty']  )) {
  			$model->qty = $returns ['qty'] [$key];
  		}
  		if (isset ( $returns ['mrp'] )) {
  			$model->mrp = $returns ['mrp'] [$key];
  		}
  		if (isset ( $returns ['price'] )) {
  			$model->price = $returns ['price'] [$key];
  		}
  		if (isset ( $returns ['salerate'] )) {
  			$model->sale_rate = $returns ['salerate'] [$key];
  		}
  		if (isset ( $returns ['discount'] )) {
  			$model->discount = $returns ['discount'] [$key];
  		}
  		if (isset ( $returns ['discount_amt'] )) {
  			$model->discount_amt = $returns ['discount_amt'] [$key];
  		}
  		if (isset ( $returns ['discount1'] )) {
  			$model->discount1 = $returns ['discount1'] [$key];
  		}
  		if (isset ( $returns ['discount_amt1'] )) {
  			$model->discount_amt1 = $returns ['discount_amt1'] [$key];
  		}
			if (isset ( $returns ['taxselectData'] )) {
				$model->tax_id = $returns ['taxselectData'] [$key];
			}
  		if (isset ( $returns ['cgstData'] )) {
  			$model->cgst_per = $returns ['cgstData'] [$key];
  		}
  		if (isset ( $returns ['sgstData'] )) {
  			$model->sgst_per = $returns ['sgstData'] [$key];
  		}
  		if (isset ( $returns ['cessData'] )) {
  			$model->cess_per = $returns ['cessData'] [$key];
  		}
  		if (isset ( $returns ['cgstamtData'] )) {
  			$model->cgst_amt = $returns ['cgstamtData'] [$key];
  		}
  		if (isset ( $returns ['sgstamtData'] )) {
  			$model->sgst_amt = $returns ['sgstamtData'] [$key];
  		}
  		if (isset ( $returns ['cessamtData'] )) {
  			$model->cess_amt = $returns ['cessamtData'] [$key];
  		}
  		if (isset ( $returns ['igstData'] )) {
  			$model->igst_per = $returns ['igstData'] [$key];
  		}
  		if (isset ( $returns ['igstamtData'] )) {
  			$model->igst_amt = $returns ['igstamtData'] [$key];
  		}
  		if (isset ( $returns ['other_charge'] )) {
  			$model->other_charge = $returns ['other_charge'] [$key];
  		}
  		if (isset ( $returns ['amount'] )) {
  			$model->total_amt = $returns ['amount'] [$key];
  		}
		if (isset ( $returns ['type_id'] )) {
  			$model->type_id = $returns ['type_id'] [$key];
  		}
		
  		$model->status = ItemReturn::STATUS_DONE;
  		if ($model->save ()) {
  			$set = true;
  			$transaction = Yii::$app->db->beginTransaction ();
  				
  			try {
  				$itemDetail = ItemDetail::findOne($model->item_detail_id);
  				if($itemDetail){
  					$itemStock = ItemStock::findOne(['item_detail_id'=>$itemDetail->id,
  							'outlet_id'=> $model->outlet_id]);
  					$item = Item::findOne($itemDetail->item_id);
  					$current = $itemStock->balance_qty;
  						
  					if($itemStock != null){
  						$itemStock->balance_qty = ($itemStock->balance_qty) - ($model->qty);
  				
  							
  							
  				
  						if($itemStock->save()){
  							$log = new StockLog();
  				
  							$log->item_detail_id = $itemDetail->id;
  							$log->item_id = $item->id;
  							$log->batch_no = $itemStock->batch_number;
  							$log->Qty = $model->qty;
  							$log->outlet_id = $model->outlet_id;
  							$log->vendor_id = $model->vendor_id;
  							$log->type_id = StockLog::TYPE_RETURNED;
  								
  							if($log->save()){
  											
										$queryItemStock = ItemStock::find();
										Criteria::compare($queryItemStock, 'item_id', $itemDetail->item_id);
										$queryItemStock->select('SUM(balance_qty) AS balance_qty');
										$queryItemStock->groupBy('item_id');
										$mrsItemStock = $queryItemStock->one();	

										$queryVendor = ItemVendor::find();
										$queryVendor->orderBy(['id' => SORT_DESC]);
										$queryVendor->andWhere('item_detail_id ='.$item->id);
										$vendor =  $queryVendor->one();
										
										/*Create MRS section*/
										$queryMrs = Mrs::find();
										$queryMrs->orderBy(['id' => SORT_DESC]);
										$queryMrs->limit(1);
										$queryMrs->andWhere('vendor_id ='.$vendor->vendor_id);
										$vendorMRS = $queryMrs->one();
								
								
									if($vendorMRS->id){
									$item = Item::findOne($item->id);
									$queryMrsD = MrsDetail::find();
									$queryMrsD->orderBy(['id' => SORT_DESC]);
									$queryMrsD->limit(1);
									$queryMrsD->andWhere('mrs_id ='.$vendorMRS->id);
									$queryMrsD->andWhere('item_id ='.$item->id);
									$vendorMRSD = $queryMrsD->one();
									
									
									if(empty($vendorMRSD)){
									
										/*Create MRS*/
										// $itemdetail = Item::findOne($item->item_id);
										$organization = Organization::find()->orderBy(['id' => SORT_DESC])->one();
										$itemdetail_ = ItemDetail::findOne($itemDetail->id);
										$tax='';
										$tax_id='';
										if($itemdetail_){
											$tax = Tax::findOne($itemdetail_->tax_id);
										$tax_id = $itemdetail_->tax_id;
										}
										Yii::warning( var_export($vendor->vendor_id, true), '$mrs_vendor_id');
										if($vendor->vendor_id != null){
										$mrs = Mrs::findOne(['status'=>Mrs::STATUS_PENDING,'vendor_id'=>$vendor->vendor_id,
										'outlet_id'=>$model->outlet_id
										]);
										Yii::warning( var_export($mrs, true), '$mrs_id');
										
										if($item->reorder_qty != ''){
										//$reorder_qty = $item->getReorderQty();
										$reorder_qty = $item->reorder_qty;
										}else{
										$reorder_qty = 10;
										}
										if($item->max_qty != ''){
										$max_qty = $item->max_qty;
										//$max_qty = $item->getMaximumQty();
										}else{
										$max_qty = 10;
										}
										if($item->min_qty != ''){
										$min_qty = $item->min_qty;
										//$min_qty = $item->getMinimumQty();
										}else{
										$min_qty = 10;
										}
										
										
										if($min_qty >= $mrsItemStock->balance_qty){
											
											$updated = true;
										if($mrs == null){
										$updated = false;
										$mrs = new Mrs();
										}


										$mrs->code = 'ddd';
										$mrs->mrs_date = date('Y-m-d');
										$mrs->mrs_req_date = date('Y-m-d');
										$mrs->outlet_id = $model->outlet_id;
										$mrs->vendor_id = $vendor->vendor_id;
									
										Yii::warning( var_export($mrs->vendor_id, true), '$mrs->vendor_id');
										//$mrs->tax_id = $this->tax_id;
 
										$mrs->organization_id = $organization->id;
										if($mrs->save()){
										$vendor = Vendor::findOne($mrs->vendor_id);

										if($updated){
										$msg = 'MRS is updated';
										}else{
										$msg = 'A new MRS is added';
										}
										$to_id = $vendor->create_user_id;
										// $type = Notification::TYPE_MRS;
										$model_id = $mrs->id;
										
										$itemdetail = ItemDetail::findOne( $itemDetail->id );
										$mrsdetail = MrsDetail::findOne(['item_detail_id'=>$model->item_detail_id,
											'mrs_id'=>$mrs->id
										]);
										if($mrsdetail == null){
										$mrsdetail = new MrsDetail();
										}
										$mrsdetail->price = $item->purchase_price;
										$mrsdetail->req_qty = $max_qty;
										$mrsdetail->approved_qty = $reorder_qty;
										$mrsdetail->min_qty =$min_qty;
										if($tax){
										$mrsdetail->cgst_per = $tax->tax_val1;
										$mrsdetail->sgst_per = $tax->tax_val2;
										$mrsdetail->cess_per = $tax->tax_val3;
										$mrsdetail->igst_per = $tax->tax_val4;
										$mrsdetail->cgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val1/100);
										$mrsdetail->sgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val2/100);
										$mrsdetail->cess_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val3/100);
										$mrsdetail->igst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val4/100);
										$mrsdetail->tax_id = $tax->id;
										}

										$mrsdetail->item_detail_id = $itemDetail->id;
										$mrsdetail->item_id =$model->item_id;
										$mrsdetail->outlet_id = $mrs->outlet_id;
										$mrsdetail->mrp = $itemdetail->getItemDetailMrp();
										$mrsdetail->sale_rate = $itemdetail->getItemDetailSaleRate();
										$mrsdetail->mrs_id = $mrs->id;
										$mrsdetail->discount = "0.00";
										$mrsdetail->discount_amt = "0.00";
										$mrsdetail->other_charge = "0.00";
										if($mrsdetail->getGSTTrue($mrs->id) == true){
										$mrsdetail->amount =($reorder_qty*($mrsdetail->price))+($mrsdetail->cgst_amt)+($mrsdetail->sgst_amt)+($mrsdetail->cess_amt);
										$price_cgst = ($mrsdetail->price  * $mrsdetail->cgst_per)/100;
										$price_sgst = ($mrsdetail->price  * $mrsdetail->sgst_per)/100;
										$price_cess = ($mrsdetail->price  * $mrsdetail->cess_per)/100;
										$calgst = $price_cgst+$price_sgst +$price_cess;
										}else{
										$mrsdetail->amount =($reorder_qty*$mrsdetail->price)+($mrsdetail->igst_amt);
										$price_igst = ($mrsdetail->price  * $mrsdetail->igst_per)/100;
										$calgst = $price_igst;
										}
										if($mrsdetail->price != '0.00' && $mrsdetail->price != null){
										$margin = (($mrsdetail->mrp)-($mrsdetail->price + $calgst))*100/($mrsdetail->price + $calgst);
										$mrsdetail->margin = $margin;
										}

										if($mrsdetail->save()){
																
										}else{

										} 
										
											
										}
										
										/*End Create MRS*/
												
									}
									
								}
							}
							
							}	
										
										
									
  							}else{
  								print_r($log->getErrors());exit;
  							}
  						}else{
  							$set = false;
  						}
  					}else{
  						$set = false;
  					}
  				
  				}
  				if($set == true){
					if($model->credit_note_no != 0){
  					$creditnote = new CreditNote();
  					$creditnote->credit_number = $returnmodel->credit_note_no;
  					$creditnote->amt = $returnmodel->total_amt;
  					if($creditnote->save()){
  						$returnmodel->credit_note_id = $creditnote->id;
  						$returnmodel->save();
  					}
					}
  					$transaction->commit ();
  				}else{
  					$transaction->rollback ();
  				}
  				} catch ( \Exception $e ) {
  					$transaction->rollback ();
  				}
  		}
  		}
  		}
  }

	public function actionAjaxupdateOnly(){
  
  	$returns = $_POST;
  	$qtys = $returns['qty'];
  	foreach ( $qtys as $key => $qty ) {
  		$model = $this->loadModel($key);
  		
				if (isset ( $returns ['qty']  )) {
					$model->qty = $returns ['qty'] [$key];
				}
				if (isset ( $returns ['mrp'] )) {
					$model->mrp = $returns ['mrp'] [$key];
				}
				if (isset ( $returns ['price'] )) {
					$model->price = $returns ['price'] [$key];
				}
				if (isset ( $returns ['salerate'] )) {
					$model->sale_rate = $returns ['salerate'] [$key];
				}
				if (isset ( $returns ['discount'] )) {
					$model->discount = $returns ['discount'] [$key];
				}
				if (isset ( $returns ['discount_amt'] )) {
					$model->discount_amt = $returns ['discount_amt'] [$key];
				}
				if (isset ( $returns ['discount1'] )) {
					$model->discount1 = $returns ['discount1'] [$key];
				}
				if (isset ( $returns ['discount_amt1'] )) {
					$model->discount_amt1 = $returns ['discount_amt1'] [$key];
				}
				if (isset ( $returns ['taxselectData'] )) {
					$model->tax_id = $returns ['taxselectData'] [$key];
				}
				if (isset ( $returns ['cgstData'] )) {
					$model->cgst_per = $returns ['cgstData'] [$key];
				}
				if (isset ( $returns ['sgstData'] )) {
					$model->sgst_per = $returns ['sgstData'] [$key];
				}
				if (isset ( $returns ['cessData'] )) {
					$model->cess_per = $returns ['cessData'] [$key];
				}
				if (isset ( $returns ['cgstamtData'] )) {
					$model->cgst_amt = $returns ['cgstamtData'] [$key];
				}
				if (isset ( $returns ['sgstamtData'] )) {
					$model->sgst_amt = $returns ['sgstamtData'] [$key];
				}
				if (isset ( $returns ['cessamtData'] )) {
					$model->cess_amt = $returns ['cessamtData'] [$key];
				}
				if (isset ( $returns ['igstData'] )) {
					$model->igst_per = $returns ['igstData'] [$key];
				}
				if (isset ( $returns ['igstamtData'] )) {
					$model->igst_amt = $returns ['igstamtData'] [$key];
				}
				if (isset ( $returns ['other_charge'] )) {
					$model->other_charge = $returns ['other_charge'] [$key];
				}
				if (isset ( $returns ['amount'] )) {
					$model->total_amt = $returns ['amount'] [$key];
				}
				if (isset ( $returns ['type_id'] )) {
					$model->type_id = $returns ['type_id'] [$key];
				}
			
				if ($model->save ()) {
					echo 'Success';
				} else {
					echo 'Failed';
				}
  	}
  }
	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id);
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'item-return-item-form');

		if (isset($_POST['ItemReturnItem'])) {
			$model->load($_POST, 'ItemReturnItem');

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
		$dataProvider = new ActiveDataProvider(['query' => ItemReturnItem::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => ItemReturnItem::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionSearch()
	{
		$model = new ItemReturnItem(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemReturnItem']))
		{
			$model->load($_GET, 'ItemReturnItem');
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
		$model = new ItemReturnItem(['scenario' => 'search']);
		$vendor_id = null;
		$outlet_id = null;
		$this->updateMenuItems($model);
		if(isset($_POST['ItemReturnItem']['vendor_id'])){
			$vendor_id = $_POST['ItemReturnItem']['vendor_id'];
			$_GET['ItemReturnItem']['vendor_id']=$vendor_id;
		}
		if($vendor_id == null){
			$_GET['ItemReturnItem']['id']=0;
		}
		$_GET['ItemReturnItem']['vendor_id']=$vendor_id;
		if(isset($_POST['ItemReturnItem']['outlet_id'])){
			$outlet_id = $_POST['ItemReturnItem']['outlet_id'];
			$_GET['ItemReturnItem']['outlet_id']=$outlet_id;
		}
		$_GET['ItemReturnItem']['status']=ItemReturn::STATUS_PENDING;
		if (isset($_GET['ItemReturnItem']))
			$model->load($_GET, 'ItemReturnItem');

		return $this->render('admin', [
			'model' => $model,'vendor_id'=>$vendor_id,'outlet_id'=>$outlet_id
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
		if ( $model == null ) $model = new ItemReturnItem();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('itemReturnItem/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemReturnItem/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemReturnItem/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemReturnItem/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemReturnItem/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					$this->menu[] = ['label'=>'List', 'url'=>['itemReturn/admin'],'icon'=>'icon-th-list icon-white'];
					//$this->menu[] = array('label'=>'Create', 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('itemReturnItem/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('itemReturnItem/admin'),'icon'=>'icon-wrench icon-white'];
					$this->menu[] = ['label'=>'Delete', 'url'=>'#', 'linkOptions' => ['submit' => ['delete', 'id' => $model->id], 
					'confirm'=>'Are you sure you want to delete this item?'],'icon'=>'icon-remove icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('itemReturnItem/create'),'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('itemReturnItem/update', ['id' => $model->id]), 'icon'=>'icon-edit icon-white'];				
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO($model);
		
		//merge actions with menu
		$this->actions = array_merge( $this->actions, $this->menu);
	}

	public function actionAjaxsavedata()
    {
        //die("sd");
        $returns = $_POST;
        $qtys = $returns['qty'];
        foreach ($qtys as $key => $qty) {
            $model = $this->loadModel($key);
           //echo "<pre>" ; print_r($model);die;
            $returnmodel = $this->loadModel($model->return_id, ItemReturn::class);
            //print_r($returnmodel);die;
            if (isset($_POST['gross_amt'])) {
                $returnmodel->gross_amt = $_POST['gross_amt'];
            }
            if (isset($_POST['total_discount'])) {
                $returnmodel->discount_amt = $_POST['total_discount'];
            }
            if (isset($_POST['tax_amount'])) {
                $returnmodel->tax_amt = $_POST['tax_amount'];
            }
            if (isset($_POST['bill_amount'])) {
                $returnmodel->total_amt = $_POST['bill_amount'];
            }
            if (isset($_POST['credit_note_no'])) {
                $returnmodel->credit_note_no = $_POST['credit_note_no'];
            }
            if (isset($_POST['credit_note_date'])) {
                $returnmodel->credit_note_date = date('Y-m-d', strtotime($_POST['credit_note_date']));
            }

            if (isset($_POST['invoice_no'])) {
                $returnmodel->invoice_no = $_POST['invoice_no'];
            }
            if (isset($_POST['bill_no'])) {
                $returnmodel->bill_no = $_POST['bill_no'];
            }
            if (isset($_POST['grn_no'])) {
                $returnmodel->grn_no = $_POST['grn_no'];
            }
            $returnmodel->status = ItemReturn::STATUS_DONE;
			
			$returnmodel->grn_save_date = date('Y-m-d H:i:s');
		
		
		
            if ($returnmodel->save()) {
                if (isset($returns['qty'])) {
                    $model->qty = $returns['qty'][$key];
                }
                if (isset($returns['mrp'])) {
                    $model->mrp = $returns['mrp'][$key];
                }
                if (isset($returns['price'])) {
                    $model->price = $returns['price'][$key];
                }
                if (isset($returns['salerate'])) {
                    $model->sale_rate = $returns['salerate'][$key];
                }
                if (isset($returns['discount'])) {
                    $model->discount = $returns['discount'][$key];
                }
                if (isset($returns['discount_amt'])) {
                    $model->discount_amt = $returns['discount_amt'][$key];
                }
                if (isset($returns['discount1'])) {
                    $model->discount1 = $returns['discount1'][$key];
                }
                if (isset($returns['discount_amt1'])) {
                    $model->discount_amt1 = $returns['discount_amt1'][$key];
                }
                if (isset($returns['cgstData'])) {
                    $model->cgst_per = $returns['cgstData'][$key];
                }
                if (isset($returns['sgstData'])) {
                    $model->sgst_per = $returns['sgstData'][$key];
                }
                if (isset($returns['cessData'])) {
                    $model->cess_per = $returns['cessData'][$key];
                }
                if (isset($returns['cgstamtData'])) {
                    $model->cgst_amt = $returns['cgstamtData'][$key];
                }
                if (isset($returns['sgstamtData'])) {
                    $model->sgst_amt = $returns['sgstamtData'][$key];
                }
                if (isset($returns['cessamtData'])) {
                    $model->cess_amt = $returns['cessamtData'][$key];
                }
                if (isset($returns['igstData'])) {
                    $model->igst_per = $returns['igstData'][$key];
                }
                if (isset($returns['igstamtData'])) {
                    $model->igst_amt = $returns['igstamtData'][$key];
                }
                if (isset($returns['other_charge'])) {
                    $model->other_charge = $returns['other_charge'][$key];
                }
                if (isset($returns['amount'])) {
                    $model->total_amt = $returns['amount'][$key];
                }
                if (isset($returns['type_id'])) {
                    $model->type_id = $returns['type_id'][$key];
                }

                $model->status = ItemReturn::STATUS_DONE;
                if ($model->save()) {
                    $set = true;
                    $transaction = Yii::$app->db->beginTransaction();

                    try {
                        $itemDetail = ItemDetail::findOne($model->item_detail_id);
                        if ($itemDetail) {
                            $itemStock = ItemStock::findOne([
                                'item_detail_id' => $itemDetail->id,
                                'outlet_id' => $model->outlet_id
                            ]);
                            $item = Item::findOne($itemDetail->item_id);
                            $current = $itemStock->balance_qty;

                            if ($itemStock != null) {
                                $itemStock->balance_qty = ($itemStock->balance_qty) - ($model->qty);

                                if ($itemStock->save()) {
                                    $log = new StockLog();

                                    $log->item_detail_id = $itemDetail->id;
                                    $log->item_id = $item->id;
                                    $log->batch_no = $itemStock->batch_number;
                                    $log->Qty = $model->qty;
                                    $log->outlet_id = $model->outlet_id;
                                    $log->vendor_id = $model->vendor_id;
                                    $log->type_id = StockLog::TYPE_RETURNED;

                                    if ($log->save()) {
										
										$queryItemStock = ItemStock::find();
										Criteria::compare($queryItemStock, 'item_id', $itemDetail->item_id);
										$queryItemStock->select('SUM(balance_qty) AS balance_qty');
										$queryItemStock->groupBy('item_id');
										$mrsItemStock = $queryItemStock->one();	
										
                                        $queryVendor = ItemVendor::find();
										$queryVendor->orderBy(['id' => SORT_DESC]);
										$queryVendor->andWhere('item_detail_id ='.$item->id);
										$vendor =  $queryVendor->one();

										/*Create MRS section*/
										$queryMrs = Mrs::find();
										$queryMrs->orderBy(['id' => SORT_DESC]);
										$queryMrs->limit(1);
										//$criteriaMrs->addCondition('vendor_id ='.$model->vendor_id);

                                        $queryMrs->andWhere('vendor_id ='.$vendor->vendor_id);

										$vendorMRS = $queryMrs->one();
								
								
									if($vendorMRS->id){
									$item = Item::findOne($item->id);
									$queryMrsD = MrsDetail::find();
									$queryMrsD->orderBy(['id' => SORT_DESC]);
									$queryMrsD->limit(1);
									$queryMrsD->andWhere('mrs_id ='.$vendorMRS->id);
									$queryMrsD->andWhere('item_id ='.$item->id);
									$vendorMRSD = $queryMrsD->one();
									
									
									if(empty($vendorMRSD)){
									
										/*Create MRS*/
										// $itemdetail = Item::findOne($item->item_id);
										$organization = Organization::find()->orderBy(['id' => SORT_DESC])->one();
										$itemdetail_ = ItemDetail::findOne($itemDetail->id);
										$tax='';
										$tax_id='';
										if($itemdetail_){
											$tax = Tax::findOne($itemdetail_->tax_id);
										$tax_id = $itemdetail_->tax_id;
										}
										// Yii::warning( var_export($model->vendor_id, true), '$mrs_vendor_id');
										// if($model->vendor_id != null){
										// $mrs = Mrs::findOne(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$model->vendor_id,
										// 'outlet_id'=>$model->outlet_id
										// ));

                                        Yii::warning( var_export($vendor->vendor_id, true), '$mrs_vendor_id');
										if($vendor->vendor_id != null){
										
                                            $mrs = Mrs::findOne(['status'=>Mrs::STATUS_PENDING,'vendor_id'=>$vendor->vendor_id,
										'outlet_id'=>$model->outlet_id
										]);
                                        
										Yii::warning( var_export($mrs, true), '$mrs_id');
										
										if($item->reorder_qty != ''){
										//$reorder_qty = $item->getReorderQty();
										$reorder_qty = $item->reorder_qty;
										}else{
										$reorder_qty = 10;
										}
										if($item->max_qty != ''){
										$max_qty = $item->max_qty;
										//$max_qty = $item->getMaximumQty();
										}else{
										$max_qty = 10;
										}
										if($item->min_qty != ''){
										$min_qty = $item->min_qty;
										//$min_qty = $item->getMinimumQty();
										}else{
										$min_qty = 10;
										}
										
										
										if($min_qty >= $mrsItemStock->balance_qty){
											
											$updated = true;
										if($mrs == null){
										$updated = false;
										$mrs = new Mrs();
										}


										$mrs->code = 'ddd';
										$mrs->mrs_date = date('Y-m-d');
										$mrs->mrs_req_date = date('Y-m-d');
										$mrs->outlet_id = $model->outlet_id;
										//$mrs->vendor_id = $model->vendor_id;

                                        $mrs->vendor_id = $vendor->vendor_id;
									
										Yii::warning( var_export($mrs->vendor_id, true), '$mrs->vendor_id');
										//$mrs->tax_id = $this->tax_id;
 
										$mrs->organization_id = $organization->id;
										if($mrs->save()){
										$vendor = Vendor::findOne($mrs->vendor_id);

										if($updated){
										$msg = 'MRS is updated';
										}else{
										$msg = 'A new MRS is added';
										}
										$to_id = $vendor->create_user_id;
										// $type = Notification::TYPE_MRS;
										$model_id = $mrs->id;
										
										$itemdetail = ItemDetail::findOne( $itemDetail->id );
										$mrsdetail = MrsDetail::findOne(['item_detail_id'=>$model->item_detail_id,
											'mrs_id'=>$mrs->id
										]);
										if($mrsdetail == null){
										$mrsdetail = new MrsDetail();
										}
										$mrsdetail->price = $item->purchase_price;
										$mrsdetail->req_qty = $max_qty;
										$mrsdetail->approved_qty = $reorder_qty;
										$mrsdetail->min_qty =$min_qty;
										if($tax){
										$mrsdetail->cgst_per = $tax->tax_val1;
										$mrsdetail->sgst_per = $tax->tax_val2;
										$mrsdetail->cess_per = $tax->tax_val3;
										$mrsdetail->igst_per = $tax->tax_val4;
										$mrsdetail->cgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val1/100);
										$mrsdetail->sgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val2/100);
										$mrsdetail->cess_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val3/100);
										$mrsdetail->igst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val4/100);
										$mrsdetail->tax_id = $tax->id;
										}

										$mrsdetail->item_detail_id = $itemDetail->id;
										$mrsdetail->item_id =$model->item_id;
										$mrsdetail->outlet_id = $mrs->outlet_id;
										$mrsdetail->mrp = $itemdetail->getItemDetailMrp();
										$mrsdetail->sale_rate = $itemdetail->getItemDetailSaleRate();
										$mrsdetail->mrs_id = $mrs->id;
										$mrsdetail->discount = "0.00";
										$mrsdetail->discount_amt = "0.00";
										$mrsdetail->other_charge = "0.00";
										if($mrsdetail->getGSTTrue($mrs->id) == true){
										$mrsdetail->amount =($reorder_qty*($mrsdetail->price))+($mrsdetail->cgst_amt)+($mrsdetail->sgst_amt)+($mrsdetail->cess_amt);
										$price_cgst = ($mrsdetail->price  * $mrsdetail->cgst_per)/100;
										$price_sgst = ($mrsdetail->price  * $mrsdetail->sgst_per)/100;
										$price_cess = ($mrsdetail->price  * $mrsdetail->cess_per)/100;
										$calgst = $price_cgst+$price_sgst +$price_cess;
										}else{
										$mrsdetail->amount =($reorder_qty*$mrsdetail->price)+($mrsdetail->igst_amt);
										$price_igst = ($mrsdetail->price  * $mrsdetail->igst_per)/100;
										$calgst = $price_igst;
										}
										if($mrsdetail->price != '0.00' && $mrsdetail->price != null){
										$margin = (($mrsdetail->mrp)-($mrsdetail->price + $calgst))*100/($mrsdetail->price + $calgst);
										$mrsdetail->margin = $margin;
										}

										if($mrsdetail->save()){
																
										}else{

										} 
										
											
										}
										
										/*End Create MRS*/
												
									}
									
								}
							}
							
							}	
										
										
										
									} else {
                                        print_r($log->getErrors());
                                        exit();
                                    }
                                } else {
                                    $set = false;
                                }
                            } else {
                                $set = false;
                            }
                        }
                        if ($set == true) {
                            if ($model->credit_note_no != 0) {
                                $creditnote = new CreditNote();
                                $creditnote->credit_number = $returnmodel->credit_note_no;
                                $creditnote->amt = $returnmodel->total_amt;
                                if ($creditnote->save()) {
                                    $returnmodel->credit_note_id = $creditnote->id;
                                    $returnmodel->save();
                                }
                            }
                            $transaction->commit();
                        } else {
                            $transaction->rollback();
                        }
                    } catch (\Exception $e) {
                        $transaction->rollback();
                    }
                }
            }
        }
        
    }

	public function actionPrintPdf()
	{
		// print_r($_GET); exit;

		$model = new ItemReturnItem();

		# mPDF
		$mPDF1 = Yii::$app->ePdf->mpdf();
		
		# You can easily override default constructor's params
		$mPDF1 = Yii::$app->ePdf->mpdf('', 'A4');
		
		# renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML($this->renderPartial('_pdf',['model'=>$model,'outlet_id'=>$_GET['outlet_id'],'vendor_id'=>$_GET['vendor_id']], true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
		// if($email != '' && ($role->id != $login->role_id)){
		// 	$from = Yii::$app->params['mail_email'] ;
		// 	$to      = $email;
		// 	$subject = 'Your purchase order :';
		
		// 	$view = $this->renderPartial ( '/mail/purchase_order_pdf', array (
		// 			'po'=>$po
		// 	), true );
		
		
		// 	//$purchaseorder->mailsend ( $to, $from, $subject, $view );
		// }
		
	}
}