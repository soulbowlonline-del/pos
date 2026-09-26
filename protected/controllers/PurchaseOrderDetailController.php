<?php

class PurchaseOrderDetailController extends GxController {

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
					'actions'=>array('index','view','create','update', 'search','admin','delete','ajaxUpdate','ajaxItems','ajaxTax','ajaxCreate','ajaxPONo',
							'sendEmail'
					),
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

	public function actionSendEmail($id){
		$purchaseorder =$this->loadModel($id,'PurchaseOrder');
		
			
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
		$mPDF1->WriteHTML($this->renderPartial('/purchaseOrder/_pdf',array('po'=>$purchaseorder,'poid'=>$id), true));
		
		# Renders image
		//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output('wdir/uploads/pdf/filename.pdf','F');
		$mPDF1->Output("whatsapp/$id.pdf",'F');
		
		 $vendor = Vendor::model()->findByPk($purchaseorder->vendor_id);
		if($vendor){
			$vendoruser = User::model()->findByPk($vendor->create_user_id);
			if($vendoruser){
		$from = 'inandoutsec4@gmail.com' ;
		if($vendor->contact_email != ''){
			$to = $vendor->contact_email;
		}else{
			if($vendoruser){
				$to  = $vendoruser->email;
			}
		}
		$subject = "Your PO number $id approved by Purchase Manager ";
		
		$view = $this->renderPartial ( '/mail/purchase_order_approved', array (
				'pomodel'=>$purchaseorder
		), true );
		
		Yii::app()->interaktApi->sendApprovalOrderMessage($vendor->contact_no , $id,'http://61.2.241.71/pos/amritdb/ws.pdf');
		$to = 'sharma.rajan3097@gmail.com';
		$purchaseorder->mailsend ( $to, $from, $subject, $view );
		}
		}
		$this->redirect(array('admin')); 
	}

	public function actionSendTemplate($id){
		$purchaseorder =$this->loadModel($id,'PurchaseOrder');
		$baseUrl = Yii::app()->params['soul_bowl_url'];
		# mPDF
		$mPDF1 = Yii::app()->ePdf->mpdf();
		
		# You can easily override default constructor's params
		$mPDF1 = Yii::app()->ePdf->mpdf('', 'A4');
		
		# renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML($this->renderPartial('/purchaseOrder/_pdf',array('po'=>$purchaseorder,'poid'=>$id), true));

		$fileName = "whatsapp/$id.pdf";
		
		$mPDF1->Output($fileName,'F');
		
		$vendor = Vendor::model()->findByPk($purchaseorder->vendor_id);
		if($vendor && $vendor->whatsapp_no != '') {
			if(Yii::app()->interaktApi->uploadFileToSoulBowl($fileName, $id)) {
				
				Yii::app()->interaktApi->sendApprovalOrderMessage($vendor->whatsapp_no , $id, $baseUrl.$fileName);
				Yii::app ()->user->setFlash ( 'success', "Message sent successfully." );
			} else {
				Yii::app ()->user->setFlash ( 'error', "Unable to upload PDF" );
			}
		} else {
			Yii::app ()->user->setFlash ( 'error', "Vendor doesn't have WhatsApp Number. Please enter here <a href='".$baseUrl."vendor/create/$purchaseorder->vendor_id'>Click Here</a>" );
		}
		$this->redirect(array('admin')); 
	}


	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	public function actionAjaxPONo() {
		$option = '';
		$alreadypermissions = array ();
		$user =Yii::app()->user->model;
		if ($user) {
			$role_id = $user->role_id;
			if($role_id == 6){
				$vendor = Vendor::model()->findByAttributes(array('create_user_id'=>$user->id));
				if($vendor){
					$_POST ['vendor_id'] = $vendor->id;
				}
			}
		}
		if (isset ( $_POST ['vendor_id'] )) {
	
			$criteria = new CDbCriteria();
			$criteria->compare('vendor_id', (int) $_POST['vendor_id']);
			$criteria->addCondition('status !='.PurchaseOrderDetail::STATUS_DONE);
			$mrslist = PurchaseOrder::model ()->findAll($criteria);
				
			$option .= '<select class="form-control"  id="PurchaseOrderDetail_purchase_order_id" name="PurchaseOrderDetail[purchase_order_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
	public function actionView($id) 
	{
		$model = $this->loadModel($id, 'PurchaseOrderDetail');
	
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		$this->render('view', array(
			'model' => $model
		));
	}
	public function actionAjaxItems() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
	
			$criteria = new CDbCriteria();
			$criteria->addCondition('status ='.UserRole::STATUS_ACTIVE);
			$criteria->compare('item_id', (int) $_POST['item_id']);
				
			$itemdetails = ItemDetail::model()->findAll($criteria);
			$option .= '<select class="form-control" onChange="checkTaxes()" name="PurchaseOrderDetail[item_detail_id]" id="PurchaseOrderDetail_item_detail_id"><option value="" id="ckbCheckAll">-Select-</option>';
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
	public function actionAjaxTax() {
		$option = '';
		$cgst = 0.00;
		$sgst = 0.00;
		$cess = 0.00;
		$igst = 0.00;
		$mrp = 0.00;
		$sale_rate = 0.00;
		$price = 0.00;
		$tax = null;
		$attr = '';
		$max_qty = 0;
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] ) && isset ( $_POST ['item_detail_id'] )) {
	
			$item = Item::model()->findByPk($_POST ['item_id'] );
			$itemdetail = ItemDetail::model ()->findByPk ( $_POST ['item_detail_id'] );
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
			
					
				if($tax != null){
					$cgst = $tax->tax_val1;
					$sgst = $tax->tax_val2;
					$cess = $tax->tax_val3;
					$igst = $tax->tax_val4;
					$attr = $itemdetail->getCompanyBarcode($itemdetail->id);
				}
			
			$taxes = Tax::model()->findAll();
			$option .= '<select class="form-control" id="PO_item_detaill_list_id" name="PurchaseOrderDetail[tax_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
			}else {
				// $option .= '<option value="">-Select-</option>';
			}
			$option .= '</select>';
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		$data['options'] = $option;
		$data['cgst'] = $cgst;
		$data['sgst'] = $sgst;
		$data['cess'] = $cess;
		$data ['igst'] = $igst;
		$data ['max_qty'] = $max_qty;
		if($tax != null){
			$data ['tax_id'] = $tax->id;
		}else{
			$data ['tax_id'] = 0;
		}
		$data ['mrp'] = $mrp;
		$data ['sale_rate'] = $sale_rate;
		$data ['price'] = $price;
		$data ['attr'] = $attr;
		echo json_encode($data);
	
	}
	public function actionAjaxCreate($id = null)
	{
		$existpo = $this->loadModel($id, 'PurchaseOrder');
		$model = new PurchaseOrderDetail;
	
		//$this->performAjaxValidation($model, 'purchase-order-detail-form');
	
		if (isset($_POST['PurchaseOrderDetail'])) {
			
			$model->setAttributes($_POST['PurchaseOrderDetail']);
			$model->outlet_id = $existpo->outlet_id;
			if($model->approved_qty != '')
				$model->bal_qty = ($model->req_qty - $model->approved_qty);
				$model->purchase_order_id = $id;
				if ($model->save()) {
					if($model->approved_qty != '' && $model->approved_qty != '0'){
						$billmodel = PurchaseBill::model()->findByAttributes(array('purchase_order_id'=>$model->id,'vendor_id'=>$model->vendor_id));
						$updated = true;
						if($billmodel == null){
							$billmodel = new PurchaseBill();
							$updated = false;
						}
						$billmodel->start_date = $existpo->start_date;
						$billmodel->code = "code";
						$billmodel->outlet_id = $existpo->outlet_id;
						$billmodel->vendor_id = $existpo->vendor_id;
						$billmodel->purchase_order_id = $existpo->id;
						$billmodel->organization_id = $existpo->organization_id;
						/* if($billmodel->save()){
							if($updated){
								$msg = 'PurchaseBill is updated';
							}else{
								$msg = 'A new PurchaseBill is added';
							}
							$vendor = Vendor::model()->findByPk($model->vendor_id);
							if($vendor){
								$to_id = $vendor->create_user_id;
							}else{
								$to_id = $model->vendor_id;
							}
							$type = Notification::TYPE_PBILL;
							$model_id = $billmodel->id;
							Notification::AddNotification($model_id,$msg,$type,$to_id);
	
							$billdetailmodel = PurchaseBillDetail::model()->findByAttributes(array('purchase_bill_id'=>$billmodel->id,'outlet_id'=>$model->outlet_id));
							if($billdetailmodel == null){
								$billdetailmodel = new PurchaseBillDetail();
							}
								
							$billdetailmodel->req_qty = $model->req_qty;
							$billdetailmodel->approved_qty = $model->approved_qty;
							$billdetailmodel->bal_qty = ($model->req_qty - $model->approved_qty);
							$billdetailmodel->item_detail_id = $model->item_detail_id;
							$billdetailmodel->item_id = $model->item_id;
							$billdetailmodel->mrp = $model->mrp;
							$billdetailmodel->price = $model->price;
							$billdetailmodel->sale_rate = $model->sale_rate;
							$billdetailmodel->discount = $model->discount;
							$billdetailmodel->discount_amt = $model->discount_amt;
							$billdetailmodel->other_charge = $model->other_charge;
							$billdetailmodel->tax_id = $model->tax_id;
							$billdetailmodel->cgst_per = $model->cgst_per;
							$billdetailmodel->sgst_per = $model->sgst_per;
							$billdetailmodel->cess_per = $model->cess_per;
							$billdetailmodel->cgst_amt = $model->cgst_amt;
							$billdetailmodel->sgst_amt = $model->sgst_amt;
							$billdetailmodel->cess_amt = $model->cess_amt;
							$billdetailmodel->igst_amt = $model->igst_amt;
							$billdetailmodel->igst_per = $model->igst_per;
							$billdetailmodel->amount = $model->amount;
							$billdetailmodel->purchase_bill_id = $billmodel->id;
							$billdetailmodel->outlet_id =$model->outlet_id;
							if($billdetailmodel->save()){
	
							}else{
								 print_r($billdetailmodel->getErrors());exit;
								throw new Exception("Something went wrong",500); 
							}
								
						} */
	
					}
					echo 'Data is saved successfully';
				}
		}
		else{
			echo 'Please add valid data';
		}
	}
	public function actionCreate($id = null)
	{
		$existpo = $this->loadModel($id, 'PurchaseOrder');
		$model = new PurchaseOrderDetail;
	
		$this->performAjaxValidation($model, 'purchase-order-detail-form');
	
		if (isset($_POST['PurchaseOrderDetail'])) {
			$model->setAttributes($_POST['PurchaseOrderDetail']);
			$model->outlet_id = $existpo->outlet_id;
			if($model->approved_qty != '')
			$model->bal_qty = ($model->req_qty - $model->approved_qty);
			$model->purchase_order_id = $id;
			if ($model->save()) {
				if($model->approved_qty != '' && $model->approved_qty != '0'){
					$billmodel = PurchaseBill::model()->findByAttributes(array('purchase_order_id'=>$model->id,'vendor_id'=>$model->vendor_id));
					$updated = true;
					if($billmodel == null){
						$billmodel = new PurchaseBill();
						$updated = false;
					}
					$billmodel->start_date = $existpo->start_date;
					$billmodel->code = "code";
					$billmodel->outlet_id = $existpo->outlet_id;
					$billmodel->vendor_id = $existpo->vendor_id;
					$billmodel->purchase_order_id = $existpo->id;
					$billmodel->organization_id = $existpo->organization_id;
					if($billmodel->save()){
						if($updated){
						$msg = 'PurchaseBill is updated';
						}else{
							$msg = 'A new PurchaseBill is added';
						}
						$vendor = Vendor::model()->findByPk($model->vendor_id);
						if($vendor){
							$to_id = $vendor->create_user_id;
						}else{
							$to_id = $model->vendor_id;
						}
						$type = Notification::TYPE_PBILL;
						$model_id = $billmodel->id;
						Notification::AddNotification($model_id,$msg,$type,$to_id);
						
						$billdetailmodel = PurchaseBillDetail::model()->findByAttributes(array('purchase_bill_id'=>$billmodel->id,'outlet_id'=>$model->outlet_id));
						if($billdetailmodel == null){
							$billdetailmodel = new PurchaseBillDetail();
						}
							
						$billdetailmodel->req_qty = $model->req_qty;
						$billdetailmodel->approved_qty = $model->approved_qty;
						$billdetailmodel->bal_qty = ($model->req_qty - $model->approved_qty);
						$billdetailmodel->item_detail_id = $model->item_detail_id;
						$billdetailmodel->item_id = $model->item_id;
						$billdetailmodel->mrp = $model->mrp;
						$billdetailmodel->price = $model->price;
						$billdetailmodel->sale_rate = $model->sale_rate;
						$billdetailmodel->discount = $model->discount;
						$billdetailmodel->discount_amt = $model->discount_amt;
						$billdetailmodel->other_charge = $model->other_charge;
						$billdetailmodel->vat = $model->vat;
						$billdetailmodel->amount = $model->amount;
						$billdetailmodel->purchase_bill_id = $billmodel->id;
						$billdetailmodel->outlet_id =$model->outlet_id;
						if($billdetailmodel->save()){
	
						}else{
							print_r($billdetailmodel->getErrors());exit;
							throw new Exception("Something went wrong",500);
						}
							
					}
	
				}
				$this->redirect(array('admin', 'id' => $existpo->vendor_id,'poid'=>$id));
			}
		}
		$this->updateMenuItems($model);
		$this->render('create', array( 'model' => $model,'id'=>$id));
	}
	

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id, 'PurchaseOrderDetail');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation($model, 'purchase-order-detail-form');

		if (isset($_POST['PurchaseOrderDetail'])) {
			$model->setAttributes($_POST['PurchaseOrderDetail']);

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
		$model = $this->loadModel($id, 'PurchaseOrderDetail');
		
		//if( !($this->isAllowed ( $model)))	throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
	
		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$this->loadModel($id, 'PurchaseOrderDetail')->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	public function actionIndex($id = null,$poid= null)
	{
	/* 	if($id == null){ */
			$loggedinuser = Yii::app()->user->model;
			if($loggedinuser->role_id != 1){
				$user = Vendor::model()->findByAttributes(array('create_user_id'=>$loggedinuser->id));
			}else{
				$user = User::model()->findByAttributes(array('id'=>$loggedinuser->id));
			}
		/* }else{
			$user = Vendor::model()->findByPk($id);
		} */
		$model = new PurchaseOrderDetail('search');
		$model->unsetAttributes();
	
		if($poid == null){
			$poids = $model->getAllPOOptions($user->id);
			if(isset($poids['0']))
				$poid = $poids['0'];
		}
	
		$_GET['poid'] = $poid;
		$this->updateMenuItems($model);
	
		if (isset($_POST['PurchaseOrderDetail']))
		{
			if (isset($_POST['PurchaseOrderDetail']['outlet_id']))
			{
				$_GET['PurchaseOrderDetail']['outlet_id'] = $_POST['PurchaseOrderDetail']['outlet_id'];
			}
			if (isset($_POST['PurchaseOrderDetail']['purchase_order_id']))
			{
				$_GET['PurchaseOrderDetail']['purchase_order_id'] = $_POST['PurchaseOrderDetail']['purchase_order_id'];
			}
			if (isset($_POST['PurchaseOrderDetail']['start_date']))
			{
				$_GET['PurchaseOrderDetail']['start_date'] = date('Y-m-d',strtotime($_POST['PurchaseOrderDetail']['start_date']));
			}
	
		}else{
			if($poid == null)
				$poid = 0;
				$_GET['PurchaseOrderDetail']['purchase_order_id'] = $poid;
		}
		if (isset($_GET['PurchaseOrderDetail']))
			$model->setAttributes($_GET['PurchaseOrderDetail']);
	
			$this->render('index', array(
					'model' => $model,'user'=>$user,'poid'=>$poid
			));
	}
	
	public function actionSearch()
	{
		$model = new Job('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
	
		if (isset($_GET['PurchaseOrderDetail']))
		{
			$model->setAttributes($_GET['PurchaseOrderDetail']);
			$this->renderPartial('_list', array(
					'dataProvider' => $model->search(),
					'model' => $model,
			));
		}
			
		$this->renderPartial('_search', array(
				'model' => $model,
		));
	}
	public function actionAdmin($id = null,$poid= null)
	{
		// $queryParams = [
		// 	'offset' => 0,
		// 	'autosubmitted_for' => 'all',
		// 	'approval_status' => 'APPROVED',
		// 	// 'variable_present' => 'Yes/No',
		// ];
		// $templates = Yii::app()->interaktApi->getTemplates($queryParams);
		// echo "<pre>"; print_r($templates); die;
		$outlet_id = null;
		$vendor_id = null;
		$start_date = null;
		/* if($id == null){ */
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			$loggedinuser = Yii::app()->user->model;
			if($loggedinuser->role_id == $role->id){
			$user = Vendor::model()->findByAttributes(array('create_user_id'=>$loggedinuser->id));
			}else{
				$user = User::model()->findByAttributes(array('id'=>$loggedinuser->id));
			}
		/* }else{
			$user = Vendor::model()->findByPk($id);
		} */
		$model = new PurchaseOrderDetail('search');
		$model->unsetAttributes();
		Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, 'pos_post' );
		if($poid == null){
			$poids = $model->getAllPOOptions($user->id);
			if(isset($poids['0']))
				$poid = $poids['0'];
		}
	
		$_GET['poid'] = $poid;
		$this->updateMenuItems($model);
		if($poid != null){
			$po = PurchaseOrder::model()->findByPk($poid);
			if($po){
				$outlet_id = $po->outlet_id;
				$vendor_id = $po->vendor_id;
				$start_date = $po->start_date;
			}
		}
		if (isset($_POST['PurchaseOrderDetail']))
		{
			
			if (isset($_POST['PurchaseOrderDetail']['outlet_id']))
			{
				$_GET['PurchaseOrderDetail']['outlet_id'] = $_POST['PurchaseOrderDetail']['outlet_id'];
			}
			if (isset($_POST['PurchaseOrderDetail']['purchase_order_id']) && ($_POST['PurchaseOrderDetail']['purchase_order_id']!= ''))
			{
				$_GET['PurchaseOrderDetail']['purchase_order_id'] = $_POST['PurchaseOrderDetail']['purchase_order_id'];
				$poid = $_POST['PurchaseOrderDetail']['purchase_order_id'] ;
				if($poid != null){
					$po = PurchaseOrder::model()->findByPk($poid);
					if($po){
						$outlet_id = $po->outlet_id;
						$vendor_id = $po->vendor_id;
						$start_date = $po->start_date;
					}
				}
			}else{
				if (isset($_POST['PurchaseOrderDetail']['outlet_id']))
				{
					$outlet_id = $_POST['PurchaseOrderDetail']['outlet_id'];
				}else{
					$outlet_id = null;
				}
				if (isset($_POST['PurchaseOrderDetail']['vendor_id']))
				{
					$vendor_id = $_POST['PurchaseOrderDetail']['vendor_id'];
				}else{
					$vendor_id = null;
				}
				
				$start_date = null;
			}
			if (isset($_POST['PurchaseOrderDetail']['vendor_id']) && ($_POST['PurchaseOrderDetail']['vendor_id']!= ''))
			{
				$_GET['PurchaseOrderDetail']['vendor_id'] = $_POST['PurchaseOrderDetail']['vendor_id'];
			}
			if (isset($_POST['PurchaseOrderDetail']['start_date']) && ($_POST['PurchaseOrderDetail']['start_date'] != ''))
			{
				if (isset ( $_POST ['PurchaseOrderDetail'] ['start_date'] ) && ($_POST ['PurchaseOrderDetail'] ['start_date'] != '')) {
					if (isset ( $_POST ['PurchaseOrderDetail'] ['purchase_order_id'] ) && ($_POST ['PurchaseOrderDetail'] ['purchase_order_id'] != '')) {
						$po = PurchaseOrder::model()->findByPk($mrsid);
						if($po){
							$start_date = $po->start_date;
						}
						$_GET ['PurchaseOrderDetail'] ['start_date'] = date ( 'Y-m-d', strtotime ( $start_date ) );
					}else{
						$_GET ['PurchaseOrderDetail'] ['start_date'] = date ( 'Y-m-d', strtotime ( $_POST ['PurchaseOrderDetail'] ['start_date'] ) );
					}
				
				}
				
			}
	
		}else{
			if($poid == null)
				$poid = 0;
				$_GET['PurchaseOrderDetail']['purchase_order_id'] = $poid;
		}
	
		if (isset($_GET['PurchaseOrderDetail']))
			$model->setAttributes($_GET['PurchaseOrderDetail']);
	
			$this->render('admin', array(
					'model' => $model,'user'=>$user,'poid'=>$poid,
					'outlet_id'=>$outlet_id,
					'vendor_id'=>$vendor_id,
					'start_date'=>$start_date,
			));
	}
	/* public function actionAdmin() 
	{
		$model = new PurchaseOrderDetail('search');
		$model->unsetAttributes();
		$this->updateMenuItems($model);
		
		if (isset($_GET['PurchaseOrderDetail']))
			$model->setAttributes($_GET['PurchaseOrderDetail']);

		$this->render('admin', array(
			'model' => $model,
		));
	} */
	/*protected function processActions($model = null)
	{
		parent::processActions($model);
		//$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	}*/
	protected function updateMenuItems($model = null)
	{	
		// create static model if model is null
		if ( $model == null ) $model = new PurchaseOrderDetail();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = array('label'=>Yii::t('app', 'View') , 'url'=>array('view','id'=>$model->id),'icon'=>'icon-plus icon-white');
				}
			case 'create':
				{
					$this->menu[] = array('label'=>Yii::t('app', 'Manage'), 'url'=>array('admin'),'icon'=>'icon-wrench icon-white');							
				//	$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');	
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
					if(isset($_GET['poid'])){
						$poid =  $_GET['poid'];
					}else{
						$poid = null;
					}
				//	$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = array('label'=>Yii::t('app', 'Add Item'), 'url'=>array('create','id'=>$poid),'icon'=>'icon-plus icon-white');
				}
				break;				
			default:
			case 'view':
				{
					//$this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
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
	
	public function actionAjaxupdate($id)
	{
		//$act = $_GET['act'];
		 
		$poIdAll = $_POST;
		if(isset($_POST['status'])  && ($_POST['status']== 1)){
		foreach($poIdAll as $qty)
		{
			if(isset( $poIdAll['qty']))
				$qtys = $poIdAll['qty'];
		}
		if(count($qtys)>0)
		{
			$purchaseorder =$this->loadModel($id,'PurchaseOrder');
			
			if (isset ( $_POST ['gross_amt'] ))
				$purchaseorder->gross_amt = $_POST ['gross_amt'];
			if (isset ( $_POST ['total_discount'] ))
		     $purchaseorder->total_discount = $_POST ['total_discount'];
			if (isset ( $_POST ['tax_amount'] ))
			$purchaseorder->tax_amount = $_POST ['tax_amount'];
			if (isset ( $_POST ['bill_amount'] ))
			$purchaseorder->bill_amount = $_POST ['bill_amount'];
			$purchasebill = PurchaseBill::model()->findByAttributes(array('purchase_order_id'=>$id,'vendor_id'=>$purchaseorder->vendor_id));
			$updated = true;
			if($purchasebill == null){
				$purchasebill = new PurchaseBill();
				$updated = false;
			}
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
			$latestbill = PurchaseBill::model()->find($criteria);
			if($latestbill){
				$bill_no = $latestbill->grn_refrence_no + 1;
			}else{
				$bill_no = 1;
			}
			
			$purchasebill->grn_refrence_no = $bill_no;
			if (isset ( $_POST ['gross_amt'] ))
				$purchasebill->gross_amt = $_POST ['gross_amt'];
			if (isset ( $_POST ['total_discount'] ))
			$purchasebill->total_discount = $_POST ['total_discount'];
			if (isset ( $_POST ['tax_amount'] ))
			$purchasebill->tax_amount = $_POST ['tax_amount'];
			if (isset ( $_POST ['bill_amount'] ))
			$purchasebill->bill_amount = $_POST ['bill_amount'];
			$purchasebill->start_date = date('Y-m-d');
			$purchasebill->code = "code";
			$purchasebill->outlet_id = $purchaseorder->outlet_id;
			$purchasebill->vendor_id = $purchaseorder->vendor_id;
			$purchasebill->purchase_order_id = $id;
			$purchasebill->organization_id = $purchaseorder->organization_id;
			$set = true;
			$transaction = Yii::app ()->db->beginTransaction ();
			try {
			if($purchasebill->save()){
				if($updated){
					$msg = 'PurchaseBill is updated';
				}else{
					$msg = 'A new PurchaseBill is added';
				}
				$vendor = Vendor::model()->findByPk($purchasebill->vendor_id);
				$email = '' ;
				if($vendor){
					$to_id = $vendor->create_user_id;
					$vendoruser = User::model()->findByAttributes(array('id'=>$vendor->create_user_id));
					if($vendoruser){
						$email = $vendoruser->email;
					}
				}else{
					$to_id = $purchasebill->vendor_id;
				}
				$type = Notification::TYPE_PBILL;
				$model_id = $purchasebill->id;
				Notification::AddNotification($model_id,$msg,$type,$to_id);
				
				if($email != ''){
					$from = Yii::app()->params['mail_email'] ;
					if($vendor->contact_email != ''){
					$to = $vendor->contact_email;
					}else{
						if($vendoruser){
						$to  = $vendoruser->email;
						}
					}
					$subject = 'Your purchase order approved:';
						
					$view = $this->renderPartial ( '/mail/purchase_order_approved', array (
							'pomodel'=>$purchaseorder
					), true );
						
						
					//$purchaseorder->mailsend ( $to, $from, $subject, $view );
				}
					
				
				
				foreach($qtys as $key=>$qty)
				{
					$model=$this->loadModel($key,'PurchaseOrderDetail');
					$purchaseordermodel = $this->loadModel($model->purchase_order_id,'PurchaseOrder');
					$model->mrp = $_POST['mrp'][$key];
					//if($qty != '' && $qty != '0'){
						$model->status = PurchaseOrderDetail::STATUS_DONE;
						$status = PurchaseOrder::STATUS_APPROVED;
					//}
					if(isset( $poIdAll['mrp']))
					{
						$model->mrp = $poIdAll['mrp'][$key];
					}
					if(isset( $poIdAll['price']))
					{
						$model->price = $poIdAll['price'][$key];
					}
					if(isset( $poIdAll['salerate']))
					{
						$model->sale_rate = $poIdAll['salerate'][$key];
					}
					if(isset( $poIdAll['discount']))
					{
						$model->discount = $poIdAll['discount'][$key];
					}
					if(isset( $poIdAll['discount_amt']))
					{
						$model->discount_amt = $poIdAll['discount_amt'][$key];
					}
					if(isset( $poIdAll['discount1']))
					{
						$model->discount1 = $poIdAll['discount1'][$key];
					}
					if(isset( $poIdAll['discount_amt1']))
					{
						$model->discount_amt1 = $poIdAll['discount_amt1'][$key];
					}
					if(isset( $mrnIdAll['cgstData']))
					{
						$model->cgst_per = $mrnIdAll['cgstData'][$key];
					}
					if(isset( $mrnIdAll['sgstData']))
					{
						$model->sgst_per = $mrnIdAll['sgstData'][$key];
					}
					if(isset( $mrnIdAll['cessData']))
					{
						$model->cess_per = $mrnIdAll['cessData'][$key];
					}
					if(isset( $mrnIdAll['cgstamtData']))
					{
						$model->cgst_amt = $mrnIdAll['cgstamtData'][$key];
					}
					if(isset( $mrnIdAll['sgstamtData']))
					{
						$model->sgst_amt = $mrnIdAll['sgstamtData'][$key];
					}
					if(isset( $mrnIdAll['cessamtData']))
					{
						$model->cess_amt = $mrnIdAll['cessamtData'][$key];
					}
					if (isset ( $mrsIdAll ['igstData'] )) {
						$model->igst_per = $mrsIdAll ['igstData'] [$key];
					}
					if (isset ( $mrsIdAll ['igstamtData'] )) {
						$model->igst_amt = $mrsIdAll ['igstamtData'] [$key];
					}
					if(isset( $poIdAll['other_charge']))
					{
						$model->other_charge = $poIdAll['other_charge'][$key];
					}
					if(isset( $poIdAll['amount']))
					{
						$model->amount = $poIdAll['amount'][$key];
					}
					if (isset ( $poIdAll ['marginData'] )) {
						$model->margin = $poIdAll ['marginData'] [$key];
					}
					if (isset ( $poIdAll ['qty'] )) {
						$model->approved_qty = $poIdAll ['qty'] [$key];
						$model->bal_qty = ($model->req_qty - $model->approved_qty );
					}
					
					
					if($model->save()){
						if($qty != '' && $qty != '0'){
							$purchasedetailmodel = PurchaseBillDetail::model()->findByAttributes(array('purchase_bill_id'=>$purchasebill->id,'outlet_id'=>$purchasebill->outlet_id,'item_id'=>$model->item_id,'item_detail_id'=>$model->item_detail_id));
							if($purchasedetailmodel == null){
								$purchasedetailmodel = new PurchaseBillDetail();
							}
								
							$purchasedetailmodel->req_qty = $model->req_qty;
							$purchasedetailmodel->approved_qty = $qty;
							$purchasedetailmodel->bal_qty = ($model->req_qty - $qty);
							$purchasedetailmodel->item_detail_id = $model->item_detail_id;
							$purchasedetailmodel->item_id = $model->item_id;
							$purchasedetailmodel->mrp = $model->mrp;
							$purchasedetailmodel->price = $model->price;
							$purchasedetailmodel->sale_rate = $model->sale_rate;
							$purchasedetailmodel->discount = $model->discount;
							$purchasedetailmodel->discount_amt = $model->discount_amt;
							$purchasedetailmodel->discount1 = $model->discount1;
							$purchasedetailmodel->discount_amt1 = $model->discount_amt1;
							$purchasedetailmodel->other_charge = $model->other_charge;
							$purchasedetailmodel->tax_id = $model->tax_id;
							$purchasedetailmodel->cgst_per = $model->cgst_per;
							$purchasedetailmodel->sgst_per = $model->sgst_per;
							$purchasedetailmodel->cess_per = $model->cess_per;
							$purchasedetailmodel->cgst_amt = $model->cgst_amt;
							$purchasedetailmodel->sgst_amt = $model->sgst_amt;
							$purchasedetailmodel->cess_amt = $model->cess_amt;
							$purchasedetailmodel->igst_amt = $model->igst_amt;
							$purchasedetailmodel->igst_per = $model->igst_per;
							$purchasedetailmodel->amount = $model->amount;
							$purchasedetailmodel->purchase_bill_id = $purchasebill->id;
							$purchasedetailmodel->outlet_id =$model->outlet_id;
							$purchasedetailmodel->margin = $model->margin;
							if($purchasedetailmodel->save()){
									
							}else{
								$set = false;
								throw new Exception("Something went wrong",500);
							}
						}
					}
						
					else{
						$set = false;
						throw new Exception("Something went wrong",500);
					}
						
				}
				$purchaseorder->status = $status;
				if($purchaseorder->save()){
					
				}else{
					$set = false;
				}
				
				if ($set == true) {
					$transaction->commit ();
					
				} else {
					$transaction->rollback ();
					
				}
			}
			
			} catch ( Exception $e ) {
				$transaction->rollback ();
			}
		}
		}else{
			$purchaseorder = PurchaseOrder::model()->findByPk($id);
			
			if($purchaseorder){
				$purchaseorder->status = PurchaseOrder::STATUS_REJECT;
					
				$purchaseorder->saveAttributes(array('status'));
					
				$podetailmodels = PurchaseOrderDetail::model()->findAllByAttributes(array('purchase_order_id'=>$id));
				if($podetailmodels){
					foreach($podetailmodels as $podetailmodel){
						$podetailmodel->status =  PurchaseOrderDetail::STATUS_REJECT;
						$podetailmodel->saveAttributes(array('status'));
					}
				
						$msg = 'PurchaseOrder is rejected';
					
					$vendor = Vendor::model()->findByPk($purchaseorder->vendor_id);
					if($vendor){
						$to_id = $vendor->create_user_id;
					}else{
						$to_id = $purchaseorder->vendor_id;
					}
					$type = Notification::TYPE_PO;
					$model_id = $purchaseorder->id;
					Notification::AddNotification($model_id,$msg,$type,$to_id);
			
				}
					
			}
			$mrn = Mrn::model()->findByPk($purchaseorder->mrn_id);
			 
			if($mrn){
				$mrn->status = Mrn::STATUS_REJECT;
				 
				$mrn->saveAttributes(array('status'));
				 
				$mrndetailmodels = MrnDetail::model()->findAllByAttributes(array('mrn_id'=>$mrn->id));
				if($mrndetailmodels){
					foreach($mrndetailmodels as $mrndetailmodel){
						$mrndetailmodel->status =  MrnDetail::STATUS_REJECT;
						$mrndetailmodel->saveAttributes(array('status'));
					}
					 
					 
				}
				 
			}
			$mrs = Mrs::model()->findByPk($mrn->mrs_id);
			
			if($mrs){
				$mrs->status = Mrs::STATUS_PENDING;
				$mrs->saveAttributes(array('status'));
				$mrsdetailmodels = MrsDetail::model()->findAllByAttributes(array('mrs_id'=>$mrs->id));
				if($mrsdetailmodels){
					foreach($mrsdetailmodels as $mrsdetailmodel){
						$mrsdetailmodel->status =  MrsDetail::STATUS_PENDING;
						$mrsdetailmodel->saveAttributes(array('status'));
					}
			
			
				}
				 
			}
		}
			
	
	}
}