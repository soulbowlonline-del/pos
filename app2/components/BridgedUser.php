<?php
namespace app\components;

use Yii;
use yii\web\User;

/**
 * Yii 2 user component that takes its login state from the Yii 1 session.
 *
 * During the port both applications serve the same browser session: Yii 1 owns
 * /, Yii 2 owns /v2, and they share PHP's session storage because they run in
 * the same container. Yii 1's WebUser mirrors the logged-in user id under
 * WebUser::POS_BRIDGE_KEY on every request, and this component reads it.
 *
 * Yii 2 now owns a login of its own as well. It keeps its state under Yii 2's
 * own session keys, which are prefixed differently from Yii 1's md5-derived
 * ones and from BRIDGE_KEY, so the two sets sit side by side in the one PHP
 * session without touching each other. The bridge remains, as a fallback: a
 * session established at / is still recognised here.
 *
 * What does not work in the other direction, and is worth knowing: a login
 * performed at /v2 does not sign the user in to Yii 1. Yii 1's WebUser::init()
 * rewrites BRIDGE_KEY on every request and clears it whenever Yii 1 considers
 * itself a guest, so anything Yii 2 wrote there would be erased by the next
 * request to /. Until Yii 1 is retired, / and /v2 are each signed in by their
 * own login, and a login at / covers both.
 */
class BridgedUser extends User
{
    /** Must match WebUser::POS_BRIDGE_KEY on the Yii 1 side. */
    public const BRIDGE_KEY = 'pos_bridge_auth';

    public $identityClass = 'app\models\Identity';
    /**
     * Off, and not an oversight. Identity::getAuthKey() returns null and
     * validateAuthKey() returns false - tbl_user has no column to hold one -
     * so an auto-login cookie could not be validated if one were issued. The
     * visible consequence is that "remember me" does not survive closing the
     * browser on /v2. Giving it one means a schema change.
     */
    public $enableAutoLogin = false;

    private $_bridged = false;

    /**
     * Yii 2's own session first, the Yii 1 bridge second.
     *
     * The order matters. Reading the bridge first would let a stale Yii 1
     * session override a login performed here. setIdentity() rather than
     * switchIdentity() for the bridged case, so that observing Yii 1's state
     * still writes nothing: that session remains Yii 1's to own.
     */
    public function getIdentity($autoRenew = true)
    {
        $identity = parent::getIdentity($autoRenew);
        if ($identity !== null || $this->_bridged) {
            return $identity;
        }

        $this->_bridged = true;
        $state = $this->readBridge();
        if ($state !== null && isset($state['id'])) {
            $class = $this->identityClass;
            $bridged = $class::findIdentity($state['id']);
            if ($bridged !== null) {
                $this->setIdentity($bridged);
                return $bridged;
            }
        }

        return null;
    }

    /**
     * @return array|null the mirrored Yii 1 auth state, or null when not logged in
     */
    public function readBridge()
    {
        if (Yii::$app->has('session')) {
            $session = Yii::$app->getSession();
            if (!$session->getIsActive()) {
                $session->open();
            }
        }
        return isset($_SESSION[self::BRIDGE_KEY]) && is_array($_SESSION[self::BRIDGE_KEY])
            ? $_SESSION[self::BRIDGE_KEY]
            : null;
    }

    /**
     * Yii 1's Yii::app()->user->model - the User row for the signed-in user.
     * Yii 2 calls it the identity, and the views say `model`.
     */
    public function getModel()
    {
        return $this->getIdentity();
    }

    /**
     * Yii 1 put flash messages on the user component; Yii 2 keeps them on the
     * session. The views say Yii::$app->user->hasFlash(...), so the three
     * accessors forward.
     */
    public function hasFlash($key)
    {
        return Yii::$app->session->hasFlash($key);
    }

    public function getFlash($key, $defaultValue = null, $delete = true)
    {
        return Yii::$app->session->getFlash($key, $defaultValue, $delete);
    }

    public function setFlash($key, $value = true)
    {
        Yii::$app->session->setFlash($key, $value);
    }
}
