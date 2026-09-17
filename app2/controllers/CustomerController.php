<?php
namespace app\controllers;

use Yii;
use app\models\City;
use app\models\Country;
use app\models\Customer;
use app\models\CustomerOtp;
use app\models\CustomerOtpVerification;
use app\components\InteraktApi;
use app\components\OTPService;
use app\models\Discount;
use app\models\Order;
use app\models\Outlet;
use app\models\OrderHold;
use app\models\Setting;
use app\models\State;
use yii\web\Controller;
use yii\web\Response;

/**
 * Partial Yii 2 port of protected/modules/api/controllers/CustomerController.php.
 *
 * The Yii 1 controller has twenty actions. The six read paths that belong to
 * the customer domain are ported here; the rest are deliberately left with
 * Yii 1, for reasons recorded against each group below. Yii 1 continues to
 * serve every /api/customer/* route, so nothing has to move before it is ready.
 *
 * Ported:      discounts, countryList, stateList, cityList, index, get, setting,
 *              holdOrderList, orderList, getLatestBill, getOrder, verifyOTP,
 *              update, verifywhatappotp, getOrderHold, sendOTP, add,
 *              sentwhatappotp, uploadwhatapporder
 *
 * Seven of those - sendOTP, sentwhatappotp, verifywhatappotp, add, update and
 * uploadwhatapporder - send WhatsApp messages or register customers with an
 * external CRM, so they could not be tested differentially until the outbound
 * stub landed (lib/PosOutbound.php). With POS_STUB_OUTBOUND=1 the request is
 * recorded rather than made, and the harness compares the recorded calls
 * alongside the response and the database state.
 *
 * Not ported - needs the Order model:
 *   These render Order::toArray(), which is 173 lines of a 1,246-line model and
 *   pulls in the core POS entity. It belongs with the order module's port, not
 *   with customer.
 *
 * Not ported - uploads a file to a remote server:
 *   uploadbill
 *   This one receives a multipart upload, writes it under webroot, and posts it
 *   on with an inline cURL request built in the controller rather than through
 *   InteraktApi - so the stub does not intercept it yet. It needs an upload
 *   channel on PosOutbound first.
 *
 * Response envelopes are reproduced exactly, as with the loyalty and emp ports.
 */
class CustomerController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    private function envelope($action)
    {
        return [
            'controller' => 'customer',
            'action' => $action,
            'status' => 'NOK',
        ];
    }

    /** POST /v2/api/customer/discounts */
    public function actionDiscounts()
    {
        $out = $this->envelope('discounts');

        // Explicit ordering; the Yii 1 route is ordered the same way.
        $discounts = Discount::find()
            ->where(['status' => Discount::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        if (empty($discounts)) {
            $out['message'] = 'No data to display';
            return $out;
        }

        $list = [];
        foreach ($discounts as $discount) {
            $list[] = $discount->toApiArray();
        }
        $out['status'] = 'OK';
        $out['discounts'] = $list;
        return $out;
    }

    /** POST /v2/api/customer/country-list */
    public function actionCountryList()
    {
        $out = $this->envelope('countryList');

        $models = Country::find()->orderBy(['id' => SORT_DESC])->all();
        if (!$models) {
            $out['message'] = 'No data to display';
            return $out;
        }

        $list = [];
        foreach ($models as $model) {
            $list[] = $model->toApiArray();
        }
        $out['status'] = 'OK';
        $out['countrylist'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/customer/state-list
     *
     * The Yii 1 version builds this condition by concatenating the request
     * parameter into SQL - addCondition('country_id =' . $id). Here it is a
     * bound parameter, which is both safe and equivalent for the numeric ids
     * the route is meant to receive.
     */
    public function actionStateList($id = null)
    {
        $out = $this->envelope('stateList');

        $query = State::find()->orderBy(['id' => SORT_DESC]);
        if ($id !== null && $id !== '') {
            $query->andWhere(['country_id' => $id]);
        }
        $models = $query->all();

        if (!$models) {
            $out['message'] = 'No data to display';
            return $out;
        }

        $list = [];
        foreach ($models as $model) {
            $list[] = $model->toApiArray();
        }
        $out['status'] = 'OK';
        $out['statelist'] = $list;
        return $out;
    }

    /** POST /v2/api/customer/city-list - same parameter-binding note as stateList. */
    public function actionCityList($id = null)
    {
        $out = $this->envelope('cityList');

        $query = City::find()->orderBy(['id' => SORT_DESC]);
        if ($id !== null && $id !== '') {
            $query->andWhere(['state_id' => $id]);
        }
        $models = $query->all();

        if (!$models) {
            $out['message'] = 'No data to display';
            return $out;
        }

        $list = [];
        foreach ($models as $model) {
            $list[] = $model->toApiArray();
        }
        $out['status'] = 'OK';
        $out['citylist'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/customer/index
     *
     * Lists every customer. Note that this writes: toApiArray1() resolves the
     * loyalty row through getOrCreate(), so listing customers inserts a zeroed
     * loyalty row for anyone who lacks one. That is the Yii 1 behaviour.
     */
    public function actionIndex()
    {
        $out = $this->envelope('index');

        $models = Customer::find()->orderBy(['id' => SORT_DESC])->all();
        if (!$models) {
            $out['message'] = 'No data to display';
            return $out;
        }

        $list = [];
        foreach ($models as $model) {
            $list[] = $model->toApiArray1();
        }
        $out['status'] = 'OK';
        $out['customerlist'] = $list;
        return $out;
    }

    /** POST /v2/api/customer/get */
    public function actionGet($id)
    {
        $out = $this->envelope('get');

        $model = Customer::findOne($id);
        if (!$model) {
            $out['message'] = 'Customer not found';
            return $out;
        }

        $out['status'] = 'OK';
        // Yii 1 wrapped this single record in a list; preserved.
        $out['profile'][] = $model->toApiArray();
        return $out;
    }

    /** POST /v2/api/customer/setting */
    public function actionSetting()
    {
        $out = $this->envelope('setting');

        $model = Setting::find()->one();
        if (!$model) {
            $out['message'] = 'Setting not found';
            return $out;
        }

        $out['status'] = 'OK';
        $out['profile'][] = $model->toApiArray();
        return $out;
    }

    /**
     * POST /v2/api/customer/hold-order-list
     *
     * status=1 lists orders for an outlet, status=2 lists held orders. Both use
     * the compact toArray1() payload.
     *
     * The Yii 1 version builds this with addCondition('outlet_id =' . $id),
     * concatenating a request parameter into SQL. Bound here.
     */
    public function actionHoldOrderList($id, $status)
    {
        $out = $this->envelope('holdOrderList');

        $orders = [];
        if ((string)$status === '1') {
            $orders = Order::find()
                ->where(['outlet_id' => $id])
                ->orderBy(['id' => SORT_ASC])
                ->all();
        } elseif ((string)$status === '2') {
            $orders = OrderHold::find()
                ->where(['outlet_id' => $id])
                ->orderBy(['id' => SORT_ASC])
                ->all();
        }

        if (empty($orders)) {
            $out['message'] = 'No data to display';
            return $out;
        }

        $list = [];
        foreach ($orders as $order) {
            $list[] = $order->toApiArray1();
        }
        $out['status'] = 'OK';
        $out['orders'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/customer/order-list
     *
     * status=1 returns billed orders for an outlet, status=2 held ones, both in
     * full with their line items.
     *
     * Unpaginated, like the Yii 1 version: outlet 5 holds over 1.5 million
     * orders and neither framework can serve that. See the note in the commit
     * that added holdOrderList.
     *
     * The Yii 1 version filters with findAllByAttributes() and no ordering, so
     * the sequence was left to MySQL; ordered by id on both sides.
     */
    public function actionOrderList($id, $status)
    {
        $out = $this->envelope('orderList');

        $orders = [];
        if ((string)$status === '1') {
            $orders = Order::find()
                ->where(['outlet_id' => $id])
                ->orderBy(['id' => SORT_ASC])
                ->all();
        } elseif ((string)$status === '2') {
            $orders = OrderHold::find()
                ->where(['outlet_id' => $id])
                ->orderBy(['id' => SORT_ASC])
                ->all();
        }

        if (empty($orders)) {
            $out['message'] = 'No data to display';
            return $out;
        }

        $list = [];
        foreach ($orders as $order) {
            $list[] = $order->toApiArray();
        }
        $out['status'] = 'OK';
        $out['orders'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/customer/get-latest-bill?id=OUTLET
     *
     * The highest bill number issued at an outlet, with that outlet's prefix.
     * The Yii 1 version concatenates $id into the condition; bound here.
     */
    public function actionGetLatestBill($id)
    {
        $out = $this->envelope('GetLatestBill');

        $order = Order::find()
            ->where(['outlet_id' => $id])
            ->orderBy(['bill_no' => SORT_DESC])
            ->one();

        if (empty($order)) {
            $out['message'] = 'Order not available';
            return $out;
        }

        $outlet = Outlet::findOne($id);
        // Key order follows Yii 1: bill_prefix is set before status.
        $out['bill_prefix'] = $outlet ? $outlet->bill_prefix : 'B';
        $out['status'] = 'OK';
        // bill_no is an int column; Yii 1 emitted it as a string.
        $out['bill_no'] = $order->bill_no === null ? null : (string)$order->bill_no;
        return $out;
    }

    /**
     * POST /v2/api/customer/get-order?id=N
     *
     * The bill view of an order: gross and net totals, loyalty figures and the
     * line items in their return form.
     */
    public function actionGetOrder($id)
    {
        $out = $this->envelope('getOrder');

        $order = Order::findOne($id);
        if (empty($order)) {
            $out['message'] = 'Order not available';
            return $out;
        }

        $out['status'] = 'OK';
        // Yii 1 wraps the single order in a list; preserved.
        $out['order'][] = $order->toApiArray2();
        return $out;
    }

    /**
     * POST /v2/api/customer/verify-otp
     *
     * Checks a code against the customer's most recent unverified OTP and, on
     * success, marks the customer as WhatsApp-verified (is_enable_wa = 2).
     *
     * The companion sendOTP action is not ported: it despatches a real WhatsApp
     * message, so a comparison run would message a real phone number twice.
     */
    public function actionVerifyOtp()
    {
        $out = $this->envelope('verifyOTP');
        $req = Yii::$app->request;

        $customerId = $req->post('customer_id');
        $otpCode = $req->post('otp_code');
        $phoneNumber = $req->post('phone_number');

        if (!$customerId && !$phoneNumber) {
            $out['message'] = 'Customer ID or phone number is required';
            return $out;
        }
        if (!$otpCode) {
            $out['message'] = 'OTP code is required';
            return $out;
        }

        if ($customerId) {
            $model = Customer::findOne($customerId);
        } else {
            $model = Customer::getUserByContactNo(preg_replace('/[^0-9]/', '', $phoneNumber));
        }

        if (!$model) {
            $out['message'] = 'Customer not found';
            return $out;
        }

        $result = CustomerOtp::verifyOTP($model->id, $otpCode);

        if (!$result['success']) {
            $out['message'] = $result['message'];
            return $out;
        }

        $model->is_enable_wa = 2;   // verified
        $model->save(false);

        // toApiArray1() casts is_enable_wa to a string, because every other
        // caller reads it from the database where Yii 1 yields a string. Here the
        // attribute was just assigned in PHP, so Yii 1 emits the int it holds.
        // Restore that just-assigned int so the payloads agree.
        $profile = $model->toApiArray1();
        $profile['is_enable_wa'] = 2;

        $out['status'] = 'OK';
        $out['message'] = $result['message'];
        $out['data'] = [
            'customer_id' => (string)$model->id,
            'customer_name' => $model->name,
            'phone_number' => $model->contact_no,
            'verified_at' => $result['verified_at'],
            'customer_profile' => $profile,
        ];
        return $out;
    }

    /**
     * POST /v2/api/customer/update?id=N
     *
     * Requires name, address, city_id, state_id, country_id, zip_code and
     * contact_no together; anything less is silently ignored, as in Yii 1,
     * which leaves the NOK envelope untouched with no message.
     *
     * Two behaviours carried over deliberately:
     *
     *  - state_id is overwritten with 1 immediately after being read from the
     *    request ("activates account set 1" in the original). Whatever the
     *    caller sends for state_id is therefore discarded. It looks like the
     *    column is being used both as a geographic state and as a status flag,
     *    but changing it would alter stored data.
     *  - the duplicate-phone check excludes the customer being edited. Without
     *    that, getUserByContactNo() matches the customer against itself and
     *    every update that did not also change the phone number was rejected
     *    with "Contact no. already in use." Fixed on the Yii 1 side too.
     */
    public function actionUpdate($id)
    {
        $out = $this->envelope('update');
        $req = Yii::$app->request;

        $model = Customer::findOne($id);
        if (!$model) {
            $out['message'] = 'Customer not found';
            return $out;
        }

        $required = ['name', 'address', 'city_id', 'state_id', 'country_id', 'zip_code', 'contact_no'];
        foreach ($required as $field) {
            if ($req->post($field) === null) {
                return $out;   // Yii 1 falls through with no message
            }
        }

        $model->name = $req->post('name');
        $model->address = $req->post('address');
        $model->city_id = $req->post('city_id');
        $model->state_id = $req->post('state_id');
        $model->country_id = $req->post('country_id');
        $model->zip_code = $req->post('zip_code');
        $model->contact_no = $req->post('contact_no');

        foreach (['email', 'opening_balance', 'credit_limit', 'payment_days'] as $optional) {
            if ($req->post($optional) !== null) {
                $model->$optional = $req->post($optional);
            }
        }

        $existing = Customer::getUserByContactNo($model->contact_no);
        if ($existing && $existing->id != $model->id) {
            $out['message'] = 'Contact no. already in use.';
            return $out;
        }

        $model->state_id = 1;   // see the note above

        if (!$model->save(false)) {
            $out['message'] = '';
            return $out;
        }

        $out['status'] = 'OK';
        $out['profile'] = $model->toApiArray();
        $out['message'] = 'Customer is updated successfully';
        return $out;
    }

    /**
     * POST /v2/api/customer/verifywhatappotp?id=N&otp=CODE
     *
     * The older WhatsApp OTP flow, backed by its own table. Marks the customer
     * verified (is_enable_wa = 2) on success.
     *
     * Its companion sentwhatappotp is not ported - it sends a real message.
     */
    public function actionVerifywhatappotp($id, $otp)
    {
        $out = $this->envelope('verifywhatappotp');

        $model = Customer::findOne($id);
        if (!$model) {
            $out['message'] = 'user not found';
            return $out;
        }

        // Yii 1 sets the failure message first and overwrites it on success.
        $out['message'] = 'in-correct otp';

        if (CustomerOtpVerification::verifyOtp($model->id, $otp)) {
            $model->is_enable_wa = 2;
            $model->save(false);
            $out['status'] = 'OK';
            $out['message'] = 'verified';
        }
        return $out;
    }

    /**
     * POST /v2/api/customer/get-order-hold?id=N
     *
     * Returns a held order in full and then DELETES it - the caller is resuming
     * the order at the till, so the hold is consumed. Reproduced, including the
     * deletion, which is why this endpoint needs its fixture rebuilt between
     * comparison runs.
     */
    public function actionGetOrderHold($id)
    {
        $out = $this->envelope('getOrderHold');

        $order = OrderHold::findOne($id);
        if (empty($order)) {
            $out['message'] = 'Order not available';
            return $out;
        }

        $out['status'] = 'OK';
        // Rendered before the delete, as in Yii 1.
        $out['order'][] = $order->toApiArray();
        $order->delete();
        return $out;
    }

    /**
     * POST /v2/api/customer/send-otp
     *
     * Issues a code and despatches it over WhatsApp. The despatch goes through
     * the shared outbound stub, so with POS_STUB_OUTBOUND=1 nothing leaves the
     * server and the attempt is recorded for comparison instead.
     *
     * Note the Yii 1 behaviour on a failed send, reproduced here: the OTP has
     * already been generated at that point, so the action still reports OK and
     * adds a 'warning' key rather than failing the request.
     */
    public function actionSendOtp()
    {
        $out = $this->envelope('sendOTP');
        $req = Yii::$app->request;

        $phoneNumber = $req->post('phone_number');
        $customerId = $req->post('customer_id');

        if (!$phoneNumber) {
            $out['message'] = 'Phone number is required';
            return $out;
        }

        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($phoneNumber) != 10) {
            $out['message'] = 'Invalid phone number format';
            return $out;
        }

        $model = $customerId
            ? Customer::findOne($customerId)
            : Customer::getUserByContactNo($phoneNumber);

        if (!$model) {
            $out['message'] = 'Unable to find customer with provided details';
            return $out;
        }

        if (CustomerOtp::hasPendingOTP($model->id)) {
            $out['message'] = 'OTP already sent. Please wait before requesting again.';
            return $out;
        }

        $otpResult = CustomerOtp::generateOTP($model->id, $phoneNumber);
        if (!$otpResult['success']) {
            $out['message'] = $otpResult['message'];
            return $out;
        }

        try {
            OTPService::sendOTPWhatsApp($phoneNumber, $model->name, $otpResult['otp_code']);

            $model->is_enable_wa = 1;
            $model->save(false);

            $out['status'] = 'OK';
            $out['message'] = 'OTP sent successfully via WhatsApp';
            $out['data'] = [
                'customer_id' => (string)$model->id,
                'phone_number' => $phoneNumber,
                'otp_expires_in' => 300,
                'otp_id' => (string)$otpResult['otp_id'],
            ];
        } catch (\Throwable $e) {
            $out['message'] = 'OTP generated but failed to send via WhatsApp: ' . $e->getMessage();
            $out['status'] = 'OK';
            $out['data'] = [
                'customer_id' => (string)$model->id,
                'phone_number' => $phoneNumber,
                'otp_expires_in' => 300,
                'otp_id' => (string)$otpResult['otp_id'],
                'warning' => 'SMS delivery may have failed',
            ];
        }
        return $out;
    }

    /**
     * POST /v2/api/customer/add
     *
     * Requires name and contact_no; the address fields fall back to fixed
     * defaults (city 1, state 1, country 1, zip 160059) when not supplied.
     *
     * After saving, the customer is registered with the WhatsApp CRM. That call
     * is wrapped in a catch-all in Yii 1 and stays that way: a CRM failure must
     * not undo a customer who is already saved. With POS_STUB_OUTBOUND=1 it is
     * recorded rather than made.
     */
    public function actionAdd()
    {
        $out = $this->envelope('add');
        $req = Yii::$app->request;

        $name = $req->post('name');
        $contactNo = $req->post('contact_no');
        if ($name === null || $contactNo === null) {
            return $out;   // Yii 1 falls through with no message
        }

        $model = new Customer();
        $model->name = $name;
        $model->contact_no = $contactNo;
        $model->state_id = $req->post('state_id', 1);
        $model->city_id = $req->post('city_id', 1);
        $model->country_id = $req->post('country_id', 1);
        $model->zip_code = $req->post('zip_code', '160059');

        foreach (['email', 'opening_balance', 'credit_limit', 'payment_days', 'is_enable_wa'] as $optional) {
            if ($req->post($optional) !== null) {
                $model->$optional = $req->post($optional);
            }
        }

        if (Customer::getUserByContactNo($model->contact_no)) {
            $out['message'] = 'Contact no. already in use.';
            return $out;
        }

        $model->state_id = 1;   // "activates account set 1" - see actionUpdate

        if (!$model->save(false)) {
            $out['message'] = '';
            return $out;
        }

        try {
            $whatsappNo = preg_replace('/[^0-9]/', '', $model->contact_no);
            if ($whatsappNo != '') {
                $api = new InteraktApi(getenv('POS_INTERAKT_API_KEY') ?: null);
                $api->createCustomer([
                    'phoneNumber' => $whatsappNo,
                    'countryCode' => '+91',
                    'traits' => ['name' => $model->name, 'email' => $model->email],
                    'tags' => ['Added By POS'],
                ]);
            }
        } catch (\Throwable $e) {
            // swallowed, as in Yii 1: the customer is saved either way
        }

        $out['status'] = 'OK';
        $out['profile'][] = $model->toApiArray();
        $out['message'] = 'Customer is added successfully';
        return $out;
    }

    /**
     * POST /v2/api/customer/sentwhatappotp?id=N
     *
     * Issues a code and sends it on the welcome_message template.
     *
     * Two behaviours carried over: is_enable_wa is set to 1 but the model is
     * never saved, so it affects only the returned payload and not the stored
     * row; and 'message' is whatever sendApprovalOrderMessageNew returns, which
     * is null because that method has no return statement.
     */
    public function actionSentwhatappotp($id)
    {
        $out = $this->envelope('sentwhatappotp');

        $model = Customer::findOne($id);
        if (!$model) {
            $out['message'] = 'Setting not found';   // yes, that is the message
            return $out;
        }

        $model->is_enable_wa = 1;   // not saved - see above
        $otp = CustomerOtpVerification::generateOtp($model->id);

        $whatsappNo = preg_replace('/[^0-9]/', '', (string)$model->contact_no);
        if ($whatsappNo != '') {
            $api = new InteraktApi(getenv('POS_INTERAKT_API_KEY') ?: null);
            $out['message'] = $api->sendApprovalOrderMessageNew(
                'welcome_message', $whatsappNo, [$model->name, $otp], '', ''
            );
        }

        $out['status'] = 'OK';
        // toApiArray1 casts is_enable_wa to a string; Yii 1 emits the int it
        // just assigned. Same situation as verifyOTP.
        $profile = $model->toApiArray();
        $out['profile'][] = $profile;
        return $out;
    }
    /**
     * Yii 1 ignores the $urlPdf it builds and sends a hard-coded test PDF
     * instead, and leaves status at 'NOK' even when the send succeeds.
     * Both are reproduced: this endpoint's response is what the POS client
     * parses, so "fixing" either would be a behaviour change.
     */
    public function actionUploadwhatapporder($id)
    {
        $out = $this->envelope('uploadwhatapporder');

        $model = Customer::findOne($id);
        if (!$model) {
            $out['message'] = 'user not found';
            return $out;
        }

        $whatsappNo = preg_replace('/[^0-9]/', '', (string)$model->contact_no);

        $api = new InteraktApi(getenv('POS_INTERAKT_API_KEY') ?: null);
        $out['message'] = $api->sendApprovalOrderMessageNew(
            'purchase_order',
            $whatsappNo,
            [$model->name],
            ['http://61.2.241.71/pos/whatapporder/payBillTest.pdf'],
            'order.pdf'
        );

        // status deliberately left at 'NOK' - as in Yii 1
        return $out;
    }
}
