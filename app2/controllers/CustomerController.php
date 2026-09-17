<?php
namespace app\controllers;

use Yii;
use app\models\City;
use app\models\Country;
use app\models\Customer;
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
 *              holdOrderList, orderList, getLatestBill
 *
 * Not ported - needs the Order model:
 *   getOrder        needs Order::toArray2()
 *   getOrderHold    reads an OrderHold and then deletes it, so it cannot be
 *                   compared without rebuilding the fixture between calls
 *   These render Order::toArray(), which is 173 lines of a 1,246-line model and
 *   pulls in the core POS entity. It belongs with the order module's port, not
 *   with customer.
 *
 * Not ported - outward-facing side effects:
 *   sendOTP, verifyOTP, sentwhatappotp, verifywhatappotp, uploadwhatapporder,
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
}
