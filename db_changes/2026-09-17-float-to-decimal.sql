-- FLOAT/DOUBLE -> DECIMAL for all money and quantity columns.
-- FLOAT is binary and cannot represent most decimal fractions exactly,
-- so sums and comparisons on money drift. Scale is preserved from the
-- original column; precision is uniform and generous by design.

ALTER TABLE `tbl_advance_logs`
  MODIFY `amount` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_advance_payment`
  MODIFY `payment` decimal(15,2) NOT NULL,
  MODIFY `balance_amt` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_b2bpurchase_bill`
  MODIFY `gross_amt` decimal(15,2) NOT NULL,
  MODIFY `total_discount` decimal(15,2) NOT NULL,
  MODIFY `tax_amount` decimal(15,2) NOT NULL,
  MODIFY `bill_amount` decimal(15,2) NOT NULL,
  MODIFY `purchase_order_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `charges_total_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `discount_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `frieght_charges` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `extra_charges` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `total_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `net_bill_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `bill_other_discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `credit_note_disc` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_b2bpurchase_bill_detail`
  MODIFY `req_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `bal_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `approved_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `mrp` decimal(15,2) NOT NULL,
  MODIFY `price` decimal(15,3) NOT NULL,
  MODIFY `discount` decimal(15,2) NOT NULL,
  MODIFY `discount_amt` decimal(15,2) NOT NULL,
  MODIFY `discount1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `discount_amt1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_per` decimal(15,2) NOT NULL,
  MODIFY `sgst_per` decimal(15,2) NOT NULL,
  MODIFY `cess_per` decimal(15,2) NOT NULL,
  MODIFY `cgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sgst_amt` decimal(15,2) NOT NULL,
  MODIFY `cess_amt` decimal(15,2) NOT NULL,
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `other_charge` decimal(15,2) NOT NULL,
  MODIFY `amount` decimal(15,2) NOT NULL,
  MODIFY `sale_rate` decimal(15,2) NOT NULL,
  MODIFY `charge_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `extra_charges` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_credit_note`
  MODIFY `amt` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `amt_used` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_customer`
  MODIFY `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00';

ALTER TABLE `tbl_discount`
  MODIFY `amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `applicable_amt` decimal(19,4) NOT NULL;

ALTER TABLE `tbl_item`
  MODIFY `mrp` decimal(15,2) NOT NULL,
  MODIFY `sale_price` decimal(15,2) NOT NULL,
  MODIFY `weight` decimal(15,2) NOT NULL,
  MODIFY `purchase_price` decimal(15,2) NOT NULL,
  MODIFY `whole_sale` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_item_detail`
  MODIFY `mrp` decimal(15,2) NULL DEFAULT NULL;

ALTER TABLE `tbl_item_return`
  MODIFY `discount_amt` decimal(15,2) NOT NULL,
  MODIFY `other_charge` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_item_return_item`
  MODIFY `price` decimal(15,3) NOT NULL,
  MODIFY `discount` decimal(15,2) NOT NULL,
  MODIFY `discount_amt` decimal(15,2) NOT NULL,
  MODIFY `discount1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `discount_amt1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_per` decimal(15,2) NOT NULL,
  MODIFY `sgst_per` decimal(15,2) NOT NULL,
  MODIFY `cess_per` decimal(15,2) NOT NULL,
  MODIFY `cgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sgst_amt` decimal(15,2) NOT NULL,
  MODIFY `cess_amt` decimal(15,2) NOT NULL,
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `other_charge` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_item_stock`
  MODIFY `balance_qty` decimal(15,3) NOT NULL,
  MODIFY `base_price` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `mrp` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_item_stock_backup_20260126`
  MODIFY `balance_qty` decimal(15,3) NOT NULL,
  MODIFY `base_price` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `mrp` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_item_velocity`
  MODIFY `velocity_change_percent` decimal(15,2) NULL DEFAULT NULL;

ALTER TABLE `tbl_item_vendor`
  MODIFY `vendor_price` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_mrn`
  MODIFY `gross_amt` decimal(15,2) NOT NULL,
  MODIFY `total_discount` decimal(15,2) NOT NULL,
  MODIFY `tax_amount` decimal(15,2) NOT NULL,
  MODIFY `bill_amount` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_mrn_detail`
  MODIFY `req_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `min_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `approved_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `bal_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `mrp` decimal(15,2) NOT NULL,
  MODIFY `price` decimal(15,3) NOT NULL,
  MODIFY `sale_rate` decimal(15,2) NOT NULL,
  MODIFY `discount` decimal(15,2) NOT NULL,
  MODIFY `discount_amt` decimal(15,2) NOT NULL,
  MODIFY `discount1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `discount_amt1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_per` decimal(15,2) NOT NULL,
  MODIFY `sgst_per` decimal(15,2) NOT NULL,
  MODIFY `cess_per` decimal(15,2) NOT NULL,
  MODIFY `cgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sgst_amt` decimal(15,2) NOT NULL,
  MODIFY `cess_amt` decimal(15,2) NOT NULL,
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `other_charge` decimal(15,2) NOT NULL,
  MODIFY `amount` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_mrs`
  MODIFY `gross_amt` decimal(15,2) NOT NULL,
  MODIFY `total_discount` decimal(15,2) NOT NULL,
  MODIFY `tax_amount` decimal(15,2) NOT NULL,
  MODIFY `bill_amount` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_mrs_detail`
  MODIFY `req_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `min_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `approved_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `ai_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `bal_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `mrp` decimal(15,2) NOT NULL,
  MODIFY `price` decimal(15,3) NOT NULL,
  MODIFY `sale_rate` decimal(15,2) NOT NULL,
  MODIFY `discount` decimal(15,2) NOT NULL,
  MODIFY `discount_amt` decimal(15,2) NOT NULL,
  MODIFY `discount1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `discount_amt1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_per` decimal(15,2) NOT NULL,
  MODIFY `sgst_per` decimal(15,2) NOT NULL,
  MODIFY `cess_per` decimal(15,2) NOT NULL,
  MODIFY `cgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sgst_amt` decimal(15,2) NOT NULL,
  MODIFY `cess_amt` decimal(15,2) NOT NULL,
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `other_charge` decimal(15,2) NOT NULL,
  MODIFY `amount` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_mrs_detail_backup`
  MODIFY `req_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `min_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `approved_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `bal_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `mrp` decimal(15,2) NOT NULL,
  MODIFY `price` decimal(15,3) NOT NULL,
  MODIFY `sale_rate` decimal(15,2) NOT NULL,
  MODIFY `discount` decimal(15,2) NOT NULL,
  MODIFY `discount_amt` decimal(15,2) NOT NULL,
  MODIFY `discount1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `discount_amt1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_per` decimal(15,2) NOT NULL,
  MODIFY `sgst_per` decimal(15,2) NOT NULL,
  MODIFY `cess_per` decimal(15,2) NOT NULL,
  MODIFY `cgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sgst_amt` decimal(15,2) NOT NULL,
  MODIFY `cess_amt` decimal(15,2) NOT NULL,
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `other_charge` decimal(15,2) NOT NULL,
  MODIFY `amount` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_online_order`
  MODIFY `grand_total` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_online_order_item`
  MODIFY `price` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `total` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_order`
  MODIFY `discount_amt` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `total_amt` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `gross_total_amt` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `paid_amt` decimal(15,3) NULL DEFAULT '0.000';

ALTER TABLE `tbl_order_hold`
  MODIFY `discount_amt` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `total_amt` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `gross_total_amt` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `paid_amt` decimal(15,3) NULL DEFAULT '0.000';

ALTER TABLE `tbl_order_hold_item`
  MODIFY `mrp` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `sale_rate` decimal(15,3) NOT NULL,
  MODIFY `total_amt` decimal(15,3) NOT NULL,
  MODIFY `price` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `discount_amt` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `tax_amount` decimal(15,2) NOT NULL,
  MODIFY `cgst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `sgst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cess_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `sgst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cess_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00';

ALTER TABLE `tbl_order_item`
  MODIFY `sale_rate` decimal(15,3) NOT NULL,
  MODIFY `mrp` decimal(15,3) NOT NULL,
  MODIFY `total_amt` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `price` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `discount_amt` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `tax_amount` decimal(19,4) NOT NULL,
  MODIFY `cgst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `sgst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cess_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_amt` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `sgst_amt` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `cess_amt` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `igst_amt` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `order_discount` decimal(15,3) NOT NULL;

ALTER TABLE `tbl_order_refund`
  MODIFY `discount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `discount_amt` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `total_amt` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `paid_amt` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_order_refund_item`
  MODIFY `price` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `discount_amt` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `total_amt` decimal(15,3) NOT NULL,
  MODIFY `tax_amt` decimal(15,2) NOT NULL,
  MODIFY `order_discount` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_payment_report`
  MODIFY `amount` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_purchase_bill`
  MODIFY `gross_amt` decimal(15,2) NOT NULL,
  MODIFY `total_discount` decimal(15,2) NOT NULL,
  MODIFY `tax_amount` decimal(15,2) NOT NULL,
  MODIFY `bill_amount` decimal(15,2) NOT NULL,
  MODIFY `purchase_order_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `charges_total_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `discount_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `frieght_charges` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `extra_charges` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `total_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `net_bill_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `bill_other_discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `credit_note_disc` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_purchase_bill_detail`
  MODIFY `req_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `bal_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `approved_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `mrp` decimal(15,2) NOT NULL,
  MODIFY `price` decimal(15,3) NOT NULL,
  MODIFY `discount` decimal(15,2) NOT NULL,
  MODIFY `discount_amt` decimal(15,2) NOT NULL,
  MODIFY `discount1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `discount_amt1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_per` decimal(15,2) NOT NULL,
  MODIFY `sgst_per` decimal(15,2) NOT NULL,
  MODIFY `cess_per` decimal(15,2) NOT NULL,
  MODIFY `cgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sgst_amt` decimal(15,2) NOT NULL,
  MODIFY `cess_amt` decimal(15,2) NOT NULL,
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `sale_cgst_per` decimal(15,2) NOT NULL,
  MODIFY `sale_sgst_per` decimal(15,2) NOT NULL,
  MODIFY `sale_cess_per` decimal(15,2) NOT NULL,
  MODIFY `sale_cgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sale_sgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sale_cess_amt` decimal(15,2) NOT NULL,
  MODIFY `sale_igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `sale_igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `other_charge` decimal(15,2) NOT NULL,
  MODIFY `amount` decimal(15,2) NOT NULL,
  MODIFY `sale_rate` decimal(15,2) NOT NULL,
  MODIFY `charge_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `extra_charges` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_purchase_order`
  MODIFY `gross_amt` decimal(15,2) NOT NULL,
  MODIFY `total_discount` decimal(15,2) NOT NULL,
  MODIFY `tax_amount` decimal(15,2) NOT NULL,
  MODIFY `bill_amount` decimal(15,2) NOT NULL,
  MODIFY `purchase_order_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `charges_total_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `discount_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `frieght_charges` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `extra_charges` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `total_amount` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_purchase_order_detail`
  MODIFY `req_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  MODIFY `approved_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `bal_qty` decimal(15,3) NULL DEFAULT '0.000',
  MODIFY `mrp` decimal(15,2) NOT NULL,
  MODIFY `price` decimal(15,3) NOT NULL,
  MODIFY `sale_rate` decimal(15,2) NOT NULL,
  MODIFY `discount` decimal(15,2) NOT NULL,
  MODIFY `discount_amt` decimal(15,2) NOT NULL,
  MODIFY `discount1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `discount_amt1` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `cgst_per` decimal(15,2) NOT NULL,
  MODIFY `sgst_per` decimal(15,2) NOT NULL,
  MODIFY `cess_per` decimal(15,2) NOT NULL,
  MODIFY `cgst_amt` decimal(15,2) NOT NULL,
  MODIFY `sgst_amt` decimal(15,2) NOT NULL,
  MODIFY `cess_amt` decimal(15,2) NOT NULL,
  MODIFY `igst_per` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `igst_amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  MODIFY `other_charge` decimal(15,2) NOT NULL,
  MODIFY `margin` decimal(15,2) NOT NULL,
  MODIFY `amount` decimal(15,2) NOT NULL,
  MODIFY `charge_amount` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `extra_charges` decimal(15,2) NULL DEFAULT '0.00';

ALTER TABLE `tbl_scanned_items`
  MODIFY `sale_rate` decimal(15,2) NOT NULL,
  MODIFY `base_price` decimal(15,2) NOT NULL,
  MODIFY `mrp` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_stock_adjust_log`
  MODIFY `mrp` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_stock_adjust_log_backup_20260126`
  MODIFY `mrp` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_tax`
  MODIFY `tax_val1` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `tax_val2` decimal(15,2) NULL DEFAULT '0.00',
  MODIFY `tax_val3` decimal(15,2) NOT NULL,
  MODIFY `tax_val4` decimal(15,2) NOT NULL;

ALTER TABLE `tbl_vendor`
  MODIFY `opening_balance` decimal(15,2) NULL DEFAULT NULL;

ALTER TABLE `tbl_vendor_schemes`
  MODIFY `discount` decimal(15,2) NULL DEFAULT '0.00';

-- tbl_discount.applicable_amt was a bare FLOAT with no declared scale; it is
-- sized to match the other money columns rather than the 19,4 its lack of
-- scale would otherwise imply.
ALTER TABLE `tbl_discount` MODIFY `applicable_amt` decimal(15,2) NOT NULL;
