#!/bin/bash
# Differential test for item/barcode. Read-only, so it runs against the live
# data: bar codes are picked from the database, including ones that appear on
# more than one item detail and ones whose item has a vendor or a stock
# adjustment, so the [0] picks in the payload are actually exercised.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

pick() { docker exec -i pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE -e \"$1\"" 2>/dev/null | head -1; }

run_case() {
  local name="$1" bc="$2"
  local r1; r1=$(curl -sS --max-time 120 -X POST -d "barcode=$bc" "$BASE/api/item/barcode")
  local r2; r2=$(curl -sS --max-time 120 -X POST -d "barcode=$bc" "$BASE/v2/api/item/barcode")
  if [ "$r1" = "$r2" ]; then
    printf "  %-44s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    echo "      yii1: $(echo "$r1" | head -c 260)"; echo "      yii2: $(echo "$r2" | head -c 260)"
  fi
}

PLAIN=$(pick "SELECT bar_code FROM tbl_item_detail WHERE bar_code <> '' ORDER BY id LIMIT 1;")
DUP=$(pick "SELECT bar_code FROM tbl_item_detail WHERE bar_code <> '' GROUP BY bar_code HAVING COUNT(*) > 1 ORDER BY bar_code LIMIT 1;")
WITHADJ=$(pick "SELECT d.bar_code FROM tbl_item_detail d JOIN tbl_stock_adjust_log l ON l.item_detail_id = d.id WHERE d.bar_code <> '' ORDER BY d.id LIMIT 1;")
WITHVEN=$(pick "SELECT d.bar_code FROM tbl_item_detail d JOIN tbl_item_vendor v ON v.item_detail_id = d.item_id WHERE d.bar_code <> '' ORDER BY d.id LIMIT 1;")
MANYADJ=$(pick "SELECT d.bar_code FROM tbl_item_detail d JOIN tbl_stock_adjust_log l ON l.item_detail_id = d.id WHERE d.bar_code <> '' GROUP BY d.id HAVING COUNT(*) > 2 ORDER BY COUNT(*) DESC LIMIT 1;")

echo "=== item/barcode differential ==="
run_case "first bar code"                       "$PLAIN"
[ -n "$DUP" ]     && run_case "bar code on more than one detail"  "$DUP"
[ -n "$WITHADJ" ] && run_case "detail with a stock adjustment"    "$WITHADJ"
[ -n "$MANYADJ" ] && run_case "detail with several adjustments"   "$MANYADJ"
[ -n "$WITHVEN" ] && run_case "item with a vendor"                "$WITHVEN"
run_case "unknown bar code"                     "NOSUCHBARCODE999"
run_case "empty bar code"                       ""

echo -n "  no barcode field at all                      "
a=$(curl -sS --max-time 60 -X POST "$BASE/api/item/barcode")
b=$(curl -sS --max-time 60 -X POST "$BASE/v2/api/item/barcode")
if [ "$a" = "$b" ]; then echo "OK  (${#a} bytes)"; PASS=$((PASS+1)); else echo "MISMATCH"; echo "      $a"; echo "      $b"; FAIL=$((FAIL+1)); fi

echo
echo "  passed: $PASS   mismatched: $FAIL"
