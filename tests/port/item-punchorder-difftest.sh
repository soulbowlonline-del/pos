#!/bin/bash
# Differential test for item/punchorder.
#
# punchorder prices the basket itself rather than trusting the client, raises
# the order through the same path as item/order, renders a PDF bill under the
# web root, posts it to the remote file endpoint and sends it over WhatsApp.
#
# Five axes: the response, the order and its lines, the stock and logs, the
# recorded outbound calls, and the PDF that landed on disk. The PDF is compared
# by name and by "is it a PDF and non-trivial", not by bytes: mPDF stamps a
# creation date into every file, so two runs never match.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
LOGIN=1
CUST=9990001
OUTLET=5
BILLS=/root/pos/pos83/uploadbills

q() { docker exec -i pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE -e \"$1\"" 2>/dev/null; }

reset() {
  q "DELETE FROM tbl_order_item      WHERE item_id = 9990600;
     DELETE FROM tbl_order_hold_item WHERE item_id = 9990600;
     DELETE FROM tbl_order           WHERE id > 9990000 AND id NOT IN (9990001, 9990500);
     DELETE FROM tbl_order_hold      WHERE id > 9990000 AND id <> 9990002;
     DELETE FROM tbl_outbound_stub_log;" >/dev/null 2>&1
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/setup_test.sql >/dev/null 2>&1
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/adjust_fixture.sql >/dev/null 2>&1
  rm -f "$BILLS"/9*.pdf
}

MODE_PAY=$(q "SELECT MIN(id) FROM tbl_payment_mode WHERE type_id = 0;")
MODE_DEL=$(q "SELECT MIN(id) FROM tbl_payment_mode WHERE type_id = 1;")
DISCOUNT=$(q "SELECT MIN(id) FROM tbl_discount WHERE type_id = 1 AND status = 0;")

# bill numbers and order ids move between runs; the PDF is named after the bill
norm() { sed -E 's/"order_id":"?[0-9]+"?/"order_id":<ID>/g; s/"bill_no":"?[^",]*"?/"bill_no":<BILL>/g; s/stub-[0-9a-f]+/stub-<FP>/g; s/[0-9]{6,}\.pdf/<BILL>.pdf/g; s/id=[0-9]+/id=<ID>/g; s/::[0-9]+::/::<NO>::/g'; }

written() {
  q "SELECT CONCAT('order:', IFNULL(customer_id,'<N>'),'|',total_amt,'|',IFNULL(discount_amt,'<N>'),'|',outlet_id,'|',IFNULL(is_mobile,'<N>'))
       FROM tbl_order WHERE id > 9990000 AND id NOT IN (9990001, 9990500) ORDER BY id;
     SELECT CONCAT('line:', item_id,'|',qty,'|',price,'|',sale_rate,'|',mrp,'|',total_amt,'|',IFNULL(tax_amount,'<N>'))
       FROM tbl_order_item WHERE item_id = 9990600 ORDER BY id;
     SELECT CONCAT('stock:', balance_qty) FROM tbl_item_stock WHERE item_id = 9990600 ORDER BY id;
     SELECT CONCAT('stocklog:', previous_qty,'|',current_qty,'|',Qty,'|',type_id) FROM tbl_stock_log WHERE item_id = 9990600 ORDER BY id;
     SELECT CONCAT('outbound:', channel,' ',target,' ',payload) FROM tbl_outbound_stub_log ORDER BY id;" | norm
}

# name plus a shape check: mPDF stamps a creation date, so bytes never match
pdfstate() {
  for f in "$BILLS"/9*.pdf; do
    [ -e "$f" ] || continue
    head -c 5 "$f" | grep -q '%PDF' && kind=pdf || kind=notpdf
    sz=$(wc -c < "$f")
    big=$([ "$sz" -gt 1000 ] && echo yes || echo no)
    echo "pdf:$(basename "$f" | sed -E 's/[0-9]{6,}/<BILL>/')|$kind|nontrivial=$big"
  done
}

run_case() {
  local name="$1" body="$2" items="$3"
  reset; local r1; r1=$(curl -sS --max-time 240 -X POST -H "userlogin: $LOGIN" --data-urlencode "item_details=$items" -d "$body" "$BASE/api/item/punchorder" | norm); local w1; w1=$(written); local p1; p1=$(pdfstate)
  reset; local r2; r2=$(curl -sS --max-time 240 -X POST -H "userlogin: $LOGIN" --data-urlencode "item_details=$items" -d "$body" "$BASE/v2/api/item/punchorder" | norm); local w2; w2=$(written); local p2; p2=$(pdfstate)
  if [ "$r1" = "$r2" ] && [ "$w1" = "$w2" ] && [ "$p1" = "$p2" ]; then
    printf "  %-44s OK  (response, order, stock, outbound, pdf)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1"|head -c 320)"; echo "      resp yii2: $(echo "$r2"|head -c 320)"; }
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1|tr '\n' ' '|head -c 360)"; echo "      rows yii2: $(echo $w2|tr '\n' ' '|head -c 360)"; }
    [ "$p1" != "$p2" ] && { echo "      pdf  yii1: $(echo $p1|tr '\n' ' ')"; echo "      pdf  yii2: $(echo $p2|tr '\n' ' ')"; }
  fi
}

B="mode_of_payment=$MODE_PAY&mode_of_delivery=$MODE_DEL&outlet_id=$OUTLET&customer_id=$CUST&status_id=1"

echo "=== item/punchorder differential  (discount $DISCOUNT) ==="
run_case "one line"                       "$B" '[{"bar_code":"PORTADJBAR600","qty":2}]'
run_case "two lines"                      "$B" '[{"bar_code":"PORTADJBAR600","qty":2},{"bar_code":"PORTADJBAR600","qty":3}]'
run_case "quantity beyond the batch"      "$B" '[{"bar_code":"PORTADJBAR600","qty":80}]'
run_case "held order"                     "mode_of_payment=$MODE_PAY&mode_of_delivery=$MODE_DEL&outlet_id=$OUTLET&customer_id=$CUST&status_id=2" '[{"bar_code":"PORTADJBAR600","qty":2}]'
run_case "percentage discount applied"    "$B&apply_discount=1&discount_id=$DISCOUNT" '[{"bar_code":"PORTADJBAR600","qty":2}]'
run_case "apply_discount without an id"   "$B&apply_discount=1" '[{"bar_code":"PORTADJBAR600","qty":2}]'
run_case "unknown discount id"            "$B&apply_discount=1&discount_id=99999999" '[{"bar_code":"PORTADJBAR600","qty":2}]'
run_case "bar code matches nothing"       "$B" '[{"bar_code":"NOSUCHBARCODE","qty":1}]'
run_case "empty item array"               "$B" '[]'
run_case "item_details is not json"       "$B" 'notjson'
run_case "status_id 3"                    "mode_of_payment=$MODE_PAY&mode_of_delivery=$MODE_DEL&outlet_id=$OUTLET&customer_id=$CUST&status_id=3" '[{"bar_code":"PORTADJBAR600","qty":2}]'
run_case "no mode_of_payment"             "outlet_id=$OUTLET&customer_id=$CUST&status_id=1" '[{"bar_code":"PORTADJBAR600","qty":2}]'

echo -n "  no item_details at all                       "
reset; a=$(curl -sS --max-time 60 -X POST -H "userlogin: $LOGIN" -d "$B" "$BASE/api/item/punchorder" | norm)
reset; b=$(curl -sS --max-time 60 -X POST -H "userlogin: $LOGIN" -d "$B" "$BASE/v2/api/item/punchorder" | norm)
if [ "$a" = "$b" ]; then echo "OK  (${#a} bytes)"; PASS=$((PASS+1)); else echo "MISMATCH"; echo "      $a"; echo "      $b"; FAIL=$((FAIL+1)); fi

reset
echo
echo "  passed: $PASS   mismatched: $FAIL"
