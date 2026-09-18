<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/B2bPurchaseBillDetail.php (Yii 1), which is
 * 1,625 lines. Only what tally/b2bsales reads is here; the rest belongs with
 * whatever ports the B2B screens.
 */
class B2bPurchaseBillDetail extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%b2bpurchase_bill_detail}}';
    }

    public function getPurchaseBill()
    {
        return $this->hasOne(B2bPurchaseBill::class, ['id' => 'purchase_bill_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    /**
     * False when the vendor and the outlet are in different states, which is
     * what decides IGST against CGST+SGST.
     *
     * $vendor->state_id is read without checking $vendor, so a bill pointing at
     * a missing vendor is a fatal - as in Yii 1.
     */
    public function getGstTrue($billId)
    {
        $gst = true;
        if ($billId) {
            $bill = B2bPurchaseBill::find()->where(['id' => $billId])->orderBy(['id' => SORT_ASC])->one();
            if ($bill) {
                $outlet = Outlet::findOne($bill->outlet_id);
                if ($outlet) {
                    $vendor = Vendor::findOne($bill->vendor_id);
                    if ($vendor->state_id != $outlet->state_id) {
                        $gst = false;
                    }
                }
            }
        }
        return $gst;
    }

    /**
     * A tax column, zeroed when it does not apply to this bill.
     *
     * CESS is the exception: it comes back whichever way the bill falls. Note
     * the zero is the string '0.00', not a number - the caller puts it straight
     * into the response.
     */
    public function getTaxPercentage($col)
    {
        $checkGst = !($col == 'igst_per' || $col == 'igst_amt');

        if ($this->getGstTrue($this->purchase_bill_id) == $checkGst) {
            return $this->$col;
        }
        if ($col == 'cess_per' || $col == 'cess_amt') {
            return $this->$col;
        }
        return '0.00';
    }

    /**
     * CGST plus SGST. The Yii 1 version also computes cess and igst and then
     * does not use them; left out rather than reproduced, since they cost two
     * queries each and cannot affect the answer.
     */
    public function getTotalGstPer()
    {
        return $this->getTaxPercentage('cgst_per') + $this->getTaxPercentage('sgst_per');
    }

    /**
     * price * approved_qty for this row.
     *
     * Yii 1 gets there by re-selecting the row it already has with a SUM over
     * a single id, which is why the value comes back as a string under the
     * alias `price`. Reproduced through the same query so the formatting
     * matches; a plain multiplication here would return a float.
     */
    public function getsaleTaxableAmount()
    {
        $row = B2bPurchaseBillDetail::find()
            ->select('sum(price*approved_qty) as price')
            ->where('id = :id', [':id' => $this->id])
            ->asArray()
            ->one();

        return $row === null ? null : $row['price'];
    }

    /** The bill's start date. No null check on the bill, as in Yii 1. */
    public function getOrderBillDate()
    {
        $bill = B2bPurchaseBill::findOne($this->purchase_bill_id);
        return $bill->start_date;
    }

    /**
     * The printed bill number: B2B<yy>-<yy>/<prefix>-<grn reference>.
     *
     * The prefix logic is inverted - it uses the outlet's prefix only when that
     * prefix is empty, and the literal 'B' otherwise - so every bill reads
     * B2B.../B-... whatever the outlet is called. Same inversion as
     * Order::getOrderBillNo(), and reproduced for the same reason.
     */
    public function getOrderBillNo()
    {
        $billPrefix = 'B';
        $bill = B2bPurchaseBill::findOne($this->purchase_bill_id);

        $month = date('m', strtotime($bill->start_date));
        if ($month > 3) {
            $year = substr(date('Y', strtotime($bill->start_date)), -2);
            $yearLast = substr((string)($year + 1), -2);
        } else {
            $year = date('Y', strtotime($bill->start_date)) - 1;
            $yearLast = date('Y', strtotime($bill->start_date));
        }

        $outlet = Outlet::findOne($this->outlet_id);
        if ($outlet) {
            $billPrefix = $outlet->bill_prefix == '' ? $outlet->bill_prefix : 'B';
        }

        return 'B2B' . $year . '-' . $yearLast . '/' . $billPrefix . '-' . $bill->grn_refrence_no;
    }

    /** The vendor's name. Neither lookup is checked, as in Yii 1. */
    public function getVendorName()
    {
        $bill = B2bPurchaseBill::findOne($this->purchase_bill_id);
        $vendor = Vendor::findOne($bill->vendor_id);
        return $vendor->name;
    }

    /** The name of the user who created the row. Not checked, as in Yii 1. */
    public function createduser()
    {
        $user = User::findOne($this->create_user_id);
        return $user->full_name;
    }

    /** The vendor's state, by name. */
    public function getStateName($id = null)
    {
        if (empty($id)) {
            $id = $this->purchaseBill->vendor->state_id;
        }
        $state = State::findOne($id);
        return $state ? $state->title : '';
    }

    /** The item's title, reached through the item detail. */
    public function getItemName()
    {
        $title = '';
        $itemDetail = ItemDetail::findOne($this->item_detail_id);
        if ($itemDetail) {
            $item = Item::findOne($itemDetail->item_id);
            if ($item) {
                $title = $item->title;
            }
        }
        return $title;
    }
}
