<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Minimal Yii 2 PurchaseBillDetail.
 *
 * Only the sale-tax lookup the item payload needs is ported. The Yii 1 model is
 * 670 lines and its toArray1() drives the Tally purchase reports through eleven
 * tax arithmetic helpers; those move with the purchase and billing module.
 */
class PurchaseBillDetail extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%purchase_bill_detail}}';
    }

    /** Latest sale_tax_id recorded against an item detail, or 0. */
    public static function getLatestSaleTaxIdByItemDetailId($itemDetailId)
    {
        $detail = static::find()
            ->where(['item_detail_id' => $itemDetailId])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();
        return $detail ? $detail->sale_tax_id : 0;
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getPurchaseBill()
    {
        return $this->hasOne(PurchaseBill::class, ['id' => 'purchase_bill_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    /** All lines of this bill sharing this line's tax id. */
    private function siblings()
    {
        return static::findAll([
            'purchase_bill_id' => $this->purchase_bill_id,
            'tax_id' => $this->tax_id,
        ]);
    }

    /**
     * Whether the bill is intra-state, i.e. CGST/SGST rather than IGST.
     *
     * As in ItemReturnItem, the vendor is dereferenced without a null check;
     * left as-is rather than defaulting silently to a different tax treatment.
     */
    public function getGstTrue($billId)
    {
        if (!$billId) {
            return true;
        }
        $bill = PurchaseBill::findOne(['id' => $billId]);
        if (!$bill) {
            return true;
        }
        $outlet = Outlet::findOne($bill->outlet_id);
        if (!$outlet) {
            return true;
        }
        $vendor = Vendor::findOne($bill->vendor_id);
        return $vendor->state_id != $outlet->state_id ? false : true;
    }

    /**
     * A tax column, zeroed when it does not apply.
     *
     * CESS is the exception: it is returned whichever way the bill falls.
     */
    public function getTaxPercentage($col)
    {
        $checkGst = !($col === 'igst_per' || $col === 'igst_amt');
        if ($this->getGstTrue($this->purchase_bill_id) == $checkGst) {
            return $this->$col;
        }
        if ($col === 'cess_per' || $col === 'cess_amt') {
            return $this->$col;
        }
        return '0.00';
    }

    /**
     * Combined GST percentage.
     *
     * The Yii 1 version reads all four components and then returns only
     * cgst + sgst - cess and igst are computed and discarded. Reproduced,
     * because the returned figure is what the Tally reports carry.
     */
    public function getTotalGstPer()
    {
        return $this->getTaxPercentage('cgst_per') + $this->getTaxPercentage('sgst_per');
    }

    /** Same shape as getTotalGstPer: four read, two summed. */
    public function getTotalGstAmt()
    {
        $total = 0;
        foreach ($this->siblings() as $detail) {
            $total += $detail->getTaxPercentage('cgst_amt') + $detail->getTaxPercentage('sgst_amt');
        }
        return $total;
    }

    /** Line amounts for this tax group, net of the tax components. */
    public function getBasicAmount()
    {
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->amount
                - ($detail->getTaxPercentage('cgst_amt')
                 + $detail->getTaxPercentage('sgst_amt')
                 + $detail->getTaxPercentage('cess_amt')
                 + $detail->getTaxPercentage('igst_amt'));
        }
        return $amount;
    }

    /**
     * Always '0'.
     *
     * The Yii 1 version sums discount_amt across the tax group and then returns
     * the literal string '0', discarding it. The loop has no other effect, so
     * only the return value is reproduced - but the behaviour is the constant,
     * not the sum, and anything relying on this column is reading a zero.
     */
    public function getMainDiscount()
    {
        return '0';
    }

    public function getCgstAmount()
    {
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->getTaxPercentage('cgst_amt');
        }
        return $amount;
    }

    public function getSgstAmount()
    {
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->getTaxPercentage('sgst_amt');
        }
        return $amount;
    }

    public function getCessAmount()
    {
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->getTaxPercentage('cess_amt');
        }
        return $amount;
    }

    /**
     * IGST for the tax group.
     *
     * Tax ids 2, 3, 16, 17 and 18 are special-cased to the bill's own
     * tax_amount - a hardcoded list in the Yii 1 source, reproduced here.
     */
    public function getIgstAmount()
    {
        if (in_array($this->tax_id, [2, 3, 16, 17, 18])) {
            $bill = PurchaseBill::findOne($this->purchase_bill_id);
            if ($bill) {
                return $bill->tax_amount;
            }
        }
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->igst_amt;
        }
        return $amount;
    }

    /**
     * The bill-level "other discount", attributed to a single line.
     *
     * The Yii 1 version picks one line per bill - grouped by tax id, ordered by
     * id descending, limited to one - and reports the discount only if that is
     * this line, so the figure appears once per bill rather than on every line.
     */
    public function getSchemeDiscount()
    {
        $bill = PurchaseBill::findOne($this->purchase_bill_id);
        $detail = static::find()
            ->where(['purchase_bill_id' => $this->purchase_bill_id])
            ->groupBy(['purchase_bill_id', 'tax_id'])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();

        return ($detail && $detail->id == $this->id && $bill)
            ? $bill->bill_other_discount
            : 0;
    }

    /**
     * Payload from PurchaseBillDetail::toArray1(), key for key.
     *
     * The $saleTax argument exists on the Yii 1 signature but its body is
     * commented out there ("not needed for tally reports"), so it has no effect
     * and is accepted here only to keep the signature.
     *
     * Several figures are per-tax-group rather than per-line, so every line of
     * a group repeats the same totals. That is how the Tally import expects it.
     */
    /**
     * PurchaseBillDetail::toArray() in Yii 1. Renamed because yii\base\Model
     * already carries a toArray() through Arrayable.
     *
     * rec_qty is hard-coded to 0 by the Yii 1 version - the received quantity
     * is filled in by the client, not read from the bill.
     */
    public function toApiArray()
    {
        return [
            'id' => (string)$this->id,
            'item' => isset($this->item) ? $this->item->title : '',
            'bar_code' => isset($this->itemDetail) ? $this->itemDetail->bar_code : '',
            'req_qty' => isset($this->req_qty) ? (string)$this->req_qty : '',
            'mrp' => isset($this->itemDetail) ? $this->itemDetail->getItemDetailMrp() : '',
            'approved_qty' => isset($this->approved_qty) ? (string)$this->approved_qty : '',
            'rec_qty' => 0,   // a literal in Yii 1 too, so it stays an int
        ];
    }

    public function toApiArray1($saleTax = false)
    {
        $bill = $this->purchaseBill;

        return [
            'id' => (string)$this->id,
            'Date' => isset($bill) ? $bill->end_date : '',
            'Vendor' => isset($bill) ? $bill->vendor->name : '',
            'HSN Code' => isset($this->tax) ? $this->tax->hrn_code : '',
            'Bill No' => isset($bill) ? $bill->bill_no : '',
            'GST NO' => $this->getVendorTAXNO(),
            'GST Rate' => $this->getTotalGstPer(),
            'CGST Rate' => $this->getTaxPercentage('cgst_per'),
            'SGST Rate' => $this->getTaxPercentage('sgst_per'),
            'CESS Rate' => $this->getTaxPercentage('cess_per'),
            'IGST Rate' => $this->getTaxPercentage('igst_per'),
            'Net Amount' => isset($bill) ? $bill->net_bill_amount : '',
            'Basic Value' => $this->getBasicAmount(),
            'Discount' => $this->getMainDiscount(),
            'GST' => $this->getTotalGstAmt(),
            'CGST' => $this->getCgstAmount(),
            'SGST' => $this->getSgstAmount(),
            'IGST' => $this->getIgstAmount(),
            'CESS' => $this->getCessAmount(),
            'GRN NUMBER' => isset($bill) ? 'Gr-' . $bill->grn_refrence_no : '',
            'SCHEME AND DISCOUNT' => isset($bill) ? $this->getSchemeDiscount() : '',
        ];
    }

    public function getVendorTAXNO()
    {
        if ($this->purchaseBill && $this->purchaseBill->vendor) {
            return $this->purchaseBill->vendor->tax_no;
        }
        return '';
    }
}
