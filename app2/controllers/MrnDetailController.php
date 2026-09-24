<?php
namespace app\controllers;

use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemTax;
use app\models\ItemVendor;
use app\models\Mrn;
use app\models\MrnDetail;
use app\models\Mrs;
use app\models\MrsDetail;
use app\models\Notification;
use app\models\PurchaseOrder;
use app\models\PurchaseOrderDetail;
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
 * Yii 2 port of protected/controllers/MrnDetailController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class MrnDetailController extends BaseUiController {



	public function isAllowed($model) 
	{
		return $model->isAllowed();
	}
	
	
	public function actionSendEmail($id)
    {
		
        $mrn = $this->loadModel($id, Mrn::class);

        # mPDF
        $mPDF1 = new \Mpdf\Mpdf(['tempDir' => Yii::getAlias('@runtime')]);

        # You can easily override default constructor's params
        $mPDF1 = new \Mpdf\Mpdf(['format' => 'A4', 'tempDir' => Yii::getAlias('@runtime')]);

        # renderPartial (only 'view' of current controller)
		
        $mPDF1->WriteHTML($this->renderPartial('/mrn/_pdf', [
            'mrn' => $mrn,
            'poid' => $id
        ], true));

      
        $mPDF1->Output('wdir/uploads/pdf/filename.pdf', 'F');

        $vendor = Vendor::findOne($mrn->vendor_id);
        if ($vendor) {
           
                $from = (Yii::$app->params['smtp_from_address'] ?? null);
				
               $to="cs@soulbowl.in";
				
				//$to="kritig@outlinesystemsindia.com";
               
                $subject = "MATERIAL RECEIPT NOTE";

                $view = $this->renderPartial('/mail/mrn_report', [
                    'pomodel' => $mrn
                ], true);
				
			

              $maill=  $mrn->mailsend($to, $from, $subject, $view);
		
        }
        return $this->redirect([
            'admin'
        ]);
    }
	
	
	
	/*public function actionAjaxItems() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
	
			$criteria = new CDbCriteria();
			$criteria->addCondition('status ='.UserRole::STATUS_ACTIVE);
			$criteria->addCondition('item_id ='.$_POST ['item_id']);
				
			$itemdetails = ItemDetail::model()->findAll($criteria);
			$option .= '<select class="form-control" id="MrnDetail_item_detaill_id" onChange="checkTaxes()"  name="MrnDetail[item_detail_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
	}*/
	public function actionAjaxItems() {
		$bar_code = '';
		$alreadypermissions = [];
		if (isset ( $_POST ['item_id'] )) {
				
			$query = ItemDetail::find();
			$query->andWhere('status =' . ItemDetail::STATUS_ACTIVE);
			$query->andWhere('item_id =' . $_POST ['item_id']);
			$query->orderBy(['id' => SORT_DESC]);
			$itemdetail = $query->one();
			if ($itemdetail) {
				$bar_code = $itemdetail->bar_code;
			} 
			
		} else {
			// $option .= '<option value="">-Select-</option>';
		}
		echo $bar_code;
	}
	public function actionAjaxMrnNo() {
		$option = '';
		$alreadypermissions = [];
		$user =Yii::$app->user->model;
		if ($user) {
			$role_id = $user->role_id;
			if($role_id == 6){
				$vendor = Vendor::findOne(['create_user_id'=>$user->id]);
				if($vendor){
					$_POST ['vendor_id'] = $vendor->id;
				}
			}
		}
		if (isset ( $_POST ['vendor_id'] )) {
	
			$query = Mrn::find();
        $query->orderBy(['id' => SORT_DESC]);
			$query->andWhere('vendor_id ='.$_POST ['vendor_id']);
			$query->andWhere('status !='.Mrs::STATUS_DONE);
			$mrslist = $query->all();
				
			$option .= '<select class="form-control"  id="MrnDetail_mrn_id" name="MrnDetail[mrn_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
		$model = $this->loadModel($id);
	
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');

		//$this->processActions($model);	
		$this->updateMenuItems($model);
		return $this->render('view', [
			'model' => $model
		]);
	}
	/* public function actionAjaxTax() {
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
		$attr = '';
		if (isset ( $_POST ['item_id'] ) && isset ( $_POST ['item_detail_id'] )) {
	
			$item = Item::findOne($_POST ['item_id'] );
			$itemdetail = ItemDetail::findOne( $_POST ['item_detail_id'] );
			if($itemdetail){
				$tax = Tax::findOne($itemdetail->tax_id);
			}else{
				$itemTax = ItemTax::findOne( array (
						'item_detail_id' => $_POST ['item_detail_id']
				) );
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
			
			$taxes = Tax::find()->all();
			$option .= '<select class="form-control"  id="MrnDetail_item_detaill_list_id" name="MrnDetail[tax_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
		$data['options'] = $option;
		$data['cgst'] = $cgst;
		$data['sgst'] = $sgst;
		$data['cess'] = $cess;
		$data['igst'] = $igst;
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
		echo json_encode($data);
	
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
		$alreadypermissions = [];
		$tax = null;
		$item = null;
		$attr = "";
		$msg = 'Inactive';
		$role = UserRole::findOne(['title'=>'Vendor']);
		$loggedinuser = Yii::$app->user->model;
		if($loggedinuser->role_id == $role->id){
			$vendor = Vendor::findOne( [
					'create_user_id' => $loggedinuser->id
			] );
			if($vendor){
				$_POST ['vendor_id'] = $vendor->id;
			}
		}
		if (isset ( $_POST ['item_detail_id'] ) && isset ( $_POST ['vendor_id'] )) {
			$itemdetail = ItemDetail::findOne( ['bar_code'=>$_POST ['item_detail_id'],
					'status'=>ItemDetail::STATUS_ACTIVE
			]);
			if($itemdetail){
					
				$item = Item::findOne( $itemdetail->item_id);
					
				$query = ItemVendor::find();
				$query->orderBy(['id' => SORT_DESC]);
				$query->andWhere('item_detail_id ='.$item->id);
				$query->andWhere('vendor_id ='.$_POST ['vendor_id']);
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
					
				$taxes = Tax::find()->all();
				$option .= '<select class="form-control" id="MrnDetail_item_detaill_list_id" name="MrnDetail[tax_id]"><option value="" id="ckbCheckAll">-Select-</option>';
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
	public function actionAjaxCreate($id = null)
	{
		$existmrn = $this->loadModel($id, Mrn::class);
		$model = new MrnDetail;
	
		//$this->performAjaxValidation($model, 'mrn-detail-form');
	
		if (isset($_POST['MrnDetail'])) {
			$model->load($_POST, 'MrnDetail');
			$model->outlet_id = $existmrn->outlet_id;
			$model->mrn_id = $id;
			$model->item_id  = $_POST['MrnDetail']['item_id'];
			if(isset($_POST ['MrnDetail']['item_id'])){
				$item = Item::findOne($_POST ['MrnDetail']['item_id']);
					
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
			if($model->approved_qty != '')
				$model->bal_qty = ($model->req_qty - $model->approved_qty);
				if ($model->save()) {
					
					if($model->approved_qty != '' && $model->approved_qty != '0'){
						$pomodel = PurchaseOrder::findOne(['mrn_id'=>$model->id,'vendor_id'=>$model->vendor_id]);
						$updated = true;
						if($pomodel == null){
							$pomodel = new PurchaseOrder();
							$updated = false;
						}
						$pomodel->start_date = date('Y-m-d');
						$pomodel->code = "code";
						$pomodel->outlet_id = $existmrn->outlet_id;
						$pomodel->vendor_id = $existmrn->vendor_id;
						$pomodel->mrn_id = $existmrn->id;
						$pomodel->organization_id = $existmrn->organization_id;
						/* if($pomodel->save()){
							if($updated){
								$msg = 'PurchaseOrder is updated';
							}else{
								$msg = 'A new PurchaseOrder is added';
							}
							$vendor = Vendor::findOne($model->vendor_id);
							if($vendor){
								$to_id = $vendor->create_user_id;
							}else{
								$to_id = $model->vendor_id;
							}
							$type = Notification::TYPE_PO;
							$model_id = $pomodel->id;
							Notification::AddNotification($model_id,$msg,$type,$to_id);
							$podetailmodel = PurchaseOrderDetail::findOne(array('purchase_order_id'=>$pomodel->id,'outlet_id'=>$model->outlet_id));
							if($podetailmodel == null){
								$podetailmodel = new PurchaseOrderDetail();
							}
								
							$podetailmodel->req_qty = $model->req_qty;
							$podetailmodel->approved_qty = $model->approved_qty;
							$podetailmodel->bal_qty = ($model->req_qty - $model->approved_qty);
							$podetailmodel->item_detail_id = $model->item_detail_id;
							$podetailmodel->item_id = $model->item_id;
							$podetailmodel->mrp = $model->mrp;
							$podetailmodel->price = $model->price;
							$podetailmodel->sale_rate = $model->sale_rate;
							$podetailmodel->discount = $model->discount;
							$podetailmodel->discount_amt = $model->discount_amt;
							$podetailmodel->other_charge = $model->other_charge;
							$podetailmodel->tax_id = $model->tax_id;
							$podetailmodel->cgst_per = $model->cgst_per;
							$podetailmodel->sgst_per = $model->sgst_per;
							$podetailmodel->cess_per = $model->cess_per;
							$podetailmodel->igst_per = $model->igst_per;
							$podetailmodel->cgst_amt = $model->cgst_amt;
							$podetailmodel->sgst_amt = $model->sgst_amt;
							$podetailmodel->cess_amt = $model->cess_amt;
							$podetailmodel->igst_amt = $model->igst_amt;
							$podetailmodel->amount = $model->amount;
							$podetailmodel->purchase_order_id = $pomodel->id;
							$podetailmodel->outlet_id =$model->outlet_id;
							if($podetailmodel->save()){
	
							}else{
								
								throw new \Exception("Something went wrong",500);
							}
								
						} */
	
					}
					echo 'Data is saved successfully';
				}
				}else{
					echo 'Please add valid data';
				}
		
	}
	public function actionCreate($id = null)
	{
		$existmrn = $this->loadModel($id, Mrn::class);
		$model = new MrnDetail;
	
		$this->performAjaxValidation($model, 'mrn-detail-form');
	
		if (isset($_POST['MrnDetail'])) {
			$model->load($_POST, 'MrnDetail');
			$model->outlet_id = $existmrn->outlet_id;
			$model->mrn_id = $id;
			if($model->approved_qty != '')
			$model->bal_qty = ($model->req_qty - $model->approved_qty);
			if ($model->save()) {
				if($model->approved_qty != '' && $model->approved_qty != '0'){
					$pomodel = PurchaseOrder::findOne(['mrn_id'=>$model->id,'vendor_id'=>$model->vendor_id]);
					$updated = true;
					if($pomodel == null){
						$pomodel = new PurchaseOrder();
						$updated = false;
					}
					$pomodel->start_date = date('Y-m-d');
					$pomodel->code = "code";
					$pomodel->outlet_id = $existmrs->outlet_id;
					$pomodel->vendor_id = $existmrs->vendor_id;
					$pomodel->mrs_id = $existmrs->id;
					$pomodel->organization_id = $existmrs->organization_id;
					if($pomodel->save()){
					if($updated){
						$msg = 'PurchaseOrder is updated';
						}else{
							$msg = 'A new PurchaseOrder is added';
						}
						$vendor = Vendor::findOne($model->vendor_id);
						if($vendor){
							$to_id = $vendor->create_user_id;
						}else{
							$to_id = $model->vendor_id;
						}
						$type = Notification::TYPE_PO;
						$model_id = $pomodel->id;
						Notification::AddNotification($model_id,$msg,$type,$to_id);
						$podetailmodel = PurchaseOrderDetail::findOne(['purchase_order_id'=>$pomodel->id,'outlet_id'=>$model->outlet_id]);
						if($podetailmodel == null){
							$podetailmodel = new PurchaseOrderDetail();
						}
							
						$podetailmodel->req_qty = $model->req_qty;
						$podetailmodel->approved_qty = $model->approved_qty;
						$podetailmodel->bal_qty = ($model->req_qty - $model->approved_qty);
						$podetailmodel->item_detail_id = $model->item_detail_id;
						$podetailmodel->item_id = $model->item_id;
						$podetailmodel->mrp = $model->mrp;
						$podetailmodel->price = $model->price;
						$podetailmodel->sale_rate = $model->sale_rate;
						$podetailmodel->discount = $model->discount;
						$podetailmodel->discount_amt = $model->discount_amt;
						$podetailmodel->other_charge = $model->other_charge;
						$podetailmodel->vat = $model->vat;
						$podetailmodel->amount = $model->amount;
						$podetailmodel->purchase_order_id = $pomodel->id;
						$podetailmodel->outlet_id =$model->outlet_id;
						if($podetailmodel->save()){
	
						}else{
							throw new \Exception("Something went wrong",500);
						}
							
					}
	
				}
				return $this->redirect(['admin', 'id' => $id]);
			}
		}
		$this->updateMenuItems($model);
		return $this->render('create', [ 'model' => $model,'id'=>$id]);
	}

	public function actionUpdate($id) 
	{
		$model = $this->loadModel($id);
		
		//if( !($this->isAllowed ( $model)))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		$this->performAjaxValidation($model, 'mrn-detail-form');

		if (isset($_POST['MrnDetail'])) {
			$model->load($_POST, 'MrnDetail');

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

	public function actionIndex($id = null,$mrnid= null)
	{
		$model = new MrnDetail(['scenario' => 'search']);
		if( !($model->checkPermission ('mrnDetail/index')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		
		if($id == null){
			$loggedinuser = Yii::$app->user->model;
			if($loggedinuser->role_id != 1){
				$user = Vendor::findOne(['create_user_id'=>$loggedinuser->id]);
			}else{
				$user = User::findOne(['id'=>$loggedinuser->id]);
			}
		}else{
			$user = Vendor::findOne($id);
		}
		$this->updateMenuItems($model);
	
		if($mrnid == null){
			$mrnids = $model->getAllMrnOptions($user->id);
	
			if(isset($mrnids['0']))
				$mrnid = $mrnids['0'];
		}
	
		$_GET['mrnid'] = $mrnid;
	
	
		if (isset($_POST['MrnDetail']))
		{
			if (isset($_POST['MrnDetail']['outlet_id']))
			{
				$_GET['MrnDetail']['outlet_id'] = $_POST['MrnDetail']['outlet_id'];
			}
			if (isset($_POST['MrnDetail']['mrn_id']))
			{
				$_GET['MrnDetail']['mrn_id'] = $_POST['MrnDetail']['mrn_id'];
			}
			if (isset($_POST['MrnDetail']['mrs_req_date']))
			{
				$_GET['MrnDetail']['mrs_req_date'] = date('Y-m-d',strtotime($_POST['MrnDetail']['mrs_req_date']));
			}
	
		}else{
			if($mrnid == null)
				$mrnid = 0;
				$_GET['MrnDetail']['mrn_id'] = $mrnid;
		}
		if (isset($_GET['MrnDetail']))
			$model->load($_GET, 'MrnDetail');
	
			return $this->render('index', [
					'model' => $model,'user'=>$user,'mrnid'=>$mrnid
			]);
	}
	
	public function actionSearch()
	{
		$model = new MrnDetail(['scenario' => 'search']);
		$this->updateMenuItems($model);
	
		if (isset($_GET['MrnDetail']))
		{
			$model->load($_GET, 'MrnDetail');
			return $this->renderPartial('_list', [
					'dataProvider' => $model->search(),
					'model' => $model,
			]);
		}
			
		return $this->renderPartial('_search', [
				'model' => $model,
		]);
	}
	public function actionAdmin($id = null,$mrnid= null) 
	{
		$vendor_id = null;
		$outlet_id = null;
		$start_date = null;
		
			$role = UserRole::findOne(['title'=>'Vendor']);
			$loggedinuser = Yii::$app->user->model;
			if($loggedinuser->role_id == $role->id){
				$user = Vendor::findOne(['create_user_id'=>$loggedinuser->id]);
			}else{
				$user = User::findOne(['id'=>$loggedinuser->id]);
			}
		
		$model = new MrnDetail(['scenario' => 'search']);
		if( !($model->checkPermission ('mrnDetail/admin')))	throw new ForbiddenHttpException('You are not allowed to access this page.');
		$this->updateMenuItems($model);
	
		if($mrnid == null){
			$mrnids = $model->getAllMrnOptions($user->id);
		
			if(isset($mrnids['0']))
				$mrnid = $mrnids['0'];
		}
		if($mrnid != null){
			$mrn = Mrn::findOne($mrnid);
			if($mrn){
				$outlet_id = $mrn->outlet_id;
				$vendor_id = $mrn->vendor_id;
				$start_date = $mrn->mrs_req_date;
			}
		}
		$_GET['mrnid'] = $mrnid;
	
	
		if (isset($_POST['MrnDetail']))
		{
			if (isset($_POST['MrnDetail']['outlet_id']))
			{
			$_GET['MrnDetail']['outlet_id'] = $_POST['MrnDetail']['outlet_id'];
			}
			if (isset($_POST['MrnDetail']['mrn_id']) && ($_POST['MrnDetail']['mrn_id'] != ''))
			{
				$_GET['MrnDetail']['mrn_id'] = $_POST['MrnDetail']['mrn_id'];
				$mrnid = $_POST['MrnDetail']['mrn_id'];
				
				if($mrnid != null){
					$mrn = Mrn::findOne($mrnid);
					if($mrn){
						$outlet_id = $mrn->outlet_id;
						$vendor_id = $mrn->vendor_id;
						$start_date = $mrn->mrs_req_date;
					}
				}
				}else{
					if (isset($_POST['MrnDetail']['outlet_id']))
					{
						$outlet_id = $_POST['MrnDetail']['outlet_id'];
					}else{
						$outlet_id = null;
					}
					if (isset($_POST['MrnDetail']['vendor_id']))
					{
						$vendor_id = $_POST['MrnDetail']['vendor_id'];
					}else{
						$vendor_id = null;
					}
				
					$start_date = null;
				}
				if (isset ( $_POST ['MrnDetail'] ['vendor_id'] ) && ($_POST ['MrnDetail'] ['vendor_id'] != '')) {
					$_GET ['MrnDetail'] ['vendor_id'] = $_POST ['MrnDetail'] ['vendor_id'];
				}
			if (isset($_POST['MrnDetail']['mrn_id']) && ($_POST['MrnDetail']['mrn_id'] != ''))
			{
				$_GET['MrnDetail']['mrn_id'] = $_POST['MrnDetail']['mrn_id'];
			}
			if (isset ( $_POST ['MrnDetail'] ['mrs_req_date'] ) && ($_POST ['MrnDetail'] ['mrs_req_date'] != '')) {
				if (isset ( $_POST ['MrnDetail'] ['mrs_id'] ) && ($_POST ['MrnDetail'] ['mrs_id'] != '')) {
					$mrs = Mrn::findOne($mrsid);
					if($mrs){
						$start_date = $mrs->mrs_req_date;
					}
					$_GET ['MrnDetail'] ['mrs_req_date'] = date ( 'Y-m-d', strtotime ( $start_date ) );
				}else{
					$_GET ['MrnDetail'] ['mrs_req_date'] = date ( 'Y-m-d', strtotime ( $_POST ['MrnDetail'] ['mrs_req_date'] ) );
				}
			
			}
			
				
		}else{
		  if($mrnid == null)
		  	$mrnid = 0;
			$_GET['MrnDetail']['mrn_id'] = $mrnid;
		}
		if (isset($_GET['MrnDetail']))
			$model->load($_GET, 'MrnDetail');

		return $this->render('admin', [
			'model' => $model,'user'=>$user,'mrnid'=>$mrnid,'outlet_id'=>$outlet_id,
				'vendor_id'=>$vendor_id,
				'start_date'=>$start_date,
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
		if ( $model == null ) $model = new MrnDetail();
		
		switch( $this->action->id)
		{
			case 'update':	
				{
					$this->menu[] = ['label'=>'View' , 'url' => Ui::to('mrnDetail/view', ['id'=>$model->id]),'icon'=>'icon-plus icon-white'];
				}
			case 'create':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('mrnDetail/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('mrnDetail/index'),'icon'=>'icon-th-list icon-white'];	
				}
				break;				
			case 'index':
				{
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('mrnDetail/admin'),'icon'=>'icon-wrench icon-white'];							
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('mrnDetail/create'),'icon'=>'icon-plus icon-white'];
				}
				break;
			case 'admin':
				{
					if(isset($_GET['mrnid'])){
						$mrnid =  $_GET['mrnid'];
					}else{
						$mrnid = null;
					}
				//	$this->menu[] = array('label'=>'List', 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu[] = ['label'=>'Add Item', 'url' => Ui::to('mrnDetail/create', ['id'=>$mrnid]),'icon'=>'icon-plus icon-white'];
				}
				break;				
			default:
			case 'view':
				{
					$this->menu[] = ['label'=>'List', 'url' => Ui::to('mrnDetail/index'),'icon'=>'icon-th-list icon-white'];
					$this->menu[] = ['label'=>'Manage', 'url' => Ui::to('mrnDetail/admin'),'icon'=>'icon-wrench icon-white'];
					$this->menu[] = ['label'=>'Delete', 'url'=>'#', 'linkOptions' => ['submit' => ['delete', 'id' => $model->id], 
					'confirm'=>'Are you sure you want to delete this item?'],'icon'=>'icon-remove icon-white'];
					$this->menu[] = ['label'=>'Create', 'url' => Ui::to('mrnDetail/create'),'icon'=>'icon-plus icon-white'];
					$this->menu[] = ['label'=>'Update', 'url' => Ui::to('mrnDetail/update', ['id' => $model->id]), 'icon'=>'icon-edit icon-white'];				
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
        //   echo '<pre>';
//print_r($_POST);exit;		   
        $mrnIdAll = $_POST;
      
        if(isset($_POST['status'])  && ($_POST['status']== 1)){
        foreach($mrnIdAll as $qty)
        {
        	if(isset( $mrnIdAll['qty']))
        	$qtys = $mrnIdAll['qty'];
        }
        if(count($qtys)>0)
        {
		$mrn =$this->loadModel($id, Mrn::class);
		if (isset ( $_POST ['gross_amt'] ))
			$mrn->gross_amt = $_POST ['gross_amt'];
		if (isset ( $_POST ['total_discount'] ))
			$mrn->total_discount = $_POST ['total_discount'];
		if (isset ( $_POST ['tax_amount'] ))
			$mrn->tax_amount = $_POST ['tax_amount'];
		if (isset ( $_POST ['bill_amount'] ))
			$mrn->bill_amount = $_POST ['bill_amount'];
		$pomodel = PurchaseOrder::findOne(['mrn_id'=>$id,'vendor_id'=>$mrn->vendor_id]);
		$updated = true;
		if($pomodel == null){
			$pomodel = new PurchaseOrder();
			$updated = false;
		}if (isset ( $_POST ['gross_amt'] ))
			$pomodel->gross_amt = $_POST ['gross_amt'];
				if (isset ( $_POST ['total_discount'] ))
					$pomodel->total_discount = $_POST ['total_discount'];
				if (isset ( $_POST ['tax_amount'] ))
					$pomodel->tax_amount = $_POST ['tax_amount'];
				if (isset ( $_POST ['bill_amount'] ))
					$pomodel->bill_amount = $_POST ['bill_amount'];
		        $pomodel->status = PurchaseOrder::STATUS_UNAPPROVED;
				$pomodel->start_date = $mrn->mrs_date;
				$pomodel->code = "code";
				$pomodel->end_date = $mrn->mrs_update_date;
				$pomodel->receiving_date = $mrn->mrs_req_date;
				$pomodel->outlet_id = $mrn->outlet_id;
				$pomodel->vendor_id = $mrn->vendor_id;
				$pomodel->mrn_id = $id;
				$pomodel->organization_id = $mrn->organization_id;
				$set = true;
				$transaction = Yii::$app->db->beginTransaction ();
				try {
				if($pomodel->save()){
					if($updated){
						$msg = 'PurchaseOrder is updated';
					}else{
						$msg = 'A new PurchaseOrder is added';
					}
					$email = '' ;
					$vendor = Vendor::findOne($pomodel->vendor_id);
					if($vendor){
						$to_id = $vendor->create_user_id;
						$vendoruser = User::findOne(['id'=>$vendor->create_user_id]);
						if($vendoruser){
							$email = $vendoruser->email;
						}
					}else{
						$to_id = $pomodel->vendor_id;
					}
					$type = Notification::TYPE_PO;
					$model_id = $pomodel->id;
					Notification::AddNotification($model_id,$msg,$type,$to_id);
					if($email != ''){
					$from = (Yii::$app->params['mail_email'] ?? null) ;
					$to      = $email;
					$subject = 'Your new purchase order:';
					
					$view = $this->renderPartial ( '/mail/purchase_order', [
							'pomodel'=>$pomodel
					], true );
					
					
					//$pomodel->mailsend ( $to, $from, $subject, $view );
					}
					
				foreach($qtys as $key=>$qty)
				{
					
				$model=$this->loadModel($key);
				$item = Item::findOne($model->item_id);
					$mrnmodel = $this->loadModel($model->mrn_id, Mrn::class);
					$model->mrp = $_POST['mrp'][$key];
					//if($qty != '' && $qty != '0'){
					$model->status = MrnDetail::STATUS_DONE;
				
					$status = Mrn::STATUS_APPROVED;
					
				//	}
				
					if(isset( $mrnIdAll['mrp']))
					{
						$model->mrp = $mrnIdAll['mrp'][$key];
					}
					if(isset( $mrnIdAll['price']))
					{
						$model->price = $mrnIdAll['price'][$key];
					}
					if(isset( $mrnIdAll['salerate']))
					{
						$model->sale_rate = $mrnIdAll['salerate'][$key];
					}
					if(isset( $mrnIdAll['discount']))
					{
						$model->discount = $mrnIdAll['discount'][$key];
					}
					if(isset( $mrnIdAll['discount_amt']))
					{
						$model->discount_amt = $mrnIdAll['discount_amt'][$key];
					}
					if(isset( $mrnIdAll['discount1']))
					{
						$model->discount1 = $mrnIdAll['discount1'][$key];
					}
					if(isset( $mrnIdAll['discount_amt1']))
					{
						$model->discount_amt1 = $mrnIdAll['discount_amt1'][$key];
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
					if (isset ( $mrnIdAll ['igstData'] )) {
						$model->igst_per = $mrnIdAll ['igstData'] [$key];
					}
					if (isset ( $mrnIdAll ['igstamtData'] )) {
						$model->igst_amt = $mrnIdAll ['igstamtData'] [$key];
					}
					/* if(isset( $mrnIdAll['vat']))
					{
						$model->vat = $mrnIdAll['vat'][$key];
					}
 */					
                    if(isset( $mrnIdAll['other_charge']))
					{
						$model->other_charge = $mrnIdAll['other_charge'][$key];
					}
					if(isset( $mrnIdAll['amount']))
					{
						$model->amount = $mrnIdAll['amount'][$key];
					}
					if (isset ( $mrnIdAll ['marginData'] )) {
						$model->margin = $mrnIdAll ['marginData'] [$key];
					}
					if (isset ( $mrnIdAll ['qty'] )) {
						$model->approved_qty = $mrnIdAll ['qty'] [$key];
						$model->bal_qty = ($model->req_qty - $model->approved_qty );
					}
					if($model->save()){
						if($item){
						if(isset( $mrnIdAll['maxData']) && isset( $mrnIdAll['minData']))
					{
						$item->max_qty = $mrnIdAll['maxData'][$key];
						$item->min_qty = $mrnIdAll['minData'][$key];
						//$item->updateAttributes(array('max_qty','min_qty'));
					}
						}
						if($qty != '' && $qty != '0'){
						$podetailmodel = PurchaseOrderDetail::findOne(['purchase_order_id'=>$pomodel->id,'outlet_id'=>$pomodel->outlet_id,'item_id'=>$model->item_id,'item_detail_id'=>$model->item_detail_id]);
						if($podetailmodel == null){
							$podetailmodel = new PurchaseOrderDetail();
						}
					$podetailmodel->status = PurchaseOrderDetail::STATUS_PENDING;
					$podetailmodel->req_qty = $model->req_qty;
					$podetailmodel->approved_qty = $qty;
					$podetailmodel->bal_qty = ($model->req_qty - $qty);
					$podetailmodel->item_detail_id = $model->item_detail_id;
					$podetailmodel->item_id = $model->item_id;
					$podetailmodel->mrp = $model->mrp;
					$podetailmodel->price = $model->price;
					$podetailmodel->sale_rate = $model->sale_rate;
					$podetailmodel->discount = $model->discount;
					$podetailmodel->discount_amt = $model->discount_amt;
					$podetailmodel->discount1 = $model->discount1;
					$podetailmodel->discount_amt1 = $model->discount_amt1;
					$podetailmodel->other_charge = $model->other_charge;
					$podetailmodel->cgst_per = $model->cgst_per ;
						
					$podetailmodel->sgst_per = $model->sgst_per ;
						
					$podetailmodel->cess_per =  $model->cess_per ;
						
						
					$podetailmodel->cgst_amt = $model->cgst_amt ;
						
						
					$podetailmodel->sgst_amt = $model->sgst_amt ;
						
					$podetailmodel->cess_amt =  $model->cess_amt ;
					$podetailmodel->igst_amt = $model->igst_amt;
					$podetailmodel->igst_per = $model->igst_per;
					$podetailmodel->tax_id = $model->tax_id;
					$podetailmodel->amount = $model->amount;
					$podetailmodel->purchase_order_id = $pomodel->id;
					$podetailmodel->outlet_id =$model->outlet_id;
					$podetailmodel->margin = $model->margin;
					if($podetailmodel->save()){
					
					}else{
						$set = false;
						throw new \Exception("Something went wrong",500);
					}
						}
					}
					
					else{
						$set = false;
						throw new \Exception("Something went wrong",500);
					}
					
				}
				$mrn->status = $status;
				if($mrn->save()){
					
				}else{
					$set = false;
				}
				if ($set == true) {
					$transaction->commit ();
						
				} else {
					$transaction->rollback ();
						
				}
				}

				
				} catch ( \Exception $e ) {
					Yii::error('mrnDetail/ajaxupdate rolled back: ' . get_class($e) . ': '
						. $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), __METHOD__);
					$transaction->rollback ();
				}
			}
        }else{
        	$mrn = Mrn::findOne($id);
        	
        	if(($mrn) && ($mrn->status == MrnDetail::STATUS_DONE)){
        		$mrn->status = Mrn::STATUS_REJECT;
        	
        		$mrn->updateAttributes(['status']);
        	
        		$mrndetailmodels = MrnDetail::findAll(['mrn_id'=>$id]);
        		if($mrndetailmodels){
        			foreach($mrndetailmodels as $mrndetailmodel){
        				$mrndetailmodel->status =  MrnDetail::STATUS_REJECT;
        				$mrndetailmodel->updateAttributes(['status']);
        			}
        			
        			$msg = 'MRN is rejected';
        			
        			$vendor = Vendor::findOne($mrn->vendor_id);
        			if($vendor){
        				$to_id = $vendor->create_user_id;
        			}else{
        				$to_id = $mrn->vendor_id;
        			}
        			$type = Notification::TYPE_MRN;
        			$model_id = $mrn->id;
        			Notification::AddNotification($model_id,$msg,$type,$to_id);
        			
        			
        		}
        			
        	}
        	$mrs = Mrs::findOne($mrn->mrs_id);
        	 
        	if($mrs){
        		$mrs->status = Mrs::STATUS_PENDING;
        		$mrs->updateAttributes(['status']);
        		$mrsdetailmodels = MrsDetail::findAll(['mrs_id'=>$mrs->id]);
        		if($mrsdetailmodels){
        			foreach($mrsdetailmodels as $mrsdetailmodel){
        				$mrsdetailmodel->status =  MrsDetail::STATUS_PENDING;
        				$mrsdetailmodel->updateAttributes(['status']);
        			}
        			 
        			 
        		}
        		 
        	}
        }
			
            
        }
	
}