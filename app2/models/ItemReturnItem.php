<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/ItemReturnItem.php (Yii 1).
 *
 * Only the Tally export payload and the helpers it needs are ported.
 */
class ItemReturnItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%item_return_item}}';
    }

    public function getItemReturn()
    {
        return $this->hasOne(ItemReturn::class, ['id' => 'return_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    /**
     * Whether this return attracts CGST/SGST rather than IGST.
     *
     * True when the vendor and the outlet are in the same state. The Yii 1
     * version dereferences $vendor without checking it was found, so a return
     * whose vendor row is missing fails there; that is left as-is rather than
     * papered over, since silently returning a different tax treatment would be
     * worse than the error.
     */
    public function getReturnGstTrue($returnId)
    {
        if (!$returnId) {
            return true;
        }
        $return = ItemReturn::findOne(['id' => $returnId]);
        if (!$return) {
            return true;
        }
        $outlet = Outlet::findOne($return->outlet_id);
        if (!$outlet) {
            return true;
        }
        $vendor = Vendor::findOne($return->vendor_id);
        return $vendor->state_id != $outlet->state_id ? false : true;
    }

    /**
     * A tax percentage column, zeroed when it does not apply: the CGST/SGST
     * columns read 0.00 on an inter-state return and IGST reads 0.00 on an
     * intra-state one.
     */
    public function getTaxPercentage($col)
    {
        $checkGst = !($col === 'igst_per' || $col === 'igst_amt');
        return $this->getReturnGstTrue($this->return_id) == $checkGst
            ? $this->$col
            : '0.00';
    }

    public function getTotalGstPer()
    {
        return $this->getTaxPercentage('cgst_per')
             + $this->getTaxPercentage('sgst_per')
             + $this->getTaxPercentage('cess_per')
             + $this->getTaxPercentage('igst_per');
    }

    public function getVendorTAXNO()
    {
        if ($this->itemReturn && $this->itemReturn->vendor) {
            return $this->itemReturn->vendor->tax_no;
        }
        return '';
    }

    public function getCustomGRNNumber()
    {
        $grnNo = isset($this->itemReturn) ? $this->itemReturn->grn_no : '';
        return $grnNo != '' ? 'GR-' . $grnNo : '';
    }

    /**
     * Payload from ItemReturnItem::toTallyArray(), key for key.
     *
     * Keys carry spaces ('Bill Date', 'GST NO') because they become column
     * headings in the Tally import.
     */
    public function toTallyApiArray()
    {
        $vendorName = '';
        $billNo = '';
        $billDate = '';
        $grnSaveDate = '';

        if (isset($this->itemReturn)) {
            if (isset($this->itemReturn->vendor)) {
                $vendor = $this->itemReturn->vendor;
                $vendorName = $vendor->name;
                if ($vendor->parent_id !== null) {
                    $vendorName = $vendor->parentvendor->name;
                }
            }
            $billNo = $this->itemReturn->bill_no;
            $grnSaveDate = date('Y-m-d', strtotime((string)$this->itemReturn->grn_save_date));
        }

        $grnDate = '';
        if ($billNo != '') {
            $purchaseBill = PurchaseBill::findOne(['bill_no' => $billNo]);
            if ($purchaseBill) {
                $billDate = $purchaseBill->end_date;
                $grnDate = date('Y-m-d', strtotime((string)$purchaseBill->start_date));
            }
        }

        $json = [];
        $json['id'] = (string)$this->id;
        $json['grn_date'] = $grnDate;
        $json['Bill Date'] = $billDate;
        $json['Date'] = $grnSaveDate;
        $json['Vendor'] = $vendorName;
        $json['HSN Code'] = isset($this->tax) ? $this->tax->hrn_code : '';
        $json['GST NO'] = $this->getVendorTAXNO();
        $json['Credit Note No'] = isset($this->itemReturn) ? $this->itemReturn->credit_note_no : '';
        $json['Credit Note Date'] = isset($this->itemReturn) ? $this->itemReturn->credit_note_date : '';
        $json['Bill No'] = isset($this->itemReturn) ? $this->itemReturn->bill_no : '';
        $json['Supplier Invoice No'] = isset($this->itemReturn) ? $this->itemReturn->invoice_no : '';
        $json['GST Rate'] = $this->getTotalGstPer();
        $json['CGST Rate'] = $this->cgst_per;
        $json['SGST Rate'] = $this->sgst_per;
        $json['CESS Rate'] = $this->cess_per;
        $json['IGST Rate'] = $this->igst_per;
        $json['Net Amount'] = isset($this->itemReturn) ? $this->itemReturn->total_amt : '';
        $json['Basic Value'] = ($this->price * $this->qty) - $this->discount_amt;
        $json['Discount'] = 0;
        $json['GST'] = $this->cgst_amt + $this->sgst_amt + $this->cess_amt + $this->igst_amt;
        $json['CGST'] = $this->cgst_amt;
        $json['SGST'] = $this->sgst_amt;
        $json['IGST'] = $this->igst_amt;
        $json['CESS'] = $this->cess_amt;
        $json['GRN NUMBER'] = $this->getCustomGRNNumber();

        return $json;
    }
}
