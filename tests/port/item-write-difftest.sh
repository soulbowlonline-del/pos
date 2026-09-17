#!/bin/bash
# Differential test for item/billUpdate, item/adjustitemtozero and
# item/scannedItem - the three write actions of ItemController that do not
# touch the order or stock-movement paths.
#
# All three write, so each case snapshots what they wrote and the rows are
# removed again afterwards. billUpdate flips purchase bill 97 (a literal id in
# the source); its original status is saved and restored at the end.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
DETAIL_ID=4
USER_ID=9990002

sql() { docker exec pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD \$MYSQL_DATABASE -e \"$1\"" >/dev/null 2>&1; }
q()   { docker exec pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE -e \"$1\"" 2>/dev/null; }

save_bill97()    { sql "CREATE TABLE IF NOT EXISTS tbl_pb97_bak AS SELECT id, status FROM tbl_purchase_bill WHERE id = 97;"; }
restore_bill97() { sql "UPDATE tbl_purchase_bill p JOIN tbl_pb97_bak b ON b.id = p.id SET p.status = b.status; DROP TABLE IF EXISTS tbl_pb97_bak;"; }

# rows these actions add, with the ids normalised away
reset() {
  sql "DELETE FROM tbl_stock_adjust_log WHERE create_user_id = $USER_ID;
       DELETE FROM tbl_scanned_items    WHERE user_id = $USER_ID;
       UPDATE tbl_purchase_bill SET status = 1 WHERE id = 97;"
}
written() {
  q "SELECT CONCAT('adj:', date,'|',item_detail_id,'|',item_id,'|',mrp,'|',IFNULL(outlet_id,'<N>'),'|',
            current_stock,'|',actual_stock,'|',adjusted,'|',IFNULL(remarks,'<N>'))
       FROM tbl_stock_adjust_log WHERE create_user_id = $USER_ID ORDER BY id;
     SELECT CONCAT('scan:', computer_name,'|',user_email,'|',item_id,'|',bar_code,'|',IFNULL(is_coupon,'<N>'),'|',
            qty,'|',sale_rate,'|',base_price,'|',mrp,'|',item_detail,'|',created_at)
       FROM tbl_scanned_items WHERE user_id = $USER_ID ORDER BY id;
     SELECT CONCAT('bill97:', status) FROM tbl_purchase_bill WHERE id = 97;"
}

run_case() {
  local name="$1" y1="$2" y2="$3" body="$4"
  reset; local r1; r1=$(curl -sS --max-time 300 -X POST ${body:+-d "$body"} "$BASE/api/item/$y1"); local w1; w1=$(written)
  reset; local r2; r2=$(curl -sS --max-time 300 -X POST ${body:+-d "$body"} "$BASE/v2/api/item/$y2"); local w2; w2=$(written)
  if [ "$r1" = "$r2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-46s OK  (response, rows written)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-46s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1" | head -c 240)"; echo "      resp yii2: $(echo "$r2" | head -c 240)"; }
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1 | tr '\n' ' ' | head -c 300)"; echo "      rows yii2: $(echo $w2 | tr '\n' ' ' | head -c 300)"; }
  fi
}

# Yii 1 reads computer_name and user_id without checking them, so a request
# missing either is a warning and a 500 on both stacks - each rendered by its
# own framework. Compare the status and what was written instead of the body.
run_error_case() {
  local name="$1" y1="$2" y2="$3" body="$4"
  reset; local c1; c1=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST ${body:+-d "$body"} "$BASE/api/item/$y1"); local w1; w1=$(written)
  reset; local c2; c2=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST ${body:+-d "$body"} "$BASE/v2/api/item/$y2"); local w2; w2=$(written)
  if [ "$c1" = "$c2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-46s OK  (status %s, rows written)\n" "$name" "$c1"; PASS=$((PASS+1))
  else
    printf "  %-46s MISMATCH  (%s vs %s)\n" "$name" "$c1" "$c2"; FAIL=$((FAIL+1))
  fi
}

echo "=== item billUpdate + adjustitemtozero + scannedItem differential ==="
save_bill97
trap 'restore_bill97; reset' EXIT

run_case "billUpdate (flips purchase bill 97)" "billUpdate" "bill-update" ""

run_case "adjustitemtozero, valid detail"   "adjustitemtozero" "adjustitemtozero" "itemdetail_id=$DETAIL_ID&user_id=$USER_ID"
run_case "adjustitemtozero, unknown detail" "adjustitemtozero" "adjustitemtozero" "itemdetail_id=99999999&user_id=$USER_ID"
run_case "adjustitemtozero, no user_id"     "adjustitemtozero" "adjustitemtozero" "itemdetail_id=$DETAIL_ID"
run_case "adjustitemtozero, no params"      "adjustitemtozero" "adjustitemtozero" ""

ITEMS='[{"item_id":1,"bar_code":"8901058856705","is_coupon":0,"qty":2,"sale_rate":10.50,"base_price":9.00,"mrp":12.00}]'
TWO='[{"item_id":1,"bar_code":"A1","is_coupon":0,"qty":1,"sale_rate":1,"base_price":1,"mrp":1},{"item_id":2,"bar_code":"A2","is_coupon":1,"qty":3,"sale_rate":2,"base_price":2,"mrp":2}]'
run_case "scannedItem, one item"            "scannedItem" "scanned-item" "items=$ITEMS&computer_name=till-01&user_id=$USER_ID&user_email=t@example.invalid&created_at=2026-09-17 12:00:00"
run_case "scannedItem, two items"           "scannedItem" "scanned-item" "items=$TWO&computer_name=till-02&user_id=$USER_ID&user_email=t@example.invalid&created_at=2026-09-17 12:00:00"
run_case "scannedItem, empty array"         "scannedItem" "scanned-item" "items=[]&computer_name=till-01&user_id=$USER_ID&user_email=t@example.invalid&created_at=2026-09-17 12:00:00"
run_case "scannedItem, no items"            "scannedItem" "scanned-item" "computer_name=till-01&user_id=$USER_ID"
run_error_case "scannedItem, no computer_name"  "scannedItem" "scanned-item" "items=$ITEMS&user_id=$USER_ID"
run_error_case "scannedItem, bad json"          "scannedItem" "scanned-item" "items=notjson&computer_name=till-01&user_id=$USER_ID"
run_case "scannedItem, unparseable created_at" "scannedItem" "scanned-item" "items=$ITEMS&computer_name=till-01&user_id=$USER_ID&user_email=t@example.invalid&created_at=notadate"

echo
echo "  passed: $PASS   mismatched: $FAIL"
