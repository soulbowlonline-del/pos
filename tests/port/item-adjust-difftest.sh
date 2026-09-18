#!/bin/bash
# Differential test for item/adjust - a stocktake adjustment.
#
# This one writes more than any other endpoint in the API: the stock row, a
# StockAdjustLog, a StockLog, update_time on both the item and its detail, the
# status of a pending MrsAdjust, and then either deletes pending requisition
# lines or creates a requisition and a line. Everything it touches belongs to
# the fixture (ids 9990600) - its own vendor, item, detail, stock, item-vendor
# link and requisition - so a create or delete cannot reach real data.
#
# The fixture is reloaded before every call. update_time and the random batch
# number are normalised; nothing else is.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
DETAIL=9990600
ITEM=9990600
USER=1

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/adjust_fixture.sql >/dev/null 2>&1
}
q() { docker exec -i pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE -e \"$1\"" 2>/dev/null; }

# batch_number is User::randomBarcode(5) on a new row, and the two stacks run
# seconds apart, so both it and the timestamps are normalised.
norm() { sed -E 's/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/<TS>/g; s/PORTADJBATCH/<BATCH>/g; s/\|[0-9]{5}\|/|<BATCH>|/g'; }

written() {
  q "SELECT CONCAT('stock:', batch_number,'|',purchase_qty,'|',balance_qty,'|',outlet_id,'|',IFNULL(vendor_id,'<N>'))
       FROM tbl_item_stock WHERE item_id = $ITEM ORDER BY id;
     SELECT CONCAT('adjlog:', date,'|',item_detail_id,'|',mrp,'|',IFNULL(outlet_id,'<N>'),'|',current_stock,'|',actual_stock,'|',adjusted,'|',IFNULL(remarks,'<N>'))
       FROM tbl_stock_adjust_log WHERE item_id = $ITEM ORDER BY id;
     SELECT CONCAT('stocklog:', IFNULL(batch_no,'<N>'),'|',previous_qty,'|',current_qty,'|',Qty,'|',type_id)
       FROM tbl_stock_log WHERE item_id = $ITEM ORDER BY id;
     SELECT CONCAT('mrsadjust:', status) FROM tbl_mrs_adjust WHERE item_id = $ITEM ORDER BY id;
     SELECT CONCAT('mrs:', id,'|',vendor_id,'|',status) FROM tbl_mrs WHERE vendor_id = 9990600 ORDER BY id;
     SELECT CONCAT('mrsdetail:', mrs_id,'|',item_id,'|',req_qty,'|',approved_qty,'|',IFNULL(min_qty,'<N>'),'|',IFNULL(amount,'<N>'),'|',IFNULL(margin,'<N>'))
       FROM tbl_mrs_detail WHERE item_id = $ITEM ORDER BY id;" | norm
}

run_case() {
  local name="$1" body="$2" pre="$3"
  reset; [ -n "$pre" ] && q "$pre" >/dev/null
  local r1; r1=$(curl -sS --max-time 120 -X POST -d "$body" "$BASE/api/item/adjust"); local w1; w1=$(written)
  reset; [ -n "$pre" ] && q "$pre" >/dev/null
  local r2; r2=$(curl -sS --max-time 120 -X POST -d "$body" "$BASE/v2/api/item/adjust"); local w2; w2=$(written)
  if [ "$r1" = "$r2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-46s OK  (response, stock, logs, mrs)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-46s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1"|head -c 230)"; echo "      resp yii2: $(echo "$r2"|head -c 230)"; }
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1|tr '\n' ' '|head -c 400)"; echo "      rows yii2: $(echo $w2|tr '\n' ' '|head -c 400)"; }
  fi
}

run_error_case() {
  local name="$1" body="$2" pre="$3"
  reset; [ -n "$pre" ] && q "$pre" >/dev/null
  local c1; c1=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST -d "$body" "$BASE/api/item/adjust"); local w1; w1=$(written)
  reset; [ -n "$pre" ] && q "$pre" >/dev/null
  local c2; c2=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST -d "$body" "$BASE/v2/api/item/adjust"); local w2; w2=$(written)
  if [ "$c1" = "$c2" ] && [ "$w1" = "$w2" ]; then
    printf "  %-46s OK  (status %s, rows)\n" "$name" "$c1"; PASS=$((PASS+1))
  else
    printf "  %-46s MISMATCH (%s vs %s)\n" "$name" "$c1" "$c2"; FAIL=$((FAIL+1))
    [ "$w1" != "$w2" ] && { echo "      rows yii1: $(echo $w1|tr '\n' ' '|head -c 300)"; echo "      rows yii2: $(echo $w2|tr '\n' ' '|head -c 300)"; }
  fi
}

B="itemdetail_id=$DETAIL&original_item_id=$ITEM&user_id=$USER"

echo "=== item/adjust differential ==="
# stock is 50, min_qty 20. counted above the book figure adds, below subtracts.
run_case "counted 60 against 50 - adds"        "$B&qty=60&remain_qty=50&remark=count%20up"
run_case "counted 40 against 50 - subtracts"   "$B&qty=40&remain_qty=50&remark=count%20down"
run_case "counted the same - no movement"      "$B&qty=50&remain_qty=50&remark=same"
run_case "counted down below min_qty"          "$B&qty=5&remain_qty=50&remark=below%20reorder"
run_case "counted to zero"                     "$B&qty=0&remain_qty=50&remark=zero"
run_case "counted down to exactly min_qty"     "$B&qty=20&remain_qty=50&remark=at%20min"
run_case "empty remark becomes a space"        "$B&qty=55&remain_qty=50&remark="
run_case "unknown item detail"                 "itemdetail_id=99999999&original_item_id=$ITEM&user_id=$USER&qty=1&remain_qty=1&remark=x"
run_case "missing user_id"                     "itemdetail_id=$DETAIL&original_item_id=$ITEM&qty=1&remain_qty=1&remark=x"
run_case "missing qty"                         "itemdetail_id=$DETAIL&original_item_id=$ITEM&user_id=$USER&remain_qty=1&remark=x"
run_case "blank itemdetail_id"                 "itemdetail_id=&original_item_id=$ITEM&user_id=$USER&qty=1&remain_qty=1&remark=x"

# The requisition-creation branch only runs when the vendor's requisition has
# no line for this item yet - an existing line short-circuits it - so these two
# remove the fixture line first. Without them the branch is never entered and
# the cases above prove nothing about it.
run_case "below min_qty, no existing MRS line"  "$B&qty=5&remain_qty=50&remark=raise" \
         "DELETE FROM tbl_mrs_detail WHERE item_id = $ITEM;"
run_case "above min_qty, no existing MRS line"  "$B&qty=60&remain_qty=50&remark=nochange" \
         "DELETE FROM tbl_mrs_detail WHERE item_id = $ITEM;"
# $vendorMRS->id with no requisition for that vendor: a fatal on both stacks,
# each rendered by its own framework, so status and rows rather than the body.
run_error_case "below min_qty, vendor has no MRS" "$B&qty=5&remain_qty=50&remark=novendormrs" \
         "DELETE FROM tbl_mrs_detail WHERE item_id = $ITEM; DELETE FROM tbl_mrs WHERE vendor_id = 9990600;"

# the two null-property paths, which are a fatal on both stacks
run_error_case "no stock row at the outlet"    "$B&qty=60&remain_qty=50&remark=x" \
               "DELETE FROM tbl_item_stock WHERE item_id = $ITEM;"
run_error_case "item has no vendor row"        "$B&qty=60&remain_qty=50&remark=x" \
               "DELETE FROM tbl_item_vendor WHERE item_detail_id = $ITEM;"

reset
echo
echo "  passed: $PASS   mismatched: $FAIL"
