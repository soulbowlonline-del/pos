<?php
class MrsDetailController extends GxController {
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
								'ajaxItems',
								'ajaxTax',
								'update',
								'ajaxMrsNo',
								'search',
								'adminn',
								'ajaxupdate',
								'admin',
								'delete',
								'pending',
								'assign',
								'ajaxCreate' 
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
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'MrsDetail' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionAjaxMrsNo() {
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
			$criteria->addCondition ( 'status !=' . Mrs::STATUS_DONE );
			$criteria->addCondition ( 'status !=' . Mrs::STATUS_REJECT );
			
			$mrslist = Mrs::model ()->findAll ( $criteria );
			
			$option .= '<select class="form-control"  id="MrsDetail_mrs_id" name="MrsDetail[mrs_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
	public function actionAjaxItems() {
		$bar_code = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
				
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'status =' . ItemDetail::STATUS_ACTIVE );
			$criteria->compare('item_id', (int) $_POST['item_id']);
			$criteria->order = 'id desc';
			$itemdetail = ItemDetail::model ()->find( $criteria );
			if ($itemdetail) {
				$bar_code = $itemdetail->bar_code;
			} 
			
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		echo $bar_code;
	}
	/* public function actionAjaxItems() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'status =' . ItemDetail::STATUS_ACTIVE );
			$criteria->compare('item_id', (int) $_POST['item_id']);
			
			$itemdetails = ItemDetail::model ()->findAll ( $criteria );
			$option .= '<select class="form-control" onChange="checkTaxes()" id="MrsDetail_item_detaill_id" name="MrsDetail[item_detail_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
	} */
	public function actionAjaxTax() {
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
		$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
		$loggedinuser = Yii::app()->user->model;
		if($loggedinuser->role_id == $role->id){
			$vendor = Vendor::model ()->findByAttributes ( array (
					'create_user_id' => $loggedinuser->id
			) );
			if($vendor){
			$_POST ['vendor_id'] = $vendor->id;
			}
		}
		if (isset ( $_POST ['item_detail_id'] ) && isset ( $_POST ['vendor_id'] )) {
			$itemdetail = ItemDetail::model ()->findByAttributes( array('bar_code'=>$_POST ['item_detail_id'],
					'status'=>ItemDetail::STATUS_ACTIVE
			));
			if($itemdetail){
			
			$item = Item::model ()->findByPk ( $itemdetail->item_id);
			
			$criteria = new CDbCriteria();
			$criteria->order = 'id desc';
			$criteria->addCondition('item_detail_id ='.$item->id);
			$criteria->compare('vendor_id', (int) $_POST['vendor_id']);
			$vendor =  ItemVendor::model()->find($criteria);
			if($vendor->vendor_id == $_POST ['vendor_id'] ){
				$msg = 'success';
			}else{
				$msg = 'failed';
			}
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
			$option .= '<select class="form-control" id="MrsDetail_item_detaill_list_id" name="MrsDetail[tax_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
	public function actionAjaxCreate($id = null) {
		$existmrs = $this->loadModel ( $id, 'Mrs' );
		$model = new MrsDetail ();
		
		if (isset ( $_POST ['MrsDetail'] )) {
			
			$model->setAttributes ( $_POST ['MrsDetail'] );
			$model->outlet_id = $existmrs->outlet_id;
				
			if(isset($_POST ['MrsDetail']['item_id'])){
			$item = Item::model()->findByPk($_POST ['MrsDetail']['item_id']);
			
			if($item->max_qty != ''){
				$model->req_qty = $item->getMaximumQty();
			}else{
				$model->req_qty = '10';
			}
			if($item->min_qty != ''){
				$model->min_qty = $item->getMinimumQty();
			}else{
				$model->min_qty = '10';
			}
			}
			$model->mrs_id = $id;
			if ($model->approved_qty != '')
				$model->bal_qty = ($model->req_qty - $model->approved_qty);
			if ($model->save ()) {
				if ($model->approved_qty != '' && $model->approved_qty != '0') {
					$mrnmodel = Mrn::model ()->findByAttributes ( array (
							'mrs_id' => $model->id,
							'vendor_id' => $model->vendor_id 
					) );
					$updated = true;
					if ($mrnmodel == null) {
						$mrnmodel = new Mrn ();
						$updated = false;
					}
					$mrnmodel->mrs_date = $existmrs->mrs_date;
					$mrnmodel->code = "code";
					$mrnmodel->mrs_update_date = $existmrs->mrs_update_date;
					$mrnmodel->mrs_req_date = $existmrs->mrs_req_date;
					$mrnmodel->outlet_id = $existmrs->outlet_id;
					$mrnmodel->vendor_id = $existmrs->vendor_id;
					$mrnmodel->mrs_id = $existmrs->id;
					$mrnmodel->organization_id = $existmrs->organization_id;
					/* if ($mrnmodel->save ()) {
						if ($updated) {
							$msg = 'MRN is updated';
						} else {
							$msg = 'A new MRN is added';
						}
						$vendor = Vendor::model ()->findByPk ( $model->vendor_id );
						if ($vendor) {
							$to_id = $vendor->create_user_id;
						} else {
							$to_id = $model->vendor_id;
						}
						$type = Notification::TYPE_MRN;
						$model_id = $mrnmodel->id;
						Notification::AddNotification ( $model_id, $msg, $type, $to_id );
						$mrndetailmodel = MrnDetail::model ()->findByAttributes ( array (
								'mrn_id' => $mrnmodel->id,
								'outlet_id' => $model->outlet_id 
						) );
						if ($mrndetailmodel == null) {
							$mrndetailmodel = new MrnDetail ();
						}
						
						$mrndetailmodel->req_qty = $model->req_qty;
						$mrndetailmodel->approved_qty = $model->approved_qty;
						$mrndetailmodel->bal_qty = ($model->req_qty - $model->approved_qty);
						$mrndetailmodel->item_detail_id = $model->item_detail_id;
						$mrndetailmodel->item_id = $model->item_id;
						$mrndetailmodel->mrp = $model->mrp;
						$mrndetailmodel->price = $model->price;
						$mrndetailmodel->sale_rate = $model->sale_rate;
						$mrndetailmodel->discount = $model->discount;
						$mrndetailmodel->discount_amt = $model->discount_amt;
						$mrndetailmodel->other_charge = $model->other_charge;
						$mrndetailmodel->tax_id = $model->tax_id;
						$mrndetailmodel->cgst_per = $model->cgst_per;
						$mrndetailmodel->sgst_per = $model->sgst_per;
						$mrndetailmodel->cess_per = $model->cess_per;
						$mrndetailmodel->igst_per = $model->igst_per;
						$mrndetailmodel->cgst_amt = $model->cgst_amt;
						$mrndetailmodel->sgst_amt = $model->sgst_amt;
						$mrndetailmodel->cess_amt = $model->cess_amt;
						$mrndetailmodel->igst_amt = $model->igst_amt;
						$mrndetailmodel->amount = $model->amount;
						$mrndetailmodel->mrn_id = $mrnmodel->id;
						$mrndetailmodel->outlet_id = $model->outlet_id;
						/* if ($mrndetailmodel->save ()) {
						} else {
							throw new Exception ( "Something went wrong", 500 );
						} 
					} */
				}
				echo 'Data is saved successfully';
			} else {
				print_r ( $model->getErrors () );
				exit ();
				// echo 'Please add valid data';
			}
		} else {
			echo 'Please add valid data';
		}
	}
	public function actionCreate($id = null) {
		$existmrs = $this->loadModel ( $id, 'Mrs' );
		$model = new MrsDetail ();
		
		$this->performAjaxValidation ( $model, 'mrs-detail-form' );
		
		if (isset ( $_POST ['MrsDetail'] )) {
			$model->setAttributes ( $_POST ['MrsDetail'] );
			$model->outlet_id = $existmrs->outlet_id;
			$model->mrs_id = $id;
			if ($model->approved_qty != '')
				$model->bal_qty = ($model->req_qty - $model->approved_qty);
			if ($model->save ()) {
				if ($model->approved_qty != '' && $model->approved_qty != '0') {
					$mrnmodel = Mrn::model ()->findByAttributes ( array (
							'mrs_id' => $model->id,
							'vendor_id' => $model->vendor_id 
					) );
					$updated = true;
					if ($mrnmodel == null) {
						$mrnmodel = new Mrn ();
						$updated = false;
					}
					$mrnmodel->mrs_date = $existmrs->mrs_date;
					$mrnmodel->code = "code";
					$mrnmodel->mrs_update_date = $existmrs->mrs_update_date;
					$mrnmodel->mrs_req_date = $existmrs->mrs_req_date;
					$mrnmodel->outlet_id = $existmrs->outlet_id;
					$mrnmodel->vendor_id = $existmrs->vendor_id;
					$mrnmodel->mrs_id = $existmrs->id;
					$mrnmodel->organization_id = $existmrs->organization_id;
					if ($mrnmodel->save ()) {
						if ($updated) {
							$msg = 'MRN is updated';
						} else {
							$msg = 'A new MRN is added';
						}
						$vendor = Vendor::model ()->findByPk ( $model->vendor_id );
						if ($vendor) {
							$to_id = $vendor->create_user_id;
						} else {
							$to_id = $model->vendor_id;
						}
						$type = Notification::TYPE_MRN;
						$model_id = $mrnmodel->id;
						Notification::AddNotification ( $model_id, $msg, $type, $to_id );
						$mrndetailmodel = MrnDetail::model ()->findByAttributes ( array (
								'mrn_id' => $mrnmodel->id,
								'outlet_id' => $model->outlet_id 
						) );
						if ($mrndetailmodel == null) {
							$mrndetailmodel = new MrnDetail ();
						}
						
						$mrndetailmodel->req_qty = $model->req_qty;
						$mrndetailmodel->approved_qty = $model->approved_qty;
						$mrndetailmodel->bal_qty = ($model->req_qty - $model->approved_qty);
						$mrndetailmodel->item_detail_id = $model->item_detail_id;
						$mrndetailmodel->item_id = $model->item_id;
						$mrndetailmodel->mrp = $model->mrp;
						$mrndetailmodel->price = $model->price;
						$mrndetailmodel->sale_rate = $model->sale_rate;
						$mrndetailmodel->discount = $model->discount;
						$mrndetailmodel->discount_amt = $model->discount_amt;
						$mrndetailmodel->other_charge = $model->other_charge;
						$mrndetailmodel->vat = $model->vat;
						$mrndetailmodel->amount = $model->amount;
						$mrndetailmodel->mrn_id = $mrnmodel->id;
						$mrndetailmodel->outlet_id = $model->outlet_id;
						if ($mrndetailmodel->save ()) {
						} else {
							throw new Exception ( "Something went wrong", 500 );
						}
					}
				}
				$this->redirect ( array (
						'admin',
						'id' => $id 
				) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model,
				'id' => $id 
		) );
	}
	public function actionAssign($id) {
		$model = $this->loadModel ( $id, 'MrsDetail' );
		if ($model) {
			$existmrs = $this->loadModel ( $model->mrs_id, 'Mrs' );
			if (isset ( $_POST ['MrsDetail'] ['vendor_id'] )) {
				$existmrs->item_id = $model->item_id;
				$existmrs->vendor_id = $_POST ['MrsDetail'] ['vendor_id'];
				$existmrs->outlet_id = $existmrs->outlet_id;
				if ($existmrs->AssignMrs ( $id )) {
					$model->status = MrsDetail::STATUS_ASSIGN;
					$model->saveAttributes ( array (
							'status' 
					) );
					$this->redirect ( array (
							'mrsDetail/pending' 
					) );
				}
			}
			
			$this->updateMenuItems ( $model );
			$this->render ( 'assign', array (
					'mrs' => $existmrs,
					'model' => $model 
			) );
		}
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'MrsDetail' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'mrs-detail-form' );
		
		if (isset ( $_POST ['MrsDetail'] )) {
			$model->setAttributes ( $_POST ['MrsDetail'] );
			
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
		$model = $this->loadModel ( $id, 'MrsDetail' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'MrsDetail' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	/* public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new CActiveDataProvider ( 'MrsDetail' );
		$this->render ( 'index', array (
				'dataProvider' => $dataProvider 
		) );
	} */
	public function actionSearch() {
		$model = new MrsDetail ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['MrsDetail'] )) {
			$model->setAttributes ( $_GET ['MrsDetail'] );
			$this->renderPartial ( '_list', array (
					'dataProvider' => $model->search (),
					'model' => $model 
			) );
		}
		
		$this->renderPartial ( '_search', array (
				'model' => $model 
		) );
	}
	public function actionPending($id = null) {
		$model = new MrsDetail ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['MrsDetail'] )) {
			if (isset ( $_POST ['MrsDetail'] ['outlet_id'] )) {
				$_GET ['MrsDetail'] ['outlet_id'] = $_POST ['MrsDetail'] ['outlet_id'];
			}
			if (isset ( $_POST ['MrsDetail'] ['mrs_id'] )) {
				$_GET ['MrsDetail'] ['mrs_id'] = $_POST ['MrsDetail'] ['mrs_id'];
			}
			if (isset ( $_POST ['MrsDetail'] ['mrs_req_date'] )) {
				$_GET ['MrsDetail'] ['mrs_req_date'] = date ( 'Y-m-d', strtotime ( $_POST ['MrsDetail'] ['mrs_req_date'] ) );
			}
		}
		if (isset ( $_GET ['MrsDetail'] ))
			$model->setAttributes ( $_GET ['MrsDetail'] );
		
		$this->render ( 'pending', array (
				'model' => $model 
		) );
	}
	public function actionAdmin($id = null, $mrsid = null) {
		$outlet_id = null;
		$vendor_id = null;
		$start_date = null;
		/* f ($id == null) { */
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			$loggedinuser = Yii::app()->user->model;
			if($loggedinuser->role_id == $role->id){
				$user = Vendor::model ()->findByAttributes ( array (
						'create_user_id' => $loggedinuser->id 
				) );
			} else {
				$user = User::model ()->findByAttributes ( array (
						'id' => $loggedinuser->id 
				) );
			}
	/* 	} else {
			$user = Vendor::model ()->findByPk ( $id );
		} */
		
		$model = new MrsDetail ( 'search' );
		$model->unsetAttributes ();
		
		if ($mrsid == null) {
			$mrsids = $model->getAllMrsOptions ( $user->id );
			if (isset ( $mrsids ['0'] ))
				$mrsid = $mrsids ['0'];
		}
		if($mrsid != null){
			$mrs = Mrs::model()->findByPk($mrsid);
			if($mrs){
				$outlet_id = $mrs->outlet_id;
				$vendor_id = $mrs->vendor_id;
				$start_date = $mrs->mrs_req_date;
			}
		}
		$_GET ['mrsid'] = $mrsid;
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['MrsDetail'] )) {
			
			if (isset ( $_POST ['MrsDetail'] ['outlet_id'] )) {
				$_GET ['MrsDetail'] ['outlet_id'] = $_POST ['MrsDetail'] ['outlet_id'];
			}
			
			if (isset ( $_POST ['MrsDetail'] ['mrs_id'] ) && ($_POST ['MrsDetail'] ['mrs_id'] != '')) {
				$_GET ['MrsDetail'] ['mrs_id'] = $_POST ['MrsDetail'] ['mrs_id'];
				$mrsid = $_POST ['MrsDetail'] ['mrs_id'];
				if($mrsid != null){
					$mrs = Mrs::model()->findByPk($mrsid);
					if($mrs){
						$outlet_id = $mrs->outlet_id;
						$vendor_id = $mrs->vendor_id;
						$start_date = $mrs->mrs_req_date;
					}
				}
				
			}else{
				if (isset($_POST['MrsDetail']['outlet_id']))
				{
					$outlet_id = $_POST['MrsDetail']['outlet_id'];
				}else{
					$outlet_id = null;
				}
				if (isset($_POST['MrsDetail']['vendor_id']))
				{
					$vendor_id = $_POST['MrsDetail']['vendor_id'];
				}else{
					$vendor_id = null;
				}
				
				$start_date = null;
			}
			if (isset ( $_POST ['MrsDetail'] ['vendor_id'] ) && ($_POST ['MrsDetail'] ['vendor_id'] != '')) {
				$_GET ['MrsDetail'] ['vendor_id'] = $_POST ['MrsDetail'] ['vendor_id'];
			}
			if (isset ( $_POST ['MrsDetail'] ['mrs_req_date'] ) && ($_POST ['MrsDetail'] ['mrs_req_date'] != '')) {
				if (isset ( $_POST ['MrsDetail'] ['mrs_id'] ) && ($_POST ['MrsDetail'] ['mrs_id'] != '')) {
					$mrs = Mrs::model()->findByPk($mrsid);
					if($mrs){
						$start_date = $mrs->mrs_req_date;
					}
					$_GET ['MrsDetail'] ['mrs_req_date'] = date ( 'Y-m-d', strtotime ( $start_date ) );
				}else{
					$_GET ['MrsDetail'] ['mrs_req_date'] = date ( 'Y-m-d', strtotime ( $_POST ['MrsDetail'] ['mrs_req_date'] ) );
				}
				
			}
		} else {
			if ($mrsid == null)
				$mrsid = 0;
			$_GET ['MrsDetail'] ['mrs_id'] = $mrsid;
		}
	
		
		if (isset ( $_GET ['MrsDetail'] ))
			$model->setAttributes ( $_GET ['MrsDetail'] );
		
		$this->render ( 'admin', array (
				'model' => $model,
				'user' => $user,
				'mrsid' => $mrsid,
				'outlet_id'=>$outlet_id,
				'vendor_id'=>$vendor_id,
				'start_date'=>$start_date,
		) );
	}
	/*
	 * protected function processActions($model = null)
	 * {
	 * parent::processActions($model);
	 * //$this->actions [] = array('label'=>Yii::t('app', 'Add Skill'), 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new MrsDetail ();
		
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
					if (isset ( $_GET ['id'] )) {
						$id = $_GET ['id'];
					} else {
						$id = Yii::app ()->user->id;
					}
					if (isset ( $_GET ['mrsid'] )) {
						$mrsid = $_GET ['mrsid'];
					} else {
						$mrsid = null;
					}
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Add Item' ),
							'url' => array (
									'create',
									'id' => $mrsid 
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
	public function actionAjaxupdate($id) {
		// $act = $_GET['act'];
		$mrsIdAll = $_POST;
		$set = true;
		$save = true;
		$save_val = false;
		$mrn_save = false;
		if (isset ( $_POST ['status'] ) && ($_POST ['status'] == 1)) {
			foreach ( $mrsIdAll as $qty ) {
				if (isset ( $mrsIdAll ['qty'] ))
					$qtys = $mrsIdAll ['qty'];
			}
			$transaction = Yii::app ()->db->beginTransaction ();
			try {
				
				if (count ( $qtys ) == 1) {
					
				foreach ( $qtys as $key => $qty ) {
				
					if(isset($_POST ['adjust_qty'] [$key]) && $_POST ['adjust_qty'] [$key] != '0.00'){
						$save = false;
						$mrs = $this->loadModel ( $id, 'Mrs' );
						
						$mrs->status = Mrs::STATUS_DONE;
						$mrs->saveAttributes(array('status'));
						$model = $this->loadModel ( $key, 'MrsDetail' );
						$model->status = MrsDetail::STATUS_DONE;
						$model->saveAttributes(array('status'));
						
						$mrsadjust = MrsAdjust::model ()->findByAttributes ( array (
								'item_id' => $model->item_id,
								'item_detail_id' => $model->item_detail_id,
								'status'=>MrsAdjust::STATUS_PENDING
						) );
						if($mrsadjust == null){
						$mrsadjust = new MrsAdjust();
						}
						$mrsadjust->item_id = $model->item_id;
						$mrsadjust->item_detail_id = $model->item_detail_id;
						$mrsadjust->qty = $_POST ['adjust_qty'] [$key];
						$mrsadjust->mrs_detail_id = $model->id;
						$mrsadjust->type_id = $_POST ['adjust_type'] [$key];
						
						if($mrsadjust->save()){
							$adjusteditem = $this->loadModel ( $mrsadjust->item_id, 'Item' );
							$adjusteditem->adjustment_time = date('Y-m-d H:i:s');
							$adjusteditem->saveAttributes(array('adjustment_time'));
						}
					}
				}
				}
			if (count ( $qtys ) > 0 && ($save == true)) {
				foreach ( $qtys as $key => $qty ) {
				if(isset($_POST ['adjust_qty'] [$key])){
					if($_POST ['adjust_qty'] [$key] == '0.00'){
						$save_val = true;
					}
				}else{
					$save_val = true;
				}
				}
				$mrs = $this->loadModel ( $id, 'Mrs' );
				if (isset ( $_POST ['gross_amt'] ))
					$mrs->gross_amt = $_POST ['gross_amt'];
				if (isset ( $_POST ['total_discount'] ))
					$mrs->total_discount = $_POST ['total_discount'];
				if (isset ( $_POST ['tax_amount'] ))
					$mrs->tax_amount = $_POST ['tax_amount'];
				if (isset ( $_POST ['bill_amount'] ))
					$mrs->bill_amount = $_POST ['bill_amount'];
				
				$mrnmodel = Mrn::model ()->findByAttributes ( array (
						'mrs_id' => $id,
						'vendor_id' => $mrs->vendor_id 
				) );
				$updated = true;
				if ($mrnmodel == null) {
					$mrnmodel = new Mrn ();
					$updated = false;
				}
				if (isset ( $_POST ['gross_amt'] ))
					$mrnmodel->gross_amt = $_POST ['gross_amt'];
				if (isset ( $_POST ['total_discount'] ))
					$mrnmodel->total_discount = $_POST ['total_discount'];
				if (isset ( $_POST ['tax_amount'] ) && ($_POST ['tax_amount'] != 'NaN'))
					$mrnmodel->tax_amount = $_POST ['tax_amount'];
				if (isset ( $_POST ['bill_amount'] ))
					$mrnmodel->bill_amount = $_POST ['bill_amount'];
				$mrnmodel->mrs_date = $mrs->mrs_date;
				$mrnmodel->code = "code";
				$mrnmodel->mrs_update_date = $mrs->mrs_update_date;
				$mrnmodel->mrs_req_date = $mrs->mrs_req_date;
				$mrnmodel->outlet_id = $mrs->outlet_id;
				$mrnmodel->status = Mrn::STATUS_UNAPPROVED;
				$mrnmodel->vendor_id = $mrs->vendor_id;
				$mrnmodel->mrs_id = $id;
				$mrnmodel->organization_id = $mrs->organization_id;
				if($save_val == true){
				if ($mrnmodel->save ()) {
					$mrn_save = true;
				}
				}else{
					$mrn_save = true;
				}
				if ($mrn_save == true) {
					if ($updated) {
						$msg = 'MRN is updated';
					} else {
						$msg = 'A new MRN is added';
					}
					$email = '';
					$vendor = Vendor::model ()->findByPk ( $mrnmodel->vendor_id );
					if ($vendor) {
						$to_id = $vendor->create_user_id;
						$vendoruser = User::model ()->findByAttributes ( array (
								'id' => $vendor->create_user_id 
						) );
						if ($vendoruser) {
							$email = $vendoruser->email;
						}
					} else {
						$to_id = $mrnmodel->vendor_id;
					}
					$type = Notification::TYPE_MRN;
					$model_id = $mrnmodel->id;
					Notification::AddNotification ( $model_id, $msg, $type, $to_id );
					
					if ($email != '') {
						$from = Yii::app ()->params ['mail_email'];
						$to = $email;
						$subject = 'A new MRN is added:';
						
						$view = $this->renderPartial ( '/mail/mrn_added', array (
								'mrnmodel' => $mrnmodel 
						), true );
						
						// $mrnmodel->mailsend ( $to, $from, $subject, $view );
					}
					
					$status = Mrs::STATUS_PENDING;
					foreach ( $qtys as $key => $qty ) {
						$model = $this->loadModel ( $key, 'MrsDetail' );
						$mrsmodel = $this->loadModel ( $model->mrs_id, 'Mrs' );
						$mrn_adjust_val = false;
						if(isset($mrsIdAll ['adjust_qty'] [$key])){
							if($mrsIdAll ['adjust_qty'] [$key] == '0.00'){
								$mrn_adjust_val = true;
							}
						}else{
							$mrn_adjust_val = true;
						}
						
						if($mrn_adjust_val == true){
						$model->mrp = $_POST ['mrp'] [$key];
						$model->status = MrsDetail::STATUS_DONE;
						$status = Mrs::STATUS_DONE;
						 if ($qty == '0') {
						 
							$criteria = new CDbCriteria();
							$criteria->addCondition('status ='.Mrs::STATUS_PENDING);
							$criteria->addCondition('vendor_id ='.$mrs->vendor_id);
							$criteria->addCondition('id !='.$id);
							$newmrs = Mrs::model()->find($criteria);
							
							if($newmrs == null){
								$newmrs = new Mrs();
							}
								$newmrs->code = 'ddd';
								
								$newmrs->mrs_date = date('Y-m-d');
								$newmrs->mrs_req_date = date('Y-m-d');
								$newmrs->outlet_id = $mrs->outlet_id;
							//	$newmrs->tax_id = $mrs->tax_id;
								$newmrs->vendor_id = $mrs->vendor_id;
								$newmrs->organization_id = $mrs->organization_id;
								
								if($newmrs->save()){
									$newmrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$model->item_detail_id,
											'mrs_id'=>$newmrs->id
									));
									if($newmrsdetail == null){
										$newmrsdetail = new MrsDetail();
									}
									$newmrsdetail->price = $model->price;
									$newmrsdetail->req_qty = $model->min_qty;
									$newmrsdetail->req_qty = $model->req_qty;
									$newmrsdetail->approved_qty =$model->approved_qty;
									$newmrsdetail->cgst_per =$model->cgst_per;
									$newmrsdetail->sgst_per =$model->sgst_per;
									$newmrsdetail->cess_per =$model->cess_per;
									$newmrsdetail->igst_per =$model->igst_per;
									$newmrsdetail->cgst_amt =$model->cgst_amt;
									$newmrsdetail->sgst_amt =$model->sgst_amt;
									$newmrsdetail->cess_amt =$model->cess_amt;
									$newmrsdetail->igst_amt =$model->igst_amt;
									$newmrsdetail->tax_id = $model->tax_id;
									$newmrsdetail->item_detail_id = $model->item_detail_id;
									$newmrsdetail->item_id = $model->item_id;
									$newmrsdetail->outlet_id = $model->outlet_id;
									$newmrsdetail->mrp = $model->mrp;
									$newmrsdetail->sale_rate = $model->sale_rate;
									$newmrsdetail->mrs_id = $newmrs->id;
									$newmrsdetail->amount = $model->amount;
									$newmrsdetail->margin = $model->margin;
									if($newmrsdetail->save()){
										
									}else{
										$set = false;
									}
								}else{
									$set = false;
								}
							
						} 
						if (isset ( $mrsIdAll ['mrp'] )) {
							$model->mrp = $mrsIdAll ['mrp'] [$key];
						}
						if (isset ( $mrsIdAll ['maxData'] )) {
							$model->req_qty = $mrsIdAll ['maxData'] [$key];
						}
						if (isset ( $mrsIdAll ['price'] )) {
							$model->price = $mrsIdAll ['price'] [$key];
						}
						if (isset ( $mrsIdAll ['salerate'] )) {
							$model->sale_rate = $mrsIdAll ['salerate'] [$key];
						}
						if (isset ( $mrsIdAll ['discount'] )) {
							$model->discount = $mrsIdAll ['discount'] [$key];
						}
						if (isset ( $mrsIdAll ['discount_amt'] )) {
							$model->discount_amt = $mrsIdAll ['discount_amt'] [$key];
						}
						if (isset ( $mrsIdAll ['discount1'] )) {
							$model->discount1 = $mrsIdAll ['discount1'] [$key];
						}
						if (isset ( $mrsIdAll ['discount_amt1'] )) {
							$model->discount_amt1 = $mrsIdAll ['discount_amt1'] [$key];
						}
						if (isset ( $mrsIdAll ['cgstData'] )) {
							$model->cgst_per = $mrsIdAll ['cgstData'] [$key];
						}
						if (isset ( $mrsIdAll ['sgstData'] )) {
							$model->sgst_per = $mrsIdAll ['sgstData'] [$key];
						}
						if (isset ( $mrsIdAll ['cessData'] )) {
							$model->cess_per = $mrsIdAll ['cessData'] [$key];
						}
						if (isset ( $mrsIdAll ['cgstamtData'] )) {
							$model->cgst_amt = $mrsIdAll ['cgstamtData'] [$key];
						}
						if (isset ( $mrsIdAll ['sgstamtData'] )) {
							$model->sgst_amt = $mrsIdAll ['sgstamtData'] [$key];
						}
						if (isset ( $mrsIdAll ['cessamtData'] )) {
							$model->cess_amt = $mrsIdAll ['cessamtData'] [$key];
						}
						if (isset ( $mrsIdAll ['igstData'] )) {
							$model->igst_per = $mrsIdAll ['igstData'] [$key];
						}
						if (isset ( $mrsIdAll ['igstamtData'] )) {
							$model->igst_amt = $mrsIdAll ['igstamtData'] [$key];
						}
						if (isset ( $mrsIdAll ['other_charge'] )) {
							$model->other_charge = $mrsIdAll ['other_charge'] [$key];
						}
						if (isset ( $mrsIdAll ['amount'] )) {
							$model->amount = $mrsIdAll ['amount'] [$key];
						}
						if (isset ( $mrsIdAll ['marginData'] )) {
							$model->margin = $mrsIdAll ['marginData'] [$key];
						}
						if (isset ( $mrsIdAll ['qty'] )) {
							$model->approved_qty = $mrsIdAll ['qty'] [$key];
							$model->bal_qty = ($model->req_qty - $model->approved_qty );
						}
						if ($model->save ()) {
							if ($qty != '' && $qty != '0') {
								$mrndetailmodel = MrnDetail::model ()->findByAttributes ( array (
										'mrn_id' => $mrnmodel->id,
										'outlet_id' => $mrnmodel->outlet_id,
										'item_id' => $model->item_id,
										'item_detail_id' => $model->item_detail_id 
								) );
								if ($mrndetailmodel == null) {
									$mrndetailmodel = new MrnDetail ();
								}
								
								$mrndetailmodel->req_qty = $model->req_qty;
								$mrndetailmodel->min_qty = $model->min_qty;
								$mrndetailmodel->approved_qty = $qty;
								$mrndetailmodel->bal_qty = ($model->req_qty - $qty);
								$mrndetailmodel->item_detail_id = $model->item_detail_id;
								$mrndetailmodel->item_id = $model->item_id;
								$mrndetailmodel->mrp = $model->mrp;
								$mrndetailmodel->price = $model->price;
								$mrndetailmodel->sale_rate = $model->sale_rate;
								$mrndetailmodel->discount = $model->discount;
								$mrndetailmodel->discount_amt = $model->discount_amt;
								$mrndetailmodel->discount1 = $model->discount1;
								$mrndetailmodel->discount_amt1 = $model->discount_amt1;
								
								$mrndetailmodel->other_charge = $model->other_charge;
								$mrndetailmodel->status = MrnDetail::STATUS_PENDING;
								$mrndetailmodel->cgst_per = $model->cgst_per;
								
								$mrndetailmodel->sgst_per = $model->sgst_per;
								
								$mrndetailmodel->cess_per = $model->cess_per;
								
								$mrndetailmodel->cgst_amt = $model->cgst_amt;
								
								$mrndetailmodel->sgst_amt = $model->sgst_amt;
								
								$mrndetailmodel->cess_amt = $model->cess_amt;
								$mrndetailmodel->igst_amt = $model->igst_amt;
								$mrndetailmodel->igst_per = $model->igst_per;
								$mrndetailmodel->tax_id = $model->tax_id;
								$mrndetailmodel->amount = $model->amount;
								$mrndetailmodel->mrn_id = $mrnmodel->id;
								$mrndetailmodel->outlet_id = $model->outlet_id;
							
								$mrndetailmodel->margin = $model->margin;
								
								if ($mrndetailmodel->save ()) {
								} else {
									$set = false;
									throw new Exception ( "Something went wrong", 500 );
								}
							}
						} 

						else {
							$set = false;
							throw new Exception ( "Something went wrong", 500 );
						}
					}else{
						
						$model = $this->loadModel ( $key, 'MrsDetail' );
						$model->status = MrsDetail::STATUS_DONE;
						$model->saveAttributes(array('status'));
						if(isset($mrsIdAll ['adjust_qty'] [$key])){
						$mrsadjust = MrsAdjust::model ()->findByAttributes ( array (
								'item_id' => $model->item_id,
								'item_detail_id' => $model->item_detail_id,
								'status'=>MrsAdjust::STATUS_PENDING
						) );
						if($mrsadjust == null){
							$mrsadjust = new MrsAdjust();
						}
						$mrsadjust->item_id = $model->item_id;
						
						$mrsadjust->item_detail_id = $model->item_detail_id;
						
						$mrsadjust->qty = $mrsIdAll ['adjust_qty'] [$key];
						
						$mrsadjust->mrs_detail_id = $model->id;
						$mrsadjust->type_id = $mrsIdAll ['adjust_type'] [$key];
						
						if($mrsadjust->save()){
							$adjusteditem = $this->loadModel ( $mrsadjust->item_id, 'Item' );
							$adjusteditem->adjustment_time = date('Y-m-d H:i:s');
							$adjusteditem->saveAttributes(array('adjustment_time'));
						}else{
							
						}
						}
					}
					}
					
					if($save_val == false){
						$mrs = $this->loadModel ( $id, 'Mrs' );
							
						$mrs->status = Mrs::STATUS_DONE;
						$mrs->saveAttributes(array('status'));
					}else{
					$mrs->status = $status;
					if($mrs->save ()){
						
					}else{
						$set = false;
					}
					}
				}else{
					$set = false;
				}
			}
			
			if ($set == true) {
				$transaction->commit ();
				
			} else {
				$transaction->rollback ();
				
			}
			} catch ( Exception $e ) {
				$transaction->rollback ();
			}
			
		} else {
			$mrs = Mrs::model ()->findByPk ( $id );
			
			if ($mrs && ($mrs->status != Mrs::STATUS_DONE)) {
				$mrs->status = Mrs::STATUS_REJECT;
				$mrs->saveAttributes ( array (
						'status' 
				) );
				$mrsdetailmodels = MrsDetail::model ()->findAllByAttributes ( array (
						'mrs_id' => $id 
				) );
				if ($mrsdetailmodels) {
					foreach ( $mrsdetailmodels as $mrsdetailmodel ) {
						$mrsdetailmodel->status = MrsDetail::STATUS_REJECT;
						$mrsdetailmodel->saveAttributes ( array (
								'status' 
						) );
					}
					
					$msg = 'MRS is rejected';
					
					$vendor = Vendor::model ()->findByPk ( $mrs->vendor_id );
					if ($vendor) {
						$to_id = $vendor->create_user_id;
					} else {
						$to_id = $mrs->vendor_id;
					}
					$type = Notification::TYPE_MRS;
					$model_id = $mrs->id;
					Notification::AddNotification ( $model_id, $msg, $type, $to_id );
				}
				
			}
		}
	}
}