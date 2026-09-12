-- Customer Loyalty Points Table
CREATE TABLE `tbl_customer_loyalty` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `customer_id` int NOT NULL,
  `total_points` decimal(10,2) DEFAULT 0,
  `lifetime_earned` decimal(10,2) DEFAULT 0,
  `lifetime_redeemed` decimal(10,2) DEFAULT 0,
  `status` tinyint DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_customer` (`customer_id`),
  INDEX `idx_customer_points` (`customer_id`, `total_points`)
) ENGINE=InnoDB;

-- Loyalty Transactions Table
CREATE TABLE `tbl_loyalty_transactions` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `customer_id` int NOT NULL,
  `order_id` int NULL,
  `transaction_type` enum('EARN','REDEEM','BONUS','EXPIRE','ADJUST') NOT NULL,
  `points` decimal(10,2) NOT NULL,
  `description` varchar(255) NULL,
  `reference_amount` decimal(10,2) NULL,
  `created_by` int NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_customer_trans` (`customer_id`, `created_at`),
  INDEX `idx_order_trans` (`order_id`)
) ENGINE=InnoDB;

-- Loyalty Settings Table
CREATE TABLE `tbl_loyalty_settings` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text NOT NULL,
  `description` varchar(255) NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default settings
INSERT INTO `tbl_loyalty_settings` (`setting_key`, `setting_value`, `description`) VALUES
('earn_rate', '1', 'Points earned per 100 spent'),
('min_redeem_points', '50', 'Minimum points required for redemption'),
('point_value', '1', 'Value of 1 point in currency'),
('expiry_months', '12', 'Points expiry in months (0 = no expiry)'),
('is_active', '1', 'Loyalty program active status');

CREATE INDEX `idx_setting_key` ON `tbl_loyalty_settings` (`setting_key`);


CREATE TABLE `tbl_customer_otp` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `customer_id` int NULL,
  `phone_number` varchar(15) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `attempts` int DEFAULT 0,
  `is_verified` tinyint DEFAULT 0,
  `expires_at` timestamp NULL,
  `verified_at` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_customer_otp` (`customer_id`, `is_verified`),
  INDEX `idx_phone_otp` (`phone_number`, `otp_code`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB;