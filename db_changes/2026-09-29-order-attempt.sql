-- Whether the answer to a POS checkout reached the till: item/order,
-- item/ordertest and item/punchorder, on both stacks.
--
-- The .NET till posts a checkout through a synchronous WebClient that gives up
-- after 100 seconds. When the call fails - the timeout, an HTTP 500, a dropped
-- connection - the till shows the error and keeps the cart, and the cashier
-- presses Save again: an identical request, often well over a minute later.
-- The Idempotency-Key header it sends is a new GUID on every click, so it says
-- nothing about a retry. OrderDedupe's ten-second content window does not
-- reach that far, and widening it would swallow genuine identical sales.
--
-- What tells the two apart is whether the first answer was delivered. A sale
-- checked by content (no online_order_id, no request_id) gets a row here when
-- it commits, status 0; at the end of the request it becomes 1 if the success
-- answer went out with a 2xx status to a connection still open within 90
-- seconds of the request starting, else 2. An identical request - same
-- fingerprint, which is the md5 of OrderDedupe's content key (cashier,
-- customer, payment mode, total, lines), from the same cashier - within ten
-- minutes of a row in 2 is answered with that order and creates nothing; a row
-- in 0 is waited for; a row in 1 means the cashier had the bill, so the
-- identical request is a new sale.
--
-- The code works before this runs: a missing table is noticed once per
-- request and this check is skipped, exactly as before. Pure addition - no
-- existing table, column or value changes. Safe to run more than once. Rows
-- are only read back for ten minutes; old ones can be deleted at any time.

CREATE TABLE IF NOT EXISTS `tbl_order_attempt` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fingerprint` CHAR(32) CHARACTER SET ascii NOT NULL,
  `create_user_id` INT NOT NULL,
  `order_id` INT NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 0,
  `started_at` DATETIME(3) NOT NULL,
  `finished_at` DATETIME(3) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fingerprint_started` (`fingerprint`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
