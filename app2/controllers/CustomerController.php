<?php
namespace app\controllers;

use Yii;
use app\models\City;
use app\models\Country;
use app\models\Customer;
use app\models\CustomerOtp;
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
 *              update
 *
 * Not ported - needs the Order model:
 *   getOrderHold    reads an OrderHold and then deletes it, so it cannot be
 *                   compared without rebuilding the fixture between calls
 *   These render Order::toArray(), which is 173 lines of a 1,246-line model and
 *   pulls in the core POS entity. It belongs with the order module's port, not
 *   with customer.
 *
 * Not ported - outward-facing side effects:
 *   sendOTP, sentwhatappotp, verifywhatappotp, uploadwhatapporder,
 *   uploadbill, add, update
 *   These send real SMS and WhatsApp messages, register customers with an
 *   external CRM, and upload files to a remote server. A differential test
 *   would have to trigger those for real against both stacks, so they need
 *   their own approach - a stubbed transport, or manual verification - rather
 *   than being ported blind.
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
}
