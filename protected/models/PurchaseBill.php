<?php

/**
 * @property integer $id
 * @property string $code
 * @property string $start_date
 * @property string $end_date
 * @property string $receiving_date
 * @property integer $status
 * @property integer $type_id
 * @property integer $is_open_po
 * @property integer $is_po_received
 * @property string $remarks
 * @property string $payment_terms
 * @property string $transport_mode
 * @property double $purchase_order_amount
 * @property double $charges_total_amount
 * @property double $discount_amount
 * @property double $frieght_charges
 * @property double $extra_charges
 * @property double $total_amount
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 * @property integer $outlet_id
 * @property integer $vendor_id
 * @property integer $purchase_order_id
 * @property integer $organization_id
 */
Yii::import ( 'application.models._base.BasePurchaseBill' );
class PurchaseBill extends BasePurchaseBill {
	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}
	public function getPOVendorOptions(){
		$list = [];
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.PurchaseBill::STATUS_UNAPPROVED);
		$mrss = PurchaseBill::model()->findAll($criteria);
		if($mrss){
			foreach($mrss as $mrs){
				$vendor = Vendor::model()->findByPk($mrs->vendor_id);
				if($vendor){
					$list[$vendor->id] = $vendor->name;
				}
			}
		}
		return $list;
	}
	public function getVendorEmail() {
		$email = '';
		if ($this->vendor) {
			$user = User::model ()->findByPk ( $this->vendor->create_user_id );
			if ($user) {
				$email = $user->email;
			}
		}
		return $email;
	}
	public function isSold() {
	}
	public function getColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'transaction_type',
					'ben_code',
					'ben_acc_no',
					'instrument_amt',
					'ben_name',
					'drawee_loc',
					'print_loc',
					'ben_add1',
					'ben_add2',
					'ben_add3',
					'ben_add4',
					'ben_add5',
					'Inst_ref_no',
					'customer_refrence_no',
					'pay_detail1',
					'pay_detail2',
					'pay_detail3',
					'pay_detail4',
					'pay_detail5',
					'pay_detail6',
					'pay_detail7',
					'cheque_no',
					'chq' ,
					'micr_no' ,
					'ifsc_code' ,
					'bene_bank_name',
					'bene_branch_name' ,
					'beneficiary_email',
					/* 'transaction_type',
					'ben_code',
					'ben_name',
					'instrument_amt',
					'cheque_no',
					'chq',
					'customer_refrence_no',
					'pay_detail1',
					'pay_detail2',
					'ben_acc_no',
					'Inst_ref_no',
					'transaction_status',
					'reject_reason',
					'ifsc_code',
					'micr_no',
					'utr_no', */
					
					
					/* 'drawee_loc',
					'print_loc',
					'ben_add1',
					'ben_add2',
					'ben_add3',
					'ben_add4',
					'ben_add5',
					'Inst_ref_no',
					
					
					'pay_detail3',
					'pay_detail4',
					'pay_detail5',
					'pay_detail6',
					'pay_detail7',
					
					
					
					
					'bene_bank_name',
					'bene_branch_name',
					'beneficiary_email' */ 
			)
			;
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				if ($select == 'transaction_type') {
					$columns [] = array (
							'label' => 'Transaction Type (N – NFET, R – RTGS)',
							'value' => function ($data) {
								return "N";
							} 
					);
				} else if ($select == 'ben_code') {
					$columns [] = array (
							'label' => 'Beneficiary Code',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'ben_acc_no') {
					$columns [] = array (
							'label' => 'Beneficiary Account Number',
							'value' => function ($data) {
								return isset ( $data->vendor ) ? "'".$data->vendor->acc_no ."'": "";
							} 
					);
				} else if ($select == 'instrument_amt') {
					$columns [] = array (
							'label' => 'Instrument Amount',
							'value' => function ($data) {
								return isset ( $data->net_bill_amount ) ? $data->net_bill_amount : "";
							} 
					);
				} else if ($select == 'ben_name') {
					$columns [] = array (
							'label' => 'Beneficiary Name (Upto 40 character withput any special character)',
							'value' => function ($data) {
								
								return isset ( $data->vendor ) ? $data->clean ( $data->vendor->name ) : "";
							} 
					);
				} else if ($select == 'drawee_loc') {
					$columns [] = array (
							'label' => 'Drawee Location',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'print_loc') {
					$columns [] = array (
							'label' => 'Print Location',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'ben_add1') {
					$columns [] = array (
							'label' => 'Bene Address 1',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'ben_add2') {
					$columns [] = array (
							'label' => 'Bene Address 2',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'ben_add3') {
					$columns [] = array (
							'label' => 'Bene Address 3',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'ben_add4') {
					$columns [] = array (
							'label' => 'Bene Address 4',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'ben_add5') {
					$columns [] = array (
							'label' => 'Bene Address 5',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'Inst_ref_no') {
					$columns [] = array (
							'label' => 'Bank Reference Number',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'customer_refrence_no') {
					$columns [] = array (
							'label' => 'Customer Reference Number(Any alpha numeric character upto 20)',
							'value' => function ($data) {
								return isset ( $data->bill_no ) ? "'".$data->bill_no."'": "";
							} 
					);
				} else if ($select == 'pay_detail1') {
					$columns [] = array (
							'label' => 'Payment details 1',
							'value' => function ($data) {
							return isset ( $data->purchaseBill ) ? 'Gr-'.$data->purchaseBill->grn_refrence_no: "";
							} 
					);
				} else if ($select == 'pay_detail2') {
					$columns [] = array (
							'label' => 'Payment details 2',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'pay_detail3') {
					$columns [] = array (
							'label' => 'Payment details 3',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'pay_detail4') {
					$columns [] = array (
							'label' => 'Payment details 4',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'pay_detail5') {
					$columns [] = array (
							'label' => 'Payment details 5',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'pay_detail6') {
					$columns [] = array (
							'label' => 'Payment details 6',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'pay_detail7') {
					$columns [] = array (
							'label' => 'Payment details 7',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'cheque_no') {
					$columns [] = array (
							'label' => 'Cheque Number',
							'value' => function ($data) {
								return '';
							} 
					);
				} else if ($select == 'chq') {
					$columns [] = array (
							'label' => 'Chq / Trn Date (DD/MM/YYYY)',
							'value' => function ($data) {
								return isset ( $data->start_date ) ? $data->getChqDate () : "";
							} 
					);
				} else if ($select == 'micr_no') {
					$columns [] = array (
							'label' => 'Micr Code',
							'value' => function ($data) {
								return '';
							} 
					);
				} 
				else if ($select == 'transaction_status') {
					$columns [] = array (
							'label' => 'Transaction Status',
							'value' => function ($data) {
							return '';
							}
							);
				}
				else if ($select == 'reject_reason') {
					$columns [] = array (
							'label' => 'Reject Reason',
							'value' => function ($data) {
							return '';
							}
							);
				}
				else if ($select == 'utr_no') {
					$columns [] = array (
							'label' => 'UTR no for RTGS',
							'value' => function ($data) {
							return '';
							}
							);
				}else if ($select == 'ifsc_code') {
					$columns [] = array (
							'label' => 'IFSC Code',
							'value' => function ($data) {
								return isset ( $data->vendor ) ? $data->vendor->ifsc : "";
							} 
					);
				} else if ($select == 'bene_bank_name') {
					$columns [] = array (
							'label' => 'Bene Bank Name',
							'value' => function ($data) {
								return isset ( $data->vendor ) ? $data->vendor->bank_name : "";
							} 
					);
				} else if ($select == 'bene_branch_name') {
					$columns [] = array (
							'label' => 'Bene Bank Branch Name',
							'value' => function ($data) {
								return isset ( $data->vendor ) ? $data->vendor->bank_name : "";
							} 
					);
				} else if ($select == 'beneficiary_email') {
					$columns [] = array (
							'label' => 'Beneficiary email id',
							'value' => function ($data) {
								return $data->getVendorEmail ();
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
	public function clean($string) {
		$string = str_replace ( ' ', '', $string ); // Replaces all spaces with hyphens.
		
		return preg_replace ( '/[^A-Za-z0-9\-]/', '', $string ); // Removes special chars.
	}
	public function getBillDiscountAmount() {
		$discount = 0;
		$purchaseBilldetails = PurchaseBillDetail::model ()->findAllByAttributes ( array (
				'purchase_bill_id' => $this->id 
		) );
		if ($purchaseBilldetails) {
			foreach ( $purchaseBilldetails as $purchaseBilldetail ) {
				$discount = $discount + $purchaseBilldetail->discount_amt;
			}
		}
		return $discount;
	}
	public function getTotalAmount() {
		$discount = 0;
		$purchaseBilldetails = PurchaseBillDetail::model ()->findAllByAttributes ( array (
				'purchase_bill_id' => $this->id 
		) );
		if ($purchaseBilldetails) {
			foreach ( $purchaseBilldetails as $purchaseBilldetail ) {
				$discount = $discount + $purchaseBilldetail->amount;
			}
		}
		return $discount;
	}
	public function getChqDate() {
		
		$chq_date = '';
		$set = false;
		if ($this->is_consignment != PurchaseBill::IS_CONSIGNMENT) {
			$set = true;
		} else {
			if (($this->is_consignment == PurchaseBill::IS_CONSIGNMENT) && ($this->is_consignment_checked == PurchaseBill::IS_CONSIGNMENT_CHECK)) {
				$set = true;
			}
		}
		if ($set == true) {
			$Date = $this->start_date;
			$days = $this->payment_days;
			if ($days != 0) {
				$chq_date = date ( 'Y-m-d', strtotime ( $Date . ' + ' . $days . ' days' ) );
			} else {
				$chq_date = $this->start_date;
			}
		}
		
		return $chq_date;
	}
	public function getAdvancePaymentValue() {
		$vendor_id = $this->vendor_id;
		$amount = $this->net_bill_amount;
		$vendor = Vendor::model ()->findByPk ( $vendor_id );
		if ($vendor) {
			
		
			if ($vendor->is_advance_payment == Vendor::ADVANCE_PAYMENT) {
				$advancePayment = AdvancePayment::model ()->findByAttributes ( array (
						'vendor_id' => $vendor_id 
				) );
				
				if ($advancePayment) {
					if (($advancePayment->balance_amt >= $amount)) {
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	public function getConsignmentOptions() {
		$model = new PurchaseBill ();
		$model->getConsignmentData ();
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'is_consignment =' . PurchaseBill::IS_CONSIGNMENT );
		$criteria->addCondition ( 'is_consignment_checked !=' . PurchaseBill::IS_CONSIGNMENT_CHECK );
		$criteria->addCondition ( 'status =' . PurchaseBill::STATUS_APPROVED );
		$purchaseBills = PurchaseBill::model ()->findAll ( $criteria );
		
		Yii::log ( CVarDumper::dumpAsString ( $purchaseBills ), CLogger::LEVEL_WARNING, '$purchaseBills' );
		if ($purchaseBills) {
			foreach ( $purchaseBills as $purchaseBill ) {
				
				$criteria1 = new CDbCriteria ();
				$criteria1->addCondition ( 'is_consignment_checked !=' . PurchaseBill::IS_CONSIGNMENT_CHECK );
				$criteria1->addCondition ( 'purchase_bill_id =' . $purchaseBill->id );
				$purchaseBillDetails = PurchaseBillDetail::model ()->findAll ( $criteria1 );
				
				Yii::log ( CVarDumper::dumpAsString ( $purchaseBillDetails ), CLogger::LEVEL_WARNING, '$purchaseBillDetails' );
				if ($purchaseBillDetails) {
					foreach ( $purchaseBillDetails as $purchaseBillDetail ) {
						$date = $purchaseBill->end_date;
						$criteria2 = new CDbCriteria ();
						$criteria2->addCondition ( 'item_id =' . $purchaseBillDetail->item_id );
						$criteria2->addCondition ( 'date(create_time) >=' . "'" . $date . "'" );
						$criteria2->addCondition ( 'item_detail_id =' . $purchaseBillDetail->item_detail_id );
						$orderitems = OrderItem::model ()->findAll ( $criteria2 );
						
						Yii::log ( CVarDumper::dumpAsString ( $orderitems ), CLogger::LEVEL_WARNING, '$orderitems' );
						if ($orderitems) {
							$qty = 0;
							foreach ( $orderitems as $orderitem ) {
								$addqty = $orderitem->qty;
								$orderrefund = OrderRefund::model ()->findByAttributes ( array (
										'order_id' => $orderitem->order_id 
								) );
								if ($orderrefund) {
									
									$criteria3 = new CDbCriteria ();
									$criteria3->addCondition ( 'order_refund_id =' . $orderrefund->id );
									$criteria2->addCondition ( 'item_id =' . $orderitem->item_id );
									$criteria3->addCondition ( 'item_detail_id =' . $orderitem->item_detail_id );
									$orderrefunditems = OrderRefundItem::model ()->findAll ( $criteria3 );
									Yii::log ( CVarDumper::dumpAsString ( $orderrefunditems ), CLogger::LEVEL_WARNING, '$orderrefunditems' );
									if ($orderrefunditems) {
										$refundqty = 0;
										foreach ( $orderrefunditems as $orderrefunditem ) {
											$refundqty = $refundqty + $orderrefunditem->qty;
										}
										if ($refundqty < $addqty) {
											$addqty = $addqty - $refundqty;
										} else {
											$addqty = 0;
										}
									}
								}
								$qty = $qty + $addqty;
							}
							
							Yii::log ( CVarDumper::dumpAsString ( $addqty ), CLogger::LEVEL_WARNING, '$addqty' );
							
							if ($qty > $purchaseBillDetail->approved_qty || $qty = $purchaseBillDetail->approved_qty) {
								$purchaseBillDetail->is_consignment_checked = PurchaseBill::IS_CONSIGNMENT_CHECK;
								$purchaseBillDetail->saveAttributes ( array (
										'is_consignment_checked' 
								) );
							}
						}
					}
				} else {
					$purchaseBill->is_consignment_checked = PurchaseBill::IS_CONSIGNMENT_CHECK;
					$purchaseBill->start_date = date ( 'Y-m-d' );
					$purchaseBill->saveAttributes ( array (
							'is_consignment_checked',
							'start_date' 
					) );
				}
			}
		}
	}
	public function getPurchasePrintDetails() {
		$bill_detail_ids = array ();
		$list = array ();
		if (isset ( Yii::app ()->session ['billidList'] ) && (Yii::app ()->session ['billidList'] != '')) {
			$criteria = new CDbCriteria ();
			$criteria->addInCondition ( 'id', Yii::app ()->session ['billidList'] );
			$purchasebilldetails = PurchaseBillDetail::model ()->findAll ( $criteria );
			if ($purchasebilldetails) {
				foreach ( $purchasebilldetails as $purchasebilldetail ) {
					$list [$purchasebilldetail->id] = isset ( $purchasebilldetail->item ) ? $purchasebilldetail->item : "";
				}
			}
		}
		return $list;
	}
	public function getConsignmentData() {
		$ch = curl_init ();
		
		curl_setopt ( $ch, CURLOPT_URL, "http://poslicense.webappline.com/api.php" );
		
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		
		$server_output = curl_exec ( $ch );
		
		curl_close ( $ch );
		
		$setting = Setting::model ()->find ();
		if ($setting == null) {
			$setting = new Setting ();
		}
		if ($server_output == 1) {
			$setting->check_val = Setting::SETTING_YES;
		} else if ($server_output == 0) {
			$setting->check_val = Setting::SETTING_NO;
		}
		$setting->create_time = date ( 'Y-m-d H:i:s' );
		$setting->save ();
	}
}