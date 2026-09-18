<?php
namespace app\components;

use app\models\Permission;
use app\models\RolePermission;
use Yii;

/**
 * GxActiveRecord::checkPermission() in Yii 1: is this role allowed to reach
 * this controller/action?
 *
 * The Yii 1 version is a method on every model, which is why views call it as
 * $data->checkPermission('paymentMode/view'). It has nothing to do with the
 * row it is called on, so here it is a static on its own class.
 *
 * Memoised per request for the same reason the Yii 1 one is: the admin layout
 * asks about ninety-five times per page, and without this that is two queries
 * each.
 */
class Access
{
    private static $permIdByUrl = null;
    private static $rolePermSet = [];

    public static function check($url)
    {
        $user = Yii::$app->user->getIdentity();
        if (!$user) {
            return false;
        }
        $roleId = $user->role_id;

        if (self::$permIdByUrl === null) {
            self::$permIdByUrl = [];
            foreach (Permission::find()->all() as $p) {
                self::$permIdByUrl[$p->url] = $p->id;
            }
        }
        if (!isset(self::$rolePermSet[$roleId])) {
            $set = [];
            foreach (RolePermission::find()->where(['role_id' => $roleId])->all() as $rp) {
                $set[$rp->permission_id] = true;
            }
            self::$rolePermSet[$roleId] = $set;
        }

        if (isset(self::$permIdByUrl[$url])) {
            return isset(self::$rolePermSet[$roleId][self::$permIdByUrl[$url]]);
        }
        return false;
    }
}
