#!/bin/bash
# Differential test for item/ordertest - an older, still-routed copy of
# item/order. Same harness as item-order-difftest.sh; the cases that differ
# between the two actions are the ones worth having here.
#
# This is the heaviest write path in the application: an Order (or an
# OrderHold), its lines, a stock deduction per line through Order::UpdateStock,
# a StockLog line per batch it spans, and possibly a requisition. Plus an SMS
# and, for an online order, a callback to the webshop - both through the
# outbound stub, so both are compared.
#
# It runs against the adjust fixture's item (9990600: its own vendor, detail,
# stock and requisition) and the setup_test customer, reloaded before every
# call, so nothing it writes or deletes can reach real data.
#
# Order ids and bill numbers are auto-increment and differ between the two
# runs, so they are normalised; every amount is not.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
LOGIN=1
CUST=9990001
OUTLET=5

q() { docker exec -i pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE -e \"$1\"" 2>/dev/null; }

reset() {
  # Order matters. The fixtures delete and re-insert tbl_item_detail 9990600,
  # and tbl_order_item rows from the previous case still reference it - so
  # loading them first hit a foreign key, aborted the script partway, and left
  # the stock row and the requisition line missing for that call only. Clear
  # the orders first, then load.
  q "DELETE FROM tbl_order_item      WHERE item_id = 9990600;
     DELETE FROM tbl_order_hold_item WHERE item_id = 9990600;
     DELETE FROM tbl_order           WHERE id > 9990000 AND id NOT IN (9990001, 9990500);
     DELETE FROM tbl_order_hold      WHERE id > 9990000 AND id <> 9990002;
     DELETE FROM tbl_outbound_stub_log;" >/dev/null 2>&1
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/setup_test.sql >/dev/null 2>&1
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/adjust_fixture.sql >/dev/null 2>&1
}

# Orders this test creates are deleted by id range, not by customer: the cases
# that post no customer_id were being left behind, and each one shifted the
# bill number the next case reads. Real orders all sit below 1,581,602; the
# fixtures are 9990001 (setup_test) and 9990500 (refund), and everything else
# above 9990000 belongs to this test.
MODE_PAY=$(q "SELECT MIN(id) FROM tbl_payment_mode WHERE type_id = 0;")
MODE_DEL=$(q "SELECT MIN(id) FROM tbl_payment_mode WHERE type_id = 1;")

# order ids, bill numbers and the stub fingerprint move between runs
# The bill number is drawn from the highest one in the financial year, so it
# depends on what else is in the table; it is normalised away rather than
# compared. What it is built from - the prefix and the year - is not.
norm() { sed -E 's/"order_id":"?[0-9]+"?/"order_id":<ID>/g; s/"bill_no":"[^"]*"/"bill_no":"<BILL>"/g; s/stub-[0-9a-f]+/stub-<FP>/g; s/id=[0-9]+/id=<ID>/g; s/::[0-9]+::/::<NO>::/g'; }

written() {
  q "SELECT CONCAT('order:', IFNULL(customer_id,'<N>'),'|',total_amt,'|',IFNULL(discount_amt,'<N>'),'|',outlet_id,'|',mode_of_payment,'|',mode_of_delivery,'|',IFNULL(online_order_id,'<N>'))
       FROM tbl_order WHERE customer_id = $CUST AND id <> 9990001 ORDER BY id;
     SELECT CONCAT('hold:', IFNULL(customer_id,'<N>'),'|',total_amt) FROM tbl_order_hold WHERE customer_id = $CUST ORDER BY id;
     SELECT CONCAT('line:', item_id,'|',item_detail_id,'|',qty,'|',price,'|',sale_rate,'|',total_amt,'|',IFNULL(tax_id,'<N>'),'|',IFNULL(tax_amount,'<N>'),'|',IFNULL(status,'<N>'))
       FROM tbl_order_item WHERE item_id = 9990600 ORDER BY id;
     SELECT CONCAT('holdline:', item_id,'|',qty,'|',total_amt) FROM tbl_order_hold_item WHERE item_id = 9990600 ORDER BY id;
     SELECT CONCAT('stock:', balance_qty,'|',purchase_qty) FROM tbl_item_stock WHERE item_id = 9990600 ORDER BY id;
     SELECT CONCAT('stocklog:', previous_qty,'|',current_qty,'|',Qty,'|',type_id) FROM tbl_stock_log WHERE item_id = 9990600 ORDER BY id;
     SELECT CONCAT('mrsdetail:', mrs_id,'|',req_qty,'|',approved_qty) FROM tbl_mrs_detail WHERE item_id = 9990600 ORDER BY id;
     SELECT CONCAT('outbound:', channel,' ',target,' ',payload) FROM tbl_outbound_stub_log ORDER BY id;" | norm
}

run_case() {
  local name="$1" body="$2" items="$3"
  reset; local r1; r1=$(curl -sS --max-time 180 -X POST -H "userlogin: $LOGIN" --data-urlencode "item_details=$items" -d "$body" "$BASE/api/item/ordertest" | norm); local w1; w1=$(written)
  reset; local r2; r2=$(curl -sS --max-time 180 -X POST -H "userlogin: $LOGIN" --data-urlencode "item_details=$items" -d "$body" "$BASE/v2/api/item/ordertest" | norm); local w2; w2=$(written)
  if [ "$r1" = "$r2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-44s OK  (response, order, lines, stock, logs, outbound)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1"|head -c 300)"; echo "      resp yii2: $(echo "$r2"|head -c 300)"; }
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1|tr '\n' ' '|head -c 420)"; echo "      rows yii2: $(echo $w2|tr '\n' ' '|head -c 420)"; }
  fi
}

run_error_case() {
  local name="$1" body="$2" items="$3"
  reset; local c1; c1=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 180 -X POST -H "userlogin: $LOGIN" --data-urlencode "item_details=$items" -d "$body" "$BASE/api/item/ordertest"); local w1; w1=$(written)
  reset; local c2; c2=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 180 -X POST -H "userlogin: $LOGIN" --data-urlencode "item_details=$items" -d "$body" "$BASE/v2/api/item/ordertest"); local w2; w2=$(written)
  if [ "$c1" = "$c2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-44s OK  (status %s, rows)\n" "$name" "$c1"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH (%s vs %s)\n" "$name" "$c1" "$c2"; FAIL=$((FAIL+1))
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1|tr '\n' ' '|head -c 300)"; echo "      rows yii2: $(echo $w2|tr '\n' ' '|head -c 300)"; }
  fi
}

LINE='{"bar_code":"PORTADJBAR600","qty":2,"base_price":"40.00","sale_rate":"50.00","mrp":"55.00","total_amount":"100.00","discount_id":0,"discount_amt":0,"tax_id":0,"tax_amt":0,"cgst_per":"2.50","sgst_per":"2.50","cess_per":"0.00","igst_per":0,"cgst_amt":2.5,"sgst_amt":2.5,"cess_amount":0,"igst_amount":0}'
BIG='{"bar_code":"PORTADJBAR600","qty":80,"base_price":"40.00","sale_rate":"50.00","mrp":"55.00","total_amount":"4000.00","discount_id":0,"discount_amt":0,"tax_id":0,"tax_amt":0,"cgst_per":"2.50","sgst_per":"2.50","cess_per":"0.00","igst_per":0,"cgst_amt":100,"sgst_amt":100,"cess_amount":0,"igst_amount":0}'
NOBAR='{"bar_code":"NOSUCHBARCODE","qty":1,"base_price":"1.00","sale_rate":"1.00","mrp":"1.00","total_amount":"1.00","discount_id":0,"discount_amt":0,"tax_id":0,"tax_amt":0,"cgst_per":0,"sgst_per":0,"cess_per":0,"igst_per":0,"cgst_amt":0,"sgst_amt":0,"cess_amount":0,"igst_amount":0}'

B="mode_of_payment=$MODE_PAY&mode_of_delivery=$MODE_DEL&total_amt=100.00&discount_amt=0.00&outlet_id=$OUTLET&customer_id=$CUST"

echo "=== item/ordertest differential  (modes $MODE_PAY/$MODE_DEL) ==="
run_case "sale of two units"                  "$B&status_id=1" "[$LINE]"
run_case "sale with no customer"              "mode_of_payment=$MODE_PAY&mode_of_delivery=$MODE_DEL&total_amt=100.00&discount_amt=0.00&outlet_id=$OUTLET&status_id=1" "[$LINE]"
# No two-lines case here. ordertest groups its tax summary by tax_id alone
# and selects t.* with no SUM(), so two lines sharing a tax return whichever
# row the database happens to pick - undefined on both stacks, and not
# something a differential test can pin down.
run_case "quantity beyond the batch"          "$B&total_amt=4000.00&status_id=1" "[$BIG]"
run_case "held order, no stock movement"      "$B&status_id=2" "[$LINE]"
run_case "status_id 3 is rejected"            "$B&status_id=3" "[$LINE]"
run_case "bar code matches nothing"           "$B&status_id=1" "[$NOBAR]"
run_case "empty item array"                   "$B&status_id=1" "[]"
run_case "item_details is not json"           "$B&status_id=1" "notjson"
run_case "no login header, no order"          "$B&status_id=1" "[$LINE]"
run_case "unknown credit note"                "$B&status_id=1&credit_note_id=NOSUCHNOTE" "[$LINE]"
run_error_case "unknown customer"             "$B&customer_id=99999999&status_id=1" "[$LINE]"

echo -n "  missing mode_of_payment                       "
reset; a=$(curl -sS --max-time 60 -X POST -H "userlogin: $LOGIN" --data-urlencode "item_details=[$LINE]" -d "status_id=1&outlet_id=$OUTLET" "$BASE/api/item/ordertest")
reset; b=$(curl -sS --max-time 60 -X POST -H "userlogin: $LOGIN" --data-urlencode "item_details=[$LINE]" -d "status_id=1&outlet_id=$OUTLET" "$BASE/v2/api/item/ordertest")
if [ "$a" = "$b" ]; then echo "OK  (${#a} bytes)"; PASS=$((PASS+1)); else echo "MISMATCH"; echo "      $a"; echo "      $b"; FAIL=$((FAIL+1)); fi

reset
echo
echo "  passed: $PASS   mismatched: $FAIL"
