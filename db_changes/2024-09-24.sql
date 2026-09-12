CREATE TABLE tbl_customer_otp_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (customer_id, otp_code)  -- Ensure unique OTP per customer
);

ALTER TABLE tbl_customer
ADD COLUMN is_enable_wa INT(11) DEFAULT 0;