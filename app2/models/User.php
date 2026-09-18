<?php
namespace app\models;

use Yii;
use DateTime;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * Ported from protected/models/User.php (Yii 1).
 *
 * Only what the emp API needs is ported: the toArray() payload, the derived
 * fields it exposes, and password validation. The Yii 1 model is far larger and
 * the rest moves with the modules that use it.
 */
class User extends ActiveRecord
{
    public const GENDER_BOTH = 2;
    /** BaseUser's representing column, used by __toString(). */
    public static function representingColumn()
    {
        return 'full_name';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('full_name') ? $this->full_name : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
    }

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_BANNED = -1;
    public const STATUS_REMOVED = -2;

    public const GENDER_MALE = 0;
    public const GENDER_FEMALE = 1;

    public static function tableName()
    {
        return '{{%user}}';
    }

    public function getEmp()
    {
        return $this->hasOne(Emp::class, ['id' => 'emp_id']);
    }

    /**
     * Unsalted MD5, compared loosely - reproduced from User::validate_password()
     * so behaviour is identical while the port is in progress.
     *
     * This is weak: MD5 is unsuitable for passwords, and a loose comparison of
     * two hashes is the classic PHP type-juggling bypass (any stored hash of the
     * form 0e followed only by digits matches any other such hash). No account
     * in this database currently has such a hash, but the scheme should be
     * replaced with password_hash()/password_verify() - a change that has to be
     * made on the Yii 1 side too, since both read the same column.
     */
    public static function validatePassword($passwordUnderTest, $passwordReal)
    {
        return md5($passwordUnderTest) == $passwordReal;
    }

    public static function getGenderOptions($id = null)
    {
        $list = [
            self::GENDER_MALE => 'Male',
            self::GENDER_FEMALE => 'Female',
        ];
        if ($id === null) {
            return $list;
        }
        if (is_numeric($id)) {
            // Yii 1 indexed straight into the array; an out-of-range value
            // produced null (plus a notice). Match the value, drop the notice.
            return isset($list[$id]) ? $list[$id] : null;
        }
        return $id;
    }

    /** Whole years between date_of_birth and today, as GetAge() computed it. */
    public function getAgeYears()
    {
        try {
            $start = new DateTime(date('Y-m-d'));
            $end = new DateTime((string)$this->date_of_birth);
            return $start->diff($end)->y;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * The API payload, reproduced key-for-key from User::toArray().
     *
     * Named toApiArray() rather than toArray(): yii\base\Model implements
     * Arrayable and declares toArray(array $fields, array $expand, $recursive),
     * so an override with the Yii 1 signature is a fatal error. The name was
     * free in Yii 1, which had no such base method.
     *
     * Key order matters: clients and the differential test compare the encoded
     * JSON, and PHP preserves insertion order. outlet_id is only present when
     * the user has an emp record, exactly as in the original.
     */
    public function toApiArray()
    {
        $setting = Setting::findOne(1);
        $defaultImg = 'default.png';

        $json = [];
        // Cast back to string: Yii 1 returned every column as a string, and a
        // client comparing with === or validating a schema would break on an
        // int. Worth revisiting once clients are updated - int is the better
        // type - but not during a port where both stacks serve the same route.
        $json['id'] = (string)$this->id;
        $json['full_name'] = isset($this->full_name) ? $this->full_name : '';
        $json['username'] = $this->username;
        $json['email'] = $this->email;
        $json['phone'] = isset($this->contact_no) ? $this->contact_no : '0';
        $json['date_of_birth'] = isset($this->date_of_birth) ? $this->date_of_birth : '';
        $json['gender_id'] = $this->gender;
        $json['gender'] = self::getGenderOptions($this->gender);
        $json['age'] = $this->getAgeYears();

        if ($this->emp) {
            // Same reason as id/role_id: an int column that Yii 1 served as a
            // string. Any integer column exposed in a ported payload needs this
            // while both frameworks answer the same route.
            $json['outlet_id'] = $this->emp->outlet_id === null
                ? null
                : (string)$this->emp->outlet_id;
        }
        if ($setting) {
            $json['api_key'] = $setting->api_key;
            $json['ivr_username'] = $setting->ivr_username;
        } else {
            $json['api_key'] = '';
            $json['ivr_username'] = '';
        }
        $json['role_id'] = $this->role_id === null ? null : (string)$this->role_id;
        $json['image_file'] = self::downloadUrl(isset($this->image_file) ? $this->image_file : $defaultImg);

        return $json;
    }

    /**
     * Absolute URL for a user image, matching what Yii 1's
     * createAbsoluteUrl('user/download', ['file' => ...]) produced under its
     * 'path' url format. The route stays on the Yii 1 side, so the URL must
     * point at the Yii 1 application, not at /v2.
     */
    private static function downloadUrl($file)
    {
        $req = Yii::$app->request;
        $host = $req->getHostInfo();
        return $host . '/user/download?file=' . urlencode((string)$file);
    }

    /**
     * Firebase push, ported from User::sendGCM(). Goes through the outbound
     * stub when one is configured, exactly as the Yii 1 method does.
     *
     * The server key used to be a literal in protected/models/User.php; it is
     * read from the environment now, but it is in the repository's history and
     * needs rotating. The endpoint itself is the legacy FCM API.
     *
     * Returns nothing on the live path - neither caller reads a result.
     */
    public function sendGCM($registrationIds, $notification)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = [
            'registration_ids' => $registrationIds,
            'data' => $notification,
        ];

        if (class_exists('PosOutbound') && \PosOutbound::isStubbed()) {
            return \PosOutbound::intercept(\PosOutbound::CHANNEL_HTTP, 'POST ' . $url, $fields);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: key=' . getenv('POS_FCM_SERVER_KEY'),
            'Content-Type:application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Yii 1's randomBarcode(): $count random digits. It uses rand(), so the
     * value differs between two runs of the same request - the differential
     * suite normalises the credit-note number for that reason.
     */
    public static function randomBarcode($count = 13)
    {
        $digits = '';
        for ($i = 0; $i < $count; $i++) {
            $digits .= (string)rand(0, 9);
        }
        return $digits;
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'User' : 'Users';
    }

    /** Views ask the model whether the current role may reach a route. */
    public function checkPermission($url)
    {
        return \app\components\Access::check($url);
    }

    /**
     * GxActiveRecord::getRelationLabel(). The generated attributeLabels()
     * above already resolves a relation or foreign key to the related
     * model's label, so this is the attribute label.
     */
    public function getRelationLabel($name, $n = null)
    {
        return $this->getAttributeLabel($name);
    }

    /**
     * The ordering Yii 1's defaultScope() put on every query for this
     * model. Null means Yii 1 applied none, and neither should this:
     * an order Yii 1 never applied is an order the user never saw.
     */
    public static function defaultOrder()
    {
        return ['id' => SORT_DESC];
    }
}
