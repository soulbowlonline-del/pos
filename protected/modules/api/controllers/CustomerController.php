<?php
class CustomerController extends GxController {
	public function filters() {
		return array (
				'accessControl' 
		);
	}

	public function beforeAction($action)
    {
        if ($action->id == 'uploadbill') {
            Yii::app()->detachEventHandler('onBeginRequest', array(Yii::app()->request, 'enableCsrfValidation'));
        }
        return parent::beforeAction($action);
    }
	public function accessRules() {
		return array (
				array (
						'allow',
						'actions' => array (
								'index',
								'add',
								'update',
								'get',
								'countryList',
								'stateList',
								'cityList',
								'orderList',
								'getOrderHold',
								'GetLatestBill',
								'getOrder',
								'discounts',
								'holdOrderList',
								'setting',
								'verify',
								'sendOTP',
								'verifyOTP',
								'sentwhatappotp',
								'verifywhatappotp',
								'uploadwhatapporder',
								"uploadbill"
						),
						'users' => array (
								'*' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'create',
								'update',
								'search' ,
								'verify',
						),
						'users' => array (
								'@' 
						) 
				),
				array (
						'allow',
						'actions' => array (
								'admin',
								'delete' ,
								'verify',
						),
						'expression' => 'Yii::app()->user->isAdmin' 
				),
				array (
						'deny',
						'users' => array (
								'*' 
						) 
				) 
		);
	}
	
	public function actionDiscounts() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		
		$discounts = Discount::model ()->findAllByAttributes ( array (
				'status' => Discount::STATUS_ACTIVE 
		) );
		if (! empty ( $discounts )) {
			
			foreach ( $discounts as $discount ) {
				$json_list [] = $discount->toArray ();
			}
			
			$arr ['status'] = 'OK';
			
			$arr ['discounts'] = $json_list;
		} else {
			$arr ['message'] = 'No data to display';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionOrderList($id, $status) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		
		if ($status == 1) {
			$orders = Order::model ()->findAllByAttributes ( array (
					'outlet_id' => $id 
			) );
		} elseif ($status == 2) {
			$orders = OrderHold::model ()->findAllByAttributes ( array (
					'outlet_id' => $id 
			) );
		}
		if (! empty ( $orders )) {
			
			foreach ( $orders as $order ) {
				$json_list [] = $order->toArray ();
			}
			
			$arr ['status'] = 'OK';
			
			$arr ['orders'] = $json_list;
		} else {
			$arr ['message'] = 'No data to display';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionHoldOrderList($id, $status) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		
		if ($status == 1) {
			$criteria = new CDbCriteria ();
			$criteria->order = 'id asc';
			$criteria->addCondition ( 'outlet_id =' . $id );
			$orders = Order::model ()->findAll ( $criteria );
		} elseif ($status == 2) {
			$criteria = new CDbCriteria ();
			$criteria->order = 'id asc';
			$criteria->addCondition ( 'outlet_id =' . $id );
			$orders = OrderHold::model ()->findAll ( $criteria );
		}
		if (! empty ( $orders )) {
			
			foreach ( $orders as $order ) {
				$json_list [] = $order->toArray1 ();
			}
			
			$arr ['status'] = 'OK';
			
			$arr ['orders'] = $json_list;
		} else {
			$arr ['message'] = 'No data to display';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionGetOrderHold($id) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		$order = OrderHold::model ()->findByPk ( $id );
		if (! empty ( $order )) {
			
			$arr ['status'] = 'OK';
			
			$arr ['order'] [] = $order->toArray ();
			$order->delete ();
		} else {
			$arr ['message'] = 'Order not available';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionGetOrder($id) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		$order = Order::model ()->findByPk ( $id );
		if (! empty ( $order )) {
			
			$arr ['status'] = 'OK';
			
			$arr ['order'] [] = $order->toArray2 ();
			
		} else {
			$arr ['message'] = 'Order not available';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function actionGetLatestBill($id) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$json_list = array ();
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'outlet_id =' . $id );
		$criteria->order = 'bill_no desc';
		$order = Order::model ()->find ( $criteria );
		if (! empty ( $order )) {
			$outlet = Outlet::model ()->findByPk ( $id );
			if ($outlet) {
				$arr ['bill_prefix'] = $outlet->bill_prefix;
			} else {
				$arr ['bill_prefix'] = 'B';
			}
			
			$arr ['status'] = 'OK';
			
			$arr ['bill_no'] = $order->bill_no;
		} else {
			$arr ['message'] = 'Order not available';
		}
		
		$this->sendJSONResponse ( $arr );
	}
	public function isAllowed($model) {
		return $model->isAllowed ();
	}
	public function actionCountryList() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$criteria = new CDbCriteria ();
		$criteria->order = 'id desc';
		// $criteria->addCondition ( "parent_id IS NULL" );
		$models = Country::model ()->findAll ( $criteria );
		
		if ($models) {
			$json_list = array ();
			$json_entry = array ();
			foreach ( $models as $model ) {
				$json_list [] = $model->toArray ();
			}
			$arr ['status'] = 'OK';
			
			$arr ['countrylist'] = $json_list;
		} else {
			$arr ['status'] = 'NOK';
			
			$arr ['message'] = 'No data to display';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionStateList($id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$criteria = new CDbCriteria ();
		$criteria->order = 'id desc';
		if ($id != null)
			$criteria->addCondition ( "country_id =" . $id );
		$models = State::model ()->findAll ( $criteria );
		
		if ($models) {
			$json_list = array ();
			$json_entry = array ();
			foreach ( $models as $model ) {
				$json_list [] = $model->toArray ();
			}
			$arr ['status'] = 'OK';
			
			$arr ['statelist'] = $json_list;
		} else {
			$arr ['status'] = 'NOK';
			
			$arr ['message'] = 'No data to display';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionCityList($id = null) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$criteria = new CDbCriteria ();
		$criteria->order = 'id desc';
		if ($id != null)
			$criteria->addCondition ( "state_id =" . $id );
		$models = City::model ()->findAll ( $criteria );
		
		if ($models) {
			$json_list = array ();
			$json_entry = array ();
			foreach ( $models as $model ) {
				$json_list [] = $model->toArray ();
			}
			$arr ['status'] = 'OK';
			
			$arr ['citylist'] = $json_list;
		} else {
			$arr ['status'] = 'NOK';
			
			$arr ['message'] = 'No data to display';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionIndex() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		$criteria = new CDbCriteria ();
		$criteria->order = 'id desc';
		// $criteria->addCondition ( "parent_id IS NULL" );
		$models = Customer::model ()->findAll ( $criteria );
		
		if ($models) {
			$json_list = array ();
			$json_entry = array ();
			foreach ( $models as $model ) {
				$json_list [] = $model->toArray1 ();
			}
			$arr ['status'] = 'OK';
			
			$arr ['customerlist'] = $json_list;
		} else {
			$arr ['status'] = 'NOK';
			
			$arr ['message'] = 'No data to display';
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionAdd() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		
		$model = new Customer ();
		/*
		 * $_POST ['name'] = "sanjeev33";
		 * $_POST ['contact_no'] = "9997734333";
		 */
		/*
		 * $_POST ['name'] = "sanjeev1";
		 * $_POST ['email'] = "sanjeev1";
		 * $_POST ['address'] = "sanjeev1@gmail.com";
		 * $_POST ['city_id'] = "1";
		 * $_POST ['state_id'] = "1";
		 * $_POST ['country_id'] = "1";
		 * $_POST ['zip_code'] = "160059";
		 * $_POST ['contact_no'] = "9997737333";
		 */
		
		if (isset ( $_POST ['name'] ) && isset ( $_POST ['contact_no'] )) {
			
			$model->name = $_POST ['name'];
			/* $model->email = $_POST ['email']; */
			/*
			 * $model->address = $_POST ['address'];
			 * $model->city_id = $_POST ['city_id'];
			 * $model->state_id = $_POST ['state_id'];
			 * $model->country_id = $_POST ['country_id'];
			 * $model->zip_code = $_POST ['zip_code'];
			 */
			$model->contact_no = $_POST ['contact_no'];
			if (isset ( $_POST ['state_id'] )){
				$model->state_id = $_POST ['state_id'];
			}else{
				$model->state_id = 1;
			}
			if (isset ( $_POST ['city_id'] )){
				$model->city_id = $_POST ['city_id'];
			}else{
				$model->city_id = 1;
			}
			if (isset ( $_POST ['country_id'] )){
				$model->country_id = $_POST ['country_id'];
			}else{
				$model->country_id = 1;
			}
			if (isset ( $_POST ['zip_code'] )){
				$model->zip_code = $_POST ['zip_code'];
			}else{
				$model->zip_code = "160059";
			}
			if (isset ( $_POST ['email'] ))
			    $model->email = $_POST ['email'];
			if (isset ( $_POST ['opening_balance'] ))
				$model->opening_balance = $_POST ['opening_balance'];
			if (isset ( $_POST ['credit_limit'] ))
				$model->credit_limit = $_POST ['credit_limit'];
			if (isset ( $_POST ['payment_days'] ))
				$model->payment_days = $_POST ['payment_days'];
		
			if (isset ( $_POST ['is_enable_wa'] ))
				$model->is_enable_wa = $_POST ['is_enable_wa'];

			$user = Customer::getUserByContactNo ( $model->contact_no );
			if (! $user) {
				
				$model->state_id = 1; // activates account set 1
				if ($model->save ()) {
					
					try {
						$whatsapp_no = preg_replace("/[^0-9]/", "", $model->contact_no);
						if ($whatsapp_no != '') {
							$data = [
								"phoneNumber" => $whatsapp_no,
								"countryCode" => "+91",
								"traits" => [
									"name" => $model->name,
									"email" => $model->email
								],
								"tags" => ["Added By POS"]
							];
							Yii::app()->interaktApi->createCustomer($data);
						}
					} catch (\Throwable $th) {
						//throw $th;
					}
					
					$arr ['status'] = 'OK';
					$arr ['profile'] [] = $model->toArray ();
					$arr ['message'] = 'Customer is added successfully';
				} else {
					$err = '';
					foreach ( $model->getErrors () as $error )
						$err .= implode ( ".", $error );
					$arr ['message'] = $err;
				}
			} else {
				$arr ['message'] = "Contact no. already in use.";
			}
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionUpdate($id) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		
		$model = Customer::model ()->findByPk ( $id );
		
		/*
		 * $_POST ['name'] = "sanjeev";
		 * $_POST ['email'] = "sanjeev";
		 * $_POST ['address'] = "sanjeev1@gmail.com";
		 * $_POST ['city_id'] = "1";
		 * $_POST ['state_id'] = "1";
		 * $_POST ['country_id'] = "1";
		 * $_POST ['zip_code'] = "160059";
		 * $_POST ['contact_no'] = "9997737333";
		 */
		
		if ($model) {
			if (isset ( $_POST ['name'] ) && isset ( $_POST ['address'] ) && isset ( $_POST ['city_id'] ) && isset ( $_POST ['state_id'] ) && isset ( $_POST ['country_id'] ) && isset ( $_POST ['zip_code'] ) && isset ( $_POST ['contact_no'] )) {
				
				$model->name = $_POST ['name'];
				//$model->email = $_POST ['email'];
				$model->address = $_POST ['address'];
				$model->city_id = $_POST ['city_id'];
				$model->state_id = $_POST ['state_id'];
				$model->country_id = $_POST ['country_id'];
				$model->zip_code = $_POST ['zip_code'];
				$model->contact_no = $_POST ['contact_no'];
				if (isset ( $_POST ['email'] ))
				    $model->email = $_POST ['email'];
				
				if (isset ( $_POST ['opening_balance'] ))
					$model->opening_balance = $_POST ['opening_balance'];
				if (isset ( $_POST ['credit_limit'] ))
					$model->credit_limit = $_POST ['credit_limit'];
				if (isset ( $_POST ['payment_days'] ))
					$model->payment_days = $_POST ['payment_days'];
				$user = Customer::getUserByContactNo ( $model->contact_no );
				if (! $user) {
					
					$model->state_id = 1; // activates account set 1
					if ($model->save ()) {
						
						$arr ['status'] = 'OK';
						$arr ['profile'] = $model->toArray ();
						$arr ['message'] = 'Customer is updated successfully';
					} else {
						$err = '';
						foreach ( $model->getErrors () as $error )
							$err .= implode ( ".", $error );
						$arr ['message'] = $err;
					}
				} else {
					$arr ['message'] = "Contact no. already in use.";
				}
			}
		} else {
			$arr ['message'] = "Customer not found";
		}
		$this->sendJSONResponse ( $arr );
	}
	public function actionGet($id) {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
		
		$model = Customer::model ()->findByPk ( $id );
		
		if ($model) {
			
			$arr ['status'] = 'OK';
			$arr ['profile'] [] = $model->toArray ();
		} else {
			$arr ['message'] = "Customer not found";
		}
		$this->sendJSONResponse ( $arr );
	}
	
	public function actionSetting() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
	
		$model = Setting::model ()->find();
	
		if ($model) {
				
			$arr ['status'] = 'OK';
			$arr ['profile'] [] = $model->toArray ();
		} else {
			$arr ['message'] = "Setting not found";
		}
		$this->sendJSONResponse ( $arr );
	}

	// New improved OTP system
	public function actionSendOTP() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
	
		$phoneNumber = isset($_POST['phone_number']) ? $_POST['phone_number'] : null;
		$customerId = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;
	
		// Validate input
		if (!$phoneNumber) {
			$arr['message'] = "Phone number is required";
			$this->sendJSONResponse($arr);
			return;
		}
	
		// Clean phone number
		$phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
		if (strlen($phoneNumber) != 10) {
			$arr['message'] = "Invalid phone number format";
			$this->sendJSONResponse($arr);
			return;
		}
	
		// Find or create customer
		if ($customerId) {
			$model = Customer::model()->findByPk($customerId);
		} else {
			$model = Customer::getUserByContactNo($phoneNumber);
		}
	
		if (!$model) {
			
				$arr['message'] = "Unable to find customer with provided details";
				$this->sendJSONResponse($arr);
				return;
		}
	
		// Check if there's already a pending OTP
		if (CustomerOtp::hasPendingOTP($model->id)) {
			$arr['message'] = "OTP already sent. Please wait before requesting again.";
			$this->sendJSONResponse($arr);
			return;
		}
	
		// Generate OTP
		$otpResult = CustomerOtp::generateOTP($model->id, $phoneNumber);
		
		if (!$otpResult['success']) {
			$arr['message'] = $otpResult['message'];
			$this->sendJSONResponse($arr);
			return;
		}
	
		// Send OTP via WhatsApp
		try {
			
			OTPService::sendOTPWhatsApp($phoneNumber, $model->name, $otpResult['otp_code']);
			
			$model->is_enable_wa = 1;
			$model->save();
			
			$arr['status'] = 'OK';
			$arr['message'] = 'OTP sent successfully via WhatsApp';
			$arr['data'] = [
				'customer_id' => $model->id,
				'phone_number' => $phoneNumber,
				'otp_expires_in' => 300, // 5 minutes in seconds
				'otp_id' => $otpResult['otp_id']
			];
			
		} catch (Exception $e) {
			$arr['message'] = "OTP generated but failed to send via WhatsApp: " . $e->getMessage();
			// You might want to still return success since OTP was generated
			$arr['status'] = 'OK';
			$arr['data'] = [
				'customer_id' => $model->id,
				'phone_number' => $phoneNumber,
				'otp_expires_in' => 300,
				'otp_id' => $otpResult['otp_id'],
				'warning' => 'SMS delivery may have failed'
			];
		}
	
		$this->sendJSONResponse($arr);
	}

	public function actionVerifyOTP() {
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
	
		$customerId = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;
		$otpCode = isset($_POST['otp_code']) ? $_POST['otp_code'] : null;
		$phoneNumber = isset($_POST['phone_number']) ? $_POST['phone_number'] : null;
	
		// Validate input
		if (!$customerId && !$phoneNumber) {
			$arr['message'] = "Customer ID or phone number is required";
			$this->sendJSONResponse($arr);
			return;
		}
	
		if (!$otpCode) {
			$arr['message'] = "OTP code is required";
			$this->sendJSONResponse($arr);
			return;
		}
	
		// Find customer
		if ($customerId) {
			$model = Customer::model()->findByPk($customerId);
		} else {
			$phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
			$model = Customer::getUserByContactNo($phoneNumber);
		}
	
		if (!$model) {
			$arr['message'] = "Customer not found";
			$this->sendJSONResponse($arr);
			return;
		}
	
		// Verify OTP
		$verifyResult = CustomerOtp::verifyOTP($model->id, $otpCode);
	
		if ($verifyResult['success']) {
			// Update customer status
			$model->is_enable_wa = 2; // Verified status
			$model->save();
			
			$arr['status'] = 'OK';
			$arr['message'] = $verifyResult['message'];
			$arr['data'] = [
				'customer_id' => $model->id,
				'customer_name' => $model->name,
				'phone_number' => $model->contact_no,
				'verified_at' => $verifyResult['verified_at'],
				'customer_profile' => $model->toArray1()
			];
		} else {
			$arr['message'] = $verifyResult['message'];
		}
	
		$this->sendJSONResponse($arr);
	}

	// Legacy OTP methods - kept for backward compatibility
	public function actionSentwhatappotp($id) {
		//die("whatapp");
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
	
		$model = Customer::model ()->findByPk ( $id );
	
		if ($model) {

			$model->is_enable_wa = 1 ;

			$otp = $model->generateOtp();

			$whatsapp_no = preg_replace("/[^0-9]/", "", $model->contact_no);
						if ($whatsapp_no != '') {
							$data = [
								"phoneNumber" => $whatsapp_no,
								"countryCode" => "+91",
								"traits" => [
									"name" => $model->name,
									"email" => $model->email
								],
								"tags" => ["Added By POS"]
							];
							//Yii::app()->interaktApi->createCustomer($data);

							$whatsapp_no = preg_replace("/[^0-9]/", "", $model->contact_no);
				$template = 'welcome_message';
				$arr ['message'] = Yii::app()->interaktApi->sendApprovalOrderMessageNew($template,$whatsapp_no , [ $model->name , $otp], '','');
						
			}
				
			$arr ['status'] = 'OK';
			$arr ['profile'] [] = $model->toArray ();
		} else {
			$arr ['message'] = "Setting not found";
		}
		$this->sendJSONResponse ( $arr );
	}

	public function actionVerifywhatappotp($id, $otp) {
		//die("whatapp");
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
	
		$model = Customer::model ()->findByPk ( $id );
	
		if ($model) {

			

			$re = $model->verifyOtp($otp);

			$arr ['message'] = "in-correct otp"; 
			if ($re){
				$model->is_enable_wa = 2 ;
				$model->save();
				$arr ['status'] = 'OK';
				$arr ['message'] = "verified";

				

			}
			
			
		} else {
			$arr ['message'] = "user not found";
		}
		$this->sendJSONResponse ( $arr );
	}


	public function actionUploadwhatapporder($id) {
		//die("whatapp");
		$arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK'
		);
	
		$model = Customer::model ()->findByPk ( $id );
	
		if ($model) {


			$whatsapp_no = preg_replace("/[^0-9]/", "", $model->contact_no);

			$template = 'purchase_order';
			$urlPdf =  "https://sect4.soulbowl.in/pos/product_order/order_first.pdf";
			$arr ['message'] = Yii::app()->interaktApi->sendApprovalOrderMessageNew($template,$whatsapp_no ,[$model->name], ["http://61.2.241.71/pos/whatapporder/payBillTest.pdf"], 'order.pdf');

			// $whatsapp_no = preg_replace("/[^0-9]/", "", $model->contact_no);

			// $template = 'purchase_order';
			// $urlPdf =  "https://sect4.soulbowl.in/pos/product_order/order_first.pdf";
			// $arr ['message'] = Yii::app()->interaktApi->sendApprovalOrderMessageNew($template,$whatsapp_no ,[$model->name], [$urlPdf],'');


			// $arr ['message'] = $this->uploadFileToServer("/var/www/html/pos_petro/src/pos_sect4/protected/modules/api/controllers/order_details.pdf");
			

			// $re = $model->verifyOtp($otp);

			// $arr ['message'] = "in-correct otp"; 
			// if ($re){
			// 	$model->is_enable_wa = 2 ;
			// 	$model->save();
			// 	$arr ['status'] = 'OK';
			// 	$arr ['message'] = "verified";
			// }
			
			
		} else {
			$arr ['message'] = "user not found";
		}
		$this->sendJSONResponse ( $arr );
	}
	

	protected function uploadFileToServer($file_path) {

		
		// URL of Server B (where you want to upload the file)
		//$upload_url = 'https://sect4.soulbowl.in/pos/uploadProductOrder.php';
		$upload_url = 'http://61.2.241.71/pos/uploadProductOrder.php';

		if (!file_exists($file_path)) {
			return 'Error: File not found.';
		}
	
		// URL of Server B (where you want to upload the file)
		//$upload_url = 'https://your-server-b.com/upload.php';
	
		// Token for authentication (replace with a secure token)
		$api_token = '5716ec355ed78420c74c6fc1596a9f39565493df9bfc37e2575f60f16a965f25';
	
		 // Get file name
		 $file_name = basename($file_path);
    
		 // Initialize cURL session
		 $ch = curl_init($upload_url);
	 
		 // Prepare the file for upload using CURLFile
		 $cfile = new CURLFile($file_path,  'application/pdf', $file_name);
	 
		 // POST data (including file and token)
		 $post_data = [
			 'file' => $cfile,     // Attach the file
			 'token' => $api_token // Authentication token (if needed)
		 ];

		// print_r($post_data);
	 
		 // Set cURL options
		 curl_setopt($ch, CURLOPT_POST, true);
		 curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		 curl_setopt($ch, CURLOPT_VERBOSE, true);  // Enable verbose output for debugging

		 curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: multipart/form-data'  // Let cURL handle the boundary for multipart/form-data
		]);
	 
		 // Optional: Disable SSL verification for development (not recommended for production)
		 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	 
		 // Execute the cURL request and get the response
		 $response = curl_exec($ch);
	 
		 // Check for errors
		 if ($response === false) {
			 return false ; //'Upload Error: ' . curl_error($ch);
		 }
	 
		 // Close cURL session
		 curl_close($ch);
	 
		 // Return the response
		 return true; //'Upload Successful: ' . $response;
	}

	public function actionUploadbill()
    {
		$arr = array (
			'controller' => $this->id,
			'action' => $this->action->id,
			'status' => 'NOK'
			);
        // Set the upload directory path
        $uploadDir = Yii::getPathOfAlias('webroot') . '/uploadbills/'; // This points to the 'uploads' directory in the web root

        // Create the upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Check if a file is uploaded
        if (isset($_FILES['file'])) {
            $file = CUploadedFile::getInstanceByName('file');

            if ($file !== null) {
                $fileName = $file->name; // Original file name
                $filePath = $uploadDir . $fileName; // Path to save the uploaded file

                // Save the file to the server
                if ($file->saveAs($filePath)) {
					$dd = $this->uploadFileToServer($filePath);
                    $response = [
						"dd" => $dd ,
                        'status' => 'success',
                        'message' => 'File uploaded successfully!',
                        'file_name' => $fileName,
                        'file_path' => Yii::app()->request->hostInfo . '/uploadbills/' . $fileName
                    ];

					
					
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to save the uploaded file.'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'No file was uploaded.'
                ];
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No file was uploaded or invalid request.'
            ];
        }

		$arr ['response'] = $response;
        $id = $_POST["id"];
			$model = Customer::model ()->findByPk ( $id );
			if ($model) {


				$whatsapp_no = preg_replace("/[^0-9]/", "", $model->contact_no);

				$template = 'purchase_order';
				$fileNms = $_FILES['file']['name'];
				$userId = isset($_POST['user_id']) ? $_POST['user_id'] : null;
				$computerName = isset($_POST['computer_name']) ? $_POST['computer_name'] : null;

				if (strpos(strtolower($fileNms), 'reprint') !== false) {
					$template = 'reprint_order';
				} else if (strpos(strtolower($fileNms), 'refund') !== false) {
					$template = 'refund_order';
				}
				$urlPdf =  'http://61.2.241.71/pos/whatapporder/' . $_FILES['file']['name'] ;
				//echo $urlPdf ;
				
				//echo $fileNms;
				$pdfName = str_replace('.pdf', '', $fileNms);
				$pdfName = str_replace(['_Reprint', '_Refund', '-Reprint', '-Refund'], '', $pdfName);
				$arr ['message'] = Yii::app()->interaktApi->sendApprovalOrderMessageNew($template,$whatsapp_no ,[$model->name, $pdfName], [$urlPdf], $fileNms, ['user_id' => $userId, 'computer_name' => $computerName]);
			
				// $arr ['message'] = $this->uploadFileToServer("/var/www/html/pos_petro/src/pos_sect4/protected/modules/api/controllers/order_details.pdf");
				

				// $re = $model->verifyOtp($otp);

				// $arr ['message'] = "in-correct otp"; 
				// if ($re){
				// 	$model->is_enable_wa = 2 ;
				// 	$model->save();
				// 	$arr ['status'] = 'OK';
				// 	$arr ['message'] = "verified";
				// }
				
				
			} else {
				$arr ['message'] = "user not found";
			}
			$this->sendJSONResponse ( $arr );

        // // Set the response header to JSON
        // header('Content-Type: application/json');
        // echo json_encode($response);


    }
}