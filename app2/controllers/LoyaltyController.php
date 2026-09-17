<?php
namespace app\controllers;

use Yii;
use app\components\LoyaltyService;
use app\models\Customer;
use app\models\LoyaltyTransaction;
use yii\web\Controller;
use yii\web\Response;

/**
 * Yii 2 port of protected/modules/api/controllers/LoyaltyController.php.
 *
 * Every response envelope is reproduced exactly - same keys in the same order,
 * the same 'OK'/'NOK' strings, the same message text, and the camelCase Yii 1
 * action id rather than Yii 2's hyphenated route - so a client cannot tell
 * which framework answered. That is what lets routes move one at a time.
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
     * Builds the envelope the Yii 1 controller opened every action with.
     * $action is the Yii 1 action id, not the Yii 2 route.
     */
    private function envelope($action)
    {
        return [
            'controller' => 'loyalty',
            'action' => $action,
            'status' => 'NOK',
        ];
    }

    private function post($key, $default = null)
    {
        $v = Yii::$app->request->post($key);
        return ($v === null || $v === '') ? $default : $v;
    }

    /** POST /v2/api/loyalty/get-customer-loyalty */
    public function actionGetCustomerLoyalty()
    {
        $out = $this->envelope('getCustomerLoyalty');

        $customerId = $this->post('customer_id');
        $phoneNumber = $this->post('phone_number');

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

    /** POST /v2/api/loyalty/pre-redeem-points */
    public function actionPreRedeemPoints()
    {
        $out = $this->envelope('preRedeemPoints');

        $customerId = $this->post('customer_id');
        $pointsToRedeem = floatval($this->post('points', 0));

        if (!$customerId || $pointsToRedeem <= 0) {
            $out['message'] = 'Invalid parameters - customer_id and points required';
            return $out;
        }

        $result = LoyaltyService::preProcessRedemption($customerId, $pointsToRedeem);

        if ($result['success']) {
            $out['status'] = 'OK';
            $out['message'] = 'Points pre-redeemed successfully';
            $out['data'] = [
                'redemption_id' => $result['redemption_id'],
                'points_redeemed' => $pointsToRedeem,
                'remaining_points' => $result['remaining_points'],
            ];
            return $out;
        }

        $out['message'] = $result['message'];
        return $out;
    }

    /** POST /v2/api/loyalty/update-redemption-with-order */
    public function actionUpdateRedemptionWithOrder()
    {
        $out = $this->envelope('updateRedemptionWithOrder');

        $redemptionId = $this->post('redemption_id');
        $orderId = $this->post('order_id');

        if (!$redemptionId || !$orderId) {
            $out['message'] = 'Invalid parameters - redemption_id and order_id required';
            return $out;
        }

        if (LoyaltyService::updateRedemptionWithOrderId($redemptionId, $orderId)) {
            $out['status'] = 'OK';
            $out['message'] = 'Redemption updated with order ID successfully';
        } else {
            $out['message'] = 'Failed to update redemption with order ID';
        }
        return $out;
    }

    /**
     * POST /v2/api/loyalty/redeem-points
     * Legacy path, kept for backward compatibility as in the Yii 1 controller.
     */
    public function actionRedeemPoints()
    {
        $out = $this->envelope('redeemPoints');

        $orderId = $this->post('order_id');
        $pointsToRedeem = floatval($this->post('points', 0));

        if (!$orderId || $pointsToRedeem <= 0) {
            $out['message'] = 'Invalid parameters';
            return $out;
        }

        if (LoyaltyService::processOrderRedeem($orderId, $pointsToRedeem)) {
            $out['status'] = 'OK';
            $out['message'] = 'Points redeemed successfully';
        } else {
            $out['message'] = 'Failed to redeem points';
        }
        return $out;
    }

    /** POST /v2/api/loyalty/rollback-redemption */
    public function actionRollbackRedemption()
    {
        $out = $this->envelope('rollbackRedemption');

        $redemptionId = $this->post('redemption_id');
        if (!$redemptionId) {
            $out['message'] = 'Invalid parameters - redemption_id required';
            return $out;
        }

        if (LoyaltyService::rollbackPreRedemption($redemptionId)) {
            $out['status'] = 'OK';
            $out['message'] = 'Redemption rolled back successfully';
        } else {
            $out['message'] = 'Failed to rollback redemption';
        }
        return $out;
    }

    /** POST /v2/api/loyalty/refund-deduct-points */
    public function actionRefundDeductPoints()
    {
        $out = $this->envelope('refundDeductPoints');

        $orderId = $this->post('order_id');
        $earnPoints = intval($this->post('earn_points', 0));

        if (!$orderId || $earnPoints <= 0) {
            $out['message'] = 'Invalid parameters - order_id and earn_points (> 0) required';
            return $out;
        }

        $result = LoyaltyService::processRefundDeductPoints($orderId, $earnPoints);

        if ($result !== false) {
            $out['status'] = 'OK';
            $out['message'] = "Deducted {$result} loyalty points for refund on order #{$orderId}";
            $out['points_deducted'] = $result;
        } else {
            $out['message'] = 'No loyalty points to deduct or deduction failed';
        }
        return $out;
    }

    /** POST /v2/api/loyalty/get-transaction-history */
    public function actionGetTransactionHistory()
    {
        $out = $this->envelope('getTransactionHistory');

        $customerId = $this->post('customer_id');
        $limit = intval($this->post('limit', 20));

        if (!$customerId) {
            $out['message'] = 'Customer ID required';
            return $out;
        }

        $rows = LoyaltyTransaction::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();

        $data = [];
        foreach ($rows as $trans) {
            $data[] = [
                'id' => $trans->id,
                'type' => $trans->transaction_type,
                'points' => $trans->points,
                'description' => $trans->description,
                'date' => $trans->created_at,
            ];
        }

        $out['status'] = 'OK';
        $out['data'] = $data;
        return $out;
    }
}
