-- One row per Claude call made by the AI screens under /v2/ai.
--
-- It is the spending record and the spending limit at once: before every call
-- the month's SUM(cost_usd) is compared with POS_AI_MONTHLY_BUDGET_USD, and at
-- or above it the call is refused - the rest of the ERP carries on. The code
-- refuses to call Claude at all while this table is missing, so the limit can
-- never be skipped by forgetting this file. The rule-based insights need no
-- table and work either way.
--
-- `summary` holds what was asked (a question, a file name), with anything that
-- looks like a phone number or an e-mail address masked before it is written.
--
-- Pure addition - no existing table, column or value changes. Safe to run more
-- than once. Old rows can be deleted at any time; only the current month's are
-- read for the limit.

CREATE TABLE IF NOT EXISTS `tbl_ai_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `feature` VARCHAR(32) NOT NULL,
  `user_id` INT NULL,
  `model` VARCHAR(64) NOT NULL,
  `input_tokens` INT NOT NULL DEFAULT 0,
  `output_tokens` INT NOT NULL DEFAULT 0,
  `cache_read_tokens` INT NOT NULL DEFAULT 0,
  `cache_write_tokens` INT NOT NULL DEFAULT 0,
  `cost_usd` DECIMAL(10,5) NOT NULL DEFAULT 0,
  `status` VARCHAR(16) NOT NULL,
  `summary` VARCHAR(255) NULL,
  `create_time` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ai_log_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
