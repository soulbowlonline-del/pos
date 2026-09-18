<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/Session.php.
 *
 * A trading session - the open/close cycle the POS groups a day's orders
 * into - not a PHP session. The layout uses only the most recent row, to
 * decide whether the operator is still looking at the current session.
 */
class Session extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%session}}';
    }

    /** The newest session row, or null when none exists. */
    public static function latest()
    {
        return self::find()->orderBy(['id' => SORT_DESC])->one();
    }
}
