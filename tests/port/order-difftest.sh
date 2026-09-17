#!/bin/bash
# Differential test for the ported order API read paths.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

run_case() {
  local name="$1" a1="$2" a2="$3" data="$4" q="$5"
  local r1 r2
  r1=$(curl -sS --max-time 240 -X POST -d "$data" "$BASE/api/order/$a1$q")
  r2=$(curl -sS --max-time 240 -X POST -d "$data" "$BASE/v2/api/order/$a2$q")
  if [ "$r1" = "$r2" ]; then
    printf "  %-40s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-40s MISMATCH (yii1=%s yii2=%s bytes)\n" "$name" "${#r1}" "${#r2}"; FAIL=$((FAIL+1))
    echo "      yii1: $(echo "$r1" | head -c 300)"
    echo "      yii2: $(echo "$r2" | head -c 300)"
  fi
}

echo "=== order API differential (Yii 1 vs Yii 2) ==="
run_case "modes (default type 0)"          modes        modes          ""  ""
run_case "modes (type=1)"                  modes        modes          ""  "?type=1"
run_case "modes (type with no rows)"       modes        modes          ""  "?type=987"
run_case "getLastOrder"                    getLastOrder get-last-order ""  ""
for _oid in 1581602 1581598 1581597 1581594 1581593 1581592 99999999; do
  run_case "get (order $_oid)" get get "" "?id=$_oid"
done
run_case "list (no data posted)"           list         list           "x=1" ""
run_case "list (1 day)"                    list         list           "start_date=2026-09-15&end_date=2026-09-15" ""
run_case "list (3 days)"                   list         list           "start_date=2026-09-14&end_date=2026-09-16" ""
run_case "list (range with no orders)"     list         list           "start_date=1990-01-01&end_date=1990-01-02" ""

# description endpoints (POST body rather than query string)
desc_case() {
  local name="$1" a1="$2" a2="$3" data="$4"
  local r1 r2
  r1=$(curl -sS --max-time 240 -X POST -d "$data" "$BASE/api/order/$a1")
  r2=$(curl -sS --max-time 240 -X POST -d "$data" "$BASE/v2/api/order/$a2")
  if [ "$r1" = "$r2" ]; then printf "  %-40s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else printf "  %-40s MISMATCH (yii1=%s yii2=%s)\n" "$name" "${#r1}" "${#r2}"; FAIL=$((FAIL+1)); fi
}
for b in 96732 B-96732 96731 ZZZ ""; do
  desc_case "descByBillId ${b:-(empty)}" getDescriptionByBillId get-description-by-bill-id "bill_id=$b"
done
GRNS=$(docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "SELECT id FROM tbl_purchase_bill ORDER BY id DESC LIMIT 4;"' 2>/dev/null | tr '\n' ' ')
for g in $GRNS 99999999 ""; do
  desc_case "descByGrn ${g:-(empty)}" getDescriptionByGrn get-description-by-grn "grn_number=$g"
done
echo
echo "  passed: $PASS   mismatched: $FAIL"
