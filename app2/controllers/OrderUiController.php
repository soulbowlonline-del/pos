<?php
namespace app\controllers;

use app\components\Criteria;
use app\components\Ui;
use app\models\B2bPurchaseBill;
use app\models\B2bPurchaseBillDetail;
use app\models\Bill;
use app\models\ItemCategory;
use app\models\ItemCompany;
use app\models\ItemDetail;
use app\models\Order;
use app\models\OrderItem;
use app\models\OrderRefund;
use app\models\OrderRefundItem;
use app\models\PaymentMode;
use app\models\Tax;
use app\models\Vendor;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/OrderController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class OrderUiController extends BaseUiController {
	public function actionPdf($id)
	{
		//$encode_id = base64_decode($id);
		
		$encode_id  =$id;
		$set = true;
		$order = $this->loadModel($encode_id);
		
	
			
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
				$mPDF1->WriteHTML($this->renderPartial('_billpdf',['order'=>$order], true));
	
				# Renders image
				//$mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
				$mPDF1->Output();
		
				
		
	}
	public function actionChangeTax() {
		$date = "2020-09-30";
	    $query = OrderRefundItem::find();
		$query->andWhere('date(create_time) > "'.$date.'"');
		$query->andWhere('qty > 0');
		$orders = $query->all();
		if($orders){
			foreach($orders as $order){
				$tax = Tax::findOne($order->tax_id);
				if($tax){
				$tax_per = $tax->tax_val1  + $tax->tax_val2 + $tax->tax_val3 +$tax->tax_val4;
				$total_amount = $order->price * $order->qty;
				$total_tax = ($total_amount)*($tax_per/100);
				
				$order->tax_amt = $total_tax;
				
				
				$order->saveAttributes(['tax_amt']);
				}
			}
		}
	}
	
	/*public function actionChangeTax() {
		$date = "2020-09-30";
	    $query_2 = OrderItem::find();
		$query_2->andWhere('create_date > "'.$date.'"');
		//$criteria->addCondition('qty > 1');
		$orders = $query_2->all();
		if($orders){
			foreach($orders as $order){
				$tax = Tax::findOne($order->tax_id);
				if($tax){
				$tax_per = $order->cgst_per + $order->sgst_per + $order->igst_per+$order->cess_per;
				$total_amount = $order->price * $order->qty;
				$total_tax = ($total_amount)*($tax_per/100);
				$cgst_tax = ($total_amount)*($order->cgst_per/100);
				$sgst_tax = ($total_amount)*($order->sgst_per/100);
				$igst_tax = ($total_amount)*($order->igst_per/100);
				$cess_tax = ($total_amount)*($order->cess_per/100);
				$order->tax_amount = $total_tax;
				$order->cgst_amt = $cgst_tax;
				$order->sgst_amt = $sgst_tax;
				$order->igst_amt = $igst_tax;
				$order->cess_amt = $cess_tax;
				
				$order->saveAttributes(array('tax_amount','cgst_amt','sgst_amt','igst_amt','cess_amt'));
				}
			}
		}
	}*/
	public function actionUpdateTotal(){
		
		$query1 = OrderItem::find();
		//$criteria1->addCondition('tax_id =15');
		$query1->andWhere(['between', 'date(create_time)', '2018-09-01', '2018-09-30']);
		//	$criteria1->compare('date(create_time)','2018-09-19');
		//$criteria1->limit = '10';
		$orderItems = $query1->all();
		if($orderItems){
			foreach ($orderItems as $orderItem){
				$tax = Tax::findOne($orderItem->tax_id);
				
				if($tax){
				$tax_val = $tax->tax_val1 + $tax->tax_val2 +$tax->tax_val3;
				
				if($orderItem->discount_amt != '0.000'){
					$baseprice = (($orderItem->sale_rate - ((($orderItem->sale_rate) * (5))/100))*100/(100+$tax_val));
					$orderItem->cgst_amt =  (($baseprice * $orderItem->qty) * ($tax->tax_val1/100));
					$orderItem->sgst_amt = ($baseprice * $orderItem->qty) * ($tax->tax_val2/100);
					$orderItem->cess_amt = ($baseprice * $orderItem->qty) * ($tax->tax_val3/100);
					$orderItem->igst_amt =  ($baseprice * $orderItem->qty) * (0);
					$orderItem->tax_amount =  ($baseprice * $orderItem->qty) * ($tax_val/100);
						
					/* $orderItem->cgst_amt =  (($cgst/$orderItem->qty) -  (($cgst/$orderItem->qty) * (5)/100)) * $orderItem->qty ;
					$orderItem->sgst_amt = (($sgst/$orderItem->qty) -  (($sgst/$orderItem->qty) * (5/100))) * $orderItem->qty ;
					$orderItem->cess_amt = (($cess/$orderItem->qty) -  (($cess/$orderItem->qty) * (5/100))) * $orderItem->qty ;
					$orderItem->igst_amt =  (($igst/$orderItem->qty) -  (($igst/$orderItem->qty) * (0/100))) * $orderItem->qty ; 
					$orderItem->tax_amount =  $orderItem->cgst_amt + $orderItem->sgst_amt + $orderItem->cess_amt;*/
					$orderItem->price  = $baseprice;
				}else{
					$orderItem->cgst_amt =  (($orderItem->price * $orderItem->qty) * ($tax->tax_val1/100));
					$orderItem->sgst_amt = ($orderItem->price * $orderItem->qty) * ($tax->tax_val2/100);
					$orderItem->cess_amt = ($orderItem->price * $orderItem->qty) * ($tax->tax_val3/100);
					$orderItem->igst_amt =  ($orderItem->price * $orderItem->qty) * (0);
					$orderItem->tax_amount =  ($orderItem->price * $orderItem->qty) * ($tax_val/100);
					$orderItem->price  = $orderItem->price;
				}
			
				}
				$orderItem->total_amt = (($orderItem->sale_rate)*($orderItem->qty)) - ($orderItem->discount_amt);
				
				$orderItem->saveAttributes(['total_amt','tax_amount','cgst_amt','sgst_amt','cess_amt','price']);
			}
		}
	}
	public function actionGetDiff(){
	$query1 = OrderItem::find();
	$query1->andWhere('tax_id =10');
	Criteria::compare($query1, 'date(create_time)', '2018-08-30');
	//$criteria1->limit = '10';
	$order_items = $query1->all();
	if($order_items){
	foreach($order_items as $order_item){
	$tax = $order_item->tax_amount;
	$total_tax = (($order_item->total_amt) - ($order_item->tax_amount))*18/100;
	$diff = $total_tax - $tax;
	if($total_tax != $tax && $diff > 1)
	{
	echo $order_item->id;
	echo'<br>';
	echo 'tax'.$tax;
	echo'<br>';
	echo '$total_tax'.$total_tax;
	echo'<br>';
	
	}
	}
	}
	}
	public function actionUpdateOrderItems(){
	$query1 = OrderRefundItem::find();
	$query1->andWhere('qty !=0.000');
	//$criteria1->limit = '10';
	$query1->andWhere(['between', 'date(create_time)', '2018-11-01', '2018-11-28']);
	$order_refunditems = $query1->all();

	if($order_refunditems){
	foreach($order_refunditems as $order_refunditem){
	$orderrefund = OrderRefund::findOne($order_refunditem->order_refund_id);
	if($orderrefund){
		$query2 = OrderItem::find();
		$query2->andWhere('order_id ='.$orderrefund->order_id);
		$query2->andWhere('item_detail_id ='.$order_refunditem->item_detail_id);
		$query2->andWhere('item_id ='.$order_refunditem->item_id);
		$order_item = $query2->one();
		if($order_item){
			$qty = $order_refunditem->qty;
			$price = $order_item->price;
			$discount = $order_refunditem->discount_amt;
			$tax = Tax::findOne($order_item->tax_id);
			$tax_val = $tax->tax_val1 + $tax->tax_val2 +$tax->tax_val3;
			$tax = (($price) * ($tax_val/100));
			$totaltax = $tax * $qty ;
			$total_amt = ($order_refunditem->price * $qty) + ($totaltax - $discount);
			$order_refunditem->price = $price;
			$order_refunditem->tax_amt = $totaltax;
			$order_refunditem->total_amt = $total_amt;
			$order_refunditem->saveAttributes(['tax_amt','total_amt','price']);
			echo $order_refunditem->id;
			echo '<br>';
		}
	}
	
	}
	}
	}
	public function actionUpdateOrders($id) {
		$query = Order::find();
		$query->andWhere('id >='.$id);
		$query->limit(1000);
		$orders = $query->all();
		if($orders){
			foreach($orders as $order){
				$query1 = OrderItem::find();
				$query1->andWhere('order_id ='. $order->id);
				$query1->select('sum(total_amt) as total_amt');
				$order_item = $query1->one();
				$order->total_amt = $order_item->total_amt;
				$order->saveAttributes(['total_amt']);
				$order_id = $order->id;
			}
			echo $order_id;
		}
	}
	public function actionUpdatetax($date = null) {
		
		$total = 0;
		$taxamount = 0;
		/* $query2 = OrderItem::find();
		$query2->select('sum(total_amt) as total_amt');
	$query2->andWhere('tax_id =10');
	Criteria::compare($query2, 'date(create_time)', '2018-08-30');
	//$criteria1->limit = '10';
	$order = $query2->one(); */
	
	$sdate = '2021-08-01';
	$edate = '2021-08-12';
	
		$query1 = OrderItem::find();
	//$criteria1->addCondition('tax_id ='.$id);
		//$criteria1->compare('create_date',$date);
	//	$criteria1->addInCondition('tax_id',array('10','5'));
	$query1->andWhere(['between', 'date(create_time)', $sdate, $edate]);
//	$criteria1->compare('date(create_time)','2018-09-19');
	//$criteria1->limit = '10';
	$orderItems = $query1->all();
	$orderItemcounts = $query1->all();
	
		if ($orderItems) {
			/* foreach ( $orderItems as $orderItem ) {
				$taxable = $orderItem->price * $orderItem->qty;
				$tax = $taxable * 18/100;
				$diff = $orderItem->tax_amount - $tax;
				if($orderItem->tax_amount != $tax ){
					echo 'tax'.$tax;
						echo '<pre>';
						print_r($orderItem);
				}
			}
			exit;  */
			$tdiff = 0;
			foreach ( $orderItems as $orderItem ) {
			
				
				$tax = Tax::findOne($orderItem->tax_id);
				$itemDetail = ItemDetail::findOne($orderItem->item_detail_id);
				if($itemDetail){
					if($orderItem->sale_rate != '0.000'){
						$sale_rate = $orderItem->sale_rate;
						if($orderItem->discount_amt != '0.000'){
							$discount = $orderItem->discount_amt/$orderItem->qty;
							$sale_rate = $orderItem->sale_rate - $discount;
							
						}
				$baseprice = $itemDetail->getBasePrice($sale_rate);
				$baseprice = str_replace(",", "", $baseprice);
					}else{
						$baseprice = $itemDetail->getBasePrice();
						$baseprice = str_replace(",", "", $baseprice);
					}
				$orderItem->price  = $baseprice;
				if($orderItem->sale_rate == '0.000' || $orderItem->price ==  $orderItem->sale_rate){
					$sale_rate = str_replace(",", "", $itemDetail->getItemDetailSaleRate());
					$orderItem->sale_rate  = $sale_rate;
				}
				if($orderItem->mrp == '0.000'){
					$mrp = str_replace(",", "", $itemDetail->getItemDetailMrp());
					$orderItem->mrp  = $mrp;
				}
				}
				if($tax){
					if(($tax->type_id == '1')){
					$val = $tax->tax_val4/2;
					
					$query = Tax::find();
					$query->andWhere('tax_val1 ='.$val);
					$query->andWhere('tax_val2 ='.$val);
					$query->andWhere('type_id = 0');
					$tax = $query->one();
					}
				
					
					
						
				$orderItem->cgst_per = $tax->tax_val1 ;
				$orderItem->sgst_per = $tax->tax_val2 ;
				$orderItem->cess_per = $tax->tax_val3;
				$orderItem->igst_per = $tax->tax_val4;
				$orderItem->tax_id = $tax->id;
				$old_tax = $orderItem->tax_amount;
				$total_per = $orderItem->cgst_per + $orderItem->sgst_per + $orderItem->cess_per;
				/* if($orderItem->discount_amt != '0.00'){
					$cgst =  (($orderItem->price * $orderItem->qty) * ($orderItem->cgst_per/100));
					$sgst = ($orderItem->price * $orderItem->qty) * ($orderItem->sgst_per/100);
					$cess = ($orderItem->price * $orderItem->qty) * (	$orderItem->cess_per/100);
					$igst =  ($orderItem->price * $orderItem->qty) * (0);
					$tax_amount =  ($orderItem->price * $orderItem->qty) * ($total_per/100);
					
					$orderItem->cgst_amt =  (($cgst/$orderItem->qty) -  (($cgst/$orderItem->qty) * (5/100))) * $orderItem->qty ;
					$orderItem->sgst_amt = (($sgst/$orderItem->qty) -  (($sgst/$orderItem->qty) * (5/100))) * $orderItem->qty ;
					$orderItem->cess_amt = (($cess/$orderItem->qty) -  (($cess/$orderItem->qty) * (5/100))) * $orderItem->qty ;
					$orderItem->igst_amt =  (($igst/$orderItem->qty) -  (($igst/$orderItem->qty) * (0/100))) * $orderItem->qty ;
					$orderItem->tax_amount =  (($tax_amount/$orderItem->qty) -  (($tax_amount/$orderItem->qty) * ($total_per/100))) * $orderItem->qty ;
				}else{ */
				$oldcgst = $orderItem->cgst_amt;
				$oldsgst = $orderItem->sgst_amt;
				$newgst = $orderItem->cgst_amt + $orderItem->sgst_amt;
				$oldgst = $orderItem->tax_amount;
				$orderItem->cgst_amt =  (($orderItem->price) * ($orderItem->cgst_per/100)) * ($orderItem->qty);
				$orderItem->sgst_amt = ($orderItem->price * $orderItem->qty) * ($orderItem->sgst_per/100);
				$orderItem->cess_amt = ($orderItem->price * $orderItem->qty) * ($orderItem->cess_per/100);
				$orderItem->igst_amt =  ($orderItem->price * $orderItem->qty) * (0);
				$orderItem->tax_amount =  ($orderItem->price * $orderItem->qty) * ($total_per/100);
				/* } */
				//$orderItem->total_amt = ($orderItem->price * $orderItem->qty)+($orderItem->tax_amount -$orderItem->discount_amt);
				$orderItem->total_amt = ($orderItem->sale_rate * $orderItem->qty);
				//echo 'prevtotal'.$total;
				$total +=  $orderItem->total_amt;
				$taxamount = $taxamount + $orderItem->tax_amount;
				//echo 'total'.$total;
				//echo '<br>';
				//echo 'id'.$orderItem->id;
				//echo '<br>';
				
				$diff  = 0;
				$orderItem->create_time = $orderItem->create_time;
				$tax = (($orderItem->total_amt + $orderItem->discount_amt) - ($orderItem->tax_amount)) *($total_per/100);
				/*if($old_tax != $orderItem->tax_amount){
					$diff = $orderItem->tax_amount - $old_tax ;
					$tdiff = $tdiff + $diff;
					//if($diff > 1){
						echo $orderItem->id;
						echo '<br>';
						echo $orderItem->order_id;
						echo '<br>';
				echo 'diff'.$diff;
				echo '<br>';
				echo 'newtax'.$orderItem->tax_amount;
				echo '<br>';
				echo 'tax'.$old_tax;
				echo '<pre>';
					//}
				
}	
if($oldgst != $newgst){
	echo 'itemid'.$orderItem->id;
						echo '<br>';
						echo 'orderid'.$orderItem->order_id;
						echo '<br>';
						echo 'oldgst'.$oldgst;
						echo '<br>';
						echo 'newgst'.$newgst;
						echo '<br>';
						$tdiff = $tdiff + $newgst;
}*/
				}else{
					$orderItem->total_amt = ($orderItem->sale_rate * $orderItem->qty)-($orderItem->discount_amt);
				}
				//$tdiff = 0;
				$newcgst = number_format($orderItem->cgst_amt,'3','.','');
				if($oldcgst != $newcgst){
					echo $orderItem->id;
					echo '<br>';
					echo 'OLD'.$oldcgst;
					echo '<br>';
					echo 'CGST'.$newcgst;
					echo '<br>';
					$diff = $newcgst - $oldcgst;
					$tdiff = $tdiff + $diff;
				}
				if($orderItem->save()){
				}else{
					print_r($orderItem->price);
				print_r($orderItem->getErrors());exit;
				}
			
			}
//echo 'total'.$tdiff;
			//exit;
		}
	}
	public function actionUpdateDetail() {
		$orderItems = OrderItem::find()->all();
		if ($orderItems) {
			foreach ( $orderItems as $orderItem ) {
				$date = date ( 'Y-m-d', strtotime ( $orderItem->create_time ) );
				$orderItem->create_date = $date;
				$orderItem->saveAttributes ( [
						'create_date' 
				] );
			}
		}
	}
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionB2bReport() {
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		
		$query = PaymentMode::find();
        $query->orderBy(['id' => SORT_DESC]);
		Criteria::compare($query, 'title', 'B2B');
		Criteria::compare($query, 'type_id', '0');
		$modePayment = $query->one();
		if($modePayment){
		Yii::$app->session ['order_mode_payment'] = $modePayment->id;
		}else{
			Yii::$app->session ['order_mode_payment'] = '';
		}
		if (isset ( $_POST ['OrderItem'] ['columns'] )) {
			$columns = $_POST ['OrderItem'] ['columns'];
		}
		if (isset ( $_POST ['OrderItem'] ['start_date'] ) && ($_POST ['OrderItem'] ['start_date'] != '') && (isset ( $_POST ['OrderItem'] ['end_date'] )) && ($_POST ['OrderItem'] ['end_date'] != '')) {
			$_GET ['OrderItem'] ['start_date'] = $_POST ['OrderItem'] ['start_date'];
			$_GET ['OrderItem'] ['end_date'] = $_POST ['OrderItem'] ['end_date'];
			Yii::$app->session ['order_b2b_start_date'] = $_POST ['OrderItem'] ['start_date'];
			Yii::$app->session ['order_b2b_end_date'] = $_POST ['OrderItem'] ['end_date'];
		}else{
			$_GET ['OrderItem'] ['start_date'] = date('Y-m-d');
			$_GET ['OrderItem'] ['end_date'] = date('Y-m-d');
			//Yii::$app->session ['order_item_start_date'] = date('Y-m-d');
			//Yii::$app->session ['order_item_end_date'] = date('Y-m-d');
			
		}
		if (isset ( $_GET ['OrderItem'] ))
			$model->load($_GET, 'OrderItem');
			$columns = $model->getb2bTaxColumns ( $columns );
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->b2bTaxsearch (), $columns );
			}
	
			return $this->render( 'b2b', [
					'model' => $model
			] );
	}
	
	public function actionGrouphsntax() {
		  ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '0');
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['OrderItem'] ['columns'] )) {
			$columns = $_POST ['OrderItem'] ['columns'];
		}
		if (isset ( $_POST ['OrderItem'] ['start_date'] ) && ($_POST ['OrderItem'] ['start_date'] != '') && (isset ( $_POST ['OrderItem'] ['end_date'] )) && ($_POST ['OrderItem'] ['end_date'] != '')) {
			$_GET ['OrderItem'] ['start_date'] = $_POST ['OrderItem'] ['start_date'];
			$_GET ['OrderItem'] ['end_date'] = $_POST ['OrderItem'] ['end_date'];
			Yii::$app->session ['order_item_start_date'] = $_POST ['OrderItem'] ['start_date'];
			Yii::$app->session ['order_item_end_date'] = $_POST ['OrderItem'] ['end_date'];
		}else{
			$_GET ['OrderItem'] ['start_date'] = date('Y-m-d');
			$_GET ['OrderItem'] ['end_date'] = date('Y-m-d');
			if(isset(Yii::$app->session ['order_item_start_date']) && isset(Yii::$app->session ['order_item_end_date'])&&(Yii::$app->session ['order_item_start_date'] == '') && (Yii::$app->session ['order_item_end_date'] == '')){
			Yii::$app->session ['order_item_start_date'] = date('Y-m-d');
			Yii::$app->session ['order_item_end_date'] = date('Y-m-d');
			}
			
		}
		if (isset ( $_GET ['OrderItem'] ))
			$model->load($_GET, 'OrderItem');
		$columns = $model->getGroupHsnTaxColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
		
			$this->exportCSV( $model->groupHSNTaxsearch (), $columns );
		}
		
		return $this->render( 'grouphsntax', [
				'model' => $model 
		] );
	}
	public function actionorderexcel()
    {
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="hsncodewise.csv"');
        
        
        
        $ordermodel = new Order();
		
        $csv = $ordermodel->getcsvexcel();
        
        
        
        $fp = fopen('php://output', 'wb');
        foreach ( $csv as $line ) {
            fputcsv($fp, $line, ',');
        }
        fclose($fp);
    }
	public function actionGroupTax() {
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['OrderItem'] ['columns'] )) {
			$columns = $_POST ['OrderItem'] ['columns'];
		}
		if (isset ( $_POST ['OrderItem'] ['start_date'] ) && ($_POST ['OrderItem'] ['start_date'] != '') && (isset ( $_POST ['OrderItem'] ['end_date'] )) && ($_POST ['OrderItem'] ['end_date'] != '')) {
			$_GET ['OrderItem'] ['start_date'] = $_POST ['OrderItem'] ['start_date'];
			$_GET ['OrderItem'] ['end_date'] = $_POST ['OrderItem'] ['end_date'];
			Yii::$app->session ['order_item_start_date'] = $_POST ['OrderItem'] ['start_date'];
			Yii::$app->session ['order_item_end_date'] = $_POST ['OrderItem'] ['end_date'];
		}else{
			$_GET ['OrderItem'] ['start_date'] = date('Y-m-d');
			$_GET ['OrderItem'] ['end_date'] = date('Y-m-d');
			//Yii::$app->session ['order_item_start_date'] = date('Y-m-d');
			//Yii::$app->session ['order_item_end_date'] = date('Y-m-d');
			if(isset($_GET ['OrderItem']['mode_of_payment']) && ($_GET ['OrderItem']['mode_of_payment'] !='')){
				$model->mode_of_payment = $_GET ['OrderItem']['mode_of_payment'];
				Yii::$app->session ['order_mode_payment'] = $_GET ['OrderItem']['mode_of_payment'];
			}else{
				
				Yii::$app->session ['order_mode_payment'] = '';
			}
		}
		if (isset ( $_GET ['OrderItem'] ))
			$model->load($_GET, 'OrderItem');
		$columns = $model->getGroupTaxColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->groupTaxsearch (), $columns );
		}
		
		return $this->render( 'grouptax', [
				'model' => $model 
		] );
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
	public function actionCreate() {
		$model = new Order ();
		
		$this->performAjaxValidation( $model, 'order-form' );
		
		if (isset ( $_POST ['Order'] )) {
			$model->load($_POST, 'Order');
			
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
		
		$this->performAjaxValidation( $model, 'order-form' );
		
		if (isset ( $_POST ['Order'] )) {
			$model->load($_POST, 'Order');
			
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
	public function actionIndex() {
		$this->updateMenuItems ();
		$dataProvider = new ActiveDataProvider(['query' => Order::find(),
            // The model's own defaultScope() decides the order - most
            // inherit `id DESC`, but 22 of them override it to none.
            // Hardcoding id DESC here listed rows Yii 1 never showed.
            // defaultOrder, not listingOrder: index builds its own
            // provider and never calls search(), so the order the admin
            // grid gets from the criteria does not apply here.
            'sort' => ['defaultOrder' => Order::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE]]);
		return $this->render( 'index', [
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionSearch() {
		$model = new Order(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['Order'] )) {
			$model->load($_GET, 'Order');
			return $this->renderPartial( '_list', [
					'dataProvider' => $model->search (),
					'model' => $model 
			] );
		}
		
		return $this->renderPartial( '_search', [
				'model' => $model 
		] );
	}
	public function actionUserWiseExport() {
		$model = new Order(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['Order'] ['columns'] )) {
			$columns = $_POST ['Order'] ['columns'];
		}
		if (isset ( $_POST ['Order'] ['item_id'] )) {
			$_GET ['Order'] ['item_id'] = $_POST ['Order'] ['item_id'];
			Yii::$app->session ['item_id'] = $_POST ['Order'] ['item_id'];
		}
		if (isset ( $_POST ['Order'] ['start_date'] ) && ($_POST ['Order'] ['start_date'] != '') && (isset ( $_POST ['Order'] ['end_date'] )) && ($_POST ['Order'] ['end_date'] != '')) {
			$_GET ['Order'] ['start_date'] = $_POST ['Order'] ['start_date'];
			$_GET ['Order'] ['end_date'] = $_POST ['Order'] ['end_date'];
			Yii::$app->session ['start_date'] = $_POST ['Order'] ['start_date'];
			Yii::$app->session ['end_date'] = $_POST ['Order'] ['end_date'];
		}
		if (isset ( $_GET ['Order'] ))
			$model->load($_GET, 'Order');
		$columns = $model->getUserwiseColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->userwisesearch (), $columns );
		}
	}
	public function actionItemWiseExport() {
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['OrderItem'] ['columns'] )) {
			$columns = $_POST ['OrderItem'] ['columns'];
		}
		if (isset ( $_POST ['OrderItem'] ['item_id'] )) {
			$_GET ['OrderItem'] ['item_id'] = $_POST ['OrderItem'] ['item_id'];
			Yii::$app->session ['item_id'] = $_POST ['OrderItem'] ['item_id'];
		}
		if (isset ( $_POST ['OrderItem'] ['start_date'] ) && ($_POST ['OrderItem'] ['start_date'] != '') && (isset ( $_POST ['OrderItem'] ['end_date'] )) && ($_POST ['OrderItem'] ['end_date'] != '')) {
			$_GET ['OrderItem'] ['start_date'] = $_POST ['OrderItem'] ['start_date'];
			$_GET ['OrderItem'] ['end_date'] = $_POST ['OrderItem'] ['end_date'];
			Yii::$app->session ['start_date'] = $_POST ['OrderItem'] ['start_date'];
			Yii::$app->session ['end_date'] = $_POST ['OrderItem'] ['end_date'];
		}
		if (isset ( $_GET ['OrderItem'] ))
			$model->load($_GET, 'OrderItem');
		$columns = $model->getItemwiseColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->itemwisesearch (), $columns );
		}
	}
	public function actionItemWise() {
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['OrderItem'] ['columns'] )) {
			$columns = $_POST ['OrderItem'] ['columns'];
		}
		
		if (isset ( $_POST ['OrderItem'] ['item_id'] )) {
			// $_GET['OrderItem']['item_id'] = $_POST ['OrderItem']['item_id'] ;
			Yii::$app->session ['item_id'] = $_POST ['OrderItem'] ['item_id'];
		} else {
			Yii::$app->session ['item_id'] = '';
		}
		if (isset ( $_POST ['OrderItem'] ['start_date'] ) && ($_POST ['OrderItem'] ['start_date'] != '') && (isset ( $_POST ['OrderItem'] ['end_date'] )) && ($_POST ['OrderItem'] ['end_date'] != '')) {
			$_GET ['OrderItem'] ['start_date'] = $_POST ['OrderItem'] ['start_date'];
			$_GET ['OrderItem'] ['end_date'] = $_POST ['OrderItem'] ['end_date'];
			Yii::$app->session ['start_date'] = $_POST ['OrderItem'] ['start_date'];
			Yii::$app->session ['end_date'] = $_POST ['OrderItem'] ['end_date'];
		} 
		if (isset ( $_GET ['OrderItem'] ))
			$model->load($_GET, 'OrderItem');
		$columns = $model->getItemwiseColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->itemwisesearch (), $columns );
		}
		
		return $this->render( 'itemwise', [
				'model' => $model 
		] );
	}
	public function actionVendorWiseExport() {
		$model = new Vendor(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['Vendor'] ['columns'] )) {
			$columns = $_POST ['Vendor'] ['columns'];
		}
	
		if (isset ( $_GET ['Vendor'] ))
			$model->load($_GET, 'Vendor');
			$columns = $model->getVendorWiseColumns ( $columns );
			
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search (), $columns );
			}
	}
	public function actionVendorWise() {
		$model = new Vendor(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		
		$columns = [];
		if (isset ( $_POST ['Vendor'] ['columns'] )) {
			$columns = $_POST ['Vendor'] ['columns'];
		}
		
				
		
			if (isset ( $_POST ['Vendor'] ['start_date'] ) && ($_POST ['Vendor'] ['start_date'] != '') && (isset ( $_POST ['Vendor'] ['end_date'] )) && ($_POST ['Vendor'] ['end_date'] != '')) {
				$_GET ['Vendor'] ['start_date'] = $_POST ['Vendor'] ['start_date'];
				$_GET ['Vendor'] ['end_date'] = $_POST ['Vendor'] ['end_date'];
				Yii::$app->session ['vendor_start_date'] = $_POST ['Vendor'] ['start_date'];
				Yii::$app->session ['vendor_end_date'] = $_POST ['Vendor'] ['end_date'];
			} else {
				if(!isset($_GET['Vendor_page'])){
				$_GET ['Vendor'] ['start_date'] = date('Y-m-d');
				$_GET ['Vendor'] ['end_date'] = date('Y-m-d');
				Yii::$app->session ['vendor_start_date'] = date('Y-m-d');
				Yii::$app->session ['vendor_end_date'] =date('Y-m-d');
				}
			}
		
		if (isset ( $_GET ['Vendor'] ))
			$model->load($_GET, 'Vendor');
			return $this->render( 'vendorwise', [
					'model' => $model
			] );
	}
	
	public function actionDeptWise() {
		$model = new ItemCategory(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['ItemCategory'] ['columns'] )) {
			$columns = $_POST ['ItemCategory'] ['columns'];
		}
		if(!isset($_GET['ItemCategory_page'])){
			
		
		if (isset ( $_POST ['ItemCategory'] ['start_date'] ) && ($_POST ['ItemCategory'] ['start_date'] != '') && (isset ( $_POST ['ItemCategory'] ['end_date'] )) && ($_POST ['ItemCategory'] ['end_date'] != '')) {
			$_GET ['ItemCategory'] ['start_date'] = $_POST ['ItemCategory'] ['start_date'];
			$_GET ['ItemCategory'] ['end_date'] = $_POST ['ItemCategory'] ['end_date'];
			Yii::$app->session ['start_date'] = $_POST ['ItemCategory'] ['start_date'];
			Yii::$app->session ['end_date'] = $_POST ['ItemCategory'] ['end_date'];
		} else {
			$_GET ['ItemCategory'] ['start_date'] = date('Y-m-d');
			$_GET ['ItemCategory'] ['end_date'] = date('Y-m-d');
			Yii::$app->session ['start_date'] = date('Y-m-d');
			Yii::$app->session ['end_date'] =date('Y-m-d');
		}
		}
		if (isset ( $_GET ['ItemCategory'] ))
			$model->load($_GET, 'ItemCategory');
			
	
			return $this->render( 'deptwise', [
					'model' => $model
			] );
	}
	public function actionDeptWiseExport() {
		$model = new ItemCategory(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['ItemCategory'] ['columns'] )) {
			$columns = $_POST ['ItemCategory'] ['columns'];
		}
		
		if (isset ( $_GET ['ItemCategory'] ))
			$model->load($_GET, 'ItemCategory');
			$columns = $model->getDeptWiseColumns ( $columns );
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search (), $columns );
			}
	}
	public function actionCompWise() {
		$model = new ItemCompany(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['ItemCompany'] ['columns'] )) {
			$columns = $_POST ['ItemCompany'] ['columns'];
		}
		if(!isset($_GET['ItemCompany_page'])){
				
	
			if (isset ( $_POST ['ItemCompany'] ['start_date'] ) && ($_POST ['ItemCompany'] ['start_date'] != '') && (isset ( $_POST ['ItemCompany'] ['end_date'] )) && ($_POST ['ItemCompany'] ['end_date'] != '')) {
				$_GET ['ItemCompany'] ['start_date'] = $_POST ['ItemCompany'] ['start_date'];
				$_GET ['ItemCompany'] ['end_date'] = $_POST ['ItemCompany'] ['end_date'];
				Yii::$app->session ['start_date'] = $_POST ['ItemCompany'] ['start_date'];
				Yii::$app->session ['end_date'] = $_POST ['ItemCompany'] ['end_date'];
			} else {
				$_GET ['ItemCompany'] ['start_date'] = date('Y-m-d');
				$_GET ['ItemCompany'] ['end_date'] = date('Y-m-d');
				Yii::$app->session ['start_date'] = date('Y-m-d');
				Yii::$app->session ['end_date'] =date('Y-m-d');
			}
		}
		if (isset ( $_GET ['ItemCompany'] ))
			$model->load($_GET, 'ItemCompany');
				
	
			return $this->render( 'compwise', [
					'model' => $model
			] );
	}
	public function actionCompWiseExport() {
		$model = new ItemCompany(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['ItemCompany'] ['columns'] )) {
			$columns = $_POST ['ItemCompany'] ['columns'];
		}
	
		if (isset ( $_GET ['ItemCompany'] ))
			$model->load($_GET, 'ItemCompany');
			$columns = $model->getcompWiseColumns ( $columns );
			if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
				$this->exportCSV( $model->search (), $columns );
			}
	}
	public function actionUserWise() {
		$model = new Order(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['Order'] ['columns'] )) {
			$columns = $_POST ['Order'] ['columns'];
		}
		
		if (isset ( $_POST ['Order'] ['start_date'] ) && ($_POST ['Order'] ['start_date'] != '') && (isset ( $_POST ['Order'] ['end_date'] )) && ($_POST ['Order'] ['end_date'] != '')) {
			$_GET ['Order'] ['start_date'] = $_POST ['Order'] ['start_date'];
			$_GET ['Order'] ['end_date'] = $_POST ['Order'] ['end_date'];
			Yii::$app->session ['start_date'] = $_POST ['Order'] ['start_date'];
			Yii::$app->session ['end_date'] = $_POST ['Order'] ['end_date'];
		} else {
			Yii::$app->session ['start_date'] = date('Y-m-d');
			Yii::$app->session ['end_date'] = date('Y-m-d');
			$_GET ['Order'] ['start_date'] = date('Y-m-d');
			$_GET ['Order'] ['end_date'] = date('Y-m-d');
		}
		if (isset ( $_POST ['Order'] ['item_id'] )) {
			$_GET ['Order'] ['item_id'] = $_POST ['Order'] ['item_id'];
			Yii::$app->session ['item_id'] = $_POST ['Order'] ['item_id'];
		}
		if (isset ( $_GET ['Order'] ))
			$model->load($_GET, 'Order');
			$columns = $model->getUserwiseColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->userwisesearch (), $columns );
		}
		
		return $this->render( 'userwise', [
				'model' => $model 
		] );
	}
	public function actionUserwisePdf() {
		$set = true;
		
		$login = Yii::$app->user->model;
		
		$model = new Order(['scenario' => 'search']);
		
		if (isset ( $_GET ['Order'] ))
			$model->load($_GET, 'Order');
			
			// mPDF
		$mPDF1 = Yii::$app->ePdf->mpdf ();
		
		// You can easily override default constructor's params
		$mPDF1 = Yii::$app->ePdf->mpdf ( '', 'A4' );
		
		// render (full page)
		// $mPDF1->WriteHTML($this->render('index', array(), true));
		
		// Load a stylesheet
		// $stylesheet = file_get_contents(Yii::getPathOfAlias('webroot.css') . '/main.css');
		// $mPDF1->WriteHTML($stylesheet, 1);
		
		// renderPartial (only 'view' of current controller)
		$mPDF1->WriteHTML ( $this->renderPartial ( '_pdf', [
				'model' => $model
				
		], true ) );
		
		// Renders image
		// $mPDF1->WriteHTML(CHtml::image(Yii::getPathOfAlias('webroot.css') . '/bg.gif' ));
		$mPDF1->Output ();
		
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
	public function actionAdmin() {
		$model = new Order(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['Order'] ['start_date'] ) && ($_POST ['Order'] ['start_date'] != '') && (isset ( $_POST ['Order'] ['end_date'] )) && ($_POST ['Order'] ['end_date'] != '')) {
			$_GET ['Order'] ['start_date'] = $_POST ['Order'] ['start_date'];
			$_GET ['Order'] ['end_date'] = $_POST ['Order'] ['end_date'];
			Yii::$app->session ['order_start_date'] = $_POST ['Order'] ['start_date'];
			Yii::$app->session ['order_end_date'] = $_POST ['Order'] ['end_date'];
		}else{
			if(!isset($_GET['Order_page'])){
			if (!$this->isExportRequest() && Yii::$app->session ['order_start_date'] == '' && Yii::$app->session ['order_end_date'] == '' ) {
			$_GET ['Order'] ['start_date'] = date('Y-m-d');
			$_GET ['Order'] ['end_date'] = date('Y-m-d');
			Yii::$app->session ['order_start_date'] = date('Y-m-d');
			Yii::$app->session ['order_end_date'] = date('Y-m-d');
			}
			}
		}
		if(Yii::$app->session ['order_start_date'] != '' && Yii::$app->session ['order_end_date'] != ''){
			$_GET ['Order'] ['start_date'] = Yii::$app->session ['order_start_date'];
			$_GET ['Order'] ['end_date'] = Yii::$app->session ['order_end_date'];
		}
		if (isset ( $_POST ['Order'] ['min_amt'] ) && ($_POST ['Order'] ['min_amt'] != '') && (isset ( $_POST ['Order'] ['max_amt'] )) && ($_POST ['Order'] ['max_amt'] != '')) {
			$_GET ['Order'] ['min_amt'] = $_POST ['Order'] ['min_amt'];
			$_GET ['Order'] ['max_amt'] = $_POST ['Order'] ['max_amt'];
			Yii::$app->session ['order_min_amt'] = $_POST ['Order'] ['min_amt'];
			Yii::$app->session ['order_max_amt'] = $_POST ['Order'] ['max_amt'];
		}
		if(isset ( $_POST ['Order'] ['mode_of_payment'] ) ){
			Yii::$app->session ['order_mode'] = $_POST ['Order'] ['mode_of_payment'];
			$_GET ['Order'] ['mode_of_payment'] = Yii::$app->session ['order_mode'];
			
		}
		if(Yii::$app->session ['order_mode'] != ''){
			$_GET ['Order'] ['mode_of_payment'] = Yii::$app->session ['order_mode'];
			
		}
		Yii::warning( var_export(Yii::$app->session ['order_start_date'], true), 'start_date');
		Yii::warning( var_export(Yii::$app->session ['order_end_date'], true), 'endd_date');
		if (isset ( $_POST ['Order'] ['columns'] )) {
			$columns = $_POST ['Order'] ['columns'];
		}
		if (isset ( $_GET ['Order'] ))
			$model->load($_GET, 'Order');
			Yii::warning( var_export($model, true), '$model');
		$columns = $model->getColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->search (), $columns );
		}
		
		return $this->render( 'admin', [
				'model' => $model 
		] );
	}
	public function actionTax() {
		$model = new OrderItem(['scenario' => 'search']);
		$this->updateMenuItems ( $model );
		$columns = [];
		if (isset ( $_POST ['OrderItem'] ['columns'] )) {
			$columns = $_POST ['OrderItem'] ['columns'];
		}
		
		if (isset ( $_GET ['OrderItem'] ))
			$model->load($_GET, 'OrderItem');
		$columns = $model->getTaxColumns ( $columns );
		if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV( $model->search (), $columns );
		}
		
		return $this->render( 'tax', [
				'model' => $model 
		] );
	}
	/*
	 * protected function processActions($model = null)
	 * {
	 * parent::processActions($model);
	 * //$this->actions [] = array('label'=>'Add Skill', 'url'=>array('skill', 'id' => $model->id),'icon'=>'icon-plus icon-white');
	 * }
	 */
	protected function updateMenuItems($model = null) {
		// create static model if model is null
		if ($model == null)
			$model = new Order ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'View' ),
							'url' => Ui::to('order/view', ['id' => $model->id]),
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
					/*
					 * $this->menu [] = array (
					 * 'label' => Yii::t ( 'app', 'List' ),
					 * 'url' => array (
					 * 'index'
					 * ),
					 * 'icon' => 'icon-th-list icon-white'
					 * );
					 */
					$this->menu [] = [
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => [
									'admin' 
							],
							'icon' => 'icon-wrench icon-white' 
					];
					/*
					 * $this->menu [] = array (
					 * 'label' => Yii::t ( 'app', 'Delete' ),
					 * 'url' => '#',
					 * 'linkOptions' => array (
					 * 'submit' => array (
					 * 'delete',
					 * 'id' => $model->id
					 * ),
					 * 'confirm' => 'Are you sure you want to delete this item?'
					 * ),
					 * 'icon' => 'icon-remove icon-white'
					 * );
					 * $this->menu [] = array (
					 * 'label' => Yii::t ( 'app', 'Create' ),
					 * 'url' => array (
					 * 'create'
					 * ),
					 * 'icon' => 'icon-plus icon-white'
					 * );
					 * $this->menu [] = array (
					 * 'label' => Yii::t ( 'app', 'Update' ),
					 * 'url' => array (
					 * 'update',
					 * 'id' => $model->id
					 * ),
					 * 'icon' => 'icon-edit icon-white'
					 * );
					 */
				}
				break;
		}
		
		// Add SEO headers
		$this->processSEO ( $model );
		
		// merge actions with menu
		$this->actions = array_merge ( $this->actions, $this->menu );
	}
	public function actionB2bItemWise()
    {
        $model = new B2bPurchaseBillDetail(['scenario' => 'itemwisesearch']);
        $this->updateMenuItems($model);
        $columns = [];
        if (isset($_POST['B2bPurchaseBillDetail']['columns'])) {
            $columns = $_POST['B2bPurchaseBillDetail']['columns'];
        }

        if (isset($_POST['B2bPurchaseBillDetail']['item_id'])) {
            $_GET['OrderItem']['item_id'] = $_POST ['OrderItem']['item_id'] ;
            Yii::$app->session['item_id'] = $_POST['B2bPurchaseBillDetail']['item_id'];
        } else {
            Yii::$app->session['item_id'] = '';
        }
        if (isset($_POST['B2bPurchaseBillDetail']['start_date']) && ($_POST['B2bPurchaseBillDetail']['start_date'] != '') && (isset($_POST['B2bPurchaseBillDetail']['end_date'])) && ($_POST['B2bPurchaseBillDetail']['end_date'] != '')) {
            $_GET['B2bPurchaseBillDetail']['start_date'] = $_POST['B2bPurchaseBillDetail']['start_date'];
            $_GET['B2bPurchaseBillDetail']['end_date'] = $_POST['B2bPurchaseBillDetail']['end_date'];
            Yii::$app->session['start_date'] = $_POST['B2bPurchaseBillDetail']['start_date'];
            Yii::$app->session['end_date'] = $_POST['B2bPurchaseBillDetail']['end_date'];
        }
        if (isset($_GET['B2bPurchaseBillDetail']))
            $model->load($_GET, 'B2bPurchaseBillDetail');
        $columns = $model->getItemwiseColumns($columns);
        if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
            $this->exportCSV($model->itemwisesearch(), $columns);
        }

        return $this->render('b2bitemwise', [
            'model' => $model
        ]);
    }

public function actionB2bItemWiseExport()
    {
		
       $model = new B2bPurchaseBillDetail(['scenario' => 'search']);
        $this->updateMenuItems($model);
        $columns = [];
        if (isset($_POST['B2bPurchaseBillDetail']['columns'])) {
            $columns = $_POST['B2bPurchaseBillDetail']['columns'];
        }
        if (isset($_POST['B2bPurchaseBillDetail']['item_id'])) {
            $_GET['B2bPurchaseBillDetail']['item_id'] = $_POST['B2bPurchaseBillDetail']['item_id'];
            Yii::$app->session['item_id'] = $_POST['B2bPurchaseBillDetail']['item_id'];
        }
        if (isset($_POST['B2bPurchaseBillDetail']['start_date']) && ($_POST['B2bPurchaseBillDetail']['start_date'] != '') && (isset($_POST['B2bPurchaseBillDetail']['end_date'])) && ($_POST['B2bPurchaseBillDetail']['end_date'] != '')) {
            $_GET['B2bPurchaseBillDetail']['start_date'] = $_POST['B2bPurchaseBillDetail']['start_date'];
            $_GET['B2bPurchaseBillDetail']['end_date'] = $_POST['B2bPurchaseBillDetail']['end_date'];
            Yii::$app->session['start_date'] = $_POST['B2bPurchaseBillDetail']['start_date'];
            Yii::$app->session['end_date'] = $_POST['B2bPurchaseBillDetail']['end_date'];
        }
        if (isset($_GET['B2bPurchaseBillDetail']))
            $model->load($_GET, 'B2bPurchaseBillDetail');
        $columns = $model->getItemwiseColumns($columns);
	
        if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
	$this->exportCSV($model->itemwisesearch(), $columns);
 
        }
    }
	
	 public function actionB2borders()
    {
        $model = new B2bPurchaseBill(['scenario' => 'search']);
		
		//echo"<pre>"; print_r($model); die;
        $this->updateMenuItems($model);
        $columns = [];
        if (isset($_POST['B2bPurchaseBill']['start_date']) && ($_POST['B2bPurchaseBill']['start_date'] != '') && (isset($_POST['B2bPurchaseBill']['end_date'])) && ($_POST['B2bPurchaseBill']['end_date'] != '')) {
            $_GET['B2bPurchaseBill']['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            $_GET['B2bPurchaseBill']['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
            Yii::$app->session['start_date'] = $_POST['B2bPurchaseBill']['start_date'];
            Yii::$app->session['end_date'] = $_POST['B2bPurchaseBill']['end_date'];
        } else {
            if (! isset($_GET['Order_page'])) {
                if (! $this->isExportRequest() && Yii::$app->session['start_date'] == '' && Yii::$app->session['end_date'] == '') {
                    $_GET['B2bPurchaseBill']['start_date'] = date('Y-m-d');
                    $_GET['B2bPurchaseBill']['end_date'] = date('Y-m-d');
                    Yii::$app->session['start_date'] = date('Y-m-d');
                    Yii::$app->session['end_date'] = date('Y-m-d');
                }
            }
        }
        if (Yii::$app->session['start_date'] != '' && Yii::$app->session['end_date'] != '') {
            $_GET['B2bPurchaseBill']['start_date'] = Yii::$app->session['start_date'];
            $_GET['B2bPurchaseBill']['end_date'] = Yii::$app->session['end_date'];
        }
        if (isset($_POST['B2bPurchaseBill']['min_amt']) && ($_POST['B2bPurchaseBill']['min_amt'] != '') && (isset($_POST['B2bPurchaseBill']['max_amt'])) && ($_POST['B2bPurchaseBill']['max_amt'] != '')) {
            $_GET['B2bPurchaseBill']['min_amt'] = $_POST['B2bPurchaseBill']['min_amt'];
            $_GET['B2bPurchaseBill']['max_amt'] = $_POST['B2bPurchaseBill']['max_amt'];
            Yii::$app->session['order_min_amt'] = $_POST['B2bPurchaseBill']['min_amt'];
            Yii::$app->session['order_max_amt'] = $_POST['B2bPurchaseBill']['max_amt'];
        }
       
        Yii::warning(var_export(Yii::$app->session['start_date'], true), 'start_date');
        Yii::warning(var_export(Yii::$app->session['end_date'], true), 'endd_date');
        if (isset($_POST['B2bPurchaseBill']['columns'])) {
            $columns = $_POST['B2bPurchaseBill']['columns'];
        }
			$_GET ['B2bPurchaseBill']['status'] = B2bPurchaseBill::STATUS_APPROVED;
	
        if (isset($_GET['B2bPurchaseBill']))
			
            $model->load($_GET, 'B2bPurchaseBill');
        Yii::warning(var_export($model, true), '$model');
        $columns = $model->getExportColumns($columns);
        if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
		
            $this->exportCSV($model->search(), $columns);
        }

        return $this->render('b2borders', [
            'model' => $model
        ]);
    }
	
	
    // public function actionB2bReport()
    // {
        // $model = new OrderItem(['scenario' => 'search']);
        //
        // $this->updateMenuItems($model);
        // $columns = array();

        // $criteria = new CDbCriteria();
        // $criteria->compare('title', 'B2B');
        // $criteria->compare('type_id', '0');
        // $modePayment = PaymentMode::model()->find(;
        // if ($modePayment) {
            // Yii::$app->session['order_mode_payment'] = $modePayment->id;
        // } else {
            // Yii::$app->session['order_mode_payment'] = '';
        // }
        // if (isset($_POST['OrderItem']['columns'])) {
            // $columns = $_POST['OrderItem']['columns'];
        // }
        // if (isset($_POST['OrderItem']['start_date']) && ($_POST['OrderItem']['start_date'] != '') && (isset($_POST['OrderItem']['end_date'])) && ($_POST['OrderItem']['end_date'] != '')) {
            // $_GET['OrderItem']['start_date'] = $_POST['OrderItem']['start_date'];
            // $_GET['OrderItem']['end_date'] = $_POST['OrderItem']['end_date'];
            // Yii::$app->session['order_b2b_start_date'] = $_POST['OrderItem']['start_date'];
            // Yii::$app->session['order_b2b_end_date'] = $_POST['OrderItem']['end_date'];
        // } else {
            // $_GET['OrderItem']['start_date'] = date('Y-m-d');
            // $_GET['OrderItem']['end_date'] = date('Y-m-d');
            // Yii::$app->session ['order_item_end_date'] = date('Y-m-d');
        // }
        // if (isset($_GET['OrderItem']))
            // $model->load($_GET, 'OrderItem');
        // $columns = $model->getb2bTaxColumns($columns);
        // if ($this->isExportRequest()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
            // $this->exportCSV($model->b2bTaxsearch(), $columns);
        // }

        // $this->render('b2b', array(
            // 'model' => $model
        // ));
    // }
	
	public function actionDetails($id) 
	{
	
		$model = $this->loadModel($id, B2bPurchaseBill::class);
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
		return $this->render('b2borderview', [
			'model' => $model,'billDetail'=>$billDetail,
				'pobill'=>$pobill,
				'id'=>$id
		]);
	}

	/**
	 * Actions Yii 1's accessRules() refuses to a signed-in user.
	 *
	 * Read from accessRules() when this file was generated, not enforced by
	 * duplicating the rules: only actions refused outright are listed, and a
	 * rule decided by a role or an expression is left out.
	 */
	public function deniedActions()
	{
		return ['orderexcel', 'index'];
	}
}
