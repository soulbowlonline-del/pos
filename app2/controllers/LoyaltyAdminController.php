<?php
namespace app\controllers;

use app\components\Ui;
use app\models\CustomerLoyalty;
use app\models\LoyaltySettings;
use app\models\LoyaltyTransaction;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/LoyaltyAdminController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class LoyaltyAdminController extends BaseUiController
{
    public function actionIndex() {
        return $this->render('index');
    }
    
    public function actionCustomers() {
        $searchName  = isset($_GET['search_name'])  ? trim($_GET['search_name'])  : '';
        $searchPhone = isset($_GET['search_phone']) ? trim($_GET['search_phone']) : '';

        $query = CustomerLoyalty::find()->alias('t');
        $query->with = ['customer'];
        $query->orderBy(['t.total_points' => SORT_DESC, 't.id' => SORT_DESC]);

        if ($searchName !== '' || $searchPhone !== '') {
            // Need a JOIN to filter on customer columns — use together:true only for this filter
            $query->with = [];
        $query->joinWith(['customer' => function ($q) { $q->alias('customer'); }]);
            if ($searchName !== '') {
                $query->andWhere(['like', 'customer.name', $searchName]);
            }
            if ($searchPhone !== '') {
                $query->andWhere(['like', 'customer.contact_no', $searchPhone]);
            }
        }

        $dataProvider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 20], 'sort'       => false]);

        return $this->render('customers', [
            'dataProvider' => $dataProvider,
            'searchName'   => $searchName,
            'searchPhone'  => $searchPhone,
        ]);
    }
    
    public function actionViewTransactions($id) {
        $id = intval($id);
        $loyalty = CustomerLoyalty::find()->with(['customer'])->where(['customer_id' => $id])->one();

        if (!$loyalty) {
            throw new NotFoundHttpException('Customer loyalty record not found.');
        }

        $query = LoyaltyTransaction::find()->alias('t');
        $query->andWhere('t.customer_id = :cid', [':cid' => $id]);
        
        $query->with = ['order'];
        $query->orderBy(['t.created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 20], 'sort'       => false]);

        return $this->render('viewTransactions', [
            'loyalty'      => $loyalty,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionGetCustomerLoyaltyInfo() {
        $customerId = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        if ($customerId) {
            $loyalty = CustomerLoyalty::findOne(['customer_id' => $customerId]);
            if ($loyalty) {
                echo json_encode([
                    'status' => 'success',
                    'data'   => [
                        'total_points'      => (int)$loyalty->total_points,
                        'lifetime_earned'   => (int)$loyalty->lifetime_earned,
                        'lifetime_redeemed' => (int)$loyalty->lifetime_redeemed,
                    ],
                ]);
                Yii::$app->end();
            }
        }
        echo json_encode(['status' => 'error']);
        Yii::$app->end();
    }

    public function actionAdjustPoints() {
        $selectedCustomerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;

        if (Yii::$app->request->isPost) {
            $customerId  = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
            $points      = floatval(isset($_POST['points']) ? $_POST['points'] : 0);
            $description = isset($_POST['description']) && trim($_POST['description']) !== ''
                           ? trim($_POST['description']) : 'Admin adjustment';

            if ($customerId && $points != 0) {
                $loyalty   = CustomerLoyalty::getOrCreateCustomerLoyalty($customerId);
                $absPoints = abs($points);

                try {
                    if ($points > 0) {
                        $loyalty->total_points    += $absPoints;
                        $loyalty->lifetime_earned += $absPoints;
                    } else {
                        $loyalty->total_points -= min($absPoints, max(0, $loyalty->total_points));
                    }
                    $loyalty->save();

                    $trans                   = new LoyaltyTransaction();
                    $trans->customer_id      = $customerId;
                    $trans->transaction_type = 'ADJUST';
                    $trans->points           = $points;
                    $trans->description      = $description;
                    $trans->save();

                    Yii::$app->user->setFlash('success', 'Points adjusted successfully.');
                    return $this->redirect(['viewTransactions', 'id' => $customerId]);
                    return;
                } catch (\Exception $e) {
                    Yii::$app->user->setFlash('error', 'Failed to adjust points.');
                }
            } else {
                Yii::$app->user->setFlash('error', 'Please select a customer and enter a non-zero point value.');
            }
        }

        return $this->render('adjustPoints', ['selectedCustomerId' => $selectedCustomerId]);
    }
    
    public function actionSettings() {
        if ($_POST && isset($_POST['settings'])) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                foreach ($_POST['settings'] as $key => $value) {
                    $result = LoyaltySettings::setValue($key, $value);
                    if (!$result) {
                        throw new \Exception("Failed to update setting: {$key}");
                    }
                }
                $transaction->commit();
                Yii::$app->user->setFlash('success', 'Settings updated successfully');
            } catch (\Exception $e) {
                $transaction->rollback();
                Yii::$app->user->setFlash('error', 'Failed to update settings: ' . $e->getMessage());
            }
        }
        
        // Initialize default settings if none exist
        $settingsCount = LoyaltySettings::find()->count();
        if ($settingsCount == 0) {
            LoyaltySettings::initializeDefaults();
        }
        
        $settings = LoyaltySettings::find()->all();
        return $this->render('settings', ['settings' => $settings]);
    }
    
    public function actionInitializeSettings() {
        $result = LoyaltySettings::initializeDefaults();
        
        if ($result) {
            Yii::$app->user->setFlash('success', 'Default loyalty settings initialized successfully');
        } else {
            Yii::$app->user->setFlash('error', 'Failed to initialize default settings');
        }
        
        return $this->redirect(['settings']);
    }
    
    public function actionExportReport() {
        $filename = 'loyalty_monthly_report_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // BOM for Excel UTF-8
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Month', 'Points Earned', 'Points Redeemed', 'Net Points', 'Redemption Rate %']);

        for ($i = 5; $i >= 0; $i--) {
            $month     = date('Y-m', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime("-{$i} months"));

            $earned = (new \yii\db\Query())->select('SUM(points)')
                ->from('tbl_loyalty_transactions')
                ->where('transaction_type = "EARN" AND DATE_FORMAT(created_at, "%Y-%m") = :month', [':month' => $month])
                ->scalar();

            $redeemed = (new \yii\db\Query())->select('SUM(ABS(points))')
                ->from('tbl_loyalty_transactions')
                ->where('transaction_type = "REDEEM" AND DATE_FORMAT(created_at, "%Y-%m") = :month', [':month' => $month])
                ->scalar();

            $earned   = $earned   ?: 0;
            $redeemed = $redeemed ?: 0;
            $net      = $earned - $redeemed;
            $rate     = $earned > 0 ? round(($redeemed / $earned) * 100, 1) : 0;

            fputcsv($out, [$monthName, $earned, $redeemed, $net, $rate . '%']);
        }

        fclose($out);
        Yii::$app->end();
    }

    public function actionExportCustomers() {
        $query = CustomerLoyalty::find()->alias('t');
        $query->with = ['customer'];
        $query->orderBy(['t.total_points' => SORT_DESC, 't.id' => SORT_DESC]);

        $records = $query->all();

        $filename = 'loyalty_customers_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // BOM for Excel UTF-8
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Customer Name', 'Phone', 'Email', 'Tier', 'Current Points', 'Lifetime Earned', 'Lifetime Redeemed', 'Status']);

        foreach ($records as $loyalty) {
            $customer = $loyalty->customer;
            $name     = $customer ? trim($customer->name)       : '';
            $phone    = $customer ? $customer->contact_no       : '';
            $email    = $customer ? $customer->email            : '';
            $pts      = (int)$loyalty->total_points;

            if ($pts >= 5000)     $tier = 'Gold';
            elseif ($pts >= 1000) $tier = 'Silver';
            elseif ($pts > 0)     $tier = 'Bronze';
            else                  $tier = 'None';

            $status = $loyalty->status == 1 ? 'Active' : 'Inactive';

            fputcsv($out, [
                $name,
                $phone,
                $email,
                $tier,
                $pts,
                (int)$loyalty->lifetime_earned,
                (int)$loyalty->lifetime_redeemed,
                $status,
            ]);
        }

        fclose($out);
        Yii::$app->end();
    }

    public function actionReports() {
        // Basic loyalty program reports
        $stats = [];
        
        // Total customers with loyalty points
        $query = CustomerLoyalty::find();
        $query->andWhere('total_points > 0');
        $stats['active_customers'] = $query->count();
        
        // Total points earned vs redeemed
        $stats['total_earned'] = (new \yii\db\Query())->select('SUM(lifetime_earned)')
            ->from('tbl_customer_loyalty')
            ->scalar();
            
        $stats['total_redeemed'] = (new \yii\db\Query())->select('SUM(lifetime_redeemed)')
            ->from('tbl_customer_loyalty')
            ->scalar();
            
        // Recent transactions
        $query_2 = LoyaltyTransaction::find();
        $query_2->with = ['customer'];
        $query_2->orderBy(['created_at' => SORT_DESC]);
        $query_2->limit(10);
        $recentTransactions = $query_2->all();
        
        return $this->render('reports', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions
        ]);
    }
}