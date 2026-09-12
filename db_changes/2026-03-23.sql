ALTER TABLE `tbl_order`
ADD `gross_total_amt` float(10,2) NULL DEFAULT '0.00' AFTER `total_amt`;

ALTER TABLE `tbl_order_hold`
ADD `gross_total_amt` float(10,2) NULL DEFAULT '0.00' AFTER `total_amt`;