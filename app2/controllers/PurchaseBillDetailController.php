<?php
namespace app\controllers;

use app\components\Criteria;
use app\components\Ui;
use app\models\CreditNote;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemStock;
use app\models\ItemTax;
use app\models\ItemVendor;
use app\models\Mrn;
use app\models\Mrs;
use app\models\MrsDetail;
use app\models\Notification;
use app\models\Outlet;
use app\models\PurchaseBill;
use app\models\PurchaseBillDetail;
use app\models\StockLog;
use app\models\Tax;
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
 * Yii 2 port of protected/controllers/PurchaseBillDetailController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class PurchaseBillDetailController extends BaseUiController {
	
	public function actionUpdateMRP($id){
		$purchasebill = $this->loadModel($id, PurchaseBill::class);
		if($purchasebill){
			$purchaseBillDetails = PurchaseBillDetail::findAll(['purchase_bill_id'=>$purchasebill->id]);
			if($purchaseBillDetails){
				foreach($purchaseBillDetails as $purchaseBillDetail){
					$itemDetail = ItemDetail::findOne($purchaseBillDetail->item_detail_id);
					if($itemDetail){
						$itemDetail->mrp = $purchaseBillDetail->mrp;
						$itemDetail->saveAttributes(['mrp']);
					}
				}
			}
		}
	}
	public function actionAjaxCreate($id = null)
	{
		$existpo = $this->loadModel($id, PurchaseBill::class);
		$model = new PurchaseBillDetail;
	
		//$this->performAjaxValidation($model, 'purchase-order-detail-form');
	
		if (isset($_POST['PurchaseBillDetail'])) {
				
			$model->load($_POST, 'PurchaseBillDetail');
			$model->outlet_id = $existpo->outlet_id;
			if($model->approved_qty != '')
				$model->bal_qty = ($model->req_qty - $model->approved_qty);
				$model->purchase_bill_id = $id;
				if ($model->save()) {
					
					echo 'Data is saved successfully';
				} else {
					print_r ( $model->getErrors () );
					exit ();
				}
		}
		else{
			echo 'Please add valid data';
		}
	}
	public function actionAjaxPBillTax() {
		$option = '';
		$cgst = 0.00;
		$sgst = 0.00;
		$cess = 0.00;
		$igst = 0.00;
		$mrp = 0.00;
		$sale_rate = 0.00;
		$price = 0.00;
		$max_qty = 0;
		$alreadypermissions = [];
		$tax = null;
		$item = null;
		$attr = "";
		$msg = 'Inactive';
		if (isset ( $_POST ['item_detail_id'] ) && isset ( $_POST ['vendor_id'] )) {
			$itemdetail = ItemDetail::findOne( ['bar_code'=>$_POST ['item_detail_id'],
					'status'=>ItemDetail::STATUS_ACTIVE
			]);
			if($itemdetail){
					
				$item = Item::findOne( $itemdetail->item_id);
					
				$query = ItemVendor::find();
				$query->orderBy(['id' => SORT_DESC]);
				$query->andWhere('item_detail_id ='.$item->id);
				$vendor =  $query->one();
				if($vendor->vendor_id == $_POST ['vendor_id'] ){
					$msg = 'success';
				}else{
					$msg = 'failed';
				}
				if($itemdetail){
					$tax = Tax::findOne($itemdetail->tax_id);
				}else{
					$itemTax = ItemTax::findOne( [
							'item_detail_id' => $_POST ['item_detail_id']
					] );
					if ($itemTax) {
						$tax = Tax::findOne( $itemTax->tax_id );
					}
				}
				if($item){
					$mrp = $itemdetail->getItemDetailMrp();
					$sale_rate = $itemdetail->getItemDetailSaleRate();
					$price = $item->purchase_price;
					$max_qty = $item->max_qty;
				}
					
				if ($tax != null) {
					$cgst = $tax->tax_val1;
					$sgst = $tax->tax_val2;
					$cess = $tax->tax_val3;
					$igst = $tax->tax_val4;
					$attr = $itemdetail->getCompanyBarcode($itemdetail->id);
				}

				$query_2 = Outlet::find();
				$query_2->orderBy(['id' => SORT_ASC]);
				$query_2->limit(1);
				$outlet_model = $query_2->one();

				$tax_type = 0;  // 0 => gst, 1 => igst
				if ($outlet_model) {
					$vend = Vendor::findOne( $vendor->vendor_id );
					if ($vend->state_id != $outlet_model->state_id) {
						$tax_type = 1;
					}
				}
				
				$taxQuery = Tax::find();
				$taxQuery->andWhere('type_id ='.$tax_type);
				$taxes = $taxQuery->all();
				$option .= '<select class="form-control" id="PurchaseBillDetail_item_detaill_list_id" name="PurchaseBillDetail[tax_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
		}
	
		$data ['options'] = $option;
		$data ['cgst'] = $cgst;
		$data ['sgst'] = $sgst;
		$data ['cess'] = $cess;
		$data ['igst'] = $igst;
		if($itemdetail){
			$data ['item_detail_id'] = $itemdetail->id;
		}
		if($item){
			$data ['item_id'] = $item->id;
			$data ['item_title'] = $item->title;
		}
		$data ['mrp'] = $mrp;
		$data ['max_qty'] = $max_qty;
		$data ['sale_rate'] = $sale_rate;
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
	
	public function actionAjaxItems() {
		$bar_code = '';
		$alreadypermissions = [];
		if (isset ( $_POST ['item_id'] )) {
			
			$query = ItemDetail::find();
			$query->andWhere('status =' . UserRole::STATUS_ACTIVE);
			$query->andWhere('item_id =' . $_POST ['item_id']);
			$query->orderBy(['id' => SORT_DESC]);
			$itemdetail = $query->one();
			if ($itemdetail) {
				$bar_code = $itemdetail->bar_code;
			}
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
	//	echo $option;
		echo $bar_code;
	}
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionAjaxTax() {
		if (isset ( $_POST ['tax_id'] )) {
			$model = $this->loadModel($_POST ['tax_id'], Tax::class);
			
			$data ['cgst'] = $model->tax_val1;
			$data ['sgst'] = $model->tax_val2;
			$data ['cess'] = $model->tax_val3;
			$data ['igst'] = $model->tax_val4;
			echo json_encode ( $data );
		}
	}
	public function actionApplyCreditNote($id) {
		$bill = PurchaseBill::findOne( $id );
		$amount = '0.00';
		$message = '';
		$noteid = '';
		if ($bill) {
			if (isset ( $_POST ['credit_note'] ) && isset ( $_POST ['bill_amount'] )) {
				$query = CreditNote::find();
        $query->orderBy(['id' => SORT_DESC]);
				Criteria::compare($query, 'credit_number', $_POST ['credit_note']);
				$model = $query->one();
				if ($model) {
				
						$noteid = $model->id;
						$balance = $model->amt - $model->amt_used;
						if ($balance != '0.00') {
							if ($_POST ['bill_amount'] <= ($model->amt - $model->amt_used)) {
								$amount = $_POST ['bill_amount'];
							} else {
								$amount = $model->amt - $model->amt_used;
							}
						} else {
							$message = 'Credit note is already used';
						}

				} else {
					$message = 'Credit note not found';
				}
			}
		}
		$data ['amount'] = $amount;
		$data ['id'] = $noteid;
		$data ['message'] = $message;
		echo json_encode ( $data );
	}
	public function actionAjaxTaxTable() {
		if (isset ( $_POST ['tax_id'] ) && isset ( $_POST ['id'] ) && isset ( $_POST ['purchase_ids'] )) {
			
			$purchase_bill_ids = $_POST ['purchase_ids'];
			$model = $this->loadModel($_POST ['id']);
			if ($model) {
				$purchaseBill = $this->loadModel($model->purchase_bill_id, PurchaseBill::class);
			}
			
			
			return $this->renderPartial( '_tax', [
					'poid' => $purchaseBill->id,
					'detailid' => $_POST ['id'],
					'tax_id' => $_POST ['tax_id'],
					'purchase_bill_ids' => $purchase_bill_ids 
			]
			 );
		}
	}
	public function actionView($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		return $this->render( 'view', [
				'model' => $model 
		] );
	}
	public function actionAjaxBillNo($id) {
		$bill = PurchaseBill::findOne( $id );
		if ($bill && isset ( $_POST ['bill_no'] )) {
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
			$query = PurchaseBill::find();
        $query->orderBy(['id' => SORT_DESC]);
			// $criteria->addCondition ( 'vendor_id =' . $bill->vendor_id );
			if($start_date != '' && $end_date != ''){
				$query->andWhere(['between', 'date(create_time)', $start_date, $end_date]);
			}
			$query->andWhere('id !=' . $id);
			Criteria::compare($query, 'bill_no', $_POST ['bill_no']);
			$bill = $query->all();
			if (! $bill) {
				echo 'Success';
			} else {
				echo 'Fail';
			}
		} else {
			echo 'Fail';
		}
	}
	public function actionAjaxPONo() {
		$option = '';
		$alreadypermissions = [];
		$user = Yii::$app->user->model;
		if ($user) {
			$role_id = $user->role_id;
			if ($role_id == 6) {
				$vendor = Vendor::findOne( [
						'create_user_id' => $user->id 
				] );
				if ($vendor) {
					$_POST ['vendor_id'] = $vendor->id;
				}
			}
		}
		if (isset ( $_POST ['vendor_id'] )) {
			
			$query = PurchaseBill::find();
        $query->orderBy(['id' => SORT_DESC]);
			$query->andWhere('vendor_id =' . $_POST ['vendor_id']);
			$query->andWhere('status !=' . PurchaseBill::STATUS_APPROVED);
			$mrslist = $query->all();
			
			$option .= '<select class="form-control"  id="PurchaseBillDetail_purchase_bill_id" name="PurchaseBillDetail[purchase_bill_id]"><option value="" id="ckbCheckAll">-Select-</option>';
			if ($mrslist) {
				foreach ( $mrslist as $mrs ) {
					
					$option .= '<option value="' . $mrs->id . '">' . $mrs->id . '</option>';
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
	public function actionCreate() {
		$model = new PurchaseBillDetail ();
		
		$this->performAjaxValidation( $model, 'purchase-bill-detail-form' );
		
		if (isset ( $_POST ['PurchaseBillDetail'] )) {
			$model->load($_POST, 'PurchaseBillDetail');
			
			if ($model->save ()) {
				if (Yii::$app->request->isAjax)
					Yii::$app->end();
				else
					return $this->redirect( [
							'view',
							'id' => $model->id 
					] );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'create', [
				'model' => $model 
		] );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation( $model, 'purchase-bill-detail-form' );
		
		if (isset ( $_POST ['PurchaseBillDetail'] )) {
			$model->load($_POST, 'PurchaseBillDetail');
			
			if ($model->save ()) {
				return $this->redirect( [
						'view',
						'id' => $model->id 
				] );
			}
		}
		$this->updateMenuItems ( $model );
		return $this->render( 'update', [
				'model' => $model 
		] );
	}
	public function actionDelete($id) {
		$model = $this->loadModel($id);
		
		// if( !($this->isAllowed ( $model))) throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		if (Yii::$app->request->isPost) {
			$this->loadModel($id)->delete ();
			
			if (! Yii::$app->request->isAjax)
				return $this->redirect( [
						'admin' 
				] );
		} else
			throw new BadRequestHttpException(Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	
	public function actionSearch() {
		$model = new PurchaseBillDetail(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['PurchaseBillDetail'] )) {
			$model->load($_GET, 'PurchaseBillDetail');
			
			return $this->renderPartial( '_list', [
					'dataProvider' => $model->search (),
					'model' => $model 
			] );
		}
		
		return $this->renderPartial( '_search', [
				'model' => $model 
		] );
	}
	public function actionList($id = null, $poid = null) {
		$model = new PurchaseBill(['scenario' => 'search']);
		
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['PurchaseBill'] ['columns'] )) {
			$columns = $_POST ['PurchaseBill'] ['columns'];
		}
		$_GET ['PurchaseBill']['status'] = PurchaseBill::STATUS_APPROVED;
		if (isset ( $_GET ['PurchaseBill'] ))
			$model->load($_GET, 'PurchaseBill');
		
		return $this->render( 'list', [
				'model' => $model 
		] );
	}
	public function actionReport($id = null, $poid = null) {
		$model = new PurchaseBillDetail(['scenario' => 'search']);
		
		$this->updateMenuItems ( $model );
		$columns = [];
		Yii::warning( var_export($_POST, true), '$_POST');
		if (isset ( $_POST ['PurchaseBillDetail'] ['tally_start_date'] ) && ($_POST ['PurchaseBillDetail'] ['tally_start_date'] != '') && (isset ( $_POST ['PurchaseBillDetail'] ['tally_end_date'] )) && ($_POST ['PurchaseBillDetail'] ['tally_end_date'] != '')) {
			$_GET ['PurchaseBillDetail'] ['tally_start_date'] = $_POST ['PurchaseBillDetail'] ['tally_start_date'];
			$_GET ['PurchaseBillDetail'] ['tally_end_date'] = $_POST ['PurchaseBillDetail'] ['tally_end_date'];
			Yii::$app->session ['tally_start_date'] = $_POST ['PurchaseBillDetail'] ['tally_start_date'];
			Yii::$app->session ['tally_end_date'] = $_POST ['PurchaseBillDetail'] ['tally_end_date'];
		}
		if (isset ( $_POST ['PurchaseBillDetail'] ['columns'] )) {
			$columns = $_POST ['PurchaseBillDetail'] ['columns'];
		}
		if (isset ( $_GET ['PurchaseBillDetail'] ))
			$model->load($_GET, 'PurchaseBillDetail');
		$columns = $model->getColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->reportsearch (), $columns );
		}
		
		return $this->render( 'report', [
				'model' => $model 
		] );
	}
	public function actionAjaxupdate($id) {
		// $act = $_GET['act'];
		$poIdAll = $_POST;
		foreach ( $poIdAll as $qty ) {
			if (isset ( $poIdAll ['qty'] ))
				$qtys = $poIdAll ['qty'];
		}
		if (count ( $qtys ) > 0) {
			
			
			$purchasebill = PurchaseBill::findOne(['id'=>$id]);
			if($purchasebill){
				$oldstatus = $purchasebill->status;
			$vendor = Vendor::findOne( $purchasebill->vendor_id );
		
			if (isset ( $_POST ['credit_note_id'] ))
				$purchasebill->credit_note_id = $_POST ['credit_note_id'];
			if (isset ( $_POST ['credit_note_disc'] ))
				$purchasebill->credit_note_disc = $_POST ['credit_note_disc'];
			if (isset ( $_POST ['gross_amt'] ))
				$purchasebill->gross_amt = $_POST ['gross_amt'];
			if (isset ( $_POST ['total_discount'] ))
				$purchasebill->total_discount = $_POST ['total_discount'];
			if (isset ( $_POST ['tax_amount'] ))
				$purchasebill->tax_amount = $_POST ['tax_amount'];
			if (isset ( $_POST ['bill_amount'] ))
				$purchasebill->bill_amount = $_POST ['bill_amount'];
			if (isset ( $_POST ['bill_other_discount'] ))
				$purchasebill->bill_other_discount = $_POST ['bill_other_discount'];
			if (isset ( $_POST ['net_bill_amount'] ))
				$purchasebill->net_bill_amount = $_POST ['net_bill_amount'];
			if (isset ( $_POST ['bill_no'] ))
				$purchasebill->bill_no = $_POST ['bill_no'];
			if (isset ( $_POST ['bill_date'] ))
				$purchasebill->end_date = $_POST ['bill_date'];
			
			$purchasebill->start_date = date ( 'Y-m-d' );
			$purchasebill->payment_done = PurchaseBill::PAYMENT_PENDING;
			if (isset ( $_POST ['status'] ))
			$purchasebill->status = $_POST ['status'];
			if ($vendor) {
				$purchasebill->payment_days = $vendor->payment_days;
			}
			if (isset ( $_POST ['is_consignment'] ))
				$purchasebill->is_consignment = $_POST ['is_consignment'];
				if (isset ( $_POST ['status'] )){
			$advancepay = $purchasebill->getAdvancePaymentValue ();
				}else{
					$advancepay = true;
				}
				Yii::warning( var_export($advancepay, true), '$$advancepay');
				$set = true;
				$transaction = Yii::$app->db->beginTransaction ();
				try {
			
				if ($purchasebill->save ()) {
					
					if (isset ( $_POST ['status'] ) && ($oldstatus != PurchaseBill::STATUS_APPROVED)){

					$msg = 'PurchaseBill is updated';
					
					$vendor = Vendor::findOne( $purchasebill->vendor_id );
					if ($vendor) {
						$to_id = $vendor->create_user_id;
					} else {
						$to_id = $purchasebill->vendor_id;
					}
					$type = Notification::TYPE_PBILL;
					$model_id = $purchasebill->id;
					Notification::AddNotification ( $model_id, $msg, $type, $to_id );
					}
					if ($oldstatus != PurchaseBill::STATUS_APPROVED){
					foreach ( $qtys as $key => $qty ) {
						
						$model = $this->loadModel($key);
						$itemdetail = ItemDetail::findOne( $model->item_detail_id );
						$item = Item::findOne( $model->item_id );
						if($poIdAll ['qty'] [$key] == 0){
							if (isset ( $_POST ['status'] ) && ($_POST ['status'] == PurchaseBill::STATUS_APPROVED)){
							$billstock = ItemStock::findOne( [
									'item_detail_id' => $itemdetail->id,
									'item_id' => $itemdetail->item_id,
									'vendor_id' => $purchasebill->vendor_id
							] );
							 if($billstock){
							 	$billstock->outlet_id = $itemdetail->outlet_id;
							 	$billstock->vendor_id = $purchasebill->vendor_id;
							 	$billstock->mrp = $item->mrp;
							 	$billstock->base_price = $item->sale_price;
							 	$billstock->item_id = $item->id;
							 	$billstock->item_detail_id = $itemdetail->id;
							 	if ($billstock->save ()) {
								$billstock->createMrs();
							 	}else{
							 		Yii::warning( var_export($billstock->getErrors(), true), 'error1');
							 	}
								Yii::warning( var_export($billstock, true), '$billstock');
							}else{ 
							    if($billstock == null){
							    	$billstock = new ItemStock ();
							    	$batch_no = User::randomBarcode ( '5' );
							    	$billstock->batch_number = $batch_no;
							    	$billstock->balance_qty = 0;
							    	$billstock->purchase_qty = 0;
							    	$billstock->outlet_id = $itemdetail->outlet_id;
							    	$billstock->vendor_id = $purchasebill->vendor_id;
							    	$billstock->mrp = $item->mrp;
							    	$billstock->base_price = $item->sale_price;
							    	$billstock->item_id = $item->id;
							    	$billstock->item_detail_id = $itemdetail->id;
							    	if ($billstock->save ()) {
							    		$billstock->createMrs();
							    		Yii::warning( var_export($billstock, true), '$billstock1');
							    	}else{
							    		Yii::warning( var_export($billstock->getErrors(), true), 'error2');
							    	}
							    }
								
							} 
							}
						}
						$model->mrp = $_POST ['mrp'] [$key];
						
						if (isset ( $poIdAll ['mrp'] )) {
							$model->mrp = $poIdAll ['mrp'] [$key];
						}
						if (isset ( $poIdAll ['price'] )) {
							$model->price = $poIdAll ['price'] [$key];
						}
						if (isset ( $poIdAll ['salerate'] )) {
							$model->sale_rate = $poIdAll ['salerate'] [$key];
						}
						if (isset ( $poIdAll ['discount'] )) {
							$model->discount = $poIdAll ['discount'] [$key];
						}
						if (isset ( $poIdAll ['discount_amt'] )) {
							$model->discount_amt = $poIdAll ['discount_amt'] [$key];
						}
						if (isset ( $poIdAll ['discount1'] )) {
							$model->discount1 = $poIdAll ['discount1'] [$key];
						}
						if (isset ( $poIdAll ['discount_amt1'] )) {
							$model->discount_amt1 = $poIdAll ['discount_amt1'] [$key];
						}
						if (isset ( $poIdAll ['taxselectData'] )) {
							$query = ItemTax::find();
							$query->andWhere('item_detail_id =' . $itemdetail->id);
							$itemtax = $query->one();
							if ($itemtax) {
								$itemtax->tax_id = $poIdAll ['taxselectData'] [$key];
								$itemtax->saveAttributes ( [
										'tax_id' 
								] );
								$itemdetail->tax_id = $poIdAll ['taxselectData'] [$key];
								$itemdetail->saveAttributes ( [
									'tax_id'
							] );
							}
							$model->tax_id = $poIdAll ['taxselectData'] [$key];
						}
						// sale_tax_id
						if (isset ( $poIdAll ['saletaxselectData'] ) && $poIdAll ['saletaxselectData'] [$key]) {
							$query_2 = ItemTax::find();
							$query_2->andWhere('item_detail_id =' . $itemdetail->id);
							$itemtax = $query_2->one();
							if ($itemtax) {
								$itemtax->tax_id = $poIdAll ['saletaxselectData'] [$key];
								$itemtax->saveAttributes ( [
										'tax_id' 
								] );
								$itemdetail->tax_id = $poIdAll ['saletaxselectData'] [$key];
								$itemdetail->saveAttributes ( [
									'tax_id'
							] );
							}
							$model->sale_tax_id = $poIdAll ['saletaxselectData'] [$key];
						}
						if (isset ( $poIdAll ['cgstData'] )) {
							$model->cgst_per = $poIdAll ['cgstData'] [$key];
						}
						if (isset ( $poIdAll ['sgstData'] )) {
							$model->sgst_per = $poIdAll ['sgstData'] [$key];
						}
						if (isset ( $poIdAll ['cessData'] )) {
							$model->cess_per = $poIdAll ['cessData'] [$key];
						}
						if (isset ( $poIdAll ['cgstamtData'] )) {
							$model->cgst_amt = $poIdAll ['cgstamtData'] [$key];
						}
						if (isset ( $poIdAll ['sgstamtData'] )) {
							$model->sgst_amt = $poIdAll ['sgstamtData'] [$key];
						}
						if (isset ( $poIdAll ['cessamtData'] )) {
							$model->cess_amt = $poIdAll ['cessamtData'] [$key];
						}
						$tax_data = Tax::findOne($model->tax_id);
							if (isset ( $poIdAll ['igstData'] )) {
								if($tax_data){
									$model->igst_per = $tax_data->tax_val4;
									//$model->cess_per = $tax_data->tax_val3;
								}else{
							$model->igst_per = $poIdAll ['igstData'] [$key];
								}
						}
						if (isset ( $poIdAll ['igstamtData'] )) {
							if($model->igst_per  > 0){
							$model->igst_amt = $poIdAll ['igstamtData'] [$key];
							}else{
								$model->igst_amt = 0;
							}
						}

						// sale taxes
						if (isset ( $poIdAll ['salecgstData'] )) {
							$model->sale_cgst_per = $poIdAll ['salecgstData'] [$key];
						}
						if (isset ( $poIdAll ['salesgstData'] )) {
							$model->sale_sgst_per = $poIdAll ['salesgstData'] [$key];
						}
						if (isset ( $poIdAll ['salecessData'] )) {
							$model->sale_cess_per = $poIdAll ['salecessData'] [$key];
						}
						if (isset ( $poIdAll ['salecgstamtData'] )) {
							$model->sale_cgst_amt = $poIdAll ['salecgstamtData'] [$key];
						}
						if (isset ( $poIdAll ['salesgstamtData'] )) {
							$model->sale_sgst_amt = $poIdAll ['salesgstamtData'] [$key];
						}
						if (isset ( $poIdAll ['salecessamtData'] )) {
							$model->sale_cess_amt = $poIdAll ['salecessamtData'] [$key];
						}
						$tax_data = Tax::findOne($model->sale_tax_id);
							if (isset ( $poIdAll ['saleigstData'] )) {
								if($tax_data){
									$model->sale_igst_per = $tax_data->tax_val4;
									//$model->cess_per = $tax_data->tax_val3;
								}else{
							$model->sale_igst_per = $poIdAll ['saleigstData'] [$key];
								}
						}
						if (isset ( $poIdAll ['saleigstamtData'] )) {
							if($model->sale_igst_per  > 0){
							$model->sale_igst_amt = $poIdAll ['saleigstamtData'] [$key];
							}else{
								$model->sale_igst_amt = 0;
							}
						}
					
						if (isset ( $poIdAll ['other_charge'] )) {
							$model->other_charge = $poIdAll ['other_charge'] [$key];
						}
						if (isset ( $poIdAll ['amount'] )) {
							$model->amount = $poIdAll ['amount'] [$key];
						}
						if (isset ( $poIdAll ['marginData'] )) {
							$model->margin = $poIdAll ['marginData'] [$key];
						}
						if (isset ( $poIdAll ['hsncode'] )) {
							if($poIdAll ['hsncode'] [$key] != ''){
							$model->hsn_code = $poIdAll ['hsncode'] [$key];
							}
						}
						if (isset ( $poIdAll ['qty'] )) {
							$model->approved_qty = $poIdAll ['qty'] [$key];
							$model->bal_qty = ($model->req_qty - $model->approved_qty);
						}
						$model->create_time = date('Y-m-d H:i:s');
						if ($model->save ()) {
							if (isset ( $_POST ['status'] ) && ($oldstatus != PurchaseBill::STATUS_APPROVED)){
							if ($item) {
								if ($item->mrp != $model->mrp) {
									$item->sale_price = $model->mrp;
									
								}
								$item->update_time = date('Y-m-d H:i:s');
								$item->mrp = $model->mrp;
								if($model->hsn_code != '' && $model->hsn_code != 0){
								$item->hsn_code = $model->hsn_code;
								}
								$item->purchase_price = $model->price;
								$item->save ();
								$itemdetail->mrp = $model->mrp;
								$itemdetail->tax_id = $model->tax_id;
								$itemdetail->update_time = date('Y-m-d H:i:s');
								
								$itemdetail->saveAttributes ( [
										'tax_id' ,'mrp','update_time'
								] );
							}
							
							
							$itemstock = ItemStock::findOne( [
									'item_detail_id' => $itemdetail->id,
									'item_id' => $itemdetail->item_id,
									'vendor_id' => $purchasebill->vendor_id 
							] );
							$qty = number_format($qty, 3, '.', '');
							if ($itemstock == null) {
								$batch_no = User::randomBarcode ( '5' );
								$itemstock = new ItemStock ();
								$purchase = $qty;
								$balance = $qty;
								$itemstock->batch_number = $batch_no;
							} else {
								$purchase = ($itemstock->purchase_qty) + $qty;
								$balance = ($itemstock->balance_qty) + $qty;
							}
							
							$itemstock->item_detail_id = $itemdetail->id;
							$itemstock->base_price = $model->price;
							$itemstock->mrp = $model->mrp;
							$itemstock->vendor_id = $purchasebill->vendor_id;
							$itemstock->outlet_id = $model->outlet_id;
							$itemstock->tax_id = $model->tax_id;
							$itemstock->item_id = $itemdetail->item_id;
							$itemstock->purchase_qty = $purchase;
							$itemstock->balance_qty = $balance;
							$itemstock->create_user_id = Yii::$app->user->id;
							if ($itemstock->save ()) {
								$remain = $item->getTotalRemainingQuantity();
								$min_qty = $item->min_qty;
								if($remain >$min_qty){
									$mrsdetails = MrsDetail::findAll(['item_id'=>$item->id,
											'status'=>Mrs::STATUS_PENDING
									]);
									Yii::warning( var_export($mrsdetails, true), '$mrsdetails');
									if($mrsdetails){
										foreach($mrsdetails as $mrsdetail){
											$mrs_id = $mrsdetail->mrs_id;
											$query1 = MrsDetail::find();
											
											Criteria::compare($query1, "mrs_id ", $mrsdetail->mrs_id);
												
											$mrsItems = $query1->count();
											$mrs = Mrs::findOne($mrs_id);
											if(($mrs) && ($mrsdetail) && ($item->id == $mrsdetail->item_id) && 
											($mrs->status != Mrs::STATUS_DONE)){
												$mrsdetail->delete();
											}
												
											if($mrsItems == 1){
												$mrs = Mrs::findOne($mrs_id);
												if(($mrs) && ($item->id == $mrsdetail->item_id) && ($mrs->status != Mrs::STATUS_DONE))
												{
														
													$mrn = Mrn::findOne(['mrs_id'=>$mrs->id]);
													if(!$mrn){
														$mrs->delete();
													}
												}
											}
										}
									}
								} else {
									$itemstock->createMrs();
								}
								
								$itemId = (int)$itemdetail->item_id;

								// Lock stock rows
								$rows = Yii::$app->db->createCommand("
										SELECT balance_qty
										FROM tbl_item_stock
										WHERE item_id = :item_id
										AND item_detail_id IS NOT NULL
										ORDER BY id ASC
										FOR UPDATE
								")->queryAll(false, [':item_id' => $itemId]);

								// Calculate from locked rows
								$currentQty = $itemdetail->calculateLockedStockQty($rows);

								$stocklog = new StockLog ();
								$stocklog->item_detail_id = $itemstock->item_detail_id;
								$stocklog->item_id = $itemstock->item_id;
								$stocklog->batch_no = $itemstock->batch_number;
								if($itemdetail){
									$stocklog->current_qty = $currentQty;
									$stocklog->previous_qty = $currentQty-$qty;
									
								}
								$stocklog->Qty = $qty;
								$stocklog->outlet_id = $itemstock->outlet_id;
								$stocklog->vendor_id = $itemstock->vendor_id;
								$stocklog->type_id = StockLog::TYPE_ADDED;
								if($stocklog->save ()){
								}else{
									$set = false;
									Yii::warning( var_export($stocklog->getErrors(), true), 'error5');
								}
							}else{
								$set = false;
								Yii::warning( var_export($itemstock->getErrors(), true), 'error4');
							}
							}
						} 

						else {
							Yii::warning( var_export($model->getErrors(), true), 'error3');
							$set = false;
							throw new \Exception ( "Something went wrong", 500 );
						}
					}
					}
					echo 'Success';
				}else{
					$set = false;
					echo 'Failed';
				}
			
			if ($set == true) {
				$transaction->commit ();
					
			} else {
				$transaction->rollback ();
					
			}
			} catch ( \Exception $e ) {
				$transaction->rollback ();
			}
			}
		}
	}
	public function actionIndex($id = null, $poid = null,$vid=null) {
		$outlet_id = null;
		$vendor_id = null;
		$start_date = null;
		
		$role = UserRole::findOne( [
				'title' => 'Vendor' 
		] );
		$loggedinuser = Yii::$app->user->model;
		if ($loggedinuser->role_id == $role->id) {
			$user = Vendor::findOne( [
					'create_user_id' => $loggedinuser->id 
			] );
		} else {
			$user = User::findOne( [
					'id' => $loggedinuser->id 
			] );
		}
	
		
		$model = new PurchaseBillDetail(['scenario' => 'search']);
		if (! ($model->checkPermission ( 'purchaseBillDetail/index' )))
			throw new ForbiddenHttpException(Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		if ($poid == null) {
			$poids = $model->getAllPOBillOptions ( $user->id );
			if (isset ( $poids ['0'] )) {
				$poid = $poids ['0'];
			}
		}
		
		$_GET ['poid'] = $poid;
		$this->updateMenuItems ( $model );
		if ($poid != null) {
			$purchasebill = PurchaseBill::findOne( $poid );
			if ($purchasebill) {
				$outlet_id = $purchasebill->outlet_id;
				$vendor_id = $purchasebill->vendor_id;
				$start_date = $purchasebill->start_date;
			}
		}
		
		if (isset ( $_POST ['PurchaseBillDetail'] )) {
			if (isset ( $_POST ['PurchaseBillDetail'] ['outlet_id'] )) {
				$_GET ['PurchaseBillDetail'] ['outlet_id'] = $_POST ['PurchaseBillDetail'] ['outlet_id'];
			}
			if (isset ( $_POST ['PurchaseBillDetail'] ['vendor_id'] ) && ($_POST ['PurchaseBillDetail'] ['vendor_id'] != '')) {
				$_GET ['PurchaseBillDetail'] ['vendor_id'] = $_POST ['PurchaseBillDetail'] ['vendor_id'];
			}
			if (isset ( $_POST ['PurchaseBillDetail'] ['purchase_bill_id'] ) && ($_POST ['PurchaseBillDetail'] ['purchase_bill_id'] != '')) {
				$_GET ['PurchaseBillDetail'] ['purchase_bill_id'] = $_POST ['PurchaseBillDetail'] ['purchase_bill_id'];
				$poid = $_GET ['PurchaseBillDetail'] ['purchase_bill_id'];
				if ($poid != null) {
					$purchasebill = PurchaseBill::findOne( $poid );
					if ($purchasebill) {
						$outlet_id = $purchasebill->outlet_id;
						$vendor_id = $purchasebill->vendor_id;
						$start_date = $purchasebill->start_date;
					}
				}
			} else {
				$outlet_id = null;
				$vendor_id = null;
				$start_date = null;
			}
			if (isset ( $_POST ['PurchaseBillDetail'] ['start_date'] ) && ($_POST ['PurchaseBillDetail'] ['start_date'] != '')) {
				if (isset ( $_POST ['PurchaseBillDetail'] ['purchase_bill_id'] ) && ($_POST ['PurchaseBillDetail'] ['purchase_bill_id'] != '')) {
					$purchasebill = PurchaseBill::findOne( $poid );
					if ($purchasebill) {
						$start_date = $purchasebill->start_date;
					}
					$_GET ['PurchaseBillDetail'] ['start_date'] = date ( 'Y-m-d', strtotime ( $start_date ) );
				} else {
					$_GET ['PurchaseBillDetail'] ['start_date'] = date ( 'Y-m-d', strtotime ( $_POST ['PurchaseBillDetail'] ['start_date'] ) );
				}
			}
		} else {
			if ($poid == null)
				$poid = 0;
			$_GET ['PurchaseBillDetail'] ['purchase_bill_id'] = $poid;
		}
		
		if (isset ( $_GET ['PurchaseBillDetail'] ))
			$model->load($_GET, 'PurchaseBillDetail');
		if($vid == null){
			$vid = $vendor_id;
		}
		
		return $this->render( 'index', [
				'model' => $model,
				'user' => $user,
				'poid' => $poid,
				'outlet_id' => $outlet_id,
				'vendor_id' => $vendor_id,'vid'=>$vid,
				'start_date' => $start_date 
		] );
	}
	public function actionAdmin($id = null, $poid = null) {
	
		$role = UserRole::findOne( [
				'title' => 'Vendor' 
		] );
		$loggedinuser = Yii::$app->user->model;
		if ($loggedinuser->role_id != $role->id) {
			$user = Vendor::findOne( [
					'create_user_id' => $loggedinuser->id 
			] );
		} else {
			$user = User::findOne( [
					'id' => $loggedinuser->id 
			] );
		}
		
		$model = new PurchaseBillDetail(['scenario' => 'search']);
		
		if ($poid == null) {
			$poids = $model->getAllPOBillOptions ( $user->id );
			if (isset ( $poids ['0'] ))
				$poid = $poids ['0'];
		}
		
		$_GET ['poid'] = $poid;
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['PurchaseBillDetail'] )) {
			if (isset ( $_POST ['PurchaseBillDetail'] ['outlet_id'] )) {
				$_GET ['PurchaseBillDetail'] ['outlet_id'] = $_POST ['PurchaseBillDetail'] ['outlet_id'];
			}
			if (isset ( $_POST ['PurchaseBillDetail'] ['vendor_id'] ) && ($_POST ['PurchaseBillDetail'] ['vendor_id'] != '')) {
				$_GET ['PurchaseBillDetail'] ['vendor_id'] = $_POST ['PurchaseBillDetail'] ['vendor_id'];
			}
			if (isset ( $_POST ['PurchaseBillDetail'] ['purchase_bill_id'] ) && ($_POST ['PurchaseBillDetail'] ['purchase_bill_id'] != '')) {
				$_GET ['PurchaseBillDetail'] ['purchase_bill_id'] = $_POST ['PurchaseBillDetail'] ['purchase_bill_id'];
			}
			if (isset ( $_POST ['PurchaseBillDetail'] ['start_date'] ) && ($_POST ['PurchaseBillDetail'] ['start_date'] != '')) {
				$_GET ['PurchaseBillDetail'] ['start_date'] = date ( 'Y-m-d', strtotime ( $_POST ['PurchaseBillDetail'] ['start_date'] ) );
			}
		} else {
			if ($poid == null)
				$poid = 0;
			$_GET ['PurchaseBillDetail'] ['purchase_bill_id'] = $poid;
		}
		if (isset ( $_GET ['PurchaseBillDetail'] ))
			$model->load($_GET, 'PurchaseBillDetail');
		
		return $this->render( 'admin', [
				'model' => $model,
				'user' => $user,
				'poid' => $poid 
		] );
	}
	
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new PurchaseBillDetail ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ),
							'url' => Ui::to('purchaseBillDetail/view', ['id' => $model->id]),
							'icon' => 'icon-plus icon-white' 
					];
				}
			case 'create' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'List' ),
							'url' => [
									'index' 
							],
							'icon' => 'icon-th-list icon-white' 
					];
				}
				break;
			case 'index' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			case 'admin' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'List' ),
							'url' => [
									'index' 
							],
							'icon' => 'icon-th-list icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
				}
				break;
			default :
			case 'view' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'List' ),
							'url' => [
									'index' 
							],
							'icon' => 'icon-th-list icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Delete' ),
							'url' => '#',
							'linkOptions' => [
									'submit' => [
											'delete',
											'id' => $model->id 
									],
									'confirm' => 'Are you sure you want to delete this item?' 
							],
							'icon' => 'icon-remove icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => [
									'create' 
							],
							'icon' => 'icon-plus icon-white' 
					];
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => Ui::to('purchaseBillDetail/update', ['id' => $model->id]),
							'icon' => 'icon-edit icon-white' 
					];
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
}