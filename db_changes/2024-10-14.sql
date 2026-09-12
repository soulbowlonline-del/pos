CREATE TABLE `tbl_scanned_items` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL,
  `computer_name` varchar(255) COLLATE 'utf8_general_ci' NOT NULL,
  `user_email` varchar(255) COLLATE 'utf8_general_ci' NOT NULL,
  `item_id` int NOT NULL,
  `bar_code` varchar(255) COLLATE 'utf8_general_ci' NOT NULL,
  `is_coupon` int NULL,
  `qty` int NOT NULL,
  `sale_rate` float(10,2) NOT NULL,
  `base_price` float(10,2) NOT NULL,
  `mrp` float(10,2) NOT NULL,
  `item_detail` text COLLATE 'utf8_general_ci' NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE='InnoDB' COLLATE 'utf8_general_ci';