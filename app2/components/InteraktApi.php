<?php
namespace app\components;

use app\models\WhatsappLogs;
use PosOutbound;

/**
 * Yii 2 port of the Interakt client, limited to the OTP path.
 *
 * Only sendOtpMessage and the sendTemplate/sendRequest chain beneath it are
 * ported - enough for customer/sendOTP. The other six methods on the Yii 1
 * component belong with the purchase-order and upload flows that use them.
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

    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey;
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

        // Not stubbed: this path is intentionally not implemented on the Yii 2
        // side yet. Yii 1 still serves every route that sends for real, so
        // reaching here means a route moved across before its transport did.
        throw new \RuntimeException(
            'InteraktApi: live outbound is not implemented in the Yii 2 port; '
            . 'set POS_STUB_OUTBOUND=1 or use the Yii 1 route.'
        );
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
        $log->save(false);

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
}
