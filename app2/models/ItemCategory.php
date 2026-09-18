<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemCategory.php (Yii 1). */
class ItemCategory extends ActiveRecord
{
    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public static function tableName()
    {
        return '{{%item_category}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'ItemCategory' : 'ItemCategories';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'title';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('title') ? $this->title : null;

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

    public static function getTypeOptions($id = null)
    {
		$list = ["TYPE1","TYPE2","TYPE3"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getStatusOptions($id = null)
    {
		$list = [
				"Active",
				"Inactive"
		];
		if ($id === null || $id === '')
			return $list;
			if (is_numeric ( $id ))
				return $list [$id];
				return $id;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Category',
            'parent_id' => 'Parent Category',
            'type_id' => 'Type',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'items' => 'Items',
            'updatedBy' => 'Updated By',
            'createUser' => 'Created By',
        ];
    }

    public function getCategoryOptions(){
            $list = [];
            $query = ItemCategory::find();
            $query->andWhere('status ='.ItemCategory::STATUS_ACTIVE);
            $query->orderBy(['title' => SORT_ASC]);
            if($this->id != ''){
                $query->andWhere('id !='.$this->id);
            }
            $categories = $query->all();
            if($categories){
                foreach($categories as $category){
                    $list[$category->id] = $category->title;
                }
            }
            return $list;
        }

    public static function getParentItemCat($parent_id){
            $name = 'Not set';
            $category = ItemCategory::findOne($parent_id);
            if($category){
                $name =  $category->title;
            }
            return $name;
        }

    public function getParentValue($parent_id){
            $name = 'Not set';
            $category = ItemCategory::findOne($parent_id);
            if($category){
                $name =  $category->title;
            }
            return $name;
        }

    public function getDeptWiseColumns($selectcolumns = []){
            if(!empty($selectcolumns)){
                $selected = $selectcolumns;
            }else{
                $selected = [
                        'category' ,
                        'amount',


                ];

            }

            if($selected){
                foreach($selected as $select){
                    if($select == 'category'){
                        $columns[] = [
                                'label' => 'Category',
                                'value' => function ($data) {
                                return isset ( $data->title ) ? $data->title : "";
                                }
                                ];
                    }
                    else if($select == 'amount'){
                        $columns[] =[
                                'label' => 'Net Amount',
                                'value' => function ($data) {
                                return $data->getCategoryTotalAmount ();
                                }
                                ];
                    }

                    else{
                        $columns[] = $select;
                    }
                }
            }




            return $columns;
        }

    public function getCategoryItem_ids(){
            $item_ids = [];
            $query = Item::find();
            $query->andWhere('category_id ='.$this->id);
            $query->andWhere('status ='.Item::STATUS_ACTIVE);
            $items = $query->all();
            if($items){
                foreach($items as $item){
                    $item_ids[] = $item->id;
                }
            }
            Yii::warning( var_export( $this->id ), '$item_category_id');
            Yii::warning( var_export( $item_ids ), '$item_ids');
            return $item_ids;
        }

    public function getItems()
    {
        return $this->hasMany(Item::class, ['category_id' => 'id']);
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
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

    public function getCategoryTotalAmount(){
            $item_ids = $this->getCategoryItem_ids();
            Yii::warning( var_export($item_ids), '$$item_ids');
            $total = 0;
            $query = OrderItem::find();
            $query->andWhere(['item_id' => $item_ids]);
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            Yii::warning( var_export( Yii::$app->session['start_date']), '$start_date');
            Yii::warning( var_export( Yii::$app->session['end_date']), '$end_date');
            $orderitems = $query->all();
            Yii::warning( var_export( $orderitems), '$orderitems');
            if($orderitems){

                foreach ($orderitems as $orderitem){
                    $qty = $orderitem->qty;
                    $refund = 0;
                    $query = OrderRefund::find();
                    $query->andWhere('order_id ='.$orderitem->order_id);
                    if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                    }
                    $orderRefund = $query->one();
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id ='.$orderRefund->id);
                        $query->andWhere('item_detail_id ='.$orderitem->item_detail_id);
                        if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                            $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                        }
                        $query->andWhere('item_id ='.$orderitem->item_id);
                        $query->select('sum(total_amt) as total_amt');
                        $orderRefundItem = $query->one();
                        if($orderRefundItem){
                            $refund = $refund + ($orderRefundItem->total_amt);
                        }
                        /* if($orderRefundItems){
                            foreach($orderRefundItems as $orderRefundItem){
                                $refund = $refund + ($orderRefundItem->total_amt);
                            }


                        } */

                    }
                    $amt = ($orderitem->total_amt) - ($refund);
                    $total = $total + $amt;

                }
            }
            return round($total);
        }
}
