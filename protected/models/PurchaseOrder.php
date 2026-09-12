<?php


/**
 * @property integer $id
 * @property string $code
 * @property string $start_date
 * @property string $end_date
 * @property string $receiving_date
 * @property integer $status
 * @property integer $type_id
 * @property integer $is_open_po
 * @property integer $is_po_received
 * @property string $remarks
 * @property string $payment_terms
 * @property string $transport_mode
 * @property double $purchase_order_amount
 * @property double $charges_total_amount
 * @property double $discount_amount
 * @property double $frieght_charges
 * @property double $extra_charges
 * @property double $total_amount
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 * @property integer $outlet_id
 * @property integer $vendor_id
 * @property integer $mrn_id
 * @property integer $organization_id
 */
Yii::import('application.models._base.BasePurchaseOrder');
class PurchaseOrder extends BasePurchaseOrder
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}