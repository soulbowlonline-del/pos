#!/bin/bash
# Differential test for order/refund.
#
# This is the most destructive endpoint in the API: it writes OrderRefund,
# OrderRefundItem, ItemStock and StockLog, adjusts loyalty points, can raise a
# credit note, and deletes pending requisition rows. So it runs entirely against
# fixture rows above id 9990000 - its own item, item detail, stock, order and
# line - and the fixture is reloaded before every single call.
#
# Four axes are compared: the response, the refund rows written, the stock row
# afterwards, and the stock log. The credit-note number is random, so it is
# normalised; the amount is not.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
BAR=PORTREFBAR500
ORDER=9990500

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/refund_fixture.sql >/dev/null 2>&1
  docker exec pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE -e "
    DELETE FROM tbl_credit_note WHERE id > 15972;"' >/dev/null 2>&1
}

norm() { sed -E 's/"credit_number":"[0-9]+"/"credit_number":"<RND>"/g'; }

written() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(\"refund:\", qty,\"|\",type_id,\"|\",IFNULL(total_amt,\"<N>\"),\"|\",order_id,\"|\",IFNULL(customer_id,\"<N>\"))
      FROM tbl_order_refund WHERE order_id = 9990500 ORDER BY id;
    SELECT CONCAT(\"refunditem:\", qty,\"|\",total_amt,\"|\",price,\"|\",discount_amt,\"|\",tax_amt,\"|\",item_id,\"|\",item_detail_id)
      FROM tbl_order_refund_item WHERE item_id = 9990500 ORDER BY id;
    SELECT CONCAT(\"stock:\", purchase_qty,\"|\",balance_qty) FROM tbl_item_stock WHERE id = 9990500;
    SELECT CONCAT(\"stocklog:\", batch_no,\"|\",previous_qty,\"|\",current_qty,\"|\",Qty,\"|\",type_id)
      FROM tbl_stock_log WHERE item_id = 9990500 ORDER BY id;
    SELECT CONCAT(\"creditnote:\", amt) FROM tbl_credit_note WHERE amt IN (100,200,300,1000) ORDER BY id;"' 2>/dev/null
}

run_case() {
  local name="$1" body="$2"
  reset; local r1; r1=$(curl -sS --max-time 120 -X POST -H "userlogin: 1" --data-urlencode "item_details=$3" -d "$body" "$BASE/api/order/refund" | norm)
  local w1; w1=$(written)
  reset; local r2; r2=$(curl -sS --max-time 120 -X POST -H "userlogin: 1" --data-urlencode "item_details=$3" -d "$body" "$BASE/v2/api/order/refund" | norm)
  local w2; w2=$(written)

  if [ "$r1" = "$r2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-44s OK  (response, rows, stock, log)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1" | head -c 260)"; echo "      resp yii2: $(echo "$r2" | head -c 260)"; }
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1 | tr '\n' ' ' | head -c 320)"; echo "      rows yii2: $(echo $w2 | tr '\n' ' ' | head -c 320)"; }
  fi
}

# status-only comparison: Yii 1 reads $refundmodel after a loop that may never
# have assigned it, which is a warning and a framework-rendered 500 on both.
run_error_case() {
  local name="$1" body="$2" items="$3"
  reset; local c1; c1=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST -H "userlogin: 1" --data-urlencode "item_details=$items" -d "$body" "$BASE/api/order/refund"); local w1; w1=$(written)
  reset; local c2; c2=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST -H "userlogin: 1" --data-urlencode "item_details=$items" -d "$body" "$BASE/v2/api/order/refund"); local w2; w2=$(written)
  if [ "$c1" = "$c2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-44s OK  (status %s, rows)\n" "$name" "$c1"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH (%s vs %s)\n" "$name" "$c1" "$c2"; FAIL=$((FAIL+1))
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1|tr '\n' ' '|head -c 300)"; echo "      rows yii2: $(echo $w2|tr '\n' ' '|head -c 300)"; }
  fi
}

ONE='[{"bar_code":"PORTREFBAR500","qty":"1","total_amt":100,"is_return":"1","total_sale":100}]'
TWO='[{"bar_code":"PORTREFBAR500","qty":"2","total_amt":200,"is_return":"2","total_sale":200}]'
ALL='[{"bar_code":"PORTREFBAR500","qty":"10","total_amt":1000,"is_return":"10","total_sale":1000}]'
OVER='[{"bar_code":"PORTREFBAR500","qty":"99","total_amt":100,"is_return":"99","total_sale":100}]'
NOITEM='[{"bar_code":"NOSUCHBARCODE","qty":"1","total_amt":100,"is_return":"1","total_sale":100}]'

echo "=== order/refund differential ==="
run_case "refund one unit"             "order_id=$ORDER&type_id=1" "$ONE"
run_case "refund two units"            "order_id=$ORDER&type_id=1" "$TWO"
run_case "refund the whole line"       "order_id=$ORDER&type_id=1" "$ALL"
run_case "refund more than was sold"   "order_id=$ORDER&type_id=1" "$OVER"
run_case "type_id=2 raises a credit note" "order_id=$ORDER&type_id=2" "$ONE"
run_case "unknown barcode"             "order_id=$ORDER&type_id=1" "$NOITEM"
run_case "unknown order"               "order_id=99999999&type_id=1" "$ONE"
run_case "no item_details key"         "order_id=$ORDER&type_id=1" ""
run_error_case "empty item array"      "order_id=$ORDER&type_id=1" "[]"
run_error_case "item_details not json" "order_id=$ORDER&type_id=1" "notjson"

echo -n "  missing order_id and type_id                 "
reset; a=$(curl -sS --max-time 60 -X POST -H "userlogin: 1" --data-urlencode "item_details=$ONE" "$BASE/api/order/refund")
reset; b=$(curl -sS --max-time 60 -X POST -H "userlogin: 1" --data-urlencode "item_details=$ONE" "$BASE/v2/api/order/refund")
if [ "$a" = "$b" ]; then echo "OK  (${#a} bytes)"; PASS=$((PASS+1)); else echo "MISMATCH"; echo "      $a"; echo "      $b"; FAIL=$((FAIL+1)); fi

reset
echo
echo "  passed: $PASS   mismatched: $FAIL"
