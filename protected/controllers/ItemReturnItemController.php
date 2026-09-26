<?php

class ItemReturnItemController extends GxController {

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
					'actions'=>array('view','create','update', 'search','admin','delete','ajaxItems','ajaxTax','ajaxupdate'	,'report','ajaxbillno', 'ajaxCreditBillNo', 'ajaxTaxTable', 'printPdf'),
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
public function actionReport($id = null) {
		$model = new ItemReturnItem ( 'search' );
		$model->unsetAttributes ();
		
		$this->updateMenuItems ( $model );
		$columns = array ();
		Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, '$_POST' );
		if (isset ( $_POST ['ItemReturnItem'] ['tally_start_date'] ) && ($_POST ['ItemReturnItem'] ['tally_start_date'] != '') && (isset ( $_POST ['ItemReturnItem'] ['tally_end_date'] )) && ($_POST ['ItemReturnItem'] ['tally_end_date'] != '')) {
			$_GET ['ItemReturnItem'] ['tally_start_date'] = $_POST ['ItemReturnItem'] ['tally_start_date'];
		$_GET ['ItemReturnItem'] ['tally_end_date'] = $_POST ['ItemReturnItem'] ['tally_end_date'];
			Yii::app ()->session ['returnitem_start_date'] = date('Y-m-d',strtotime($_POST ['ItemReturnItem'] ['tally_start_date']));
			Yii::app ()->session ['returnitem_end_date'] = date('Y-m-d',strtotime($_POST ['ItemReturnItem'] ['tally_end_date']));
		}
		Yii::log ( CVarDumper::dumpAsString ( Yii::app ()->session ['returnitem_start_date'] ), CLogger::LEVEL_WARNING, 'returnsession' );
		if (isset ( $_POST ['ItemReturnItem'] ['columns'] )) {
			$columns = $_POST ['ItemReturnItem'] ['columns'];
		}
		if (isset ( $_GET ['ItemReturnItem'] ))
			$model->setAttributes ( $_GET ['ItemReturnItem'] );
		$columns = $model->getColumns ( $columns );
		if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV ( $model->reportsearch (), $columns );
		}
		
		$this->render ( 'report', array (
				'model' => $model 
		) );
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
		$alreadypermissions = array ();
		$tax = null;
		$attr = "";
		$item = null;
		$msg = 'Inactive';
		if (isset ( $_POST ['item_detail_id'] ) && isset($_POST ['vendor_id'])) {
				
			$itemdetail = ItemDetail::model ()->findByAttributes( array('bar_code'=>$_POST ['item_detail_id'],
					'status'=>ItemDetail::STATUS_ACTIVE
			));
			if($itemdetail){
				$item = Item::model ()->findByPk ( $itemdetail->item_id);
				$tax = Tax::model ()->findByPk($itemdetail->tax_id);
			}else{
				$itemTax = ItemTax::model ()->findByAttributes ( array (
						'item_detail_id' => $_POST ['item_detail_id']
				) );
				if ($itemTax) {
					$tax = Tax::model ()->findByPk ( $itemTax->tax_id );
				}
			}
			
			if($item != null){
				$criteria = new CDbCriteria();
				$criteria->order = 'id desc';
				$criteria->addCondition('item_detail_id ='.$item->id);
				$vendor =  ItemVendor::model()->find($criteria);
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
				
			$taxes = Tax::model ()->findAll ();
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
				
			$criteria = new CDbCriteria ();
			$criteria->compare('grn_refrence_no', PostId::get('grn_no'));
			
				
			$bill = PurchaseBill::model ()->find ( $criteria );
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
			$criteria = new CDbCriteria ();
			// $criteria->addCondition ( 'vendor_id =' . $bill->vendor_id );
			if($start_date != '' && $end_date != ''){
				$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
			}
			$criteria->compare ( 'credit_note_no', $_POST ['credit_note_no'] );
			$bill = ItemReturn::model ()->findAll ( $criteria );
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

			$this->renderPartial ( '_tax', array (
				  'returnid' => $_POST ['id'],
					'tax_id' => $_POST ['tax_id'],
					'return_item_ids' => $return_item_ids,
					'model' => $model, 
			)
			 );
		}
	}

	public function actionAjaxItems() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
				
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'status =' . ItemDetail::STATUS_ACTIVE );
			$criteria->compare('item_id', PostId::get('item_id'));
				
			$itemdetails = ItemDetail::model ()->findAll ( $criteria );
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
		$model = $this->loadModel($id, 'ItemReturnItem');
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}

	public function actionCreate() 
	{
		$model = new ItemReturnItem;

		//$this->performAjaxValidation($model, 'item-return-item-form');
       // echo '<pre>';
       // print_r($_POST['ItemReturnItem']);exit;
		if (isset($_POST['ItemReturnItem'])) {
			
			$itemdetail = ItemDetail::model ()->findByAttributes( array('bar_code'=>$_POST['ItemReturnItem'] ['bar_code'],
					'status'=>ItemDetail::STATUS_ACTIVE
			));
			$item = Item::model()->findByPk($_POST['ItemReturnItem']['item_id']);
			if($item && $itemdetail){
				$_POST['ItemReturnItem']['item_detail_id'] = $itemdetail->id;
			$barcodeQty = $item->getBarCodeTotalRemainingQuantity();
			
			$itemreturn = ItemReturn::model()->findByAttributes(array('status'=>ItemReturn::STATUS_PENDING,'vendor_id'=>$_POST['ItemReturnItem']['vendor_id'],
					'outlet_id'=>$_POST['ItemReturnItem']['outlet_id']
			));
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
			$model = ItemReturnItem::model()->findByAttributes(array('item_id'=>$_POST['ItemReturnItem']['item_id'],
					'item_detail_id'=>$itemdetail->id,'return_id'=>$itemreturn->id,
			));
			if($model == null){
				$model = new ItemReturnItem;
			}
			$model->setAttributes($_POST['ItemReturnItem']);
			
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
  		$model = $this->loadModel ( $key, 'ItemReturnItem' );
  		$returnmodel = $this->loadModel ( $model->return_id, 'ItemReturn' );
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
  			$transaction = Yii::app ()->db->beginTransaction ();
  				
  			try {
  				$itemDetail = ItemDetail::model()->findByPk($model->item_detail_id);
  				if($itemDetail){
  					$itemStock = ItemStock::model()->findByAttributes(array('item_detail_id'=>$itemDetail->id,
  							'outlet_id'=> $model->outlet_id));
  					$item = Item::model()->findByPk($itemDetail->item_id);
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
  											
										$criteriaItemStock = new CDbCriteria();
										$criteriaItemStock->compare('item_id',$itemDetail->item_id);
										$criteriaItemStock->select ='SUM(balance_qty) AS balance_qty';
										$criteriaItemStock->group = 'item_id';
										$mrsItemStock = ItemStock::model()->find($criteriaItemStock);	

										$criteriaVendor = new CDbCriteria();
										$criteriaVendor->order = 'id desc';
										$criteriaVendor->addCondition('item_detail_id ='.$item->id);
										$vendor =  ItemVendor::model()->find($criteriaVendor);
										
										/*Create MRS section*/
										$criteriaMrs = new CDbCriteria();
										$criteriaMrs->order = 'id desc';
										$criteriaMrs->limit = '1';
										$criteriaMrs->addCondition('vendor_id ='.$vendor->vendor_id);
										$vendorMRS = Mrs::model()->find($criteriaMrs);
								
								
									if($vendorMRS->id){
									$item = Item::model()->findByPk($item->id);
									$criteriaMrsD = new CDbCriteria();
									$criteriaMrsD->order = 'id desc';
									$criteriaMrsD->limit = '1';
									$criteriaMrsD->addCondition('mrs_id ='.$vendorMRS->id);
									$criteriaMrsD->addCondition('item_id ='.$item->id);
									$vendorMRSD = MrsDetail::model()->find($criteriaMrsD);
									
									
									if(empty($vendorMRSD)){
									
										/*Create MRS*/
										// $itemdetail = Item::model()->findByPk($item->item_id);
										$organization = Organization::model()->find();
										$itemdetail_ = ItemDetail::model()->findByPk($itemDetail->id);
										$tax='';
										$tax_id='';
										if($itemdetail_){
											$tax = Tax::model()->findByPk($itemdetail_->tax_id);
										$tax_id = $itemdetail_->tax_id;
										}
										Yii::log ( CVarDumper::dumpAsString ( $vendor->vendor_id ), CLogger::LEVEL_WARNING, '$mrs_vendor_id' );
										if($vendor->vendor_id != null){
										$mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$vendor->vendor_id,
										'outlet_id'=>$model->outlet_id
										));
										Yii::log ( CVarDumper::dumpAsString ( $mrs ), CLogger::LEVEL_WARNING, '$mrs_id' );
										
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
									
										Yii::log ( CVarDumper::dumpAsString ( $mrs->vendor_id ), CLogger::LEVEL_WARNING, '$mrs->vendor_id' );
										//$mrs->tax_id = $this->tax_id;
 
										$mrs->organization_id = $organization->id;
										if($mrs->save()){
										$vendor = Vendor::model()->findByPk($mrs->vendor_id);

										if($updated){
										$msg = 'MRS is updated';
										}else{
										$msg = 'A new MRS is added';
										}
										$to_id = $vendor->create_user_id;
										// $type = Notification::TYPE_MRS;
										$model_id = $mrs->id;
										
										$itemdetail = ItemDetail::model ()->findByPk ( $itemDetail->id );
										$mrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$model->item_detail_id,
											'mrs_id'=>$mrs->id
										));
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
  				} catch ( Exception $e ) {
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
  		$model = $this->loadModel ( $key, 'ItemReturnItem' );
  		
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
		$model = $this->loadModel($id, 'ItemReturnItem');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'item-return-item-form');

		if (isset($_POST['ItemReturnItem'])) {
			$model->setAttributes($_POST['ItemReturnItem']);

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
		$model = $this->loadModel($id, 'ItemReturnItem');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'ItemReturnItem')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex() 
	{
		$this->updateMenuItems();
		$dataProvider = new CActiveDataProvider('ItemReturnItem');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['ItemReturnItem']))
		{
			$model->setAttributes($_GET['ItemReturnItem']);
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
		$model = new ItemReturnItem('search');
		$vendor_id = null;
		$outlet_id = null;
		$model->unsetAttributes();
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
			$model->setAttributes($_GET['ItemReturnItem']);

		$this->render('admin', array(
			'model' => $model,'vendor_id'=>$vendor_id,'outlet_id'=>$outlet_id
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
		if ( $model == null ) $model = new ItemReturnItem();
		
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
					$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('itemReturn/admin'),'icon'=>'icon-th-list icon-white');
					//$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 
					'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Create'), 'url'=>array('create'),'icon'=>'icon-plus icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Update'), 'url'=>array('update', 'id' => $model->id), 'icon'=>'icon-edit icon-white');				
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
            $model = $this->loadModel($key, 'ItemReturnItem');
           //echo "<pre>" ; print_r($model);die;
            $returnmodel = $this->loadModel($model->return_id, 'ItemReturn');
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
                    $transaction = Yii::app()->db->beginTransaction();

                    try {
                        $itemDetail = ItemDetail::model()->findByPk($model->item_detail_id);
                        if ($itemDetail) {
                            $itemStock = ItemStock::model()->findByAttributes(array(
                                'item_detail_id' => $itemDetail->id,
                                'outlet_id' => $model->outlet_id
                            ));
                            $item = Item::model()->findByPk($itemDetail->item_id);
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
										
										$criteriaItemStock = new CDbCriteria();
										$criteriaItemStock->compare('item_id',$itemDetail->item_id);
										$criteriaItemStock->select ='SUM(balance_qty) AS balance_qty';
										$criteriaItemStock->group = 'item_id';
										$mrsItemStock = ItemStock::model()->find($criteriaItemStock);	
										
                                        $criteriaVendor = new CDbCriteria();
										$criteriaVendor->order = 'id desc';
										$criteriaVendor->addCondition('item_detail_id ='.$item->id);
										$vendor =  ItemVendor::model()->find($criteriaVendor);

										/*Create MRS section*/
										$criteriaMrs = new CDbCriteria();
										$criteriaMrs->order = 'id desc';
										$criteriaMrs->limit = '1';
										//$criteriaMrs->addCondition('vendor_id ='.$model->vendor_id);

                                        $criteriaMrs->addCondition('vendor_id ='.$vendor->vendor_id);

										$vendorMRS = Mrs::model()->find($criteriaMrs);
								
								
									if($vendorMRS->id){
									$item = Item::model()->findByPk($item->id);
									$criteriaMrsD = new CDbCriteria();
									$criteriaMrsD->order = 'id desc';
									$criteriaMrsD->limit = '1';
									$criteriaMrsD->addCondition('mrs_id ='.$vendorMRS->id);
									$criteriaMrsD->addCondition('item_id ='.$item->id);
									$vendorMRSD = MrsDetail::model()->find($criteriaMrsD);
									
									
									if(empty($vendorMRSD)){
									
										/*Create MRS*/
										// $itemdetail = Item::model()->findByPk($item->item_id);
										$organization = Organization::model()->find();
										$itemdetail_ = ItemDetail::model()->findByPk($itemDetail->id);
										$tax='';
										$tax_id='';
										if($itemdetail_){
											$tax = Tax::model()->findByPk($itemdetail_->tax_id);
										$tax_id = $itemdetail_->tax_id;
										}
										// Yii::log ( CVarDumper::dumpAsString ( $model->vendor_id ), CLogger::LEVEL_WARNING, '$mrs_vendor_id' );
										// if($model->vendor_id != null){
										// $mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$model->vendor_id,
										// 'outlet_id'=>$model->outlet_id
										// ));

                                        Yii::log ( CVarDumper::dumpAsString ( $vendor->vendor_id ), CLogger::LEVEL_WARNING, '$mrs_vendor_id' );
										if($vendor->vendor_id != null){
										
                                            $mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$vendor->vendor_id,
										'outlet_id'=>$model->outlet_id
										));
                                        
										Yii::log ( CVarDumper::dumpAsString ( $mrs ), CLogger::LEVEL_WARNING, '$mrs_id' );
										
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
									
										Yii::log ( CVarDumper::dumpAsString ( $mrs->vendor_id ), CLogger::LEVEL_WARNING, '$mrs->vendor_id' );
										//$mrs->tax_id = $this->tax_id;
 
										$mrs->organization_id = $organization->id;
										if($mrs->save()){
										$vendor = Vendor::model()->findByPk($mrs->vendor_id);

										if($updated){
										$msg = 'MRS is updated';
										}else{
										$msg = 'A new MRS is added';
										}
										$to_id = $vendor->create_user_id;
										// $type = Notification::TYPE_MRS;
										$model_id = $mrs->id;
										
										$itemdetail = ItemDetail::model ()->findByPk ( $itemDetail->id );
										$mrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$model->item_detail_id,
											'mrs_id'=>$mrs->id
										));
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
                    } catch (Exception $e) {
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
		$mPDF1 = Yii::app()->ePdf->mpdf();
		
		# You can easily override default constructor's params
		$mPDF1 = Yii::app()->ePdf->mpdf('', 'A4');
		
		# renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML($this->renderPartial('_pdf',array('model'=>$model,'outlet_id'=>$_GET['outlet_id'],'vendor_id'=>$_GET['vendor_id']), true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output();
		// if($email != '' && ($role->id != $login->role_id)){
		// 	$from = Yii::app()->params['mail_email'] ;
		// 	$to      = $email;
		// 	$subject = 'Your purchase order :';
		
		// 	$view = $this->renderPartial ( '/mail/purchase_order_pdf', array (
		// 			'po'=>$po
		// 	), true );
		
		
		// 	//$purchaseorder->mailsend ( $to, $from, $subject, $view );
		// }
		
	}
}