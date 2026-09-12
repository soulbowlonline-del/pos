ALTER TABLE `tbl_whatsapp_logs`
ADD `user_id` int(11) NULL AFTER `status`,
ADD `computer_name` varchar(255) COLLATE 'utf8_general_ci' NULL AFTER `user_id`;