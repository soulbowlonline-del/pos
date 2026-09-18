<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/MrsDetail.php (Yii 1) - a requisition line. */
class MrsDetail extends ActiveRecord
{
    public const STATUS_REJECT = 4;
    public const STATUS_ASSIGN = 3;
    public const STATUS_DONE = 1;
    public const STATUS_HALF_DONE = 2;
    public const STATUS_PENDING = 0;
    public static function tableName()
    {
        return '{{%mrs_detail}}';
    }

    /**
     * Yii 1's getGstTrue(): false when the vendor and the outlet are in
     * different states, which is what decides IGST against CGST+SGST.
     *
     * The Yii 1 version reads $vendor->state_id without checking $vendor, so a
     * requisition pointing at a missing vendor is a fatal there. Reproduced.
     */
    public function getGstTrue($mrsId)
    {
        if (!$mrsId) {
            return true;
        }
        $mrs = Mrs::findOne(['id' => $mrsId]);
        if (!$mrs) {
            return true;
        }
        $outlet = Outlet::findOne($mrs->outlet_id);
        if (!$outlet) {
            return true;
        }
        $vendor = Vendor::findOne($mrs->vendor_id);
        return $vendor->state_id != $outlet->state_id ? false : true;
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

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('remarks') ? $this->remarks : null;

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
            //Yii::warning( var_export( $list ), '$list1');
            asort($list);
            //Yii::warning( var_export( $list ), '$list2');
            return $list;
        }

    public function getVendorOptions(){
            $list = [];
            $item_vendors = ItemVendor::findAll(['item_detail_id'=>$this->item_id]);
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
                $role = UserRole::findOne(['title'=>'Vendor']);

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = Mrs::find();
            $query->orderBy(['id' => SORT_DESC]);
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status !='.Mrs::STATUS_DONE);
                        $mrslist = $query->all();
                    } else {
                        $query = Mrs::find();
            $query->orderBy(['id' => SORT_DESC]);

                        $query->andWhere('status !='.Mrs::STATUS_DONE);
                        $mrslist = $query->all();
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
                $role = UserRole::findOne(['title'=>'Vendor']);

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = Mrs::find();
            $query->orderBy(['id' => SORT_DESC]);
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status !='.Mrs::STATUS_DONE);
                        $mrslist = $query->all();
                    }else {
                        $query = Mrs::find();
            $query->orderBy(['id' => SORT_DESC]);

                        $query->andWhere('status !='.Mrs::STATUS_DONE);
                        $mrslist = $query->all();
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
                $command->bindParam(':itemId', $itemId, PDO::PARAM_INT);
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
            $command->bindParam(':item_id', $itemId, PDO::PARAM_INT);
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
            $command->bindParam(':item_id', $itemId, PDO::PARAM_INT);
            $result = $command->queryRow();

            if ($result && $result['mrs_count'] >= 2) {
                $avgQty = ceil($result['avg_req_qty']);
                Yii::log("Fallback qty for item $itemId: avg_req_qty=$avgQty from {$result['mrs_count']} MRS records", 'info', 'mrs.fallback_qty');
                return max(1, $avgQty);
            }

            // Final fallback - return minimum quantity of 1
            return 1;
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
            $command->bindParam(':start_date', $startDate, PDO::PARAM_STR);
            $command->bindParam(':end_date', $endDate, PDO::PARAM_STR);
            $command->bindParam(':limit', $limit, PDO::PARAM_INT);

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
            Yii::warning( var_export( $this ), '$mrs');
            if($purchase_amount > $sale_amount){
                $cssClass='mrsred';
            }else if($sale_qty > $per_purchase_qty){
            $cssClass='mrsgreen';
           }else if($this->margin < 10){
            $cssClass='mrsorange';
           }else{
               $cssClass='';
           }
           Yii::warning( var_export( $cssClass ), '$cssClass');
            return $cssClass;
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

    public function getPurchaseAmount(){
            $mrs = Mrs::findOne($this->mrs_id);
            $amount = '0';
            $query = PurchaseBillDetail::find();
            $query->andWhere('t.item_id ='.$this->item_id);
            $query->select('sum(t.amount) as amount');
            $orderitem = $query->one();
            if($orderitem){
                $amount = $orderitem->amount;
            }
            if($amount == ''){
                $amount = '0';
            }
            //Yii::warning( var_export( $amount ), '$amount');
            return $amount;
        }

    public function getSaleAmount(){
            $amount = '0';
            $query = OrderItem::find();
            $query->andWhere('t.item_id ='.$this->item_id);
            $query->select('sum(t.total_amt) as total_amt');
            $orderitem = $query->one();
            if($orderitem){
                $amount = $orderitem->total_amt;
            }
            if($amount == ''){
                $amount = '0';
            }
            //Yii::warning( var_export( $amount ), '$saleamount');
            return $amount;
        }

    public function getPurchaseQty(){
            $mrs = Mrs::findOne($this->mrs_id);
            $qty = '0';
            $query = PurchaseBillDetail::find();
            $query->andWhere('t.item_id ='.$this->item_id);
            $query->select('sum(t.approved_qty) as approved_qty');
            $orderitem = $query->one();
            if($orderitem){
                $qty = $orderitem->approved_qty;
            }
            if($qty == ''){
                $qty = '0';
            }
            Yii::warning( var_export( $this->item_id ), '$this->item_id');
            Yii::warning( var_export( $qty ), '$purqty');
            return $qty;
        }

    public function getSaleQty(){
            $qty = '0';
            $query = OrderItem::find();
            $query->andWhere('t.item_id ='.$this->item_id);
            $query->select('sum(t.qty) as qty');
            $orderitem = $query->one();
            if($orderitem){
                $qty = $orderitem->qty;
            }
            if($qty == ''){
                $qty = '0';
            }
            Yii::warning( var_export( $this->item_id ), '$this->item_id');
            Yii::warning( var_export( $qty ), '$saleqty');
            return $qty;
        }
}
