ALTER TABLE `tbl_purchase_bill_detail`
ADD `sale_tax_id` int(11) NOT NULL AFTER `tax_id`,
ADD `sale_cgst_per` float(10,2) NOT NULL AFTER `igst_amt`,
ADD `sale_sgst_per` float(10,2) NOT NULL AFTER `sale_cgst_per`,
ADD `sale_cess_per` float(10,2) NOT NULL AFTER `sale_sgst_per`,
ADD `sale_cgst_amt` float(10,2) NOT NULL AFTER `sale_cess_per`,
ADD `sale_sgst_amt` float(10,2) NOT NULL AFTER `sale_cgst_amt`,
ADD `sale_cess_amt` float(10,2) NOT NULL AFTER `sale_sgst_amt`,
ADD `sale_igst_per` float(10,2) NOT NULL DEFAULT '0.00' AFTER `sale_cess_amt`,
ADD `sale_igst_amt` float(10,2) NOT NULL DEFAULT '0.00' AFTER `sale_igst_per`;