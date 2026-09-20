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
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $mode_of_payment;
    public $refund_qty;
    public $columns;
    public $start_date;
    public $end_date;
    public $customer_id;
    public $min_amt;
    public $max_amt;
    public $bill_date;

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

    /**
     * GxActiveRecord::__toString(): the representing column's value.
     *
     * Empty when that value is null. Yii 1 falls back to the primary key when
     * representingColumn() itself is empty - which is why 'id' is named above
     * for the models that have no other - and never because the column happens
     * to be null on this row. Falling back on the value put an id in every grid
     * cell where Yii 1 shows nothing.
     */
    public function __toString()
    {
        $value = $this->hasAttribute('create_time') ? $this->create_time : null;

        return $value === null ? '' : (string) $value;
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
        return ['t.id' => SORT_DESC];
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
            'discount_id' => 'Discount Id',
            'discount_amt' => 'Discount Amt',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'item_id' => 'Item',
            'create_user_id' => 'Create User Id',
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
                $command->bindParam(':itemId', $itemId, \PDO::PARAM_INT);
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
                // Yii::warning( var_export( $taxes , true), '$ordertaxes');

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
      $date = new \DateTime($a_date);
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

    public function toArray1($return = 0) {
            $model = $this;
            $json_entry = null;
            if ($model) {
                $refundorderitem = null;
                $query = OrderRefund::find();
                $query->andWhere('order_id =' . $model->order_id);
                $refundorder = $query->one();
                if ($refundorder) {
                    $query = OrderRefundItem::find();
                    $query->andWhere('order_refund_id =' . $refundorder->id);
                    $query->andWhere('item_detail_id =' . $model->item_detail_id);
                    $query->andWhere('item_id =' . $model->item_id);
                    $refundorderitem = $query->one();
                }
                $json_list = [];
                $json_entry = [];
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

    public function getItemQuantity() {
            $order_ids = [];
            $sum = 0;
            $order = Order::findOne( $this->order_id );
            if ($order) {
                $query = Order::find();
                $query->andWhere('customer_id =' . $order->customer_id);
                $orders = $query->all();

                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
                $query = OrderItem::find();
                $query->andWhere(['order_id' => $order_ids]);
                $query->groupBy('item_detail_id');
                // MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
                // explicitly by the grouped columns to preserve the previous output order.
                $query->orderBy(['item_detail_id' => SORT_ASC]);
                $orderitems = $query->all();
                if ($orderitems) {
                    foreach ( $orderitems as $orderitem ) {
                        $sum = $sum + $orderitem->qty;
                    }
                }
            }
            return $sum;
        }

    public function getItemTotalQty() {
            $total = 0;
            $query = OrderItem::find();
            if ((Yii::$app->session ['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session ['item_id']]);
            }
            if ((Yii::$app->session ['start_date'] != '') && (Yii::$app->session ['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['start_date'], Yii::$app->session ['end_date']]);
            }
            Yii::warning( var_export( Yii::$app->session ['start_date'] , true), 'start_date');
            $query->andWhere('item_id =' . $this->item_id);
            $orderitems = $query->all();
            Yii::warning( var_export( $orderitems , true), '$orderitems');
            Yii::warning( var_export( $orderitems , true), '$orderitems');
            if ($orderitems) {

                foreach ( $orderitems as $orderitem ) {
                    $qty = $orderitem->qty;
                    $query = OrderRefund::find();
                    $query->andWhere('order_id =' . $orderitem->order_id);
                    if ((Yii::$app->session ['start_date'] != '') && (Yii::$app->session ['end_date'] != '')) {
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['start_date'], Yii::$app->session ['end_date']]);
                    }
                    $orderRefund = $query->one();
                    if ($orderRefund) {
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id =' . $orderRefund->id);
                        $query->andWhere('item_detail_id =' . $orderitem->item_detail_id);
                        if ((Yii::$app->session ['start_date'] != '') && (Yii::$app->session ['end_date'] != '')) {
                            $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['start_date'], Yii::$app->session ['end_date']]);
                        }
                        $query->andWhere('item_id =' . $orderitem->item_id);
                        $orderRefundItems = $query->all();
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
            $query = OrderItem::find();
            if ((Yii::$app->session ['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session ['item_id']]);
            }
            // $criteria1->compare('order_id', $this->order_id);
            if ((Yii::$app->session ['start_date'] != '') && (Yii::$app->session ['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['start_date'], Yii::$app->session ['end_date']]);
            }
            $query->andWhere('item_id =' . $this->item_id);
            $orderitems = $query->all();
            $refund = 0;
            Yii::warning( var_export( Yii::$app->session ['start_date'] , true), 'start_date');
            Yii::warning( var_export( Yii::$app->session ['end_date'] , true), 'end_date');
            Yii::warning( var_export( Yii::$app->session ['item_id'] , true), 'item_id');
            foreach ( $orderitems as $orderitem ) {
                $qty = $orderitem->qty;
                $refund = 0;
                $query = OrderRefund::find();
                $query->andWhere('order_id =' . $orderitem->order_id);
                if ((Yii::$app->session ['start_date'] != '') && (Yii::$app->session ['end_date'] != '')) {
                    $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['start_date'], Yii::$app->session ['end_date']]);
                }
                $orderRefund = $query->one();
                if ($orderRefund) {
                    $query = OrderRefundItem::find();
                    $query->andWhere('order_refund_id =' . $orderRefund->id);
                    $query->andWhere('item_detail_id =' . $orderitem->item_detail_id);
                    if ((Yii::$app->session ['start_date'] != '') && (Yii::$app->session ['end_date'] != '')) {
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['start_date'], Yii::$app->session ['end_date']]);
                    }
                    $query->andWhere('item_id =' . $orderitem->item_id);
                    $orderRefundItems = $query->all();
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

    public function getTotalHsnItemTaxableAmount() {
            $amount = 0;
            $eamount = 0;
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('item_id =' . $this->item_id);
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(price*qty) as price');
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $query->andWhere(['order_id' => $order_ids]);
            }
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->price;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->select('sum(price*qty) as qty');
            $query->andWhere('item_id =' . $this->item_id);
            $orderRefundItem = $query->one();

            $refund_price = $orderRefundItem->qty;

            $amount = $order_price - $refund_price;

            /*
             * if($this->tax_id == '10'){
             * Yii::warning( var_export( $orderRefundItems , true), '$orderRefundItemsss');
             * Yii::warning( var_export( Yii::$app->session ['order_mode_payment'] , true), 'sessionn');
             * Yii::warning( var_export( $this->create_date , true), '$this->create_date');
             * Yii::warning( var_export( $eamount , true), '$eamount');
             * Yii::warning( var_export( $refund , true), '$eerefund');
             * }
             */
            return $amount;
        }

    public function getTotalItemTaxableAmount() {
            $amount = 0;
            $eamount = 0;
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(price*qty) as price');
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $query->andWhere(['order_id' => $order_ids]);
            }
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->price;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->select('sum(price*qty) as qty');
            $orderRefundItem = $query->one();
            $refund_price = $orderRefundItem->qty;

            $amount = $order_price - $refund_price;

            /*
             * if($this->tax_id == '10'){
             * Yii::warning( var_export( $orderRefundItems , true), '$orderRefundItemsss');
             * Yii::warning( var_export( Yii::$app->session ['order_mode_payment'] , true), 'sessionn');
             * Yii::warning( var_export( $this->create_date , true), '$this->create_date');
             * Yii::warning( var_export( $eamount , true), '$eamount');
             * Yii::warning( var_export( $refund , true), '$eerefund');
             * }
             */
            return $amount;
        }

    public function getItemTaxableAmount() {
            $amount = 0;
            $date = date ( 'Y-m-d', strtotime ( $this->create_time ) );

            $qty = $this->qty;
            $refund = 0;
            $query = OrderRefund::find();
            $query->andWhere('order_id =' . $this->order_id);
            Criteria::compare($query, 'date(create_time)', $date);
            $orderRefund = $query->one();
            if ($orderRefund) {
                $query = OrderRefundItem::find();
                $query->andWhere('order_refund_id =' . $orderRefund->id);
                $query->andWhere('item_detail_id =' . $this->item_detail_id);
                Criteria::compare($query, 'date(create_time)', $date);
                $query->andWhere('item_id =' . $this->item_id);
                $orderRefundItems = $query->all();

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
            $query = OrderItem::find();
            Criteria::compare($query, 'date(create_time)', $date);
            $query->andWhere('order_id =' . $this->order_id);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orders = $query->all();
            if ($orders) {
                foreach ( $orders as $order ) {
                    $qty = $order->qty;
                    $refund = 0;
                    $query = OrderItem::find();
                    $query->andWhere('order_id =' . $order->order_id);
                    Criteria::compare($query, 'date(create_time)', $date);
                    $orderRefund = $query->all();
                    if ($orderRefund) {
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id =' . $orderRefund->id);
                        $query->andWhere('item_detail_id =' . $order->item_detail_id);
                        Criteria::compare($query, 'date(create_time)', $date);
                        $query->andWhere('item_id =' . $order->item_id);
                        $orderRefundItems = $query->all();

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
            $query = OrderItem::find();
            Criteria::compare($query, 'date(create_time)', $date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orders = $query->all();
            if ($orders) {
                foreach ( $orders as $order ) {
                    $qty = $order->qty;
                    $query = OrderItem::find();
                    $query->andWhere('order_id =' . $order->order_id);
                    Criteria::compare($query, 'date(create_time)', $date);
                    $orderRefund = $query->all();
                    if ($orderRefund) {
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id =' . $orderRefund->id);
                        Criteria::compare($query, 'date(create_time)', $date);
                        $query->andWhere('item_detail_id =' . $order->item_detail_id);
                        $query->andWhere('item_id =' . $order->item_id);
                        $orderRefundItems = $query->all();
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
            $query = OrderItem::find();
            Criteria::compare($query, 'date(create_time)', $date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orders = $query->all();
            if ($orders) {
                foreach ( $orders as $order ) {
                    $qty = $order->qty;
                    $query = OrderItem::find();
                    $query->andWhere('order_id =' . $order->order_id);
                    Criteria::compare($query, 'date(create_time)', $date);
                    $orderRefund = $query->all();
                    if ($orderRefund) {
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id =' . $orderRefund->id);
                        $query->andWhere('item_detail_id =' . $order->item_detail_id);
                        Criteria::compare($query, 'date(create_time)', $date);
                        $query->andWhere('item_id =' . $order->item_id);
                        $orderRefundItems = $query->all();
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
            $query = OrderItem::find();
            Criteria::compare($query, 'date(create_time)', $date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orders = $query->all();
            if ($orders) {
                foreach ( $orders as $order ) {
                    $qty = $order->qty;
                    $query = OrderItem::find();
                    $query->andWhere('order_id =' . $order->order_id);
                    Criteria::compare($query, 'date(create_time)', $date);
                    $orderRefund = $query->all();
                    if ($orderRefund) {
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id =' . $orderRefund->id);
                        $query->andWhere('item_detail_id =' . $order->item_detail_id);
                        Criteria::compare($query, 'date(create_time)', $date);
                        $query->andWhere('item_id =' . $order->item_id);
                        $orderRefundItems = $query->all();
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
            $query = OrderItem::find();
            Criteria::compare($query, 'date(create_time)', $date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orders = $query->all();
            if ($orders) {
                foreach ( $orders as $order ) {
                    $qty = $order->qty;
                    $query = OrderItem::find();
                    $query->andWhere('order_id =' . $order->order_id);
                    Criteria::compare($query, 'date(create_time)', $date);
                    $orderRefund = $query->all();
                    if ($orderRefund) {
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id =' . $orderRefund->id);
                        $query->andWhere('item_detail_id =' . $order->item_detail_id);
                        Criteria::compare($query, 'date(create_time)', $date);
                        $query->andWhere('item_id =' . $order->item_id);
                        $orderRefundItems = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }

            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(tax_amount) as tax_amount');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
                $query->andWhere(['order_id' => $order_ids]);
            } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->tax_amount;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            $query->select('sum(tax_amt) as tax_amt');
            $orderRefundItem = $query->one();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }

            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(tax_amount) as tax_amount');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
                $query->andWhere(['order_id' => $order_ids]);
            } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->tax_amount;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->select('sum(tax_amt) as tax_amt');
            $orderRefundItem = $query->one();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(cgst_amt) as cgst_amt');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
             $query->andWhere(['order_id' => $order_ids]);
             } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->cgst_amt;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_id =' . $orderRefund->order_id);
                        $query->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query->andWhere('item_id =' . $orderRefundItem->item_id);
                    $order = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(sgst_amt) as sgst_amt');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
             $query->andWhere(['order_id' => $order_ids]);
             } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->sgst_amt;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_id =' . $orderRefund->order_id);
                        $query->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(igst_amt) as igst_amt');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
             $query->andWhere(['order_id' => $order_ids]);
             } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->igst_amt;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_id =' . $orderRefund->order_id);
                        $query->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(cess_amt) as cess_amt');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
             $query->andWhere(['order_id' => $order_ids]);
             } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->cess_amt;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_id =' . $orderRefund->order_id);
                        $query->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];

                $query = Order::find();
                $query->andWhere('mode_of_payment =' . $this->order->mode_of_payment);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);

            $orders = $query->all();
            if($orders){
                foreach($orders as $order){
                    $oamount = $oamount + ((($order->price) * ($order->qty)) + ($order->tax_amount));
                }
            }


            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){

                foreach($orderRefundItems as $orderRefundItem){
                    $refund = $refund + ((($orderRefundItem->price) * ($orderRefundItem->qty)) + ($orderRefundItem->tax_amt));

                }
            }
            $amount = $oamount - $refund;


            return round ( $amount, 2 );
        }

    public function getGroupHsnTaxCgstAmount() {
            $amount = 0;
            $refund = 0;
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(cgst_amt) as cgst_amt');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
             $query->andWhere(['order_id' => $order_ids]);
             } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->cgst_amt;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_id =' . $orderRefund->order_id);
                        $query->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query->andWhere('item_id =' . $orderRefundItem->item_id);
                    $order = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(sgst_amt) as sgst_amt');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
             $query->andWhere(['order_id' => $order_ids]);
             } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->sgst_amt;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_id =' . $orderRefund->order_id);
                        $query->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(igst_amt) as igst_amt');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
             $query->andWhere(['order_id' => $order_ids]);
             } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->igst_amt;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('item_id =' . $this->item_id);
            $query->andWhere('tax_id =' . $this->tax_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_id =' . $orderRefund->order_id);
                        $query->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];
                $query = Order::find();

                $query->andWhere('mode_of_payment =' . Yii::$app->session ['order_mode_payment']);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(cess_amt) as cess_amt');
            /* if (Yii::$app->session ['order_mode_payment'] != '') {
             $query->andWhere(['order_id' => $order_ids]);
             } */
            // $criteria->addCondition ( 'order_id =' . $this->order_id );
            $order = $query->one();

            $order_price = $order->cess_amt;

            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_id =' . $orderRefund->order_id);
                        $query->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query->all();
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
            if (Yii::$app->session ['order_mode_payment'] != '') {
                $order_ids = [];

                $query = Order::find();
                $query->andWhere('mode_of_payment =' . $this->order->mode_of_payment);

                $orders = $query->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);

            $orders = $query->all();
            if($orders){
                foreach($orders as $order){
                    $oamount = $oamount + ((($order->price) * ($order->qty)) + ($order->tax_amount));
                }
            }


            $query = OrderRefundItem::find();
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere('item_id =' . $this->item_id);
            $orderRefundItems = $query->all();
            if($orderRefundItems){

                foreach($orderRefundItems as $orderRefundItem){
                    $refund = $refund + ((($orderRefundItem->price) * ($orderRefundItem->qty)) + ($orderRefundItem->tax_amt));

                }
            }
            $amount = $oamount - $refund;


            return round ( $amount, 2 );
        }

    /**
     * GxActiveRecord::getCompanyBarcode(): 'readOnly' when the item
     * detail's bar code is the company's own, and an empty string
     * otherwise. The grids use the result as an html attribute, so a
     * barcode belonging to the company cannot be edited in place.
     */
    public function getCompanyBarcode($id)
    {
        $itemDetail = ItemDetail::findOne($id);

        return $itemDetail && $itemDetail->company_bar_code == ItemDetail::IS_COMPANY
            ? 'readOnly'
            : '';
    }

    /**
     * GxActiveRecord::getItemOptions(): the active items, as id => 'title(mrp)',
     * for the item dropdowns.
     *
     * Restricted to a vendor's own items when the signed-in user holds the
     * Vendor role, and again when a vendor id is passed. Both filters compare
     * Item.id against ItemVendor.item_detail_id, which is what Yii 1 does. It
     * reads like a mistake, but it is the list these dropdowns have always
     * shown, so it is ported as it stands rather than corrected here.
     *
     * An empty id list is not "no filter": Yii 1's addInCondition() degrades to
     * 0=1 and ['id' => []] does the same, so a vendor with no items gets an
     * empty dropdown rather than every item in the catalogue.
     */
    public function getItemOptions($vendor_id = null)
    {
        $query = Item::find();

        $role = UserRole::findOne(['title' => 'Vendor']);
        $user = Yii::$app->user->model;
        if ($user && $role && $user->role_id == $role->id) {
            $query->andWhere(['id' => self::vendorItemDetailIds(
                ['create_user_id' => $user->id])]);
        }
        if ($vendor_id !== null) {
            $query->andWhere(['id' => self::vendorItemDetailIds(['id' => $vendor_id])]);
        }
        $query->andWhere('status = ' . Item::STATUS_ACTIVE);
        $query->orderBy('title asc');

        $list = [];
        foreach ($query->all() as $item) {
            $list[$item->id] = $item->title . '(' . $item->mrp . ')';
        }

        return $list;
    }

    /**
     * GxActiveRecord::getItemOptionIdsInBarcode(): the ids of the items an
     * itemDetail admin filter matches, which that grid then filters item_id by.
     *
     * The values are bound rather than interpolated into the condition as Yii 1
     * does. For every value the grid can actually produce the two are the same
     * query; this is not a fix for a reported problem, only a refusal to build
     * the same hole again.
     */
    public function getItemOptionIdsInBarcode($match_item_id, $match_mrp, $match_hsn_code,
        $match_product_code, $match_purchase_price, $match_company_id, $is_vendor)
    {
        $query = Item::find();

        if ($match_item_id != null) {
            $query->andWhere('title LIKE :title', [':title' => trim($match_item_id) . '%']);
        }
        if ($is_vendor == 1) {
            $user = Yii::$app->user->model;
            $query->andWhere(['id' => self::vendorItemDetailIds(
                ['create_user_id' => $user->id])]);
        }
        if ($match_company_id != null) {
            Criteria::compare($query, 'company_id', $match_company_id, true);
        }
        if ($match_mrp != null) {
            $query->andWhere(['mrp' => $match_mrp]);
        }
        if ($match_hsn_code != null) {
            $query->andWhere(['hsn_code' => $match_hsn_code]);
        }
        if ($match_product_code != null) {
            $query->andWhere(['item_code' => $match_product_code]);
        }
        if ($match_purchase_price != null) {
            Criteria::compare($query, 'purchase_price', $match_purchase_price);
        }

        return $query->select('id')->column();
    }

    /** The item_detail_ids ItemVendor holds for the matching vendor. */
    private static function vendorItemDetailIds($condition)
    {
        $vendor = Vendor::findOne($condition);
        if ($vendor === null) {
            return [];
        }

        return ItemVendor::find()->where(['vendor_id' => $vendor->id])
            ->select('item_detail_id')->column();
    }

    public function getTotalItemB2bTaxableAmount() {
            $amount = 0;
            $oamount = 0;
            $refund = 0;
            $order_ids = [];
            $query1 = PaymentMode::find();
            $query1->orderBy(['id' => SORT_DESC]);
                $query1->andWhere('title = "B2B"');

                $paymentmode = $query1->one();

            if ($paymentmode) {


                $query1_2 = Order::find();
                $query1_2->andWhere('mode_of_payment =' . $paymentmode->id);

                $orders = $query1_2->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->select('sum(price*qty) as price');
            $query->andWhere(['order_id' => $order_ids]);
            $order = $query->one();




            $amount = $order->price ;




            $refund_price = '0.00';
            $query3 = OrderRefundItem::find()->alias('t');
            $query3->andWhere('t.tax_id =' . $this->tax_id);
            Criteria::compare($query3, 'date(orderRefund.create_time)', $this->create_date);
            $query3->select('sum(t.price*t.qty) as qty');
            $query3->joinWith(['orderRefund' => function ($q) { $q->alias('orderRefund'); }]);
            $query3->andWhere(['orderRefund.order_id' => $order_ids]);

            $orderRefundItem = $query3->one();
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

            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->select('sum(tax_amount) as tax_amount');
            $query->andWhere('order_id =' . $this->order_id);
            $order = $query->one();

            $order_price = $order->tax_amount;
            $refund_price = '0.00';
            $query3 = OrderRefundItem::find()->alias('t');

            $query3->andWhere('t.tax_id =' . $this->tax_id);
            $query3->select('sum(t.tax_amt) as tax_amt');
            $query3->joinWith(['orderRefund' => function ($q) { $q->alias('orderRefund'); }]);
            $query3->andWhere('orderRefund.order_id ='.$this->order_id);
            $orderRefundItem = $query3->one();
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

            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->select('sum(cgst_amt) as cgst_amt');
            $query->andWhere('order_id =' . $this->order_id);
            $order = $query->one();

            $order_price = $order->cgst_amt;

            $query3 = OrderRefundItem::find()->alias('t');
            $query3->andWhere('t.tax_id =' . $this->tax_id);
            $query3->joinWith(['orderRefund' => function ($q) { $q->alias('orderRefund'); }]);
            $query3->andWhere('orderRefund.order_id ='.$this->order_id);
            $orderRefundItems = $query3->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query3_2 = OrderItem::find();
                        $query3_2->andWhere('order_id =' . $orderRefund->order_id);
                        $query3_2->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query3_2->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query3_2->one();
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

            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->select('sum(sgst_amt) as sgst_amt');
            $query->andWhere('order_id =' . $this->order_id);
            $order = $query->one();

            $order_price = $order->sgst_amt;

            $query3 = OrderRefundItem::find()->alias('t');

            $query3->andWhere('t.tax_id =' . $this->tax_id);
            $query3->joinWith(['orderRefund' => function ($q) { $q->alias('orderRefund'); }]);
            $query3->andWhere('order_id =' . $this->order_id);
            $orderRefundItems = $query3->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query3_2 = OrderItem::find();
                        $query3_2->andWhere('order_id =' . $orderRefund->order_id);
                        $query3_2->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query3_2->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query3_2->one();
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

            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->select('sum(igst_amt) as igst_amt');
            $query->andWhere('order_id =' . $this->order_id);
            $order = $query->one();

            $order_price = $order->igst_amt;

            $query3 = OrderRefundItem::find()->alias('t');

            $query3->andWhere('t.tax_id =' . $this->tax_id);
            $query3->joinWith(['orderRefund' => function ($q) { $q->alias('orderRefund'); }]);
            $query3->andWhere('orderRefund.order_id =' . $this->order_id);
            $orderRefundItems = $query3->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query3_2 = OrderItem::find();
                        $query3_2->andWhere('order_id =' . $orderRefund->order_id);
                        $query3_2->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query3_2->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query3_2->one();
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

            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->select('sum(cess_amt) as cess_amt');
            $query->andWhere('order_id =' . $this->order_id);
            $order = $query->one();

            $order_price = $order->cess_amt;

            $query3 = OrderRefundItem::find()->alias('t');
            $query3->andWhere('t.tax_id =' . $this->tax_id);
            $query3->joinWith(['orderRefund' => function ($q) { $q->alias('orderRefund'); }]);
            $query3->andWhere('orderRefund.order_id =' . $this->order_id);
            $orderRefundItems = $query3->all();
            if($orderRefundItems){
                $refund = 0;
                foreach($orderRefundItems as $orderRefundItem){
                    $orderRefund = OrderRefund::findOne( $orderRefundItem->order_refund_id );
                    if($orderRefund){
                        $query3_2 = OrderItem::find();
                        $query3_2->andWhere('order_id =' . $orderRefund->order_id);
                        $query3_2->andWhere('item_detail_id =' . $orderRefundItem->item_detail_id);
                        $query3_2->andWhere('item_id =' . $orderRefundItem->item_id);
                        $order = $query3_2->one();
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
            $order_ids = [];
            $query1 = PaymentMode::find();
            $query1->orderBy(['id' => SORT_DESC]);
                $query1->andWhere('title = "B2B"');

                $paymentmode = $query1->one();

            if ($paymentmode) {


                $query1_2 = Order::find();
                $query1_2->andWhere('mode_of_payment =' . $paymentmode->id);

                $orders = $query1_2->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            }
            $query = OrderItem::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            Criteria::compare($query, 'date(create_time)', $this->create_date);
            $query->andWhere(['order_id' => $order_ids]);
            $orders = $query->all();



            if($orders){
                foreach($orders as $order){
                    $oamount = $oamount + (($order->total_amt));
                }
            }
            //$amount = $oamount;




            $query3 = OrderRefundItem::find()->alias('t');

            $query3->andWhere('t.tax_id =' . $this->tax_id);
            $query3->joinWith(['orderRefund' => function ($q) { $q->alias('orderRefund'); }]);
                Criteria::compare($query3, 'date(orderRefund.create_time)', $this->create_date);
            $query3->andWhere(['orderRefund.order_id' => $order_ids]);
            $orderRefundItems = $query3->all();
            if($orderRefundItems){

                foreach($orderRefundItems as $orderRefundItem){
                    $refund = $refund + ((($orderRefundItem->price) * ($orderRefundItem->qty)) + ($orderRefundItem->tax_amt));

                }
            }
            $amount = $oamount - $refund;


            return round ( $amount, 2 );
        }

    /**
     * GxActiveRecord::getItemOptionIds(): the ids of the items the signed-in
     * user may see.
     *
     * getItemOptions() filters on status and this does not, because Yii 1
     * does not: the barcode dropdown this feeds lists inactive items too.
     */
    public function getItemOptionIds()
    {
        $query = Item::find();

        $role = UserRole::findOne(['title' => 'Vendor']);
        $user = Yii::$app->user->model;
        if ($user && $role && $user->role_id == $role->id) {
            $query->andWhere(['id' => self::vendorItemDetailIds(
                ['create_user_id' => $user->id])]);
        }

        return $query->select('id')->column();
    }

    /**
     * GxActiveRecord::getItemOptionbarcodes(): item detail id => bar code, for
     * the items getItemOptionIds() allows.
     */
    public function getItemOptionbarcodes()
    {
        $list = [];
        foreach (ItemDetail::find()->where(['item_id' => $this->getItemOptionIds()])
                     ->all() as $itemDetail) {
            $list[$itemDetail->id] = $itemDetail->bar_code;
        }

        return $list;
    }

    /** GxActiveRecord::getItemCustomerName(): the customer on this row's order. */
    public function getItemCustomerName()
    {
        $customer = Customer::findOne($this->order->customer_id);

        return $customer ? $customer->name : '';
    }

    /**
     * GxActiveRecord::getSessionStartDate(): 1 April of the selected session's
     * opening year, or '' when no session is selected.
     */
    public function getSessionStartDate()
    {
        $years = self::selectedSessionYears();

        return isset($years[0]) ? $years[0] . '-04-01' : '';
    }

    /** GxActiveRecord::getSessionEndDate(): 31 March of its closing year. */
    public function getSessionEndDate()
    {
        $years = self::selectedSessionYears();

        return isset($years[1]) ? $years[1] . '-03-31' : '';
    }

    /**
     * The two years in the selected session's name, which is '<from>-<to>'.
     * The financial year runs 1 April to 31 March, which is where the two
     * dates above come from.
     */
    private static function selectedSessionYears()
    {
        $id = Yii::$app->session['select_session_id'];
        if ($id === null || $id === '') {
            return [];
        }
        $session = Session::findOne($id);

        return $session ? explode('-', $session->name) : [];
    }

    /** GxActiveRecord::getVendorDataOptions(): the active vendors, id => name. */
    public function getVendorDataOptions()
    {
        $list = [];
        $query = Vendor::find()->where(['status' => Vendor::STATUS_ACTIVE]);
        // Yii 1 reaches these through findAllByAttributes(), which applies the
        // model's defaultScope; the order is what the dropdown shows.
        $query->orderBy(Vendor::defaultOrder() ?: []);
        foreach ($query->all() as $vendor) {
            $list[$vendor->id] = $vendor->name;
        }

        return $list;
    }

    /**
     * Yii 1's itemsearch(): a listing of its own, converted as written.
     */
    public function itemsearch($id)
    {

		$query = self::find();
	   if($id == null){
	   	$ids = [];
	   	$query->andWhere(['id' => $ids]);
	   }
		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'order_id', $this->order_id);
		Criteria::compare($query, 'item_detail_id', $this->item_detail_id);
		Criteria::compare($query, 'item_id', $this->item_id);
		Criteria::compare($query, 'qty', $this->qty);
		Criteria::compare($query, 'tax_id', $this->tax_id);
		Criteria::compare($query, 'tax_amount', $this->tax_amount);
		Criteria::compare($query, 'price', $this->price);
		Criteria::compare($query, 'discount_id', $this->discount_id);
		Criteria::compare($query, 'discount_amt', $this->discount_amt);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'update_time', $this->update_time, true);
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
	
		$query->orderBy(['id' => SORT_DESC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }

    /**
     * Yii 1's b2bTaxsearch(): a listing of its own, converted as written.
     */
    public function b2bTaxsearch()
    {

	
		$order_ids = [];
		$query1 = Order::find()->alias('t');
		if ((Yii::$app->session ['order_b2b_start_date'] != '') && (Yii::$app->session ['order_b2b_end_date'] != '')) {
			$query1->andWhere(['between', 't.bill_date', Yii::$app->session ['order_b2b_start_date'], Yii::$app->session ['order_b2b_end_date']]);
		}else{
			$query1->andWhere(['between', 't.bill_date', $this->start_date, $this->end_date]);
		}
		if(Yii::$app->session ['order_mode_payment'] != ''){
		$query1->andWhere('t.mode_of_payment ='.Yii::$app->session ['order_mode_payment']);
		}
		$orders = $query1->all();
		if($orders){
			foreach($orders as $order){
				$order_ids[] = $order->id;
			}
		}
	Yii::warning( var_export($order_ids, true), '$order_ids_b2b');
		$query = OrderItem::find()->alias('t');
		$query->andWhere(['order_id' => $order_ids]);
	
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }, 'order' => function ($q) { $q->alias('order'); }]);
		$query->groupBy('t.tax_id,t.create_date');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['t.tax_id' => SORT_ASC, 't.create_date' => SORT_ASC]);
		//$criteria->group = 't.tax_id,t.order_id';
		Criteria::compare($query, 'item.title', $this->item_id, true);
	
		Criteria::compare($query, 'order.bill_no', $this->order_id);
		Criteria::compare($query, 'order.bill_date', $this->bill_date);
		Criteria::compare($query, 'order.customer_id', $this->customer_id);
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
	
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.tax_amount', $this->tax_amount);
		//$criteria->compare ('tax_id', $this->tax_id );
		Criteria::compare($query, 't.date(create_date)', $this->create_date);
		Criteria::compare($query, 't.qty', $this->qty);
		Criteria::compare($query, 't.price', $this->price);
		Criteria::compare($query, 't.discount_id', $this->discount_id);
		Criteria::compare($query, 't.discount_amt', $this->discount_amt);
		
	
		$query->orderBy(['t.tax_id' => SORT_ASC, 't.create_date' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'totalCount' => (clone $query)->select(new \yii\db\Expression('1'))->count(),
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }

    /**
     * Yii 1's groupHSNTaxsearch(): a listing of its own, converted as written.
     */
    public function groupHSNTaxsearch()
    {

	
		$order_ids = [];
		$query1 = Order::find()->alias('t');
		if ((Yii::$app->session ['order_item_start_date'] != '') && (Yii::$app->session ['order_item_end_date'] != '')) {
			$query1->andWhere(['between', 't.bill_date', Yii::$app->session ['order_item_start_date'], Yii::$app->session ['order_item_end_date']]);
		}else{
			$query1->andWhere(['between', 't.bill_date', $this->start_date, $this->end_date]);
		}
		if ( $this->mode_of_payment != null) {
			$query1->andWhere('t.mode_of_payment ='.$this->mode_of_payment);
		}
		Yii::warning( var_export(Yii::$app->session ['order_item_start_date'], true), 'start_date');
		Yii::warning( var_export(Yii::$app->session ['order_item_end_date'], true), 'end_date');
		$orders = $query1->all();
		if($orders){
			foreach($orders as $order){
				$order_ids[] = $order->id;
			}
		}
		
		$query = OrderItem::find()->alias('t');
		$query->andWhere(['order_id' => $order_ids]);
	
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }, 'order' => function ($q) { $q->alias('order'); }]);
		$query->groupBy('t.item_id,t.tax_id,t.create_date');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['t.item_id' => SORT_ASC, 't.tax_id' => SORT_ASC, 't.create_date' => SORT_ASC]);
		Criteria::compare($query, 'item.title', $this->item_id, true);
	
		Criteria::compare($query, 'order.bill_no', $this->order_id);
		Criteria::compare($query, 'order.bill_date', $this->bill_date);
		Criteria::compare($query, 'order.customer_id', $this->customer_id);
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
	
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.tax_amount', $this->tax_amount);
		//$criteria->compare ('tax_id', $this->tax_id );
		Criteria::compare($query, 't.date(create_date)', $this->create_date);
		Criteria::compare($query, 't.qty', $this->qty);
		Criteria::compare($query, 't.price', $this->price);
		Criteria::compare($query, 't.discount_id', $this->discount_id);
		Criteria::compare($query, 't.discount_amt', $this->discount_amt);
	//    $orderItems = OrderItem::model()->findAll($criteria);
	   // Yii::warning( var_export($orderItems, true), '$orderItems');
	   /*   if($orderItems){
	     	$gst = 0;
	     	$cgst = 0;
	     	$sgst = 0;
	     	$cess = 0;
	     	$igst = 0;
	     	$total = 0;
	     	$taxable = 0;
	     	foreach($orderItems as $orderItem){
	     		$gst = $gst + $orderItem->getOrderTotalgstAmount();
	     		$cgst = $cgst + $orderItem->getGroupTaxCgstAmount();
	     		//Yii::warning( var_export($orderItem->getGroupTaxCgstAmount(), true), '$cgst');
	     		$sgst = $sgst + $orderItem->getGroupTaxSgstAmount();
	     		$cess = $cess + $orderItem->getGroupTaxCessAmount();
	     		$igst = $igst + $orderItem->getGroupTaxIgstAmount();
	     		$total = $total + $orderItem->getGroupTaxOrderTotalAmount();
	     		$taxable = $taxable + $orderItem->getTotalItemTaxableAmount();
	     	}
	     	Yii::$app->session ['group_tax_gst'] = number_format($gst,2);
	     	Yii::$app->session ['group_tax_cgst']= number_format($cgst,2);
	     	Yii::$app->session ['group_tax_sgst']= number_format($sgst,2);
	     	Yii::$app->session ['group_tax_cess']= number_format($cess,2);
	     	Yii::$app->session ['group_tax_igst']= number_format($igst,2);
	     	Yii::$app->session ['group_tax_total']=number_format($total,2);
	     	Yii::$app->session ['group_taxable_total']=number_format($taxable,2);
	     } */
	
		$query->orderBy(['t.item_id' => SORT_ASC, 't.tax_id' => SORT_ASC, 't.create_date' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'totalCount' => (clone $query)->select(new \yii\db\Expression('1'))->count(),
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }

    /**
     * Yii 1's CActiveRecord fills a new record with the column defaults
     * declared by the table; Yii 2 leaves them null until asked. Without
     * this a create form shows an empty box where Yii 1 shows 0.00, and
     * an insert writes NULL where Yii 1 writes the default.
     */
    public function init()
    {
        parent::init();

        // Not in the search scenario. Yii 1 loaded the defaults and then
        // the admin action called unsetAttributes() to clear them; a
        // search model that keeps them filters the grid by every column
        // that has a default, which showed 4 rows where Yii 1 shows 11.
        if ($this->isNewRecord && $this->scenario !== 'search') {
            $this->loadDefaultValues();
        }
    }

    /**
     * Port of the base model's beforeValidate(): stamps the row with who
     * created or changed it and when. Yii 1 ran this on every save, so a
     * row written by the port has to carry the same stamps.
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if ($this->isNewRecord) {
            if ($this->hasAttribute('create_time') && !isset($this->create_time)) {
                $this->create_time = date('Y-m-d H:i:s');
            }
            if ($this->hasAttribute('create_user_id') && !isset($this->create_user_id)) {
                $this->create_user_id = Yii::$app->user->id;
            }
        } elseif ($this->hasAttribute('updated_by') && !isset($this->updated_by)) {
            $this->updated_by = Yii::$app->user->id;
        }

        return true;
    }

    public function rules()
    {
        return [
            [['refund_qty', 'bill_date'], 'safe'],  // form-only, declared on the Yii 1 model
            [['mode_of_payment', 'columns', 'start_date', 'end_date', 'customer_id', 'min_amt', 'max_amt'], 'safe'],  // form-only, declared on the Yii 1 model
            [['create_user_id'], 'required'],
            [['order_id', 'item_detail_id', 'discount_id', 'status', 'type_id', 'create_user_id', 'updated_by'], 'integer'],
            [['price', 'discount_amt'], 'number'],
            [['create_time', 'update_time', 'columns', 'order_discount', 'item_id', 'tax_id', 'customer_id', 'mrp', 'total_amt', 'start_date', 'end_date', 'min_amt', 'max_amt', 'create_date', 'req_qty', 'qty', 'cgst_per', 'sgst_per', 'igst_per', 'cess_per', 'cgst_amt', 'sgst_amt', 'cess_amt', 'igst_amt', 'sale_rate', 'total_amt', 'mrp', 'mode_of_payment', 'original_tax', 'start_date', 'end_date', 'customer_id', 'order_id'], 'safe'],
            [['order_id', 'item_detail_id', 'qty', 'price', 'discount_id', 'discount_amt', 'status', 'type_id', 'create_time', 'update_time', 'updated_by'], 'default', 'value' => null],
            [['tax_amount', 'id', 'order_id', 'item_detail_id', 'qty', 'customer_id', 'mrp', 'total_amt', 'price', 'discount_id', 'discount_amt', 'status', 'type_id', 'create_time', 'update_time', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
        ];
    }

    /**
     * Backs the admin grid.
     *
     * The comparison rules are Yii 1's, and there is deliberately no
     * validate() call: the generated search() compares whatever is set and
     * never validates, and a required rule with no `on` clause would
     * otherwise reject every filtered request and return the full list.
     */
    public function search($params = [])
    {
        $this->load($params, $this->formName());

		$order_ids = array();
		if(Yii::$app->session['order_item_item_id'] != ''){
			$this->item_id = Yii::$app->session['order_item_item_id'];
		}
		
		
		if(Yii::$app->session['order_item_customer_id'] != ''){
			$this->customer_id = Yii::$app->session['order_item_customer_id'];
		}
		
		
		if(Yii::$app->session['order_item_create_user_id'] != ''){
			$this->create_user_id = Yii::$app->session['order_item_create_user_id'];
		}
		
		
		Yii::warning( var_export(Yii::$app->session['order_item_item_id'], true), '$orderItems');
		$query1 = Order::find()->alias('t');
		if ((Yii::$app->session ['order_item_start_date'] != '') && (Yii::$app->session ['order_item_end_date'] != '')) {
			$query1->andWhere(['between', 't.bill_date', Yii::$app->session ['order_item_start_date'], Yii::$app->session ['order_item_end_date']]);
		}
		if ((Yii::$app->session ['order_item_min_amt'] != '') && (Yii::$app->session ['order_item_max_amt'] != '')) {
			$query1->andWhere(['between', 't.total_Amt', Yii::$app->session ['order_item_min_amt'], Yii::$app->session ['order_item_max_amt']]);
		}
		
		$orders = $query1->all();
		Yii::warning( var_export(Yii::$app->session['order_item_start_date'], true), '$order_item_start_date');
		Yii::warning( var_export(Yii::$app->session['order_item_end_date'], true), '$order_item_end_date');
		if($orders){
			foreach($orders as $order){
				$order_ids[] = $order->id;
			}
		}
		Yii::warning( var_export($order_ids, true), '$order_ids');
		$query = OrderItem::find()->alias('t');
		$query->andWhere(['t.order_id' => $order_ids]);
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }, 'order' => function ($q) { $q->alias('order'); }]);
		Criteria::compare($query, 'item.title', $this->item_id, true);
		
		Criteria::compare($query, 'order.bill_no', $this->order_id);
		Criteria::compare($query, 'order.customer_id', $this->customer_id);
		Criteria::compare($query, 'order.create_user_id', $this->create_user_id);
		Criteria::compare($query, 'order.total_amt', $this->total_amt);
		Criteria::compare($query, 'item.mrp', $this->mrp);
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
		
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.tax_amount', $this->tax_amount);
		
		Criteria::compare($query, 't.qty', $this->qty);
		Criteria::compare($query, 't.price', $this->price);
		Criteria::compare($query, 't.discount_id', $this->discount_id);
		Criteria::compare($query, 't.discount_amt', $this->discount_amt);
	
		

		$query->orderBy(['t.id' => SORT_DESC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 100],
		]);
    }

    private function updateItemVelocity($itemId, $velocityChangePercent)
        {
            $sql = "
                    INSERT INTO tbl_item_velocity (item_id, velocity_change_percent, update_time)
                    VALUES (:itemId, :velocityChangePercent, NOW())
            ";

                $connection = Yii::$app->db;
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

    /**
     * Yii 1's itemwisesearch(): a listing of its own, converted as written.
     */
    public function itemwisesearch()
    {

		$query = OrderItem::find()->alias('t');

		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }, 'order' => function ($q) { $q->alias('order'); }], true, 'INNER JOIN');
		$query->groupBy('item_detail_id');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['item_detail_id' => SORT_ASC]);
		
		if ((Yii::$app->session ['start_date'] != '') && (Yii::$app->session ['end_date'] != '')) {
			$order_ids = [];
			$query1 = Order::find()->alias('t');
			$query1->andWhere(['between', 't.bill_date', Yii::$app->session ['start_date'], Yii::$app->session ['end_date']]);
			$orders = $query1->all();
			if($orders){
				foreach($orders as $order){
					$order_ids[] = $order->id;
				}
			}
			$query->andWhere(['t.order_id' => $order_ids]);
			
		}
		
		
		
	
		if(!empty(Yii::$app->session['item_id'])){
			$query->andWhere(['item.id' => Yii::$app->session['item_id']]);
		}else{
			Criteria::compare($query, 'item.title', $this->item_id, true);
		}
		Criteria::compare($query, 'order.customer_id', $this->customer_id);
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);

		Criteria::compare($query, 't.tax_amount', $this->tax_amount, true);
		Criteria::compare($query, 't.order_id', $this->order_id);
		Criteria::compare($query, 't.tax_id', $this->tax_id);
	
		Criteria::compare($query, 't.qty', $this->qty);
		Criteria::compare($query, 't.price', $this->price, true);

		Criteria::compare($query, 't.discount_amt', $this->discount_amt, true);

		/* $orderitems = OrderItem::model()->findAll($criteria);
		if($orderitems){
		$total = 0;
			foreach($orderitems as $orderitem){
				
				$total = $total + $orderitem->getItemTotalAmount();
			}
		Yii::$app->session ['itemwise_total_amt']=number_format($total,2);
		} */
		$query->orderBy(['item_detail_id' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'totalCount' => (clone $query)->select(new \yii\db\Expression('1'))->count(),
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }
}
