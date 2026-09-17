<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Item.php (Yii 1). */
class Item extends ActiveRecord
{
    public const STATUS_ACTIVE = 0;

    public static function tableName()
    {
        return '{{%item}}';
    }

    /** Unit labels, indexed as stored. From BaseItem::getMeasurementTypeOptions(). */
    public static function getMeasurementTypeOptions($id = null)
    {
        $list = [
            'PCS-PIECES',
            'Box',
            'Case',
            'KGS-KILOGRAMS',
            'ML',
            'NOS',
            'PCS',
            'PETI',
            'TIN',
        ];
        if ($id === null) {
            return $list;
        }
        if (is_numeric($id)) {
            // Yii 1 indexed directly; an out-of-range unit yielded null.
            return isset($list[$id]) ? $list[$id] : null;
        }
        return $id;
    }

    public function getCategory()
    {
        return $this->hasOne(ItemCategory::class, ['id' => 'category_id']);
    }

    public function getSubcategory()
    {
        return $this->hasOne(ItemCategory::class, ['id' => 'sub_category_id']);
    }

    public function getCompany()
    {
        return $this->hasOne(ItemCompany::class, ['id' => 'company_id']);
    }
}
