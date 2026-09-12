CREATE TABLE IF NOT EXISTS `pos_live`.`tbl_item_velocity` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `item_id` int(11) NOT NULL,
  `velocity_change_percent` FLOAT(10,2) DEFAULT NULL COMMENT 'Percentage change in sales velocity (last 2 weeks vs previous 2 weeks)',
  `update_time` DATETIME DEFAULT NULL COMMENT 'Last updated timestamp',
  INDEX `idx_velocity_change` (`velocity_change_percent`),
  INDEX `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `tbl_item_velocity`
ADD INDEX `item_id` (`item_id`);