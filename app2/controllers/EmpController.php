<?php
namespace app\controllers;

use Yii;
use app\models\User;
use app\models\UserRole;
use yii\web\Controller;
use yii\web\Response;

/**
 * Yii 2 port of protected/modules/api/controllers/EmpController.php.
 *
 * Response envelopes are reproduced exactly - same keys in the same order, the
 * same 'OK'/'NOK' strings, the same message text, and the camelCase Yii 1
 * action id - so clients cannot tell which framework answered.
 *
 * A note on authentication, carried over unchanged: profile, picker and
 * deliveryboy identify the caller from a 'userlogin' request header holding a
 * user id, and nothing verifies it. Any client can pass any id and read that
 * user's profile, or list every employee. That is how the Yii 1 version behaves
 * and this port does not change it, because changing it here while Yii 1 still
 * serves the same routes would only make the two disagree. It should be fixed
 * on both sides together - a signed token, or at minimum checking the id
 * against the session.
 */
class EmpController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    private function envelope($action)
    {
        return [
            'controller' => 'emp',
            'action' => $action,
            'status' => 'NOK',
        ];
    }

    /** The unverified caller id, as getallheaders()['userlogin'] supplied it. */
    private function headerUserId()
    {
        $v = Yii::$app->request->getHeaders()->get('userlogin');
        return ($v === null || $v === '') ? null : $v;
    }

    /**
     * POST /v2/api/emp/login
     *
     * Authenticates and returns the profile. Unlike the Yii 1 action this does
     * not establish a Yii 1 session: the rest of this API identifies callers by
     * the 'userlogin' header rather than the session cookie, and forging Yii 1's
     * internal session keys from here would be a needless auth risk. A client
     * that does depend on the cookie should keep calling /api/emp/login until
     * the login flow itself is ported.
     */
    public function actionLogin()
    {
        $out = $this->envelope('login');
        $req = Yii::$app->request;

        $username = $req->post('username');
        $password = $req->post('password');

        if ($username === null || $password === null) {
            $out['message'] = 'No data posted';
            return $out;
        }

        // Yii 1's UserIdentity tries email first, then username.
        $user = User::findOne(['email' => $username]);
        if ($user === null) {
            $user = User::findOne(['username' => $username]);
        }

        if ($user === null) {
            $out['message'] = 'User does not exist.';
            return $out;
        }
        if (!User::validatePassword($password, $user->password)) {
            $out['message'] = 'Password is incorrect';
            return $out;
        }
        if ((int)$user->state_id === User::STATUS_INACTIVE) {
            $out['message'] = 'This account is not activated.';
            return $out;
        }
        if ((int)$user->state_id === User::STATUS_BANNED) {
            $out['message'] = 'This account is blocked.';
            return $out;
        }

        $deviceId = $req->post('deviceID');
        if ($deviceId !== null && $deviceId !== '') {
            $user->device_token = $deviceId;
            $user->updateAttributes(['device_token']);
        }

        $out['status'] = 'OK';
        $out['message'] = 'you have successfully Login';
        $out['user_profile'] = $user->toApiArray();
        return $out;
    }

    /** POST /v2/api/emp/profile */
    public function actionProfile()
    {
        $out = $this->envelope('profile');

        $loginId = $this->headerUserId();
        if ($loginId) {
            $user = User::findOne($loginId);
            if ($user) {
                $out['status'] = 'OK';
                $out['profile'] = $user->toApiArray();
            }
        }
        return $out;
    }

    /**
     * POST /v2/api/emp/recover
     *
     * Deliberately delegates nothing: sendPassword() on the Yii 1 side resets
     * the account's password to a random value and emails it. Re-implementing a
     * destructive, mail-sending path in a second framework while the first one
     * still serves it invites the two drifting apart, and it cannot be
     * exercised by a comparison test without resetting a real password. The
     * validation branches are ported; the reset itself is refused so a caller
     * that reaches Yii 2 is told plainly to use the Yii 1 route.
     */
    public function actionRecover()
    {
        $out = $this->envelope('recover');

        $email = Yii::$app->request->post('email');
        if ($email === null) {
            return $out;
        }
        if ($email === '') {
            $out['message'] = 'Please enter the email.';
            return $out;
        }

        $user = User::findOne(['email' => $email]);
        if (!$user) {
            $out['message'] = 'Email is not registered';
            return $out;
        }

        $out['message'] = 'Password reset is not available on this endpoint yet; use /api/emp/recover';
        return $out;
    }

    /** POST /v2/api/emp/picker */
    public function actionPicker()
    {
        $out = $this->envelope('picker');

        $loginId = $this->headerUserId();
        if (!$loginId) {
            return $out;
        }

        // The Yii 1 version looks up the Admin role purely as a guard and then
        // ignores it, listing everyone outside roles 1 and 6. Preserved as-is.
        $role = UserRole::findOne(['title' => 'Admin']);
        if (!$role) {
            return $out;
        }

        // Explicit ordering: without it MySQL may return these in any order,
        // and the Yii 1 route is ordered the same way.
        $users = User::find()->where(['not in', 'role_id', ['1', '6']])->orderBy(['id' => SORT_ASC])->all();
        if (!$users) {
            return $out;
        }

        $list = [];
        foreach ($users as $user) {
            $list[] = $user->toApiArray();
        }
        $out['status'] = 'OK';
        $out['pickerlist'] = $list;
        return $out;
    }

    /** POST /v2/api/emp/deliveryboy */
    public function actionDeliveryboy()
    {
        $out = $this->envelope('deliveryboy');

        $loginId = $this->headerUserId();
        if (!$loginId) {
            return $out;
        }

        $role = UserRole::findOne(['title' => 'Delivery Boy']);
        if (!$role) {
            return $out;
        }

        $users = User::find()->where(['role_id' => $role->id])->orderBy(['id' => SORT_ASC])->all();
        if (!$users) {
            return $out;
        }

        $list = [];
        foreach ($users as $user) {
            $list[] = $user->toApiArray();
        }
        $out['status'] = 'OK';
        $out['deliverylist'] = $list;
        return $out;
    }
}
