#!/bin/bash
# Differential test for the three online-order read actions:
# order/online, order/getOnlineOrder and order/getAssignList.
#
# These run against the live demo data rather than a fixture - there are 7,908
# online orders and 89,113 lines, which is far better coverage than anything
# worth building by hand, and none of the three writes.
#
# Yii 1 action ids are camelCase; the Yii 2 routes are hyphenated. Both take
# their parameters from the query string.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

run_case() {
  local name="$1" y1="$2" y2="$3" login="${4:-1}" body="$5"
  local r1; r1=$(curl -sS --max-time 600 -X POST -H "userlogin: $login" ${body:+-d "$body"} "$BASE/api/order/$y1")
  local r2; r2=$(curl -sS --max-time 600 -X POST -H "userlogin: $login" ${body:+-d "$body"} "$BASE/v2/api/order/$y2")
  if [ "$r1" = "$r2" ]; then
    printf "  %-46s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-46s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    echo "      yii1: $(echo "$r1" | head -c 220)"
    echo "      yii2: $(echo "$r2" | head -c 220)"
  fi
}

echo "=== order/online + getOnlineOrder + getAssignList differential ==="

# --- online: the status filter. 2 means "packed or shipped", not "status 2".
run_case "online, default (pending)"          "online"                      "online"
run_case "online, status=1 packed"            "online?status=1"             "online?status=1"
run_case "online, status=2 packed or shipped" "online?status=2"             "online?status=2"
# status 3 and 4 are the big buckets - 3,954 and 954 orders, and every row
# costs several queries on both stacks. Windowed to a month: the code path is
# the same and the whole-table versions took longer than the rest of the
# regression put together. The unwindowed status=2 case above still covers a
# few hundred rows end to end.
run_case "online, status=3 completed"         "online?status=3"             "online?status=3" 1 "start_date=2023-01-01&end_date=2023-01-31"
run_case "online, status=4 cancelled"         "online?status=4"             "online?status=4" 1 "start_date=2023-01-01&end_date=2023-01-31"
run_case "online, status=0 falls back"        "online?status=0"             "online?status=0"
run_case "online, narrow date window"          "online" "online" 1 "start_date=2023-01-01&end_date=2023-01-31"
run_case "online, window with no orders"       "online" "online" 1 "start_date=1990-01-01&end_date=1990-01-02"
run_case "online, unparseable dates"           "online" "online" 1 "start_date=notadate&end_date=alsonot"
run_case "online, only start_date (ignored)"   "online" "online" 1 "start_date=2023-01-01"
run_case "online, end before start"            "online" "online" 1 "start_date=2023-06-01&end_date=2023-01-01"
run_case "online, date window + status=3"      "online?status=3" "online?status=3" 1 "start_date=2023-01-01&end_date=2023-01-31"

# --- getOnlineOrder: the item payload. ids 1-3 have no POS order, so they
# render through ItemDetail::toOnlineOrderArray; 6197 has a NULL delivery
# method, which is the compare() case.
for id in 1 2 3 100 4837 6197 7514; do
  run_case "getOnlineOrder id=$id" "getOnlineOrder?id=$id" "get-online-order?id=$id"
done
run_case "getOnlineOrder, unknown id"         "getOnlineOrder?id=99999999"  "get-online-order?id=99999999"
run_case "getOnlineOrder, no id at all"       "getOnlineOrder"              "get-online-order"

# --- getAssignList: rider 552 has 4,407 orders, 556 has 35, 553 has 5.
run_case "getAssignList, rider with 30 open"  "getAssignList"               "get-assign-list"               556
run_case "getAssignList, status=3 completed"  "getAssignList?status=3"      "get-assign-list?status=3"      556
run_case "getAssignList, small rider"         "getAssignList"               "get-assign-list"               553
run_case "getAssignList, rider with none"     "getAssignList"               "get-assign-list"               341
run_case "getAssignList, unknown rider"       "getAssignList"               "get-assign-list"               99999
run_case "getAssignList, narrow window"        "getAssignList" "get-assign-list" 556 "start_date=2023-01-01&end_date=2023-12-31"
run_case "getAssignList, window with none"     "getAssignList" "get-assign-list" 556 "start_date=1990-01-01&end_date=1990-01-02"

# order/online used to overwrite the caller id with '1' and so answered
# regardless; it honours the header now, on both stacks.
echo -n "  online, no login header                      "
o1=$(curl -sS --max-time 60 -X POST "$BASE/api/order/online")
o2=$(curl -sS --max-time 60 -X POST "$BASE/v2/api/order/online")
if [ "$o1" = "$o2" ]; then echo "OK  (${#o1} bytes)"; PASS=$((PASS+1)); else echo "MISMATCH"; echo "      yii1: $o1"; echo "      yii2: $o2"; FAIL=$((FAIL+1)); fi

# getOnlineOrder had the same overwrite online() did; it honours the header
# now too, on both stacks.
echo -n "  getOnlineOrder, no login header              "
g1=$(curl -sS --max-time 60 -X POST "$BASE/api/order/getOnlineOrder?id=1")
g2=$(curl -sS --max-time 60 -X POST "$BASE/v2/api/order/get-online-order?id=1")
if [ "$g1" = "$g2" ]; then echo "OK  (${#g1} bytes)"; PASS=$((PASS+1)); else echo "MISMATCH"; echo "      yii1: $g1"; echo "      yii2: $g2"; FAIL=$((FAIL+1)); fi

# no login header at all

echo -n "  getAssignList, no login header               "
r1=$(curl -sS --max-time 60 -X POST "$BASE/api/order/getAssignList")
r2=$(curl -sS --max-time 60 -X POST "$BASE/v2/api/order/get-assign-list")
if [ "$r1" = "$r2" ]; then echo "OK  (${#r1} bytes)"; PASS=$((PASS+1)); else echo "MISMATCH"; echo "      yii1: $r1"; echo "      yii2: $r2"; FAIL=$((FAIL+1)); fi

echo
echo "  passed: $PASS   mismatched: $FAIL"
