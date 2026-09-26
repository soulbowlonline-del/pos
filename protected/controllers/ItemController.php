<?php
class ItemController extends GxController {
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
								
								'addItems',
								//'check',
								'online',
								//'getDiffStocks'
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
								'toggle',
								'search',
								'admin',
								'delete',
								'extra',
								'vendor',
								'ajaxUpdate',
								'import',
								'import1',
								'vendorCreate',
								'vendorList',
								'adjuststock',
								'ajaxCategory',
								'ajaxCompany',
								'adjust',
								'hsnCodeList',
								'getBarCodeStock',
								'expireStock',
								'barcode',
								'printBarcode',
								'import2',
								'stockImport',
								'checkOutlets',
								'ajaxExpireItems',
								'ajaxExpireValues',
								'saveExpire',
								'gridUpdate',
								'taxUpdate',
								'getAllItems',
								'toggleItems',
								'report',
								'getStockList',
								'getSameCodeItems',
								'scannedItems',
								'getitemsbydate',
								'export',
								'exportnew',
								'importTaxData',
								'batchUpdatePrices',
								'checkUpdateStatus'
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
	
	public function actionGetSameCodeItems() {
		$items = Item::model()->findAll();
		if($items){
			foreach($items as $item){
				 $item->item_code = $item->id;
				$item->saveAttributes(array('item_code')); 
				/*  $criteria = new CDbCriteria ();
				$criteria->addCondition ( 'item_code ='.$item->item_code);
				$criteria->addCondition ( 'id !='.$item->id );
				$sameitems = Item::model ()->findAll ( $criteria );
				if($sameitems){
					echo 'item'.$item->id;
					echo '<br>';
					foreach($sameitems as $sameitem){
						echo 'same'.$sameitem->id;
						echo '<br>';
					}
				}  */
			}
		}
	
	}
	public function actionGetStockList() {
		$start_date = '2019-04-01';
		$end_date = '2019-04-18';
		$criteria = new CDbCriteria ();
		$criteria->addBetweenCondition ( 'date(create_time)', $start_date, $end_date );
		$criteria->addCondition ( 'Qty = 0.000' );
		$logs = StockLog::model ()->findAll ( $criteria );
		if ($logs) {
			foreach ( $logs as $log ) {
				echo 'Item Name: ' . $log->item->title . ' Qty: ' . $log->item->getTotalRemainingQuantity ();
				echo '<br>';
			}
		}
	}
	public function actionGetDiffStocks() {
		$ordered_qty = '0.000';
		$ordered_refund_qty = '0.000';
		$total_added_qty = '0.000';
		$added_qty = '0.000';
		$detail_added_qty = '0.000';
		$subtracted_qty = '0.000';
		$items = Item::model ()->findAllByAttributes ( array (
				'status' => Item::STATUS_ACTIVE 
		) );
		if ($items) {
			foreach ( $items as $item ) {
				$item_remain_qty = $item->getTotalRemainingQuantity ();
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'item_id =' . $item->id );
				$criteria->addCondition ( 'type_id =' . StockLog::TYPE_ADDED );
				$criteria->select = 'sum(Qty) as Qty';
				$log_added_qty = StockLog::model ()->find ( $criteria );
				if ($log_added_qty) {
					$added_qty = $log_added_qty->Qty;
				}
				
				$criteria_new = new CDbCriteria ();
				$criteria_new->addCondition ( 'item_id =' . $item->id );
				$criteria_new->select = 'sum(open_stock_qty) as open_stock_qty';
				$detail_qty = ItemDetail::model ()->find ( $criteria_new );
				if ($detail_qty) {
					$detail_added_qty = $detail_qty->open_stock_qty;
				}
				
				$total_added_qty = bcadd ( $added_qty, $detail_added_qty, 3 );
				
				$criteria1 = new CDbCriteria ();
				$criteria1->addCondition ( 'item_id =' . $item->id );
				$criteria1->addNotInCondition ( 'type_id', array (
						StockLog::TYPE_ADDED,
						StockLog::TYPE_ORDER 
				) );
				$criteria1->select = 'sum(Qty) as Qty';
				$log_subtracted_qty = StockLog::model ()->find ( $criteria1 );
				if ($log_subtracted_qty) {
					$subtracted_qty = $log_subtracted_qty->Qty;
				}
				
				$remaining_quantity = bcsub ( $total_added_qty, $subtracted_qty, 3 );
				
				$criteria2 = new CDbCriteria ();
				$criteria2->addCondition ( 'item_id =' . $item->id );
				$criteria2->select = 'sum(qty) as qty';
				$order = OrderItem::model ()->find ( $criteria2 );
				
				if ($order) {
					$ordered_qty = $order->qty;
				}
				
				$criteria3 = new CDbCriteria ();
				$criteria3->addCondition ( 'item_id =' . $item->id );
				$criteria3->select = 'sum(qty) as qty';
				$order_refund = OrderRefundItem::model ()->find ( $criteria3 );
				
				if ($order_refund) {
					$ordered_refund_qty = $order_refund->qty;
				}
				
				$final_ordered_quantity = bcsub ( $ordered_qty, $ordered_refund_qty, 3 );
				
				$remain_quantity = bcsub ( $remaining_quantity, $final_ordered_quantity, 3 );
				
				$criteria4 = new CDbCriteria ();
				$criteria4->addCondition ( 'item_id =' . $item->id );
				$criteria4->select = 'sum(adjusted) as adjusted';
				$adjust_log = StockAdjustLog::model ()->find ( $criteria4 );
				
				if ($adjust_log) {
					$adjusted_qty = $adjust_log->adjusted;
				}
				
				if ($adjusted_qty < 0) {
					$total_remain_quantity = bcsub ( $remain_quantity, abs ( $adjusted_qty ), 3 );
				} else {
					$total_remain_quantity = bcadd ( $remain_quantity, abs ( $adjusted_qty ), 3 );
				}
				
				if ($total_remain_quantity != $item_remain_qty) {
					echo '<b>Name : </b>' . $item->title . ' <b>Code :</b> ' . $item->item_code . ' <b>Remain Qty:</b> ' . $item_remain_qty . ' <b>Log Qty:</b> ' . $total_remain_quantity;
					echo '<br/>';
				}
			}
		}
	}
	public function actionToggleItems() {
		$items = Item::model ()->findAllByAttributes ( array (
				'status' => Item::STATUS_ACTIVE 
		) );
		if ($items) {
			foreach ( $items as $item ) {
				
				$itemdetailss = ItemDetail::model ()->findAllByAttributes ( array (
						'item_id' => $item->id 
				) );
				if ($itemdetailss) {
					foreach ( $itemdetailss as $itemdetails ) {
						$itemdetails->status = ItemDetail::STATUS_ACTIVE;
						$itemdetails->saveAttributes ( array (
								'status' 
						) );
					}
				}
			}
		}
	}
	public function actionOnline() {
		ini_set ( 'memory_limit', '-1' );
		
		ini_set ( 'max_execution_time', '0' );
		/*
		 * $arr = array (
		 * 'controller' => $this->id,
		 * 'action' => $this->action->id,
		 * 'status' => 'NOK'
		 * );
		 */
		$list = array ();
		$newTime = date ( "Y-m-d H:i:s", strtotime ( date ( "Y-m-d H:i:s" ) . " -50 minutes" ) );
		$criteria = new CDbCriteria ();
		
		// $criteria->limit = '10';
		// $criteria->addCondition('status ='.Item::STATUS_ACTIVE);
		// $criteria->addCondition('update_time >'.'"'.$newTime.'"');
		$items = Item::model ()->findAll ( $criteria );
		
		if ($items) {
			foreach ( $items as $item ) {
				$list [] = $item->toonlineArray ();
			}
		}
		
		$pagename = 'Products';
		
		$newFileName = './' . $pagename . ".txt";
		// file_put_contents ( $newFileName, print_r($list),true );
		
		$newFileContent = print_r ( json_encode ( $list ), true );
		if (file_put_contents ( $newFileName, $newFileContent ) !== false) {
			echo "File created (" . basename ( $newFileName ) . ")";
			// connect and login to FTP server
			/*
			$ftp_server = "192.169.235.187";
			$ftp_conn = ftp_connect ( $ftp_server ) or die ( "Could not connect to $ftp_server" );
			$ftp_username = 'newapi@soulbowl.in';
			$ftp_userpass = 'Lobo@4321!';
			$login = ftp_login ( $ftp_conn, $ftp_username, $ftp_userpass );
			
			$file = "Products.txt";
			$check_file_exist = "D.txt"; // combine string for easy use
			
			$contents_on_server = ftp_nlist ( $ftp_conn, '/' ); // Returns an array of filenames from the specified directory on success or FALSE on error.
			                                                    
			// Test if file is in the ftp_nlist array
			if (! in_array ( $check_file_exist, $contents_on_server )) {
				// upload file
				if (ftp_put ( $ftp_conn, "Products.txt", $file, FTP_ASCII )) {
					echo "Successfully uploaded $file.";
				} else {
					echo "Error uploading $file.";
				}
			} else {
				echo "file is uploading";
			}
			
			// close connection
			ftp_close ( $ftp_conn );
			*/

			/*send data to sect4 greycell*/
			
			//$ftp_server =  "143.110.254.206";//Yii::app()->params['ftp_server'] ;
			//Soul Bowl Demo Server FTP Credentials
			// username ftp_user
			// passowrd gtech_ftp
			// host 202.164.34.118
			$ftp_server =  "143.110.254.206";
			$ftp_conn = ftp_connect ( $ftp_server ) or die ( "Could not connect to $ftp_server" );
			$ftp_username = Yii::app()->params['ftp_username'];
			$ftp_userpass = Yii::app()->params['ftp_password'];
			$login = ftp_login ( $ftp_conn, $ftp_username, $ftp_userpass );
			
			$file = "Products.txt";
			$check_file_exist = "D.txt"; // combine string for easy use
			
			$contents_on_server = ftp_nlist ( $ftp_conn, '/' ); // Returns an array of filenames from the specified directory on success or FALSE on error.
			                                                    
			// Test if file is in the ftp_nlist array
			if (! in_array ( $check_file_exist, $contents_on_server )) {
				// upload file
				$uploadfilename = "SBProducts".date('YmdHis').".txt";
				//echo $uploadfilename;die;
				if (ftp_put ( $ftp_conn, $uploadfilename, $file, FTP_ASCII )) {
					echo "Successfully uploaded $file.";
				} else {
					echo "Error uploading $file.";
				}
			} else {
				echo "file is uploading";
			}
			
			// close connection
			ftp_close ( $ftp_conn );
			
			/*end */
			
			
		} else {
			echo "Cannot create file (" . basename ( $newFileName ) . ")";
		}
		/*
		 * $arr ['status'] = 'OK';
		 *
		 * $arr ['item'] = $list;
		 */
		
		// $this->sendJSONResponse ( $arr );
	}
	public function actionToggle() {
		if (isset ( $_POST ['pk'] ) && (isset ( $_POST ['value'] )) && (isset ( $_POST ['name'] ))) {
			$item = Item::model ()->findByPk ( $_POST ['pk'] );
			if ($item) {
				$item->status = $_POST ['value'];
				$item->saveAttributes ( array (
						'status' 
				) );
				
				$itemdetailss = ItemDetail::model ()->findAllByAttributes ( array (
						'item_id' => $item->id 
				) );
				if ($itemdetailss) {
					foreach ( $itemdetailss as $itemdetails ) {
						$itemdetails->status = $_POST ['value'];
						$itemdetails->saveAttributes ( array (
								'status' 
						) );
					}
				}
				$mrsdetails = MrsDetail::model ()->findAllByAttributes ( array (
						'item_id' => $item->id,
						'status' => Mrs::STATUS_PENDING 
				) );
				Yii::log ( CVarDumper::dumpAsString ( $mrsdetails ), CLogger::LEVEL_WARNING, '$mrsdetails' );
				if ($mrsdetails) {
					foreach ( $mrsdetails as $mrsdetail ) {
						$mrs_id = $mrsdetail->mrs_id;
						$mrs_item_id = $mrsdetail->item_id;
						$criteria1 = new CDbCriteria ();
						
						$criteria1->compare ( "mrs_id ", $mrsdetail->mrs_id );
						
						$mrsItems = MrsDetail::model ()->count ( $criteria1 );
						if ($mrsdetail && $item->id == $mrsdetail->item_id) {
							$mrsdetail->delete ();
						}
						
						if ($mrsItems == 1) {
							$mrs = Mrs::model ()->findByPk ( $mrs_id );
							if ($mrs && $item->id == $mrs_item_id) {
								
								$mrs->delete ();
							}
						}
					}
				}
			}
		}
	}
	public function actioncheck() {
		$setting = Setting::model ()->find ();
		if ($setting) {
			$setting->days = 10;
			$setting->create_time = '2018-08-10 12:11:11';
			$setting->save ();
		}
	}
	public function actionTaxUpdate() {
		if (isset ( $_POST ['pk'] ) && (isset ( $_POST ['value'] )) && (isset ( $_POST ['name'] ))) {
			$item = Item::model ()->findByPk ( $_POST ['pk'] );
			if ($item) {
				$itemDetails = ItemDetail::model ()->findAllByAttributes ( array (
						'item_id' => $item->id 
				) );
				if ($itemDetails) {
					foreach ( $itemDetails as $itemDetail ) {
						$itemDetail->tax_id = $_POST ['value'];
						$itemDetail->saveAttributes ( array (
								'tax_id' 
						) );
					}
				}
			}
		}
	}
	public function actionGridUpdate() {
		if (isset ( $_POST ['pk'] ) && (isset ( $_POST ['value'] )) && (isset ( $_POST ['name'] ))) {
			$item = Item::model ()->findByPk ( $_POST ['pk'] );
			if ($item) {
				$name = $_POST ['name'];
				if ($name != 'vendor_id') {
					$item->$name = $_POST ['value'];
					$item->saveAttributes ( array (
							$name 
					) );
				} else {
					$vendor_id = $_POST ['value'];
					$itemvendor = ItemVendor::model ()->findByAttributes ( array (
							'item_detail_id' => $item->id,
							'vendor_id' => $vendor_id 
					) );
					if (! $itemvendor) {
						$itemvendor = new ItemVendor ();
					}
					$itemvendor = new ItemVendor ();
					$itemvendor->item_detail_id = $item->id;
					$itemvendor->vendor_id = $vendor_id;
					$itemvendor->save ();
				}
			}
		}
	}
	public function actionAddItems() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$list = array ();
		$last = 0;
		$setting = Setting::model ()->find ();
		if ($setting) {
			if (isset ( $setting ['last_item_id'] ) && ($setting ['last_item_id'] != null)) {
				$last = $setting ['last_item_id'];
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->limit = '1';
		$criteria->addCondition ( 'id >' . $last );
		// $criteria->addCondition('id >'.$last);
		$criteria->addCondition ( 'status =' . ItemDetail::STATUS_ACTIVE );
		$itemdetails = ItemDetail::model ()->findAll ( $criteria );
		if ($itemdetails) {
			foreach ( $itemdetails as $itemdetail ) {
				$last_id = $itemdetail->id;
				$list [] = $itemdetail->toonlineArray ();
			}
			
			$setting->last_item_id = $last_id;
			$setting->saveAttributes ( array (
					'last_item_id' 
			) );
		}
		if (! empty ( $list )) {
			/*
			 * $ch = curl_init( "https://soulbowl.in/shell/apis/import_allproducts.php" );
			 * # Setup request to send json via POST.
			 * $payload = json_encode($list);
			 *
			 * curl_setopt( $ch, CURLOPT_POSTFIELDS, "products=$payload&skey=c1a6dd7c6e8453f79dae51acca3510c3" );
			 * curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			 * # Return response instead of printing.
			 * curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			 * # Send request.
			 * $result = curl_exec($ch);
			 * curl_close($ch);
			 */
			$ch = curl_init ();
			$payload = json_encode ( $list );
			
			curl_setopt ( $ch, CURLOPT_URL, "https://soulbowl.in/shell/apis/import_allproducts.php" );
			curl_setopt ( $ch, CURLOPT_POST, 1 );
			/*
			 * curl_setopt($ch, CURLOPT_POSTFIELDS,
			 * "products=$payload&skey=c1a6dd7c6e8453f79dae51acca3510c3");
			 */
			
			// in real life you should use something like:
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( array (
					'products' => $payload,
					'skey' => 'c1a6dd7c6e8453f79dae51acca3510c3' 
			) ) );
			
			// receive server response ...
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
			
			$server_output = curl_exec ( $ch );
			
			curl_close ( $ch );
			// Print response.
			// new soulbowl hita
			echo "start";
		/*	print_r($list);
			$ch = curl_init();
$curlConfig = array(
    CURLOPT_URL            => "https://sect4.soulbowl.in/pos/import_new_product_soul.php",
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => array(
        'field1' => 'some date',
        'field2' => 'some other data',
    )
);
curl_setopt_array($ch, $curlConfig);
$result = curl_exec($ch);
var_dump($result);
curl_close($ch);
*/
/*
			$chs = curl_init ();
			$payloads = json_encode ( $list );
			
			curl_setopt ( $chs, CURLOPT_URL, "https://sect4.soulbowl.in/pos/import_new_product_soul.php" );
			curl_setopt ( $chs, CURLOPT_POST, 1 );
			/*
			 * curl_setopt($ch, CURLOPT_POSTFIELDS,
			 * "products=$payload&skey=c1a6dd7c6e8453f79dae51acca3510c3");
			 */
			/*
			// in real life you should use something like:
			curl_setopt ( $chs, CURLOPT_POSTFIELDS, http_build_query ( array (
					'products' => $payloads,
					'skey' => 'c1a6dd7c6e8453f79dae51acca3510c3' 
			) ) );
			
			// receive server response ...
			curl_setopt ( $chs, CURLOPT_RETURNTRANSFER, true );
			
			$server_outputff = curl_exec ( $chs );
			echo "end";
			var_dump($server_outputff);
			curl_close ( $chs );*/
			
		}
	}
	public function actionGetBarCodeStock() {
		$remaining_quantity = 0;
		if (isset ( $_POST ['item_detail_id'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->compare('id', PostId::get('item_detail_id'));
			$item_detail = ItemDetail::model ()->find ( $criteria );
			if ($item_detail) {
				$remaining_quantity = '0.000';
				$add_quantity = '0.000';
				$sub_quantity = '0.000';
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'item_detail_id =' . $item_detail->id );
				$criteria->order = 'id asc';
				$criteria->addCondition ( "balance_qty > 0.000" );
				$criteria->addCondition ( 'item_detail_id IS NOT NULL' );
				$stocks = ItemStock::model ()->findAll ( $criteria );
				
				if (! empty ( $stocks )) {
					foreach ( $stocks as $stock ) {
						$add_quantity = ($add_quantity) + ($stock->balance_qty);
					}
				}
				$criteria1 = new CDbCriteria ();
				$criteria1->addCondition ( 'item_detail_id =' . $item_detail->id );
				$criteria1->order = 'id asc';
				$criteria1->addCondition ( "balance_qty < 0.000" );
				$criteria1->addCondition ( 'item_detail_id IS NOT NULL' );
				$stocks = ItemStock::model ()->findAll ( $criteria1 );
				
				if (! empty ( $stocks )) {
					foreach ( $stocks as $stock ) {
						$sub_quantity = ($sub_quantity) + abs ( $stock->balance_qty );
					}
				}
				$remaining_quantity = bcsub ( $add_quantity, $sub_quantity, 3 );
			}
			/*
			 * if($remaining_quantity < 0)
			 * {
			 * $remaining_quantity =0;
			 * }
			 */
		}
		
		echo $remaining_quantity;
	}
	public function actionAdjust() {
		

		if (isset ( $_POST ['formData'] ) && isset ( $_POST ['qtyData'] ) && isset ( $_POST ['remainData'] )&& isset ( $_POST ['remarksData'] )) {
			$posted = $_POST;
			
			if ($posted) {
				
				$formdata = $_POST ['formData'];
				$saleStatus = Yii::app()->params['saleStatus'];
				//echo "<pre>"; print_r($formdata); echo "<pre>";die;
				foreach ( $formdata as $key => $itemDetail ) {
					
					if (($posted ['formData'] [$key] != '') && ($posted ['qtyData'] [$key] != '')) {
						
						$criteria = new CDbCriteria ();
						$criteria->order = 'id asc';
						$criteria->limit = 1;
						$outlet_model = Outlet::model ()->find ( $criteria );
						if ($outlet_model) {
							$outlet = $outlet_model->id;
						}
						$itemDetail = ItemDetail::model ()->findByPk ( $posted ['formData'] [$key] );
						$current = '0.000';
						$adjusted = '0.000';
						$actual = '0.000';
						if ($itemDetail) {
							$itemStock = ItemStock::model ()->findByAttributes ( array (
									'item_detail_id' => $itemDetail->id,
									'outlet_id' => $outlet 
							) );
							
							/*for item vendor*/
								$ItemVendor = ItemVendor::model ()->findByAttributes ( array (
									'item_detail_id' => $itemDetail->item_id
								
							) );
							$itemStock->vendor_id=$ItemVendor->vendor_id;
							/*end item vendor*/
							$item = Item::model ()->findByPk ( $itemDetail->item_id );
							if ($saleStatus) { // sale on
								if($posted ['remainData'] [$key] > $posted ['qtyData'] [$key]){
									$posted ['type'] [$key] = ItemStock::TYPE_SUBSTRACT;
									$posted ['qtyData'] [$key] = $posted ['remainData'] [$key] - $posted ['qtyData'] [$key];
								}else{
									$posted ['type'] [$key] = ItemStock::TYPE_ADDED;
									$posted ['qtyData'] [$key] = $posted ['qtyData'] [$key] - $posted ['remainData'] [$key];
								}
							} else { // sale off
								$posted ['type'] [$key] = ItemStock::TYPE_ADDED;
							}
							
							$existingStock = ($itemStock != null);
							if ($itemStock == null) {
								
								$itemStock = new ItemStock ();
								if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
									$itemStock->purchase_qty = $itemStock->purchase_qty + $posted ['qtyData'] [$key];
									$itemStock->balance_qty = $itemStock->balance_qty + $posted ['qtyData'] [$key];
								} else {
									// if($itemStock->balance_qty >= $posted['qtyData'][$key] ){
									$itemStock->purchase_qty = $posted ['qtyData'] [$key];
									$itemStock->balance_qty = $posted ['qtyData'] [$key];
									// }
								}
								$itemStock->batch_number = User::randomBarcode ( '5' );
								$itemStock->item_detail_id = $itemDetail->id;
								
								if ($item) {
									
									$itemStock->item_id = $item->id;
									$itemStock->mrp = $itemDetail->getItemDetailMrp ();
									$itemStock->base_price = $item->purchase_price;
									$itemStock->outlet_id = $outlet;
								}
							} else {
								// Existing batch: the change is applied in the database when
								// the row is saved below (addToBalance), so a sale or GRN
								// landing at the same moment is not overwritten.
								if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
									$stockDelta = $posted ['qtyData'] [$key];
									$purchaseDelta = $posted ['qtyData'] [$key];
								} else {
									$stockDelta = - $posted ['qtyData'] [$key];
									$purchaseDelta = 0;
								}
							}
							$current = $item->getOutletTotalRemainingQuantity ( $itemDetail->id, $outlet );
							if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
								$actual = bcadd ( $current, $posted ['qtyData'] [$key], 3 );
							} else {
								if ($current == 0) {
									$actual = bcsub ( $current, $posted ['qtyData'] [$key], 3 );
								}
								
								if ($current < 0) {
									$bquantity = abs ( $current );
									$remain = bcadd ( $current, $posted ['qtyData'] [$key], 3 );
									$actual = '-' . $remain;
								}
								if ($current > 0) {
									if ($current > $posted ['qtyData'] [$key]) {
										$actual = bcsub ( $current, $posted ['qtyData'] [$key], 3 );
									} else {
										$actual = bcsub ( $posted ['qtyData'] [$key], $current, 3 );
									}
								}
								// $actual = $current - $posted ['qtyData'] [$key];
							}
							if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
								$adjusted = $posted ['qtyData'] [$key];
							} else {
								$adjusted = '-' . $posted ['qtyData'] [$key];
							}
						
							if ($existingStock ? ($itemStock->saveExceptQty () && $itemStock->addToBalance ( $stockDelta, $purchaseDelta )) : $itemStock->save ()) {
								$criteria = new CDbCriteria();
								$criteria->compare('status',MrsAdjust::STATUS_PENDING);
								$criteria->compare('item_id',$itemStock->item_id);
								$criteria->order = 'id desc';
								$mrsadjust = MrsAdjust::model()->find($criteria);
								if($mrsadjust){
									$mrsadjust->status = MrsAdjust::STATUS_DONE;
									$mrsadjust->saveAttributes(array('status'));
								}
								$itemDetail->update_time = date ( 'Y-m-d H:i:s' );
								$itemDetail->saveAttributes ( array (
										'update_time' 
								) );
								$item->update_time = date ( 'Y-m-d H:i:s' );
								$item->saveAttributes ( array (
										'update_time' 
								) );
								$log = new StockAdjustLog ();
								$log->date = date ( 'Y-m-d' );
								$log->item_detail_id = $itemDetail->id;
								$log->item_id = $item->id;
								$log->mrp = $itemDetail->getItemDetailMrp ();
								$log->outlet_id = $outlet;
								$log->current_stock = $current;
								$log->actual_stock = $actual;
								$log->adjusted = $adjusted;
								$log->remarks = $posted ['remarksData'] [$key];
								if ($log->save ()) {
									$stocklog = new StockLog ();
									$stocklog->item_detail_id = $itemDetail->id;
									$stocklog->item_id = $item->id;
									$stocklog->batch_no = $itemStock->batch_number;
									if ($itemDetail) {
										$stocklog->current_qty = $itemDetail->getStockQty ();
										if ($posted ['type'] [$key] == ItemStock::TYPE_ADDED) {
											$stocklog->previous_qty = bcsub ( $itemDetail->getStockQty (), $posted ['qtyData'] [$key], 3 );
										} else {
											$stocklog->previous_qty = bcadd ( $itemDetail->getStockQty (), $posted ['qtyData'] [$key], 3 );
										}
										// $stocklog->previous_qty = $itemDetail->getStockQty();
									}
									$stocklog->Qty = $adjusted;
									$stocklog->outlet_id = $outlet;
									$stocklog->vendor_id = $itemStock->vendor_id;
									$stocklog->type_id = StockLog::TYPE_ADJUSTED;
									if ($stocklog->save ()) {
										
										$criteriaItemStock = new CDbCriteria();
										$criteriaItemStock->compare('item_id',$itemDetail->item_id);
										$criteriaItemStock->select ='SUM(balance_qty) AS balance_qty';
										$criteriaItemStock->group = 'item_id';
										$mrsItemStock = ItemStock::model()->find($criteriaItemStock);	
										
										
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
								}else{
									
										/*Create MRS section*/
										$criteriaMrs = new CDbCriteria();
										$criteriaMrs->order = 'id desc';
										$criteriaMrs->limit = '1';
										$criteriaMrs->addCondition('vendor_id ='.$itemStock->vendor_id);
										$vendorMRS = Mrs::model()->find($criteriaMrs);
								
								// echo"<pre>"; print_r($vendorMRS); die;
									if($vendorMRS->id){
									$item = Item::model()->findByPk($item->id);
									$criteriaMrsD = new CDbCriteria();
									$criteriaMrsD->order = 'id desc';
									$criteriaMrsD->limit = '1';
									$criteriaMrsD->addCondition('mrs_id ='.$vendorMRS->id);
									$criteriaMrsD->addCondition('item_id ='.$item->id);
									$vendorMRSD = MrsDetail::model()->find($criteriaMrsD);
									// echo"<pre>"; print_r($vendorMRSD); die;
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
										Yii::log ( CVarDumper::dumpAsString ( $itemStock->vendor_id ), CLogger::LEVEL_WARNING, '$mrs_vendor_id' );
										if($itemStock->vendor_id != null){
										$mrs = Mrs::model()->findByAttributes(array('status'=>Mrs::STATUS_PENDING,'vendor_id'=>$itemStock->vendor_id,
										'outlet_id'=>$outlet
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
										$mrs->outlet_id = $outlet;
										$mrs->vendor_id = $itemStock->vendor_id;
									
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
										$mrsdetail = MrsDetail::model()->findByAttributes(array('item_detail_id'=>$itemStock->item_detail_id,
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
										$mrsdetail->item_id =$itemDetail->item_id;
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
									
								}
										
										
									} else {
										print_r ( $stocklog->getErrors () );
										exit ();
									}
								} else {
									print_r ( $log->getErrors () );
									exit ();
								}
							}
						}
					}
				}
			}
		}
	}
	public function actionAjaxCategory() {
		$option = '';
		if (isset ( $_POST ['category_id'] )) {
			
			$cats = ItemCategory::model ()->findAllByAttributes ( array (
					'parent_id' => $_POST ['category_id'],
					'status' => ItemCategory::STATUS_ACTIVE 
			) );
			
			if ($cats) {
				$option .= '<select id="Item_sub_category_id" name="Item[sub_category_id]" class="form-control">';
				
				foreach ( $cats as $cat ) {
					
					$option .= '<option value="' . $cat->id . '">' . $cat->title . '</option>';
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
	public function actionAjaxCompany() {
		$option = '';
		if (isset ( $_POST ['company_id'] )) {
			
			$cats = ItemCompany::model ()->findAllByAttributes ( array (
					'parent_id' => $_POST ['company_id'],
					'status' => ItemCompany::STATUS_ACTIVE 
			) );
			
			if ($cats) {
				$option .= '<select id="Item_sub_company_id" name="Item[sub_company_id]" class="form-control">';
				
				foreach ( $cats as $cat ) {
					
					$option .= '<option value="' . $cat->id . '">' . $cat->title . '</option>';
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
	public function actionGetAllItems($vendor_id = null) {
		$user = Yii::app ()->user->model;
		$vendor_arr = array ();
		
		$exist = [ ];
		$lists = [ ];
		$term = Yii::app ()->request->getQuery ( 'term' );
		
		/*
		 * $criteria = new CDbCriteria ();
		 * if ($user->role_id != 1) {
		 * $criteria->addCondition ( 'create_user_id =' . $user->id );
		 * }
		 *
		 * $criteria->condition = "hsn_code LIKE :hsn_code";
		 * $criteria->params = array (
		 * ':hsn_code' => trim ( $term ) . '%'
		 * );
		 * $criteria->limit = '100';
		 *
		 * $criteria->addCondition ( 'status =' . Item::STATUS_ACTIVE );
		 * $items = Item::model ()->findAll ( $criteria );
		 *
		 * if ($items != null) {
		 * foreach ( $items as $item ) {
		 * $lists [] = array (
		 * 'item_id' => $item->id,
		 * 'name' => $item->hsn_code
		 * )
		 * ;
		 * }
		 * }
		 */
		$criteria = new CDbCriteria ();
		$role = UserRole::model ()->findByAttributes ( array (
				'title' => 'Vendor' 
		) );
		$user = Yii::app ()->user->model;
		if ($user->role_id == $role->id) {
			$itemvendor_ids = array ();
			$vendor = Vendor::model ()->findByAttributes ( array (
					'create_user_id' => $user->id 
			) );
			if ($vendor) {
				$itemvendors = ItemVendor::model ()->findAllByAttributes ( array (
						'vendor_id' => $vendor->id 
				) );
				if ($itemvendors) {
					
					foreach ( $itemvendors as $itemvendor ) {
						$itemvendor_ids [] = $itemvendor->item_detail_id;
					}
				}
			}
			$criteria->addInCondition ( 'id', $itemvendor_ids );
		}
		if ($term != '') {
			$criteria->condition = "title LIKE :hsn_code";
			$criteria->params = array (
					':hsn_code' => trim ( $term ) . '%' 
			);
		}
		if ($vendor_id != null) {
			$itemvendor_ids = array ();
			$vendor = Vendor::model ()->findByAttributes ( array (
					'id' => $vendor_id 
			) );
			if ($vendor) {
				$itemvendors = ItemVendor::model ()->findAllByAttributes ( array (
						'vendor_id' => $vendor->id 
				) );
				if ($itemvendors) {
					
					foreach ( $itemvendors as $itemvendor ) {
						$itemvendor_ids [] = $itemvendor->item_detail_id;
					}
				}
			}
			$criteria->addInCondition ( 'id', $itemvendor_ids );
		}
		$criteria->addCondition ( 'status =' . Item::STATUS_ACTIVE );
		
		$criteria->limit = '50';
		$items = Item::model ()->findAll ( $criteria );
		
	//	 print_r($itemvendor_ids);exit;
		if ($items) {
			foreach ( $items as $item ) {
				$lists [] = array (
						'item_id' => $item->id,
						'name' => $item->title,
						'mrp' => $item->mrp 
				);
			}
		}
		echo json_encode ( $lists );
	}
	public function actionHsnCodeList() {
		$user = Yii::app ()->user->model;
		$vendor_arr = array ();
		
		$exist = [ ];
		$lists = [ ];
		$term = Yii::app ()->request->getQuery ( 'term' );
		
		$criteria = new CDbCriteria ();
		if ($user->role_id != 1) {
			$criteria->addCondition ( 'create_user_id =' . $user->id );
		}
		
		$criteria->condition = "hsn_code LIKE :hsn_code";
		$criteria->params = array (
				':hsn_code' => trim ( $term ) . '%' 
		);
		$criteria->limit = '100';
		
		$criteria->addCondition ( 'status =' . Item::STATUS_ACTIVE );
		$items = Item::model ()->findAll ( $criteria );
		
		if ($items != null) {
			foreach ( $items as $item ) {
				$lists [] = array (
						'item_id' => $item->id,
						'name' => $item->hsn_code 
				);
			}
		}
		
		echo json_encode ( $lists );
	}
	public function actionVendorList() {
		$user = Yii::app ()->user->model;
		$vendor_arr = array ();
		$id = $_GET ['id'];
		$exist = [ ];
		$lists = [ ];
		$term = Yii::app ()->request->getQuery ( 'term' );
		if ($id != null) {
			$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'item_detail_id =' . $id );
			$itemvendors = ItemVendor::model ()->findAll ( $criteria1 );
			if ($itemvendors) {
				foreach ( $itemvendors as $itemvendor ) {
					$exist [] = $itemvendor->vendor_id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		if ($user->role_id == 1) {
			$criteria->addNotInCondition ( 'id', $exist );
		} else {
			$criteria->addCondition ( 'create_user_id =' . $user->id );
		}
		$criteria->condition = "name LIKE :name";
		$criteria->params = array (
				':name' => trim ( $term ) . '%' 
		);
		
		$criteria->limit = '100';
		
		$criteria->addCondition ( 'status =' . Vendor::STATUS_ACTIVE );
		$vendors = Vendor::model ()->findAll ( $criteria );
		if ($vendors != null) {
			foreach ( $vendors as $vendor ) {
				$lists [] = array (
						'user_id' => $vendor->id,
						'name' => $vendor->name 
				);
			}
		}
		
		echo json_encode ( $lists );
	}
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionstockImport() {
		ini_set ( 'max_execution_time', 10000 );
		$model = new Item ();
		if (isset ( $_FILES ['Item'] )) {
			
			$csvfile = $_FILES ['Item'] ['tmp_name'] ['csv_file'];
			
			$handle = fopen ( $csvfile, 'r' );
			if (! $handle)
				die ( 'Cannot open uploaded file.' );
			$row_count = 0;
			$rows = array ();
			$valued_rows = array ();
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
			
			$item = new Item ();
			$result = $item->setAllStockValues ( $valued_rows );
			if ($result == 1) {
				Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
			} else {
				Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
			}
		}
		$this->render ( 'stockImport', array (
				'model' => $model 
		) );
	}
	public function actionImport2() {
		$model = new Item ();
		if (isset ( $_FILES ['Item'] )) {
			
			$csvfile = $_FILES ['Item'] ['tmp_name'] ['csv_file'];
			
			$handle = fopen ( $csvfile, 'r' );
			if (! $handle)
				die ( 'Cannot open uploaded file.' );
			$row_count = 0;
			$rows = array ();
			$valued_rows = array ();
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
			
			$item = new Item ();
			$result = $item->setAllVendorValues ( $valued_rows );
			if ($result == 1) {
				Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
			} else {
				Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
			}
		}
		$this->render ( 'import2', array (
				'model' => $model 
		) );
	}
	public function actionImport1() {
		$model = new Item ();
		if (isset ( $_FILES ['Item'] )) {
			
			$csvfile = $_FILES ['Item'] ['tmp_name'] ['csv_file'];
			
			$handle = fopen ( $csvfile, 'r' );
			if (! $handle)
				die ( 'Cannot open uploaded file.' );
			$row_count = 0;
			$rows = array ();
			$valued_rows = array ();
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
			
			$item = new Item ();
			$result = $item->setAllNewValues ( $valued_rows );
			if ($result == 1) {
				Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
			} else {
				Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
			}
		}
		$this->render ( 'import1', array (
				'model' => $model 
		) );
	}
	public function actionImport() {
		ini_set ( 'max_execution_time', 30000 );
		$model = new Item ();
		if (isset ( $_FILES ['Item'] )) {
			
			$csvfile = $_FILES ['Item'] ['tmp_name'] ['csv_file'];
			
			$handle = fopen ( $csvfile, 'r' );
			if (! $handle)
				die ( 'Cannot open uploaded file.' );
			$row_count = 0;
			$rows = array ();
			$valued_rows = array ();
			// Read the file as csv
			while ( ($data = fgetcsv ( $handle, 1000, "," )) !== FALSE ) {
				$row_count ++;
				$data = array_map ( "utf8_encode", $data ); // added
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
			
			$item = new Item ();
			$result = $item->setAllValues ( $valued_rows );
			if ($result == 1) {
				Yii::app ()->user->setFlash ( 'success', 'File is successfully uploaded' );
			} else {
				Yii::app ()->user->setFlash ( 'danger', 'File getting problem! please upload again' );
			}
		}
		$this->render ( 'import', array (
				'model' => $model 
		) );
	}
	public function actionView($id) {
		$model = $this->loadModel ( $id, 'Item' );
		if (! ($model->checkPermission ( 'item/view' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
			
		// $this->processActions($model);
		$this->updateMenuItems ( $model );
		$this->render ( 'view', array (
				'model' => $model 
		) );
	}
	public function actionBarCode() {
		$model = new ItemDetail ( 'search' );
		
		$model->unsetAttributes ();
		// $this->updateMenuItems ( $model );
		Yii::app ()->session ['idList'] = '';
		Yii::app ()->session ['date_list'] = '';
		Yii::app ()->session ['packing_date_list'] = '';
		Yii::app ()->session ['expiry_val'] = '';
		if (isset ( $_GET ['ItemDetail'] ))
			$model->setAttributes ( $_GET ['ItemDetail'] );
		if (isset ( $_POST ['ItemDetail'] ['company_id'] )) {
			$model->company_id = $_POST ['ItemDetail'] ['company_id'];
		}
		Yii::log ( CVarDumper::dumpAsString ( $_POST ), CLogger::LEVEL_WARNING, '$_POST' );
		if (isset ( $_GET ['id'] )) {
			$model->item_id = $_GET ['id'];
		}
		$this->render ( 'barcode', array (
				'model' => $model 
		) );
	}
	/*
	 * public function actionBarCode() {
	 * $model = new ItemDetail();
	 * $criteria = new CDbCriteria();
	 * $item_detail_ids = array();
	 * $model->item_id = Yii::app()->session['item_id'];
	 * if (isset ( $_POST ['ItemDetail']['item_id'] )) {
	 * $item_detail_ids = $_POST ['ItemDetail']['item_id'];
	 * $model->item_id = $_POST ['ItemDetail']['item_id'];
	 * Yii::app()->session['item_id'] = $_POST ['ItemDetail']['item_id'] ;
	 * }
	 * $criteria->addInCondition('id', Yii::app()->session['item_id']);
	 * $item_count = ItemDetail::model()->count($criteria);
	 *
	 * $dataProvider = new CActiveDataProvider('ItemDetail',array('criteria'=>$criteria ,'pagination' => array(
	 * 'pageSize' => 10,
	 * ),));
	 * $pages = new CPagination($item_count);
	 * $pages->setPageSize(Yii::app()->params['listPerPage']);
	 *
	 *
	 * $this->render('barcode',array('model' => $model,'dataProvider'=>$dataProvider,'totalItemCount'=>$item_count,
	 * 'pagination'=>array(
	 * 'pageSize'=>Yii::app()->session['pageSize'],
	 * )));
	 *
	 * }
	 */
	public function actionPrintBarcode() {
		$model = new ItemDetail ( 'search' );
		$criteria = new CDbCriteria ();
		$item_detail_ids = array ();
		if ((isset ( $_POST ['ItemDetail'] ['item_print_id'] )) && ($_POST ['ItemDetail'] ['item_print_id'] != '') && ((isset ( $_POST ['ItemDetail'] ['item_qty'] )) && ($_POST ['ItemDetail'] ['item_qty'] != ''))) {
			Yii::app ()->session ['item_print_id'] = $_POST ['ItemDetail'] ['item_print_id'];
			Yii::app ()->session ['item_qty'] = $_POST ['ItemDetail'] ['item_qty'];
		} else {
			Yii::app ()->session ['item_print_id'] = '';
			Yii::app ()->session ['item_qty'] = '';
		}
		if (isset ( Yii::app ()->session ['idList'] )) {
			$item_detail_ids = Yii::app ()->session ['idList'];
		}
		$criteria->addInCondition ( 'id', Yii::app ()->session ['idList'] );
		$item_count = ItemDetail::model ()->count ( $criteria );
		
		$dataProvider = new CActiveDataProvider ( 'ItemDetail', array (
				'criteria' => $criteria,
				'pagination' => array (
						'pageSize' => 10 
				) 
		) );
		$pages = new CPagination ( $item_count );
		$pages->setPageSize ( Yii::app ()->params ['listPerPage'] );
		
		$this->render ( 'print', array (
				'dataProvider' => $dataProvider,
				'totalItemCount' => $item_count,
				'model' => $model,
				'pagination' => array (
						'pageSize' => Yii::app ()->session ['pageSize'] 
				) 
		) );
	}
	public function actionAjaxUpdate() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'status =' . UserRole::STATUS_ACTIVE );
			$criteria->compare('item_id', PostId::get('item_id'));
			
			$itemdetails = ItemDetail::model ()->findAll ( $criteria );
			$option .= '<select class="form-control" name="MrsDetail[item_detail_id]"><option value="" id="ckbCheckAll">-Select-</option>';
			if ($itemdetails) {
				foreach ( $itemdetails as $itemdetail ) {
					
					$option .= '<option value="' . $itemdetail->id . '">' . $itemdetail->bar_code . '</option>';
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
	public function actionVendorCreate($id = null) {
		if ($id != null) {
			$model = $this->loadModel ( $id, 'Item' );
		} else {
			$model = new Item ();
		}
		if (! ($model->checkPermission ( 'item/create' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation ( $model, 'item-form' );
		
		if (isset ( $_POST ['Item'] )) {
			
			$model->setAttributes ( $_POST ['Item'] );
			$model->status = Item::STATUS_INACTIVE;
			
			if ($model->save ()) {
				$user = Yii::app ()->user->model;
				$role = UserRole::model ()->findByAttributes ( array (
						'title' => 'Vendor' 
				) );
				if ($user->role_id == $role->id) {
					$vendor = Vendor::model ()->findByAttributes ( array (
							'create_user_id' => $user->id 
					) );
					if ($vendor) {
						$itemvendor = new ItemVendor ();
						$itemvendor->item_detail_id = $model->id;
						$itemvendor->vendor_id = $vendor->id;
						$itemvendor->save ();
					}
				}
				$outlet_ids = $_POST ['Item'] ['outlet_id'];
				foreach ( $outlet_ids as $outlet_id ) {
					$itemdetail = new ItemDetail ();
					$itemdetail->bar_code = $_POST ['Item'] ['bar_code'];
					$itemdetail->item_id = $model->id;
					$itemdetail->outlet_id = $outlet_id;
					$itemdetail->open_stock_qty = '0.00';
					$itemdetail->save ();
				}
				Yii::app ()->user->setFlash ( 'success', 'Item info is saved sucessfully' );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'vendorcreate', array (
				'model' => $model,
				'id' => $id 
		) );
	}
	public function actionCreate($id = null, $flash = false) {
		if ($id != null) {
			$model = $this->loadModel ( $id, 'Item' );
		} else {
			$model = new Item ();
			$model->min_qty = 10;
			$model->max_qty = 50;
			$model->reorder_qty = 10;
		}
		if (! ($model->checkPermission ( 'item/create' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
		
		$this->performAjaxValidation ( $model, 'item-form' );
		
		if (isset ( $_POST ['Item'] )) {
			
			$model->setAttributes ( $_POST ['Item'] );
			$role = UserRole::model ()->findByAttributes ( array (
					'title' => 'Vendor' 
			) );
			$user = Yii::app ()->user->model;
			if ($user->role_id == $role->id) {
				$model->status = Item::STATUS_INACTIVE;
			}
			
			if (isset ( $_POST ['Item'] ['short_name'] )) {
				
				$model->short_name = $_POST ['Item'] ['short_name'];
			}
			$model->update_time = date ( 'Y-m-d H:i:s' );
			if ($model->save ()) {
				$model->item_code = $model->id;
				$model->saveAttributes(array('item_code'));
			/* $criteria = new CDbCriteria();
			$criteria->addCondition('item_code ='.$model->item_code);
			$criteria->addCondition('id !='.$model->id);
			$existitem = Item::model()->find($criteria);
				if($existitem){
				$model->item_code = $model->item_code.$model->id;
				$model->saveAttributes(array('item_code'));
				} */
				if (isset ( $_POST ['Item'] ['status'] ) && ($_POST ['Item'] ['status'] != '')) {
					$itemdetailss = ItemDetail::model ()->findAllByAttributes ( array (
							'item_id' => $model->id 
					) );
					if ($itemdetailss) {
						foreach ( $itemdetailss as $itemdetails ) {
							$itemdetails->status = $_POST ['Item'] ['status'];
							$itemdetails->saveAttributes ( array (
									'status' 
							) );
						}
					}
				}
				$mainbarcode = $model->getItemBarcodes ();
				if ($mainbarcode) {
					$itemdetail = ItemDetail::model ()->findByAttributes ( array (
							'bar_code' => $mainbarcode 
					) );
					if ($itemdetail) {
						$itemdetail->update_time = date ( 'Y-m-d H:i:s' );
						$itemdetail->mrp = $model->mrp;
						$itemdetail->saveAttributes ( array (
								'mrp',
								'update_time' 
						) );
					}
				}
				if ($user->role_id == $role->id) {
					$vendor = Vendor::model ()->findByAttributes ( array (
							'create_user_id' => $user->id 
					) );
					if ($vendor) {
						$itemvendor = new ItemVendor ();
						$itemvendor->item_detail_id = $model->id;
						$itemvendor->vendor_id = $vendor->id;
						$itemvendor->save ();
					}
				}
				Yii::app ()->user->setFlash ( 'success', 'Item info is saved sucessfully' );
				$this->redirect ( array (
						'create',
						'id' => $model->id,
						'flash' => true 
				) );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'create', array (
				'model' => $model,
				'id' => $id,
				'flash' => $flash 
		) );
	}
	public function actionExtra($id = null) {
		if ($id != null) {
			$model = $this->loadModel ( $id, 'Item' );
			$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'status =' . ItemDetail::STATUS_ACTIVE );
			$criteria1->addCondition ( 'item_id =' . $id );
			$criteria1->order = 'id asc';
			$criteria1->limit = '1';
			$itemdetail = ItemDetail::model ()->find ( $criteria1 );
			
			if (! $itemdetail) {
				$itemdetail = new ItemDetail ();
			}
			$bar_code = $model->item_code;
		} else {
			$model = new Item ();
			$itemdetail = new ItemDetail ();
			$bar_code = User::randomBarcode ( '5' );
		}
		
		// print_r($itemdetail);exit;
		
		// if( !($model->checkPermission ('item/extra'))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'item-extra-form' );
		
		if (isset ( $_POST ['Item'] )) {
			if ($id != null) {
				$model->setAttributes ( $_POST ['Item'] );
				$model->update_time = date ( 'Y-m-d H:i:s' );
				if ($model->save ()) {
					if (isset ( $_POST ['ItemDetail'] ['outlet_id'] )) {
						$outlet_ids = $_POST ['ItemDetail'] ['outlet_id'];
						foreach ( $outlet_ids as $outlet_id ) {
							/*
							 * $itemdetail = ItemDetail::model()->findByAttributes(array('item_id'=>$model->id,
							 * 'outlet_id'=>$outlet_id,'bar_code'=>$_POST ['ItemDetail']['bar_code']
							 * ));
							 * if($itemdetail == null){
							 * $itemdetail = new ItemDetail ();
							 * }
							 */
							$itemdetail->setAttributes ( $_POST ['ItemDetail'] );
							$itemdetail->mrp = $model->mrp;
							$itemdetail->item_id = $model->id;
							$itemdetail->open_stock_qty = $model->opening_stock;
							$itemdetail->outlet_id = $outlet_id;
							$barcodestr = $itemdetail->bar_code;
							$itemdetail->bar_code = $barcodestr;
							$itemdetail->update_time = date ( 'Y-m-d H:i:s' );
							if (isset ( $_POST ['ItemDetail'] ['company_bar_code'] )) {
								if ($_POST ['ItemDetail'] ['company_bar_code'] == ItemDetail::IS_NOT_COMPANY) {
									$itemdetail->company_bar_code = ItemDetail::IS_COMPANY;
								} else {
									$itemdetail->company_bar_code = ItemDetail::IS_NOT_COMPANY;
								}
							} else {
								$itemdetail->company_bar_code = ItemDetail::IS_NOT_COMPANY;
							}
							// $itemdetail->bar_code = str_pad($barcodestr,13,"0",STR_PAD_LEFT);
							if ($itemdetail->save ()) {
								$batch_no = User::randomBarcode ( '5' );
								
								 $itemstock = ItemStock::model ()->findByAttributes ( array (
										'outlet_id' => $itemdetail->outlet_id,
										'item_detail_id' => $itemdetail->id,
										'item_id' => $model->id 
								) );
								if ($itemstock == null) {
									$itemstock = new ItemStock ();
									
									$itemstock->balance_qty = 0;
									$itemstock->purchase_qty = 0;
									$itemstock->outlet_id = $itemdetail->outlet_id;
									$itemstock->vendor_id = 0;
									$itemstock->mrp = $model->mrp;
									$itemstock->base_price = $model->sale_price;
									$itemstock->batch_number = $batch_no;
									$itemstock->item_id = $model->id;
									$itemstock->item_detail_id = $itemdetail->id;
									if ($itemstock->save ()) {
										$stocklog = new StockLog ();
										$stocklog->item_detail_id = $itemdetail->id;
										$stocklog->item_id = $model->id;
										$stocklog->batch_no = $batch_no;
										if ($itemdetail) {
											$stocklog->current_qty = $itemdetail->getStockQty ();
											$stocklog->previous_qty = bcadd ( $itemdetail->getStockQty (), $itemstock->purchase_qty, 3 );
										}
										$stocklog->Qty = $itemstock->purchase_qty;
										$stocklog->outlet_id = $itemdetail->outlet_id;
										$stocklog->vendor_id = 0;
										$stocklog->type_id = StockLog::TYPE_ADDED;
										if ($stocklog->save ()) {
										} else {
											print_r ( $stocklog->getErrors () );
											exit ();
										}
									} else {
										print_r ( $itemstock->getErrors () );
										exit ();
									}
								} 
								if (isset ( $_POST ['ItemDetail'] ['tax_id'] )) {
									$itemtax = ItemTax::model ()->findByAttributes ( array (
											'item_detail_id' => $itemdetail->id 
									) );
									if ($itemtax == null) {
										$itemtax = new ItemTax ();
									}
									$itemtax->item_detail_id = $itemdetail->id;
									$itemtax->tax_id = $_POST ['ItemDetail'] ['tax_id'];
									$itemtax->save ();
								}
								
								$bar_code = $itemdetail->bar_code;
							} else {
								print_r ( $itemdetail->getErrors () );
								exit ();
							}
						}
						
						Yii::app ()->user->setFlash ( 'success', 'Extra info is saved sucessfully' );
					} else {
						Yii::app ()->user->setFlash ( 'error', 'Pleas select Outlet' );
					}
				}
			} else {
				Yii::app ()->user->setFlash ( 'error', 'Please add product info first' );
			}
		}
		$this->updateMenuItems ( $model );
		$this->render ( 'extra', array (
				'model' => $model,
				'itemdetail' => $itemdetail,
				'id' => $id,
				'bar_code' => $bar_code 
		) );
	}
	public function actionVendor($id = null) {
		if ($id != null) {
			$model = $this->loadModel ( $id, 'Item' );
		} else {
			$model = new Item ();
		}
		
		$itemvendor = new ItemVendor ();
		
		$itemvendor->scenario = 'create';
		// if( !($model->checkPermission ('item/extra'))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		// $this->performAjaxValidation ( $model, 'item-vendor-form' );
		if (isset ( $_POST ['ItemVendor'] )) {
			if ($id != null) {
				$itemvendor = ItemVendor::model ()->findByAttributes ( array (
						'item_detail_id' => $id,
						'vendor_id' => $_POST ['ItemVendor'] ['vendor_id'] 
				) );
				if (! $itemvendor) {
					$itemvendor = new ItemVendor ();
				}
				
				// Item::RemoveVendors($model->id);
				$itemvendor->setAttributes ( $_POST ['ItemVendor'] );
				
				$itemvendor->item_detail_id = $id;
				if ($itemvendor->save ()) {
					
					Yii::app ()->user->setFlash ( 'success', 'Extra info is saved sucessfully' );
				}
			} else {
				Yii::app ()->user->setFlash ( 'error', 'Please add product info first' );
			}
		}
		
		$this->updateMenuItems ( $model );
		$this->render ( 'vendor', array (
				'model' => $model,
				'id' => $id,
				'vendor' => $itemvendor 
		) );
	}
	public function actionUpdate($id) {
		$model = $this->loadModel ( $id, 'Item' );
		if (! ($model->checkPermission ( 'item/update' )))
			throw new CHttpException ( 403, Yii::t ( 'app', 'You are not allowed to access this page.' ) );
			
			// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		$this->performAjaxValidation ( $model, 'item-form' );
		
		if (isset ( $_POST ['Item'] )) {
			
			$model->setAttributes ( $_POST ['Item'] );
			$model->update_time = date ( 'Y-m-d H:i:s' );
			if ($model->save ()) {
				if (isset ( $_POST ['Item'] ['vendor_id'] )) {
					Item::RemoveVendors ( $model->id );
					$vendor_ids = $_POST ['Item'] ['vendor_id'];
					foreach ( $vendor_ids as $vendor_id ) {
						$itemvendor = new ItemVendor ();
						$itemvendor->vendor_id = $vendor_id;
						$itemvendor->item_detail_id = $model->id;
						$itemvendor->save ();
					}
				}
				$this->redirect ( array (
						'view',
						'id' => $model->id 
				) );
			}
		}
		$model->vendor_id = $model->getSelectedVendors ();
		$this->updateMenuItems ( $model );
		$this->render ( 'update', array (
				'model' => $model 
		) );
	}
	public function actionDelete($id) {
		$model = $this->loadModel ( $id, 'Item' );
		
		// if( !($this->isAllowed ( $model))) throw new CHttpException(403, Yii::t('app','You are not allowed to access this page.'));
		
		if (Yii::app ()->getRequest ()->getIsPostRequest ()) {
			$this->loadModel ( $id, 'Item' )->delete ();
			
			if (! Yii::app ()->getRequest ()->getIsAjaxRequest ())
				$this->redirect ( array (
						'admin' 
				) );
		} else
			throw new CHttpException ( 400, Yii::t ( 'app', 'Your request is invalid.' ) );
	}
	public function actionIndex() {
		$model = new Item ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['Item'] ['name'] )) {
			$_GET ['Item'] ['name'] = $_POST ['Item'] ['name'];
			$model->name = $_POST ['Item'] ['name'];
			Yii::app ()->session ['item_name'] = $_POST ['Item'] ['name'];
		}
		
		if (isset ( $_GET ['Item'] )) {
			if (Yii::app ()->session ['item_name'] != '') {
				$model->name = Yii::app ()->session ['item_name'];
			}
			$model->setAttributes ( $_GET ['Item'] );
		}
		
		$this->render ( 'index', array (
				'model' => $model
		) );
		
	}
	public function actionSearch() {
		$model = new Job ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['Item'] )) {
			$model->setAttributes ( $_GET ['Item'] );
			$this->renderPartial ( '_list', array (
					'dataProvider' => $model->search (),
					'model' => $model 
			) );
		}
		
		$this->renderPartial ( '_search', array (
				'model' => $model 
		) );
	}
	public function actionReport() {
		$model = new Item ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_GET ['Item'] ['title'] ) ) {
			$_GET ['Item'] ['name'] = $_GET ['Item'] ['title'];
			$model->name = $_GET ['Item'] ['title'];
			Yii::app ()->session ['item_name'] = $_GET ['Item'] ['title'];
		}
		if (isset ( $_POST ['Item'] ['start_date'] ) && ($_POST ['Item'] ['start_date'] != '')) {
			Yii::app ()->session ['stock_start_date'] = $_POST ['Item'] ['start_date'];
			$_GET ['Item'] ['start_date'] = $_POST ['Item'] ['start_date'];
		} else {
			if(isset(Yii::app ()->session ['stock_start_date']) && (Yii::app ()->session ['stock_start_date'] == '')){
			Yii::app ()->session ['stock_start_date'] = date ( 'Y-m-d' );
			$_GET ['Item'] ['start_date'] = date ( 'Y-m-d' );
			}else{
			Yii::app ()->session ['stock_start_date'] = Yii::app ()->session ['stock_start_date'];
			$_GET ['Item'] ['start_date'] = Yii::app ()->session ['stock_start_date'];
			}
		}
		if (isset ( $_POST ['Item'] ['end_date'] ) && ($_POST ['Item'] ['end_date'] != '')) {
			Yii::app ()->session ['stock_end_date'] = $_POST ['Item'] ['end_date'];
			$_GET ['Item'] ['end_date'] = $_POST ['Item'] ['end_date'];
		} else {
			if(isset(Yii::app ()->session ['stock_end_date']) && (Yii::app ()->session ['stock_end_date'] == '')){
			Yii::app ()->session ['stock_end_date'] = date ( 'Y-m-d' );
			$_GET ['Item'] ['end_date'] = date ( 'Y-m-d' );
			}else{
			Yii::app ()->session ['stock_end_date'] = Yii::app ()->session ['stock_end_date'];
			$_GET ['Item'] ['end_date'] = Yii::app ()->session ['stock_end_date'];
			}
			
			
		}
		if (isset ( $_GET ['Item'] )) {
			if (Yii::app ()->session ['item_name'] != '') {
				$model->name = Yii::app ()->session ['item_name'];
			}
			$model->setAttributes ( $_GET ['Item'] );
		}
		if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
		
		ini_set('memory_limit', '-1');

        ini_set('max_execution_time', '0');
		
		
			$this->exportCSV ( $model->reportstocksearch (), array (
					
					'item_code',
					array (
							'label' => 'Bar Code',
							'value' => function ($data) {
								return $data->getItemBarcodes ();
							} 
					),
					'title',
					'mrp',
					'purchase_price',
					array (
							'label' => 'Opening',
							'value' => function ($data) {
								return $data->getOpeningQuantity ();
							} 
					),
					array (
							'label' => 'Adjustment',
							'value' => function ($data) {
								return $data->getAdjustmentQuantity ();
							} 
					),
					array (
							'label' => 'Purchase/Added',
							'value' => function ($data) {
								return $data->getAddedQuantity ();
							} 
					),
					array (
							'label' => 'Purchase Return',
							'value' => function ($data) {
								return $data->getReturnQuantity ();
							} 
					),
					array (
							'label' => 'Sale/Order',
							'value' => function ($data) {
								return $data->getSoldQuantity ();
							} 
					),
					array (
							'label' => 'Sale/Order Refund',
							'value' => function ($data) {
								return $data->getRefundQuantity ();
							} 
					),
					array (
							'label' => 'Expiry',
							'value' => function ($data) {
								return $data->getExpiryQuantity ();
							} 
					),
					array (
							'label' => 'Total Remain Qty',
							'value' => function ($data) {
								return $data->getStockRemainingQuantity ();
							} 
					) 
			)
			 )
			// 'sale_price',
			
			// 'weight',
			// 'whole_sale',
			// 'bar_code',
			// 'tax_id',
			// 'opening_stock',
			
			;
		}
		$this->render ( 'report', array (
				'model' => $model 
		) );
	}
	public function actionAdmin() {
		$user = Yii::app()->user->model;
		$role_id = $user->role_id;
		if($role_id == 6){
		
			$this->redirect ( array (
					'index'
		
			) );
		}
		$model = new Item ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['Item'] ['name'] )) {
			$_GET ['Item'] ['name'] = $_POST ['Item'] ['name'];
			$model->name = $_POST ['Item'] ['name'];
			Yii::app ()->session ['item_name'] = $_POST ['Item'] ['name'];
		}
		
		if (isset ( $_GET ['Item'] )) {
			if (Yii::app ()->session ['item_name'] != '') {
				$model->name = Yii::app ()->session ['item_name'];
			}
			$model->setAttributes ( $_GET ['Item'] );
		}
		if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV ( $model->search (), array (
					
					'title',
					'short_name',
					'hsn_code',
					'item_code',
					/* array (
							'label' => 'Item Type',
							'value' => function ($data) {
								return Item::getTypeOptions ( $data->item_type );
							} 
					), */
					array (
							'label' => 'Barcode',
							'value' => function ($data) {
								return Item::getItemMainBarcodes ( $data->id );
							} 
					),
					array (
							'label' => 'Tax',
							'value' => function ($data) {
								return Item::getStaticMainItemTax ( $data->id );
							} 
					),
					'mrp',
					'purchase_price',
					array (
							'label' => 'Total Remain Qty',
							'value' => function ($data) {
								return Item::getMainTotalRemainingQuantity ( $data->id );
							} 
					),
					
					array (
							'label' => 'Item Category',
							'value' => function ($data) {
								return Item::getItemCategory ( $data->category_id );
							} 
					),
					array (
							'label' => 'Sub Category',
							'value' => function ($data) {
								return Item::getItemCategory ( $data->sub_category_id );
							} 
					),
					array (
							'label' => 'Item Company',
							'value' => function ($data) {
								return Item::getItemCompany ( $data->company_id );
							} 
					),
					
					array (
							'label' => 'Item Company Category',
							'value' => function ($data) {
								return Item::getItemCompanyCategory ( $data->sub_company_id );
							} 
					),
					array (
							'label' => 'Vendor',
							'value' => function ($data) {
								return Item::getMainLatestVendorName ( $data->id );
							} 
					),
					'min_qty',
					'max_qty',
					'reorder_qty',
					array (
							'label' => 'Status',
							'value' => function ($data) {
								return Item::getStatusOptions ( $data->status );
							} 
					) 
			) )
			// 'sale_price',
			
			// 'weight',
			// 'whole_sale',
			// 'bar_code',
			// 'tax_id',
			// 'opening_stock',
			
			;
		}
		$this->render ( 'admin', array (
				'model' => $model 
		) );
	}

	public function actionExport() {
		$user = Yii::app()->user->model;
		$role_id = $user->role_id;
		if($role_id == 6){
		
			$this->redirect ( array (
					'index'
		
			) );
		}
		$model = new Item ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['Item'] ['name'] )) {
			$_GET ['Item'] ['name'] = $_POST ['Item'] ['name'];
			$model->name = $_POST ['Item'] ['name'];
			Yii::app ()->session ['item_name'] = $_POST ['Item'] ['name'];
		}
		
		if (isset ( $_GET ['Item'] )) {
			if (Yii::app ()->session ['item_name'] != '') {
				$model->name = Yii::app ()->session ['item_name'];
			}
			$model->setAttributes ( $_GET ['Item'] );
		}
		if ($this->isExportRequest ()) {
			$this->exportCSV ( $model->search (), array (
					'id',
					'title',
					'short_name',
					'hsn_code',
					'item_code',
					/* array (
							'label' => 'Item Type',
							'value' => function ($data) {
								return Item::getTypeOptions ( $data->item_type );
							} 
					), */
					array (
							'label' => 'Barcode',
							'value' => function ($data) {
								return Item::getItemMainBarcodes ( $data->id );
							} 
					),
					array (
							'label' => 'Tax',
							'value' => function ($data) {
								return Item::getStaticMainItemTax ( $data->id );
							} 
					),
					'mrp',
					'purchase_price',
					array (
							'label' => 'Margin',
							'value' => function ($data) {
								return Item::getItemMainMargin ( $data->id );
							} 
					),
					array (
							'label' => 'Total Remain Qty',
							'value' => function ($data) {
								return Item::getMainTotalRemainingQuantity ( $data->id );
							} 
					),
					
					array (
							'label' => 'Item Category',
							'value' => function ($data) {
								return Item::getItemCategory ( $data->category_id );
							} 
					),
					array (
							'label' => 'Sub Category',
							'value' => function ($data) {
								return Item::getItemCategory ( $data->sub_category_id );
							} 
					),
					array (
							'label' => 'Item Company',
							'value' => function ($data) {
								return Item::getItemCompany ( $data->company_id );
							} 
					),
					
					array (
							'label' => 'Item Company Category',
							'value' => function ($data) {
								return Item::getItemCompanyCategory ( $data->sub_company_id );
							} 
					),
					array (
							'label' => 'Vendor',
							'value' => function ($data) {
								return Item::getMainLatestVendorName ( $data->id );
							} 
					),
					'min_qty',
					'max_qty',
					'reorder_qty',
					array (
							'label' => 'Status',
							'value' => function ($data) {
								return Item::getStatusOptions ( $data->status );
							} 
					) 
			) )
			// 'sale_price',
			
			// 'weight',
			// 'whole_sale',
			// 'bar_code',
			// 'tax_id',
			// 'opening_stock',
			
			;
		} else {
			echo 'No data found to export';
			die;
		}

		$this->render ( 'admin', array (
				'model' => $model 
		) );
		
	}

	public function actionExportnew() {
		$user = Yii::app()->user->model;
		$role_id = $user->role_id;
		if($role_id == 6){
		
			$this->redirect ( array (
					'index'
		
			) );
		}
		$model = new Item ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['Item'] ['name'] )) {
			$_GET ['Item'] ['name'] = $_POST ['Item'] ['name'];
			$model->name = $_POST ['Item'] ['name'];
			Yii::app ()->session ['item_name'] = $_POST ['Item'] ['name'];
		}
		
		if (isset ( $_GET ['Item'] )) {
			if (Yii::app ()->session ['item_name'] != '') {
				$model->name = Yii::app ()->session ['item_name'];
			}
			$model->setAttributes ( $_GET ['Item'] );
		}
		if ($this->isExportRequest ()) {
			$this->exportCSV ( $model->search (), array (
					'id',
					'title',
					'short_name',
					'hsn_code',
					'item_code',
					/* array (
							'label' => 'Item Type',
							'value' => function ($data) {
								return Item::getTypeOptions ( $data->item_type );
							} 
					), */
					array (
							'label' => 'Barcode',
							'value' => function ($data) {
								return Item::getItemMainBarcodes ( $data->id );
							} 
					),
					array (
							'label' => 'Tax',
							'value' => function ($data) {
								return Item::getStaticMainItemTax ( $data->id );
							} 
					),
					'mrp',
					'purchase_price',
					array (
							'label' => 'Margin',
							'value' => function ($data) {
								return Item::getItemMainMargin ( $data->id );
							} 
					),
					array (
							'label' => 'GST 0 MRP',
							'value' => function ($data) {
								return Item::getItemMainGSTNewMRP ( $data->id, 0 );
							} 
					),
					array (
							'label' => 'GST 5 MRP',
							'value' => function ($data) {
								return Item::getItemMainGSTNewMRP ( $data->id, 5 );
							} 
					),
					array (
							'label' => 'GST 18 MRP',
							'value' => function ($data) {
								return Item::getItemMainGSTNewMRP ( $data->id, 18 );
							} 
					),
					array (
							'label' => 'GST 40 MRP',
							'value' => function ($data) {
								return Item::getItemMainGSTNewMRP ( $data->id, 40 );
							} 
					),
					array (
							'label' => 'New GST Tax',
							'value' => function ($data) {
								// return Item::getItemMainGSTNewActualTax ( $data->id, $data->hsn_code );
								return $data->new_gst;
							} 
					),
					array (
							'label' => 'New MRP / Sale Rate',
							'value' => function ($data) {
								return Item::getItemMainGSTNewActualMRP ( $data->id, $data->new_gst );
							} 
					),
					array (
							'label' => 'Total Remain Qty',
							'value' => function ($data) {
								return Item::getMainTotalRemainingQuantity ( $data->id );
							} 
					),
					
					array (
							'label' => 'Item Category',
							'value' => function ($data) {
								return Item::getItemCategory ( $data->category_id );
							} 
					),
					array (
							'label' => 'Sub Category',
							'value' => function ($data) {
								return Item::getItemCategory ( $data->sub_category_id );
							} 
					),
					array (
							'label' => 'Item Company',
							'value' => function ($data) {
								return Item::getItemCompany ( $data->company_id );
							} 
					),
					
					array (
							'label' => 'Item Company Category',
							'value' => function ($data) {
								return Item::getItemCompanyCategory ( $data->sub_company_id );
							} 
					),
					array (
							'label' => 'Vendor',
							'value' => function ($data) {
								return Item::getMainLatestVendorName ( $data->id );
							} 
					),
					'min_qty',
					'max_qty',
					'reorder_qty',
					array (
							'label' => 'Status',
							'value' => function ($data) {
								return Item::getStatusOptions ( $data->status );
							} 
					) 
			) )
			// 'sale_price',
			
			// 'weight',
			// 'whole_sale',
			// 'bar_code',
			// 'tax_id',
			// 'opening_stock',
			
			;
		} else {
			echo 'No data found to export';
			die;
		}

		$this->render ( 'admin', array (
				'model' => $model 
		) );
		
	}
	public function actionAdjustStock() {
		$model = new Item ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		if (isset ( $_POST ['Item'] ['name'] )) {
			$_GET ['Item'] ['name'] = $_POST ['Item'] ['name'];
			$model->name = $_POST ['Item'] ['name'];
			Yii::app ()->session ['item_name'] = $_POST ['Item'] ['name'];
		}
		
		if(isset ( $_GET  ['Item']['remaining_quan'] )){
			
		Yii::app ()->session ['remaining_quan'] = $_GET ['Item'] ['remaining_quan'];
		
		}		
		if (Yii::app ()->session ['remaining_quan'] != '') {
			
			$model->remaining_quan = Yii::app ()->session ['remaining_quan'];
		
		}
		
		if (isset ( $_GET ['Item'] )) {
			if (Yii::app ()->session ['item_name'] != '') {
				$model->name = Yii::app ()->session ['item_name'];
			}
		}
		if (isset ( $_GET ['Item'] ['company_id'] )) {
			$model->company_id = $_GET ['Item'] ['company_id'];
			Yii::app ()->session ['company_id'] = $_GET ['Item'] ['company_id'];
		}
		
		if (Yii::app ()->session ['company_id'] != '') {
			$model->company_id = Yii::app ()->session ['company_id'];
		}
		
		if (isset ( $_GET ['Item'] ))
			$model->setAttributes ( $_GET ['Item'] );
		
		$this->render ( 'adjuststock', array (
				'model' => $model 
		) );
	}
	public function actionCheckOutlets() {
		$option = '';
		
		if (isset ( $_POST ['vendor_id'] )) {
			$outlet_ids = array ();
			$vendor = Vendor::model ()->findByPk ( $_POST ['vendor_id'] );
			if ($vendor) {
				$outlet_ids = explode ( ',', $vendor->outlet_id );
			}
			
			$criteria = new CDbCriteria ();
			$criteria->addInCondition ( 'id ', $outlet_ids );
			$criteria->addCondition ( 'status =' . Outlet::STATUS_ACTIVE );
			$outletlist = Outlet::model ()->findAll ( $criteria );
			
			$option .= '<select class="form-control"  id="item_outlet_id" name="Item[outlet_id]"><option value="" id="ckbCheckAll">-Select-</option>';
			if ($outletlist) {
				foreach ( $outletlist as $outlet ) {
					
					$option .= '<option value="' . $outlet->id . '">' . $outlet->title . '</option>';
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
	public function actionAjaxExpireItems() {
		$option = '';
		$alreadypermissions = array ();
		if (isset ( $_POST ['item_id'] )) {
			
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'status =' . UserRole::STATUS_ACTIVE );
			$criteria->compare('item_id', PostId::get('item_id'));
			
			$itemdetails = ItemDetail::model ()->findAll ( $criteria );
			$option .= '<select class="form-control" id="ItemExpire_item_detaill_id" onChange="checkTaxes()"  name="ItemExpireItem[item_detail_id]"><option value="" id="ckbCheckAll">-Select-</option>';
			if ($itemdetails) {
				foreach ( $itemdetails as $itemdetail ) {
					$stock = $itemdetail->checkStock ();
					if ($stock > 0) {
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
	public function actionSaveExpire() {
		if (isset ( $_POST ['vendor'] ) && (isset ( $_POST ['outlet'] ))) {
			$itemExpire = ItemExpire::model ()->findByAttributes ( array (
					'vendor_id' => $_POST ['vendor'],
					'outlet_id' => $_POST ['outlet'],
					'status' => ItemExpire::STATUS_PENDING 
			) );
			if ($itemExpire) {
				$set = true;
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'item_expire_id =' . $itemExpire->id );
				$criteria->addCondition ( 'status =' . ItemExpire::STATUS_PENDING );
				$eitems = ItemExpireItem::model ()->findAll ( $criteria );
				if ($eitems) {
					foreach ( $eitems as $eitem ) {
						$eitem->status = ItemExpire::STATUS_DONE;
						if ($eitem->save ()) {
							$itemDetail = ItemDetail::model ()->findByPk ( $eitem->item_detail_id );
							if ($itemDetail) {
								$itemStock = ItemStock::model ()->findByAttributes ( array (
										'item_detail_id' => $itemDetail->id,
										'outlet_id' => $eitem->outlet_id 
								) );
								$item = Item::model ()->findByPk ( $itemDetail->item_id );
								$current = $itemStock->balance_qty;
								
								if ($itemStock != null) {
									
									if ($itemStock->saveExceptQty() && $itemStock->addToBalance(-($eitem->qty))) {
										$log = new StockLog ();
										
										$log->item_detail_id = $itemDetail->id;
										$log->item_id = $item->id;
										$log->batch_no = $itemStock->batch_number;
										$log->current_qty = $itemDetail->getStockQty ();
										$log->previous_qty = ($itemDetail->getStockQty ()) + ($eitem->qty);
										$log->Qty = $eitem->qty;
										$log->outlet_id = $eitem->outlet_id;
										$log->vendor_id = $eitem->vendor_id;
										$log->type_id = StockLog::TYPE_EXPIRED;
										
										if ($log->save ()) {
										} else {
											print_r ( $log->getErrors () );
											exit ();
										}
									}
								}
							}
						}
					}
					$itemExpire->status = ItemExpire::STATUS_DONE;
					$itemExpire->saveAttributes ( array (
							'status' 
					) );
				}
			}
		}
	}
	public function actionAjaxExpireValues() {
		$mrp = '0.00';
		$sale_rate = '0.00';
		$total_amt = '0.00';
		if (isset ( $_POST ['item_id'] ) && ($_POST ['item_detail_id'])) {
			$item_id = $_POST ['item_id'];
			$item_detail_id = $_POST ['item_detail_id'];
			$itemDetail = ItemDetail::model ()->findByPk ( $item_detail_id );
			$item = Item::model ()->findByPk ( $item_id );
			if (isset ( $_POST ['qty'] )) {
				$qty = $_POST ['qty'];
			} else {
				$qty = 1;
			}
			if ($item) {
				if ($itemDetail) {
					$mrp = $itemDetail->getItemDetailMrp ();
				} else {
					$mrp = $item->mrp;
				}
				$sale_rate = $item->purchase_price;
				$total_amt = $qty * ($item->purchase_price);
			}
		}
		$data ['mrp'] = $mrp;
		$data ['sale_rate'] = $sale_rate;
		$data ['total_amt'] = $total_amt;
		echo json_encode ( $data );
	}
	public function actionExpireStock($vendor_id = null, $outlet_id = null, $set = true) {
		$model = new ItemExpireItem ( 'search' );
		$model->unsetAttributes ();
		
		$this->updateMenuItems ( $model );
		
		if (isset ( $_POST ['ItemExpireItem'] ['vendor_id'] )) {
			$vendor_id = $_POST ['ItemExpireItem'] ['vendor_id'];
			$_GET ['ItemExpireItem'] ['vendor_id'] = $vendor_id;
		} else {
			if ($vendor_id != null) {
				$_GET ['ItemExpireItem'] ['vendor_id'] = $vendor_id;
			}
		}
		if (isset ( $_POST ['ItemExpireItem'] ['outlet_id'] )) {
			$outlet_id = $_POST ['ItemExpireItem'] ['outlet_id'];
			$_GET ['ItemExpireItem'] ['outlet_id'] = $outlet_id;
		} else {
			if ($outlet_id != null) {
				$_GET ['ItemExpireItem'] ['outlet_id'] = $outlet_id;
			}
		}
		$_GET ['ItemExpireItem'] ['status'] = ItemExpire::STATUS_PENDING;
		if (isset ( $_GET ['ItemExpireItem'] ))
			$model->setAttributes ( $_GET ['ItemExpireItem'] );
		
		$this->render ( 'expirestock', array (
				'model' => $model,
				'vendor_id' => $vendor_id,
				'outlet_id' => $outlet_id,
				'set' => $set 
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
			$model = new Item ();
		
		switch ($this->action->id) {
			case 'update' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'View' ),
							'url' => array (
									'view',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "item/view" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
				}
			
			case 'expireStock' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'List' ),
							'url' => array (
									'itemExpire/admin' 
							),
							
							'icon' => 'icon-wrench icon-white' 
					);
				}
				break;
			case 'create' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'visible' => $model->checkPermission ( "item/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
				}
				break;
			case 'vendorCreate' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'visible' => $model->checkPermission ( "item/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create Category' ),
							'url' => array (
									'itemCategory/create' 
							),
							'visible' => $model->checkPermission ( "itemCategory/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create Company' ),
							'url' => array (
									'itemCompany/create' 
							),
							'visible' => $model->checkPermission ( "itemCompany/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create Company Category' ),
							'url' => array (
									'itemCompanyCategory/create' 
							),
							'visible' => $model->checkPermission ( "itemCompanyCategory/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			case 'extra' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'visible' => $model->checkPermission ( "item/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
				}
				break;
			case 'vendor' :
				{
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'visible' => $model->checkPermission ( "item/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
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
							'visible' => $model->checkPermission ( "item/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'visible' => $model->checkPermission ( "item/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
				}
				break;
			case 'admin' :
				{
					$user = Yii::app ()->user->model;
					$role = UserRole::model ()->findByAttributes ( array (
							'title' => 'Vendor' 
					) );
					if ($user->role_id != $role->id) {
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Print Barcode' ),
								'url' => array (
										'item/barcode' 
								),
								// 'visible' => $model->checkPermission ( "itemCompanyCategory/create" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Manage Stock' ),
								'url' => array (
										'itemStock/create' 
								),
								'icon' => 'icon-th-list icon-white' 
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Create' ),
								'url' => array (
										'create' 
								),
								'visible' => $model->checkPermission ( "item/create" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Add Free Item' ),
								'url' => array (
										'freeItem/create' 
								),
								// 'visible' => $model->checkPermission ( "item/create" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Add SubItem' ),
								'url' => array (
										'itemDetail/add' 
								),
								'visible' => $model->checkPermission ( "itemDetail/create" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Import' ),
								'url' => array (
										'item/import' 
								),
								// 'visible' => $model->checkPermission ( "item/import" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Stock Import' ),
								'url' => array (
										'item/stockImport' 
								),
								// 'visible' => $model->checkPermission ( "item/import" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Vendor Import' ),
								'url' => array (
										'item/import2' 
								),
								// 'visible' => $model->checkPermission ( "item/import" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
					} else {
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Create' ),
								'url' => array (
										'vendorCreate' 
								),
								'visible' => $model->checkPermission ( "item/create" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
						$this->menu [] = array (
								'label' => Yii::t ( 'app', 'Add Free Item' ),
								'url' => array (
										'freeItem/create' 
								),
								// 'visible' => $model->checkPermission ( "item/create" ) == "true",
								'icon' => 'icon-plus icon-white' 
						);
					}
				}
				break;
			default :
			case 'view' :
				{
					// $this->menu[] = array('label'=>Yii::t('app', 'List'), 'url'=>array('index'),'icon'=>'icon-th-list icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Manage' ),
							'url' => array (
									'admin' 
							),
							'visible' => $model->checkPermission ( "item/admin" ) == "true",
							'icon' => 'icon-wrench icon-white' 
					);
					// $this->menu[] = array('label'=>Yii::t('app', 'Delete'), 'url'=>'#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id),
					// 'confirm'=>'Are you sure you want to delete this item?'),'icon'=>'icon-remove icon-white');
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Create' ),
							'url' => array (
									'create' 
							),
							'visible' => $model->checkPermission ( "item/create" ) == "true",
							'icon' => 'icon-plus icon-white' 
					);
					$this->menu [] = array (
							'label' => Yii::t ( 'app', 'Update' ),
							'url' => array (
									'update',
									'id' => $model->id 
							),
							'visible' => $model->checkPermission ( "item/update" ) == "true",
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

	public function actionScannedItems() {
		$model = new ScannedItems ( 'search' );
		$model->unsetAttributes ();
		$this->updateMenuItems ( $model );
		$columns = array ();
		if (isset ( $_POST ['ScannedItems'] ['columns'] )) {
			$columns = $_POST ['ScannedItems'] ['columns'];
		}

		if (isset ( $_POST ['ScannedItems'] ['start_date'] ) && ($_POST ['ScannedItems'] ['start_date'] != '')) {
			Yii::app ()->session ['scanned_start_date'] = $_POST ['ScannedItems'] ['start_date'];
			$model->start_date = $_GET ['ScannedItems'] ['start_date'] = $_POST ['ScannedItems'] ['start_date'];
		} else {
			if(!isset(Yii::app ()->session ['scanned_start_date']) || (Yii::app ()->session ['scanned_start_date'] == '')){
			Yii::app ()->session ['scanned_start_date'] = date('Y-m-d', strtotime('first day of this month'));
			$model->start_date = $_GET ['ScannedItems'] ['start_date'] = date('Y-m-d', strtotime('first day of this month'));
			}else{
				$model->start_date = Yii::app ()->session ['scanned_start_date'];
			$_GET ['ScannedItems'] ['start_date'] = Yii::app ()->session ['scanned_start_date'];
			}
		}
		if (isset ( $_POST ['ScannedItems'] ['end_date'] ) && ($_POST ['ScannedItems'] ['end_date'] != '')) {
			Yii::app ()->session ['scanned_end_date'] = $_POST ['ScannedItems'] ['end_date'];
			$model->end_date = $_GET ['ScannedItems'] ['end_date'] = $_POST ['ScannedItems'] ['end_date'];
		} else {
			if(!isset(Yii::app ()->session ['scanned_end_date']) || (Yii::app ()->session ['scanned_end_date'] == '')){
			$model->end_date = Yii::app ()->session ['scanned_end_date'] = date ( 'Y-m-d' );
			$_GET ['ScannedItems'] ['end_date'] = date ( 'Y-m-d' );
			}else{
				$model->end_date = Yii::app ()->session ['scanned_end_date'];
				$_GET ['ScannedItems'] ['end_date'] = Yii::app ()->session ['scanned_end_date'];
			}
		}

		if (isset ( $_GET ['ScannedItems'] )) {
			$model->setAttributes ( $_GET ['ScannedItems'] );
		}
		$columns = $model->getScannedItemsColumns ( $columns );
		// echo "<pre>"; print_r($columns); die;
		if ($this->isExportRequest ()) { // <==== [[ADD THIS BLOCK BEFORE RENDER]]
			$this->exportCSV ( $model->scannedItemsSearchExport(), $columns );
		}
		
		// $columns = $model->getUserwiseColumns ( $columns );
		// echo "<pre>"; print_r($model); die;
		$this->render ( 'scannedItems', array (
				'model' => $model 
		) );
	}

	// create an endpoint to get all item by given date if the quantity is less than equal to 0 in tbl_stock_log table

	public function actionGetitemsbydate()
	{
			$date = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');
			// SQL query to fetch the required data
			$sql = "
					SELECT 
							i.id AS item_id,
							i.title AS item_name,
							v.name AS vendor_name,
							sl.current_qty,
							i.min_qty,
							i.max_qty,
							sl.id AS stock_log_id
					FROM 
							tbl_item i
					INNER JOIN 
							(SELECT 
									item_id, 
									MAX(id) AS latest_log_id
							FROM 
									tbl_stock_log
							WHERE 
									DATE(create_time) = :date
							GROUP BY 
									item_id) AS latest
					ON 
							i.id = latest.item_id
					INNER JOIN 
							tbl_stock_log sl
					ON 
							sl.id = latest.latest_log_id
					LEFT JOIN 
							tbl_vendor v
					ON 
							sl.vendor_id = v.id
					WHERE 
							sl.current_qty < i.min_qty 
							AND i.id NOT IN (
									SELECT 
											item_id 
									FROM 
											tbl_mrs_detail md 
									INNER JOIN 
											tbl_mrs m 
									ON 
											m.id = md.mrs_id 
									WHERE 
											m.mrs_date = :date
							)
			";

			// Execute the query
			$command = Yii::app()->db->createCommand($sql);
			$command->bindParam(':date', $date, PDO::PARAM_STR);
			$items = $command->queryAll();

			// echo "<pre>"; print_r($command); die;

			// Check if data exists
			if ($items) {
		
					# You can easily override default constructor's params
					$mPDF = Yii::app()->ePdf->mpdf('', 'A4');
					
					// Create HTML content for the PDF
					$htmlContent = '<h1>Items Below Minimum Quantity</h1>';
					$htmlContent .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
					$htmlContent .= '<thead>
							<tr>
									<th>Item ID</th>
									<th>Item Name</th>
									<th>Vendor Name</th>
									<th>Current Quantity</th>
									<th>Min Quantity</th>
									<th>Max Quantity</th>
									<th>Stock Log ID</th>
							</tr>
						</thead>';
					$htmlContent .= '<tbody>';
					foreach ($items as $item) {
							$htmlContent .= '<tr>
									<td>' . $item['item_id'] . '</td>
									<td>' . $item['item_name'] . '</td>
									<td>' . $item['vendor_name'] . '</td>
									<td>' . $item['current_qty'] . '</td>
									<td>' . $item['min_qty'] . '</td>
									<td>' . $item['max_qty'] . '</td>
									<td>' . $item['stock_log_id'] . '</td>
								</tr>';
					}
					$htmlContent .= '</tbody>';
					$htmlContent .= '</table>';
	
					// Write HTML content to PDF
					$mPDF->WriteHTML($htmlContent);
					$random = rand(1000, 9999); // Generate a random number for unique file name
					$fileName = "whatsapp/$random.pdf";
					
					$mPDF->Output($fileName,'F');

					$baseUrl = Yii::app ()->request->hostinfo . Yii::app()->baseUrl.'/';
					
						$phoneNumbers = [
							'9814740064',
							'9915892003',
							'9040287000',
							'9855618877',
							'9855404577',
							'9914495636',
						];

						// $phoneNumbers = [
						// 	'8872444548',
						// 	'9914495636',
						// ];
						foreach ($phoneNumbers as $phoneNumber) {
								Yii::app()->interaktApi->sendApprovalOrderMessage($phoneNumber, date('Y-m-d'), $baseUrl.$fileName, 'missing_mrs_items');
						}
						echo "Message sent to WhatsApp";
					
					Yii::app()->end();
			} else {
					// Return empty response if no data is found
					echo "No items found below minimum quantity for the given date.";
					Yii::app()->end();
			}
	}

	/**
	 * Import tax data from CSV file
	 */
	public function actionImportTaxData()
	{
		$uploadError = '';
		$importResults = array(
			'success' => 0,
			'failed' => 0,
			'errors' => array()
		);

		if (isset($_POST['submit'])) {
			// Check if file was uploaded
			if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
				$csvFile = $_FILES['csv_file']['tmp_name'];
				
				// Validate file type
				$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
				$mimeType = finfo_file($fileInfo, $csvFile);
				finfo_close($fileInfo);
				
				if ($mimeType !== 'text/plain' && $mimeType !== 'text/csv') {
					$uploadError = 'Please upload a valid CSV file.';
				} else {
					$importResults = $this->processCsvFile($csvFile);
				}
			} else {
				$uploadError = 'Please select a CSV file to upload.';
			}
		}

		$this->render('importTaxData', array(
			'uploadError' => $uploadError,
			'importResults' => $importResults
		));
	}

	/**
	 * Process the uploaded CSV file
	 */
	private function processCsvFile($csvFile)
	{
		$results = array(
			'success' => 0,
			'failed' => 0,
			'errors' => array()
		);

		if (($handle = fopen($csvFile, 'r')) !== FALSE) {
			$rowNumber = 0;
			$isFirstRow = true;
			
			while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
				$rowNumber++;
				
				// Skip header row
				if ($isFirstRow) {
					$isFirstRow = false;
					continue;
				}
				// echo "<pre>";
				// print_r($data);
				
				// if ($rowNumber < 10) {
				// 	$isFirstRow = false;
				// 	continue;
				// }

				// die;
				// Check if we have enough columns
				if (count($data) < 3) {
					$results['failed']++;
					$results['errors'][] = "Row $rowNumber: Insufficient data columns";
					continue;
				}
				
				// Extract data from CSV columns
				$id = trim($data[0]);
				$newGst = trim($data[1]);
				$calGst = trim($data[2]);
				
				// Extract tax percentage from text like "GST @5%" or "IGST @5%"
				// $tax = $this->extractTaxPercentage($taxText);
				
				// // Validate required fields
				// if (empty($title)) {
				// 	$results['failed']++;
				// 	$results['errors'][] = "Row $rowNumber: Title is required";
				// 	continue;
				// }
				
				// if (empty($hsnCode)) {
				// 	$results['failed']++;
				// 	$results['errors'][] = "Row $rowNumber: HSN Code is required";
				// 	continue;
				// }
				
				// if ($tax === false) {
				// 	$results['failed']++;
				// 	$results['errors'][] = "Row $rowNumber: Invalid tax format '$taxText'";
				// 	continue;
				// }
				
				// // Check if record already exists
				// $existingRecord = TblItemNewTax::model()->findByAttributes(array(
				// 	'title' => $title,
				// 	'hsn_code' => $hsnCode
				// ));
				
				// if ($existingRecord) {
				// 	// Update existing record
				// 	$existingRecord->tax = $tax;
				// 	if ($existingRecord->save()) {
				// 		$results['success']++;
				// 	} else {
				// 		$results['failed']++;
				// 		$results['errors'][] = "Row $rowNumber: Failed to update existing record - " . implode(', ', $existingRecord->getErrors());
				// 	}
				// } else {
					// Create new record
					// $model = new TblItemNewTax();
					// $model->title = $title;
					// $model->hsn_code = $hsnCode;
					// $model->tax = $tax;

					// need to update record in tblitem table
					$model = Item::model()->findByPk($id);
					if (!$model) {
						$results['failed']++;
						$results['errors'][] = "Row $rowNumber: Item with ID $id not found";
						continue;
					}
					$model->new_gst = $newGst >= 0 ? $newGst : $calGst;

					// echo "<pre>"; print_r($model); die;
					if ($model->save()) {
						$results['success']++;
					} else {
						$results['failed']++;
						$errors = array();
						foreach ($model->getErrors() as $field => $fieldErrors) {
							$errors[] = $field . ': ' . implode(', ', $fieldErrors);
						}
						$results['errors'][] = "Row $rowNumber: " . implode('; ', $errors);
					}
			}
			fclose($handle);
		} else {
			$results['errors'][] = 'Unable to read the CSV file';
		}
		
		return $results;
	}
	
	/**
	 * Extract tax percentage from text like "GST @5%" or "IGST @28%"
	 */
	private function extractTaxPercentage($taxText)
	{
		// Remove spaces and convert to lowercase for matching
		$cleanText = strtolower(str_replace(' ', '', $taxText));
		
		// Pattern to match GST @5%, IGST @28%, etc.
		if (preg_match('/@(\d+)%/', $cleanText, $matches)) {
			return intval($matches[1]);
		}
		
		// If no percentage symbol found, try to extract just the number
		if (preg_match('/(\d+)/', $cleanText, $matches)) {
			return intval($matches[1]);
		}
		
		return false;
	}

	/**
	 * Batch update prices and tax IDs for items
	 * Updates items in batches to avoid memory issues and timeouts
	 */
	public function actionBatchUpdatePrices()
	{
		$batchSize = isset($_GET['batch_size']) ? (int)$_GET['batch_size'] : 50; // Default 50 items per batch
		$dryRun = isset($_GET['dry_run']) ? (bool)$_GET['dry_run'] : false; // Preview mode
		
		$results = array(
			'status' => 'success',
			'batch_size' => $batchSize,
			'processed' => 0,
			'updated' => 0,
			'errors' => 0,
			'remaining' => 0,
			'dry_run' => $dryRun,
			'details' => array(),
			'sample_updates' => array()
		);

		try {
			// Get total pending items count
			$totalPending = $this->getPendingItemsCount();
			$results['remaining'] = $totalPending;

			if ($totalPending == 0) {
				$results['message'] = 'No items pending for price update.';
				$this->sendJsonResponse($results);
				return;
			}

			// Process one batch
			$batchResults = $this->processPriceBatch($batchSize, $dryRun);
			$results = array_merge($results, $batchResults);
			
			// Get updated remaining count
			$results['remaining'] = $this->getPendingItemsCount();
			
			if ($results['remaining'] > 0) {
				$results['message'] = "Batch completed. {$results['remaining']} items remaining.";
				$results['continue'] = true;
			} else {
				$results['message'] = "All items have been processed successfully!";
				$results['continue'] = false;
			}

		} catch (Exception $e) {
			$results['status'] = 'error';
			$results['message'] = 'Error during batch processing: ' . $e->getMessage();
			$results['error_details'] = $e->getTraceAsString();
		}

		$this->sendJsonResponse($results);
	}

	/**
	 * Check update status and progress
	 */
	public function actionCheckUpdateStatus()
	{
		$status = array(
			'total_items' => $this->getTotalItemsCount(),
			'pending_items' => $this->getPendingItemsCount(),
			'updated_items' => $this->getUpdatedItemsCount(),
			'progress_percentage' => 0
		);

		if ($status['total_items'] > 0) {
			$status['progress_percentage'] = round(($status['updated_items'] / $status['total_items']) * 100, 2);
		}

		$this->sendJsonResponse($status);
	}

	/**
	 * Process a batch of items for price and tax updates
	 */
	private function processPriceBatch($batchSize, $dryRun = false)
	{
		$results = array(
			'processed' => 0,
			'updated' => 0,
			'errors' => 0,
			'details' => array(),
			'sample_updates' => array()
		);

		// Get items that need updating
		$criteria = new CDbCriteria();
		$criteria->addCondition('(price_update_flag = 0 OR price_update_flag IS NULL) AND new_gst IS NOT NULL AND new_gst >= 0 AND new_gst=40');
		$criteria->limit = $batchSize;
		$criteria->order = 'id ASC';
		
		$items = Item::model()->findAll($criteria);

		foreach ($items as $item) {
			$results['processed']++;
			
			try {
				$updateData = $this->calculateItemUpdates($item);
				
				if ($updateData['has_updates']) {
					if (!$dryRun) {
						$success = $this->applyItemUpdates($item, $updateData);
						if ($success) {
							$results['updated']++;
							
							// Mark as updated
							$item->price_update_flag = 1;
							$item->price_update_time = new CDbExpression('NOW()');
							$item->save(false); // Skip validation for performance
						} else {
							$results['errors']++;
							$results['details'][] = "Failed to update item ID: {$item->id} - {$item->title}";
						}
					} else {
						// Dry run - just count what would be updated
						$results['updated']++;
					}

					// Store sample updates for preview (first 50 items)
					if (count($results['sample_updates']) < 50) {
						$results['sample_updates'][] = array(
							'id' => $item->id,
							'title' => $item->title,
							'hsn_code' => $item->hsn_code,
							'old_mrp' => $item->mrp,
							'new_mrp' => $updateData['new_mrp'],
							'old_sale_price' => $item->sale_price,
							'new_sale_price' => $updateData['new_sale_price'],
							'has_change' => $item->mrp != $updateData['new_mrp'] ? true : false,
							'tax_info' => $updateData['tax_info']
						);
					}
				} else {
					// No updates needed, but mark as processed
					if (!$dryRun) {
						$item->price_update_flag = 1;
						$item->price_update_time = new CDbExpression('NOW()');
						$item->save(false);
					}
				}
				
			} catch (Exception $e) {
				$results['errors']++;
				$results['details'][] = "Error processing item ID: {$item->id} - " . $e->getMessage();
			}
		}

		return $results;
	}

	/**
	 * Calculate new prices and tax for an item
	 */
	private function calculateItemUpdates($item)
	{
		$updateData = array(
			'has_updates' => false,
			'new_mrp' => $item->mrp,
			'new_sale_price' => $item->sale_price,
			'tax_info' => array(),
			'item_detail_updates' => array()
		);

		try {
			// Get tax percentage from the item's new_gst field
			$taxPercentage = 0;
			
			$taxPercentage = floatval($item->new_gst);
			$updateData['tax_info']['hsn_code'] = $item->hsn_code;
			$updateData['tax_info']['tax_percentage'] = $taxPercentage;
			$updateData['tax_info']['source'] = 'tbl_item.new_gst';
			
			// Calculate new MRP and sale price using the existing function
			if ($item->purchase_price > 0) {
				$newMrp = Item::getItemMainGSTNewActualMRP($item->id, $taxPercentage);
				$newSalePrice = $newMrp; // Assuming sale price = MRP, adjust if needed
				
				if ($newMrp > 0) {
					$updateData['new_mrp'] = round($newMrp, 2);
					$updateData['new_sale_price'] = round($newSalePrice, 2);
					
					// Check if values actually changed
					if ($updateData['new_mrp'] != $item->mrp || $updateData['new_sale_price'] != $item->sale_price) {
						$updateData['has_updates'] = true;
					}
				}
			}

				
			// Prepare item detail updates
			$criteria = new CDbCriteria();
			$criteria->addCondition('item_id = :item_id');
			$criteria->params = array(':item_id' => $item->id);
			$detail = ItemDetail::model()->find($criteria);
			
			if ($detail) {
				
				$tax = null;
			
				$criteria = new CDbCriteria();
				$criteria->addCondition('(tax_val1 + tax_val2 + tax_val4) = :total_tax');
				$criteria->addCondition('status = :status');
				$criteria->addCondition('type_id = :type_id');
				$criteria->params = array(':total_tax' => $taxPercentage, ':status' => Tax::STATUS_ACTIVE, ':type_id' => $detail->tax->type_id);
				$tax = Tax::model()->find($criteria);
				
				if ($tax) {
					$updateData['tax_info']['tax_id'] = $tax->id;
					$updateData['tax_info']['tax_title'] = $tax->title;
					$updateData['tax_info']['tax_type'] = $tax->type_id;
					$updateData['tax_info']['tax_type_name'] = $tax->type_id == Tax::TYPE_GST ? 'GST' : 'IGST';
					$updateData['has_updates'] = true;
				}

				$detailUpdate = array(
					'id' => $detail->id,
					'current_mrp' => $detail->mrp,
					'current_tax_id' => $detail->tax_id,
					'new_mrp' => $updateData['new_mrp'],
					'new_tax_id' => isset($updateData['tax_info']['tax_id']) ? $updateData['tax_info']['tax_id'] : $detail->tax_id
				);
				
				if ($detailUpdate['new_mrp'] != $detail->mrp || $detailUpdate['new_tax_id'] != $detail->tax_id) {
					$updateData['item_detail_updates'][] = $detailUpdate;
					$updateData['has_updates'] = true;
				}
			}
				
		} catch (Exception $e) {
			// Log error but continue processing
			Yii::log('Error calculating updates for item ID ' . $item->id . ': ' . $e->getMessage(), CLogger::LEVEL_ERROR);
		}

		return $updateData;
	}

	/**
	 * Apply calculated updates to item and related tables
	 */
	private function applyItemUpdates($item, $updateData)
	{
		$transaction = Yii::app()->db->beginTransaction();
		
		try {
			// Update main item table
			if ($updateData['new_mrp'] != $item->mrp || $updateData['new_sale_price'] != $item->sale_price) {
				$item->mrp = $updateData['new_mrp'];
				$item->sale_price = $updateData['new_sale_price'];
				
				if (!$item->save(false)) {
					throw new Exception('Failed to update item: ' . implode(', ', $item->getErrors()));
				}
			}

			// Update item details
			foreach ($updateData['item_detail_updates'] as $detailUpdate) {
				$itemDetail = ItemDetail::model()->findByPk($detailUpdate['id']);
				if ($itemDetail) {
					$itemDetail->mrp = $detailUpdate['new_mrp'];
					$itemDetail->tax_id = $detailUpdate['new_tax_id'];
					
					if (!$itemDetail->save(false)) {
						throw new Exception('Failed to update item detail ID ' . $detailUpdate['id']);
					}

					// Update item tax table if exists and tax_id is available
					try {
					
						if (isset($updateData['tax_info']['tax_id'])) {
							$criteria = new CDbCriteria();
							$criteria->addCondition('item_detail_id = :item_detail_id');
							$criteria->params = array(':item_detail_id' => $itemDetail->id);
							$itemTax = ItemTax::model()->find($criteria);
							
							if ($itemTax && $itemTax->tax_id != $updateData['tax_info']['tax_id']) {
								$itemTax->tax_id = $updateData['tax_info']['tax_id'];
								$itemTax->save(false);
							}
						}
							//code...
					} catch (\Throwable $th) {
						//throw $th;
					}


				}
			}

			$transaction->commit();
			return true;
			
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}
	}

	/**
	 * Get count of items pending for price update
	 */
	private function getPendingItemsCount()
	{
		$criteria = new CDbCriteria();
		$criteria->addCondition('(price_update_flag = 0 OR price_update_flag IS NULL) AND new_gst IS NOT NULL AND new_gst >= 0');
		return Item::model()->count($criteria);
	}

	/**
	 * Get total items count
	 */
	private function getTotalItemsCount()
	{
		return Item::model()->find('(price_update_flag = 0 OR price_update_flag IS NULL) AND new_gst IS NOT NULL AND new_gst >= 0')->count();
	}

	/**
	 * Get count of updated items
	 */
	private function getUpdatedItemsCount()
	{
		$criteria = new CDbCriteria();
		$criteria->addCondition('price_update_flag = 1');
		return Item::model()->count($criteria);
	}

	/**
	 * Send JSON response
	 */
	public function sendJsonResponse($data)
	{
		header('Content-Type: application/json');
		echo json_encode($data);
		Yii::app()->end();
	}

}