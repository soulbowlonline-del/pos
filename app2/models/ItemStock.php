<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemStock.php (Yii 1). */
class ItemStock extends ActiveRecord
{
    public const TYPE_ADDED = 0;
    public const TYPE_SUBSTRACT = 1;

    public static function tableName()
    {
        return '{{%item_stock}}';
    }

    /**
     * Yii 1's isnetLessMin(): true when the stock of this item detail at this
     * outlet has fallen to or below the item's minimum.
     *
     * The Yii 1 version opens with four queries - an MrnDetail, a
     * PurchaseOrderDetail, and two over purchase bills - whose results it never
     * reads; the code that used them is commented out below the return. They
     * are left out here rather than reproduced: they have no side effects and
     * no effect on the answer, and they run on every line of every order.
     */
    public function isnetLessMin()
    {
        $item = Item::findOne($this->item_id);

        $total = 0;
        $stocks = ItemStock::find()
            ->where([
                'item_detail_id' => $this->item_detail_id,
                'item_id' => $this->item_id,
                'outlet_id' => $this->outlet_id,
            ])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($stocks as $stock) {
            $total = $total + $stock->balance_qty;
        }

        return !($total > $item->min_qty);
    }

    /**
     * Yii 1's createMrs(): raises or tops up a pending requisition for this
     * item, at the first outlet, with the item's last vendor.
     *
     * Two things worth knowing. It overwrites $this->outlet_id with the first
     * outlet before doing anything, so the requisition is always raised against
     * that outlet whatever outlet the stock row belongs to. And the tax lookup
     * reads Item::findOne($this->item_detail_id) - the *Item* table, keyed by
     * an item *detail* id - so it resolves to an unrelated item whenever those
     * ids happen to collide, and to nothing otherwise. Both reproduced.
     */
    public function createMrs()
    {
        $organization = Organization::find()->orderBy(['id' => SORT_ASC])->one();
        $outlet = Outlet::find()->orderBy(['id' => SORT_ASC])->one();
        if ($outlet) {
            $this->outlet_id = $outlet->id;
        }
        $item = Item::findOne($this->item_id);

        $vendorId = null;
        $vendor = ItemVendor::find()
            ->where('item_detail_id = :i', [':i' => $item->id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if ($vendor) {
            $vendorId = $vendor->vendor_id;
        }

        // Item, keyed by an item *detail* id - as in Yii 1. tbl_item has no
        // tax_id column at all, so this reads a property that does not exist.
        // Yii 1 answers null for that rather than throwing, and the branch
        // below therefore finds no tax; Yii 2 would raise "Getting unknown
        // property". hasAttribute() reproduces the Yii 1 answer explicitly.
        $misreadItem = Item::findOne($this->item_detail_id);
        if ($misreadItem) {
            $taxId = $misreadItem->hasAttribute('tax_id') ? $misreadItem->tax_id : null;
            $tax = Tax::findOne($taxId);
        } else {
            $tax = Tax::findOne($this->tax_id);
            $taxId = $this->tax_id;
        }

        if ($vendorId === null) {
            return true;
        }

        $mrs = Mrs::find()
            ->where(['status' => Mrs::STATUS_PENDING, 'vendor_id' => $vendorId, 'outlet_id' => $this->outlet_id])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        $reorderQty = $item->reorder_qty != '' ? $item->reorder_qty : 10;
        $maxQty = $item->max_qty != '' ? $item->max_qty : 10;
        $minQty = $item->min_qty != '' ? $item->min_qty : 10;

        $updated = true;
        if ($mrs === null) {
            $updated = false;
            $mrs = new Mrs();
        }

        $mrs->code = 'ddd';
        $mrs->mrs_date = date('Y-m-d');
        $mrs->mrs_req_date = date('Y-m-d');
        $mrs->outlet_id = $this->outlet_id;
        $mrs->vendor_id = $vendorId;
        $mrs->organization_id = $organization->id;

        if (!$mrs->save()) {
            return true;
        }

        $vendorRow = Vendor::findOne($mrs->vendor_id);
        Notification::AddNotification(
            $mrs->id,
            $updated ? 'MRS is updated' : 'A new MRS is added',
            Notification::TYPE_MRS,
            $vendorRow->create_user_id
        );

        $itemDetail = ItemDetail::findOne($this->item_detail_id);
        $mrsDetail = MrsDetail::find()
            ->where(['item_detail_id' => $this->item_detail_id, 'mrs_id' => $mrs->id])
            ->orderBy(['id' => SORT_ASC])
            ->one();
        if ($mrsDetail === null) {
            $mrsDetail = new MrsDetail();
        }

        $mrsDetail->price = $item->purchase_price;
        $mrsDetail->req_qty = $maxQty;
        $mrsDetail->approved_qty = $reorderQty;
        $mrsDetail->min_qty = $minQty;

        if ($tax) {
            $mrsDetail->cgst_per = $tax->tax_val1;
            $mrsDetail->sgst_per = $tax->tax_val2;
            $mrsDetail->cess_per = $tax->tax_val3;
            $mrsDetail->igst_per = $tax->tax_val4;
            $mrsDetail->cgst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val1 / 100);
            $mrsDetail->sgst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val2 / 100);
            $mrsDetail->cess_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val3 / 100);
            $mrsDetail->igst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val4 / 100);
            $mrsDetail->tax_id = $tax->id;
        }

        $mrsDetail->item_detail_id = $this->item_detail_id;
        $mrsDetail->item_id = $this->item_id;
        $mrsDetail->outlet_id = $mrs->outlet_id;
        $mrsDetail->mrp = $itemDetail->getItemDetailMrp();
        $mrsDetail->sale_rate = $itemDetail->getItemDetailSaleRate();
        $mrsDetail->mrs_id = $mrs->id;

        if ($mrsDetail->getGstTrue($mrs->id) == true) {
            $mrsDetail->amount = ($reorderQty * $mrsDetail->price)
                + $mrsDetail->cgst_amt + $mrsDetail->sgst_amt + $mrsDetail->cess_amt;
            $calculatedGst = ($mrsDetail->price * $mrsDetail->cgst_per) / 100
                + ($mrsDetail->price * $mrsDetail->sgst_per) / 100
                + ($mrsDetail->price * $mrsDetail->cess_per) / 100;
        } else {
            $mrsDetail->amount = ($reorderQty * $mrsDetail->price) + $mrsDetail->igst_amt;
            $calculatedGst = ($mrsDetail->price * $mrsDetail->igst_per) / 100;
        }

        if ($mrsDetail->price != '0.00' && $mrsDetail->price !== null) {
            $mrsDetail->margin = ($mrsDetail->mrp - ($mrsDetail->price + $calculatedGst))
                * 100 / ($mrsDetail->price + $calculatedGst);
        }

        $mrsDetail->save();

        return true;
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'ItemStock' : 'ItemStocks';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'batch_number';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('batch_number') ? $this->batch_number : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
    }

    /**
     * The ordering Yii 1's defaultScope() put on every query for this
     * model. Null means Yii 1 applied none, and neither should this:
     * an order Yii 1 never applied is an order the user never saw.
     */
    public static function defaultOrder()
    {
        return ['id' => SORT_DESC];
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
		$list = ["Add","Substract"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_detail_id' => 'Bar Code',
            'item_id' => 'Item',
            'batch_number' => 'Batch Number',
            'purchase_qty' => 'Qty',
            'base_price' => 'Base Price',
            'mrp' => 'Mrp',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'create_user_id' => 'User',
            'tax_id' => 'Tax',
            'updated_by' => 'User',
            'createUser' => 'User',
            'itemDetail' => 'Bar Code',
            'tax' => 'Tax',
            'updatedBy' => 'User',
        ];
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
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
        return self::defaultOrder();
    }
}
