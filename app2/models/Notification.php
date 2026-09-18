<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Notification.php (Yii 1). */
class Notification extends ActiveRecord
{
    // BaseNotification's full set. The layout branches on all four to pick
    // the link a notification points at.
    public const TYPE_MRS = 1;
    public const TYPE_MRN = 2;
    public const TYPE_PO = 3;
    public const TYPE_PBILL = 4;

    public static function tableName()
    {
        return '{{%notification}}';
    }

    /** Yii 1's AddNotification(): saves and ignores the result. */
    public static function AddNotification($modelId, $msg, $type, $toId)
    {
        $notification = new Notification();
        $notification->model_id = $modelId;
        $notification->model_type = $type;
        $notification->to_id = $toId;
        $notification->description = $msg;
        $notification->save();
    }
}
