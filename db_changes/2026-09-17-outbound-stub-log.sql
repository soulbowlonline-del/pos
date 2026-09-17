CREATE TABLE IF NOT EXISTS tbl_outbound_stub_log (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  channel    VARCHAR(16)  NOT NULL,
  target     VARCHAR(512) NOT NULL,
  payload    MEDIUMTEXT   NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_channel (channel),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SELECT CONCAT('tbl_outbound_stub_log ready, rows=', COUNT(*)) FROM tbl_outbound_stub_log;
