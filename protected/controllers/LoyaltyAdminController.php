<?php
class LoyaltyAdminController extends GxController
{
    public function actionIndex() {
        $this->render('index');
    }
    
    public function actionCustomers() {
        $searchName  = isset($_GET['search_name'])  ? trim($_GET['search_name'])  : '';
        $searchPhone = isset($_GET['search_phone']) ? trim($_GET['search_phone']) : '';

        $criteria = new CDbCriteria();
        $criteria->with = array('customer' => array('together' => false));
        $criteria->order = 't.total_points DESC, t.id DESC';

        if ($searchName !== '' || $searchPhone !== '') {
            // Need a JOIN to filter on customer columns — use together:true only for this filter
            $criteria->with = array('customer' => array('together' => true));
            if ($searchName !== '') {
                $criteria->addSearchCondition('customer.name', $searchName, true, 'AND', 'LIKE');
            }
            if ($searchPhone !== '') {
                $criteria->addSearchCondition('customer.contact_no', $searchPhone, true, 'AND', 'LIKE');
            }
        }

        $dataProvider = new CActiveDataProvider('CustomerLoyalty', array(
            'criteria'   => $criteria,
            'pagination' => array('pageSize' => 20),
            'sort'       => false,
        ));

        $this->render('customers', array(
            'dataProvider' => $dataProvider,
            'searchName'   => $searchName,
            'searchPhone'  => $searchPhone,
        ));
    }
    
    public function actionTransactions() {
        $validTypes = array(
            LoyaltyTransaction::TYPE_EARN,
            LoyaltyTransaction::TYPE_REDEEM,
            LoyaltyTransaction::TYPE_BONUS,
            LoyaltyTransaction::TYPE_EXPIRE,
            LoyaltyTransaction::TYPE_ADJUST,
        );

        // Type filter — REDEEM by default, 'ALL' shows every type
        $type = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : LoyaltyTransaction::TYPE_REDEEM;
        if ($type !== 'ALL' && !in_array($type, $validTypes)) {
            $type = LoyaltyTransaction::TYPE_REDEEM;
        }

        // Date range filter (optional, Y-m-d from the date inputs)
        $fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
        $toDate   = isset($_GET['to_date'])   ? trim($_GET['to_date'])   : '';
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        if ($fromDate !== '' && !preg_match($datePattern, $fromDate)) $fromDate = '';
        if ($toDate !== ''   && !preg_match($datePattern, $toDate))   $toDate   = '';

        $criteria = new CDbCriteria();
        $criteria->with = array(
            'customer' => array('together' => false),
            'order'    => array('together' => false),
        );
        $criteria->order = 't.created_at DESC';

        if ($type !== 'ALL') {
            $criteria->addCondition('t.transaction_type = :type');
            $criteria->params[':type'] = $type;
        }
        if ($fromDate !== '') {
            $criteria->addCondition('t.created_at >= :from_date');
            $criteria->params[':from_date'] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== '') {
            $criteria->addCondition('t.created_at <= :to_date');
            $criteria->params[':to_date'] = $toDate . ' 23:59:59';
        }

        // Summary for the filtered set
        $summaryCommand = Yii::app()->db->createCommand()
            ->select('COUNT(*) AS cnt, COALESCE(SUM(points), 0) AS total_points, COALESCE(SUM(ABS(points)), 0) AS total_abs_points')
            ->from('tbl_loyalty_transactions t');
        if ($criteria->condition !== '') {
            $summaryCommand->where($criteria->condition, $criteria->params);
        }
        $summaryRow = $summaryCommand->queryRow();

        $dataProvider = new CActiveDataProvider('LoyaltyTransaction', array(
            'criteria'   => $criteria,
            'pagination' => array('pageSize' => 20),
            'sort'       => false,
        ));

        $this->render('transactions', array(
            'dataProvider' => $dataProvider,
            'type'         => $type,
            'fromDate'     => $fromDate,
            'toDate'       => $toDate,
            'summary'      => $summaryRow,
        ));
    }

    public function actionViewTransactions($id) {
        $id = intval($id);
        $loyalty = CustomerLoyalty::model()
            ->with(array('customer' => array('together' => false)))
            ->findByAttributes(array('customer_id' => $id));

        if (!$loyalty) {
            throw new CHttpException(404, 'Customer loyalty record not found.');
        }

        $criteria            = new CDbCriteria();
        $criteria->condition = 't.customer_id = :cid';
        $criteria->params    = array(':cid' => $id);
        $criteria->with      = array('order' => array('together' => false));
        $criteria->order     = 't.created_at DESC';

        $dataProvider = new CActiveDataProvider('LoyaltyTransaction', array(
            'criteria'   => $criteria,
            'pagination' => array('pageSize' => 20),
            'sort'       => false,
        ));

        $this->render('viewTransactions', array(
            'loyalty'      => $loyalty,
            'dataProvider' => $dataProvider,
        ));
    }

    public function actionGetCustomerLoyaltyInfo() {
        $customerId = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        if ($customerId) {
            $loyalty = CustomerLoyalty::model()->findByAttributes(array('customer_id' => $customerId));
            if ($loyalty) {
                echo CJSON::encode(array(
                    'status' => 'success',
                    'data'   => array(
                        'total_points'      => (int)$loyalty->total_points,
                        'lifetime_earned'   => (int)$loyalty->lifetime_earned,
                        'lifetime_redeemed' => (int)$loyalty->lifetime_redeemed,
                    ),
                ));
                Yii::app()->end();
            }
        }
        echo CJSON::encode(array('status' => 'error'));
        Yii::app()->end();
    }

    public function actionAdjustPoints() {
        $selectedCustomerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;

        if (Yii::app()->request->isPostRequest) {
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

                    Yii::app()->user->setFlash('success', 'Points adjusted successfully.');
                    $this->redirect(array('viewTransactions', 'id' => $customerId));
                    return;
                } catch (Exception $e) {
                    Yii::app()->user->setFlash('error', 'Failed to adjust points.');
                }
            } else {
                Yii::app()->user->setFlash('error', 'Please select a customer and enter a non-zero point value.');
            }
        }

        $this->render('adjustPoints', array('selectedCustomerId' => $selectedCustomerId));
    }
    
    public function actionSettings() {
        if ($_POST && isset($_POST['settings'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                foreach ($_POST['settings'] as $key => $value) {
                    $result = LoyaltySettings::setValue($key, $value);
                    if (!$result) {
                        throw new Exception("Failed to update setting: {$key}");
                    }
                }
                $transaction->commit();
                Yii::app()->user->setFlash('success', 'Settings updated successfully');
            } catch (Exception $e) {
                $transaction->rollback();
                Yii::app()->user->setFlash('error', 'Failed to update settings: ' . $e->getMessage());
            }
        }
        
        // Initialize default settings if none exist
        $settingsCount = LoyaltySettings::model()->count();
        if ($settingsCount == 0) {
            LoyaltySettings::initializeDefaults();
        }
        
        $settings = LoyaltySettings::model()->findAll();
        $this->render('settings', ['settings' => $settings]);
    }
    
    public function actionInitializeSettings() {
        $result = LoyaltySettings::initializeDefaults();
        
        if ($result) {
            Yii::app()->user->setFlash('success', 'Default loyalty settings initialized successfully');
        } else {
            Yii::app()->user->setFlash('error', 'Failed to initialize default settings');
        }
        
        $this->redirect(array('settings'));
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

        fputcsv($out, array('Month', 'Points Earned', 'Points Redeemed', 'Net Points', 'Redemption Rate %'));

        for ($i = 5; $i >= 0; $i--) {
            $month     = date('Y-m', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime("-{$i} months"));

            $earned = Yii::app()->db->createCommand()
                ->select('SUM(points)')
                ->from('tbl_loyalty_transactions')
                ->where('transaction_type = "EARN" AND DATE_FORMAT(created_at, "%Y-%m") = :month', array(':month' => $month))
                ->queryScalar();

            $redeemed = Yii::app()->db->createCommand()
                ->select('SUM(ABS(points))')
                ->from('tbl_loyalty_transactions')
                ->where('transaction_type = "REDEEM" AND DATE_FORMAT(created_at, "%Y-%m") = :month', array(':month' => $month))
                ->queryScalar();

            $earned   = $earned   ?: 0;
            $redeemed = $redeemed ?: 0;
            $net      = $earned - $redeemed;
            $rate     = $earned > 0 ? round(($redeemed / $earned) * 100, 1) : 0;

            fputcsv($out, array($monthName, $earned, $redeemed, $net, $rate . '%'));
        }

        fclose($out);
        Yii::app()->end();
    }

    public function actionExportCustomers() {
        $criteria = new CDbCriteria();
        $criteria->with = array('customer' => array('together' => false));
        $criteria->order = 't.total_points DESC, t.id DESC';

        $records = CustomerLoyalty::model()->findAll($criteria);

        $filename = 'loyalty_customers_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // BOM for Excel UTF-8
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, array('Customer Name', 'Phone', 'Email', 'Tier', 'Current Points', 'Lifetime Earned', 'Lifetime Redeemed', 'Status'));

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

            fputcsv($out, array(
                $name,
                $phone,
                $email,
                $tier,
                $pts,
                (int)$loyalty->lifetime_earned,
                (int)$loyalty->lifetime_redeemed,
                $status,
            ));
        }

        fclose($out);
        Yii::app()->end();
    }

    public function actionReports() {
        // Basic loyalty program reports
        $stats = array();
        
        // Total customers with loyalty points
        $criteria = new CDbCriteria();
        $criteria->condition = 'total_points > 0';
        $stats['active_customers'] = CustomerLoyalty::model()->count($criteria);
        
        // Total points earned vs redeemed
        $stats['total_earned'] = Yii::app()->db->createCommand()
            ->select('SUM(lifetime_earned)')
            ->from('tbl_customer_loyalty')
            ->queryScalar();
            
        $stats['total_redeemed'] = Yii::app()->db->createCommand()
            ->select('SUM(lifetime_redeemed)')
            ->from('tbl_customer_loyalty')
            ->queryScalar();
            
        // Recent transactions
        $criteria = new CDbCriteria();
        $criteria->with = array('customer' => array('together' => false));
        $criteria->order = 'created_at DESC';
        $criteria->limit = 10;
        $recentTransactions = LoyaltyTransaction::model()->findAll($criteria);
        
        $this->render('reports', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions
        ]);
    }
}