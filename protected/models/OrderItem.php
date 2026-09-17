<?php

/**
 *
 * @property integer $id
 * @property integer $order_id
 * @property integer $item_detail_id
 * @property integer $qty
 * @property double $price
 * @property integer $discount_id
 * @property double $discount_amt
 * @property integer $status
 * @property integer $type_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import ( 'application.models._base.BaseOrderItem' );
class OrderItem extends BaseOrderItem {
	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}

	protected function afterSave()
	{
			parent::afterSave();

			// Get the item_id and current record id
			$itemId = $this->item_id;
			$recordId = $this->id;

			// Calculate velocity for this item
			$velocityData = $this->calculateVelocity($itemId);
			// echo "<pre>"; print_r($velocityData);

			// Update the current record with velocity_change_percent
			if ($velocityData) {
					$this->updateItemVelocity($itemId, $velocityData['velocity_change_percent']);
			} else {
					Yii::log("Failed to calculate velocity for item $itemId", 'error');
			}

			return true;
	}

	private function calculateVelocity($itemId)
	{
			$sql = "
					SELECT 
							item_id,
							COALESCE(SUM(CASE WHEN create_time >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
																AND create_time < CURDATE() + INTERVAL 1 DAY 
														THEN qty ELSE 0 END), 0) / 14 AS velocity_last_2_weeks,
							COALESCE(SUM(CASE WHEN create_time >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) 
																AND create_time < DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
														THEN qty ELSE 0 END), 0) / 14 AS velocity_previous_2_weeks,
							CASE 
									WHEN SUM(CASE WHEN create_time >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) 
																AND create_time < DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
														THEN qty ELSE 0 END) = 0 THEN 0
									ELSE (SUM(CASE WHEN create_time >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
																	AND create_time < CURDATE() + INTERVAL 1 DAY 
														THEN qty ELSE 0 END) -
												SUM(CASE WHEN create_time >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) 
																	AND create_time < DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
														THEN qty ELSE 0 END)) /
												SUM(CASE WHEN create_time >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) 
																	AND create_time < DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
														THEN qty ELSE 0 END) * 100
							END AS velocity_change_percent
					FROM 
							tbl_order_item
					WHERE 
							item_id = :itemId
							AND create_time >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
							AND status = 1
					GROUP BY 
							item_id
			";

			$connection = Yii::app()->db;
			$command = $connection->createCommand($sql);
			$command->bindParam(':itemId', $itemId, PDO::PARAM_INT);
			return $command->queryRow();
	}

	private function updateItemVelocity($itemId, $velocityChangePercent)
	{
		$sql = "
				INSERT INTO tbl_item_velocity (item_id, velocity_change_percent, update_time)
				VALUES (:itemId, :velocityChangePercent, NOW())
		";

			$connection = Yii::app()->db;
			$command = $connection->createCommand($sql);
			$command->bindParam(':itemId', $itemId, PDO::PARAM_INT);
			$command->bindParam(':velocityChangePercent', $velocityChangePercent, PDO::PARAM_STR);

			try {
					$command->execute();
					Yii::log("Updated tbl_item_velocity for item $itemId: $velocityChangePercent", 'info');
			} catch (Exception $e) {
					Yii::log("Failed to update tbl_item_velocity for item $itemId: " . $e->getMessage(), 'error');
					throw $e; // Re-throw for transaction handling
			}
	}
	
	public function getTaxArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$order = Order::model()->findByPk($this->order_id);
			$json_entry = array ();
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'order_id =' . $this->order_id );
			$criteria->addCondition ( 'tax_id =' . $this->tax_id );
			$taxes = OrderItem::model ()->findAll ( $criteria );
			// Yii::log ( CVarDumper::dumpAsString ( $taxes ), CLogger::LEVEL_WARNING, '$ordertaxes' );
			
			if ($taxes) {
				$cgst = 0;
				$sgst = 0;
				$cess = 0;
				$igst = 0;
				
				foreach ( $taxes as $tax ) {
					$cgst = $cgst + ($tax->cgst_amt);
					$sgst = $sgst + ($tax->sgst_amt);
					$cess = $cess + ($tax->cess_amt);
					$igst = $igst + ($tax->igst_amt);
				}
			}
		    $json_entry ['hsn_code'] = isset ( $this->item ) ? $this->item->hsn_code : "";
			$json_entry ['total_amt'] = $this->total_amt;
			$json_entry ['price'] = $this->qty * $this->price;
			$json_entry ['tax_amount'] = $this->tax_amount;
			$json_entry ['unit_name'] = isset ( $this->item ) ? $this->item->getMeasurementTypeOptions ( $this->item->unit ) : "";
			$json_entry ['qty'] = $this->qty;
			
			
			$json_entry ['tax_id'] = $this->tax_id;
			
			$item_detail = $this->itemDetail;
			
			
			$json_entry ['tax_percent'] = $item_detail->getItemTaxPercent ();
			
			
			
			$json_entry ['cgst_per'] = isset ( $this->tax ) ? $this->tax->tax_val1 : "";
			$json_entry ['sgst_per'] = isset ( $this->tax ) ? $this->tax->tax_val2 : "";
			$json_entry ['cess_per'] = isset ( $this->tax ) ? $this->tax->tax_val3 : "";
			$json_entry ['igst_per'] = isset ( $this->tax ) ? $this->tax->tax_val4 : "";
			$json_entry ['cgst_amt'] = $this->cgst_amt;
			$json_entry ['sgst_amt'] = $this->sgst_amt;
			$json_entry ['cess_amount'] = $this->cess_amt;
			$json_entry ['igst_amount'] = $this->igst_amt;
			if($order){
				$json_entry ['bill_date'] = date('d-m-Y',strtotime($order->bill_date));
			}
		}
		return $json_entry;
	}
	public function toArray1($return = 0) {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$refundorderitem = null;
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'order_id =' . $model->order_id );
			$refundorder = OrderRefund::model ()->find ( $criteria );
			if ($refundorder) {
				$criteria1 = new CDbCriteria ();
				$criteria1->addCondition ( 'order_refund_id =' . $refundorder->id );
				$criteria1->addCondition ( 'item_detail_id =' . $model->item_detail_id );
				$criteria1->addCondition ( 'item_id =' . $model->item_id );
				$refundorderitem = OrderRefundItem::model ()->find ( $criteria1 );
			}
			$json_list = array ();
			$json_entry = array ();
			$item_detail = $model->itemDetail;
			$json_entry ['item_id'] = $item_detail->id;
			$json_entry ['bar_code'] = $item_detail->bar_code;
			$json_entry ['item_name'] = isset ( $item_detail->item ) ? $item_detail->item->title : "";
			$json_entry ['item_desc'] = isset ( $item_detail->item ) ? $item_detail->item->short_name : "";
			$json_entry ['unit_name'] = isset ( $model->item ) ? $model->item->getMeasurementTypeOptions ( $model->item->unit ) : "";
			$json_entry ['is_coupon'] = isset ( $model->item ) ? $model->item->is_coupon : "";
			if ($return == 0) {
				$json_entry ['box'] = 0;
			} else {
				$json_entry ['is_return'] = 0;
			}
			// $json_entry ['item_detail_id'] = $model->item_detail_id;
			if ($refundorderitem == null) {
				$json_entry ['qty'] = $model->qty;
			} else {
				$json_entry ['qty'] = ($model->qty) - ($refundorderitem->qty);
			}
			$json_entry ['stock_qty'] = $item_detail->getStockQty ();
			$json_entry ['sale_rate'] = $model->sale_rate;
			$json_entry ['base_price'] = $model->price;
			$json_entry ['mrp'] = $model->getItemOrderMrp ();
			$json_entry ['batch_numbers'] = '';
			$item_stock = $item_detail->itemStock;
			if (! empty ( $item_stock )) {
	
				$batch_no = $item_stock->batch_number;
				$json_entry ['batch_numbers'] = $batch_no;
			}
			$json_entry ['discount_id'] = $model->discount_id;
			$json_entry ['discount_val'] = isset ( $model->discount ) ? $model->discount->amount : "0";
			$json_entry ['discount_type'] = isset ( $model->discount ) ? $model->discount->type_id : "1";
			$json_entry ['discount_amt'] = $model->discount_amt;
			$json_entry ['tax_id'] = $model->tax_id;
			$json_entry ['tax_percent'] = $item_detail->getItemTaxPercent ();
			$json_entry ['tax_amt'] = $model->tax_amount;
			if ($refundorderitem == null) {
				$json_entry ['total_amount'] = $model->total_amt;
			} else {
				$json_entry ['total_amount'] = ($model->total_amt) - ($refundorderitem->total_amt);
			}
				
			$json_entry ['cgst_per'] = $model->cgst_per;
			$json_entry ['sgst_per'] = $model->sgst_per;
			$json_entry ['cess_per'] = $model->cess_per;
			$json_entry ['igst_per'] = $model->igst_per;
			$json_entry ['cgst_amt'] = $model->cgst_amt;
			$json_entry ['sgst_amt'] = $model->sgst_amt;
			$json_entry ['cess_amount'] = $model->cess_amt;
			$json_entry ['igst_amount'] = $model->igst_amt;
			if ($refundorderitem == null) {
				$json_entry ['refund_qty'] = 0;
				$json_entry ['refund_amount'] = 0;
			} else {
				$json_entry ['refund_qty'] = $refundorderitem->qty;
				$json_entry ['refund_amount'] = $refundorderitem->total_amt;
			}
		}
		return $json_entry;
	}
	public function toArray($return = 0) {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$refundorderitem = null;
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'order_id =' . $model->order_id );
			$refundorder = OrderRefund::model ()->find ( $criteria );
			if ($refundorder) {
				$criteria1 = new CDbCriteria ();
				$criteria1->addCondition ( 'order_refund_id =' . $refundorder->id );
				$criteria1->addCondition ( 'item_detail_id =' . $model->item_detail_id );
				$criteria1->addCondition ( 'item_id =' . $model->item_id );
				$refundorderitem = OrderRefundItem::model ()->find ( $criteria1 );
			}
			$json_list = array ();
			$json_entry = array ();
			$item_detail = $model->itemDetail;
			$json_entry ['item_id'] = $item_detail->id;
			$json_entry ['bar_code'] = $item_detail->bar_code;
			$json_entry ['item_name'] = isset ( $item_detail->item ) ? $item_detail->item->title : "";
			$json_entry ['item_desc'] = isset ( $item_detail->item ) ? $item_detail->item->short_name : "";
			$json_entry ['hsn_code'] = isset ( $item_detail->item ) ? $item_detail->item->hsn_code : "";
			$json_entry ['unit_name'] = isset ( $model->item ) ? $model->item->getMeasurementTypeOptions ( $model->item->unit ) : "";
			$json_entry ['is_coupon'] = isset ( $model->item ) ? $model->item->is_coupon : "";
			if ($return == 0) {
				$json_entry ['box'] = 0;
			} else {
				$json_entry ['is_return'] = 0;
			}
			// $json_entry ['item_detail_id'] = $model->item_detail_id;
			if ($refundorderitem == null) {
				$json_entry ['qty'] = $model->qty;
			} else {
				$json_entry ['qty'] = ($model->qty) - ($refundorderitem->qty);
			}
			$json_entry ['stock_qty'] = $item_detail->getStockQty ();
			$json_entry ['sale_rate'] = $model->sale_rate;
			$json_entry ['base_price'] = $model->price;
			$json_entry ['mrp'] = $model->getItemOrderMrp ();
			$json_entry ['batch_numbers'] = '';
			$item_stock = $item_detail->itemStock;
			if (! empty ( $item_stock )) {
				
				$batch_no = $item_stock->batch_number;
				$json_entry ['batch_numbers'] = $batch_no;
			}
			$json_entry ['discount_id'] = $model->discount_id;
			$json_entry ['discount_val'] = isset ( $model->discount ) ? $model->discount->amount : "0";
			$json_entry ['discount_type'] = isset ( $model->discount ) ? $model->discount->type_id : "1";
			$json_entry ['discount_amt'] = $model->discount_amt;
			$json_entry ['tax_id'] = $model->tax_id;
			$json_entry ['tax_percent'] = $item_detail->getItemTaxPercent ();
			$json_entry ['tax_amount'] = $model->tax_amount;
			if ($refundorderitem == null) {
				$json_entry ['total_amount'] = $model->total_amt;
			} else {
				$json_entry ['total_amount'] = ($model->total_amt) - ($refundorderitem->total_amt);
			}
			
			$json_entry ['cgst_per'] = $model->cgst_per;
			$json_entry ['sgst_per'] = $model->sgst_per;
			$json_entry ['cess_per'] = $model->cess_per;
			$json_entry ['igst_per'] = $model->igst_per;
			$json_entry ['cgst_amt'] = $model->cgst_amt;
			$json_entry ['sgst_amt'] = $model->sgst_amt;
			$json_entry ['cess_amt'] = $model->cess_amt;
			$json_entry ['igst_amt'] = $model->igst_amt;
			if ($refundorderitem == null) {
				$json_entry ['refund_qty'] = 0;
				$json_entry ['refund_amount'] = 0;
			} else {
				$json_entry ['refund_qty'] = $refundorderitem->qty;
				$json_entry ['refund_amount'] = $refundorderitem->total_amt;
			}
		}
		return $json_entry;
	}
	public function getItemName() {
		$title = '';
		$item_detail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
		if ($item_detail) {
			$item = Item::model ()->findByPk ( $item_detail->item_id );
			if ($item) {
				$title = $item->title;
			}
		}
		return $title;
	}
	public function getItemQuantity() {
		$order_ids = array ();
		$sum = 0;
		$order = Order::model ()->findByPk ( $this->order_id );
		if ($order) {
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'customer_id =' . $order->customer_id );
			$orders = Order::model ()->findAll ( $criteria );
			
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
			$criteria1 = new CDbCriteria ();
			$criteria1->addInCondition ( 'order_id', $order_ids );
			$criteria1->group = 'item_detail_id';
			// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
			// explicitly by the grouped columns to preserve the previous output order.
			$criteria1->order = 'item_detail_id';
			$orderitems = OrderItem::model ()->findAll ( $criteria1 );
			if ($orderitems) {
				foreach ( $orderitems as $orderitem ) {
					$sum = $sum + $orderitem->qty;
				}
			}
		}
		return $sum;
	}
	public function getTaxColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'bill_no',
					'bar_code',
					'sale_rate',
					'item',
					'tax',
					'hrn_code',
					'cgst_per',
					'cgst_amt',
					'sgst_per',
					'sgst_amt',
					'cess_per',
					'cess_amt',
					'tax_amount' 
			);
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'bill_no') {
					$columns [] = array (
							'label' => 'Bill No',
							'value' => function ($data) {
								return isset ( $data->order ) ? $data->order->getOrderBillNo () : "";
							} 
					);
				} else if ($select == 'bar_code') {
					$columns [] = array (
							'label' => 'Barcode',
							'value' => function ($data) {
								return isset ( $data->itemDetail ) ? $data->itemDetail->bar_code : "";
							} 
					);
				} else if ($select == 'item') {
					$columns [] = array (
							'label' => 'Item',
							'value' => function ($data) {
								return $data->getItemName ();
							} 
					);
				} else if ($select == 'sale_rate') {
					$columns [] = array (
							'label' => 'sale_rate',
							'value' => function ($data) {
								return $data->getSaleRate ();
							} 
					);
				} else if ($select == 'tax') {
					$columns [] = array (
							'label' => 'Tax',
							'value' => function ($data) {
								return isset ( $data->tax ) ? $data->tax->title : "";
							} 
					);
				} else if ($select == 'hrn_code') {
					$columns [] = array (
							'label' => 'HSN Code',
							'value' => function ($data) {
								return isset ( $data->tax ) ? $data->tax->hrn_code : "";
							} 
					);
				} else if ($select == 'cgst_per') {
					$columns [] = array (
							'label' => 'CGST(%age)',
							'value' => function ($data) {
								return $data->cgst_per;
							} 
					);
				} else if ($select == 'cgst_amt') {
					$columns [] = array (
							'label' => 'CGST Amount',
							'value' => function ($data) {
								return $data->cgst_amt;
							} 
					);
				} else if ($select == 'sgst_per') {
					$columns [] = array (
							'label' => 'SGST(%age)',
							'value' => function ($data) {
								return $data->sgst_per;
							} 
					);
				} else if ($select == 'sgst_amt') {
					$columns [] = array (
							'label' => 'SGST Amount',
							'value' => function ($data) {
								return $data->sgst_amt;
							} 
					);
				} else if ($select == 'cess_per') {
					$columns [] = array (
							'label' => 'CESS(%age)',
							'value' => function ($data) {
								return $data->cess_per;
							} 
					);
				} else if ($select == 'cess_amt') {
					$columns [] = array (
							'label' => 'CESS Amount',
							'value' => function ($data) {
								return $data->cess_amt;
							} 
					);
				} 

				else {
					$columns [] = $select;
				}
			}
		}
		
		return $columns;
	}
	
	public function getb2bTaxColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'order_id',
					'customer_id',
					'bill_date',
					'taxable',
					'bill_date',
					'Gst',
					'Cgst_per',
					'Sgst_per',
					'Cess_per',
					'Igst_per',
					'Cgst',
					'Sgst',
					'Cess',
					'Igst',
					'Amount'
			);
		}
	
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'order_id') {
					$columns [] = array (
							'label' => 'Bill No',
							'value' => function ($data) {
							return isset ( $data->order ) ? $data->order->getOrderBillNo() : "";
							}
							);
				}
				else if ($select == 'customer_id') {
					$columns [] = array (
							'label' => 'Customer',
							'value' => function ($data) {
							return $data->getItemCustomerName();
							}
							);
				}
				else if ($select == 'bill_date') {
					$columns [] = array (
							'label' => 'Bill Date',
							'value' => function ($data) {
							return isset ( $data->order ) ? $data->order->bill_date : "";
							}
							);
				}
				else if ($select == 'taxable') {
					$columns [] = array (
							'label' => 'Taxable',
							'value' => function ($data) {
							return $data->getTotalItemB2bTaxableAmount ();
							}
							);
				} else if ($select == 'Gst') {
					$columns [] = array (
							'label' => 'Gst',
							'value' => function ($data) {
							return $data->getB2BOrdertotalgstAmount ();
							}
							);
				} else if ($select == 'Cgst_per') {
					$columns [] = array (
							'label' => 'Cgst(%age)',
							'value' => function ($data) {
							return $data->cgst_per;
							}
							);
				} else if ($select == 'Sgst_per') {
					$columns [] = array (
							'label' => 'Sgst(%age)',
							'value' => function ($data) {
							return $data->sgst_per;
							}
							);
				} else if ($select == 'Cess_per') {
					$columns [] = array (
							'label' => 'Cess(%age)',
							'value' => function ($data) {
							return $data->cess_per;
							}
							);
				} else if ($select == 'Igst_per') {
					$columns [] = array (
							'label' => 'Igst(%age)',
							'value' => function ($data) {
							return $data->igst_per;
							}
							);
				} else if ($select == 'Cgst') {
					$columns [] = array (
							'label' => 'Cgst',
							'value' => function ($data) {
							return $data->getB2BGroupTaxCgstAmount ();
							}
							);
				} else if ($select == 'Sgst') {
					$columns [] = array (
							'label' => 'Sgst',
							'value' => function ($data) {
							return $data->getB2BGroupTaxSgstAmount ();
							}
							);
				} else if ($select == 'Cess') {
					$columns [] = array (
							'label' => 'Cess',
							'value' => function ($data) {
							return $data->getB2BGroupTaxCessAmount ();
							}
							);
				} else if ($select == 'Igst') {
					$columns [] = array (
							'label' => 'Igst',
							'value' => function ($data) {
							return $data->getB2BGroupTaxIgstAmount ();
							}
							);
				} else if ($select == 'Amount') {
					$columns [] = array (
							'label' => 'Amount',
							'value' => function ($data) {
							return $data->getB2BGroupTaxOrderTotalAmount ();
							}
							);
				}
	
				else {
					$columns [] = $select;
				}
			}
		}
	
		return $columns;
	}
	
	public function getGroupHSNTaxColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'item_id',
					'hsn_code',
					'order_id',
					'taxable',
					'mode_of_payment',
					'gst',
					'Cgst_per',
					'Sgst_per',
					'Cess_per',
					'Igst_per',
					'Cgst',
					'Sgst',
					'Cess',
					'Igst',
					'Amount' 
			);
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'item_id') {
					$columns [] = array (
							'label' => 'Item name',
							'value' => function ($data) {
								return isset($data->item)?$data->item->title:"";
							} 
					);
				} else if ($select == 'hsn_code') {
					$columns [] = array (
							'label' => 'HSN Code',
							'value' => function ($data) {
								return isset($data->item)?$data->item->hsn_code:"";
							} 
					);
				} else if ($select == 'order_id') {
					$columns [] = array (
							'label' => 'Bill Date',
							'value' => function ($data) {
								return isset($data->order)?$data->order->bill_date:"";
							} 
					);
				}  else if ($select == 'taxable') {
					$columns [] = array (
							'label' => 'Taxable',
							'value' => function ($data) {
								return $data->getTotalHsnItemTaxableAmount();
							} 
					);
				} else if ($select == 'mode_of_payment') {
					$columns [] = array (
							'label' => 'Mode of Payment',
							'value' => function ($data) {
								return isset($data->order)?$data->order->modePayment:"";
							} 
					);
				}else if ($select == 'gst') {
					$columns [] = array (
							'label' => 'Gst',
							'value' => function ($data) {
								return $data->getOrdertotalHsngstAmount();
							} 
					);
				}else if ($select == 'Cgst_per') {
					$columns [] = array (
							'label' => 'Cgst(%age)',
							'value' => function ($data) {
								return $data->cgst_per;
							} 
					);
				} else if ($select == 'Sgst_per') {
					$columns [] = array (
							'label' => 'Sgst(%age)',
							'value' => function ($data) {
								return $data->sgst_per;
							} 
					);
				} else if ($select == 'Cess_per') {
					$columns [] = array (
							'label' => 'Cess(%age)',
							'value' => function ($data) {
								return $data->cess_per;
							} 
					);
				} else if ($select == 'Igst_per') {
					$columns [] = array (
							'label' => 'Igst(%age)',
							'value' => function ($data) {
								return $data->igst_per;
							} 
					);
				} else if ($select == 'Cgst') {
					$columns [] = array (
							'label' => 'Cgst',
							'value' => function ($data) {
								return $data->getGroupHsnTaxCgstAmount ();
							} 
					);
				} else if ($select == 'Sgst') {
					$columns [] = array (
							'label' => 'Sgst',
							'value' => function ($data) {
								return $data->getGroupHsnTaxSgstAmount ();
							} 
					);
				} else if ($select == 'Cess') {
					$columns [] = array (
							'label' => 'Cess',
							'value' => function ($data) {
								return $data->getGroupHsnTaxCessAmount ();
							} 
					);
				} else if ($select == 'Igst') {
					$columns [] = array (
							'label' => 'Igst',
							'value' => function ($data) {
								return $data->getGroupTaxHsnIgstAmount ();
							} 
					);
				} else if ($select == 'Amount') {
					$columns [] = array (
							'label' => 'Amount',
							'value' => function ($data) {
								return $data->getGroupHsnTaxOrderTotalAmount ();
							} 
					);
				} 

				else {
					$columns [] = $select;
				}
			}
		}
		
		return $columns;
	}
	public function getcsvexcel() {
		$selmonth = $month;
		$tank_id = $tank;
		$year = $year;
		$array2  = array();
		$val11 = array();
$csv = array ();
$tank = Tank::findOne($tank_id);
  $a_date = $year."-".$selmonth."-01";
  $date = new DateTime($a_date);
  $date->modify('last day of this month');
  $months = array('01'=>'Jan','02'=>'Feb','03'=>'March','04'=>'April',
	'05'=>'May','06'=>'June','07'=>'July','08'=>'August',
	'09'=>'September','10'=>'October','11'=>'November','12'=>'December');
  $last = $date->format('d') + 1;
  $nozzles = Nozzle::find()->where(['tank_id'=>$tank->id,'status'=>Nozzle::STATUS_ACTIVE])->all();
  $array1 = array (
  'Tank',
  'Month',
  'Year',
					'Date',
					'Opening Balance',
					'Supply',
					'Total',
					'Todays Sales',
					'Closing Balance',
				);
				
  if($nozzles){
   			foreach($nozzles as $nozzle){
					$val1 [] = $nozzle->nozzle_no.'(Opening)';
					$val1 [] = $nozzle->nozzle_no.'(Closing)';
				}}
  if (! empty ( $val1 )) {
					$array2 = $val1;
				}
			$array3 = array('Meter Sale','Testing Sale');	
				
			$array4 = array_merge($array2,$array3);
			$csv [0] = array_merge($array1,$array4);
			$total_supply = 0;
	  $total_today_sale = 0;
			for($i=1;$i<=$last;$i++){
	  $vol = '0.00';
      if($last == $i){
			
			$minus_date =  $year."-".$selmonth.'-'.($i-1);
			$minus_date = date('Y-m-d', strtotime($minus_date . ' +1 day'));
		}else{
      $minus_date =  $year."-".$selmonth.'-'.$i;
		}
     
      $date = date('Y-m-d', strtotime($minus_date . ' +1 day'));
	  $less_date = date('Y-m-d', strtotime($minus_date . ' -1 day'));
      $dipchart = DipChart::find ()->andFilterWhere ( [
          '=',
          'chart_date',
          $date
      ] )->andFilterWhere ( [
          '=',
          'tank_id',
          $tank_id
      ] )->one ();
       
      	$opening_stock = $tank->getTankOpeningStock($date);
   		 	$opening_read = $tank->getTankOpeningReading($minus_date);
   		 	$closing_read = $tank->getTankClosingReading($date);
   		 	$total_sale = $tank->getTankSaleReading($date);
   		 	$supply = Supply::find()->where(['=', 'supply_date',$minus_date])->
   		 	andFilterWhere(['tank_id'=>$tank->id])->one();
   		 	
   		 	if($supply){
   		 		$vol = $supply->volume;
   		 	}
   		 	 if($last != $i){
  $val = $i;
  }else{
	  $val = '1';
  }
   		 	$opening_read = $opening_read - $vol;
   		 	$get_sale = number_format($total_sale,'2','.','');
   		 	$total_vol = $opening_stock + $vol;
			$main_sale = $tank->getTankMainReading($date);
			$testing_sale = $tank->getTestingSale($minus_date);
			
			if($last != $i){
				$diff = 0;
			 $total_supply =  $total_supply + $vol;
			 $total_today_sale =  $total_today_sale + $get_sale;
			}else{
				$diff = $opening_stock - $last_sale;
			}
			  if($last != $i){
				  
				$last_sale = number_format($total_vol-$total_sale,'2','.','');
 $array11 = array (
			$tank->tank_no,
			$months[$month],
			$year,
					$val,
					$opening_stock,
					$vol,
					$total_vol,
					$get_sale,
					number_format($total_vol-$total_sale,'2','.',''),
				);
  }else{
	 $array11 = array (
			$tank->tank_no,
			$months[$month],
			$year,
					'',
					'',
					'',
					'',
					'',
					$opening_stock,
				);
  }
			
     
  
   //$val11[] = array("","");
   $val11 = array();
   $opening = '';
				$closing = '';
   		if($nozzles){
   			foreach($nozzles as $nozzle){
				$opening = '';
				$closing = '';
   			    $reading = Reading::find()->where(['=', 'date(start_time)',$minus_date])->
   			    andFilterWhere(['nozzle_id'=>$nozzle->id])->
   			    andFilterWhere(['=', 'status',Reading::STATUS_APPROVED])->one();
				 $minusreading = Reading::find()->where(['=', 'date(start_time)',$less_date])->
   			    andFilterWhere(['nozzle_id'=>$nozzle->id])->
   			    andFilterWhere(['=', 'status',Reading::STATUS_APPROVED])->one();
				
   if($reading){
  
   		$opening = $reading->opening;
					$closing = $reading->closing;
					}else if($minusreading){
  
   		$opening = $minusreading->closing;
					$closing = '';
					}
					if($last != $i){
			$val11[] = $opening;
					
   		$val11[] = $closing;
					}else{
						$val11[] = '';
					
   		$val11[] = '';
					}
   
  
   		}}
		  if (! empty ( $val11 )) {
					$array12 = $val11;
				}
				$array13 = array(number_format($main_sale,'2','.',''),$testing_sale);
		$array14 = array_merge($array12,$array13);
			$csv [] = array_merge($array11,$array14);
			

    
 }
	  $array15 = array (
  '',
  '',
  '',
					'',
					'',
					$total_supply,
					'',
					$total_today_sale,
					$diff,
				);
				
  if($nozzles){
   			foreach($nozzles as $nozzle){
					$val6 [] = '';
					$val6 [] = '';
				}}
  if (! empty ( $val6 )) {
					$array16 = $val6;
				}
			$array17 = array('','');	
				
			$array18 = array_merge($array16,$array17);
			$csv [] = array_merge($array15,$array17);	
		return $csv;
	}
	public function getGroupTaxColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'taxable',
					'bill_date',
					'Gst',
					'Cgst_per',
					'Sgst_per',
					'Cess_per',
					'Igst_per',
					'Cgst',
					'Sgst',
					'Cess',
					'Igst',
					'Amount' 
			);
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'taxable') {
					$columns [] = array (
							'label' => 'Taxable',
							'value' => function ($data) {
								return $data->getTotalItemTaxableAmount ();
							} 
					);
				} else if ($select == 'bill_date') {
					$columns [] = array (
							'label' => 'Bill Date',
							'value' => function ($data) {
								return isset ( $data->order ) ? $data->order->bill_date : "";
							} 
					);
				} else if ($select == 'Gst') {
					$columns [] = array (
							'label' => 'Gst',
							'value' => function ($data) {
								return $data->getOrderTotalgstAmount ();
							} 
					);
				} else if ($select == 'Cgst_per') {
					$columns [] = array (
							'label' => 'Cgst(%age)',
							'value' => function ($data) {
								return $data->cgst_per;
							} 
					);
				} else if ($select == 'Sgst_per') {
					$columns [] = array (
							'label' => 'Sgst(%age)',
							'value' => function ($data) {
								return $data->sgst_per;
							} 
					);
				} else if ($select == 'Cess_per') {
					$columns [] = array (
							'label' => 'Cess(%age)',
							'value' => function ($data) {
								return $data->cess_per;
							} 
					);
				} else if ($select == 'Igst_per') {
					$columns [] = array (
							'label' => 'Igst(%age)',
							'value' => function ($data) {
								return $data->igst_per;
							} 
					);
				} else if ($select == 'Cgst') {
					$columns [] = array (
							'label' => 'Cgst',
							'value' => function ($data) {
								return $data->getGroupTaxCgstAmount ();
							} 
					);
				} else if ($select == 'Sgst') {
					$columns [] = array (
							'label' => 'Sgst',
							'value' => function ($data) {
								return $data->getGroupTaxSgstAmount ();
							} 
					);
				} else if ($select == 'Cess') {
					$columns [] = array (
							'label' => 'Cess',
							'value' => function ($data) {
								return $data->getGroupTaxCessAmount ();
							} 
					);
				} else if ($select == 'Igst') {
					$columns [] = array (
							'label' => 'Igst',
							'value' => function ($data) {
								return $data->getGroupTaxIgstAmount ();
							} 
					);
				} else if ($select == 'Amount') {
					$columns [] = array (
							'label' => 'Amount',
							'value' => function ($data) {
								return $data->getGroupTaxOrderTotalAmount ();
							} 
					);
				} 

				else {
					$columns [] = $select;
				}
			}
		}
		
		return $columns;
	}
	public function getColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'bill_no',
					'bill_date',
					'bar_code',
					'item',
					'customer_id',
					'employee_id',
					'qty',
					'refund_qty',
					'mrp',
					'discount_amt',
					'tax_amount',
					'total_amount' 
			);
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'bill_date') {
					$columns [] = array (
							'label' => 'Bill Date',
							'value' => function ($data) {
								return isset ( $data->order ) ? $data->order->bill_date : "";
							} 
					);
				} else if ($select == 'bill_no') {
					$columns [] = array (
							'label' => 'Bill No',
							'value' => function ($data) {
								return isset ( $data->order ) ? $data->order->bill_no : "";
							} 
					);
				} else if ($select == 'bar_code') {
					$columns [] = array (
							'label' => 'Barcode',
							'value' => function ($data) {
								return isset ( $data->itemDetail ) ? $data->itemDetail->bar_code : "";
							} 
					);
				} else if ($select == 'item') {
					$columns [] = array (
							'label' => 'Item',
							'value' => function ($data) {
								return $data->getItemName ();
							} 
					);
				} else if ($select == 'customer_id') {
					$columns [] = array (
							'label' => 'Customer',
							'value' => function ($data) {
								return isset ( $data->order ) ? $data->order->customer : "";
							} 
					);
				} else if ($select == 'employee_id') {
					$columns [] = array (
							'label' => 'Employee',
							'value' => function ($data) {
								return isset ( $data->order ) ? $data->order->createUser : "";
							} 
					);
				} else if ($select == 'refund_qty') {
					$columns [] = array (
							'label' => 'Refund Quantity',
							'value' => function ($data) {
								return $data->getOrderRefundQty ();
							} 
					);
				} else if ($select == 'mrp') {
					$columns [] = array (
							'label' => 'Mrp',
							'value' => function ($data) {
								return $data->getItemOrderMrp ();
							} 
					);
				} else if ($select == 'total_amount') {
					$columns [] = array (
							'label' => 'Total Amount',
							'value' => function ($data) {
								return $data->total_amt;
							} 
					);
				} 

				else {
					$columns [] = $select;
				}
			}
		}
		
		return $columns;
	}
	public function getCgstAmt() {
		$discount = 0;
		$item_detail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
		if ($item_detail) {
			$item = Item::model ()->findByPk ( $item_detail->item_id );
			if ($item) {
				$price = $item->sale_price;
				if ($this->tax) {
					$tax = $this->tax->tax_val1;
					$discount = $price * $tax / 100;
				}
			}
		}
		return $discount;
	}
	public function getSgstAmt() {
		$discount = 0;
		$item_detail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
		if ($item_detail) {
			$item = Item::model ()->findByPk ( $item_detail->item_id );
			if ($item) {
				$price = $item->sale_price;
				if ($this->tax) {
					$tax = $this->tax->tax_val2;
					$discount = $price * $tax / 100;
				}
			}
		}
		return $discount;
	}
	public function getCessAmt() {
		$discount = 0;
		$item_detail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
		if ($item_detail) {
			$item = Item::model ()->findByPk ( $item_detail->item_id );
			if ($item) {
				$price = $item->sale_price;
				if ($this->tax) {
					$tax = $this->tax->tax_val3;
					$discount = $price * $tax / 100;
				}
			}
		}
		return $discount;
	}
	public function getSaleRate() {
		$sale_rate = 0;
		$item_detail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
		if ($item_detail) {
			$item = Item::model ()->findByPk ( $item_detail->item_id );
			$sale_rate = $item->sale_price;
		}
		return $sale_rate;
	}
	public function getItemwiseColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'item_detail_id',
					'item_id',
					'qty',
					'price',
					// 'discount_amt',
					'tax_amt',
					'amount' 
			);
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'item_detail_id') {
					$columns [] = array (
							'label' => 'Bar Code',
							'value' => function ($data) {
								return isset ( $data->itemDetail ) ? $data->itemDetail->bar_code : "";
							} 
					);
				}
				if ($select == 'item_id') {
					$columns [] = array (
							'label' => 'Item',
							'value' => function ($data) {
								return $data->getItemName ();
							} 
					);
				}
				if ($select == 'price') {
					$columns [] = array (
							'label' => 'MRP',
							'value' => function ($data) {
								return $data->getItemOrderMrp ();
							} 
					);
				}
				
				if ($select == 'qty') {
					$columns [] = array (
							'label' => 'Quantity',
							'value' => function ($data) {
								return $data->getItemTotalQty ();
							} 
					);
				} else if ($select == 'amount') {
					$columns [] = array (
							'label' => 'Total Amount',
							'value' => function ($data) {
								return $data->getItemTotalAmount ();
							} 
					);
				} 

				else {
					$columns [] = $select;
				}
			}
		}
		
		return $columns;
	}
	public function getItemTotalQty() {
		$total = 0;
		$criteria1 = new CDbCriteria ();
		if ((Yii::app ()->session ['item_id'] != '')) {
			$criteria1->addInCondition ( 'item_id', Yii::app ()->session ['item_id'] );
		}
		if ((Yii::app ()->session ['start_date'] != '') && (Yii::app ()->session ['end_date'] != '')) {
			$criteria1->addBetweenCondition ( 'date(create_time)', Yii::app ()->session ['start_date'], Yii::app ()->session ['end_date'] );
		}
		Yii::log ( CVarDumper::dumpAsString ( Yii::app ()->session ['start_date'] ), CLogger::LEVEL_WARNING, 'start_date' );
		$criteria1->addCondition ( 'item_id =' . $this->item_id );
		$orderitems = OrderItem::model ()->findAll ( $criteria1 );
		Yii::log ( CVarDumper::dumpAsString ( $orderitems ), CLogger::LEVEL_WARNING, '$orderitems' );
		Yii::log ( CVarDumper::dumpAsString ( $orderitems ), CLogger::LEVEL_WARNING, '$orderitems' );
		if ($orderitems) {
			
			foreach ( $orderitems as $orderitem ) {
				$qty = $orderitem->qty;
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'order_id =' . $orderitem->order_id );
				if ((Yii::app ()->session ['start_date'] != '') && (Yii::app ()->session ['end_date'] != '')) {
					$criteria->addBetweenCondition ( 'date(create_time)', Yii::app ()->session ['start_date'], Yii::app ()->session ['end_date'] );
				}
				$orderRefund = OrderRefund::model ()->find ( $criteria );
				if ($orderRefund) {
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_refund_id =' . $orderRefund->id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderitem->item_detail_id );
					if ((Yii::app ()->session ['start_date'] != '') && (Yii::app ()->session ['end_date'] != '')) {
						$criteria3->addBetweenCondition ( 'date(create_time)', Yii::app ()->session ['start_date'], Yii::app ()->session ['end_date'] );
					}
					$criteria3->addCondition ( 'item_id =' . $orderitem->item_id );
					$orderRefundItems = OrderRefundItem::model ()->findAll ( $criteria3 );
					if ($orderRefundItems) {
						$refundqty = 0;
						foreach ( $orderRefundItems as $orderRefundItem ) {
							$refundqty = $refundqty + ($orderRefundItem->qty);
						}
						$qty = $qty - $refundqty;
						if ($qty < 0) {
							$qty = 0;
						}
					}
				}
				$total = $total + $qty;
			}
		}
		return $total;
	}
	public function getItemTotalAmount() {
		$total = 0;
		$criteria1 = new CDbCriteria ();
		if ((Yii::app ()->session ['item_id'] != '')) {
			$criteria1->addInCondition ( 'item_id', Yii::app ()->session ['item_id'] );
		}
		// $criteria1->compare('order_id', $this->order_id);
		if ((Yii::app ()->session ['start_date'] != '') && (Yii::app ()->session ['end_date'] != '')) {
			$criteria1->addBetweenCondition ( 'date(create_time)', Yii::app ()->session ['start_date'], Yii::app ()->session ['end_date'] );
		}
		$criteria1->addCondition ( 'item_id =' . $this->item_id );
		$orderitems = OrderItem::model ()->findAll ( $criteria1 );
		$refund = 0;
		Yii::log ( CVarDumper::dumpAsString ( Yii::app ()->session ['start_date'] ), CLogger::LEVEL_WARNING, 'start_date' );
		Yii::log ( CVarDumper::dumpAsString ( Yii::app ()->session ['end_date'] ), CLogger::LEVEL_WARNING, 'end_date' );
		Yii::log ( CVarDumper::dumpAsString ( Yii::app ()->session ['item_id'] ), CLogger::LEVEL_WARNING, 'item_id' );
		foreach ( $orderitems as $orderitem ) {
			$qty = $orderitem->qty;
			$refund = 0;
			$criteria = new CDbCriteria ();
			$criteria->addCondition ( 'order_id =' . $orderitem->order_id );
			if ((Yii::app ()->session ['start_date'] != '') && (Yii::app ()->session ['end_date'] != '')) {
				$criteria->addBetweenCondition ( 'date(create_time)', Yii::app ()->session ['start_date'], Yii::app ()->session ['end_date'] );
			}
			$orderRefund = OrderRefund::model ()->find ( $criteria );
			if ($orderRefund) {
				$criteria3 = new CDbCriteria ();
				$criteria3->addCondition ( 'order_refund_id =' . $orderRefund->id );
				$criteria3->addCondition ( 'item_detail_id =' . $orderitem->item_detail_id );
				if ((Yii::app ()->session ['start_date'] != '') && (Yii::app ()->session ['end_date'] != '')) {
					$criteria3->addBetweenCondition ( 'date(create_time)', Yii::app ()->session ['start_date'], Yii::app ()->session ['end_date'] );
				}
				$criteria3->addCondition ( 'item_id =' . $orderitem->item_id );
				$orderRefundItems = OrderRefundItem::model ()->findAll ( $criteria3 );
				if ($orderRefundItems) {
					
					foreach ( $orderRefundItems as $orderRefundItem ) {
						$refund = $refund + ($orderRefundItem->total_amt);
					}
					/*
					 * $qty = $qty - $refundqty;
					 * if($qty <0){
					 * $qty = 0;
					 * }
					 */
				}
			}
			$amt = ($orderitem->total_amt) - ($refund);
			$total = $total + $amt;
		}
		return $total;
	}
	public function getCgstAmount() {
		$taxAmount = 0;
		if ($this->tax_id != 0) {
			$tax = Tax::model ()->findByPk ( $this->tax_id );
			if ($tax) {
				
				$baseprice = $this->price;
				$discount_val = $this->discount_amt;
				$tax = $tax->tax_val1;
				$baseprice = $this->price - $discount_val;
				
				$taxAmount = ($baseprice) * $tax / 100;
			}
		}
		
		return number_format ( $taxAmount, 4 );
	}
	public function getSgstAmount() {
		$taxAmount = 0;
		if ($this->tax_id != 0) {
			$tax = Tax::model ()->findByPk ( $this->tax_id );
			if ($tax) {
				
				$baseprice = $this->price;
				$discount_val = $this->discount_amt;
				$tax = $tax->tax_val2;
				$baseprice = $this->price - $discount_val;
				$taxAmount = ($baseprice) * $tax / 100;
			}
		}
		return number_format ( $taxAmount, 4 );
	}
	public function getCessAmount() {
		$taxAmount = 0;
		if ($this->tax_id != 0) {
			$tax = Tax::model ()->findByPk ( $this->tax_id );
			if ($tax) {
				
				$baseprice = $this->price;
				$discount_val = $this->discount_amt;
				$tax = $tax->tax_val3;
				$baseprice = $this->price - $discount_val;
				$taxAmount = ($baseprice) * $tax / 100;
			}
		}
		return number_format ( $taxAmount, 4 );
	}
	public function getIgstAmount() {
		$taxAmount = 0;
		if ($this->tax_id != 0) {
			$tax = Tax::model ()->findByPk ( $this->tax_id );
			if ($tax) {
				
				$baseprice = $this->price;
				$discount_val = $this->discount_amt;
				$tax = $tax->tax_val4;
				$baseprice = $this->price - $discount_val;
				$taxAmount = ($baseprice) * $tax / 100;
			}
		}
		return number_format ( $taxAmount, 4 );
	}
	public function getTotalHsnItemTaxableAmount() {
		$amount = 0;
		$eamount = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
			
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'item_id =' . $this->item_id );
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(price*qty) as price';
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$criteria->addInCondition ( 'order_id', $order_ids );
		}
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->price;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria3->select = 'sum(price*qty) as qty';
		$criteria3->addCondition ( 'item_id =' . $this->item_id );
		$orderRefundItem = OrderRefundItem::model ()->find ( $criteria3 );
		
		$refund_price = $orderRefundItem->qty;
		
		$amount = $order_price - $refund_price;
		
		/*
		 * if($this->tax_id == '10'){
		 * Yii::log ( CVarDumper::dumpAsString ( $orderRefundItems ), CLogger::LEVEL_WARNING, '$orderRefundItemsss' );
		 * Yii::log ( CVarDumper::dumpAsString ( Yii::app ()->session ['order_mode_payment'] ), CLogger::LEVEL_WARNING, 'sessionn' );
		 * Yii::log ( CVarDumper::dumpAsString ( $this->create_date ), CLogger::LEVEL_WARNING, '$this->create_date' );
		 * Yii::log ( CVarDumper::dumpAsString ( $eamount ), CLogger::LEVEL_WARNING, '$eamount' );
		 * Yii::log ( CVarDumper::dumpAsString ( $refund ), CLogger::LEVEL_WARNING, '$eerefund' );
		 * }
		 */
		return $amount;
	}
	public function getTotalItemTaxableAmount() {
		$amount = 0;
		$eamount = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
			
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(price*qty) as price';
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$criteria->addInCondition ( 'order_id', $order_ids );
		}
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->price;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria3->select = 'sum(price*qty) as qty';
		$orderRefundItem = OrderRefundItem::model ()->find ( $criteria3 );
		$refund_price = $orderRefundItem->qty;
		
		$amount = $order_price - $refund_price;
		
		/*
		 * if($this->tax_id == '10'){
		 * Yii::log ( CVarDumper::dumpAsString ( $orderRefundItems ), CLogger::LEVEL_WARNING, '$orderRefundItemsss' );
		 * Yii::log ( CVarDumper::dumpAsString ( Yii::app ()->session ['order_mode_payment'] ), CLogger::LEVEL_WARNING, 'sessionn' );
		 * Yii::log ( CVarDumper::dumpAsString ( $this->create_date ), CLogger::LEVEL_WARNING, '$this->create_date' );
		 * Yii::log ( CVarDumper::dumpAsString ( $eamount ), CLogger::LEVEL_WARNING, '$eamount' );
		 * Yii::log ( CVarDumper::dumpAsString ( $refund ), CLogger::LEVEL_WARNING, '$eerefund' );
		 * }
		 */
		return $amount;
	}
	public function getItemTaxableAmount() {
		$amount = 0;
		$date = date ( 'Y-m-d', strtotime ( $this->create_time ) );
		
		$qty = $this->qty;
		$refund = 0;
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'order_id =' . $this->order_id );
		$criteria->compare ( 'date(create_time)', $date );
		$orderRefund = OrderRefund::model ()->find ( $criteria );
		if ($orderRefund) {
			$criteria3 = new CDbCriteria ();
			$criteria3->addCondition ( 'order_refund_id =' . $orderRefund->id );
			$criteria3->addCondition ( 'item_detail_id =' . $this->item_detail_id );
			$criteria3->compare ( 'date(create_time)', $date );
			$criteria3->addCondition ( 'item_id =' . $this->item_id );
			$orderRefundItems = OrderRefundItem::model ()->findAll ( $criteria3 );
			
			foreach ( $orderRefundItems as $orderRefundItem ) {
				$refund = $refund + ($orderRefundItem->total_amt);
			}
		}
		$amount = $amount + ((($this->total_amt + $this->discount_amt) - ($this->tax_amount)) - $refund);
		
		return $amount;
	}
	public function getOrderTaxableAmount() {
		$amount = 0;
		$refund = 0;
		$date = date ( 'Y-m-d', strtotime ( $this->create_time ) );
		$criteria = new CDbCriteria ();
		$criteria->compare ( 'date(create_time)', $date );
		$criteria->addCondition ( 'order_id =' . $this->order_id );
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$orders = OrderItem::model ()->findAll ( $criteria );
		if ($orders) {
			foreach ( $orders as $order ) {
				$qty = $order->qty;
				$refund = 0;
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'order_id =' . $order->order_id );
				$criteria->compare ( 'date(create_time)', $date );
				$orderRefund = OrderRefund::model ()->find ( $criteria );
				if ($orderRefund) {
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_refund_id =' . $orderRefund->id );
					$criteria3->addCondition ( 'item_detail_id =' . $order->item_detail_id );
					$criteria3->compare ( 'date(create_time)', $date );
					$criteria3->addCondition ( 'item_id =' . $order->item_id );
					$orderRefundItems = OrderRefundItem::model ()->findAll ( $criteria3 );
					
					foreach ( $orderRefundItems as $orderRefundItem ) {
						$refund = $refund + ($orderRefundItem->total_amt);
					}
				}
				$amount = $amount + ((($order->total_amt) - ($order->tax_amount)) - $refund);
			}
		}
		return $amount;
	}
	public function getOrderCgstAmount() {
		$amount = 0;
		$date = date ( 'Y-m-d', strtotime ( $this->create_time ) );
		$criteria = new CDbCriteria ();
		$criteria->compare ( 'date(create_time)', $date );
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$orders = OrderItem::model ()->findAll ( $criteria );
		if ($orders) {
			foreach ( $orders as $order ) {
				$qty = $order->qty;
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'order_id =' . $order->order_id );
				$criteria->compare ( 'date(create_time)', $date );
				$orderRefund = OrderRefund::model ()->find ( $criteria );
				if ($orderRefund) {
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_refund_id =' . $orderRefund->id );
					$criteria3->compare ( 'date(create_time)', $date );
					$criteria3->addCondition ( 'item_detail_id =' . $order->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $order->item_id );
					$orderRefundItems = OrderRefundItem::model ()->findAll ( $criteria3 );
					if ($orderRefundItems) {
						$refundqty = 0;
						foreach ( $orderRefundItems as $orderRefundItem ) {
							$refundqty = $refundqty + ($orderRefundItem->qty);
						}
						$qty = $qty - $refundqty;
						if ($qty < 0) {
							$qty = 0;
						}
					}
				}
				$cgst = ($qty) * ($order->getCgstAmount ());
				$amount = $amount + $cgst;
			}
		}
		return $amount;
	}
	public function getOrderSgstAmount() {
		$amount = 0;
		$date = date ( 'Y-m-d', strtotime ( $this->create_time ) );
		$criteria = new CDbCriteria ();
		$criteria->compare ( 'date(create_time)', $date );
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$orders = OrderItem::model ()->findAll ( $criteria );
		if ($orders) {
			foreach ( $orders as $order ) {
				$qty = $order->qty;
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'order_id =' . $order->order_id );
				$criteria->compare ( 'date(create_time)', $date );
				$orderRefund = OrderRefund::model ()->find ( $criteria );
				if ($orderRefund) {
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_refund_id =' . $orderRefund->id );
					$criteria3->addCondition ( 'item_detail_id =' . $order->item_detail_id );
					$criteria3->compare ( 'date(create_time)', $date );
					$criteria3->addCondition ( 'item_id =' . $order->item_id );
					$orderRefundItems = OrderRefundItem::model ()->findAll ( $criteria3 );
					if ($orderRefundItems) {
						$refundqty = 0;
						foreach ( $orderRefundItems as $orderRefundItem ) {
							$refundqty = $refundqty + ($orderRefundItem->qty);
						}
						$qty = $qty - $refundqty;
						if ($qty < 0) {
							$qty = 0;
						}
					}
				}
				$cgst = ($qty) * ($order->getSgstAmount ());
				$amount = $amount + $cgst;
			}
		}
		return $amount;
	}
	public function getOrderCessAmount() {
		$amount = 0;
		$date = date ( 'Y-m-d', strtotime ( $this->create_time ) );
		$criteria = new CDbCriteria ();
		$criteria->compare ( 'date(create_time)', $date );
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$orders = OrderItem::model ()->findAll ( $criteria );
		if ($orders) {
			foreach ( $orders as $order ) {
				$qty = $order->qty;
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'order_id =' . $order->order_id );
				$criteria->compare ( 'date(create_time)', $date );
				$orderRefund = OrderRefund::model ()->find ( $criteria );
				if ($orderRefund) {
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_refund_id =' . $orderRefund->id );
					$criteria3->addCondition ( 'item_detail_id =' . $order->item_detail_id );
					$criteria3->compare ( 'date(create_time)', $date );
					$criteria3->addCondition ( 'item_id =' . $order->item_id );
					$orderRefundItems = OrderRefundItem::model ()->findAll ( $criteria3 );
					if ($orderRefundItems) {
						$refundqty = 0;
						foreach ( $orderRefundItems as $orderRefundItem ) {
							$refundqty = $refundqty + ($orderRefundItem->qty);
						}
						$qty = $qty - $refundqty;
						if ($qty < 0) {
							$qty = 0;
						}
					}
				}
				$cgst = ($qty) * ($order->getCessAmount ());
				$amount = $amount + $cgst;
			}
		}
		return $amount;
	}
	public function getOrderIgstAmount() {
		$amount = 0;
		$date = date ( 'Y-m-d', strtotime ( $this->create_time ) );
		$criteria = new CDbCriteria ();
		$criteria->compare ( 'date(create_time)', $date );
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$orders = OrderItem::model ()->findAll ( $criteria );
		if ($orders) {
			foreach ( $orders as $order ) {
				$qty = $order->qty;
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'order_id =' . $order->order_id );
				$criteria->compare ( 'date(create_time)', $date );
				$orderRefund = OrderRefund::model ()->find ( $criteria );
				if ($orderRefund) {
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_refund_id =' . $orderRefund->id );
					$criteria3->addCondition ( 'item_detail_id =' . $order->item_detail_id );
					$criteria3->compare ( 'date(create_time)', $date );
					$criteria3->addCondition ( 'item_id =' . $order->item_id );
					$orderRefundItems = OrderRefundItem::model ()->findAll ( $criteria3 );
					if ($orderRefundItems) {
						$refundqty = 0;
						foreach ( $orderRefundItems as $orderRefundItem ) {
							$refundqty = $refundqty + ($orderRefundItem->qty);
						}
						$qty = $qty - $refundqty;
						if ($qty < 0) {
							$qty = 0;
						}
					}
				}
				$cgst = ($qty) * ($order->getIgstAmount ());
				$amount = $amount + $cgst;
			}
		}
		return $amount;
	}
	public function getOrdertotalHsngstAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
			
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->addCondition ( 'item_id =' . $this->item_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(tax_amount) as tax_amount';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
			$criteria->addInCondition ( 'order_id', $order_ids );
		} */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->tax_amount;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria3->addCondition ( 'item_id =' . $this->item_id );
		$criteria3->select = 'sum(tax_amt) as tax_amt';
		$orderRefundItem = OrderRefundItem::model ()->find ( $criteria3 );
		$refund_price = $orderRefundItem->tax_amt;
		
		$amount = $order_price - $refund_price;
		
		/*
		 * if ($orders) {
		 *
		 * foreach ( $orders as $order ) {
		 * $amount = $amount + $order->tax_amount;
		 * }
		 * }
		 */
		return $amount;
	}
	public function getOrdertotalgstAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
			
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(tax_amount) as tax_amount';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
			$criteria->addInCondition ( 'order_id', $order_ids );
		} */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->tax_amount;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria3->select = 'sum(tax_amt) as tax_amt';
		$orderRefundItem = OrderRefundItem::model ()->find ( $criteria3 );
		$refund_price = $orderRefundItem->tax_amt;
		
		$amount = $order_price - $refund_price;
		
		/*
		 * if ($orders) {
		 *
		 * foreach ( $orders as $order ) {
		 * $amount = $amount + $order->tax_amount;
		 * }
		 * }
		 */
		return $amount;
	}
	public function getGroupTaxCgstAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
			
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(cgst_amt) as cgst_amt';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
		 $criteria->addInCondition ( 'order_id', $order_ids );
		 } */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->cgst_amt;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
				$order = OrderItem::model ()->find( $criteria3);
				if($order){
					$ordercgst = $order->cgst_amt / $order->qty;
					$refund = $refund + ($ordercgst * $orderRefundItem->qty);
				}
				}
			}
		}
		$amount = $order_price - $refund;
		
		
		return round ( $amount, 2 );
	}
	public function getGroupTaxSgstAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
				
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
				
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(sgst_amt) as sgst_amt';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
		 $criteria->addInCondition ( 'order_id', $order_ids );
		 } */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->sgst_amt;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->sgst_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
		
		
		return round ( $amount, 2 );
		
		
		
	}
	public function getGroupTaxIgstAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
		
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
		
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(igst_amt) as igst_amt';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
		 $criteria->addInCondition ( 'order_id', $order_ids );
		 } */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->igst_amt;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->igst_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
		
		
		return round ( $amount, 2 );
	}
	public function getGroupTaxCessAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
		
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
		
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(cess_amt) as cess_amt';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
		 $criteria->addInCondition ( 'order_id', $order_ids );
		 } */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->cess_amt;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->cess_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
		
		
		return round ( $amount, 2 );
	}
	public function getGroupTaxOrderTotalAmount() {
		$amount = 0;
		$oamount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			
			$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'mode_of_payment =' . $this->order->mode_of_payment );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		
		$orders = OrderItem::model ()->findAll( $criteria );
		if($orders){
			foreach($orders as $order){
				$oamount = $oamount + ((($order->price) * ($order->qty)) + ($order->tax_amount));
			}
		}
		
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			
			foreach($orderRefundItems as $orderRefundItem){
				$refund = $refund + ((($orderRefundItem->price) * ($orderRefundItem->qty)) + ($orderRefundItem->tax_amt));
					
			}
		}
		$amount = $oamount - $refund;
		
		
		return round ( $amount, 2 );
	}
	public function getOrdergstAmount() {
		$amount = 0;
		
		$order = OrderItem::model ()->findByPk ( $this->id );
		if ($order) {
			$cgst = ($order->cgst_amt) + ($order->sgst_amt) + ($order->cess_amt) + ($order->igst_amt);
			$amount = $amount + $cgst;
		}
		return $amount;
	}
	public function getOrderTotalAmount() {
		$amount = 0;
		$order = OrderItem::model ()->findByPk ( $this->id );
		if ($order) {
			// $cgst = $order->getOrdergstAmount()+$order->getOrderTaxableAmount();
			$item_total_amt = $order->total_amt;
			$amount = $amount + $item_total_amt;
		}
		return $amount;
	}
	
	public function getGroupHsnTaxCgstAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
			
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->addCondition ( 'item_id =' . $this->item_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(cgst_amt) as cgst_amt';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
		 $criteria->addInCondition ( 'order_id', $order_ids );
		 } */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->cgst_amt;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria3->addCondition ( 'item_id =' . $this->item_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
				$order = OrderItem::model ()->find( $criteria3);
				if($order){
					$ordercgst = $order->cgst_amt / $order->qty;
					$refund = $refund + ($ordercgst * $orderRefundItem->qty);
				}
				}
			}
		}
		$amount = $order_price - $refund;
		
		
		return round ( $amount, 2 );
	}
	public function getGroupHsnTaxSgstAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
				
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
				
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->addCondition ( 'item_id =' . $this->item_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(sgst_amt) as sgst_amt';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
		 $criteria->addInCondition ( 'order_id', $order_ids );
		 } */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->sgst_amt;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria3->addCondition ( 'item_id =' . $this->item_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->sgst_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
		
		
		return round ( $amount, 2 );
		
		
		
	}
	public function getGroupTaxHsnIgstAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
		
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
		
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->addCondition ( 'item_id =' . $this->item_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(igst_amt) as igst_amt';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
		 $criteria->addInCondition ( 'order_id', $order_ids );
		 } */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->igst_amt;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'item_id =' . $this->item_id );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->igst_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
		
		
		return round ( $amount, 2 );
	}
	public function getGroupHsnTaxCessAmount() {
		$amount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			$criteria1 = new CDbCriteria ();
		
			$criteria1->addCondition ( 'mode_of_payment =' . Yii::app ()->session ['order_mode_payment'] );
		
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->addCondition ( 'item_id =' . $this->item_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(cess_amt) as cess_amt';
		/* if (Yii::app ()->session ['order_mode_payment'] != '') {
		 $criteria->addInCondition ( 'order_id', $order_ids );
		 } */
		// $criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
		
		$order_price = $order->cess_amt;
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria3->addCondition ( 'item_id =' . $this->item_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->cess_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
		
		
		return round ( $amount, 2 );
	}
	public function getGroupHsnTaxOrderTotalAmount() {
		$amount = 0;
		$oamount = 0;
		$refund = 0;
		if (Yii::app ()->session ['order_mode_payment'] != '') {
			$order_ids = array ();
			
			$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'mode_of_payment =' . $this->order->mode_of_payment );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->addCondition ( 'item_id =' . $this->item_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		
		$orders = OrderItem::model ()->findAll( $criteria );
		if($orders){
			foreach($orders as $order){
				$oamount = $oamount + ((($order->price) * ($order->qty)) + ($order->tax_amount));
			}
		}
		
		
		$criteria3 = new CDbCriteria ();
		$criteria3->compare ( 'date(create_time)', $this->create_date );
		$criteria3->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria3->addCondition ( 'item_id =' . $this->item_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			
			foreach($orderRefundItems as $orderRefundItem){
				$refund = $refund + ((($orderRefundItem->price) * ($orderRefundItem->qty)) + ($orderRefundItem->tax_amt));
					
			}
		}
		$amount = $oamount - $refund;
		
		
		return round ( $amount, 2 );
	}
	
	public function getOrderRefundQty() {
		$qty = 0;
		$orderRefund = OrderRefund::model ()->findByAttributes ( array (
				'order_id' => $this->order_id 
		) );
		if ($orderRefund) {
			$criteria = new CDbCriteria ();
			$criteria->compare ( 'order_refund_id', $orderRefund->id );
			$criteria->compare ( 'item_detail_id', $this->item_detail_id );
			$criteria->compare ( 'item_id', $this->item_id );
			$items = OrderRefundItem::model ()->findAll ( $criteria );
			if ($items) {
				foreach ( $items as $item ) {
					$qty = $qty + ($item->qty);
				}
			}
		}
		return $qty;
	}
	public function getItemOrderMrp() {
		return $this->mrp;
	}
	public function getTaxValueID($tax_id) {
		$tax = Tax::model ()->findByPk ( $tax_id );
		if ($tax) {
			if ($tax->type_id == Tax::TYPE_IGST) {
				$val = $tax->tax_val4 / 2;
				$criteria = new CDbCriteria ();
				$criteria->addCondition ( 'tax_val1 =' . $val );
				$criteria->addCondition ( 'tax_val2 =' . $val );
				$criteria->addCondition ( 'type_id =' . Tax::TYPE_GST );
				$tax = Tax::model ()->find ( $criteria );
				if ($tax) {
					return $tax->id;
				}
			} else {
				return $tax->id;
			}
		} else {
			return $tax->id;
		}
	}
	
	public function getTotalItemB2bTaxableAmount() {
		$amount = 0;
		$oamount = 0;
		$refund = 0;
		$order_ids = array ();
		$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'title = "B2B"' );
			
			$paymentmode = PaymentMode::model ()->find( $criteria1 );
			
		if ($paymentmode) {
			
			
			$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'mode_of_payment =' . $paymentmode->id );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->select = 'sum(price*qty) as price';
		$criteria->addInCondition('order_id',$order_ids);
		$order = OrderItem::model ()->find( $criteria );
		
		
		
		
		$amount = $order->price ;
		
		
		
		
		$refund_price = '0.00';
		$criteria3 = new CDbCriteria ();
		$criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		$criteria3->compare ( 'date(orderRefund.create_time)', $this->create_date );
		$criteria3->select = 'sum(t.price*t.qty) as qty';
		$criteria3->with = 'orderRefund';
		$criteria3->addInCondition('orderRefund.order_id',$order_ids);
		
		$orderRefundItem = OrderRefundItem::model ()->find ( $criteria3 );
		if($orderRefundItem){
		$refund_price = $orderRefundItem->qty;
		}
	
		$amount = $amount - $refund_price;
	
		return $amount;
	}
	public function getB2BOrdertotalgstAmount() {
		$tax =  $this->getB2BGroupTaxCgstAmount() + $this->getB2BGroupTaxSgstAmount()+$this->getB2BGroupTaxIgstAmount() + $this->getB2BGroupTaxCessAmount();
		return round ( $tax, 2 );
		/*$amount = 0;
		$refund = 0;
	
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->select = 'sum(tax_amount) as tax_amount';
		$criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
	
		$order_price = $order->tax_amount;
		$refund_price = '0.00';
		$criteria3 = new CDbCriteria ();
		
		$criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		$criteria3->select = 'sum(t.tax_amt) as tax_amt';
		$criteria3->with = 'orderRefund';
		$criteria3->addCondition ( 'orderRefund.order_id ='.$this->order_id );
		$orderRefundItem = OrderRefundItem::model ()->find ( $criteria3 );
		if($orderRefundItem){
		//$refund_price = $orderRefundItem->tax_amt;
		}
		$amount = $order_price - $refund_price;
	
		return $amount;*/
	}
	
	
	public function getB2BGroupTaxCgstAmount() {
		$amount = 0;
		$oamount = 0;
		$refund = 0;
		$taxable = $this->getTotalItemB2bTaxableAmount();
		$cgst_per = $this->cgst_per;
		
		
		
		
		
		$amount = $taxable * $cgst_per/100;
		
		
		return round ( $amount, 2 );
		/*$amount = 0;
		$refund = 0;
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
	    $criteria->select = 'sum(cgst_amt) as cgst_amt';
		$criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
	
		$order_price = $order->cgst_amt;
	
		$criteria3 = new CDbCriteria ();
		$criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		$criteria3->with = 'orderRefund';
		$criteria3->addCondition ( 'orderRefund.order_id ='.$this->order_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->cgst_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
	
	
		return round ( $amount, 2 );*/
	}
	public function getB2BGroupTaxSgstAmount() {
		$taxable = $this->getTotalItemB2bTaxableAmount();
		$sgst_per = $this->sgst_per;
		
		
		
		
		
		$amount = $taxable * $sgst_per/100;
		
		
		return round ( $amount, 2 );
		/*$amount = 0;
		$refund = 0;
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->select = 'sum(sgst_amt) as sgst_amt';
		$criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
	
		$order_price = $order->sgst_amt;
	
		$criteria3 = new CDbCriteria ();
		
		$criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		$criteria3->with = 'orderRefund';
		$criteria3->addCondition ( 'order_id =' . $this->order_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->sgst_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
	
	
		return round ( $amount, 2 );*/
	
	
	
	}
	public function getB2BGroupTaxIgstAmount() {
		$taxable = $this->getTotalItemB2bTaxableAmount();
		$igst_per = $this->igst_per;
		
		
		
		
		
		$amount = $taxable * $igst_per/100;
		
		
		return round ( $amount, 2 );
		/*$amount = 0;
		$refund = 0;
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->select = 'sum(igst_amt) as igst_amt';
		$criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
	
		$order_price = $order->igst_amt;
	
		$criteria3 = new CDbCriteria ();
		
		$criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		$criteria3->with = 'orderRefund';
		$criteria3->addCondition ( 'orderRefund.order_id =' . $this->order_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->igst_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
	
	
		return round ( $amount, 2 );*/
	}
	public function getB2BGroupTaxCessAmount() {
		$taxable = $this->getTotalItemB2bTaxableAmount();
		$cess_per = $this->cess_per;
		
		
		
		
		
		$amount = $taxable * $cess_per/100;
		
		
		return round ( $amount, 2 );
		/*$amount = 0;
		$refund = 0;
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->select = 'sum(cess_amt) as cess_amt';
		$criteria->addCondition ( 'order_id =' . $this->order_id );
		$order = OrderItem::model ()->find ( $criteria );
	
		$order_price = $order->cess_amt;
	
		$criteria3 = new CDbCriteria ();
		$criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		$criteria3->with = 'orderRefund';
		$criteria3->addCondition ( 'orderRefund.order_id =' . $this->order_id );
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
			$refund = 0;
			foreach($orderRefundItems as $orderRefundItem){
				$orderRefund = OrderRefund::model ()->findByPk( $orderRefundItem->order_refund_id );
				if($orderRefund){
					$criteria3 = new CDbCriteria ();
					$criteria3->addCondition ( 'order_id =' . $orderRefund->order_id );
					$criteria3->addCondition ( 'item_detail_id =' . $orderRefundItem->item_detail_id );
					$criteria3->addCondition ( 'item_id =' . $orderRefundItem->item_id );
					$order = OrderItem::model ()->find( $criteria3);
					if($order){
						$ordercgst = $order->cess_amt / $order->qty;
						$refund = $refund + ($ordercgst * $orderRefundItem->qty);
					}
				}
			}
		}
		$amount = $order_price - $refund;
	
	
		return round ( $amount, 2 );*/
	}
	public function getB2BGroupTaxOrderTotalAmount() {
		$amount = 0;
		$oamount = 0;
		$refund = 0;
		$order_ids = array ();
		$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'title = "B2B"' );
			
			$paymentmode = PaymentMode::model ()->find( $criteria1 );
			
		if ($paymentmode) {
			
			
			$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'mode_of_payment =' . $paymentmode->id );
			
			$orders = Order::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->compare ( 'date(create_time)', $this->create_date );
		$criteria->addInCondition('order_id',$order_ids);
		$orders = OrderItem::model ()->findAll( $criteria );
		
		
		
		if($orders){
			foreach($orders as $order){
				$oamount = $oamount + (($order->total_amt));
			}
		}
		//$amount = $oamount;
		
		
	
	
		$criteria3 = new CDbCriteria ();
		
		$criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		$criteria3->with = 'orderRefund';
			$criteria3->compare ( 'date(orderRefund.create_time)', $this->create_date );
		$criteria3->addInCondition('orderRefund.order_id',$order_ids);
		$orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		if($orderRefundItems){
				
			foreach($orderRefundItems as $orderRefundItem){
				$refund = $refund + ((($orderRefundItem->price) * ($orderRefundItem->qty)) + ($orderRefundItem->tax_amt));
					
			}
		}
		$amount = $oamount - $refund;
	
	
		return round ( $amount, 2 );
	}
	
	
}