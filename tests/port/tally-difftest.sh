#!/bin/bash
# Differential test for the ported tally cashsale action.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
# The first three dates below carry order items with tax_id = 0. There is no
# tbl_tax row 0, and the query orders by tax_id, so that row is rendered first
# with the tax figures still unset - which was a 500 on PHP 8 until both
# stacks cleared them per iteration. 32 order items across 23 dates are in
# this state; without one of them here the case was never exercised.
BADTAX=$(docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
  SELECT DISTINCT oi.create_date FROM tbl_order_item oi
    LEFT JOIN tbl_tax t ON t.id = oi.tax_id
   WHERE t.id IS NULL AND oi.create_date IS NOT NULL ORDER BY oi.create_date LIMIT 3;"' 2>/dev/null | tr '\n' ' ')
for d in $BADTAX 2026-09-15 2026-09-14 2026-09-13 2026-08-20 2026-08-01 1990-01-01; do
  r1=$(curl -sS --max-time 240 -X POST "$BASE/api/tally/cashsale?date=$d")
  r2=$(curl -sS --max-time 240 -X POST "$BASE/v2/api/tally/cashsale?date=$d")
  if [ "$r1" = "$r2" ]; then printf "  cashsale %-12s OK  (%s bytes)\n" "$d" "${#r1}"; PASS=$((PASS+1))
  else printf "  cashsale %-12s MISMATCH (yii1=%s yii2=%s)\n" "$d" "${#r1}" "${#r2}"; FAIL=$((FAIL+1))
       echo "      yii1: $(echo "$r1" | head -c 240)"; echo "      yii2: $(echo "$r2" | head -c 240)"; fi
done
for d in 2026-09-16 2026-09-15 2026-09-14 2026-09-12 1990-01-01; do
  r1=$(curl -sS --max-time 300 -X POST "$BASE/api/tally/stockreturn?date=$d")
  r2=$(curl -sS --max-time 300 -X POST "$BASE/v2/api/tally/stockreturn?date=$d")
  if [ "$r1" = "$r2" ]; then printf "  stockreturn %-12s OK  (%s bytes)\n" "$d" "${#r1}"; PASS=$((PASS+1))
  else printf "  stockreturn %-12s MISMATCH (yii1=%s yii2=%s)\n" "$d" "${#r1}" "${#r2}"; FAIL=$((FAIL+1)); fi
done
for q in "?date=2026-09-16" "?date=2026-09-15" "?date=2026-09-14" "?date=2026-09-13" "?date=1990-01-01" "?date=2026-09-15&id=796000" "?date=2026-09-15&id=99999999"; do
  r1=$(curl -sS --max-time 400 -X POST "$BASE/api/tally/paymentreport$q")
  r2=$(curl -sS --max-time 400 -X POST "$BASE/v2/api/tally/paymentreport$q")
  if [ "$r1" = "$r2" ]; then printf "  paymentreport %-28s OK  (%s bytes)\n" "$q" "${#r1}"; PASS=$((PASS+1))
  else printf "  paymentreport %-28s MISMATCH (yii1=%s yii2=%s)\n" "$q" "${#r1}" "${#r2}"; FAIL=$((FAIL+1)); fi
done
B2BDATES=$(docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "SELECT DISTINCT DATE(create_time) FROM tbl_b2bpurchase_bill_detail ORDER BY create_time DESC LIMIT 4;"' 2>/dev/null | tr '\n' ' ')
for d in $B2BDATES 1990-01-01; do
  r1=$(curl -sS --max-time 400 -X POST "$BASE/api/tally/b2btaxwise?date=$d")
  r2=$(curl -sS --max-time 400 -X POST "$BASE/v2/api/tally/b2btaxwise?date=$d")
  if [ "$r1" = "$r2" ]; then printf "  b2btaxwise %-13s OK  (%s bytes)\n" "$d" "${#r1}"; PASS=$((PASS+1))
  else printf "  b2btaxwise %-13s MISMATCH (yii1=%s yii2=%s)\n" "$d" "${#r1}" "${#r2}"; FAIL=$((FAIL+1)); fi
done
# b2bsales. The dates are picked from the bills themselves so the busiest day
# and a quiet one are both covered, plus a day with none and the empty date
# that used to reach MySQL as date(start_date) = "".
B2BSALESDATES=$(docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
  SELECT DATE(start_date) FROM tbl_b2bpurchase_bill
   GROUP BY DATE(start_date) ORDER BY COUNT(*) DESC LIMIT 3;"' 2>/dev/null | tr '\n' ' ')
for d in $B2BSALESDATES 1990-01-01; do
  r1=$(curl -sS --max-time 400 -X POST "$BASE/api/tally/b2bsales?date=$d")
  r2=$(curl -sS --max-time 400 -X POST "$BASE/v2/api/tally/b2bsales?date=$d")
  if [ "$r1" = "$r2" ]; then printf "  b2bsales %-13s OK  (%s bytes)\n" "$d" "${#r1}"; PASS=$((PASS+1))
  else printf "  b2bsales %-13s MISMATCH (yii1=%s yii2=%s)\n" "$d" "${#r1}" "${#r2}"; FAIL=$((FAIL+1))
       echo "      yii1: $(echo "$r1" | head -c 200)"; echo "      yii2: $(echo "$r2" | head -c 200)"; fi
done
r1=$(curl -sS --max-time 120 -X POST "$BASE/api/tally/b2bsales")
r2=$(curl -sS --max-time 120 -X POST "$BASE/v2/api/tally/b2bsales")
if [ "$r1" = "$r2" ]; then printf "  b2bsales %-13s OK  (%s bytes)\n" "(no date)" "${#r1}"; PASS=$((PASS+1))
else printf "  b2bsales %-13s MISMATCH\n" "(no date)"; echo "      yii1: $r1"; echo "      yii2: $r2"; FAIL=$((FAIL+1)); fi

# An empty date is not compared: it is broken on both stacks in different ways
# (Yii 1 throws CDbException, Yii 2 would run to the execution limit), and the
# port deliberately short-circuits it. See the commit message.
echo
echo "  passed: $PASS   mismatched: $FAIL"
