<?php
namespace app\components;

use app\models\WhatsappLogs;
use PosOutbound;

/**
 * Yii 2 port of protected/components/InteraktApi.php.
 *
 * The request payload and the target URL are built exactly as Yii 1 builds
 * them, because both are recorded by the stub and compared: the test checks
 * that the port would have sent the same message, not merely that it returned
 * the same response.
 */
class InteraktApi
{
    public $apiUrl = 'https://api.interakt.ai/v1/public';
    public $apiKey;

    /**
     * Yii 1 configures the key as getenv('POS_INTERAKT_API_KEY') or '', so it
     * is never null there; callers here pass `?: null`, which is kept as ''.
     */
    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey === null ? '' : $apiKey;
    }

    private function sendRequest($method, $endpoint, $data = [], $queryParams = [])
    {
        $url = $this->apiUrl . $endpoint;
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        if (class_exists('PosOutbound') && PosOutbound::isStubbed()) {
            return PosOutbound::intercept(
                PosOutbound::CHANNEL_HTTP,
                strtoupper($method) . ' ' . $url,
                $data
            );
        }

        // Live: the same request Yii 1 makes, and the same failures - a curl
        // error or an HTTP status of 400 and up is an exception, the body of a
        // success is decoded.
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
                throw new \RuntimeException('Invalid HTTP method: ' . $method);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Curl error: ' . $error);
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException('Error while calling API: ' . $response);
        }
        return json_decode($response, true);
    }

    /** Sends a template and records the attempt, as the Yii 1 version does. */
    public function sendTemplate($data, $extraData = [])
    {
        $response = $this->sendRequest('POST', '/message/', $data);

        $log = new WhatsappLogs();
        $log->number = $data['phoneNumber'];
        $log->status = isset($response['result']) ? ($response['result'] ? 1 : 0) : 0;
        $log->message = isset($response['message']) ? $response['message'] : '';
        $log->message_id = isset($response['id']) ? $response['id'] : '';
        $log->template_name = $data['template']['name'];
        $log->user_id = isset($extraData['user_id']) ? $extraData['user_id'] : null;
        $log->computer_name = isset($extraData['computer_name']) ? $extraData['computer_name'] : null;
        $log->created_at = date('Y-m-d H:i:s');
        $log->save();   // validated, as in Yii 1 - see WhatsappLogs::rules()

        return $response;
    }

    public function sendOtpMessage($template, $phone, $bodyValues, $buttonValues)
    {
        $data = [
            'countryCode' => '+91',
            'phoneNumber' => $phone,
            'type' => 'Template',
            'template' => [
                'name' => $template,
                'languageCode' => 'en',
                'bodyValues' => $bodyValues,
                'buttonValues' => $buttonValues,
            ],
        ];
        return $this->sendTemplate($data);
    }

    public function createCustomer($data)
    {
        return $this->sendRequest('POST', '/track/users/', $data);
    }

    public function getTemplates($queryParams = [])
    {
        return $this->sendRequest('GET', '/track/organization/templates', [], $queryParams);
    }

    /** Returns null, as the Yii 1 method has no return statement. */
    public function sendApprovalOrderMessage($phone, $poId, $pdfUrl, $template = 'purchase_order_approved')
    {
        $data = [
            'countryCode' => '+91',
            'phoneNumber' => $phone,
            'type' => 'Template',
            'template' => [
                'name' => $template,
                'languageCode' => 'en',
                'headerValues' => [$pdfUrl],
                'fileName' => 'mpdf.pdf',
                'bodyValues' => [$poId],
            ],
        ];

        $this->sendTemplate($data);
        return null;
    }

    /**
     * Puts a file on the webshop's FTP server. Yii 1 dies with a bare message
     * when it cannot connect, which is reproduced.
     */
    public function uploadFileToSoulBowl($fileName, $id)
    {
        if (class_exists('PosOutbound') && PosOutbound::isStubbed()) {
            return PosOutbound::intercept(
                PosOutbound::CHANNEL_FTP, 'ftp-upload', ['file' => $fileName, 'id' => $id]
            );
        }

        // Live, as Yii 1: the local file is put under its own name. $tofile and
        // $uploadFileName are computed there and never used, so not here.
        $ftpServer = '143.110.254.206';
        $ftpConn = ftp_connect($ftpServer);
        if (!$ftpConn) {
            die('Could not connect to ' . $ftpServer);   // as in Yii 1
        }
        ftp_login($ftpConn, 'soulbowlftp', getenv('POS_FTP_PASSWORD') !== false ? getenv('POS_FTP_PASSWORD') : '');
        ftp_pasv($ftpConn, true);
        if (ftp_put($ftpConn, $fileName, $fileName, FTP_BINARY)) {
            return true;
        }
        return false;
    }

    /**
     * Sends a template, optionally with a document header.
     *
     * Returns null: the Yii 1 method ends with $this->sendTemplate(...) and no
     * return statement, so its caller stores null in the response. Reproduced,
     * because customer/sentwhatappotp puts that value straight into its
     * 'message' key.
     */
    public function sendApprovalOrderMessageNew($template, $phone, $bodyValues, $pdfUrl, $fileName = 'text', $extraData = [])
    {
        if ($pdfUrl == '') {
            $data = [
                'countryCode' => '+91',
                'phoneNumber' => $phone,
                'type' => 'Template',
                'template' => [
                    'name' => $template,
                    'languageCode' => 'en',
                    'bodyValues' => $bodyValues,
                ],
            ];
        } else {
            $data = [
                'countryCode' => '+91',
                'phoneNumber' => $phone,
                'type' => 'Template',
                'template' => [
                    'name' => $template,
                    'languageCode' => 'en',
                    'headerValues' => $pdfUrl,
                    'fileName' => $fileName,
                    'bodyValues' => $bodyValues,
                ],
            ];
        }

        $this->sendTemplate($data, $extraData);
        return null;   // as in Yii 1
    }

    /**
     * Ported from InteraktApi::uploadFileToServer() - posts a file to the
     * remote endpoint that serves the WhatsApp attachment.
     *
     * Returns 'Error: File not found.', true or false; never the response
     * body. The stub records the call and answers true, after the file_exists
     * guard so that branch survives being stubbed.
     */
    public function uploadFileToServer($filePath)
    {
        $uploadUrl = (getenv('POS_UPLOAD_URL') ?: 'http://61.2.241.71/pos/uploadProductOrder.php');

        if (!file_exists($filePath)) {
            return 'Error: File not found.';
        }

        if (class_exists('PosOutbound') && \PosOutbound::isStubbed()) {
            \PosOutbound::record(
                \PosOutbound::CHANNEL_UPLOAD, $uploadUrl, ['file' => basename($filePath)]
            );
            return true;
        }

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new \CURLFile($filePath, 'application/pdf', basename($filePath)),
            'token' => getenv('POS_UPLOAD_TOKEN') ?: '',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response === false ? false : true;
    }
}
