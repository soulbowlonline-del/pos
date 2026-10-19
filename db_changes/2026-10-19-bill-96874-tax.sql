-- Bill 96874 (order 9994506, 19 Oct 2026): GST recorded at 90%.
--
-- The till sent tax_amount, cgst_amt and sgst_amt at 90% of the GST on each
-- taxed line's price, on a bill with its 5% bill discount. The line totals and
-- the 3380 paid are right; only the recorded tax is short, by 18.07 over 7
-- lines, so the order and GST reports understate that day's tax. The cause is
-- in the .NET till, not here (PHP stores what the till sends).
--
-- This sets the 7 lines' tax to price x qty x rate, which is what their
-- total_amt already includes. Nothing else changes: not price, not total_amt,
-- not the order. The WHERE matches only this bill's under-taxed CGST/SGST
-- lines, so a second run changes 0 rows.
--
-- Back up first (see the commit message for the commands), then:
--   docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -t pos_live' < db_changes/2026-10-19-bill-96874-tax.sql

SELECT 'before' AS step, COUNT(*) AS lines_to_fix,
       ROUND(SUM(price * qty * (cgst_per + sgst_per + cess_per) / 100 - tax_amount), 2) AS tax_short
  FROM tbl_order_item
 WHERE order_id = 9994506
   AND igst_per = 0
   AND (cgst_per + sgst_per + cess_per) > 0
   AND tax_amount < 0.99 * price * qty * (cgst_per + sgst_per + cess_per) / 100;

UPDATE tbl_order_item
   SET cgst_amt   = ROUND(price * qty * cgst_per / 100, 3),
       sgst_amt   = ROUND(price * qty * sgst_per / 100, 3),
       cess_amt   = ROUND(price * qty * cess_per / 100, 3),
       tax_amount = ROUND(price * qty * (cgst_per + sgst_per + cess_per) / 100, 4)
 WHERE order_id = 9994506
   AND igst_per = 0
   AND (cgst_per + sgst_per + cess_per) > 0
   AND tax_amount < 0.99 * price * qty * (cgst_per + sgst_per + cess_per) / 100;

SELECT ROW_COUNT() AS rows_changed;

-- Expected: price + tax over all 13 lines = 3380.10, the lines' total_amt and
-- (to the paisa) the bill's 3380.
SELECT 'after' AS step, o.total_amt AS bill_total,
       ROUND(SUM(i.price * i.qty), 2) AS taxable,
       ROUND(SUM(i.tax_amount), 2) AS tax,
       ROUND(SUM(i.cgst_amt + i.sgst_amt + i.cess_amt), 2) AS cgst_sgst_cess,
       ROUND(SUM(i.price * i.qty + i.tax_amount), 2) AS price_plus_tax,
       ROUND(SUM(i.total_amt), 2) AS lines_total
  FROM tbl_order o
  JOIN tbl_order_item i ON i.order_id = o.id
 WHERE o.id = 9994506
 GROUP BY o.id, o.total_amt;
