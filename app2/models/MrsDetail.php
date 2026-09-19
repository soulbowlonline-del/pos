<?php
namespace app\models;

use app\components\Criteria;
use app\components\Gx;
use app\components\Ui;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * Ported from protected/models/MrsDetail.php and its giix base class.
 */
class MrsDetail extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers below
    // compare them loosely and give the wrong answer for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $vendor_id;

    public const STATUS_PENDING = 0;
    public const STATUS_HALF_DONE = 2;
    public const STATUS_DONE = 1;
    public const STATUS_ASSIGN = 3;
    public const STATUS_REJECT = 4;

    // Declared on the Yii 1 model and not columns: the forms post to
    // these and the actions assign them. Yii 2 throws on an unknown
    // property, so the declarations have to come across.
    public $adjust_qty;
    public $adjust_type_id;
    public $mrs_req_date;
    public $salerate;
    public $item_val_id;
    public $bar_code;

    public static function tableName()
    {
        return '{{%mrs_detail}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'MrsDetail' : 'MrsDetails';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'remarks';
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
        $value = $this->hasAttribute('remarks') ? $this->remarks : null;

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
        return ['item.title' => SORT_ASC];
    }

    /** Views ask the model whether the current role may reach a route. */
    public function checkPermission($url)
    {
        return \app\components\Access::check($url);
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
		$list = ["Pending","Half Done","Done","Assigned","Rejected"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getAdjustTypeOptions($id = null)
    {
		$list = ["Add","Substract"];
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
            [['salerate'], 'safe'],  // form-only, declared on the Yii 1 model
            [['adjust_qty', 'adjust_type_id', 'mrs_req_date', 'vendor_id', 'item_val_id', 'bar_code'], 'safe'],  // form-only, declared on the Yii 1 model
            [['req_qty', 'item_detail_id', 'mrs_id', 'outlet_id'], 'required'],
            [['status', 'type_id', 'create_user_id', 'updated_by', 'item_detail_id', 'mrs_id', 'outlet_id'], 'integer'],
            [['adjust_qty', 'adjust_type_id', 'remarks', 'create_time', 'update_time', 'item_id', 'gross_amt', 'total_discount', 'tax_amount', 'bill_amount', 'mrs_req_date', 'vendor_id', 'mrp', 'sale_rate', 'tax_id', 'discount1', 'discount_amt1', 'discount', 'discount_amt', 'other_charge', 'amount', 'price', 'cgst_per', 'sgst_per', 'cess_per', 'cgst_amt', 'sgst_amt', 'cess_amt', 'igst_per', 'igst_amt', 'margin', 'item_val_id', 'bar_code', 'min_qty'], 'safe'],
            [['approved_qty', 'bal_qty', 'status', 'type_id', 'remarks', 'create_time', 'update_time', 'updated_by'], 'default', 'value' => null],
            [['id', 'req_qty', 'margin', 'approved_qty', 'bal_qty', 'status', 'type_id', 'remarks', 'create_time', 'update_time', 'create_user_id', 'updated_by', 'item_detail_id', 'mrs_id', 'outlet_id'], 'safe', 'on' => 'search'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'req_qty' => 'Max Qty',
            'ai_qty' => 'AI Qty',
            'approved_qty' => 'Approved Qty',
            'bal_qty' => 'Bal Qty',
            'status' => 'Status',
            'type_id' => 'Type',
            'remarks' => 'Remarks',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'item_id' => 'Item',
            'tax_id' => 'Tax',
            'discount' => 'Discount(%)',
            'discount_amt' => 'Discount Amount',
            'discount1' => 'Other Discount(%)',
            'discount_amt1' => 'Other Discount Amount',
            'vendor_id' => 'Vendor',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'item_detail_id' => 'Bar Code',
            'mrs_id' => 'Mrs No',
            'outlet_id' => 'Outlet',
            'createUser' => 'User',
            'itemDetail' => 'Bar Code',
            'mrs' => 'Mrs',
            'outlet' => 'Outlet',
            'updatedBy' => 'User',
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

		$query = self::find()->alias('t');
		$query->joinWith(['item' => function ($q) { $q->alias('item'); }, 'itemDetail' => function ($q) { $q->alias('itemDetail'); }]);
		$query->orderBy(['item.title' => SORT_ASC]);
		$query->select('t.*, item.title as item_title');
		if($this->mrs_req_date != null || $this->vendor_id != null){
			$mrs_ids = array();
		$query1 = Mrs::find();
        $query1->orderBy(['id' => SORT_DESC]);
		if($this->mrs_req_date != null){
		Criteria::compare($query1, 't.mrs_req_date', $this->mrs_req_date);
		}
		if($this->vendor_id != null){
		Criteria::compare($query1, 't.vendor_id', $this->vendor_id);
		}
		$mrss= $query1->all();
		Yii::warning( var_export( $mrss , true), '$mrss');
		if($mrss){
			foreach($mrss as $mrs){
				$mrs_ids[] = $mrs->id;
			}
			
		}
		Yii::warning( var_export( $mrs_ids , true), '$mrs_ids');
		$query->andWhere(['t.mrs_id' => $mrs_ids]);
		}
		$query->andWhere('t.status !='. Mrs::STATUS_DONE);
		$query->andWhere('t.status !='. MrsDetail::STATUS_REJECT);
		Criteria::compare($query, 't.id', $this->id);
		
		Criteria::compare($query, 't.req_qty', $this->req_qty);
		//$criteria->compare('mrs_req_date', $this->mrs_req_date);
		Criteria::compare($query, 't.approved_qty', $this->approved_qty);
		Criteria::compare($query, 't.bal_qty', $this->bal_qty);
		Criteria::compare($query, 't.status', $this->status);
		Criteria::compare($query, 't.type_id', $this->type_id);
		Criteria::compare($query, 't.remarks', $this->remarks, true);
		Criteria::compare($query, 't.create_time', $this->create_time, true);
		Criteria::compare($query, 't.update_time', $this->update_time, true);
		Criteria::compare($query, 't.create_user_id', $this->create_user_id);
		Criteria::compare($query, 't.updated_by', $this->updated_by);
		Criteria::compare($query, 't.item_detail_id', $this->item_detail_id);
		Criteria::compare($query, 't.item_id', $this->item_id);
		Criteria::compare($query, 't.mrs_id', $this->mrs_id);
		Criteria::compare($query, 't.outlet_id', $this->outlet_id);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => false,
		]);
    }

    private function getLatestVelocity($itemId)
      {
                $sql = "
                        SELECT
                                item_id,
                                velocity_change_percent
                        FROM
                                tbl_item_velocity
                        WHERE
                                item_id = :itemId
                        ORDER BY
                                id DESC
                        LIMIT 1
                ";

                $connection = Yii::$app->db;
                $command = $connection->createCommand($sql);
                $command->bindParam(':itemId', $itemId, \PDO::PARAM_INT);
                return $command->queryRow();
        }

    private function calculateAIQty($itemId) {
            // Calculate AI-based reorder quantity using velocity and lead time analysis
            $sql = "
            SELECT
                v.item_id,
                lt.title,
                lt.vendor_name,
                v.daily_velocity,
                lt.avg_lead_time,
                lt.receiving_date,
                lt.mrs_date,
                lt.start_date,
                lt.current_reorder_qty,

                ROUND(v.daily_velocity * lt.avg_lead_time, 2) AS reorder_point,

                ROUND(v.daily_velocity * lt.avg_lead_time * 1.5, 2) AS calculated_reorder_qty,

                ROUND(v.daily_velocity * SQRT(lt.avg_lead_time) * 1.65, 2) AS safety_stock

            FROM
            (
                SELECT
                    sl.item_id,
                    ROUND(SUM(sl.qty) / (DATEDIFF(CURDATE(), DATE_SUB(CURDATE(), INTERVAL 30 DAY)) + 1), 2) AS daily_velocity,
                    COUNT(*) AS transaction_count
                FROM tbl_stock_log sl
                WHERE sl.type_id IN (4,7)  -- Sales and consumption types
                  AND sl.create_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  AND sl.item_id = :item_id
                GROUP BY sl.item_id
                HAVING COUNT(*) >= 2  -- Minimum transactions for reliable calculation
            ) v

            LEFT JOIN
            (
                SELECT
                    md.item_id,
                    AVG(DATEDIFF(pb.start_date, m.mrs_date)) AS avg_lead_time,
                    po.receiving_date,
                    m.mrs_date,
                    i.title,
                    i.reorder_qty AS current_reorder_qty,
                    vv.name AS vendor_name,
                    pb.start_date,
                    COUNT(*) AS procurement_cycles
                FROM tbl_mrs_detail md
                INNER JOIN tbl_mrs m ON m.id = md.mrs_id
                INNER JOIN tbl_mrn n ON n.mrs_id = m.id
                INNER JOIN tbl_purchase_order po ON po.mrn_id = n.id
                INNER JOIN tbl_purchase_bill pb ON po.id = pb.purchase_order_id
                INNER JOIN tbl_item i ON i.id = md.item_id
                INNER JOIN tbl_item_vendor iv ON i.id = iv.item_detail_id
                INNER JOIN tbl_vendor vv ON iv.vendor_id = vv.id
                WHERE pb.start_date IS NOT NULL
                  AND pb.start_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                  AND md.item_id = :item_id
                  AND DATEDIFF(pb.start_date, m.mrs_date) > 0
                GROUP BY md.item_id, i.title, i.reorder_qty, vv.name
                HAVING COUNT(*) >= 1
            ) lt ON v.item_id = lt.item_id

            WHERE v.item_id = :item_id
              AND v.daily_velocity > 0
              AND lt.avg_lead_time > 0
              AND lt.mrs_date != lt.start_date
            LIMIT 1
            ";

            $connection = Yii::$app->db;
            $command = $connection->createCommand($sql);
            $command->bindParam(':item_id', $itemId, \PDO::PARAM_INT);
            $result = $command->queryRow();

            if ($result) {
                // Use calculated reorder quantity with buffer
                $calculatedQty = $result['calculated_reorder_qty'];
                $safetyStock = $result['safety_stock'];
                $currentReorderQty = $result['current_reorder_qty'];

                // Apply business rules
                $finalQty = max($calculatedQty, $safetyStock);

                // Don't drastically change from current reorder qty - max 50% increase/decrease
                if ($currentReorderQty > 0) {
                    $maxChange = $currentReorderQty * 0.5;
                    $maxQty = $currentReorderQty + $maxChange;
                    $minQty = $currentReorderQty - $maxChange;
                    $finalQty = min(max($finalQty, $minQty), $maxQty);
                }

                // Minimum quantity should be at least 1
                $finalQty = max(1, ceil($finalQty));

                // Log the calculation for debugging
                Yii::log("AI Qty calculation for item $itemId: velocity={$result['daily_velocity']}, lead_time={$result['avg_lead_time']}, calculated=$calculatedQty, safety=$safetyStock, final=$finalQty", 'info', 'mrs.ai_qty');

                return $finalQty;
            } else {
                // Fallback: Use historical average or minimum quantity
                return $this->getFallbackQty($itemId);
            }
        }

    private function getFallbackQty($itemId) {
            // Get average MRS quantity for this item in last 6 months
            $sql = "
            SELECT
                AVG(md.req_qty) as avg_req_qty,
                COUNT(*) as mrs_count
            FROM tbl_mrs_detail md
            INNER JOIN tbl_mrs m ON m.id = md.mrs_id
            WHERE md.item_id = :item_id
              AND m.create_time >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              AND md.req_qty > 0
            ";

            $connection = Yii::$app->db;
            $command = $connection->createCommand($sql);
            $command->bindParam(':item_id', $itemId, \PDO::PARAM_INT);
            $result = $command->queryRow();

            if ($result && $result['mrs_count'] >= 2) {
                $avgQty = ceil($result['avg_req_qty']);
                Yii::log("Fallback qty for item $itemId: avg_req_qty=$avgQty from {$result['mrs_count']} MRS records", 'info', 'mrs.fallback_qty');
                return max(1, $avgQty);
            }

            // Final fallback - return minimum quantity of 1
            return 1;
        }

    public function getMrsVendorOptions(){
            $list = [];
            $query = Mrs::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->andWhere('status ='.Mrs::STATUS_PENDING);
            $query->andWhere('Date(create_time) >= DATE_SUB(CURDATE(), INTERVAL 50 DAY)');
            $mrss = $query->all();
            if($mrss){
                foreach($mrss as $mrs){
                    $count = count($mrs->mrsDetails);
                    $create_time = date('d-m-Y',strtotime($mrs->create_time));
                    $vendor = Vendor::findOne($mrs->vendor_id);
                    $amt = '0.00';
                    if($mrs->bill_amount != null){
                        $amt = $mrs->bill_amount;
                    }
                    if($vendor){

                        $list[$vendor->id] = $vendor->name.'('.$create_time.')'.'[ Count- '.$count.']';
                    }
                }
            }
            //Yii::warning( var_export($list, true), '$list1');
            asort($list);
            //Yii::warning( var_export($list, true), '$list2');
            return $list;
        }

    public function getVendorOptions(){
            $list = [];
            $item_vendors = ItemVendor::find()->where(['item_detail_id'=>$this->item_id])->all();
            if($item_vendors){
                foreach($item_vendors as $item_vendor){
                    $vendor = Vendor::findOne($item_vendor->vendor_id);
                    if($vendor){
                        $list[$vendor->id] = $vendor->name;
                    }
                }
            }
            return $list;
        }

    public function getMrsOptions($id = null) {
            $list = [];
            $user = Yii::$app->user->model;
            //$user = User::findOne( $id );
            if ($user) {
                $role_id = $user->role_id;
                $role = UserRole::find()->where(['title'=>'Vendor'])->one();

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = Mrs::find();
            $query->orderBy(['id' => SORT_DESC]);
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status !='.Mrs::STATUS_DONE);
                        $mrslist = $query->all();
                    } else {
                        $query_2 = Mrs::find();
            $query_2->orderBy(['id' => SORT_DESC]);

                        $query_2->andWhere('status !='.Mrs::STATUS_DONE);
                        $mrslist = $query_2->all();
                    }
                    if ($mrslist) {
                        foreach ( $mrslist as $mrs ) {
                            $list [$mrs->id] = $mrs->id;
                        }
                    }
                }
            }
            return $list;
        }

    public function getAllMrsOptions($id = null) {
            $list = [];
            $user = Yii::$app->user->model;
            if ($user) {
                $role_id = $user->role_id;
                $role = UserRole::find()->where(['title'=>'Vendor'])->one();

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = Mrs::find();
            $query->orderBy(['id' => SORT_DESC]);
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status !='.Mrs::STATUS_DONE);
                        $mrslist = $query->all();
                    }else {
                        $query_2 = Mrs::find();
            $query_2->orderBy(['id' => SORT_DESC]);

                        $query_2->andWhere('status !='.Mrs::STATUS_DONE);
                        $mrslist = $query_2->all();
                    }
                    if ($mrslist) {
                        foreach ( $mrslist as $mrs ) {
                            $list [] = $mrs->id;
                        }
                    }
                }
            }
            return $list;
        }

    public function getGstTrue($mrsid){
            $gst = true;
            if($mrsid){
                $mrs = Mrs::find()->where(['id'=>$mrsid])->one();
                if($mrs){
                    $outlet = Outlet::findOne($mrs->outlet_id);
                    Yii::warning( var_export( $outlet->state_id , true), '$outlet->state_id');
                    if($outlet){
                        $vendor = Vendor::findOne($mrs->vendor_id);
                        Yii::warning( var_export( $vendor->state_id , true), '$vendor->state_id');
                        if($vendor->state_id != $outlet->state_id){
                            $gst = false;
                        }
                    }
                }
            }
            return $gst;
        }

    public function getPurchaseAmount(){
            $mrs = Mrs::findOne($this->mrs_id);
            $amount = '0';
            $query1 = PurchaseBillDetail::find();
            $query1->andWhere('t.item_id ='.$this->item_id);
            $query1->select('sum(t.amount) as amount');
            $orderitem = $query1->one();
            if($orderitem){
                $amount = $orderitem->amount;
            }
            if($amount == ''){
                $amount = '0';
            }
            //Yii::warning( var_export( $amount , true), '$amount');
            return $amount;
        }

    public function getSaleAmount(){
            $amount = '0';
            $query1 = OrderItem::find();
            $query1->andWhere('t.item_id ='.$this->item_id);
            $query1->select('sum(t.total_amt) as total_amt');
            $orderitem = $query1->one();
            if($orderitem){
                $amount = $orderitem->total_amt;
            }
            if($amount == ''){
                $amount = '0';
            }
            //Yii::warning( var_export( $amount , true), '$saleamount');
            return $amount;
        }

    public function getPurchaseQty(){
            $mrs = Mrs::findOne($this->mrs_id);
            $qty = '0';
            $query1 = PurchaseBillDetail::find();
            $query1->andWhere('t.item_id ='.$this->item_id);
            $query1->select('sum(t.approved_qty) as approved_qty');
            $orderitem = $query1->one();
            if($orderitem){
                $qty = $orderitem->approved_qty;
            }
            if($qty == ''){
                $qty = '0';
            }
            Yii::warning( var_export( $this->item_id , true), '$this->item_id');
            Yii::warning( var_export( $qty , true), '$purqty');
            return $qty;
        }

    public function getSaleQty(){
            $qty = '0';
            $query1 = OrderItem::find();
            $query1->andWhere('t.item_id ='.$this->item_id);
            $query1->select('sum(t.qty) as qty');
            $orderitem = $query1->one();
            if($orderitem){
                $qty = $orderitem->qty;
            }
            if($qty == ''){
                $qty = '0';
            }
            Yii::warning( var_export( $this->item_id , true), '$this->item_id');
            Yii::warning( var_export( $qty , true), '$saleqty');
            return $qty;
        }

    public static function getReorderAnalysisData($limit = 50, $startDate = null, $endDate = null) {
            if (!$startDate) $startDate = date('Y-m-01', strtotime('-1 month'));
            if (!$endDate) $endDate = date('Y-m-t');

            $sql = "
            SELECT
                v.item_id,
                lt.title,
                lt.vendor_name,
                v.daily_velocity,
                v.transaction_count,
                lt.avg_lead_time,
                lt.procurement_cycles,
                lt.receiving_date,
                lt.mrs_date,
                lt.start_date,
                lt.current_reorder_qty,

                -- Reorder Point = Daily Velocity × Lead Time
                ROUND(v.daily_velocity * lt.avg_lead_time, 2) AS reorder_point,

                -- Reorder Quantity = ROP × 1.5 buffer
                ROUND(v.daily_velocity * lt.avg_lead_time * 1.5, 2) AS calculated_reorder_qty,

                -- Current stock status
                COALESCE(stock.current_stock, 0) AS current_stock,

                -- Status indicators
                CASE
                    WHEN COALESCE(stock.current_stock, 0) <= (v.daily_velocity * lt.avg_lead_time) THEN 'REORDER_NOW'
                    WHEN COALESCE(stock.current_stock, 0) <= (v.daily_velocity * lt.avg_lead_time * 1.2) THEN 'REORDER_SOON'
                    ELSE 'SUFFICIENT'
                END AS stock_status,

                -- Days of stock remaining
                CASE
                    WHEN v.daily_velocity > 0 THEN ROUND(COALESCE(stock.current_stock, 0) / v.daily_velocity, 1)
                    ELSE 999
                END AS days_remaining

            FROM
            (
                -- Velocity Subquery
                SELECT
                    sl.item_id,
                    ROUND(SUM(sl.qty) / (DATEDIFF(:end_date, :start_date) + 1), 2) AS daily_velocity,
                    COUNT(*) AS transaction_count
                FROM tbl_stock_log sl
                WHERE sl.type_id IN (4,7)
                  AND DATE(sl.create_time) BETWEEN :start_date AND :end_date
                  AND sl.qty > 0
                GROUP BY sl.item_id
                HAVING COUNT(*) >= 2
            ) v

            LEFT JOIN
            (
                -- Lead Time Subquery
                SELECT
                    md.item_id,
                    AVG(DATEDIFF(pb.start_date, m.mrs_date)) AS avg_lead_time,
                    po.receiving_date,
                    m.mrs_date,
                    i.title,
                    i.reorder_qty AS current_reorder_qty,
                    vv.name AS vendor_name,
                    pb.start_date,
                    COUNT(*) AS procurement_cycles
                FROM tbl_mrs_detail md
                INNER JOIN tbl_mrs m ON m.id = md.mrs_id
                INNER JOIN tbl_mrn n ON n.mrs_id = m.id
                INNER JOIN tbl_purchase_order po ON po.mrn_id = n.id
                INNER JOIN tbl_purchase_bill pb ON po.id = pb.purchase_order_id
                INNER JOIN tbl_item i ON i.id = md.item_id
                INNER JOIN tbl_item_vendor iv ON i.id = iv.item_detail_id
                INNER JOIN tbl_vendor vv ON iv.vendor_id = vv.id
                WHERE pb.start_date IS NOT NULL
                  AND DATE(pb.start_date) BETWEEN DATE_SUB(:end_date, INTERVAL 90 DAY) AND :end_date
                  AND DATEDIFF(pb.start_date, m.mrs_date) > 0
                GROUP BY md.item_id, i.title, i.reorder_qty, vv.name
                HAVING COUNT(*) >= 1
            ) lt ON v.item_id = lt.item_id

            LEFT JOIN (
                -- Current Stock Subquery
                SELECT
                    i.id as item_id,
                    SUM(ist.qty) as current_stock
                FROM tbl_item i
                LEFT JOIN tbl_item_detail id ON i.id = id.item_id
                LEFT JOIN tbl_item_stock ist ON id.id = ist.item_detail_id
                WHERE i.status = 0
                GROUP BY i.id
            ) stock ON v.item_id = stock.item_id

            WHERE lt.item_id IS NOT NULL
              AND v.daily_velocity > 0
              AND lt.avg_lead_time > 0
              AND lt.mrs_date != lt.start_date

            ORDER BY
                CASE stock_status
                    WHEN 'REORDER_NOW' THEN 1
                    WHEN 'REORDER_SOON' THEN 2
                    ELSE 3
                END,
                days_remaining ASC,
                v.daily_velocity DESC
            LIMIT :limit
            ";

            $connection = Yii::$app->db;
            $command = $connection->createCommand($sql);
            $command->bindParam(':start_date', $startDate, \PDO::PARAM_STR);
            $command->bindParam(':end_date', $endDate, \PDO::PARAM_STR);
            $command->bindParam(':limit', $limit, \PDO::PARAM_INT);

            return $command->queryAll();
        }

    public function getCssClass()
        {
            $cssClass = '';
            $purchase_amount = $this->getPurchaseAmount();
            $sale_amount = $this->getSaleAmount();
            $purchase_qty = $this->getPurchaseQty();
            $per_purchase_qty = (($this->getPurchaseQty()) - (10/100) * ($this->getPurchaseQty()));
            $sale_qty = $this->getSaleQty();
            Yii::warning( var_export( $this , true), '$mrs');
            if($purchase_amount > $sale_amount){
                $cssClass='mrsred';
            }else if($sale_qty > $per_purchase_qty){
            $cssClass='mrsgreen';
           }else if($this->margin < 10){
            $cssClass='mrsorange';
           }else{
               $cssClass='';
           }
           Yii::warning( var_export( $cssClass , true), '$cssClass');
            return $cssClass;
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getMrs()
    {
        return $this->hasOne(Mrs::class, ['id' => 'mrs_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
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
     * Yii 1's pendingsearch(): a listing of its own, converted as written.
     */
    public function pendingsearch()
    {

		$query = self::find();
		
			$mrs_ids = [];
			$query1 = Mrs::find();
        $query1->orderBy(['id' => SORT_DESC]);
			if($this->mrs_req_date != null){
			Criteria::compare($query1, 'mrs_req_date', $this->mrs_req_date);
			}
			Criteria::compare($query1, 'status', Mrs::STATUS_HALF_DONE);
			
			$mrss= $query1->all();
			Yii::warning( var_export( $mrss , true), '$mrss');
			if($mrss){
				foreach($mrss as $mrs){
					$mrs_ids[] = $mrs->id;
				}
					
			}
			$query->andWhere(['mrs_id' => $mrs_ids]);
		
		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'req_qty', $this->req_qty);
		Criteria::compare($query, 'status', MrsDetail::STATUS_HALF_DONE);
		//$criteria->compare('mrs_req_date', $this->mrs_req_date);
		Criteria::compare($query, 'approved_qty', $this->approved_qty);
		Criteria::compare($query, 'bal_qty', $this->bal_qty);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'remarks', $this->remarks, true);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'update_time', $this->update_time, true);
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
		Criteria::compare($query, 'item_detail_id', $this->item_detail_id);
		Criteria::compare($query, 'item_id', $this->item_id);
		Criteria::compare($query, 'mrs_id', $this->mrs_id);
		Criteria::compare($query, 'outlet_id', $this->outlet_id);
	
		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }
}
