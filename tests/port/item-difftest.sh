#!/bin/bash
# Differential test for the ported item endpoints.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

cmp_case() {
  local name="$1" p1="$2" p2="$3"
  local r1 r2
  r1=$(curl -sS --max-time 240 -X POST "$BASE$p1")
  r2=$(curl -sS --max-time 240 -X POST "$BASE$p2")
  if [ "$r1" = "$r2" ]; then printf "  %-30s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else printf "  %-30s MISMATCH (yii1=%s yii2=%s)\n" "$name" "${#r1}" "${#r2}"; FAIL=$((FAIL+1)); fi
}

echo "=== item API differential (Yii 1 vs Yii 2) ==="
for pg in "" "?page=1" "?page=2" "?page=3" "?page=5" "?page=99999"; do
  cmp_case "list ${pg:-(no page)}" "/api/item/list$pg" "/v2/api/item/list$pg"
done

# real barcodes, taken from the data so the suite stays valid as it changes
CODES=$(docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "SELECT bar_code FROM tbl_item_detail WHERE status=0 AND bar_code IS NOT NULL AND bar_code<>\"\" ORDER BY id LIMIT 4;"' 2>/dev/null | tr '\n' ' ')
for c in $CODES; do
  cmp_case "getItem code=$c" "/api/item/getItem?code=$c" "/v2/api/item/get-item?code=$c"
done
cmp_case "getItem (no code, 50 rows)" "/api/item/getItem" "/v2/api/item/get-item"
cmp_case "getItem (unknown code)"     "/api/item/getItem?code=NOSUCHCODE" "/v2/api/item/get-item?code=NOSUCHCODE"
# the code!price form overrides the sale rate through the whole calculation
for c in $CODES; do
  cmp_case "getItem $c!99.50" "/api/item/getItem?code=$c!99.50" "/v2/api/item/get-item?code=$c!99.50"
done
echo
echo "  passed: $PASS   mismatched: $FAIL"
