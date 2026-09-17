<?php


/**
 * @property integer $id
 * @property string $full_name
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string $lat
 * @property string $long
 * @property string $contact_no
 * @property string $date_of_birth
 * @property string $gender
 * @property string $about_me
 * @property string $address
 * @property integer $postal_code
 * @property string $country
 * @property string $city
 * @property string $state
 * @property string $lang
 * @property string $image_file
 * @property integer $is_passenger
 * @property integer $is_dispatcher
 * @property integer $is_driver
 * @property integer $role_id
 * @property integer $state_id
 * @property integer $type_id
 * @property string $last_visit_time
 * @property string $last_action_time
 * @property string $last_password_change
 * @property string $activation_key
 * @property integer $is_active
 * @property integer $login_error_count
 * @property integer $create_user_id
 * @property string $create_time
 */
Yii::import('application.models._base.BaseUser');

//include ("pbkdf.php");
class User extends BaseUser
{
	public static $password_expiration_day = 15;
	protected static $salt1 = "rome" ;
	protected static $hashFunc='md5' ;
	public $offline_indication_time = 3600; // 5 Minutes
	public $password_3;
	public $password_2;

	public static function model($className=__CLASS__) {
		return parent::model($className);
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


	function sendGCM($registatoin_ids, $notification) {
		$url = 'https://fcm.googleapis.com/fcm/send';
		if (class_exists('PosOutbound') && PosOutbound::isStubbed()) {
			return PosOutbound::intercept(
				PosOutbound::CHANNEL_HTTP, 'POST ' . $url,
				array('registration_ids' => $registatoin_ids, 'data' => $notification)
			);
		}
			$fields = array(
				//	'to' => $recipient,
        'registration_ids' => $registatoin_ids,
					'data' => $notification
			);
			Yii::log ( CVarDumper::dumpAsString ( $fields ), CLogger::LEVEL_WARNING, '$$fields');				
		// Firebase API Key
		$headers = array('Authorization: key=' . getenv('POS_FCM_SERVER_KEY'),'Content-Type:application/json');
		// Open connection
		$ch = curl_init();
		// Set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Disabling SSL Certificate support temporarly
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);
		//if ($result === FALSE) {
			Yii::log ( CVarDumper::dumpAsString ( $result ), CLogger::LEVEL_WARNING, '$resultnotif' );
			
			//die('Curl failed: ' . curl_error($ch));
		//}
		curl_close($ch);
		
		}
		
		
	
	public function logout()
	{
		if ( !Yii::app()->user->isGuest) {
			$this->last_action_time = date( 'Y-m-d H:i:s');
			$this->saveAttributes(array('last_action_time'));
		}
	}
	public function after_login()
	{
		$driver = Driver::model()->findByAttributes(array('user_id'=>Yii::app()->user->id));
		if(!$driver)
		{
			$driver = new Driver();
			$driver->user_id = Yii::app()->user->id;
			$driver->no_of_riders = 8;
			$driver->rider_rating = 0;
			$driver->receive_notification = 0;
			$driver->rider_reviews = 0;
			$driver->sex = 2;
			$driver->distance_from_you = 10;
			if($driver->save())
			return true;
			else
			return false;
		}
	}

	public function updateLastActionTime()
	{
		$this->last_action_time = date( 'Y-m-d H:i:s');
		$this->saveAttributes(array('last_action_time'));
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


	public function updateLastVisit()
	{
		$this->last_visit_time = date( 'Y-m-d H:i:s');
		$this->saveAttributes(array('last_visit_time'));
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


	public static function searchByName($keyword, $limit = 20)
	{
		$list = array();
		$chars = str_split($keyword);
		{
			$criteria = new CDbCriteria;
			$criteria->addSearchCondition('username', $keyword, true);
			$criteria->addSearchCondition('full_name', $keyword, true);
			$criteria->scopes = 'active';
			$criteria->order = 'username';
			$criteria->limit = $limit;
			$list = self::model()->findAll($criteria);
		}
		return $list;
	}


	public static function getUserByEmail($name)
	{
		$user = User::model()->findByAttributes(array('email'=>$name));
		return $user;
	}


	public static function getUserByName($name)
	{
		$user = User::model()->active()->findByAttributes(array ( 'username'=>$name));
		return $user;
	}


	public static function getUserById($id)
	{
		$user = User::model()->active()->findByAttributes(array ( 'id'=>$id));
		return $user;
	}


	public function recover()
	{
		$password = self::randomPassword();
		$this->setPassword($password,$password);
		if( $this->save('password') )
		{
			$to      = $this->email;
		
			$subject = 'Your new password: ';

			$body 	= 'Dear '. $this->full_name ."\r\n";
			$body 	.=' ' ."\r\n";
			$body 	.= 'In order to login, please use following credentials. '."\r\n";;
			$body 	.=' ' ."\r\n";
			$body 	.= 'Email ID: ' . $this->email . "\r\n";
			$body 	.= 'Password: ' . $password . "\r\n";
			$body 	.= ' ' ."\r\n";
			$body 	.= 'Thanks' ."\r\n";
			$body 	.= 'Admin' ."\r\n";
		
			$headers = 'From: ' . Yii::app()->params['adminEmail'] . "\r\n" .
					'Reply-To: ' . Yii::app()->params['adminEmail'] ."\r\n"; // terminator restored: the trailing '.' swallowed the mail() call below
			//	'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			(class_exists('PosOutbound') && PosOutbound::isStubbed())
				? PosOutbound::intercept(PosOutbound::CHANNEL_MAIL, $to, array('subject' => $subject, 'body' => $body, 'headers' => $headers))
				: @mail($to, $subject, $body, $headers);

			//if ( YII_ENV == 'dev' && !isset( Yii::app()->controller->module ) ) echo $body;
			//exit();
		}
	}


	public function register()
	{
		$to      = $this->email;

		$subject = 'Confirm Your Account at: ' . Yii::app()->params['company'];

		$body 	= 'Thank you for registering with us. Please click on below given link to activate. ' ."\r\n";
		$body 	.= CHtml::link ( 'Activate', $this->getActivationUrl());
		$body 	.= $this->getActivationUrl();
		$body 	.= ' ' ."\r\n";
		$body 	.= 'Thanks' ."\r\n";
		$body 	.= 'Admin' ."\r\n";

		$headers = 'From: ' . Yii::app()->params['adminEmail'] . "\r\n" .
				'Reply-To: ' . Yii::app()->params['adminEmail'] ."\r\n"; // terminator restored: the trailing '.' swallowed the mail() call below
		//	'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		(class_exists('PosOutbound') && PosOutbound::isStubbed())
				? PosOutbound::intercept(PosOutbound::CHANNEL_MAIL, $to, array('subject' => $subject, 'body' => $body, 'headers' => $headers))
				: @mail($to, $subject, $body, $headers);

		if ( YII_ENV == 'dev' && !isset( Yii::app()->controller->module ) ) echo $body;
	}
public function mailtoadmin()
	{
		$to      = Yii::app()->params['adminEmail'] ;//$this->email;

		$subject = 'New User Request For Bar: ' . Yii::app()->params['company'];

		$body 	= 'New Request for Register the Bar. ' ."\r\n";
		//$body 	.= CHtml::link ( 'Activate', $this->getActivationUrl());
	//	$body 	.= $this->getActivationUrl();
		$body 	.= ' ' ."\r\n";
		$body 	.= 'Username'.$this->username ."\r\n";
		$body 	.= 'Email-id of Bar'.$this->email ."\r\n";
		$body 	.= 'Full Name Of Bar '.$this->full_name ."\r\n";
		

		$headers = 'From: ' . Yii::app()->params['adminEmail'] . "\r\n" .
				'Reply-To: ' . Yii::app()->params['adminEmail'] ."\r\n"; // terminator restored: the trailing '.' swallowed the mail() call below
		//	'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		(class_exists('PosOutbound') && PosOutbound::isStubbed())
				? PosOutbound::intercept(PosOutbound::CHANNEL_MAIL, $to, array('subject' => $subject, 'body' => $body, 'headers' => $headers))
				: @mail($to, $subject, $body, $headers);

		if ( YII_ENV == 'dev' && !isset( Yii::app()->controller->module ) ) echo $body;
	}



	public function sendPassword()
	{
		$password = self::randomPassword();
		$this->setPassword($password,$password);
		if( $this->save('password') )
		{
			$to      = $this->email;
			$subject = 'Your new password: ';

			$body 	= 'Dear '. $this->full_name ."\r\n";
			$body 	.=' ' ."\r\n";
			$body 	.= 'In order to login, please use following credentials. '."\r\n";;
			$body 	.=' ' ."\r\n";
			$body 	.= 'Email ID: ' . $this->email . "\r\n";
			$body 	.= 'Password: ' . $password . "\r\n";
			$body 	.= ' ' ."\r\n";
			$body 	.= 'Thanks' ."\r\n";
			$body 	.= 'Admin' ."\r\n";

			$headers = 'From: ' . Yii::app()->params['adminEmail'] . "\r\n" .
					'Reply-To: ' . Yii::app()->params['adminEmail'] ."\r\n"; // terminator restored: the trailing '.' swallowed the mail() call below
			//	'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			(class_exists('PosOutbound') && PosOutbound::isStubbed())
				? PosOutbound::intercept(PosOutbound::CHANNEL_MAIL, $to, array('subject' => $subject, 'body' => $body, 'headers' => $headers))
				: @mail($to, $subject, $body, $headers);

			//if ( YII_ENV == 'dev' )
			return $password;
		}
	}



	/**
	 * Send Journey Receipt in Email to passenger
	 * @param unknown_type $journey_model
	 */
	public function sendReceiptEmail($journey_model)
	{

		$user_id = $journey_model->passenger_id;

		//$user_model = $this->loadModel($user_id, 'User');
		$user_model = User::model()->findByPk($user_id);

		$to      = $user_model->email;
		$subject = 'Smarttaxi - Cash Payment Receipt : ';

		$body 	= 'Dear '. $user_model->full_name ."\r\n";
		$body 	.=' ' ."\r\n";
			
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
			
		$body 	.= 'This is your receipt of payment  of journey, from '.$origin_address.' to '.$destination_address.' of dated '.$start_time.".\r\n";
		$body 	.= ' ' ."\r\n";
		$body 	.= 'Paid Amount : ' . $amount_paid . "\r\n";
		$body 	.= 'Journey Receipt  No : ' . $journey_model->id . "\r\n";
		$body 	.= ' ' ."\r\n";
		$body 	.= 'Thanks' ."\r\n";
		$body 	.= 'Admin' ."\r\n";

		$headers = 'From: ' . Yii::app()->params['adminEmail'] . "\r\n" .
				'Reply-To: ' . Yii::app()->params['adminEmail'] ."\r\n"; // terminator restored: the trailing '.' swallowed the mail() call below
		//	'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		(class_exists('PosOutbound') && PosOutbound::isStubbed())
				? PosOutbound::intercept(PosOutbound::CHANNEL_MAIL, $to, array('subject' => $subject, 'body' => $body, 'headers' => $headers))
				: @mail($to, $subject, $body, $headers);

		if ( YII_ENV == 'dev' && !isset( Yii::app()->controller->module ) ) echo $body;

	}

	public static function randomBarcode($count = 13) {
		$alphabet = "0123456789";
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < $count; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass);; //turn the array into a string
	}
	public static function randomPassword($count = 8) {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$alphabet = "abcdefghijklmnopqrstuwxyz0123456789";
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < $count; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass);; //turn the array into a string
	}


	public function generateActivationKey($activate = false)
	{
		$this->activation_key = $activate? User::encrypt(microtime()): User::encrypt(microtime() . $this->password);
		$this->saveAttributes(array('activation_key'));
		return $this->activation_key;
	}


	public function getActivationUrl($mode = 'login')
	{
		$this->generateActivationKey();
		return Yii::app()->createAbsoluteUrl('user/activate', array ( 'id' => $this->id, 'key' => $this->activation_key, 'mode'=>$mode));
	}


	public function activate($email, $key)
	{

		if ($this->email == $email)
		{
			if ($this->state_id != self::STATUS_INACTIVE)
			return -1;
			if ($this->activation_key == $key)
			{
				$this->state_id = self::STATUS_ACTIVE;
				if ($this->saveAttributes(array( 'state_id')))
				{
					return 1;
				}
			} else return -2;
		}
		return false;
	}
	/* public function isAdmin()
	{
		if($this->role_id == self::ROLE_ADMIN) return true;
		return false;
	}

	public function isUser()
	{
		if($this->role_id == self::ROLE_USER)
		return true;
		return false;
	} */
	public function isTribe($id)
	{
		$tribe = Tribe::model()->findByAttributes(array('create_user_id'=>$id));
		if($tribe)
		return true;
		else
		{
			$usertribe = UserTribe::model()->findByAttributes(array('user_id'=>$id));
			if($usertribe)
			return true;
			else
			return false;
		}
	
		
	}

	/*public function isManager()
	 {
	 if($this->role_id == self::ROLE_USER || $this->role_id == self::ROLE_MANAGER)
		return true;
		return false;
		}*/

	public function setPassword($password,$password_2)
	{
		if ($password != '' && $password == $password_2 ) {
			$this->password = User::encrypt2($password);
			return $this->save(false,'password');
		}
		return false;
	}

	public static function encrypt($string = "")
	{
		$salt = self::$salt1;
		$hashFunc = self::$hashFunc;
		$string = sprintf("%s%s%s", $salt, $string, $salt);

		if (!function_exists($hashFunc))
		throw new CException('Function `' . $hashFunc . '` is not a valid callback for hashing algorithm.');

		return $hashFunc($string);
	}



	public static function encrypt2($string = "")
	{
		//	include ("pbkdf.php");
		$out = md5($string);
		return $out;
	}


	/* 	public static function isLoggedIn( $id)
	 {
	 if ( Yii::app()->user->isAdmin() || $id == Yii::app()->user->id )
		return true;
		return false;
		} */


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
		return array(
				'active' => array('condition' => 'state_id=' . self::STATUS_ACTIVE,),
				'inactive' => array('condition' => 'state_id=' . self::STATUS_INACTIVE,),
				'banned' => array('condition' => 'state_id=' . self::STATUS_BANNED,),
		);
	}
	protected function beforeDelete()
	{
		MerchantStore::model()->deleteAllByAttributes(array ('merchant_id'=>$this->id));
		$cats = Category::model()->findAllByAttributes(array ('create_user_id'=>$this->id));
		if($cats)
		{
			foreach($cats as $cat)
			{
				$cat->delete();
			}
		}
		$products = Product::model()->findAllByAttributes(array ('create_user_id'=>$this->id));
		if($products)
		{
			foreach($products as $product)
			{
				$product->delete();
			}
		}
		PromotionalAdd::model()->deleteAllByAttributes(array ('create_user_id'=>$this->id));
		Order::model()->deleteAllByAttributes(array ('create_user_id'=>$this->id));
		AuthSession::model()->deleteAllByAttributes(array ('create_user_id'=>$this->id));
		return parent::beforeDelete();
		}



	public function toArray()
	{
		$model = $this;
		$json_entry = null;
		if ( $model )
		{
			$id = 1;
			$setting = Setting::model()->findByPk($id);
			$default_img = 'default.png';
			$json_entry = array();
			$json_entry['id'] = $model->id;
			$json_entry['full_name'] = isset($model->full_name)?$model->full_name:'';
			$json_entry['username'] = $model->username;
			$json_entry['email'] = $model->email;
			$json_entry['phone'] = isset($model->contact_no)?$model->contact_no:'0';
			$json_entry['date_of_birth'] = isset($model->date_of_birth)?$model->date_of_birth:'';
			$json_entry ['gender_id'] = $model->gender;
			$json_entry ['gender'] = $model->getGenderOptions($model->gender);
			$json_entry['age'] = $model->GetAge();
			if($model->emp){
			$json_entry['outlet_id'] = $model->emp->outlet_id;
			}
			if($setting){
			$json_entry['api_key'] = $setting->api_key;
			$json_entry['ivr_username'] = $setting->ivr_username;
			}else{
				$json_entry['api_key'] = '';
				$json_entry['ivr_username'] = '';
			}
			$json_entry['role_id'] = $model->role_id;
			//$json_entry['is_favorite'] = $model->is_favorite($model->id)?"1":"0";
			$json_entry['image_file'] = isset($this->image_file) ? Yii::app()->createAbsoluteUrl('user/download',array('file'=>$this->image_file)) :
			Yii::app()->createAbsoluteUrl('user/download',array('file'=>$default_img));
		}
		return $json_entry;
	}
	/**
	 * Sends notification to GCM and ACM device
	 * @param unknown_type $arr1
	 * @return string
	 */
	/* public function sendGCM($arr1)
	{
		$push_status = "NOK";
		
		foreach ($this->authSessions as $session)
		{
                if( $session->type_id  != 2)
                {
			$class =  'GCM';
			Yii::import('ext.push.' . $class);
			$push = new $class();
		
			$push_status  = $push->send_notification($session->device_token, $arr1);
		}
                }
		return $push_status;
	} */
	public static function is_favorite($id)
	{
		$favorite = FavoriteUser::model()->findByAttributes(array('user_id'=>$id,'create_user_id'=>Yii::app()->user->id));
		if($favorite)
		return true;
		else
		return false;

	}
	
	public function getStoreName(){
		$name = '';
		$merchantstore = MerchantStore::model()->findByAttributes(array('merchant_id'=>$this->id));
		if($merchantstore != null )
		{
			$store = Store::model()->findByPk($merchantstore->store_id);
			if($store)
			{
				$name =  $store->title;
			}
		}
		return $name;
		
	}
	public static function RemoveStore($id)
	{
		MerchantStore::model()->deleteAllByAttributes(array('merchant_id'=>$id));
	}
	public function getSelectedStores(){
		$cat_ids = array();
		$cats = MerchantStore::model()->findAllByAttributes(array('merchant_id'=>$this->id));
		if($cats != null)
		{
			foreach($cats as $cat)
			{
				$cat_ids[] = $cat->store_id;
			}
		}
	
		return $cat_ids;
	}
	public static function getMerchantOptions(){
		$userlist = array();
		$users = User::model()->findAllByAttributes(array('role_id'=>User::ROLE_MERCHANT));
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
		$store_arr = array();
		$string = '';
		$stores = MerchantStore::model()->findAllByAttributes(array('merchant_id'=>$this->id));
		if($stores)
		{
			foreach($stores as $store)
			{
				$storedata = Store::model()->findByPk($store->store_id);
				$store_arr[] = $storedata->title;
			}
			if(!empty($store_arr))
			{
				$string = implode(',',$store_arr);
			}
				
		}
		return $string;
	}

	public function addNewSessionName(){
		$current_date = date('Y-m-d');
		$date = date('Y').'-04-01';
		$month = date('m');
		
		if($month > 3){
			$year = date('Y');
			$yearadd = $year + 1;
		}else{
			$yearadd = date('Y');
			$year = $yearadd - 1;
		}
		if($current_date == $date){
		$name = $year.'-'.$yearadd;
		$session = Session::model()->findByAttributes(array('name'=>$name));
		if($session == null){
			$session = new Session();
		}
		$session->name = $name;
		$session->create_time = date('Y-m-d H:i:s');
		
		Yii::log ( CVarDumper::dumpAsString ( $session ), CLogger::LEVEL_WARNING, 'timer is working' );
		$session->save();
		}
	}

	public function checkSelectedSession(){
		
		$criteria = new CDbCriteria();
		$criteria->order = 'id desc';
		$criteria->limit = 1;
		$latestSession = Session::model()->find($criteria);
		if((Yii::app()->session['select_session_id'] != '') && (Yii::app()->session['select_session_id'] != $latestSession->id))
		{
			return false;
		}
		
		return true;
	}
}
