<?php

Yii::import('ext.httpclient.*');

class InteraktApi extends CApplicationComponent
{
  public $apiUrl = 'https://api.interakt.ai/v1/public';
  public $apiKey;

  public function init()
  {
    parent::init();
    if ($this->apiKey === null) {
      throw new \CDbException('API Key cannot be null.');
    }
  }

  private function  sendRequest($method, $endpoint, $data = [], $queryParams = [])
  {
    $url = $this->apiUrl . $endpoint;
    if (!empty($queryParams)) {
      $url .= '?' . http_build_query($queryParams);
    }
    // Stubbed outbound: record the call and answer with a canned response
    // instead of making it. Inert unless POS_STUB_OUTBOUND=1.
    if (class_exists('PosOutbound') && PosOutbound::isStubbed()) {
      return PosOutbound::intercept(
        PosOutbound::CHANNEL_HTTP,
        strtoupper($method) . ' ' . $url,
        $data
      );
    }
    // echo $url; die;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Basic ' . $this->apiKey,
      'Content-Type: application/json',
    ]);

    switch (strtoupper($method)) {
      case 'GET':
        break;
      case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        break;
      case 'PUT':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        break;
      case 'DELETE':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
      default:
        throw new CException('Invalid HTTP method: ' . $method);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
      throw new CException('Curl error: ' . curl_error($ch));
    }

    curl_close($ch);

    if ($httpCode >= 400) {
      throw new CException('Error while calling API: ' . $response);
    }
    return json_decode($response, true);
  }

  public function createCustomer($data)
  {
    return $this->sendRequest('POST', '/track/users/', $data);
  }

  public function getTemplates($queryParams = [])
  {
    $url = "track/organization/templates";
    $url .= http_build_query($queryParams);
    return $this->sendRequest('GET', "/track/organization/templates", [], $queryParams);
  }

  public function sendTemplate($data, $extraData = [])
  {
    $response = $this->sendRequest('POST', "/message/", $data);
    $model = new WhatsappLogs();
    $model->number = $data['phoneNumber'];
    $model->status = isset($response['result']) ? $response['result'] ? 1 : 0 : 0;
    $model->message = isset($response['message']) ? $response['message'] : '';
    $model->message_id = isset($response['id']) ? $response['id'] : '';
    $model->template_name = $data['template']['name'];
    $model->user_id = isset($extraData['user_id']) ? $extraData['user_id'] : null;
    $model->computer_name = isset($extraData['computer_name']) ? $extraData['computer_name'] : null;
    $model->created_at = date('Y-m-d H:i:s');
    $model->save();
    return $response;
  }

  public function sendApprovalOrderMessage($phone, $poId, $pdfUrl, $template = 'purchase_order_approved') {
    $data = [
      'countryCode' => '+91',
      'phoneNumber' => $phone, // $phone
      'type' => 'Template',
      'template' => [
        'name' => $template,
        'languageCode' => 'en',
        'headerValues' => [$pdfUrl],
        'fileName' => 'mpdf.pdf',
        'bodyValues' => [$poId]
      ],

    ];

    $this->sendTemplate($data);


  }

  public function uploadFileToSoulBowl($fileName, $id) {
		if (class_exists('PosOutbound') && PosOutbound::isStubbed()) {
			return PosOutbound::intercept(
				PosOutbound::CHANNEL_FTP, 'ftp-upload', array('file' => $fileName, 'id' => $id)
			);
		}
    $tofile = 'whatsapp'.$fileName; 
    $uploadFileName = $id.'.pdf';
    $ftp_server =  "143.110.254.206";
    $ftp_conn = ftp_connect ( $ftp_server ) or die ( "Could not connect to $ftp_server" );
    $ftp_username = Yii::app()->params['ftp_username'] ;
    $ftp_userpass = Yii::app()->params['ftp_password'] ;

    $login = ftp_login ( $ftp_conn, $ftp_username, $ftp_userpass );
    # set this to true
    ftp_pasv($ftp_conn, true);
    if (ftp_put ( $ftp_conn, $fileName, $fileName, FTP_BINARY )) {
      return true;
    } 
    return false;
  }


  public function sendApprovalOrderMessageNew($template, $phone, $bodyValues, $pdfUrl, $fileName  = 'text', $extraData = []) {
    
    $data = [
      'countryCode' => '+91',
      'phoneNumber' => $phone, // $phone
      'type' => 'Template',
      'template' => [
        'name' => $template,
        'languageCode' => 'en',
        'headerValues' => $pdfUrl,
        'fileName' => $fileName,
        'bodyValues' => $bodyValues
      ],

    ];

    if ($pdfUrl == '') {
      $data = [
        'countryCode' => '+91',
        'phoneNumber' => $phone, // $phone
        'type' => 'Template',
        'template' => [
          'name' => $template,
          'languageCode' => 'en',
          'bodyValues' => $bodyValues
        ],
  
      ];
    }

    

    $this->sendTemplate($data, $extraData);


  }

  public function sendOtpMessage($template, $phone, $bodyValues, $buttonValues) {
    
      $data = [
        'countryCode' => '+91',
        'phoneNumber' => $phone, // $phone
        'type' => 'Template',
        'template' => [
          'name' => $template,
          'languageCode' => 'en',
          'bodyValues' => $bodyValues,
          'buttonValues' => $buttonValues
        ],
  
      ];

    return $this->sendTemplate($data);

  }


  public function uploadFileToServer($file_path) {

		
		// URL of Server B (where you want to upload the file)
		//$upload_url = 'https://sect4.soulbowl.in/pos/uploadProductOrder.php';
		$upload_url = 'http://61.2.241.71/pos/uploadProductOrder.php';
		if (class_exists('PosOutbound') && PosOutbound::isStubbed()) {
			return PosOutbound::intercept(
				PosOutbound::CHANNEL_UPLOAD, $upload_url, array('file' => basename($file_path))
			);
		}

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


}
