<?php
namespace app\components;

use app\models\User;

/**
 * Yii 2 port of protected/components/UserIdentity.php.
 *
 * Yii 1's class extended CUserIdentity, which is two things at once: it checks
 * a username and password, and it is the object CWebUser stores. Yii 2 splits
 * those - `yii\web\IdentityInterface` is only the second - so this class keeps
 * the first job and `app\models\Identity` keeps the second. The caller
 * authenticates here, reads `errorCode`, and on success logs in the Identity
 * for the id this resolved.
 *
 * The error codes keep Yii 1's numbers, including the three CUserIdentity
 * defined, because the login controller switches on them.
 *
 * Two behaviours are reproduced rather than corrected:
 *
 *  - A removed account (state_id -2) is left at the code it started with.
 *    Yii 1 writes `else if ($user->state_id == User::STATUS_REMOVED);` - an
 *    empty statement, so the success branch that follows is its `else` and
 *    does not run either. The caller's switch has no case for the starting
 *    code, so such a login fails silently with no message on the form.
 *  - The password is compared as an unsalted MD5, through
 *    User::validate_password(), which is what the stored column holds.
 */
class UserIdentity
{
    /** CUserIdentity's own codes; Yii 2 has no equivalent to inherit. */
    public const ERROR_NONE = 0;
    public const ERROR_USERNAME_INVALID = 1;
    public const ERROR_PASSWORD_INVALID = 2;

    /** UserIdentity's additions, at the numbers Yii 1 gave them. */
    public const ERROR_EMAIL_INVALID = 3;
    public const ERROR_STATUS_INACTIVE = 4;
    public const ERROR_STATUS_BANNED = 5;
    public const ERROR_STATUS_REMOVED = 6;
    public const ERROR_STATUS_USER_DOES_NOT_EXIST = 7;
    public const ERROR_PASSWORD_EXPIRED = 8;

    public $username;
    public $password;
    public $id;
    public $user;

    /** CUserIdentity starts here, and a removed account never leaves it. */
    public $errorCode = self::ERROR_USERNAME_INVALID;

    public function __construct($username = null, $password = null)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Resolves the credentials. The argument is Yii 1's and is not read there
     * either: the lookup tries the email column first and the username column
     * second regardless.
     *
     * @return bool true when errorCode is ERROR_NONE
     */
    public function authenticate($loginByEmail = true)
    {
        $user = User::findOne(['email' => $this->username]);
        if ($user === null) {
            $user = User::findOne(['username' => $this->username]);
        }

        if (!$user) {
            $this->errorCode = self::ERROR_STATUS_USER_DOES_NOT_EXIST;
            return false;
        }

        if (!User::validate_password($this->password, $user->password)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        } elseif ($user->state_id == User::STATUS_INACTIVE) {
            $this->errorCode = self::ERROR_STATUS_INACTIVE;
        } elseif ($user->state_id == User::STATUS_BANNED) {
            $this->errorCode = self::ERROR_STATUS_BANNED;
        } elseif ($user->state_id == User::STATUS_REMOVED) {
            // Left as it is. See the note above: Yii 1's branch is empty.
        } else {
            $this->id = $user->id;
            $this->user = $user;
            $this->username = $user->full_name;
            $this->errorCode = self::ERROR_NONE;
        }

        return !$this->errorCode;
    }

    /**
     * Accepts a user resolved by something other than a password - an auth
     * code carried by the API. No credential is checked here, as in Yii 1.
     */
    public function authenticateSession($user)
    {
        if (!$user) {
            return $this->errorCode = self::ERROR_STATUS_USER_DOES_NOT_EXIST;
        }

        $this->id = $user->id;
        $this->user = $user;
        $this->username = $user->full_name;
        $this->errorCode = self::ERROR_NONE;

        return !$this->errorCode;
    }

    /** @return int|null the id of the user this resolved, or null */
    public function getId()
    {
        return $this->id;
    }
}
