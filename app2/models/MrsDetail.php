<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/MrsDetail.php (Yii 1) - a requisition line. */
class MrsDetail extends ActiveRecord
{
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
}
