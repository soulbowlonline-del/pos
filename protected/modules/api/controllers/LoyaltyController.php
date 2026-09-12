<?php
class LoyaltyController extends GxController
{
    public function actionGetCustomerLoyalty() {
      $arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
    
        $customerId = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $phoneNumber = isset($_POST['phone_number']) ? $_POST['phone_number'] : null;
        
        if (!$customerId && $phoneNumber) {
            $customer = Customer::model()->findByAttributes(['contact_no' => $phoneNumber]);
            $customerId = $customer ? $customer->id : null;
        }
        
        if (!$customerId) {
            $arr['message'] = 'Customer not found'; 
            $this->sendJSONResponse($arr);
            return;
        }
        $loyaltyInfo = LoyaltyService::getCustomerLoyaltyInfo($customerId);
        $arr['status'] = 'OK';
        $arr['data'] = $loyaltyInfo;
        $this->sendJSONResponse($arr);
    }
    
    // Pre-redemption: Process redemption before order completion
    public function actionPreRedeemPoints() {
        $arr = array (
            'controller' => $this->id,
            'action' => $this->action->id,
            'status' => 'NOK' 
        );
        
        $customerId = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $pointsToRedeem = floatval(isset($_POST['points']) ? $_POST['points'] : 0);
        
        if (!$customerId || $pointsToRedeem <= 0) {
           $arr['message'] = 'Invalid parameters - customer_id and points required'; 
           $this->sendJSONResponse($arr);
            return;
        }
        
        $result = LoyaltyService::preProcessRedemption($customerId, $pointsToRedeem);
        
        if ($result['success']) {
            $arr['status'] = 'OK';
            $arr['message'] = 'Points pre-redeemed successfully';
            $arr['data'] = [
                'redemption_id' => $result['redemption_id'],
                'points_redeemed' => $pointsToRedeem,
                'remaining_points' => $result['remaining_points']
            ];
            $this->sendJSONResponse($arr);
        } else {
            $arr['message'] = $result['message'];
            $this->sendJSONResponse($arr);
        }
    }
    
    // Update redemption with order ID after order completion
    public function actionUpdateRedemptionWithOrder() {
        $arr = array (
            'controller' => $this->id,
            'action' => $this->action->id,
            'status' => 'NOK' 
        );
        
        $redemptionId = isset($_POST['redemption_id']) ? $_POST['redemption_id'] : null;
        $orderId = isset($_POST['order_id']) ? $_POST['order_id'] : null;
        
        if (!$redemptionId || !$orderId) {
           $arr['message'] = 'Invalid parameters - redemption_id and order_id required'; 
           $this->sendJSONResponse($arr);
            return;
        }
        
        $result = LoyaltyService::updateRedemptionWithOrderId($redemptionId, $orderId);
        
        if ($result) {
            $arr['status'] = 'OK';
            $arr['message'] = 'Redemption updated with order ID successfully';
            $this->sendJSONResponse($arr);
        } else {
            $arr['message'] = 'Failed to update redemption with order ID';
            $this->sendJSONResponse($arr);
        }
    }
    
    // Legacy method - kept for backward compatibility
    public function actionRedeemPoints() {
      $arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
        $orderId = isset($_POST['order_id']) ? $_POST['order_id'] : null;
        $pointsToRedeem = floatval(isset($_POST['points']) ? $_POST['points'] : 0);
        
        if (!$orderId || $pointsToRedeem <= 0) {
           $arr['message'] = 'Invalid parameters'; 
           $this->sendJSONResponse($arr);
            return;
        }
        
        $result = LoyaltyService::processOrderRedeem($orderId, $pointsToRedeem);
        
        if ($result) {
            $arr['status'] = 'OK';
            $arr['message'] = 'Points redeemed successfully';
            $this->sendJSONResponse($arr);
        } else {
            $arr['message'] = 'Failed to redeem points';
            $this->sendJSONResponse($arr);
        }
    }
    
    // Rollback pre-redemption if order fails
    public function actionRollbackRedemption() {
        $arr = array (
            'controller' => $this->id,
            'action' => $this->action->id,
            'status' => 'NOK' 
        );
        
        $redemptionId = isset($_POST['redemption_id']) ? $_POST['redemption_id'] : null;
        
        if (!$redemptionId) {
           $arr['message'] = 'Invalid parameters - redemption_id required'; 
           $this->sendJSONResponse($arr);
            return;
        }
        
        $result = LoyaltyService::rollbackPreRedemption($redemptionId);
        
        if ($result) {
            $arr['status'] = 'OK';
            $arr['message'] = 'Redemption rolled back successfully';
            $this->sendJSONResponse($arr);
        } else {
            $arr['message'] = 'Failed to rollback redemption';
            $this->sendJSONResponse($arr);
        }
    }
    
    // Deduct earned loyalty points when items are refunded
    public function actionRefundDeductPoints() {
        $arr = array(
            'controller' => $this->id,
            'action'     => $this->action->id,
            'status'     => 'NOK'
        );

        $orderId    = isset($_POST['order_id'])    ? $_POST['order_id']                    : null;
        $earnPoints = intval(isset($_POST['earn_points']) ? $_POST['earn_points'] : 0);

        if (!$orderId || $earnPoints <= 0) {
            $arr['message'] = 'Invalid parameters - order_id and earn_points (> 0) required';
            $this->sendJSONResponse($arr);
            return;
        }

        $result = LoyaltyService::processRefundDeductPoints($orderId, $earnPoints);

        if ($result !== false) {
            $arr['status']          = 'OK';
            $arr['message']         = "Deducted {$result} loyalty points for refund on order #{$orderId}";
            $arr['points_deducted'] = $result;
        } else {
            $arr['message'] = 'No loyalty points to deduct or deduction failed';
        }
        $this->sendJSONResponse($arr);
    }

    public function actionGetTransactionHistory() {
      $arr = array (
				'controller' => $this->id,
				'action' => $this->action->id,
				'status' => 'NOK' 
		);
        $customerId = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $limit = intval(isset($_POST['limit']) ? $_POST['limit'] : 20);
        
        if (!$customerId) {
            $arr['message'] = 'Customer ID required';
            $this->sendJSONResponse($arr);
            return;
        }
        
        $criteria = new CDbCriteria();
        $criteria->condition = 'customer_id = :customer_id';
        $criteria->params = [':customer_id' => $customerId];
        $criteria->order = 'created_at DESC';
        $criteria->limit = $limit;
        
        $transactions = LoyaltyTransaction::model()->findAll($criteria);
        $data = array();
        
        foreach ($transactions as $trans) {
            $data[] = [
                'id' => $trans->id,
                'type' => $trans->transaction_type,
                'points' => $trans->points,
                'description' => $trans->description,
                'date' => $trans->created_at,
            ];
        }
        
        $arr['status'] = 'OK';
        $arr['data'] = $data;
        $this->sendJSONResponse($arr);
    }
}