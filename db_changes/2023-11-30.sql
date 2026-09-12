ALTER TABLE `tbl_stock_adjust_log`
ADD `create_user_id` int(11) NOT NULL DEFAULT '1' AFTER `adjusted`;

ALTER TABLE `tbl_stock_adjust_log`
ADD FOREIGN KEY (`create_user_id`) REFERENCES `tbl_user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;