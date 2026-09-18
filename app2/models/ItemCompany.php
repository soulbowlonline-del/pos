<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemCompany.php (Yii 1). */
class ItemCompany extends ActiveRecord
{
    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public static function tableName()
    {
        return '{{%item_company}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'ItemCompany' : 'ItemCompanies';
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

    public function getCompanyOptions(){
            $list = [];
            $query = ItemCompany::find();
            $query->andWhere('status ='.ItemCompany::STATUS_ACTIVE);
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

    public function getItems()
    {
        return $this->hasMany(Item::class, ['company_id' => 'id']);
    }

    public function getCompanycats()
    {
        return $this->hasMany(ItemCompanyCategory::class, ['company_id' => 'id']);
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
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
            'title' => 'Company',
            'parent_id' => 'Parent Company',
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

    public function getParentValue($parent_id){
            $name = 'Not set';
            $category = ItemCompany::findOne($parent_id);
            if($category){
                $name =  $category->title;
            }
            return $name;
        }

    public static function getParentItemCompany($parent_id){
            $name = 'Not set';
            $category = ItemCompany::findOne($parent_id);
            if($category){
                $name =  $category->title;
            }
            return $name;
        }

    public function getCompWiseColumns($selectcolumns = []){

            if(!empty($selectcolumns)){
                $selected = $selectcolumns;
            }else{
                $selected = [
                        'company' ,
                        'amount',


                ];

            }

            if($selected){
                foreach($selected as $select){
                    if($select == 'company'){
                        $columns[] = [
                                'label' => 'Company',
                                'value' => function ($data) {
                                return isset ( $data->title ) ? $data->title : "";
                                }
                                ];
                    }
                    else if($select == 'amount'){
                        $columns[] =[
                                'label' => 'Net Amount',
                                'value' => function ($data) {
                                return $data->getCompanyTotalAmount ();
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

    public function getCompanyItem_ids(){
            $item_ids = [];
            $query = Item::find();
            $query->andWhere('company_id ='.$this->id);
            $query->andWhere('status ='.Item::STATUS_ACTIVE);
            $items = $query->all();
            if($items){
                foreach($items as $item){
                    $item_ids[] = $item->id;
                }
            }

            return $item_ids;
        }
}
