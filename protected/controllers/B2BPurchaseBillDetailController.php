<?php
class B2BPurchaseBillDetailController extends GxController {
	public function filters() {
		return array (
				'accessControl' 
		);
	}
	public function accessRules() {
		return array (
				array (
						'allow',
						'actions' => array (
								//'index',
								//'view',
								/* 'download', 'thumbnail' */),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'index',
								'view',
								'create',
								'update',
								'search',
								'admin',
								'delete',
								'report',
								'ajaxupdate',
								'ajaxPONo',
								'list',
								'ajaxBillNo',
								'ajaxTax',
								'ajaxTaxTable',
								'applyCreditNote',
								'ajaxItems',
								'ajaxPBillTax',
								'ajaxCreate',
								'taxwise',
								'getpendingbill',
								'getpendingbilldate',
								'updateMRP'
						),
						'users' => array (
								'@' 
						) 
				),
				/*array('allow', 
					'actions'=>array('admin','delete'),
					'expression'=>'Yii::app()->user->isAdmin',
					),*/
				array (
						'deny',
						'users' => array (
								'*' 
						) 
				) 
		);
	}
	
	public function actionUpdateMRP($id){
		$purchasebill = $this->loadModel ( $id, 'B2bPurchaseBill' );
		if($purchasebill){
			$purchaseBillDetails = B2bPurchaseBillDetail::model()->findAllByAttributes(array('purchase_bill_id'=>$purchasebill->id));
			if($purchaseBillDetails){
				foreach($purchaseBillDetails as $purchaseBillDetail){
					$itemDetail = ItemDetail::model()->findByPk($purchaseBillDetail->item_detail_id);
					if($itemDetail){
						$itemDetail->mrp = $purchaseBillDetail->mrp;
						$itemDetail->saveAttributes(array('mrp'));
					}
				}
			}
		}
	}
	public function actionAjaxCreate($id = null)
	{
// echo"<pre>"; print_t($_POST); die;

		// $existpo = $this->loadModel($id, 'PurchaseBill');
		$model = new B2bPurchaseBillDetail;
		//$this->performAjaxValidation($model, 'purchase-order-detail-form');
	
		if (isset($_POST['B2bPurchaseBillDetail'])) {
			// echo"<pre>"; print_t($_POST['B2bPurchaseBillDetail']); die;
			$model->setAttributes($_POST['B2bPurchaseBillDetail']);
			$model->outlet_id = '5';
			if($model->approved_qty != '')
				 $billmodel = B2bPurchaseBill::model()->findByAttributes(array(
                        'vendor_id' => $_POST['vendor'],
                        'start_date' =>  $_POST['date'],
						  'status' =>  '0'
                    ));
                    $updated = true;
                    if ($billmodel == null) {
                        $billmodel = new B2bPurchaseBill();
                        $updated = false;
                    }
                    $billmodel->start_date = $_POST['date'];
                    $billmodel->code = "code";
                    $billmodel->outlet_id = $id;
                    $billmodel->vendor_id = $_POST['vendor'];
                    $billmodel->purchase_order_id = rand(10,10000);
                    $billmodel->organization_id = '4';
                   $billmodel->save();
				   
				$model->bal_qty = ($model->req_qty - $model->approved_qty);
				$model->purchase_bill_id = $billmodel->id;
				
				if ($model->save()) {
				
					echo 'Data is saved successfully';
				}else{
					echo"no";
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
		$alreadypermissions = array ();
		$tax = null;
		$item = null;
		$attr = "";
		$msg = 'Inactive';
		if (isset ( $_POST ['item_detail_id'] )) {
			$itemdetail = ItemDetail::model ()->findByAttributes( array('bar_code'=>$_POST ['item_detail_id'],
					'status'=>ItemDetail::STATUS_ACTIVE
			));
			if($itemdetail){
					
				$item = Item::model ()->findByPk ( $itemdetail->item_id);
					
				$criteria = new CDbCriteria();
				$criteria->order = 'id desc';
				$criteria->addCondition('item_detail_id ='.$item->id);
				$vendor =  ItemVendor::model()->find($criteria);
				// if($vendor->vendor_id == $_POST ['vendor_id'] ){
					// $msg = 'success';
				// }else{
					// $msg = 'failed';
				// }
				$msg = 'success';
				if($itemdetail){
					$tax = Tax::model ()->findByPk($itemdetail->tax_id);
				}else{
					$itemTax = ItemTax::model ()->findByAttributes ( array (
							'item_detail_id' => $_POST ['item_detail_id']
					) );
					if ($itemTax) {
						$tax = Tax::model ()->findByPk ( $itemTax->tax_id );
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
					
				$taxes = Tax::model ()->findAll ();
				$option .= '<select class="form-control" id="B2bPurchaseBillDetail_item_detaill_list_id" name="B2bPurchaseBillDetail[tax_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'status =' . UserRole::STATUS_ACTIVE );
			$criteria->compare('item_id', (int) $_POST['item_id']);
			$criteria->order = 'id desc';
			$itemdetail = ItemDetail::model ()->find( $criteria );
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
			$model = $this->loadModel ( $_POST ['tax_id'], 'Tax' );
			
			$data ['cgst'] = $model->tax_val1;
			$data ['sgst'] = $model->tax_val2;
			$data ['cess'] = $model->tax_val3;
			$data ['igst'] = $model->tax_val4;
			echo json_encode ( $data );
		}
	}
	public function actionApplyCreditNote($id) {
		$bill = PurchaseBill::model ()->findByPk ( $id );
		$amount = '0.00';
		$message = '';
		$noteid = '';
		if ($bill) {
			if (isset ( $_POST ['credit_note'] ) && isset ( $_POST ['bill_amount'] )) {
				$criteria = new CDbCriteria ();
				$criteria->compare ( 'credit_number', $_POST ['credit_note'] );
				$model = CreditNote::model ()->find ( $criteria );
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
			$model = $this->loadModel ( $_POST ['id'], 'B2bPurchaseBillDetail' );
			if ($model) {
				$purchaseBill = $this->loadModel ( $model->purchase_bill_id, 'B2bPurchaseBill' );
			}
			
			
			$this->renderPartial ( '_tax', array (
					'poid' => $purchaseBill->id,
					'detailid' => $_POST ['id'],
					'tax_id' => $_POST ['tax_id'],
					'purchase_bill_ids' => $purchase_bill_ids 
			)
			 );
		}
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'B2bPurchaseBillDetail' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionAjaxBillNo($id) {
		
		$criteria1 = new CDbCriteria ();
		$criteria1->addCondition  ('id ='. $id );
		// $criteria1->addCondition  ('start_date ='. $_POST ['date'] );
		$criteria1->addCondition ( 'status !=' . B2bPurchaseBill::STATUS_APPROVED );
		$bill = B2bPurchaseBill::model ()->find ( $criteria1 );
	
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
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'vendor_id =' . $bill->vendor_id );
			if($start_date != '' && $end_date != ''){
				$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
			}
			$criteria->addCondition ( 'id !=' . $bill->id );
			$criteria->compare ( 'bill_no', $_POST ['bill_no'] );
			$bill = B2bPurchaseBill::model ()->findAll ( $criteria );
			
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
		$alreadypermissions = array ();
		$user = Yii::app ()->user->model;
		if ($user) {
			$role_id = $user->role_id;
			if ($role_id == 6) {
				$vendor = Vendor::model ()->findByAttributes ( array (
						'create_user_id' => $user->id 
				) );
				if ($vendor) {
					$_POST ['vendor_id'] = $vendor->id;
				}
			}
		}
		if (isset ( $_POST ['vendor_id'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->compare('vendor_id', (int) $_POST['vendor_id']);
			$criteria->addCondition ( 'status !=' . B2bPurchaseBill::STATUS_APPROVED );
			$mrslist = B2bPurchaseBill::model ()->findAll ( $criteria );
			
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
			$option .= '<input type="text" class="form-control"  id="" name="PurchaseBillDetail[purchase_bill_id]">';
		}
		echo $option;
	}
	public function actionCreate() {
		$model = new PurchaseBillDetail ();
		
		$this->performAjaxValidation ( $model, 'purchase-bill-detail-form' );
		
		if (isset ( $_POST ['B2bPurchaseBillDetail'] )) {
			$model->setAttributes ( $_POST ['B2bPurchaseBillDetail'] );
			
			if ($model->save ()) {
				if (Yii::app ()->getRequest ()->getIsAjaxRequest ())
					Yii::app ()->end ();
				else
					$this->redirect ( array (
							'view',
							'id' => $model->id 
					) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model 
		) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'B2bPurchaseBillDetail' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'purchase-bill-detail-form' );
		
		if (isset ( $_POST ['B2bPurchaseBillDetail'] )) {
			$model->setAttributes ( $_POST ['B2bPurchaseBillDetail'] );
			
			if ($model->save ()) {
				$this->redirect ( array (
						'view',
						'id' => $model->id 
				) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'update', array (
				'model' => $model 
		) );
	}
	public function actionDelete($id) {
		$model = $this->loadModel ( $id, 'B2bPurchaseBillDetail' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'B2bPurchaseBillDetail' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	
	public function actionSearch() {
		$model = new Job ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['B2bPurchaseBillDetail'] )) {
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
			
			$this->renderPartial ( '_list', array (
					'dataProvider' => $model->search (),
					'model' => $model 
			) );
		}
		
		$this->renderPartial ( '_search', array (
				'model' => $model 
		) );
	}
	public function actionList($id = null, $poid = null) {
		$model = new B2bPurchaseBill ( 'search' );
		$model->unsetAttributes ();
		
		$this->updateMenuItems ( $model );
		$columns = array ();
		if (isset ( $_POST ['B2bPurchaseBill'] ['columns'] )) {
			$columns = $_POST ['B2bPurchaseBill'] ['columns'];
		}
		$_GET ['B2bPurchaseBill']['status'] = B2bPurchaseBill::STATUS_APPROVED;
		if (isset ( $_GET ['B2bPurchaseBill'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBill'] );
		
		$this->render ( 'list', array (
				'model' => $model 
		) );
	}
	public function actionReport($id = null, $poid = null) {
		$model = new B2bPurchaseBillDetail ( 'search' );
		$model->unsetAttributes ();
		
		$this->updateMenuItems ( $model );
		$columns = array ();
		Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, '$_POST' );
		if (isset ( $_POST ['B2bPurchaseBillDetail'] ['tally_start_date'] ) && ($_POST ['B2bPurchaseBillDetail'] ['tally_start_date'] != '') && (isset ( $_POST ['B2bPurchaseBillDetail'] ['tally_end_date'] )) && ($_POST ['B2bPurchaseBillDetail'] ['tally_end_date'] != '')) {
			$_GET ['B2bPurchaseBillDetail'] ['tally_start_date'] = $_POST ['B2bPurchaseBillDetail'] ['tally_start_date'];
			$_GET ['B2bPurchaseBillDetail'] ['tally_end_date'] = $_POST ['B2bPurchaseBillDetail'] ['tally_end_date'];
			Yii::app ()->session ['tally_start_date'] = $_POST ['B2bPurchaseBillDetail'] ['tally_start_date'];
			Yii::app ()->session ['tally_end_date'] = $_POST ['B2bPurchaseBillDetail'] ['tally_end_date'];
		}
		if (isset ( $_POST ['B2bPurchaseBillDetail'] ['columns'] )) {
			$columns = $_POST ['B2bPurchaseBillDetail'] ['columns'];
		}
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
		$columns = $model->getColumns ( $columns );
		if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV ( $model->reportsearch (), $columns );
		}
		
		$this->render ( 'report', array (
				'model' => $model 
		) );
	}
	public function actionAjaxupdate($id) {
		// $act = $_GET['act'];
		$poIdAll = $_POST;
		//echo"<pre>"; print_r($id); die;
		foreach ( $poIdAll as $qty ) {
			if (isset ( $poIdAll ['qty'] ))
				$qtys = $poIdAll ['qty'];
		}
		
	
	
		if (count ( $qtys ) > 0) {
			
			$purchasebill = B2bPurchaseBill::model()->findByAttributes(array('id'=>$id, 'status'=>'0'));
			if($purchasebill){

				if(!isset($purchasebill->grn_refrence_no) ){
					/** Start for grn code */
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
					
					$criteria = new CDbCriteria();
					$criteria->order = 'grn_refrence_no desc';
					if($start_date != '' && $end_date != ''){
						$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
					}
					//$criteria->addCondition('year(create_time) ='.$year);
					$latestbill = B2bPurchaseBill::model()->find($criteria);
					if($latestbill){
						$bill_no = $latestbill->grn_refrence_no + 1;
					}else{
						$bill_no = 1;
					}
					
					$purchasebill->grn_refrence_no = $bill_no;
				}
				/** end for grn code */
				
				$oldstatus = $purchasebill->status;
				$vendor = Vendor::model ()->findByPk ( $purchasebill->vendor_id );
		
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
			$purchasebill->payment_done = B2bPurchaseBill::PAYMENT_PENDING;
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
				Yii::log ( CVarDumper::dumpAsString ( $advancepay ), CLogger::LEVEL_WARNING, '$$advancepay' );
				$set = true;
				$transaction = Yii::app ()->db->beginTransaction ();
				try {
			
				if ($purchasebill->save ()) {
					
					if (isset ( $_POST ['status'] ) && ($oldstatus != B2bPurchaseBill::STATUS_APPROVED)){
					$msg = 'B2BPurchaseBill is updated';

					
					$vendor = Vendor::model ()->findByPk ( $purchasebill->vendor_id );
					if ($vendor) {
						$to_id = $vendor->create_user_id;
					} else {
						$to_id = $purchasebill->vendor_id;
					}
					$type = Notification::TYPE_PBILL;
					$model_id = $purchasebill->id;
					Notification::AddNotification ( $model_id, $msg, $type, $to_id );
					}
					if ($oldstatus != B2bPurchaseBill::STATUS_APPROVED){
					foreach ( $qtys as $key => $qty ) {
						$model = $this->loadModel ( $key, 'B2bPurchaseBillDetail' );
						$itemdetail = ItemDetail::model ()->findByPk ( $model->item_detail_id );
						$item = Item::model ()->findByPk ( $model->item_id );
						
						if($poIdAll ['qty'] [$key] == 0){
							
							
							if (isset ( $_POST ['status'] ) && ($_POST ['status'] == B2bPurchaseBill::STATUS_APPROVED)){
							$billstock = ItemStock::model ()->findByAttributes ( array (
									'item_detail_id' => $itemdetail->id,
									'item_id' => $itemdetail->item_id,
									'vendor_id' => $purchasebill->vendor_id
							) );
							
							
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
							 		Yii::log ( CVarDumper::dumpAsString ( $billstock->getErrors() ), CLogger::LEVEL_WARNING, 'error1' );
							 	}
								Yii::log ( CVarDumper::dumpAsString ( $billstock ), CLogger::LEVEL_WARNING, '$billstock' );
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
							    		Yii::log ( CVarDumper::dumpAsString ( $billstock ), CLogger::LEVEL_WARNING, '$billstock1' );
							    	}else{
							    		Yii::log ( CVarDumper::dumpAsString ( $billstock->getErrors() ), CLogger::LEVEL_WARNING, 'error2' );
							    	}
							    }
								
							} 
							}
						}
							// echo "<pre>"; print_r("without sss"); die;
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
							$criteria = new CDbCriteria ();
							$criteria->addCondition ( 'item_detail_id =' . $itemdetail->id );
							$itemtax = ItemTax::model ()->find ( $criteria );
							if ($itemtax) {
								$itemtax->tax_id = $poIdAll ['taxselectData'] [$key];
								$itemtax->saveAttributes ( array (
										'tax_id' 
								) );
							}
							$model->tax_id = $poIdAll ['taxselectData'] [$key];
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
						
					if (isset ( $poIdAll ['igstData'] )) {
							$model->igst_per = $poIdAll ['igstData'] [$key];
							// $model->cgst_per = 0;
							// $model->sgst_per = 0;
							if($poIdAll ['igstData'][$key] > 0.00){
								$model->cgst_per = 0.00;
								$model->sgst_per = 0.00;
							}
						}
						
						$tax_data = Tax::model()->findByPk($model->tax_id);
						
					
							// if (isset ( $poIdAll ['igstData'] )) {
								// if($tax_data){
									// $model->igst_per = $tax_data->tax_val4;
									// $model->cess_per = $tax_data->tax_val3;
								// }else{
							
							// $model->igst_per = $poIdAll ['igstData'] [$key];
								// }
						// }
						if (isset ( $poIdAll ['igstamtData'] )) {
							// $model->cgst_amt = 0;
							// $model->sgst_amt = 0;
							$model->igst_amt = $poIdAll ['igstamtData'] [$key];
							if($poIdAll ['igstamtData'][$key] > 0.00){
								$model->cgst_amt = 0.00;
								$model->sgst_amt = 0.00;
							}
							}else{
								$model->igst_amt = 0;
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
							if (isset ( $_POST ['status'] ) && ($oldstatus != B2bPurchaseBill::STATUS_APPROVED)){
							if ($item) {
								// if ($item->mrp != $model->mrp) {
									// $item->sale_price = $model->mrp;
									
								// }
								// $item->update_time = date('Y-m-d H:i:s');
								// $item->mrp = $model->mrp;
								// if($model->hsn_code != '' && $model->hsn_code != 0){
								// $item->hsn_code = $model->hsn_code;
								// }
								// $item->purchase_price = $model->price;
								// $item->save ();
								// $itemdetail->mrp = $model->mrp;
								// $itemdetail->tax_id = $model->tax_id;
								// $itemdetail->update_time = date('Y-m-d H:i:s');
								
								// $itemdetail->saveAttributes ( array (
										// 'tax_id' ,'mrp','update_time'
								// ) );
							}
							
							
							$itemstock = ItemStock::model ()->findByAttributes ( array (
									'item_detail_id' => $itemdetail->id,
									'item_id' => $itemdetail->item_id,
									'vendor_id' => $purchasebill->vendor_id ,
									'type' => 'B2B'
							) );
							
							
							$qty = number_format($qty, 3, '.', '');
							if ($itemstock == null) {
								$batch_no = User::randomBarcode ( '5' );
								$itemstock = new ItemStock ();
								$purchase = '-'.$qty;
								$balance =  '-'.$qty;
								$itemstock->batch_number = $batch_no;
								$itemstock->type = 'B2B';
							} else {
								$purchase = ($itemstock->purchase_qty) - $qty;
								$balance = ($itemstock->balance_qty) - $qty;
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
							$itemstock->create_user_id = Yii::app ()->user->id;
							if ($itemstock->save ()) {
									$itemstock->createB2bMrs();
								$remain = $item->getTotalRemainingQuantity();
								$min_qty = $item->min_qty;
								
								if($remain >$min_qty){
									$mrsdetails = MrsDetail::model()->findAllByAttributes(array('item_id'=>$item->id,
											'status'=>Mrs::STATUS_PENDING
									));
									Yii::log ( CVarDumper::dumpAsString ( $mrsdetails ), CLogger::LEVEL_WARNING, '$mrsdetails' );
									if($mrsdetails){
										foreach($mrsdetails as $mrsdetail){
											$mrs_id = $mrsdetail->mrs_id;
											$criteria1 = new CDbCriteria ();
											
											$criteria1->compare ( "mrs_id ", $mrsdetail->mrs_id );
												
											$mrsItems = MrsDetail::model ()->count ( $criteria1 );
											$mrs = Mrs::model()->findByPk($mrs_id);
											if(($mrs) && ($mrsdetail) && ($item->id == $mrsdetail->item_id) && 
											($mrs->status != Mrs::STATUS_DONE)){
												$mrsdetail->delete();
											}
												
											if($mrsItems == 1){
												$mrs = Mrs::model()->findByPk($mrs_id);
												if(($mrs) && ($item->id == $mrsdetail->item_id) && ($mrs->status != Mrs::STATUS_DONE))
												{
														
													$mrn = Mrn::model()->findByAttributes(array('mrs_id'=>$mrs->id));
													if(!$mrn){
														$mrs->delete();
													}
												}
											}
										}
									}
								}
								
								$stocklog = new StockLog ();
								$stocklog->item_detail_id = $itemstock->item_detail_id;
								$stocklog->item_id = $itemstock->item_id;
								$stocklog->batch_no = $itemstock->batch_number;
								if($itemdetail){
									$stocklog->current_qty = $itemdetail->getStockQty();
									$stocklog->previous_qty = ($itemdetail->getStockQty())+($qty);
									
								}
								$stocklog->Qty = $qty;
								$stocklog->outlet_id = $itemstock->outlet_id;
								$stocklog->vendor_id = $itemstock->vendor_id;
								$stocklog->type_id = StockLog::TYPE_B2B;
								if($stocklog->save ()){
									
								}else{
									$set = false;
									Yii::log ( CVarDumper::dumpAsString ( $stocklog->getErrors() ), CLogger::LEVEL_WARNING, 'error5' );
								}
							}else{
								$set = false;
								Yii::log ( CVarDumper::dumpAsString ( $itemstock->getErrors() ), CLogger::LEVEL_WARNING, 'error4' );
							}
							}
						} 

						else {
							Yii::log ( CVarDumper::dumpAsString ( $model->getErrors() ), CLogger::LEVEL_WARNING, 'error3' );
							$set = false;
							throw new Exception ( "Something went wrong", 500 );
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
			} catch ( Exception $e ) {
				
				
				echo $e; die;
				$transaction->rollback ();
			}
			}else{
				echo 'failed';
				
			}
		}
	}
	public function actionIndex($id = null, $poid = null,$vid=null) {
		$outlet_id = null;
		$vendor_id = null;
		$start_date = null;
	
		$role = UserRole::model ()->findByAttributes ( array (
				'title' => 'Vendor' 
		) );
		$loggedinuser = Yii::app ()->user->model;
		if ($loggedinuser->role_id == $role->id) {
			$user = Vendor::model ()->findByAttributes ( array (
					'create_user_id' => $loggedinuser->id 
			) );
		} else {
			$user = User::model ()->findByAttributes ( array (
					'id' => $loggedinuser->id 
			) );
		}
	
		
		$model = new B2bPurchaseBillDetail ( 'search' );
		$model->unsetAttributes ();
		if (! ($model->checkPermission ( 'purchaseBillDetail/index' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		$vendor='';
		if (isset ( $_POST ['B2bPurchaseBillDetail'] )) {
		if (isset ( $_POST ['B2bPurchaseBillDetail'] ['vendor_id'] ) && ($_POST ['B2bPurchaseBillDetail'] ['vendor_id'] != '')) {
				$vendor = $_POST ['B2bPurchaseBillDetail'] ['vendor_id'];
				
			}
			}
		
	
		if ($poid == null) {
			$poids = $model->getAllPOBillOptions ( $user->id , $vendor);
				// echo $poids; die;
			if (isset ( $poids ['0'] )) {
				$poid = $poids ['0'];
			}
		}
		
			if (isset ( $_POST ['B2bPurchaseBillDetail']['id'] )){
				
		$poid=$_POST['B2bPurchaseBillDetail']['id'];
			}
		$_GET ['poid'] = $poid;
		
		
		$this->updateMenuItems ( $model );
		if ($poid != null) {
			$purchasebill = B2bPurchaseBill::model ()->findByPk ( $poid );
			
			if ($purchasebill) {
				$outlet_id = $purchasebill->outlet_id;
				$vendor_id = $purchasebill->vendor_id;
				$start_date = $purchasebill->start_date;
			}
		}
		
			
		
		
		if (isset ( $_POST ['B2bPurchaseBillDetail'] )) {
			if (isset ( $_POST ['B2bPurchaseBillDetail'] ['outlet_id'] )) {
				$_GET ['B2bPurchaseBillDetail'] ['outlet_id'] = $_POST ['B2bPurchaseBillDetail'] ['outlet_id'];
			}
			if (isset ( $_POST ['B2bPurchaseBillDetail'] ['vendor_id'] ) && ($_POST ['B2bPurchaseBillDetail'] ['vendor_id'] != '')) {
				$_GET ['B2bPurchaseBillDetail'] ['vendor_id'] = $_POST ['B2bPurchaseBillDetail'] ['vendor_id'];
				
			}
			if (isset ( $_POST ['B2bPurchaseBillDetail'] ['purchase_bill_id'] ) && ($_POST ['B2bPurchaseBillDetail'] ['purchase_bill_id'] != '')) {
				$_GET ['B2bPurchaseBillDetail'] ['purchase_bill_id'] = $_POST ['B2bPurchaseBillDetail'] ['purchase_bill_id'];
				$poid = $_GET ['B2bPurchaseBillDetail'] ['purchase_bill_id'];
				if ($poid != null) {
					$purchasebill = B2bPurchaseBill::model ()->findByPk ( $poid );
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
			if (isset ( $_POST ['B2bPurchaseBillDetail'] ['start_date'] ) && ($_POST ['B2bPurchaseBillDetail'] ['start_date'] != '')) {
				if (isset ( $_POST ['B2bPurchaseBillDetail'] ['purchase_bill_id'] ) && ($_POST ['B2bPurchaseBillDetail'] ['purchase_bill_id'] != '')) {
					$purchasebill = B2bPurchaseBill::model ()->findByPk ( $poid );
					if ($purchasebill) {
						$start_date = $purchasebill->start_date;
					}
					$_GET ['B2bPurchaseBillDetail'] ['start_date'] = date ( 'Y-m-d', strtotime ( $start_date ) );
				} else {
					$_GET ['B2bPurchaseBillDetail'] ['start_date'] = date ( 'Y-m-d', strtotime ( $_POST ['B2bPurchaseBillDetail'] ['start_date'] ) );
				}
			}
		} else {
			if ($poid == null)
				$poid = 0;
			$_GET ['B2bPurchaseBillDetail'] ['purchase_bill_id'] = $poid;
		}
		
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
		if($vid == null){
			$vid = $vendor_id;
		}
		 // echo"<pre>"; print_r($poid); die;
		$this->render ( 'index', array (
				'model' => $model,
				'user' => $user,
				'poid' => $poid,
				'outlet_id' => $outlet_id,
				'vendor_id' => $vendor_id,'vid'=>$vid,
				'start_date' => $start_date 
		) );
	}
	public function actionAdmin($id = null, $poid = null) {
	
		$role = UserRole::model ()->findByAttributes ( array (
				'title' => 'Vendor' 
		) );
		$loggedinuser = Yii::app ()->user->model;
		if ($loggedinuser->role_id != $role->id) {
			$user = Vendor::model ()->findByAttributes ( array (
					'create_user_id' => $loggedinuser->id 
			) );
		} else {
			$user = User::model ()->findByAttributes ( array (
					'id' => $loggedinuser->id 
			) );
		}
		
		$model = new B2bPurchaseBillDetail ( 'search' );
		$model->unsetAttributes ();
		
		if ($poid == null) {
			$poids = $model->getAllPOBillOptions ( $user->id );
			if (isset ( $poids ['0'] ))
				$poid = $poids ['0'];
		}
		
		$_GET ['poid'] = $poid;
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['B2bPurchaseBillDetail'] )) {
			if (isset ( $_POST ['B2bPurchaseBillDetail'] ['outlet_id'] )) {
				$_GET ['B2bPurchaseBillDetail'] ['outlet_id'] = $_POST ['B2bPurchaseBillDetail'] ['outlet_id'];
			}
			if (isset ( $_POST ['B2bPurchaseBillDetail'] ['vendor_id'] ) && ($_POST ['B2bPurchaseBillDetail'] ['vendor_id'] != '')) {
				$_GET ['B2bPurchaseBillDetail'] ['vendor_id'] = $_POST ['B2bPurchaseBillDetail'] ['vendor_id'];
			}
			if (isset ( $_POST ['B2bPurchaseBillDetail'] ['purchase_bill_id'] ) && ($_POST ['B2bPurchaseBillDetail'] ['purchase_bill_id'] != '')) {
				$_GET ['B2bPurchaseBillDetail'] ['purchase_bill_id'] = $_POST ['B2bPurchaseBillDetail'] ['purchase_bill_id'];
			}
			if (isset ( $_POST ['B2bPurchaseBillDetail'] ['start_date'] ) && ($_POST ['B2bPurchaseBillDetail'] ['start_date'] != '')) {
				$_GET ['B2bPurchaseBillDetail'] ['start_date'] = date ( 'Y-m-d', strtotime ( $_POST ['B2bPurchaseBillDetail'] ['start_date'] ) );
			}
		} else {
			if ($poid == null)
				$poid = 0;
			$_GET ['B2bPurchaseBillDetail'] ['purchase_bill_id'] = $poid;
		}
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
		
		$this->render ( 'admin', array (
				'model' => $model,
				'user' => $user,
				'poid' => $poid 
		) );
	}
	
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new B2bPurchaseBillDetail ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
			case 'create' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					);
				}
				break;
			case 'index' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			case 'admin' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			default :
			case 'view' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'index' 
							),
							'icon' => 'icon-th-list icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Delete' ),
							'url' => '#',
							'linkOptions' => array (
									'submit' => array (
											'delete',
											'id' => $model->id 
									),
									'confirm' => 'Are you sure you want to delete this item?' 
							),
							'icon' => 'icon-remove icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => array (
									'update',
									'id' => $model->id 
							),
							'icon' => 'icon-edit icon-white' 
					);
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
	public function actionTaxwise() {
		$model = new B2bPurchaseBillDetail ( 'b2bsearch' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		$columns = array();
		if (isset ( $_POST ['B2bPurchaseBillDetail']['start_date'] ) &&( $_POST ['B2bPurchaseBillDetail']['start_date'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['end_date'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['end_date'] !='')){
					$_GET['B2bPurchaseBillDetail']['start_date'] = $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					$_GET['B2bPurchaseBillDetail']['end_date'] = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
					Yii::app()->session['order_item_start_date'] =  $_POST ['B2bPurchaseBillDetail']['start_date'] ;
					Yii::app()->session['order_item_end_date']  = $_POST ['B2bPurchaseBillDetail']['end_date'] ;
		
		}else{
			if(!isset($_GET['B2bPurchaseBillDetail_page'])){
			if (!$this->isExportRequest () && Yii::app()->session['order_item_start_date'] == '' && Yii::app()->session['order_item_end_date'] == '') {
				$_GET ['B2bPurchaseBillDetail'] ['start_date'] = date('Y-m-d');
				$_GET ['B2bPurchaseBillDetail'] ['end_date'] = date('Y-m-d');
			Yii::app()->session['order_item_start_date'] = date('Y-m-d') ;
			Yii::app()->session['order_item_end_date']  = date('Y-m-d') ;
			Yii::app()->session['order_item_item_id'] ='';
			}
			}
		}
		if (isset ( $_POST ['B2bPurchaseBillDetail']['min_amt'] ) &&( $_POST ['B2bPurchaseBillDetail']['min_amt'] !='')&& (isset ( $_POST ['B2bPurchaseBillDetail']['max_amt'] ))
				&& (  $_POST ['B2bPurchaseBillDetail']['max_amt'] !='')){
					$_GET['B2bPurchaseBillDetail']['min_amt'] = $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					$_GET['B2bPurchaseBillDetail']['max_amt'] = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
					Yii::app()->session['order_item_min_amt'] =  $_POST ['B2bPurchaseBillDetail']['min_amt'] ;
					Yii::app()->session['order_item_max_amt']  = $_POST ['B2bPurchaseBillDetail']['max_amt'] ;
		
		}
		if (isset ( $_GET ['B2bPurchaseBillDetail']['item_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['item_id'] !='')){
					Yii::app()->session['order_item_item_id'] =  $_GET ['B2bPurchaseBillDetail']['item_id'] ;
					
		}else{
			
			
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_item_id'] =	'';
				
			}
			
	
		}
		
		
		if (isset ( $_GET ['B2bPurchaseBillDetail']['vendor_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['vendor_id'] !='')){
					Yii::app()->session['order_item_vendor_id'] =  $_GET ['B2bPurchaseBillDetail']['vendor_id'] ;
					
		}else{
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_vendor_id'] =	'';
				
			}
		
		}
		
		
		
		//employee_id
		if (isset ( $_GET ['B2bPurchaseBillDetail']['create_user_id'] ) &&( $_GET ['B2bPurchaseBillDetail']['create_user_id'] !='')){
					Yii::app()->session['order_item_create_user_id'] =  $_GET ['B2bPurchaseBillDetail']['create_user_id'] ;
					
		}else{
			
			if ($this->isExportRequest ()) {
				
			}else{
				Yii::app()->session['order_item_create_user_id'] =	'';
				
			}
			
		//Yii::app()->session['order_item_create_user_id'] =	'';
		}
		
				
		
		if (isset ( $_POST ['B2bPurchaseBillDetail']['columns'] )){
			$columns = $_POST ['B2bPurchaseBillDetail']['columns'];
		}
		
		if (isset ( $_GET ['B2bPurchaseBillDetail'] ))
			$model->setAttributes ( $_GET ['B2bPurchaseBillDetail'] );
			
			$columns = $model->getB2bDeptColumns($columns);
			if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV ( $model->b2bTaxwisesearch (), $columns
						);
			}
	
		$this->render ( 'texwisereport', array (
				'model' => $model 
		) );
	}
	
	
	
	public function actionGetpendingbill() {
		$option = '';
		$alreadypermissions = array ();
		$user = Yii::app ()->user->model;
	
		if (isset ( $_POST ['vendor_id'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->compare('vendor_id', (int) $_POST['vendor_id']);
			$criteria->addCondition ( 'status !=' . B2BPurchaseBill::STATUS_APPROVED );
			$Getpendingbill = B2BPurchaseBill::model ()->findAll ( $criteria );
			
			
			if ($Getpendingbill) {
				$option .= '<select class="form-control"  id="Pending_bill_id" name="B2bPurchaseBillDetail[id]" onchange="checkPendingBillDate(this.value)"><option value="" id="ckbCheckAll">-Select-</option>';
				$flag = true;
				foreach ( $Getpendingbill as $_mrs ) {
					if($flag){
						$flag = false;
						$option .= '<option  value="' . $_mrs->id . '" selected>' . $_mrs->id . '</option>';
					}else{
						$option .= '<option  value="' . $_mrs->id . '">' . $_mrs->id . '</option>';
					}

					
				}
				$option .= '</select>';
			} else {
				// $option .= '<option value="">-Select-</option>';
			}
			
		
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		echo $option;
	}
	
	public function actionGetpendingbilldate(){
		$criteria = new CDbCriteria ();
			$criteria->compare('id', (int) $_POST['bill_id']);
			$criteria->addCondition ( 'status !=' . B2BPurchaseBill::STATUS_APPROVED );
			$Getpendingbill = B2BPurchaseBill::model ()->find ( $criteria );
			$date=$Getpendingbill->start_date;
			echo $date;	
		
	}	
	
}