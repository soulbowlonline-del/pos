<?php

/**
 * @property integer $id
 * @property integer $qty
 * @property double $discount_amt
 * @property double $total_amt
 * @property double $paid_amt
 * @property integer $status
 * @property integer $type_id
 * @property integer $city_id
 * @property integer $state_id
 * @property integer $country_id
 * @property string $address
 * @property string $note
 * @property string $create_time
 * @property string $update_time
 * @property integer $customer_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseB2bOrder');

class B2bOrder extends BaseB2bOrder
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public static function getOrderRecord()
    {
        $list = array();
        $string = '';
        $i = 1;
        for ($i = 1; $i < 13; $i ++) {
            $criteria = new CDbCriteria();
            $criteria->addCondition('month(create_time) = ' . $i);
            $count = Order::model()->count($criteria);
            $list[] = $count;
        }

        $string = implode(',', $list);
        return $string;
    }

    public function getOrderAfterRefundQty()
    {
        $itemqty = 0;
        $criteria = new CDbCriteria();
        $criteria->addCondition('order_id = ' . $this->id);
        $criteria->select = 'sum(qty) as qty';
        $orderitem = OrderItem::model()->find($criteria);
        if ($orderitem) {
            $itemqty = $orderitem->qty;
        }
        $refundorders = OrderRefund::model()->findAllByAttributes(array(
            'order_id' => $this->id
        ));
        if ($refundorders) {
            $refund = 0;
            foreach ($refundorders as $refundorder) {
                $criteria = new CDbCriteria();
                $criteria->addCondition('order_refund_id = ' . $refundorder->id);
                $criteria->select = 'sum(qty) as qty';
                $orderrefunditem = OrderRefundItem::model()->find($criteria);
                if ($orderrefunditem) {
                    $refund = $refund + $orderrefunditem->qty;
                }
            }
            if ($refund != 0) {
                $itemqty = $itemqty - $refund;
            }
        }

        return round($itemqty);
    }

    public function getOrderAfterRefundAmount()
    {
        $amount = $this->total_amt;
        $refundorders = OrderRefund::model()->findAllByAttributes(array(
            'order_id' => $this->id
        ));
        if ($refundorders) {
            $refund = 0;
            foreach ($refundorders as $refundorder) {
                $refund = $refund + $refundorder->total_amt;
            }
            if ($refund != 0) {
                $amount = $amount - $refund;
            }
        }

        return round($amount);
    }

    public function toArray1()
    {
        $model = $this;
        $json_entry = null;
        $bill_prefix = 'B';
        if ($model) {
            $outlet = Outlet::model()->findByPk($model->outlet_id);
            if ($outlet) {
                if ($outlet->bill_prefix != '') {
                    $bill_prefix = $outlet->bill_prefix;
                } else {
                    $bill_prefix = 'B';
                }
            }
            $json_list = array();
            $json_entry = array();
            $json_entry['id'] = $model->id;
            $json_entry['bill_no'] = $model->getOrderBillNo();
            $json_entry['bill_date'] = $model->bill_date;
            $json_entry['create_time'] = $model->create_time;
            $json_entry['customer_name'] = isset($model->customer) ? $model->customer->name : '';
            $json_entry['total_amt'] = $model->getOrderAfterRefundAmount();
            $json_entry['qty'] = $model->getOrderAfterRefundQty();
        }
        return $json_entry;
    }

    public function toArray2()
    {
        $model = $this;
        $json_entry = null;
        $bill_prefix = 'B';
        if ($model) {
            $outlet = Outlet::model()->findByPk($model->outlet_id);
            if ($outlet) {
                if ($outlet->bill_prefix != '') {
                    $bill_prefix = $outlet->bill_prefix;
                } else {
                    $bill_prefix = 'B';
                }
            }
            $json_list = array();
            $json_entry = array();
            $json_entry['id'] = $model->id;
            $json_entry['bill_no'] = $model->getOrderBillNo();
            $json_entry['bill_date'] = $model->bill_date;
            $json_entry['mode_of_payment'] = isset($model->modePayment) ? $model->modePayment->title : '';
            $json_entry['mode_of_delivery'] = isset($model->modeDelivery) ? $model->modeDelivery->title : '';
            $json_entry['qty'] = $model->qty;
            $json_entry['discount_amt'] = $model->discount_amt;
            $json_entry['total_sale'] = $model->total_amt;
            $json_entry['total_amt'] = ($model->total_amt) + ($model->discount_amt);
            $json_entry['paid_amt'] = $model->paid_amt;
            $json_entry['status'] = $model->status;
            $json_entry['type_id'] = $model->type_id;
            $json_entry['city_id'] = $model->city_id;
            $json_entry['state_id'] = $model->state_id;
            $json_entry['country_id'] = $model->country_id;
            $json_entry['address'] = $model->address;
            $json_entry['note'] = $model->note;
            $json_entry['create_time'] = $model->create_time;
            $json_entry['customer_id'] = $model->customer_id;
            $json_entry['customer_name'] = isset($model->customer) ? $model->customer->name : '';
            $order_items = $model->orderItems;
            if (! empty($order_items)) {
                foreach ($order_items as $order_item) {
                    /*
                     * if(isset($order_item->itemDetail)){
                     * $json_list [] = $order_item->itemDetail->toArray1($order_item->id,2,1);
                     * }
                     */
                    $json_list[] = $order_item->toArray($return = 1);
                }
            }
            $json_entry['order_items'] = $json_list;
        }
        return $json_entry;
    }

    public function toArray()
    {
        $model = $this;
        $json_entry = null;
        $bill_prefix = 'B';
        if ($model) {
            $outlet = Outlet::model()->findByPk($model->outlet_id);
            if ($outlet) {
                if ($outlet->bill_prefix != '') {
                    $bill_prefix = $outlet->bill_prefix;
                } else {
                    $bill_prefix = 'B';
                }
            }
            $json_list = array();
            $json_entry = array();
            $json_entry['id'] = $model->id;
            $json_entry['bill_no'] = $model->getOrderBillNo();
            $json_entry['bill_date'] = $model->bill_date;
            $json_entry['mode_of_payment'] = isset($model->modePayment) ? $model->modePayment->title : '';
            $json_entry['mode_of_delivery'] = isset($model->modeDelivery) ? $model->modeDelivery->title : '';
            $json_entry['qty'] = $model->qty;
            $json_entry['discount_amt'] = $model->discount_amt;
            $json_entry['total_amt'] = $model->total_amt;
            $json_entry['paid_amt'] = $model->paid_amt;
            $json_entry['status'] = $model->status;
            $json_entry['type_id'] = $model->type_id;
            $json_entry['city_id'] = $model->city_id;
            $json_entry['state_id'] = $model->state_id;
            $json_entry['country_id'] = $model->country_id;
            $json_entry['address'] = $model->address;
            $json_entry['note'] = $model->note;
            $json_entry['create_time'] = $model->create_time;
            $json_entry['customer_id'] = $model->customer_id;

            $order_items = $model->orderItems;
            if (! empty($order_items)) {
                foreach ($order_items as $order_item) {
                    /*
                     * if(isset($order_item->itemDetail)){
                     * $json_list [] = $order_item->itemDetail->toArray1($order_item->id,2,0);
                     * }
                     */
                    $json_list[] = $order_item->toArray();
                }
            }
            $json_entry['order_items'] = $json_list;
        }
        return $json_entry;
    }

    public function UpdateStock($qty, $item_detail_id)
    {
        $item_detail = ItemDetail::model()->findByPk($item_detail_id);
        $item_detail->update_time = date('Y-m-d H:i:s');
        $item_detail->saveAttributes(array(
            'update_time'
        ));

        $vendor_id = 0;
        if ($item_detail) {
            $item = Item::model()->findByPk($item_detail->item_id);
            $remain_qty = $qty;
            $quantity = $qty;

            $criteria2 = new CDbCriteria();
            $criteria2->order = 'id asc';
            $criteria2->compare("item_id ", $item_detail->item_id);
            $criteria2->compare("item_detail_id ", $item_detail->id);
            $criteria2->compare("outlet_id ", $this->outlet_id);
            $criteria2->addCondition("balance_qty > 0");
            $itemstock = ItemStock::model()->find($criteria2);
            if ($item != null) {
                $item->update_time = date('Y-m-d H:i:s');
                $item->saveAttributes(array(
                    'update_time'
                ));
                $criteria = new CDbCriteria();
                $criteria->order = 'id desc';
                $criteria->addCondition('item_detail_id =' . $item->id);
                $vendor = ItemVendor::model()->find($criteria);
                if ($vendor) {
                    $vendor_id = $vendor->vendor_id;
                }
            }
            if ($itemstock) {
                if (($itemstock->balance_qty) >= $quantity) {
                    $balance = $itemstock->balance_qty;
                    $itemstock->balance_qty = $itemstock->balance_qty - $quantity;
                    $itemstock->tax_id = $item_detail->tax_id;
                    if ($itemstock->save()) {
                        $log = new StockLog();

                        $log->item_detail_id = $item_detail->id;
                        $log->item_id = $item->id;
                        $log->batch_no = $itemstock->batch_number;
                        $log->current_qty = $item_detail->getStockQty();
                        $log->previous_qty = ($item_detail->getStockQty()) + ($quantity);
                        $log->Qty = $quantity;
                        $log->outlet_id = $this->outlet_id;
                        if ($vendor_id != null) {
                            $log->vendor_id = $vendor_id;
                        }
                        $log->type_id = StockLog::TYPE_ORDER;

                        if ($log->save()) {} else {}
                        $net_less = $itemstock->isnetLessMin();
                        Yii::log(CVarDumper::dumpAsString($net_less), CLogger::LEVEL_WARNING, '$net_less');
                        if ($net_less) {
                            $itemstock->createMrs();
                        }
                    }
                    return true;
                } else {
                    $balance = $itemstock->balance_qty;

                    $itemstock->balance_qty = 0;
                    $itemstock->tax_id = $item_detail->tax_id;
                    Yii::log(CVarDumper::dumpAsString($itemstock), CLogger::LEVEL_WARNING, '$itemstock');
                    if ($itemstock->save()) {
                        $log = new StockLog();

                        $log->item_detail_id = $item_detail->id;
                        $log->item_id = $item->id;
                        $log->batch_no = $itemstock->batch_number;
                        $log->current_qty = $item_detail->getStockQty();
                        $log->previous_qty = ($item_detail->getStockQty()) + $balance;
                        $log->Qty = abs($balance);
                        $log->outlet_id = $this->outlet_id;
                        if ($vendor_id != null) {
                            $log->vendor_id = $vendor_id;
                        }
                        $log->type_id = StockLog::TYPE_ORDER;

                        if ($log->save()) {} else {}
                        $net_less = $itemstock->isnetLessMin();
                        Yii::log(CVarDumper::dumpAsString($net_less), CLogger::LEVEL_WARNING, '$net_less1');
                        if ($net_less) {
                            $itemstock->createMrs();
                        }
                    }
                    /*
                     * if($remain_qty == 0){
                     * $remain_qty = bcsub($remain_qty, $balance,3);
                     * }else if($remain_qty < 0){
                     * $balance = abs($balance);
                     * $remain = bcadd($remain_qty, $balance,3);
                     * $remain_qty = '-'.$remain;
                     * }else{
                     * if($remain_qty > $balance){
                     * $remain_qty = bcsub($remain_qty, $balance,3);
                     * }else{
                     *
                     * }
                     * }
                     */

                    $remain_qty = $remain_qty - $balance;
                    if ($remain_qty > 0) {
                        $this->UpdateStock($remain_qty, $item_detail_id);
                    }
                }
            } else {
                $criteria2 = new CDbCriteria();
                $criteria2->order = 'id asc';
                $criteria2->compare("item_id ", $item_detail->item_id);
                $criteria2->compare("item_detail_id ", $item_detail->id);
                $criteria2->compare("outlet_id ", $this->outlet_id);
                $itemstock = ItemStock::model()->find($criteria2);
                if ($itemstock) {
                    Yii::log(CVarDumper::dumpAsString($itemstock->balance_qty), CLogger::LEVEL_WARNING, '$balance1');
                    $balance = $itemstock->balance_qty;
                    if ($balance == 0) {
                        $itemstock->balance_qty = bcsub($itemstock->balance_qty, $quantity, 3);
                    }
                    Yii::log(CVarDumper::dumpAsString($itemstock->balance_qty), CLogger::LEVEL_WARNING, '$balance2');
                    Yii::log(CVarDumper::dumpAsString($quantity), CLogger::LEVEL_WARNING, '$quantit3');

                    if ($balance < 0) {
                        $bquantity = abs($itemstock->balance_qty);
                        $remain = bcadd($bquantity, $quantity, 3);
                        $itemstock->balance_qty = '-' . $remain;
                    }
                    $itemstock->tax_id = $item_detail->tax_id;
                    if ($itemstock->save()) {
                        $log = new StockLog();

                        $log->item_detail_id = $item_detail->id;
                        $log->item_id = $item->id;
                        $log->batch_no = $itemstock->batch_number;
                        $log->current_qty = $item_detail->getStockQty();
                        $log->previous_qty = ($item_detail->getStockQty()) + ($quantity);
                        $log->Qty = $quantity;
                        $log->outlet_id = $this->outlet_id;
                        if ($vendor_id != null) {
                            $log->vendor_id = $vendor_id;
                        }
                        $log->type_id = StockLog::TYPE_ORDER;

                        if ($log->save()) {} else {}
                        $net_less = $itemstock->isnetLessMin();
                        Yii::log(CVarDumper::dumpAsString($net_less), CLogger::LEVEL_WARNING, '$net_less');
                        if ($net_less) {
                            $itemstock->createMrs();
                        }
                    }
                }
            }
        }
    }

    public function getColumns($selectcolumns = array())
    {
        if (! empty($selectcolumns)) {
            $selected = $selectcolumns;
        } else {
            $selected = array(
                'bill_no',
                'bill_date',
                'customer_id',
                'mode_of_payment',
                'employee_id',
                'total_amt',
                'discount_amt',
                'refund_amt',
                'refund_by',
                'tax_amt',
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
                if ($select == 'customer_id') {
                    $columns[] = array(
                        'label' => 'Customer',
                        'value' => function ($data) {
                            return isset($data->customer) ? $data->customer : "";
                        }
                    );
                }
                if ($select == 'mode_of_payment') {
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

    public function getOrderTaxAmount()
    {
        $tax = 0;
        if ((Yii::app()->session['order_start_date'] != '') && (Yii::app()->session['order_end_date'] != '')) {
            if (($this->bill_date >= Yii::app()->session['order_start_date']) && ($this->bill_date <= Yii::app()->session['order_end_date'])) {
                $orderitems = OrderItem::model()->findAllByAttributes(array(
                    'order_id' => $this->id
                ));
            } else {
                Yii::log(CVarDumper::dumpAsString($this->id), CLogger::LEVEL_WARNING, '$this->id');
                return $tax;
            }
        } else {
            $orderitems = OrderItem::model()->findAllByAttributes(array(
                'order_id' => $this->id
            ));
        }

        if ($orderitems) {
            foreach ($orderitems as $orderitem) {
                $tax = $tax + $orderitem['tax_amount'];
            }
        }
        return $tax;
    }

    public function getOrderTotalAmount()
    {
        $total_amt = 0;
        if ((Yii::app()->session['order_start_date'] != '') && (Yii::app()->session['order_end_date'] != '')) {
            if (($this->bill_date >= Yii::app()->session['order_start_date']) && ($this->bill_date <= Yii::app()->session['order_end_date'])) {
                return $this->total_amt;
            } else {

                return $total_amt;
            }
        } else {
            $total_amt = $this->total_amt;
        }

        return $total_amt;
    }

    public function getOrderRefundAmount()
    {
        $total_amt = 0;
        if ((Yii::app()->session['order_start_date'] != '') && (Yii::app()->session['order_end_date'] != '')) {
            $orderrefund = OrderRefund::model()->findByAttributes(array(
                'order_id' => $this->id
            ));
            if ($orderrefund) {
                $refunddate = date('Y-m-d', strtotime($orderrefund->create_time));
                if (($refunddate >= Yii::app()->session['order_start_date']) && ($refunddate <= Yii::app()->session['order_end_date'])) {
                    $total_amt = $orderrefund->total_amt;
                }
            }
        } else {
            $orderrefund = OrderRefund::model()->findByAttributes(array(
                'order_id' => $this->id
            ));
            if ($orderrefund) {
                $refunddate = date('Y-m-d', strtotime($orderrefund->create_time));

                $total_amt = $orderrefund->total_amt;
            }
        }

        return $total_amt;
    }

    public function getOrderRefundBy()
    {
        $username = '';
        if ((Yii::app()->session['order_start_date'] != '') && (Yii::app()->session['order_end_date'] != '')) {
            $orderrefund = OrderRefund::model()->findByAttributes(array(
                'order_id' => $this->id
            ));
            if ($orderrefund) {
                $refunddate = date('Y-m-d', strtotime($orderrefund->create_time));
                if (($refunddate >= Yii::app()->session['order_start_date']) && ($refunddate <= Yii::app()->session['order_end_date'])) {
                    $orderRefundItem = OrderRefundItem::model()->findByAttributes(array(
                        'order_refund_id' => $orderrefund->id
                    ));
                    if ($orderRefundItem) {
                        $username = isset($orderRefundItem->createUser) ? $orderRefundItem->createUser : "";
                    }
                }
            }
        } else {
            $orderrefund = OrderRefund::model()->findByAttributes(array(
                'order_id' => $this->id
            ));
            if ($orderrefund) {
                $orderRefundItem = OrderRefundItem::model()->findByAttributes(array(
                    'order_refund_id' => $orderrefund->id
                ));
                if ($orderRefundItem) {
                    $username = isset($orderRefundItem->createUser) ? $orderRefundItem->createUser : "";
                }
            }
        }

        return $username;
    }

    public function getUserwiseColumns($selectcolumns = array())
    {
        if (! empty($selectcolumns)) {
            $selected = $selectcolumns;
        } else {
            $selected = array(
                'username',
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
                } else if ($select == 'amount') {
                    $columns[] = array(
                        'label' => 'Net Amount',
                        'value' => function ($data) {
                            return $data->getTotalNetAmount();
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

    public function getTotalNetAmount()
    {
        $total = 0;
        $criteria1 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria1->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria1->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria1->select = 'sum(price*qty) as price,sum(tax_amount) as tax_amount';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $orderitem = OrderItem::model()->find($criteria1);
        $order_amt = $orderitem->price + $orderitem->tax_amount;

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
        $total = $order_amt - $order_refund_amt;
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
        $criteria1->select = 'sum(price*qty) as price,sum(tax_amount) as tax_amount';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $orderitem = OrderItem::model()->find($criteria1);
        $order_amt = $orderitem->price + $orderitem->tax_amount;

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
        $total = $order_amt - $order_refund_amt;
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
        $criteria1->select = 'sum(discount_amt) as discount_amt';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $discountorder = Order::model()->find($criteria1);

        $total = $discountorder->discount_amt;

        return round($total);
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
        $criteria1->select = 'sum(price*qty) as price';
        $criteria1->addCondition('create_user_id =' . $this->create_user_id);
        $orderitem = OrderItem::model()->find($criteria1);
        $order_amt = $orderitem->price;

        $criteria2 = new CDbCriteria();
        if ((Yii::app()->session['item_id'] != '')) {
            $criteria2->addInCondition('item_id', Yii::app()->session['item_id']);
        }
        if ((Yii::app()->session['start_date'] != '') && (Yii::app()->session['end_date'] != '')) {
            $criteria2->addBetweenCondition('date(create_time)', Yii::app()->session['start_date'], Yii::app()->session['end_date']);
        }
        $criteria2->select = 'sum(price*qty) as price';
        $criteria2->addCondition('create_user_id =' . $this->create_user_id);
        $orderrefunditem = OrderRefundItem::model()->find($criteria2);
        $order_refund_amt = $orderrefunditem->price;

        $total = $order_amt - $order_refund_amt;

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
        $orderitems = OrderItem::model()->findAll($criteria1);

        if ($orderitems) {

            foreach ($orderitems as $orderitem) {
                $qty = $orderitem->qty;
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

    protected function beforeDelete()
    {
        OrderItem::model()->deleteAllByAttributes(array(
            'order_id' => $this->id
        ));

        return parent::beforeDelete();
    }

    public function getOrderBillNo()
    {
        $bill_prefix = 'B';
        $billno = $this->bill_no;
        $month = date('m', strtotime($this->bill_date));
        if ($month > 3) {
            $year = date('Y', strtotime($this->bill_date));
            $yearlast = $year + 1;
        } else {
            $year = date('Y', strtotime($this->bill_date));
            $year = $year - 1;
            $yearlast = date('Y', strtotime($this->bill_date));
        }

        $billyear = date('Y', strtotime($this->bill_date));
        $newyear = $billyear + 1;
        $outlet = Outlet::model()->findByPk($this->outlet_id);
        if ($outlet) {
            if ($outlet->bill_prefix == '') {
                $bill_prefix = $outlet->bill_prefix;
            } else {
                $bill_prefix = 'B';
            }
        }
        $billno = 'Gst ' . $year . '-' . $yearlast . '/' . $bill_prefix . '-' . $billno;
        return $billno;
    }

    public function SendSms()
    {
        
        $customer = Customer::model()->findByPk($this->customer_id);
        
        
        $pdf_main_url = Yii::app()->params['pdf_url']; 
        $pdfurl = "$pdf_main_url" . $this->id;
        if ($customer) {

            $customername = $customer->name;
            $contact = $customer->contact_no;
            if ($contact != '') {
                if ($this->online_order_id == null) {
                    $order_no = $this->bill_no;
                } else {
                    $onlineorder = OnlineOrder::model()->findByPk($this->online_order_id);
                    if ($onlineorder) {
                        $order_no = $onlineorder->order_id;
                    } else {
                        $order_no = $this->bill_no;
                    }
                }
                $bill_no = $this->bill_no;
                // $packed_by = $this->createUser->full_name;
                $packed_by = Yii::app()->params['sms_packed_by'] ;
                $templateid = Yii::app()->params['sms_template_id'] ;
                
                $sms_url = Yii::app()->params['sms_url'];
                try {
                 

                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_URL, "$sms_url");
                    curl_setopt($ch, CURLOPT_POST, 1);

                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                        'longSms' => '1',
                        'apiToken' => Yii::app()->params['sms_apiToken'] ,
                        'mobileNo' => $contact,
                        'senderId' => Yii::app()->params['sms_senderId'] ,
                        'templateId' => $templateid,
                        'param' => "$customername::$order_no::$packed_by::$bill_no::$pdfurl"
                    )));

                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    $server_output = curl_exec($ch);

                    curl_close($ch);
                } catch (Exception $e) {

                    trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
                    return false;
                }
            }
        }

        return true;
    }
}