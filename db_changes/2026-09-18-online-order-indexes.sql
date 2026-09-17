-- Two missing indexes behind the online-order endpoints.
--
-- OnlineOrder::toArray() runs, for every row it renders:
--
--   SELECT * FROM tbl_order WHERE online_order_id = ?
--
-- tbl_order has 1,580,276 rows and no index on that column, so each row of
-- the response costs a full table scan - measured at 291 ms. order/online
-- returns 177 rows on the default date window, so the endpoint spent the best
-- part of a minute in that one query before timing out. It is called by the
-- delivery app.
--
-- The second is the same shape: the line items of an online order are looked
-- up by order_id against 89,113 unindexed rows.
--
-- Both are pure additions. No column, constraint or value changes, so the
-- application cannot tell the difference except in how long it waits.

ALTER TABLE `tbl_order`
  ADD INDEX `idx_order_online_order_id` (`online_order_id`);

ALTER TABLE `tbl_online_order_item`
  ADD INDEX `idx_online_order_item_order_id` (`order_id`);
