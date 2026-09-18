#!/bin/bash
# Differential test for item/updateStock - receiving a GRN.
#
# It writes: the purchase bill's status, and each detail line's approved_qty,
# order, margin, amount and four tax columns. It does NOT write stock, because
# the ItemStock save is commented out in Yii 1 - so tbl_item_stock is compared
# too, to prove that stays true on both stacks.
#
# Runs on grn_fixture.sql's bills (9990010 unapproved with lines, 9990011
# unapproved with none, 9990012 already approved), reloaded before every call.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
LOGIN=9990004

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/grn_fixture.sql >/dev/null 2>&1
}
q() { docker exec -i pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE -e \"$1\"" 2>/dev/null; }

# Load the fixture before reading anything out of it: the rows are removed by
# the teardown, so querying first returned empty strings and every case below
# silently tested a bar code of "".
reset
BARCODE=$(q "SELECT d.bar_code FROM tbl_purchase_bill_detail p JOIN tbl_item_detail d ON d.id = p.item_detail_id WHERE p.id = 9990020;" | head -1)
DETAILID=$(q "SELECT item_detail_id FROM tbl_purchase_bill_detail WHERE id = 9990020;" | head -1)

written() {
  q "SELECT CONCAT('bill:', id,'|',status) FROM tbl_purchase_bill WHERE id IN (9990010,9990011,9990012) ORDER BY id;
     SELECT CONCAT('line:', id,'|',IFNULL(approved_qty,'<N>'),'|',IFNULL(\`order\`,'<N>'),'|',IFNULL(margin,'<N>'),'|',IFNULL(amount,'<N>'),'|',IFNULL(cgst_amt,'<N>'),'|',IFNULL(sgst_amt,'<N>'),'|',IFNULL(cess_amt,'<N>'),'|',IFNULL(igst_amt,'<N>'))
       FROM tbl_purchase_bill_detail WHERE id IN (9990020,9990021,9990022) ORDER BY id;
     SELECT CONCAT('stockrows:', COUNT(*)) FROM tbl_item_stock WHERE item_detail_id = $DETAILID;
     SELECT CONCAT('stocksum:', IFNULL(SUM(balance_qty),0)) FROM tbl_item_stock WHERE item_detail_id = $DETAILID;"
}

run_case() {
  local name="$1" details="$2" login="${3:-$LOGIN}"
  reset; local r1; r1=$(curl -sS --max-time 120 -X POST ${login:+-H "userlogin: $login"} --data-urlencode "stock_details=$details" "$BASE/api/item/updateStock"); local w1; w1=$(written)
  reset; local r2; r2=$(curl -sS --max-time 120 -X POST ${login:+-H "userlogin: $login"} --data-urlencode "stock_details=$details" "$BASE/v2/api/item/update-stock"); local w2; w2=$(written)
  if [ "$r1" = "$r2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-46s OK  (response, bill, lines, stock)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-46s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1"|head -c 220)"; echo "      resp yii2: $(echo "$r2"|head -c 220)"; }
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1|tr '\n' ' '|head -c 340)"; echo "      rows yii2: $(echo $w2|tr '\n' ' '|head -c 340)"; }
  fi
}

run_error_case() {
  local name="$1" details="$2"
  reset; local c1; c1=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST -H "userlogin: $LOGIN" --data-urlencode "stock_details=$details" "$BASE/api/item/updateStock"); local w1; w1=$(written)
  reset; local c2; c2=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST -H "userlogin: $LOGIN" --data-urlencode "stock_details=$details" "$BASE/v2/api/item/update-stock"); local w2; w2=$(written)
  if [ "$c1" = "$c2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-46s OK  (status %s, rows)\n" "$name" "$c1"; PASS=$((PASS+1))
  else
    printf "  %-46s MISMATCH (%s vs %s)\n" "$name" "$c1" "$c2"; FAIL=$((FAIL+1))
  fi
}

ONE="[{\"bar_code\":\"$BARCODE\",\"qty\":\"7\",\"purchase_bill_id\":\"9990010\",\"entry_position\":\"1\"}]"
BATCH="[{\"bar_code\":\"$BARCODE\",\"qty\":\"4\",\"purchase_bill_id\":\"9990010\",\"entry_position\":\"2\",\"batch_no\":\"PTBATCH1\"}]"
ZERO="[{\"bar_code\":\"$BARCODE\",\"qty\":\"0\",\"purchase_bill_id\":\"9990010\",\"entry_position\":\"1\"}]"
TWICE="[{\"bar_code\":\"$BARCODE\",\"qty\":\"2\",\"purchase_bill_id\":\"9990010\",\"entry_position\":\"1\",\"batch_no\":\"PTB\"},{\"bar_code\":\"$BARCODE\",\"qty\":\"3\",\"purchase_bill_id\":\"9990010\",\"entry_position\":\"2\",\"batch_no\":\"PTB\"}]"
APPROVED="[{\"bar_code\":\"$BARCODE\",\"qty\":\"7\",\"purchase_bill_id\":\"9990012\",\"entry_position\":\"1\"}]"
NOLINES="[{\"bar_code\":\"$BARCODE\",\"qty\":\"7\",\"purchase_bill_id\":\"9990011\",\"entry_position\":\"1\"}]"
MISSINGQTY="[{\"bar_code\":\"$BARCODE\",\"purchase_bill_id\":\"9990010\"}]"
NOBARCODE="[{\"bar_code\":\"NOSUCHBARCODE\",\"qty\":\"7\",\"purchase_bill_id\":\"9990010\",\"entry_position\":\"1\"}]"

if [ -z "$BARCODE" ] || [ -z "$DETAILID" ]; then
  echo "  FIXTURE NOT LOADED: bar code or item detail id is empty - refusing to run"
  echo "  passed: 0   mismatched: 1"; exit 1
fi
echo "=== item/updateStock differential  (barcode $BARCODE, detail $DETAILID) ==="
run_case "receive one line"                 "$ONE"
run_case "with an explicit batch_no"        "$BATCH"
run_case "quantity zero"                    "$ZERO"
run_case "same batch twice in one call"     "$TWICE"
run_case "bill already received"            "$APPROVED"
run_case "bill with no detail lines"        "$NOLINES"
run_case "line missing qty"                 "$MISSINGQTY"
run_case "no login header"                  "$ONE" ""
run_case "stock_details is not json"        "notjson"
run_case "stock_details is an empty array"  "[]"
run_error_case "bar code matches nothing"   "$NOBARCODE"
run_error_case "unknown purchase bill"      "[{\"bar_code\":\"$BARCODE\",\"qty\":\"1\",\"purchase_bill_id\":\"99999999\",\"entry_position\":\"1\"}]"

echo -n "  no stock_details field at all                  "
reset; a=$(curl -sS --max-time 60 -X POST -H "userlogin: $LOGIN" "$BASE/api/item/updateStock")
reset; b=$(curl -sS --max-time 60 -X POST -H "userlogin: $LOGIN" "$BASE/v2/api/item/update-stock")
if [ "$a" = "$b" ]; then echo "OK  (${#a} bytes)"; PASS=$((PASS+1)); else echo "MISMATCH"; echo "      $a"; echo "      $b"; FAIL=$((FAIL+1)); fi

reset
echo
echo "  passed: $PASS   mismatched: $FAIL"
