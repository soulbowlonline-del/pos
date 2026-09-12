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
Yii::import ( 'application.models._base.BaseB2bPurchaseBill' );
class B2bPurchaseBill extends BaseB2bPurchaseBill {
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
	
	 public function getUserwiseColumns($selectcolumns = array())
    {
        if (! empty($selectcolumns)) {
            $selected = $selectcolumns;
        } else {
            $selected = array(
                'username',
                'tax_amount',
                'gross_amount',
                'refund_amount',
                'discount_amount',
                'amount'
            );
        }

        if ($selected) {
            foreach ($selected as $select) {
                if ($select == 'username') {
                    $columns[] = array(
                        'label' => 'Username',
                        'value' => function ($data) {
                            return isset($data->createUser) ? $data->createUser : "";
                        }
                    );
                } else if ($select == 'tax_amount') {
                    $columns[] = array(
                        'label' => 'Taxable Amount',
                        'value' => function ($data) {
                            // return $data->getTotalGrossAmount();
                            return $data->getTotalUserwiseGrossAmount();
                        }
                    );
                } 
				else if ($select == 'gross_amount') {
                    $columns[] = array(
                        'label' => 'Gross Amount',
                        'value' => function ($data) {
                            // return $data->getTotalGrossAmountData();
                            return $data->getTotalB2bUserwiseGrossAmountData();
                        }
                    );
                } 
				else if ($select == 'refund_amount') {
                    $columns[] = array(
                        'label' => 'Refund Amount',
                        'value' => function ($data) {
                            return $data->getUserTotalRefundAmountData();
                        }
                    );
                } 	else if ($select == 'discount_amount') {
                    $columns[] = array(
                        'label' => 'Discount Amount',
                        'value' => function ($data) {
                            return $data->getUserTotalDiscountAmountData();
                        }
                    );
                } 
				else if ($select == 'amount') {
                    $columns[] = array(
                        'label' => 'Net Amount',
                        'value' => function ($data) {
                            // return $data->getTotalNetAmountData();
                            return $data->getTotalUserwiseNetAmountData();
                        }
                    );
                } 
                else {
                    $columns[] = $select;
                }
            }
        }

        return $columns;
    }

	public function getExportColumns($selectcolumns = array())
    {
        if (! empty($selectcolumns)) {
            $selected = $selectcolumns;
        } else {
            $selected = array(
				'bill_no',
				'start_date',
				'customer_id',
				'bill_amount',
                'tax_amount',
               
                // 'tax_amt',
                'outlet'
            );
        }
		
		

        if ($selected) {
            foreach ($selected as $select) {
                if ($select == 'bill_no') {
                    $columns[] = array(
                        'label' => 'Bill No',
                        'value' => function ($data) {
                            return $data->getOrderBillNo();
                        }
                    );
                }
                // else if ($select == 'customer_id') {
                    // $columns[] = array(
                        // 'label' => 'Customer',
                        // 'value' => function ($data) {
                            // return isset($data->customer) ? $data->customer : "";
                        // }
                    // );
                // }
				
				elseif ($select == 'customer_id') {
                    $columns[] = array(
                        'label' => 'Employee',
                        'value' => function ($data) {
                            return isset($data->createUser)?$data->createUser:"";
                        }
                    );
                }
				 elseif ($select == 'bill_amount') {
                    $columns[] = array(
                        'label' => 'Total amount',
                        'value' => function ($data) {
                            return  $data->bill_amount;
                        }
                    );
                }
                else if ($select == 'mode_of_payment') {
                    $columns[] = array(
                        'label' => 'Mode Of Payment',
                        'value' => function ($data) {
                            return isset($data->modePayment) ? $data->modePayment : "";
                        }
                    );
                } else if ($select == 'employee_id') {
                    $columns[] = array(
                        'label' => 'Employee',
                        'value' => function ($data) {
                            return isset($data->createUser) ? $data->createUser : "";
                        }
                    );
                } 
                else if ($select == 'outlet') {
                    $columns[] = array(
                        'label' => 'Outlet',
                        'value' => function ($data) {
                            return isset($data->outlet) ? $data->outlet : "";
                        }
                    );
                } else if ($select == 'tax_amt') {
                    $columns[] = array(
                        'label' => 'Tax Amount',
                        'value' => function ($data) {
                            return $data->getOrderTaxAmount();
                        }
                    );
                } else if ($select == 'refund_amt') {
                    $columns[] = array(
                        'label' => 'Refund Amount',
                        'value' => function ($data) {
                            return $data->getOrderRefundAmount();
                        }
                    );
                } else if ($select == 'refund_by') {
                    $columns[] = array(
                        'label' => 'Refund By',
                        'value' => function ($data) {
                            return $data->getOrderRefundBy();
                        }
                    );
                } 
                else if ($select == 'total_amt') {
                    $columns[] = array(
                        'label' => 'Total Amount',
                        'value' => function ($data) {
                            return $data->getOrderTotalAmount();
                        }
                    );
                } else {
                    $columns[] = $select;
                }
            }
        }

        /*
         * $columns[] =
         *
         * array (
         *
         * 'bill_no',
         * 'bill_date',
         * array (
         * 'label' => 'Customer',
         * 'value' => function ($data) {
         * return isset ( $data->customer ) ? $data->customer : "";
         * }
         * ),
         *
         * 'total_amt',
         * 'discount_amt',
         * 'paid_amt',
         * array (
         * 'label' => 'Mode Of Payment',
         * 'value' => function ($data) {
         * return Order::getPaymentTypeOptions ( $data->mode_of_payment );
         * }
         * ),
         * array (
         * 'label' => 'Mode Of Delivery',
         * 'value' => function ($data) {
         * return Order::getDeliveryTypeOptions ( $data->mode_of_delivery );
         * }
         * ),
         * array (
         * 'label' => 'Order Type',
         * 'value' => function ($data) {
         * return Order::getTypeOptions ( $data->type_id );
         * }
         * ),
         *
         *
         * array (
         * 'label' => 'Outlet',
         * 'value' => function ($data) {
         * return isset ( $data->outlet ) ? $data->outlet : "";
         * }
         * )
         * )
         */

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
	    
	    $url = 'http://generic.poc.webappline.com/api.php';
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, "$url" );
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
	
	
	public function getTotalUserwiseGrossAmount()
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria1->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(gross_amt) as gross_amt';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $criteria1->addCondition('status =1');
        //echo "<pre>" ;print_r($criteria1);
        $orderitem = B2bPurchaseBill::model()->find($criteria1);
        //$orderitem->getRawSql();
        //print_r($orderitem);
        $order_amt = $orderitem->gross_amt;

        $criteria2 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria2->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria2->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria2->select = 'sum(price) as price';
        $criteria2->addCondition('create_user_id =' . $this->create_user_id);
        $orderrefunditem = OrderRefundItem::model()->find($criteria2);
        $order_refund_amt = $orderrefunditem->price;

        $total = $order_amt ;

        
        return $total;
    }
	public function getTotalGrossAmount()
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria1->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(price * approved_qty) as price';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $orderitem = B2bPurchaseBillDetail::model()->find($criteria1);
        $order_amt = $orderitem->price;

        $criteria2 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria2->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria2->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria2->select = 'sum(price) as price';
        $criteria2->addCondition('create_user_id =' . $this->create_user_id);
        $orderrefunditem = OrderRefundItem::model()->find($criteria2);
        $order_refund_amt = $orderrefunditem->price;

        $total = $order_amt ;

        /*
         * if($orderitems){
         *
         * foreach ($orderitems as $orderitem){
         * $qty = $orderitem->qty;
         * $refund = 0;
         * $criteria = new CDbCriteria();
         * $criteria->addCondition('order_id ='.$orderitem->order_id);
         * if((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')){
         * $criteria->addBetweenCondition('date(create_time)',Yii::app()->session['start_date'], Yii::app()->session['end_date']);
         * }
         * $orderRefund = OrderRefund::model()->find($criteria);
         * if($orderRefund){
         * $criteria3 = new CDbCriteria();
         * $criteria3->addCondition('order_refund_id ='.$orderRefund->id);
         * $criteria3->addCondition('item_detail_id ='.$orderitem->item_detail_id);
         * if((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')){
         * $criteria3->addBetweenCondition('date(create_time)',Yii::app()->session['start_date'], Yii::app()->session['end_date']);
         * }
         * $criteria3->select = 'sum(total_amt) as total_amt,sum(tax_amt) as tax_amt';
         * $criteria3->addCondition('item_id ='.$orderitem->item_id);
         * $orderRefundItem = OrderRefundItem::model()->find($criteria3);
         *
         * $refund = $orderRefundItem->total_amt - $orderRefundItem->tax_amt;
         * }
         * $amt = (($orderitem->total_amt)-($orderitem->tax_amount))- ($refund);
         * $total = $total + $amt;
         * }
         * }
         */
        return $total;
    }
	
	 public function getTotalGrossAmountData()
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria1->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(price*qty) as price,sum(tax_amount) as tax_amount ,sum(discount_amt) as discount_amt';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);

        $orderitem = OrderItem::model()->find($criteria1);
        $order_amt = $orderitem->price + $orderitem->tax_amount + $orderitem->discount_amt;

        return round($order_amt);
    }
	
	
	public function getTotalB2bUserwiseGrossAmountData()
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria1->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(gross_amt) as gross_amt , sum(tax_amount) as tax_amount ';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $criteria1->addCondition('status =1');
        $orderitem = B2bPurchaseBill::model()->find($criteria1);
		
	
        $order_amt = $orderitem->gross_amt + $orderitem->tax_amount ;

        $total = $order_amt ;
		 return round($total);
	}
	
	
	public function getTotalB2bGrossAmountData()
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria1->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(price * approved_qty) as price , cgst_amt ,sgst_amt,igst_amt,cess_amt';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $orderitem = B2bPurchaseBillDetail::model()->find($criteria1);
		
	
        $order_amt = $orderitem->price + $orderitem->cgst_amt + $orderitem->sgst_amt + $orderitem->igst_amt + $orderitem->cess_amt;

        $total = $order_amt ;
		 return round($total);
	}
	 public function getUserTotalRefundAmountData()
    {
        $total = 0;

        $criteria2 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria2->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria2->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria2->select = 'sum(price*qty) as price,sum(tax_amt) as tax_amt';
        $criteria2->addCondition('create_user_id =' . $this->create_user_id);
        $orderrefunditem = OrderRefundItem::model()->find($criteria2);
        $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt;

        $total = $order_refund_amt;

        return round($total);
    }

    public function getUserTotalDiscountAmountData()
    {
        $total = 0;

        $criteria1 = new CDbCriteria();

        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(total_discount) as total_discount';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $discountorder = B2bPurchaseBill::model()->find($criteria1);

        $total = $discountorder->total_discount;

        return round($total);
    }

    

    public function getValTotalNetAmount($start_date, $end_date, $item_id)
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((! empty($item_id))) {
            $criteria1->addInCondition('item_id', $item_id);
        }
        if (($start_date != '') && ($end_date != '')) {
            $criteria1->addBetweenCondition('date(create_time)', $start_date, $end_date);
        }
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $orderitems = B2bPurchaseBillDetail::model()->findAll($criteria1);

        if ($orderitems) {

            foreach ($orderitems as $orderitem) {
              
                $refund = 0;
                $criteria = new CDbCriteria();
                $criteria->addCondition('order_id =' . $orderitem->order_id);
                if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
                    $criteria->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
                }
                $orderRefund = OrderRefund::model()->find($criteria);
                if ($orderRefund) {
                    $criteria3 = new CDbCriteria();
                    $criteria3->addCondition('order_refund_id =' . $orderRefund->id);
                    $criteria3->addCondition('item_detail_id =' . $orderitem->item_detail_id);
                    if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
                        $criteria3->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
                    }
                    $criteria3->addCondition('item_id =' . $orderitem->item_id);
                    $orderRefundItems = OrderRefundItem::model()->findAll($criteria3);
                    if ($orderRefundItems) {

                        foreach ($orderRefundItems as $orderRefundItem) {
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
        }
        return round($total);
    }
	public function getTotalUserwiseNetAmountData()
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria1->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(bill_amount) as bill_amount';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $criteria1->addCondition('status =1');
        $orderitem = B2bPurchaseBill::model()->find($criteria1);
        $order_amt = $orderitem->bill_amount ;

        $criteria2 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria2->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria2->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria2->select = 'sum(price) as price,sum(tax_amt) as tax_amt';
        $criteria2->addCondition('create_user_id =' . $this->create_user_id);
        $orderrefunditem = OrderRefundItem::model()->find($criteria2);
        $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt;
        $total = $order_amt ;
        Yii::log(CVarDumper::dumpAsString($order_amt), CLogger::LEVEL_WARNING, '$order_amt');
        Yii::log(CVarDumper::dumpAsString($this->create_user_id), CLogger::LEVEL_WARNING, '$$this->create_user_id');
        Yii::log(CVarDumper::dumpAsString($order_refund_amt), CLogger::LEVEL_WARNING, '$$order_refund_amt');

        $criteria1 = new CDbCriteria();

        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(discount_amt) as discount_amt';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $discountorder = Order::model()->find($criteria1);

        return round($total);
    }
	public function getTotalNetAmountData()
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria1->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(amount) as amount';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $orderitem = B2bPurchaseBillDetail::model()->find($criteria1);
        $order_amt = $orderitem->amount ;

        $criteria2 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria2->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria2->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria2->select = 'sum(price) as price,sum(tax_amt) as tax_amt';
        $criteria2->addCondition('create_user_id =' . $this->create_user_id);
        $orderrefunditem = OrderRefundItem::model()->find($criteria2);
        $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt;
        $total = $order_amt ;
        Yii::log(CVarDumper::dumpAsString($order_amt), CLogger::LEVEL_WARNING, '$order_amt');
        Yii::log(CVarDumper::dumpAsString($this->create_user_id), CLogger::LEVEL_WARNING, '$$this->create_user_id');
        Yii::log(CVarDumper::dumpAsString($order_refund_amt), CLogger::LEVEL_WARNING, '$$order_refund_amt');

        $criteria1 = new CDbCriteria();

        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(discount_amt) as discount_amt';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $discountorder = Order::model()->find($criteria1);

        // $total = $total - $discountorder->discount_amt;

        // Yii::log ( CVarDumper::dumpAsString ($orderitems), CLogger::LEVEL_WARNING, '$orderitems' );
        /*
         * if($orderitems){
         *
         * foreach ($orderitems as $orderitem){
         * $qty = $orderitem->qty;
         *
         * $refund = 0;
         * $criteria = new CDbCriteria();
         * $criteria->addCondition('order_id ='.$orderitem->order_id);
         * if((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')){
         * $criteria->addBetweenCondition('date(create_time)',Yii::app()->session['start_date'], Yii::app()->session['end_date']);
         * }
         * $orderRefund = OrderRefund::model()->find($criteria);
         * if($orderRefund){
         * $criteria3 = new CDbCriteria();
         * $criteria3->addCondition('order_refund_id ='.$orderRefund->id);
         * $criteria3->addCondition('item_detail_id ='.$orderitem->item_detail_id);
         * if((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')){
         * $criteria3->addBetweenCondition('date(create_time)',Yii::app()->session['start_date'], Yii::app()->session['end_date']);
         * }
         * $criteria3->addCondition('item_id ='.$orderitem->item_id);
         * $criteria3->select = 'sum(total_amt) as total_amt';
         * $orderRefundItem = OrderRefundItem::model()->find($criteria3);
         * $refund = $orderRefundItem->total_amt;
         *
         * }
         * $amt = ($orderitem->total_amt) - ($refund);
         * $total = $total + $amt;
         * /* Yii::log ( CVarDumper::dumpAsString ($orderitem->id), CLogger::LEVEL_WARNING, '$order_item_id' );
         * Yii::log ( CVarDumper::dumpAsString ($amt), CLogger::LEVEL_WARNING, '$order_amt' );
         * Yii::log ( CVarDumper::dumpAsString ($total), CLogger::LEVEL_WARNING, '$order_total' );
         * }
         * }
         */
        return round($total);
    }
	
	public function getOrderBillNo()
    {
        $bill_prefix = 'B';
        $billno = $this->id;
        $month = date('m', strtotime($this->start_date));
        if ($month > 3) {
            $year = date('Y', strtotime($this->start_date));
			$year= substr($year, -2);
            $yearlast = $year + 1;
			$yearlast= substr($yearlast, -2);
        } else {
            $year = date('Y', strtotime($this->start_date));
            $year = $year - 1;
				$year= substr($year, -2);
            $yearlast = date('Y', strtotime($this->start_date));
			$yearlast= substr($yearlast, -2);
        }

        $billyear = date('Y', strtotime($this->start_date));
        $newyear = $billyear + 1;
        $outlet = Outlet::model()->findByPk($this->outlet_id);
        if ($outlet) {
            if ($outlet->bill_prefix == '') {
                $bill_prefix = $outlet->bill_prefix;
            } else {
                $bill_prefix = 'B';
            }
        }
        $billno = 'B2B ' . $year . '-' . $yearlast . '/' . $bill_prefix . '-' . $billno;
        return $billno;
    }

}