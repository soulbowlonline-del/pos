<?php	 			
public function actionOrder() {
	$arr = array (
			'controller' => $this->id,
			'action' => $this->action->id,
			'status' => 'NOK'
	);

	$model = new Customer ();


	$headers = getallheaders ();

	$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
	//$loginid = 1;
	Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, '$_POST' );
	if ($loginid) {
		if (isset ( $_POST ['item_details'] ) && isset ( $_POST ['mode_of_payment'] ) && isset ( $_POST ['mode_of_delivery'] ) && isset ( $_POST ['status_id'] )) {
				
				
			$status = $_POST ['status_id'];
				
			if ($status == '1') {
				$order = new Order ();
			} elseif ($status == '2') {
				$order = new OrderHold ();
			} else {
				$arr ['message'] = 'Order status wrong';
				$this->sendJSONResponse ( $arr );
			}
				
			$set = true;
			
			 $transaction = Yii::app ()->db->beginTransaction ();
			try { 
				if(isset($_POST ['customer_id'])){
					$customer = Customer::model()->findByPk($_POST ['customer_id'] );
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
				/* $criteria = new CDbCriteria();
				$criteria->order = 'bill_no desc';
				if($start_date != '' && $end_date != ''){
					$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
				}
				$latestorder = Order::model()->find($criteria);
				if($latestorder){
					$bill_no = $latestorder->bill_no + 1;
				}else{
					$bill_no = 1;
				}

				$order->bill_no = $bill_no; */
				$order->bill_date = date('Y-m-d');
				$order->mode_of_payment = $_POST ['mode_of_payment'];
				$order->mode_of_delivery = $_POST ['mode_of_delivery'];
				$order->total_amt = $_POST ['total_amt'];
				$order->discount_amt = $_POST ['discount_amt'];
				$order->outlet_id = $_POST ['outlet_id'];
				if(isset($_POST ['customer_id'])){
					$order->city_id = $customer->city_id;
					$order->state_id = $customer->state_id;
					$order->country_id = $customer->country_id;
					$order->customer_id = $customer->id;
				}
				if(isset($_POST ['online_order_id'])){
					$order->online_order_id = $_POST ['online_order_id'];
				}
				if(isset($_POST ['is_mobile'])){
					$order->is_mobile = $_POST ['is_mobile'];
				}
				
				$order->create_user_id = $loginid;
				if ($order->save ()) {
					if ($status == '1') {
						if(isset($_POST['credit_note_id'])){

							$creditnot = CreditNote::model()->findByAttributes(array('credit_number'=>$_POST['credit_note_id']));
							if($creditnot){
								$remain_amt = $creditnot->amt - $creditnot->amt_used;
								if(($remain_amt) >= ($order->total_amt)){
									$creditnot->amt_used = ($creditnot->amt_used) + $order->total_amt;
									$creditnot->save();
								}else{
									$set = false;
									$arr ['message'] = 'Credit note amount is less than total amount';
								}
							}else{
								$set = false;
								$arr ['message'] = 'Credit note is not found';
							}
						}
					}
					$item_arrays = json_decode ( $_POST ['item_details'] );
						
					if ($item_arrays) {
						foreach ( $item_arrays as $item_array ) {
								
							if ($status == '1') {
								$orderItem = new OrderItem ();
							} else {
								$orderItem = new OrderHoldItem ();
							}
							$criteria1 = new CDbCriteria ();
							$criteria1->compare ( "bar_code ", $item_array->bar_code);
							$itemdetail = ItemDetail::model ()->find ( $criteria1 );
								
							if($itemdetail){
									
								$orderItem->item_detail_id = $itemdetail->id;
								$orderItem->item_id = $itemdetail->item_id;
								$orderItem->qty = $item_array->qty;
								$orderItem->price = $orderItem->remove_format($item_array->base_price);
								if($item_array->discount_id != 0){
									$orderItem->discount_id = $item_array->discount_id;
										
									$orderItem->discount_amt = $item_array->discount_amt;
								}
								if($item_array->tax_id != 0){
									$orderItem->tax_id = $orderItem->getTaxValueID($item_array->tax_id);
									if ($status == '1') {
										$orderItem->original_tax = $item_array->tax_id;
									}
									$orderItem->tax_amount = $item_array->tax_amt;
								}
								$orderItem->sale_rate = $item_array->sale_rate;
								$orderItem->mrp = $item_array->mrp;
								$orderItem->total_amt = $item_array->total_amount;
									
								$orderItem->cgst_per = $item_array->cgst_per;
								$orderItem->sgst_per = $item_array->sgst_per;
								$orderItem->cess_per = $item_array->cess_per;
								$orderItem->igst_per = $item_array->igst_per;
								$orderItem->cgst_amt = $item_array->cgst_amt;
								$orderItem->sgst_amt = $item_array->sgst_amt;
								$orderItem->cess_amt = $item_array->cess_amount;
								$orderItem->igst_amt = $item_array->igst_amount;
								$orderItem->create_user_id = $loginid;
								if ($status == '1') {
									$orderItem->order_id = $order->id;
								$orderItem->status = '1';	
								}else{
									$orderItem->order_hold_id = $order->id;
								}
								Yii::log ( CVarDumper::dumpAsString ( $orderItem ), CLogger::LEVEL_WARNING, '$orderItem' );
									
								if ($orderItem->save ()) {

									if ($status == '1') {
										/*if(isset($_POST ['online_order_id'])){
										$online_order = OnlineOrder::model()->findByAttributes(array('id'=>$_POST['online_order_id']));
										if($online_order){
											$online_order->order_status = OnlineOrder::ORDERSTATUS_PACKED;
											$online_order->saveAttributes(array('order_status'));
											
											$order_id = $online_order->order_id;
											//$order_id = 100007417;
											$ch = curl_init ();
											
											curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api" );
											curl_setopt ( $ch, CURLOPT_POST, 1 );
											
											curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
													'sKeY' => 'f$*@g644^@cghjku853c$','order_id'=>$online_order->order_id,'action'=>'update_dispatch_status','status'=>'1'
											) ) );
											
											curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
											
											$server_output = curl_exec ( $ch );
											
											curl_close ( $ch );
											$response = json_decode ( $server_output, true );
										}
										}*/
										$order->UpdateStock($item_array->qty,$itemdetail->id);
									}

								} else {
										
									//print_r($orderItem->getErrors());exit;
									$set = false;
								}
							}else{
								$set = false;
							}
								
								
						}
					}
						
					if ($set == true) {
						$transaction->commit ();
						$criteria = new CDbCriteria();
						$criteria->order = 'bill_no desc';
						if($start_date != '' && $end_date != ''){
							$criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
						}
						$latestorder = Order::model()->find($criteria);
						if($latestorder){
							$bill_no = $latestorder->bill_no + 1;
						}else{
							$bill_no = 1;
						}
							
						$order->bill_no = $bill_no;
						$order->saveAttributes(array('bill_no'));
						if ($status == '1') {
						$data = array();
						
						$item_list = array();
						$itemorderitems = OrderItem::model()->findAllByAttributes(array('order_id'=>$order->id));
						if($itemorderitems){
						foreach($itemorderitems as $orderitem){
						$get_item = Item::model()->findByPk($orderitem->item_id);
							if($get_item){
								$item_list[$get_item->item_code] = array('name'=>$get_item->title,'qty'=>$orderitem->qty,'price'=>($orderitem->qty *$orderitem->sale_rate));
							}
						}
						}
						$grand = $order->total_amt;
						$shipping = '0.00';
						/*if($order->total_amt >= 2000){
							$shipping = '0.00';
						}else{
							$shipping = '50.00';
							$grand = $order->total_amt + $shipping;
						}*/

						$data['items']= $item_list;
						$data['sub_total']= $order->total_amt;
						$data['grand_total']= $grand;
						$data['shipping']= $shipping;

						$item_str = '';
						$item_str = json_encode($data);
							Yii::log ( CVarDumper::dumpAsString ( $item_str ), CLogger::LEVEL_WARNING, '$item_str' );

						if(isset($_POST ['online_order_id'])){
							$date = date('Y-m-d H:i:s');
										$online_order = OnlineOrder::model()->findByAttributes(array('id'=>$_POST['online_order_id']));
										if($online_order){
											$online_order->order_status = OnlineOrder::ORDERSTATUS_PACKED;
											$online_order->saveAttributes(array('order_status'));
											
											$order_id = $online_order->order_id;
										//	$order_id = 100007417;
											$ch = curl_init ();
											
											curl_setopt ( $ch, CURLOPT_URL, "http://soulbowl.in/rest/api" );
											curl_setopt ( $ch, CURLOPT_POST, 1 );
											
											curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
													'sKeY' => 'f$*@g644^@cghjku853c$','order_id'=>$online_order->order_id,'action'=>'update_dispatch_status2','status'=>'1','data'=>$item_str,'date'=>$date
											) ) );
											
											curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
											
											$server_output = curl_exec ( $ch );
											
											curl_close ( $ch );
											$response = json_decode ( $server_output, true );
										}
										}
							}
						
						$taxarr = array();
						if ($status == '1') {
						$order->SendSms();
						}
						$arr['status'] = 'OK';
						
						if ($status == '1') {
							$bill_prefix = 'B';
							$outlet = Outlet::model()->findByPk($order->outlet_id);
							if($outlet){
								if($outlet->bill_prefix != ''){
									$bill_prefix = $outlet->bill_prefix;
								}else{
									$bill_prefix = 'B';
								}
									
							}
							$arr['bill_no'] = $order->getOrderBillNo();
							$criteria = new CDbCriteria();
							//$criteria->group = 'tax_id';
							$criteria->select ='SUM(qty) AS qty,SUM(tax_amount) AS tax_amount, SUM(cgst_amt) AS cgst_amt, SUM(sgst_amt) AS sgst_amt, SUM(cess_amt) AS cess_amt, SUM(igst_amt) AS igst_amt,t.*';
							$criteria->with = 'item';
							$criteria->group = 'tax_id,item_id,item.hsn_code';
							$criteria->compare('order_id',$order->id);
							$itemms = OrderItem::model()->findAll($criteria);

							if($itemms){
								foreach($itemms as $itemmtax){
									$taxarr[] = $itemmtax->getTaxArray();
								}
							}
							$arr['taxes'] =$taxarr;
							
						}else{
							$arr['bill_no'] = '0';
						}
						
						$arr ['message'] = 'Order is saved Successfully';
					} else {
						$transaction->rollback ();
						$arr ['message'] = 'Try again';
					}
				
				}

				else {
						//print_r($order->getErrors());exit;
					$set = false;
				}
			 } catch ( Exception $e ) {
				$transaction->rollback ();
			} 
		}
	}
	$this->sendJSONResponse ( $arr );
	Yii::log ( CVarDumper::dumpAsString ( $this->sendJSONResponse ( $arr ) ), CLogger::LEVEL_WARNING, 'order_response' );
}
