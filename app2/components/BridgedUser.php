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
 * Login and logout stay entirely with Yii 1 for now. Yii 2 only observes.
 * When the login flow itself is ported, this component is what gets replaced.
 */
class BridgedUser extends User
{
    /** Must match WebUser::POS_BRIDGE_KEY on the Yii 1 side. */
    public const BRIDGE_KEY = 'pos_bridge_auth';

    public $identityClass = 'app\models\Identity';
    public $enableAutoLogin = false;
    /** Yii 1 owns the session; Yii 2 must not write its own auth keys into it. */
    public $enableSession = false;

    private $_bridged = false;

    /**
     * Resolves the identity from the Yii 1 session rather than Yii 2's own
     * session keys.
     */
    public function getIdentity($autoRenew = true)
    {
        if (!$this->_bridged) {
            $this->_bridged = true;
            $state = $this->readBridge();
            if ($state !== null && isset($state['id'])) {
                $class = $this->identityClass;
                $identity = $class::findIdentity($state['id']);
                if ($identity !== null) {
                    $this->setIdentity($identity);
                }
            }
        }
        return parent::getIdentity($autoRenew);
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
