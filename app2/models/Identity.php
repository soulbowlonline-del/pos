<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Yii 2 identity backed by the same tbl_user the Yii 1 application uses.
 *
 * Authentication itself is not implemented here: while the port is in progress
 * Yii 1 owns login, and this class exists so Yii 2 can resolve who is already
 * logged in. validateAuthKey/getAuthKey are stubs for that reason - nothing in
 * Yii 2 issues cookies yet.
 */
class Identity extends ActiveRecord implements IdentityInterface
{
    public static function tableName()
    {
        return '{{%user}}';
    }

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        // The Yii 1 API module does not use bearer tokens; revisit when that
        // module's authentication is ported.
        return null;
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }
}
