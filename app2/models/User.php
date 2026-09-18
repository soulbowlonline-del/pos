<?php
namespace app\models;

use yii\helpers\Html;

use app\components\Ui;

use yii\data\ActiveDataProvider;

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
				self::GENDER_FEMALE =>  'Female',
			//	self::GENDER_BOTH =>  'Both',
				 ];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
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

    /**
     * GxActiveRecord::isAllowCreate(): whether the session the operator
     * has selected is the current financial year.
     *
     * The year runs April to March, so a month past April belongs to
     * year..year+1 and anything earlier to year-1..year. Session names
     * are '<from>-<to>'. False when no session is selected, which is what
     * stops the create button appearing.
     */
    public function isAllowCreate()
    {
        $month = (int) date('m');
        $year = $month > 4 ? (int) date('Y') : (int) date('Y') - 1;
        $yearadd = $year + 1;

        $selected = Yii::$app->session['select_session_id'];
        if ($selected === null || $selected === '') {
            return false;
        }

        $session = Session::findOne($selected);
        if ($session === null) {
            return false;
        }
        $parts = explode('-', $session->name);

        return isset($parts[0], $parts[1])
            && $parts[0] == $year && $parts[1] == $yearadd;
    }

    /**
     * GxActiveRecord::getTotals(): the SUM of one column over a set of
     * ids, which the grids use for a footer row.
     *
     * The column and table names are interpolated, as in Yii 1 - the
     * call sites pass literals. The ids are bound, which Yii 1 did not:
     * they come from the data provider rather than the request, so this
     * is not a fix for anything, only a refusal to build the same hole
     * again.
     */
    public function getTotals($ids, $columnname, $tablename)
    {
        if (empty($ids)) {
            return null;
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($ids) as $i => $id) {
            $placeholders[] = ':id' . $i;
            $params[':id' . $i] = $id;
        }

        return Yii::$app->db->createCommand(
            'SELECT SUM(' . $columnname . ') FROM ' . $tablename
            . ' WHERE id IN (' . implode(',', $placeholders) . ')', $params)
            ->queryScalar();
    }

    public static function getAllRoleOptions($id = null)
    {
		$list = [];
		$alreadyroles = [6,7];
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.UserRole::STATUS_ACTIVE);
		$criteria->addNotInCondition('id', $alreadyroles);
		$criteria->order = 'title asc';
		$roles = UserRole::model()->findAll($criteria);
	
		if($roles){
			foreach($roles as $role){
				$list[$role->id] = $role->title;
			}
		}
		return $list;
    }

    public static function getAllUserOptions($id = null)
    {
		$list = [];
		$alreadyroles = [6,7];
		$criteria = new CDbCriteria();
		$criteria->addCondition('state_id = 1');
		$criteria->order = 'full_name asc';
		$users = User::model()->findAll($criteria);
	
		if($users){
			foreach($users as $user){
				$list[$user->id] = $user->full_name;
			}
		}
		return $list;
    }

    public static function getStatusOptions($id = null)
    {
		$list = [
				self::STATUS_INACTIVE => 'Inactive',
				self::STATUS_ACTIVE =>  'Active',
				self::STATUS_BANNED =>  'Banned',
				self::STATUS_REMOVED =>  'Removed' ];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getTypeOptions($id = null)
    {
		$list = ["Type1","Type2"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getMerchantOptions(){
            $userlist = [];
            $users = User::find()->where(['role_id'=>User::ROLE_MERCHANT])->all();
            if($users)
            {
                foreach($users as $user)
                {
                    $userlist[$user->id] = $user->full_name;
                }
            }
            return $userlist;
        }

    public function getStoreOptions(){
            $store_arr = [];
            $string = '';
            $stores = MerchantStore::find()->where(['merchant_id'=>$this->id])->all();
            if($stores)
            {
                foreach($stores as $store)
                {
                    $storedata = Store::findOne($store->store_id);
                    $store_arr[] = $storedata->title;
                }
                if(!empty($store_arr))
                {
                    $string = implode(',',$store_arr);
                }

            }
            return $string;
        }

    public function getAuthSessions()
    {
        return $this->hasMany(AuthSession::class, ['create_user_id' => 'id']);
    }

    public function getCallcenters()
    {
        return $this->hasMany(Callcenter::class, ['user_id' => 'id']);
    }

    public function getCardDetails()
    {
        return $this->hasMany(CardDetails::class, ['user_id' => 'id']);
    }

    public function getDispatchers()
    {
        return $this->hasMany(Dispatcher::class, ['user_id' => 'id']);
    }

    public function getDrivers()
    {
        return $this->hasMany(Driver::class, ['user_id' => 'id']);
    }

    public function getGroups()
    {
        return $this->hasMany(Group::class, ['create_user_id' => 'id']);
    }

    public function getJourneys()
    {
        return $this->hasMany(Journey::class, ['create_user_id' => 'id']);
    }

    public function getNotifications()
    {
        return $this->hasMany(Notification::class, ['create_user_id' => 'id']);
    }

    public function getUserGroups()
    {
        return $this->hasMany(UserGroup::class, ['user_id' => 'id']);
    }

    public function getBar()
    {
        return $this->hasOne(Bar::class, ['create_user_id' => 'id']);
    }

    public function getTrans()
    {
        return $this->hasOne(Transaction::class, ['user_id' => 'id']);
    }

    public function getRole()
    {
        return $this->hasOne(UserRole::class, ['id' => 'role_id']);
    }

    public function getStores()
    {
        return $this->hasMany(MerchantStore::class, ['merchant_id' => 'id']);
    }

    /** GxActiveRecord::getRelatedDataProvider(): the rows of a relation. */
    public function getRelatedDataProvider($relation, $config = [])
    {
        $getter = 'get' . ucfirst($relation);
        if (!method_exists($this, $getter)) {
            throw new \yii\base\InvalidArgumentException(
                get_class($this) . ' does not have relation "' . $relation . '".');
        }

        return new ActiveDataProvider(array_merge(
            ['query' => $this->$getter(), 'pagination' => ['pageSize' => Ui::PAGE_SIZE]],
            $config));
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'full_name' => 'Full Name',
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'password_2' => 'Confirm Password',
            'lat' => 'Lat',
            'long' => 'Long',
            'contact_no' => 'Contact No',
            'date_of_birth' => 'Date Of Birth',
            'store_id' => 'Store',
            'gender' => 'Gender',
            'about_me' => 'About Me',
            'address' => 'Address',
            'postal_code' => 'Postal Code',
            'country' => 'Country',
            'city' => 'City',
            'state' => 'State',
            'session_id' => 'Session',
            'lang' => 'Lang',
            'image_file' => 'Image File',
            'is_passenger' => 'Is Passenger',
            'is_dispatcher' => 'Is Dispatcher',
            'is_driver' => 'Is Driver',
            'role_id' => 'Role',
            'state_id' => 'State',
            'type_id' => 'Type',
            'last_visit_time' => 'Last Visit Time',
            'last_action_time' => 'Last Action Time',
            'last_password_change' => 'Last Password Change',
            'activation_key' => 'Activation Key',
            'is_active' => 'Is Active',
            'login_error_count' => 'Login Error Count',
            'create_time' => 'Create Time',
            'authSessions' => 'AuthSessions',
            'callcenters' => 'Callcenters',
            'cardDetails' => 'CardDetailss',
            'dispatchers' => 'Dispatchers',
            'drivers' => 'Drivers',
            'groups' => 'Groups',
            'journeys' => 'Journeys',
            'notifications' => 'Notifications',
            'userGroups' => 'UserGroups',
        ];
    }

    public function checkPasswordStrength($attribute, $params)
        {
            $password = $this->$attribute;
            $valid = true;
            $valid = $valid && preg_match('/[0-9]/', $password); // digit
            $valid = $valid && preg_match('/\W/', $password); // non-alphanum
            // ... other rules ...
            $valid = $valid && (strlen($password) > 7); // min size
            if ($valid) {
                return true;
            } else {
                $this->addError($attribute, "Not secure enough");
                return false;
            }
        }

    public function GetAge()
        {
            // GetAge(): guarded for PHP 8. date_of_birth is nullable, and passing null
            // to DateTime::__construct() is deprecated from PHP 8.1. Yii 1's error
            // handler escalates any reported error, so on a user with no date of birth
            // this turned /api/emp/deliveryboy into a 500. Casting to string keeps the
            // previous behaviour - null and '' both mean 'now', giving an age of 0 -
            // without emitting the deprecation.
            try {
                $start = new DateTime(date('Y-m-d'));
                $end = new DateTime((string)$this->date_of_birth);
            } catch (Exception $e) {
                return 0;
            }
            $form = $start->diff($end);

            return $form->y;

        }

    public function isActive()
        {
            return ($this->state_id == User::STATUS_ACTIVE);
        }

    public function isOnline()
        {
            return strtotime($this->last_action_time) > time() - $this->offline_indication_time;
        }

    public function isPasswordExpired()
        {
            $distance = self::$password_expiration_day * 24 * 60 * 60;
             $next_change = strtotime($this->last_password_change) + $distance;
             return ($next_change < time());
            return false;
        }

    public static function getUsers()
        {
            $users = User::model()->active()->findAll();
            return $users;
        }

    public static function getUserByEmail($name)
        {
            $user = User::findOne(['email'=>$name]);
            return $user;
        }

    public static function getUserByName($name)
        {
            $user = User::model()->active()->findByAttributes([ 'username'=>$name]);
            return $user;
        }

    public static function getUserById($id)
        {
            $user = User::model()->active()->findByAttributes([ 'id'=>$id]);
            return $user;
        }

    public function register()
        {
            $to      = $this->email;

            $subject = 'Confirm Your Account at: ' . Yii::$app->params['company'];

            $body     = 'Thank you for registering with us. Please click on below given link to activate. ' ."\r\n";
            $body     .= Html::a( 'Activate', $this->getActivationUrl());
            $body     .= $this->getActivationUrl();
            $body     .= ' ' ."\r\n";
            $body     .= 'Thanks' ."\r\n";
            $body     .= 'Admin' ."\r\n";

            $headers = 'From: ' . Yii::$app->params['adminEmail'] . "\r\n" .
                    'Reply-To: ' . Yii::$app->params['adminEmail'] ."\r\n"; // terminator restored: the trailing '.' swallowed the mail() call below
            //    'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            (class_exists('PosOutbound') && PosOutbound::isStubbed())
                    ? PosOutbound::intercept(PosOutbound::CHANNEL_MAIL, $to, ['subject' => $subject, 'body' => $body, 'headers' => $headers])
                    : @mail($to, $subject, $body, $headers);

            if ( YII_ENV == 'dev' && !isset( Yii::$app->controller->module ) ) echo $body;
        }

    public function sendReceiptEmail($journey_model)
        {

            $user_id = $journey_model->passenger_id;

            //$user_model = $this->loadModel($user_id, 'User');
            $user_model = User::findOne($user_id);

            $to      = $user_model->email;
            $subject = 'Smarttaxi - Cash Payment Receipt : ';

            $body     = 'Dear '. $user_model->full_name ."\r\n";
            $body     .=' ' ."\r\n";

            /*
             $latit = $journey_model->from_latitude; //latitude
             $longit = $journey_model->from_longitude; //longitude

             $get_address = $journey_model->getAddress($latit,$longit);
             if($get_address)
             {
             $user_address = $address;
             }
             else
             {
             $user_address =  "Not Known";
             }
             */
            $origin_address = $journey_model->origin_address;
            $destination_address = $journey_model->destination_address;
            $start_time = $journey_model->start_time;
            $amount_paid = $journey_model->amountpaid;

            $body     .= 'This is your receipt of payment  of journey, from '.$origin_address.' to '.$destination_address.' of dated '.$start_time.".\r\n";
            $body     .= ' ' ."\r\n";
            $body     .= 'Paid Amount : ' . $amount_paid . "\r\n";
            $body     .= 'Journey Receipt  No : ' . $journey_model->id . "\r\n";
            $body     .= ' ' ."\r\n";
            $body     .= 'Thanks' ."\r\n";
            $body     .= 'Admin' ."\r\n";

            $headers = 'From: ' . Yii::$app->params['adminEmail'] . "\r\n" .
                    'Reply-To: ' . Yii::$app->params['adminEmail'] ."\r\n"; // terminator restored: the trailing '.' swallowed the mail() call below
            //    'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            (class_exists('PosOutbound') && PosOutbound::isStubbed())
                    ? PosOutbound::intercept(PosOutbound::CHANNEL_MAIL, $to, ['subject' => $subject, 'body' => $body, 'headers' => $headers])
                    : @mail($to, $subject, $body, $headers);

            if ( YII_ENV == 'dev' && !isset( Yii::$app->controller->module ) ) echo $body;

        }

    public static function randomPassword($count = 8) {
            $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
            $alphabet = "abcdefghijklmnopqrstuwxyz0123456789";
            $pass = []; //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < $count; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
            return implode($pass);; //turn the array into a string
        }

    public function getActivationUrl($mode = 'login')
        {
            $this->generateActivationKey();
            return Yii::$app->createAbsoluteUrl('user/activate', [ 'id' => $this->id, 'key' => $this->activation_key, 'mode'=>$mode]);
        }

    public function isUser()
        {
            if($this->role_id == self::ROLE_USER)
            return true;
            return false;
        }

    public function isTribe($id)
        {
            $tribe = Tribe::findOne(['create_user_id'=>$id]);
            if($tribe)
            return true;
            else
            {
                $usertribe = UserTribe::findOne(['user_id'=>$id]);
                if($usertribe)
                return true;
                else
                return false;
            }


        }

    public static function encrypt2($string = "")
        {
            //    include ("pbkdf.php");
            $out = md5($string);
            return $out;
        }

    public static function validate_password($password_under_test, $password_real)
        {

            if(md5($password_under_test)  == $password_real)
            {

                return true;
            }
            return false;

        }

    public function scopes()
        {
            return [
                    'active' => ['condition' => 'state_id=' . self::STATUS_ACTIVE,],
                    'inactive' => ['condition' => 'state_id=' . self::STATUS_INACTIVE,],
                    'banned' => ['condition' => 'state_id=' . self::STATUS_BANNED,],
            ];
        }

    public static function is_favorite($id)
        {
            $favorite = FavoriteUser::findOne(['user_id'=>$id,'create_user_id'=>Yii::$app->user->id]);
            if($favorite)
            return true;
            else
            return false;

        }

    public function getStoreName(){
            $name = '';
            $merchantstore = MerchantStore::findOne(['merchant_id'=>$this->id]);
            if($merchantstore != null )
            {
                $store = Store::findOne($merchantstore->store_id);
                if($store)
                {
                    $name =  $store->title;
                }
            }
            return $name;

        }

    public static function RemoveStore($id)
        {
            MerchantStore::model()->deleteAllByAttributes(['merchant_id'=>$id]);
        }

    public function getSelectedStores(){
            $cat_ids = [];
            $cats = MerchantStore::findAll(['merchant_id'=>$this->id]);
            if($cats != null)
            {
                foreach($cats as $cat)
                {
                    $cat_ids[] = $cat->store_id;
                }
            }

            return $cat_ids;
        }

    public function checkSelectedSession(){

            $query = Session::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->limit(1);
            $latestSession = $query->one();
            if((Yii::$app->session['select_session_id'] != '') && (Yii::$app->session['select_session_id'] != $latestSession->id))
            {
                return false;
            }

            return true;
        }

    /**
     * The order this model's listings use.
     *
     * The grid's own sort when search() names one, otherwise whatever
     * defaultScope() applies. Both the admin grid and the index listing
     * read this, so the two cannot drift apart.
     */
    public static function listingOrder()
    {
        return self::defaultOrder();
    }
}
