<?php
namespace app\models;

use app\components\Ui;

use app\components\Criteria;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/**
 * Partial Yii 2 port of protected/models/OrderItem.php (2,809 lines).
 *
 * toArray() and the relations it needs are ported; the reporting column
 * builders (getTaxColumns, getGroupHSNTaxColumns, getColumns and the rest, some
 * 1,700 lines between them) drive the Excel reports and move with those.
 */
class OrderItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_item}}';
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getDiscount()
    {
        return $this->hasOne(Discount::class, ['id' => 'discount_id']);
    }

    public function getItemOrderMrp()
    {
        return $this->mrp;
    }

    /**
     * Payload from OrderItem::toArray(), key for key and in the same order.
     *
     * Quantities and totals are reported net of any refund against the same
     * item on the same order, which is why the refund lookup happens first.
     *
     * @param int $return 0 emits a 'box' key, anything else emits 'is_return'
     */
    public function toApiArray($return = 0)
    {
        // Yii 1 takes the first refund for the order, not all of them.
        $refundOrderItem = null;
        $refundOrder = OrderRefund::find()->where(['order_id' => $this->order_id])->one();
        if ($refundOrder) {
            $refundOrderItem = OrderRefundItem::find()
                ->where([
                    'order_refund_id' => $refundOrder->id,
                    'item_detail_id' => $this->item_detail_id,
                    'item_id' => $this->item_id,
                ])
                ->one();
        }

        $itemDetail = $this->itemDetail;
        $json = [];
        $json['item_id'] = (string)$itemDetail->id;
        $json['bar_code'] = $itemDetail->bar_code;
        $json['item_name'] = isset($itemDetail->item) ? $itemDetail->item->title : '';
        $json['item_desc'] = isset($itemDetail->item) ? $itemDetail->item->short_name : '';
        $json['hsn_code'] = isset($itemDetail->item) ? $itemDetail->item->hsn_code : '';
        $json['unit_name'] = isset($this->item)
            ? Item::getMeasurementTypeOptions($this->item->unit)
            : '';
        $json['is_coupon'] = isset($this->item) && $this->item->is_coupon !== null
            ? (string)$this->item->is_coupon
            : '';

        if ($return == 0) {
            $json['box'] = 0;
        } else {
            $json['is_return'] = 0;
        }

        $json['qty'] = $refundOrderItem === null
            ? $this->qty
            : $this->qty - $refundOrderItem->qty;

        $json['stock_qty'] = $itemDetail->getStockQty();
        $json['sale_rate'] = $this->sale_rate;
        $json['base_price'] = $this->price;
        $json['mrp'] = $this->getItemOrderMrp();

        $json['batch_numbers'] = '';
        $itemStock = $itemDetail->itemStock;
        if (!empty($itemStock)) {
            $json['batch_numbers'] = $itemStock->batch_number;
        }

        $json['discount_id'] = $this->discount_id === null ? null : (string)$this->discount_id;
        $json['discount_val'] = isset($this->discount) ? $this->discount->amount : '0';
        $json['discount_type'] = isset($this->discount)
            ? (string)$this->discount->type_id
            : '1';
        $json['discount_amt'] = $this->discount_amt;
        $json['tax_id'] = $this->tax_id === null ? null : (string)$this->tax_id;
        $json['tax_percent'] = $itemDetail->getItemTaxPercent();
        $json['tax_amount'] = $this->tax_amount;

        $json['total_amount'] = $refundOrderItem === null
            ? $this->total_amt
            : $this->total_amt - $refundOrderItem->total_amt;

        $json['cgst_per'] = $this->cgst_per;
        $json['sgst_per'] = $this->sgst_per;
        $json['cess_per'] = $this->cess_per;
        $json['igst_per'] = $this->igst_per;
        $json['cgst_amt'] = $this->cgst_amt;
        $json['sgst_amt'] = $this->sgst_amt;
        $json['cess_amt'] = $this->cess_amt;
        $json['igst_amt'] = $this->igst_amt;

        if ($refundOrderItem === null) {
            $json['refund_qty'] = 0;
            $json['refund_amount'] = 0;
        } else {
            $json['refund_qty'] = $refundOrderItem->qty;
            $json['refund_amount'] = $refundOrderItem->total_amt;
        }

        return $json;
    }

    /**
     * Payload from OrderItem::toArray1(), which differs from toArray() in
     * exactly three places, all of them key names rather than values:
     * no hsn_code, tax_amt instead of tax_amount, and cess_amount/igst_amount
     * instead of cess_amt/igst_amt. Built by rewriting toApiArray() rather
     * than duplicating sixty lines, so the two cannot drift apart.
     */
    public function toApiArray1($return = 0)
    {
        $row = $this->toApiArray($return);

        unset($row['hsn_code']);

        $renamed = [];
        foreach ($row as $key => $value) {
            if ($key === 'tax_amount') {
                $renamed['tax_amt'] = $value;
            } elseif ($key === 'cess_amt') {
                $renamed['cess_amount'] = $value;
            } elseif ($key === 'igst_amt') {
                $renamed['igst_amount'] = $value;
            } else {
                $renamed[$key] = $value;
            }
        }

        return $renamed;
    }

    /**
     * Payload from OrderItem::getTaxArray(), used by order/reprint for the tax
     * summary lines. The Yii 1 version opens with a loop that sums cgst, sgst,
     * cess and igst across every line sharing this order and tax id, and then
     * never reads the totals - the keys it emits are this line's own amounts.
     * The loop is left out here rather than reproduced: it costs a query per
     * summary row and cannot affect the output.
     *
     * cgst_per and friends come off the Tax row as tax_val1..tax_val4. That
     * mapping is the one recorded in docs/live-bugs-found.md as suspect
     * (getSgstPercent() reads tax_val1 elsewhere); reproduced, not corrected.
     */
    public function getTaxApiArray()
    {
        $order = Order::findOne($this->order_id);
        $itemDetail = $this->itemDetail;

        $out = [];
        $out['hsn_code'] = $this->item ? $this->item->hsn_code : '';
        $out['total_amt'] = $this->total_amt;
        $out['price'] = $this->qty * $this->price;
        $out['tax_amount'] = $this->tax_amount;
        $out['unit_name'] = $this->item ? Item::getMeasurementTypeOptions($this->item->unit) : '';
        $out['qty'] = $this->qty;
        // Yii 1 emits the stringified column; Yii 2's AR casts it to int
        $out['tax_id'] = $this->tax_id === null ? null : (string)$this->tax_id;
        $out['tax_percent'] = $itemDetail->getItemTaxPercent();
        $out['cgst_per'] = $this->tax ? $this->tax->tax_val1 : '';
        $out['sgst_per'] = $this->tax ? $this->tax->tax_val2 : '';
        $out['cess_per'] = $this->tax ? $this->tax->tax_val3 : '';
        $out['igst_per'] = $this->tax ? $this->tax->tax_val4 : '';
        $out['cgst_amt'] = $this->cgst_amt;
        $out['sgst_amt'] = $this->sgst_amt;
        $out['cess_amount'] = $this->cess_amt;
        $out['igst_amount'] = $this->igst_amt;
        if ($order) {
            $out['bill_date'] = date('d-m-Y', strtotime($order->bill_date));
        }

        return $out;
    }

    /**
     * GxActiveRecord::remove_format() in Yii 1 - every model inherits it.
     * Strips thousands separators from a posted number.
     */
    public function remove_format($text)
    {
        return str_replace(',', '', $text);
    }

    /**
     * Yii 1's getTaxValueID(): an IGST tax maps to the GST tax whose two halves
     * add up to the same rate; anything else maps to itself.
     *
     * The final else reads $tax->id when $tax is null, so an unknown tax id is
     * a fatal. Reproduced.
     */
    public function getTaxValueID($taxId)
    {
        $tax = Tax::findOne($taxId);
        if ($tax) {
            if ($tax->type_id == Tax::TYPE_IGST) {
                $half = $tax->tax_val4 / 2;
                $gst = Tax::find()
                    ->where('tax_val1 = :a', [':a' => $half])
                    ->andWhere('tax_val2 = :b', [':b' => $half])
                    ->andWhere('type_id = :t', [':t' => Tax::TYPE_GST])
                    ->orderBy(['id' => SORT_ASC])
                    ->one();
                if ($gst) {
                    return $gst->id;
                }
            } else {
                return $tax->id;
            }
        }
        return $tax->id;   // null here in Yii 1 too
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'OrderItem' : 'OrderItems';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'create_time';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('create_time') ? $this->create_time : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
    }

    /**
     * The ordering Yii 1's defaultScope() put on every query for this
     * model. Null means Yii 1 applied none, and neither should this:
     * an order Yii 1 never applied is an order the user never saw.
     */
    public static function defaultOrder()
    {
        return null;
    }

    /**
     * GxActiveRecord::isAllowCreate(): whether the session the operator
     * has selected is the current financial year.
     *
     * The year runs April to March, so a month past April belongs to
     * year..year+1 and anything earlier to year-1..year. Session names
     * are '<from>-<to>'. False when no session is selected, which is what
     * stops the create button appearing.
     */
    public function isAllowCreate()
    {
        $month = (int) date('m');
        $year = $month > 4 ? (int) date('Y') : (int) date('Y') - 1;
        $yearadd = $year + 1;

        $selected = Yii::$app->session['select_session_id'];
        if ($selected === null || $selected === '') {
            return false;
        }

        $session = Session::findOne($selected);
        if ($session === null) {
            return false;
        }
        $parts = explode('-', $session->name);

        return isset($parts[0], $parts[1])
            && $parts[0] == $year && $parts[1] == $yearadd;
    }

    /**
     * The order this model's listings use.
     *
     * The grid's own sort when search() names one, otherwise whatever
     * defaultScope() applies. Both the admin grid and the index listing
     * read this, so the two cannot drift apart.
     */
    public static function listingOrder()
    {
        return ['id' => SORT_DESC];
    }

    /** Views ask the model whether the current role may reach a route. */
    public function checkPermission($url)
    {
        return \app\components\Access::check($url);
    }

    /**
     * GxActiveRecord::getRelationLabel(). The generated attributeLabels()
     * above already resolves a relation or foreign key to the related
     * model's label, so this is the attribute label.
     */
    public function getRelationLabel($name, $n = null)
    {
        return $this->getAttributeLabel($name);
    }

    /**
     * GxActiveRecord::getTotals(): the SUM of one column over a set of
     * ids, which the grids use for a footer row.
     *
     * The column and table names are interpolated, as in Yii 1 - the
     * call sites pass literals. The ids are bound, which Yii 1 did not:
     * they come from the data provider rather than the request, so this
     * is not a fix for anything, only a refusal to build the same hole
     * again.
     */
    public function getTotals($ids, $columnname, $tablename)
    {
        if (empty($ids)) {
            return null;
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($ids) as $i => $id) {
            $placeholders[] = ':id' . $i;
            $params[':id' . $i] = $id;
        }

        return Yii::$app->db->createCommand(
            'SELECT SUM(' . $columnname . ') FROM ' . $tablename
            . ' WHERE id IN (' . implode(',', $placeholders) . ')', $params)
            ->queryScalar();
    }

    /** GxActiveRecord::getRelatedDataProvider(): the rows of a relation. */
    public function getRelatedDataProvider($relation, $config = [])
    {
        $getter = 'get' . ucfirst($relation);
        if (!method_exists($this, $getter)) {
            throw new \yii\base\InvalidArgumentException(
                get_class($this) . ' does not have relation "' . $relation . '".');
        }

        return new ActiveDataProvider(array_merge(
            ['query' => $this->$getter(), 'pagination' => ['pageSize' => Ui::PAGE_SIZE]],
            $config));
    }

    public static function getStatusOptions($id = null)
    {
		$list = ["Draft","Published","Archive"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getTypeOptions($id = null)
    {
		$list = ["TYPE1","TYPE2","TYPE3"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order',
            'item_detail_id' => 'ItemDetail',
            'qty' => 'Qty',
            'price' => 'Price',
            'min_amt' => 'Minimum Total Amount',
            'max_amt' => 'Maximum Total Amount',
            'discount_id' => 'Discount',
            'discount_amt' => 'Discount Amt',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'item_id' => 'Item',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'createUser' => 'User',
            'discount' => 'Discount',
            'itemDetail' => 'ItemDetail',
            'order' => 'Order',
            'updatedBy' => 'User',
        ];
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

                $connection = Yii::$app->db;
                $command = $connection->createCommand($sql);
                $command->bindParam(':itemId', $itemId, PDO::PARAM_INT);
                return $command->queryRow();
        }

    public function getTaxArray() {
            $model = $this;
            $json_entry = null;
            if ($model) {
                $order = Order::findOne($this->order_id);
                $json_entry = [];
                $query = OrderItem::find();
                $query->andWhere('order_id =' . $this->order_id);
                $query->andWhere('tax_id =' . $this->tax_id);
                $taxes = $query->all();
                // Yii::warning( var_export( $taxes ), '$ordertaxes');

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

    public function getItemName() {
            $title = '';
            $item_detail = ItemDetail::findOne( $this->item_detail_id );
            if ($item_detail) {
                $item = Item::findOne( $item_detail->item_id );
                if ($item) {
                    $title = $item->title;
                }
            }
            return $title;
        }

    public function getTaxColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
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
                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'bill_no') {
                        $columns [] = [
                                'label' => 'Bill No',
                                'value' => function ($data) {
                                    return isset ( $data->order ) ? $data->order->getOrderBillNo () : "";
                                }
                        ];
                    } else if ($select == 'bar_code') {
                        $columns [] = [
                                'label' => 'Barcode',
                                'value' => function ($data) {
                                    return isset ( $data->itemDetail ) ? $data->itemDetail->bar_code : "";
                                }
                        ];
                    } else if ($select == 'item') {
                        $columns [] = [
                                'label' => 'Item',
                                'value' => function ($data) {
                                    return $data->getItemName ();
                                }
                        ];
                    } else if ($select == 'sale_rate') {
                        $columns [] = [
                                'label' => 'sale_rate',
                                'value' => function ($data) {
                                    return $data->getSaleRate ();
                                }
                        ];
                    } else if ($select == 'tax') {
                        $columns [] = [
                                'label' => 'Tax',
                                'value' => function ($data) {
                                    return isset ( $data->tax ) ? $data->tax->title : "";
                                }
                        ];
                    } else if ($select == 'hrn_code') {
                        $columns [] = [
                                'label' => 'HSN Code',
                                'value' => function ($data) {
                                    return isset ( $data->tax ) ? $data->tax->hrn_code : "";
                                }
                        ];
                    } else if ($select == 'cgst_per') {
                        $columns [] = [
                                'label' => 'CGST(%age)',
                                'value' => function ($data) {
                                    return $data->cgst_per;
                                }
                        ];
                    } else if ($select == 'cgst_amt') {
                        $columns [] = [
                                'label' => 'CGST Amount',
                                'value' => function ($data) {
                                    return $data->cgst_amt;
                                }
                        ];
                    } else if ($select == 'sgst_per') {
                        $columns [] = [
                                'label' => 'SGST(%age)',
                                'value' => function ($data) {
                                    return $data->sgst_per;
                                }
                        ];
                    } else if ($select == 'sgst_amt') {
                        $columns [] = [
                                'label' => 'SGST Amount',
                                'value' => function ($data) {
                                    return $data->sgst_amt;
                                }
                        ];
                    } else if ($select == 'cess_per') {
                        $columns [] = [
                                'label' => 'CESS(%age)',
                                'value' => function ($data) {
                                    return $data->cess_per;
                                }
                        ];
                    } else if ($select == 'cess_amt') {
                        $columns [] = [
                                'label' => 'CESS Amount',
                                'value' => function ($data) {
                                    return $data->cess_amt;
                                }
                        ];
                    }

                    else {
                        $columns [] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getb2bTaxColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
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
                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'order_id') {
                        $columns [] = [
                                'label' => 'Bill No',
                                'value' => function ($data) {
                                return isset ( $data->order ) ? $data->order->getOrderBillNo() : "";
                                }
                                ];
                    }
                    else if ($select == 'customer_id') {
                        $columns [] = [
                                'label' => 'Customer',
                                'value' => function ($data) {
                                return $data->getItemCustomerName();
                                }
                                ];
                    }
                    else if ($select == 'bill_date') {
                        $columns [] = [
                                'label' => 'Bill Date',
                                'value' => function ($data) {
                                return isset ( $data->order ) ? $data->order->bill_date : "";
                                }
                                ];
                    }
                    else if ($select == 'taxable') {
                        $columns [] = [
                                'label' => 'Taxable',
                                'value' => function ($data) {
                                return $data->getTotalItemB2bTaxableAmount ();
                                }
                                ];
                    } else if ($select == 'Gst') {
                        $columns [] = [
                                'label' => 'Gst',
                                'value' => function ($data) {
                                return $data->getB2BOrdertotalgstAmount ();
                                }
                                ];
                    } else if ($select == 'Cgst_per') {
                        $columns [] = [
                                'label' => 'Cgst(%age)',
                                'value' => function ($data) {
                                return $data->cgst_per;
                                }
                                ];
                    } else if ($select == 'Sgst_per') {
                        $columns [] = [
                                'label' => 'Sgst(%age)',
                                'value' => function ($data) {
                                return $data->sgst_per;
                                }
                                ];
                    } else if ($select == 'Cess_per') {
                        $columns [] = [
                                'label' => 'Cess(%age)',
                                'value' => function ($data) {
                                return $data->cess_per;
                                }
                                ];
                    } else if ($select == 'Igst_per') {
                        $columns [] = [
                                'label' => 'Igst(%age)',
                                'value' => function ($data) {
                                return $data->igst_per;
                                }
                                ];
                    } else if ($select == 'Cgst') {
                        $columns [] = [
                                'label' => 'Cgst',
                                'value' => function ($data) {
                                return $data->getB2BGroupTaxCgstAmount ();
                                }
                                ];
                    } else if ($select == 'Sgst') {
                        $columns [] = [
                                'label' => 'Sgst',
                                'value' => function ($data) {
                                return $data->getB2BGroupTaxSgstAmount ();
                                }
                                ];
                    } else if ($select == 'Cess') {
                        $columns [] = [
                                'label' => 'Cess',
                                'value' => function ($data) {
                                return $data->getB2BGroupTaxCessAmount ();
                                }
                                ];
                    } else if ($select == 'Igst') {
                        $columns [] = [
                                'label' => 'Igst',
                                'value' => function ($data) {
                                return $data->getB2BGroupTaxIgstAmount ();
                                }
                                ];
                    } else if ($select == 'Amount') {
                        $columns [] = [
                                'label' => 'Amount',
                                'value' => function ($data) {
                                return $data->getB2BGroupTaxOrderTotalAmount ();
                                }
                                ];
                    }

                    else {
                        $columns [] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getGroupHSNTaxColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
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
                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'item_id') {
                        $columns [] = [
                                'label' => 'Item name',
                                'value' => function ($data) {
                                    return isset($data->item)?$data->item->title:"";
                                }
                        ];
                    } else if ($select == 'hsn_code') {
                        $columns [] = [
                                'label' => 'HSN Code',
                                'value' => function ($data) {
                                    return isset($data->item)?$data->item->hsn_code:"";
                                }
                        ];
                    } else if ($select == 'order_id') {
                        $columns [] = [
                                'label' => 'Bill Date',
                                'value' => function ($data) {
                                    return isset($data->order)?$data->order->bill_date:"";
                                }
                        ];
                    }  else if ($select == 'taxable') {
                        $columns [] = [
                                'label' => 'Taxable',
                                'value' => function ($data) {
                                    return $data->getTotalHsnItemTaxableAmount();
                                }
                        ];
                    } else if ($select == 'mode_of_payment') {
                        $columns [] = [
                                'label' => 'Mode of Payment',
                                'value' => function ($data) {
                                    return isset($data->order)?$data->order->modePayment:"";
                                }
                        ];
                    }else if ($select == 'gst') {
                        $columns [] = [
                                'label' => 'Gst',
                                'value' => function ($data) {
                                    return $data->getOrdertotalHsngstAmount();
                                }
                        ];
                    }else if ($select == 'Cgst_per') {
                        $columns [] = [
                                'label' => 'Cgst(%age)',
                                'value' => function ($data) {
                                    return $data->cgst_per;
                                }
                        ];
                    } else if ($select == 'Sgst_per') {
                        $columns [] = [
                                'label' => 'Sgst(%age)',
                                'value' => function ($data) {
                                    return $data->sgst_per;
                                }
                        ];
                    } else if ($select == 'Cess_per') {
                        $columns [] = [
                                'label' => 'Cess(%age)',
                                'value' => function ($data) {
                                    return $data->cess_per;
                                }
                        ];
                    } else if ($select == 'Igst_per') {
                        $columns [] = [
                                'label' => 'Igst(%age)',
                                'value' => function ($data) {
                                    return $data->igst_per;
                                }
                        ];
                    } else if ($select == 'Cgst') {
                        $columns [] = [
                                'label' => 'Cgst',
                                'value' => function ($data) {
                                    return $data->getGroupHsnTaxCgstAmount ();
                                }
                        ];
                    } else if ($select == 'Sgst') {
                        $columns [] = [
                                'label' => 'Sgst',
                                'value' => function ($data) {
                                    return $data->getGroupHsnTaxSgstAmount ();
                                }
                        ];
                    } else if ($select == 'Cess') {
                        $columns [] = [
                                'label' => 'Cess',
                                'value' => function ($data) {
                                    return $data->getGroupHsnTaxCessAmount ();
                                }
                        ];
                    } else if ($select == 'Igst') {
                        $columns [] = [
                                'label' => 'Igst',
                                'value' => function ($data) {
                                    return $data->getGroupTaxHsnIgstAmount ();
                                }
                        ];
                    } else if ($select == 'Amount') {
                        $columns [] = [
                                'label' => 'Amount',
                                'value' => function ($data) {
                                    return $data->getGroupHsnTaxOrderTotalAmount ();
                                }
                        ];
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
            $array2  = [];
            $val11 = [];
    $csv = [];
    $tank = Tank::findOne($tank_id);
      $a_date = $year."-".$selmonth."-01";
      $date = new DateTime($a_date);
      $date->modify('last day of this month');
      $months = ['01'=>'Jan','02'=>'Feb','03'=>'March','04'=>'April',
        '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
        '09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
      $last = $date->format('d') + 1;
      $nozzles = Nozzle::find()->where(['tank_id'=>$tank->id,'status'=>Nozzle::STATUS_ACTIVE])->all();
      $array1 = [
      'Tank',
      'Month',
      'Year',
                        'Date',
                        'Opening Balance',
                        'Supply',
                        'Total',
                        'Todays Sales',
                        'Closing Balance',
                    ];

      if($nozzles){
                   foreach($nozzles as $nozzle){
                        $val1 [] = $nozzle->nozzle_no.'(Opening)';
                        $val1 [] = $nozzle->nozzle_no.'(Closing)';
                    }}
      if (! empty ( $val1 )) {
                        $array2 = $val1;
                    }
                $array3 = ['Meter Sale','Testing Sale'];

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
     $array11 = [
                $tank->tank_no,
                $months[$month],
                $year,
                        $val,
                        $opening_stock,
                        $vol,
                        $total_vol,
                        $get_sale,
                        number_format($total_vol-$total_sale,'2','.',''),
                    ];
      }else{
         $array11 = [
                $tank->tank_no,
                $months[$month],
                $year,
                        '',
                        '',
                        '',
                        '',
                        '',
                        $opening_stock,
                    ];
      }



       //$val11[] = array("","");
       $val11 = [];
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
                    $array13 = [number_format($main_sale,'2','.',''),$testing_sale];
            $array14 = array_merge($array12,$array13);
                $csv [] = array_merge($array11,$array14);



     }
          $array15 = [
      '',
      '',
      '',
                        '',
                        '',
                        $total_supply,
                        '',
                        $total_today_sale,
                        $diff,
                    ];

      if($nozzles){
                   foreach($nozzles as $nozzle){
                        $val6 [] = '';
                        $val6 [] = '';
                    }}
      if (! empty ( $val6 )) {
                        $array16 = $val6;
                    }
                $array17 = ['',''];

                $array18 = array_merge($array16,$array17);
                $csv [] = array_merge($array15,$array17);
            return $csv;
        }

    public function getGroupTaxColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
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
                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'taxable') {
                        $columns [] = [
                                'label' => 'Taxable',
                                'value' => function ($data) {
                                    return $data->getTotalItemTaxableAmount ();
                                }
                        ];
                    } else if ($select == 'bill_date') {
                        $columns [] = [
                                'label' => 'Bill Date',
                                'value' => function ($data) {
                                    return isset ( $data->order ) ? $data->order->bill_date : "";
                                }
                        ];
                    } else if ($select == 'Gst') {
                        $columns [] = [
                                'label' => 'Gst',
                                'value' => function ($data) {
                                    return $data->getOrderTotalgstAmount ();
                                }
                        ];
                    } else if ($select == 'Cgst_per') {
                        $columns [] = [
                                'label' => 'Cgst(%age)',
                                'value' => function ($data) {
                                    return $data->cgst_per;
                                }
                        ];
                    } else if ($select == 'Sgst_per') {
                        $columns [] = [
                                'label' => 'Sgst(%age)',
                                'value' => function ($data) {
                                    return $data->sgst_per;
                                }
                        ];
                    } else if ($select == 'Cess_per') {
                        $columns [] = [
                                'label' => 'Cess(%age)',
                                'value' => function ($data) {
                                    return $data->cess_per;
                                }
                        ];
                    } else if ($select == 'Igst_per') {
                        $columns [] = [
                                'label' => 'Igst(%age)',
                                'value' => function ($data) {
                                    return $data->igst_per;
                                }
                        ];
                    } else if ($select == 'Cgst') {
                        $columns [] = [
                                'label' => 'Cgst',
                                'value' => function ($data) {
                                    return $data->getGroupTaxCgstAmount ();
                                }
                        ];
                    } else if ($select == 'Sgst') {
                        $columns [] = [
                                'label' => 'Sgst',
                                'value' => function ($data) {
                                    return $data->getGroupTaxSgstAmount ();
                                }
                        ];
                    } else if ($select == 'Cess') {
                        $columns [] = [
                                'label' => 'Cess',
                                'value' => function ($data) {
                                    return $data->getGroupTaxCessAmount ();
                                }
                        ];
                    } else if ($select == 'Igst') {
                        $columns [] = [
                                'label' => 'Igst',
                                'value' => function ($data) {
                                    return $data->getGroupTaxIgstAmount ();
                                }
                        ];
                    } else if ($select == 'Amount') {
                        $columns [] = [
                                'label' => 'Amount',
                                'value' => function ($data) {
                                    return $data->getGroupTaxOrderTotalAmount ();
                                }
                        ];
                    }

                    else {
                        $columns [] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
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
                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'bill_date') {
                        $columns [] = [
                                'label' => 'Bill Date',
                                'value' => function ($data) {
                                    return isset ( $data->order ) ? $data->order->bill_date : "";
                                }
                        ];
                    } else if ($select == 'bill_no') {
                        $columns [] = [
                                'label' => 'Bill No',
                                'value' => function ($data) {
                                    return isset ( $data->order ) ? $data->order->bill_no : "";
                                }
                        ];
                    } else if ($select == 'bar_code') {
                        $columns [] = [
                                'label' => 'Barcode',
                                'value' => function ($data) {
                                    return isset ( $data->itemDetail ) ? $data->itemDetail->bar_code : "";
                                }
                        ];
                    } else if ($select == 'item') {
                        $columns [] = [
                                'label' => 'Item',
                                'value' => function ($data) {
                                    return $data->getItemName ();
                                }
                        ];
                    } else if ($select == 'customer_id') {
                        $columns [] = [
                                'label' => 'Customer',
                                'value' => function ($data) {
                                    return isset ( $data->order ) ? $data->order->customer : "";
                                }
                        ];
                    } else if ($select == 'employee_id') {
                        $columns [] = [
                                'label' => 'Employee',
                                'value' => function ($data) {
                                    return isset ( $data->order ) ? $data->order->createUser : "";
                                }
                        ];
                    } else if ($select == 'refund_qty') {
                        $columns [] = [
                                'label' => 'Refund Quantity',
                                'value' => function ($data) {
                                    return $data->getOrderRefundQty ();
                                }
                        ];
                    } else if ($select == 'mrp') {
                        $columns [] = [
                                'label' => 'Mrp',
                                'value' => function ($data) {
                                    return $data->getItemOrderMrp ();
                                }
                        ];
                    } else if ($select == 'total_amount') {
                        $columns [] = [
                                'label' => 'Total Amount',
                                'value' => function ($data) {
                                    return $data->total_amt;
                                }
                        ];
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
            $item_detail = ItemDetail::findOne( $this->item_detail_id );
            if ($item_detail) {
                $item = Item::findOne( $item_detail->item_id );
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
            $item_detail = ItemDetail::findOne( $this->item_detail_id );
            if ($item_detail) {
                $item = Item::findOne( $item_detail->item_id );
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
            $item_detail = ItemDetail::findOne( $this->item_detail_id );
            if ($item_detail) {
                $item = Item::findOne( $item_detail->item_id );
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
            $item_detail = ItemDetail::findOne( $this->item_detail_id );
            if ($item_detail) {
                $item = Item::findOne( $item_detail->item_id );
                $sale_rate = $item->sale_price;
            }
            return $sale_rate;
        }

    public function getItemwiseColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
                        'item_detail_id',
                        'item_id',
                        'qty',
                        'price',
                        // 'discount_amt',
                        'tax_amt',
                        'amount'
                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'item_detail_id') {
                        $columns [] = [
                                'label' => 'Bar Code',
                                'value' => function ($data) {
                                    return isset ( $data->itemDetail ) ? $data->itemDetail->bar_code : "";
                                }
                        ];
                    }
                    if ($select == 'item_id') {
                        $columns [] = [
                                'label' => 'Item',
                                'value' => function ($data) {
                                    return $data->getItemName ();
                                }
                        ];
                    }
                    if ($select == 'price') {
                        $columns [] = [
                                'label' => 'MRP',
                                'value' => function ($data) {
                                    return $data->getItemOrderMrp ();
                                }
                        ];
                    }

                    if ($select == 'qty') {
                        $columns [] = [
                                'label' => 'Quantity',
                                'value' => function ($data) {
                                    return $data->getItemTotalQty ();
                                }
                        ];
                    } else if ($select == 'amount') {
                        $columns [] = [
                                'label' => 'Total Amount',
                                'value' => function ($data) {
                                    return $data->getItemTotalAmount ();
                                }
                        ];
                    }

                    else {
                        $columns [] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getCgstAmount() {
            $taxAmount = 0;
            if ($this->tax_id != 0) {
                $tax = Tax::findOne( $this->tax_id );
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
                $tax = Tax::findOne( $this->tax_id );
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
                $tax = Tax::findOne( $this->tax_id );
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
                $tax = Tax::findOne( $this->tax_id );
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

    public function getOrdergstAmount() {
            $amount = 0;

            $order = OrderItem::findOne( $this->id );
            if ($order) {
                $cgst = ($order->cgst_amt) + ($order->sgst_amt) + ($order->cess_amt) + ($order->igst_amt);
                $amount = $amount + $cgst;
            }
            return $amount;
        }

    public function getOrderTotalAmount() {
            $amount = 0;
            $order = OrderItem::findOne( $this->id );
            if ($order) {
                // $cgst = $order->getOrdergstAmount()+$order->getOrderTaxableAmount();
                $item_total_amt = $order->total_amt;
                $amount = $amount + $item_total_amt;
            }
            return $amount;
        }

    public function getOrderRefundQty() {
            $qty = 0;
            $orderRefund = OrderRefund::findOne( [
                    'order_id' => $this->order_id
            ] );
            if ($orderRefund) {
                $query = OrderRefundItem::find();
                Criteria::compare($query, 'order_refund_id', $orderRefund->id);
                Criteria::compare($query, 'item_detail_id', $this->item_detail_id);
                Criteria::compare($query, 'item_id', $this->item_id);
                $items = $query->all();
                if ($items) {
                    foreach ( $items as $item ) {
                        $qty = $qty + ($item->qty);
                    }
                }
            }
            return $qty;
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }
}
