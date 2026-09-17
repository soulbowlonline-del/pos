<?php
namespace app\controllers;

use Yii;
use app\components\LoyaltyService;
use app\models\Customer;
use yii\web\Controller;
use yii\web\Response;

/**
 * Yii 2 port of protected/modules/api/controllers/LoyaltyController.php.
 *
 * The response envelope is reproduced exactly - same keys, same order, same
 * 'OK'/'NOK' status strings, same 'controller'/'action' values - so existing
 * clients cannot tell which framework answered. That is the point: routes move
 * across one at a time without a coordinated client release.
 */
class LoyaltyController extends Controller
{
    /** Clients post form data without a Yii 2 CSRF token. */
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /**
     * POST /v2/api/loyalty/get-customer-loyalty
     * Accepts customer_id, or phone_number to look one up.
     */
    public function actionGetCustomerLoyalty()
    {
        // 'action' mirrors the Yii 1 action id (camelCase), not Yii 2's
        // hyphenated route, so the payload is unchanged for clients.
        $out = [
            'controller' => 'loyalty',
            'action' => 'getCustomerLoyalty',
            'status' => 'NOK',
        ];

        $post = Yii::$app->request->post();
        $customerId = isset($post['customer_id']) && $post['customer_id'] !== '' ? $post['customer_id'] : null;
        $phoneNumber = isset($post['phone_number']) && $post['phone_number'] !== '' ? $post['phone_number'] : null;

        if (!$customerId && $phoneNumber) {
            $customer = Customer::findByPhone($phoneNumber);
            $customerId = $customer ? $customer->id : null;
        }

        if (!$customerId) {
            $out['message'] = 'Customer not found';
            return $out;
        }

        $out['status'] = 'OK';
        $out['data'] = LoyaltyService::getCustomerLoyaltyInfo($customerId);
        return $out;
    }
}
