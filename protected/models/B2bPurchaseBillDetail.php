<?php

/**
 * @property integer $id
 * @property integer $req_qty
 * @property integer $bal_qty
 * @property integer $approved_qty
 * @property double $mrp
 * @property double $price
 * @property double $discount
 * @property double $discount_amt
 * @property double $vat
 * @property double $other_charge
 * @property double $amount
 * @property double $sale_rate
 * @property integer $status
 * @property integer $type_id
 * @property double $charge_amount
 * @property double $extra_charges
 * @property string $remarks
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 * @property integer $item_detail_id
 * @property integer $purchase_bill_id
 * @property integer $outlet_id
 */
Yii::import('application.models._base.BaseB2bPurchaseBillDetail');

class B2bPurchaseBillDetail extends BaseB2bPurchaseBillDetail
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function getPBillVendorOptions()
    {
        $list = [];
        $criteria = new CDbCriteria();
        $criteria->addCondition('status !=' . PurchaseBill::STATUS_APPROVED);
        $mrss = PurchaseBill::model()->findAll($criteria);
        Yii::log(CVarDumper::dumpAsString($mrss), CLogger::LEVEL_WARNING, '$mrss');
        if ($mrss) {
            foreach ($mrss as $mrs) {
                $create_time = date('d-m-Y', strtotime($mrs->create_time));
                $vendor = Vendor::model()->findByPk($mrs->vendor_id);
                if ($vendor) {
                    // $list[$vendor->id] = $vendor->name.'('.$create_time.')';
                    $list[$vendor->id] = $vendor->name;
                }
            }
        }
			$vendor = Vendor::model()->findAll();
		  
            if ($vendor) {
				foreach ($vendor as $_vendor) {
                    // $list[$vendor->id] = $vendor->name.'('.$create_time.')';
                    $list[$_vendor->id] = $_vendor->name;
                }
            }
		
        asort($list);
        return $list;
    }

    public function getAllTaxOptions($id = null)
    {
        $list = array();
        $taxes = Tax::model()->findAllByAttributes(array(
            'status' => Tax::STATUS_ACTIVE
        ));
        if ($taxes) {
            foreach ($taxes as $tax) {
                $list[$tax->id] = $tax->title;
            }
        }
        return $list;
        if ($id == null)
            return $list;
        if (is_numeric($id))
            return $list[$id];
        return $id;
    }

    public function toArray()
    {
        $model = $this;
        $json_entry = null;
        if ($model) {
            $default_img = 'default.png';
            $json_entry = array();
            $json_entry['id'] = $model->id;
            $json_entry['item'] = isset($model->item) ? $model->item->title : '';
            $json_entry['bar_code'] = isset($model->itemDetail) ? $model->itemDetail->bar_code : '';
            $json_entry['req_qty'] = isset($model->req_qty) ? $model->req_qty : '';
            $json_entry['mrp'] = isset($model->itemDetail) ? $model->itemDetail->getItemDetailMrp() : '';
            $json_entry['approved_qty'] = isset($model->approved_qty) ? $model->approved_qty : '';
            $json_entry['rec_qty'] = 0;
        }
        return $json_entry;
    }

    public function getPOBillOptions($id = null)
    {
        $list = array();
        $user = Yii::app()->user->model;
        // $user = User::model()->findByPk($id);
        if ($user) {
            $role_id = $user->role_id;
            $role = UserRole::model()->findByAttributes(array(
                'title' => 'Vendor'
            ));

            if ($id != null) {
                if ($role_id == $role->id) {
                    $criteria = new CDbCriteria();
                    $criteria->addCondition('vendor_id =' . $id);
                    $criteria->addCondition('status !=' . PurchaseBill::STATUS_APPROVED);
                    $polist = PurchaseBill::model()->findAll($criteria);
                } else {
                    $criteria = new CDbCriteria();

                    $criteria->addCondition('status !=' . PurchaseBill::STATUS_APPROVED);
                    $polist = PurchaseBill::model()->findAll($criteria);
                }
                if ($polist) {
                    foreach ($polist as $po) {
                        $list[$po->id] = $po->id;
                    }
                }
            }
        }
        return $list;
    }

    public function getAllPOBillOptions($id = null , $v =null)
    {
        $list = array();
        $user = Yii::app()->user->model;
        if ($user) {
            $role_id = $user->role_id;

            $role = UserRole::model()->findByAttributes(array(
                'title' => 'Vendor'
            ));

            if ($id != null) {
                if ($role_id == $role->id) {
                    $criteria = new CDbCriteria();
                    $criteria->addCondition('vendor_id =' . $id);
                    $criteria->addCondition('status !=' . B2bPurchaseBill::STATUS_APPROVED);
                    $polist = B2bPurchaseBill::model()->findAll($criteria);
                } else {
                    $criteria = new CDbCriteria();
				if(!empty($v)){
					 $criteria->addCondition('vendor_id =' . $v);
				}
                    $criteria->addCondition('status !=' . B2bPurchaseBill::STATUS_APPROVED);
                    $polist = B2bPurchaseBill::model()->findAll($criteria);
                }
                if ($polist) {
                    foreach ($polist as $po) {
                        $list[] = $po->id;
                    }
                }
            }
        }
        return $list;
    }

    public function getVendorTAXNO()
    {
        $tax_no = '';
        if ($this->purchaseBill) {
            if ($this->purchaseBill->vendor) {
                $tax_no = $this->purchaseBill->vendor->tax_no;
            }
        }
        return $tax_no;
    }
	
	public function getUnitName()
    {
		
		 $details = Item::model()->findByPk(array(
            'id' => $this->item_id
            // 'tax_id' => $this->tax_id
        ));
		$unit=$details->unit;
        if($unit=='0'){
			$unit="PCS-PIECES";
		}elseif($unit=='1'){	
		$unit="Box";
		}
		elseif($unit=='2'){
		$unit="Case";
			}
		elseif($unit=='3'){	
		$unit="KGS-KILOGRAMS";
		}
		elseif($unit=='4'){
		$unit="ML";
			}
		elseif($unit=='5'){
			$unit="NOS";
			}
		elseif($unit=='6'){	
		$unit="PCS";
		}
		elseif($unit=='7'){
		$unit="PETI";
		}
		elseif($unit=='8'){
		$unit="TIN";
		}
        return $unit;
    }
	
	public function getSalesColumns($selectcolumns = array())
    {
        if (! empty($selectcolumns)) {
            $selected = $selectcolumns;
        } else {

            $selected = array(
                // 'date',
                'pin_code',
                'bill_no',
                'state',
                'state_code',
                'place',
               'tax_no',
                'vendor',
                'item_name',
                'hsn_code',
                'qty',
                'unit',
				 'basic_value',
				 'taxable',
                'gst_per',
                'cgst_per',
                'sgst_per',
                'igst_per',
                'cess_per',
                'cgst_amt',
                'sgst_amt',
                'igst_amt',
                'cess_amt',
                'invoice_amt'
            );
        }

        if ($selected) {
            foreach ($selected as $select) {
                if ($select == 'date') {
                    $columns[] = array(
                        'label' => 'Date',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->end_date : "";
                        }
                    );
					
					} else if ($select == 'state_code') {
                    $columns[] = array(
                        'label' => 'State code',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ?  substr($data->purchaseBill->vendor->tax_no, 0, 2) : "";
                        }
                    );
					} else if ($select == 'place') {
                    $columns[] = array(
                        'label' => 'Place',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->vendor->primary_address : "";
                        }
                    );
                } else if ($select == 'vendor') {
                    $columns[] = array(
                        'label' => 'Vendor',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->vendor : "";
                        }
                    );
					} else if ($select == 'item_name') {
                    $columns[] = array(
                        'label' => 'Item Name',
                        'value' => function ($data) {
  return isset($data->purchaseBill) ? $data->getItemName() : "";                          
						 
                        }
                    );
					} else if ($select == 'unit') {
                    $columns[] = array(
                        'label' => 'Unit',
                        'value' => function ($data) {
  return $data->getUnitName();                          
						 
                        }
                    );
                } else if ($select == 'hsn_code') {
                    $columns[] = array(
                        'label' => 'HSN Code',
                        'value' => function ($data) {
                            return isset($data->hsn_code) ? $data->hsn_code : "";
                        }
                    );  
					} else if ($select == 'state') {
                    $columns[] = array(
                        'label' => 'State',
                        'value' => function ($data) {
						
						  return isset($data->purchaseBill) ? $data->getStateName($data->purchaseBill->vendor->state_id) : "";
                           
                        }
                    );  
					} else if ($select == 'qty') {
                    $columns[] = array(
                        'label' => 'Qty',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->approved_qty : "";
                        }
                    );
                } else if ($select == 'bill_no') {
                    $columns[] = array(
                        'label' => 'Bill No',
                        'value' => function ($data) {
                            return $data->getOrderBillNo();
                        }
                    );
                } else if ($select == 'tax_no') {
                    $columns[] = array(
                        'label' => 'GST NO',
                        'value' => function ($data) {
                            return $data->getVendorTAXNO();
                        }
                    );
                } else if ($select == 'gst_per') {
                    $columns[] = array(
                        'label' => 'TAX%',
                        'value' => function ($data) {
                            return $data->getTotalGstPer();
                        }
                    );
                } else if ($select == 'cgst_per') {
                    $columns[] = array(
                        'label' => 'CGST%',
                        'value' => function ($data) {
                            return $data->getTaxPercentage("cgst_per");
                        }
                    );
                } else if ($select == 'sgst_per') {
                    $columns[] = array(
                        'label' => 'SGST%',
                        'value' => function ($data) {
                            return $data->getTaxPercentage("sgst_per");
                        }
                    );
                } else if ($select == 'igst_per') {
                    $columns[] = array(
                        'label' => 'IGST%',
                        'value' => function ($data) {
                            return $data->getTaxPercentage("igst_per");
                        }
                    );
                } else if ($select == 'cess_per') {
                    $columns[] = array(
                        'label' => 'CESS%',
                        'value' => function ($data) {
                            return $data->getTaxPercentage("cess_per");
                        }
                    );
                } else if ($select == 'taxable') {
                    $columns[] = array(
                        'label' => 'Taxable Amount',
                        'value' => function ($data) {
                            return $data->getsaleTaxableAmount() - ($data->discount_amt1 + $data->discount_amt);
                        }
                    );
					
				 } else if ($select == 'invoice_amt') {
                    $columns[] = array(
                        'label' => 'Invoice Amount',
                        'value' => function ($data) {
                            return $data->amount;
                        }
                    );	
                } else if ($select == 'basic_value') {
                    $columns[] = array(
                        'label' => 'Rate',
                        'value' => function ($data) {
                            return $data->mrp;
                        }
                    );
                } else if ($select == 'discount') {
                    $columns[] = array(
                        'label' => 'Discount',
                        'value' => function ($data) {
                            return $data->getMainDiscount();
                        }
                    );
                } else if ($select == 'gst_amt') {
                    $columns[] = array(
                        'label' => 'GST',
                        'value' => function ($data) {
                            return $data->getTotalGstAmt();
                        }
                    );
                } else if ($select == 'cgst_amt') {
                    $columns[] = array(
                        'label' => 'CGST',
                        'value' => function ($data) {
                            return $data->cgst_amt;
                        }
                    );
                } else if ($select == 'sgst_amt') {
                    $columns[] = array(
                        'label' => 'SGST',
                        'value' => function ($data) {
                            return $data->sgst_amt;
                        }
                    );
                } else if ($select == 'igst_amt') {
                    $columns[] = array(
                        'label' => 'IGST',
                        'value' => function ($data) {
                            return $data->igst_amt;
                        }
                    );
                } else if ($select == 'cess_amt') {
                    $columns[] = array(
                        'label' => 'CESS Amount',
                        'value' => function ($data) {
                            return $data->cess_amt;
                        }
                    );
                } else if ($select == 'grn_no') {
                    $columns[] = array(
                        'label' => 'GRN NUMBER',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? 'Gr-' . $data->purchaseBill->grn_refrence_no : "";
                            // return 'Gr-'.$data->purchase_bill_id;
                        }
                    );
                } else if ($select == 'scheme') {
                    $columns[] = array(
                        'label' => 'SCHEME AND DISCOUNT',
                        'value' => function ($data) {
                            return $data->getSchemeDiscount();
                        }
                    );
                } else {
                    $columns[] = $select;
                }
            }
        }

        return $columns;
    }
 public function getB2bDeptColumns($selectcolumns = array())
    {
        if (! empty($selectcolumns)) {
            $selected = $selectcolumns;
        } else {

            $selected = array(
               
               
                'bill_no',
                'start_date',
                'customer',
                'taxable',
                'gst_per',
                'cgst_per',
                'sgst_per',
                'igst_per',
                'cess_per',
                'cgst_amt',
                'sgst_amt',
                'cess_amt',
                'igst_amt',
                'round_amt',
              
            );
        }

        if ($selected) {
            foreach ($selected as $select) {
                if ($select == 'start_date') {
                    $columns[] = array(
                        'label' => 'Date',
                        'value' => function ($data) {
					// echo"<pre>"; print_r($data->purchaseBil); die;
                            return $data->getOrderBillDate();
                        }
                    );
					
					}
					else if ($select == 'taxable') {
                    $columns[] = array(
                        'label' => 'Taxable',
                        'value' => function ($data) {
                            return $data->price-($data->discount_amt1 + $data->discount_amt) ;
                        }
                    );
					} else if ($select == 'customer') {
                    $columns[] = array(
                        'label' => 'Customer',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->vendor->name : "";
                        }
                    );
					} 
					else if ($select == 'place') {
                    $columns[] = array(
                        'label' => 'Place',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->vendor->primary_address : "";
                        }
                    );
                } else if ($select == 'vendor') {
                    $columns[] = array(
                        'label' => 'Vendor',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->vendor : "";
                        }
                    );
					} 
                 else if ($select == 'hsn_code') {
                    $columns[] = array(
                        'label' => 'HSN Code',
                        'value' => function ($data) {
                            return isset($data->hsn_code) ? $data->hsn_code : "";
                        }
                    );  
					} else if ($select == 'state') {
                    $columns[] = array(
                        'label' => 'State',
                        'value' => function ($data) {
						
						  return isset($data->purchaseBill) ? $data->getStateName($data->purchaseBill->vendor->state_id) : "";
                           
                        }
                    );  
					} else if ($select == 'qty') {
                    $columns[] = array(
                        'label' => 'Qty',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->approved_qty : "";
                        }
                    );
                } else if ($select == 'bill_no') {
                    $columns[] = array(
                        'label' => 'Bill No',
                        'value' => function ($data) {
                            return $data->getOrderBillNo();
                        }
                    );
                } else if ($select == 'tax_no') {
                    $columns[] = array(
                        'label' => 'GST NO',
                        'value' => function ($data) {
                            return $data->getVendorTAXNO();
                        }
                    );
                } else if ($select == 'gst_per') {
                    $columns[] = array(
                        'label' => 'TAX%',
                        'value' => function ($data) {
                            return $data->getTaxTitle();
                        }
                    );
                } else if ($select == 'cgst_per') {
                    $columns[] = array(
                        'label' => 'CGST%',
                        'value' => function ($data) {
                            return $data->cgst_per;
                        }
                    );
                } else if ($select == 'sgst_per') {
                    $columns[] = array(
                        'label' => 'SGST%',
                        'value' => function ($data) {
                            return $data->sgst_per;
                        }
                    );
                } else if ($select == 'igst_per') {
                    $columns[] = array(
                        'label' => 'IGST%',
                        'value' => function ($data) {
                            return $data->igst_per;
                        }
                    );
                } else if ($select == 'cess_per') {
                    $columns[] = array(
                        'label' => 'CESS%',
                        'value' => function ($data) {
                            return $data->cess_per;
                        }
                    );
                } else if ($select == 'round_amt') {
                    $columns[] = array(
                        'label' => 'Amount',
                        'value' => function ($data) {
                            return $data->amount;
                        }
                    );
					
				 } else if ($select == 'invoice_amt') {
                    $columns[] = array(
                        'label' => 'Invoice Amount',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->net_bill_amount : "";
                        }
                    );	
                } else if ($select == 'basic_value') {
                    $columns[] = array(
                        'label' => 'Rate',
                        'value' => function ($data) {
                            return $data->mrp;
                        }
                    );
                } else if ($select == 'discount') {
                    $columns[] = array(
                        'label' => 'Discount',
                        'value' => function ($data) {
                            return $data->getMainDiscount();
                        }
                    );
                } else if ($select == 'gst_amt') {
                    $columns[] = array(
                        'label' => 'GST',
                        'value' => function ($data) {
                            return $data->getTotalGstAmt();
                        }
                    );
                } else if ($select == 'cgst_amt') {
                    $columns[] = array(
                        'label' => 'CGST',
                        'value' => function ($data) {
                            return $data->cgst_amt;
                        }
                    );
                } else if ($select == 'sgst_amt') {
                    $columns[] = array(
                        'label' => 'SGST',
                        'value' => function ($data) {
                            return $data->sgst_amt;
                        }
                    );
                } else if ($select == 'igst_amt') {
                    $columns[] = array(
                        'label' => 'IGST',
                        'value' => function ($data) {
                            return $data->getIgstAmount();
                        }
                    );
                } else if ($select == 'cess_amt') {
                    $columns[] = array(
                        'label' => 'CESS Amount',
                        'value' => function ($data) {
                            return $data->getCessAmount();
                        }
                    );
                } else if ($select == 'grn_no') {
                    $columns[] = array(
                        'label' => 'GRN NUMBER',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? 'Gr-' . $data->purchaseBill->grn_refrence_no : "";
                            // return 'Gr-'.$data->purchase_bill_id;
                        }
                    );
                } else if ($select == 'scheme') {
                    $columns[] = array(
                        'label' => 'SCHEME AND DISCOUNT',
                        'value' => function ($data) {
                            return $data->getSchemeDiscount();
                        }
                    );
                } else {
                    $columns[] = $select;
                }
            }
        }

        return $columns;
    }
    public function getColumns($selectcolumns = array())
    {
        if (! empty($selectcolumns)) {
            $selected = $selectcolumns;
        } else {

            $selected = array(
                'date',
                'vendor',
                'hsn_code',
                'bill_no',
                'tax_no',
                'gst_per',
                'cgst_per',
                'sgst_per',
                'igst_per',
                'cess_per',
                'net_amount',
                'basic_value',
                'discount',
                'gst_amt',
                'cgst_amt',
                'sgst_amt',
                'igst_amt',
                'cess_amt',
                // 'grn_no',
                // 'scheme'
            );
        }

        if ($selected) {
            foreach ($selected as $select) {
                if ($select == 'date') {
                    $columns[] = array(
                        'label' => 'Date',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->start_date : "";
                        }
                    );
                } else if ($select == 'vendor') {
                    $columns[] = array(
                        'label' => 'Vendor',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->vendor : "";
                        }
                    );
                } else if ($select == 'hsn_code') {
                    $columns[] = array(
                        'label' => 'HSN Code',
                        'value' => function ($data) {
                            return isset($data->tax) ? $data->tax->hrn_code : "";
                        }
                    );
                } else if ($select == 'bill_no') {
                    $columns[] = array(
                        'label' => 'Bill No',
                        'value' => function ($data) {
                          return isset($data->purchaseBill) ? '' . $data->purchaseBill->getOrderBillNo() . '' : "";		
                        }
                    );
                } else if ($select == 'tax_no') {
                    $columns[] = array(
                        'label' => 'GST NO',
                        'value' => function ($data) {
                            return $data->getVendorTAXNO();
                        }
                    );
                } else if ($select == 'gst_per') {
                    $columns[] = array(
                        'label' => 'GST%',
                        'value' => function ($data) {
                            return $data->getTotalGstPer();
                        }
                    );
                } else if ($select == 'cgst_per') {
                    $columns[] = array(
                        'label' => 'CGST%',
                        'value' => function ($data) {
                            return $data->getTaxPercentage("cgst_per");
                        }
                    );
                } else if ($select == 'sgst_per') {
                    $columns[] = array(
                        'label' => 'SGST%',
                        'value' => function ($data) {
                            return $data->getTaxPercentage("sgst_per");
                        }
                    );
                } else if ($select == 'igst_per') {
                    $columns[] = array(
                        'label' => 'IGST%',
                        'value' => function ($data) {
                            return $data->getTaxPercentage("igst_per");
                        }
                    );
                } else if ($select == 'cess_per') {
                    $columns[] = array(
                        'label' => 'CESS%',
                        'value' => function ($data) {
                            return $data->getTaxPercentage("cess_per");
                        }
                    );
                } else if ($select == 'net_amount') {
                    $columns[] = array(
                        'label' => 'Net Amount',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? $data->purchaseBill->net_bill_amount : "";
                        }
                    );
                } else if ($select == 'basic_value') {
                    $columns[] = array(
                        'label' => 'Basic Value',
                        'value' => function ($data) {
                            return $data->getBasicAmount();
                        }
                    );
                } else if ($select == 'discount') {
                    $columns[] = array(
                        'label' => 'Discount',
                        'value' => function ($data) {
                            return $data->getMainDiscount();
                        }
                    );
                } else if ($select == 'gst_amt') {
                    $columns[] = array(
                        'label' => 'GST',
                        'value' => function ($data) {
                            return $data->getTotalGstAmt();
                        }
                    );
                } else if ($select == 'cgst_amt') {
                    $columns[] = array(
                        'label' => 'CGST',
                        'value' => function ($data) {
                            return $data->getCgstAmount();
                        }
                    );
                } else if ($select == 'sgst_amt') {
                    $columns[] = array(
                        'label' => 'SGST',
                        'value' => function ($data) {
                            return $data->getSgstAmount();
                        }
                    );
                } else if ($select == 'igst_amt') {
                    $columns[] = array(
                        'label' => 'IGST',
                        'value' => function ($data) {
                            return $data->getIgstAmount();
                        }
                    );
                } else if ($select == 'cess_amt') {
                    $columns[] = array(
                        'label' => 'CESS Amount',
                        'value' => function ($data) {
                            return $data->getCessAmount();
                        }
                    );
                } else if ($select == 'grn_no') {
                    $columns[] = array(
                        'label' => 'GRN NUMBER',
                        'value' => function ($data) {
                            return isset($data->purchaseBill) ? 'Gr-' . $data->purchaseBill->grn_refrence_no : "";
                            // return 'Gr-'.$data->purchase_bill_id;
                        }
                    );
                // } else if ($select == 'scheme') {
                    // $columns[] = array(
                        // 'label' => 'SCHEME AND DISCOUNT',
                        // 'value' => function ($data) {
                            // return $data->getSchemeDiscount();
                        // }
                    // );
                } else {
                    $columns[] = $select;
                }
            }
        }

        return $columns;
    }




    public function getTotalGstPer()
    {
        $data = $this;
        $cgst = $data->getTaxPercentage("cgst_per");
        $sgst = $data->getTaxPercentage("sgst_per");
        $cess = $data->getTaxPercentage("cess_per");
        $igst = $data->getTaxPercentage("igst_per");
        $total = $cgst + $sgst;
        return $total;
    }
	

    public function getTotalGstAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = PurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id,
            'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $cgst = $detail->getTaxPercentage("cgst_amt");
                $sgst = $detail->getTaxPercentage("sgst_amt");
                $cess = $detail->getTaxPercentage("cess_amt");
                $igst = $detail->getTaxPercentage("igst_amt");
                $total = $total + ($cgst + $sgst);
            }
        }
        return $total;
    }
	
	public function getStateName($id =null )
    {
        $total = 0;
			if(empty($id)){

			$id = $this->purchaseBill->vendor->state_id;
			}
		
        $details = State::model()->findByPk(array(
             'id' => $id
             // 'tax_id' => $this->tax_id
        ));
		if( $details){
			
         return  $details->title;
		}else{
			
			return "";
		}
    }
    public function getTotalTaxAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $cgst = $detail->getTaxPercentage("cgst_amt");
                $sgst = $detail->getTaxPercentage("sgst_amt");
                $cess = $detail->getTaxPercentage("cess_amt");
                $igst = $detail->getTaxPercentage("igst_amt");
                $total = $total + ($cgst + $sgst +  $cess +$igst);
            }
        }
        return $total;
    }
public function getTotalNetAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
           
        ));
        if ($details) {
            foreach ($details as $detail) {
				  $amount = $detail->amount;
                // $amount = $detail->price * $detail->approved_qty;
                $total = $total + $amount ;
                
            }
        }
        return $total;
    }
 public function getTotalCgstAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $cgst = $detail->getTaxPercentage("cgst_amt");
                
                $total = $total + $cgst ;
            }
        }
        return $total;
    }
	
	 public function getTotalSgstAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $cgst = $detail->getTaxPercentage("sgst_amt");
                
                $total = $total + $cgst ;
            }
        }
        return $total;
    }
	
	public function getTotalDisAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
              
                $cess = $detail->discount_amt ;
             
                $total = $total + $cess;
            }
        }
        return $total;
    }
	public function getTotalDis1Amt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
              
                $cess = $detail->discount_amt1;
             
                $total = $total + $cess;
            }
        }
        return $total;
    }
	
	public function getTotalCessAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
              
                $cess = $detail->getTaxPercentage("cess_amt");
             
                $total = $total + $cess;
            }
        }
        return $total;
    }
	 public function getTotalIgstAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
               
                $igst = $detail->getTaxPercentage("igst_amt");
                $total = $total + $igst;
            }
        }
        return $total;
    }
	
	
    public function getGstTrue($poid)
    {
        $gst = true;
        if ($poid) {
            $mrs = B2bPurchaseBill::model()->findByAttributes(array(
                'id' => $poid
            ));
            if ($mrs) {
                $outlet = Outlet::model()->findByPk($mrs->outlet_id);
                if ($outlet) {
                    $vendor = Vendor::model()->findByPk($mrs->vendor_id);
                    if ($vendor->state_id != $outlet->state_id) {
                        $gst = false;
                    }
                }
            }
        }
        // Yii::log ( CVarDumper::dumpAsString ( $gst ), CLogger::LEVEL_WARNING, '$$gst' );
        return $gst;
    }

    public function getTaxPercentage($col)
    {
        $checkgst = true;
        if ($col == 'igst_per' || $col == 'igst_amt' ) {
			
            $checkgst = false;
			
        }
		
        if ($this->getGstTrue($this->purchase_bill_id) == $checkgst) {

            return $this->$col;
        } else {
			if($col == 'cess_per' || $col == 'cess_amt'){
				  return $this->$col;
			}else{
				
            return '0.00';
			}
        }
    }

    public function getDetailGstTrue()
    {
        $gst = false;

        Yii::log(CVarDumper::dumpAsString($gst), CLogger::LEVEL_WARNING, '$$gst');

        return $gst;
    }

    public function getMainDiscount()
    {
        $amount = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id,
            'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $amount = $amount + $detail->discount_amt;
            }
        }
        return '0';
    }

    public function getSchemeDiscount()
    {
        $amount = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $purchaseBill = PurchaseBill::model()->findByPk($purchase_bill_id);
        $criteria = new CDbCriteria();
        $criteria->addCondition('purchase_bill_id =' . $purchase_bill_id);
        $criteria->order = 'id desc';
        $criteria->limit = '1';
        $criteria->group = 'purchase_bill_id,tax_id';
        $detail = B2bPurchaseBillDetail::model()->find($criteria);
        if ($detail->id == $this->id) {
            $amount = $purchaseBill->bill_other_discount;
        }
        return $amount;
    }

    public function getCgstAmount()
    {
        $amount = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id,
            'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $cgst = $detail->getTaxPercentage("cgst_amt");
                $amount = $amount + $cgst;
            }
        }
        return $amount;
    }

    public function getSgstAmount()
    {
        $amount = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id,
            'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $sgst = $detail->getTaxPercentage("sgst_amt");
                $amount = $amount + $sgst;
            }
        }
        return $amount;
    }

    public function getCessAmount()
    {
        $amount = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id,
            'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $cess = $detail->getTaxPercentage("cess_amt");
                $amount = $amount + $cess;
            }
        }
        return $amount;
    }

    public function getIgstAmount()
    {
        $amount = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id,
            'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $igst = $detail->igst_amt;
                $amount = $amount + $igst;
            }
        }
        return $amount;
    }

    public function getNetAmount()
    {
        $amount = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id,
            'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                $amount = $amount + $detail->amount;
            }
        }
        return $amount;
    }


	public function getItemBasicAmount()
    {
        $amount = 0;
		
		$purchase_bill_id = $this->purchase_bill_id;
       
        $criteria = new CDbCriteria();
        $criteria->addCondition('purchase_bill_id =' . $purchase_bill_id);
       
        $detail = B2bPurchaseBillDetail::model()->find($criteria);
      
        if ($detail) {
           
                $amount = $amount + ((($detail->amount)) - (($detail->getTaxPercentage("cgst_amt")) + ($detail->getTaxPercentage("sgst_amt")) + ($detail->getTaxPercentage("cess_amt")) + ($detail->getTaxPercentage("igst_amt"))));
                if ($purchase_bill_id == '127') {
                    Yii::log(CVarDumper::dumpAsString($detail->getTaxPercentage("cgst_amt")), CLogger::LEVEL_WARNING, '$detail->getCgstAmount()');
                    Yii::log(CVarDumper::dumpAsString($detail->id), CLogger::LEVEL_WARNING, '$detail');
                    Yii::log(CVarDumper::dumpAsString($amount), CLogger::LEVEL_WARNING, '$amount');
                }
           
        }
        return $amount;
    }
	
	public function getTotalTaxableAmt()
    {
        $total = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                  // $total = $total + ((($detail->amount)) - (($detail->getTaxPercentage("cgst_amt")) + ($detail->getTaxPercentage("sgst_amt")) + ($detail->getTaxPercentage("cess_amt")) + ($detail->getTaxPercentage("igst_amt"))));
				  
				  $total = $total + (($detail->approved_qty * $detail->price) - ($detail->discount_amt + $detail->discount_amt1));
            }
        }
        return $total;
    }
	
	 
	
	
	
    public function getBasicAmount()
    {
        $amount = 0;
        $purchase_bill_id = $this->purchase_bill_id;
        $details = B2bPurchaseBillDetail::model()->findAllByAttributes(array(
            'purchase_bill_id' => $purchase_bill_id
            // 'tax_id' => $this->tax_id
        ));
        if ($details) {
            foreach ($details as $detail) {
                // $amount = $amount + ((($detail->amount)) - (($detail->getTaxPercentage("cgst_amt")) + ($detail->getTaxPercentage("sgst_amt")) + ($detail->getTaxPercentage("cess_amt")) + ($detail->getTaxPercentage("igst_amt"))));
  $amount = $amount + (($detail->approved_qty * $detail->price) - ($detail->discount_amt + $detail->discount_amt1));               

			   if ($purchase_bill_id == '127') {
                    Yii::log(CVarDumper::dumpAsString($detail->getTaxPercentage("cgst_amt")), CLogger::LEVEL_WARNING, '$detail->getCgstAmount()');
                    Yii::log(CVarDumper::dumpAsString($detail->id), CLogger::LEVEL_WARNING, '$detail');
                    Yii::log(CVarDumper::dumpAsString($amount), CLogger::LEVEL_WARNING, '$amount');
                }
            }
        }
        return $amount;
    }
	
	

    public function toArray1()
    {
        $model = $this;
        $bill = $this;
        $json_entry = null;
        if ($model) {
            $default_img = 'default.png';
            $json_entry = array();
            $json_entry['id'] = $model->id;
            $json_entry['Date'] = isset($model->purchaseBill) ? $model->purchaseBill->end_date : "";
            $json_entry['Vendor'] = isset($model->purchaseBill) ? $model->purchaseBill->vendor->name : "";
            $json_entry['HSN Code'] = isset($model->tax) ? $model->tax->hrn_code : "";
            $json_entry['Bill No'] = isset($model->purchaseBill) ? $model->purchaseBill->bill_no : "";
            $json_entry['GST NO'] = $model->getVendorTAXNO();
            $json_entry['GST Rate'] = $model->getTotalGstPer();
            $json_entry['CGST Rate'] = $model->getTaxPercentage("cgst_per");
            $json_entry['SGST Rate'] = $model->getTaxPercentage("sgst_per");

            $json_entry['CESS Rate'] = $model->getTaxPercentage("cess_per");
            $json_entry['IGST Rate'] =  $model->getTaxPercentage("igst_per");
            $json_entry['Net Amount'] = isset($model->purchaseBill) ? $model->purchaseBill->net_bill_amount : "";
            $json_entry['Basic Value'] = $model->getBasicAmount();
            $json_entry['Discount'] = $model->getMainDiscount();
            $json_entry['GST'] = $model->getTotalGstAmt();
            $json_entry['CGST'] = $model->getCgstAmount();
            $json_entry['SGST'] = $model->getSgstAmount();
            $json_entry['IGST'] = $model->getIgstAmount();
            $json_entry['CESS'] = $model->getCessAmount();
            $json_entry['GRN NUMBER'] = isset($model->purchaseBill) ? 'Gr-' . $model->purchaseBill->grn_refrence_no : "";
            $json_entry['SCHEME AND DISCOUNT'] = isset($model->purchaseBill) ? $model->getSchemeDiscount() : "";
        }
        return $json_entry;
    }

    public function gethsncode()
    {
        if ($this->hsn_code != null) {
            return $this->hsn_code;
        } else {
            return $this->item->hsn_code;
        }
    }
	
		public function getItemwiseColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			$selected = array (
					'item_detail_id',
					'item_id',
					'approved_qty',
					'mrp',
					'amount',
					
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
								return $data->amount;
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
	public function getItemUnit() {
		$title = '';
		$item_detail = ItemDetail::model ()->findByPk ( $this->item_detail_id );
		if ($item_detail) {
			$item = Item::model ()->findByPk ( $item_detail->item_id );
			if ($item) {
				$title = $item->unit;
			}
		}
		return $title;
	}
	public function getsaleTaxableAmount(){
		// echo"<pre>"; print_r($this->id); die;
		$criteria = new CDbCriteria ();
		// $criteria->with = array( 'purchaseBill');
		$criteria->addCondition ( 'id =' . $this->id );
		// $criteria->compare ( 'date(create_time)', $this->start_date );
		$criteria->select = 'sum(price*approved_qty) as price';
		// $criteria->addInCondition('t.purchase_bill_id',$order_ids);
		
		// $criteria->addInCondition('purchase_bill_id',$order_ids);
		$order = B2bPurchaseBillDetail::model ()->find( $criteria );
		
		$amount = $order->price ;
		return $amount;
	}
		
	public function getTotalItemB2bTaxableAmount() {
		$amount = 0;
		$oamount = 0;
		$refund = 0;
		$order_ids = array ();
		$criteria1 = new CDbCriteria();
		$orders = B2bPurchaseBill::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			} 
		$criteria = new CDbCriteria ();
		$criteria->with = array( 'purchaseBill');
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		$criteria->addInCondition('t.purchase_bill_id',$order_ids);
		// $criteria->compare ( 'date(create_time)', $this->start_date );
		$criteria->select = 'sum(price*approved_qty) as price';
		$criteria->group = 'purchaseBill.vendor_id,t.tax_id';
		// $criteria->addInCondition('purchase_bill_id',$order_ids);
		$order = B2bPurchaseBillDetail::model ()->find( $criteria );
		
		$amount = $order->price ;
		// echo"<pre>"; print_r($amount); die;
		// $refund_price = '0.00';
		// $criteria3 = new CDbCriteria ();
		// $criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		// $criteria3->compare ( 'date(orderRefund.create_time)', $this->create_date );
		// $criteria3->select = 'sum(t.price*t.qty) as qty';
		// $criteria3->with = 'orderRefund';
		// $criteria3->addInCondition('orderRefund.order_id',$order_ids);
		
		// $orderRefundItem = OrderRefundItem::model ()->find ( $criteria3 );
		// if($orderRefundItem){
		// $refund_price = $orderRefundItem->qty;
		// }
	
		$amount = $amount ;
	
		return $amount;
	}
	
	public function getB2BOrdertotalgstAmount() {
		$tax =  $this->getB2BGroupTaxCgstAmount() + $this->getB2BGroupTaxSgstAmount()+$this->getB2BGroupTaxIgstAmount() + $this->getB2BGroupTaxCessAmount();
		return round ( $tax, 2 );
	
	}
	
	
	public function getB2BGroupTaxCgstAmount() {
		$amount = 0;
		$oamount = 0;
		$refund = 0;
		$taxable = $this->getTotalItemB2bTaxableAmount();
		$cgst_per = $this->cgst_per;
		
		
		
		
		
		$amount = $taxable * $cgst_per/100;
		
		
		return round ( $amount, 2 );
		
	}
	public function getB2BGroupTaxSgstAmount() {
		$taxable = $this->getTotalItemB2bTaxableAmount();
		$sgst_per = $this->sgst_per;
		
		
		
		
		
		$amount = $taxable * $sgst_per/100;
		
		
		return round ( $amount, 2 );
	
	
	
	
	}
	public function getB2BGroupTaxIgstAmount() {
		$taxable = $this->getTotalItemB2bTaxableAmount();
		$igst_per = $this->igst_per;
		
		
		
		
		
		$amount = $taxable * $igst_per/100;
		
		
		return round ( $amount, 2 );
	
	}
	public function getB2BGroupTaxCessAmount() {
		$taxable = $this->getTotalItemB2bTaxableAmount();
		$cess_per = $this->cess_per;
		
		
		
		
		
		$amount = $taxable * $cess_per/100;
		
		
		return round ( $amount, 2 );
	
	}
		public function getB2BGroupTaxOrderTotalAmount() {
		$amount = 0;
		$oamount = 0;
		$refund = 0;
		$order_ids = array ();
		$criteria1 = new CDbCriteria ();
		
			$orders = B2bPurchaseBill::model ()->findAll ( $criteria1 );
			if ($orders) {
				foreach ( $orders as $order ) {
					$order_ids [] = $order->id;
				}
			}
		
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'tax_id =' . $this->tax_id );
		// $criteria->compare ( 'date(create_time)', $this->start_date );
		$criteria->addInCondition('purchase_bill_id',$order_ids);
		$orders = B2bPurchaseBillDetail::model ()->findAll( $criteria );
		
		
		
		if($orders){
			foreach($orders as $order){
				$oamount = $oamount + (($order->amount));
			}
		}
		//$amount = $oamount;
		
		
	
	
		// $criteria3 = new CDbCriteria ();
		
		// $criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
		// $criteria3->with = 'orderRefund';
			// $criteria3->compare ( 'date(orderRefund.create_time)', $this->create_date );
		// $criteria3->addInCondition('orderRefund.order_id',$order_ids);
		// $orderRefundItems = OrderRefundItem::model ()->findAll( $criteria3 );
		// if($orderRefundItems){
				
			// foreach($orderRefundItems as $orderRefundItem){
				// $refund = $refund + ((($orderRefundItem->price) * ($orderRefundItem->qty)) + ($orderRefundItem->tax_amt));
					
			// }
		// }
		$amount = $oamount ;
	
	
		return round ( $amount, 2 );
	}
	
	
}