#!/bin/bash
# Differential test for the ported customer API read paths.
#
# Note: index and get render Customer::toArray*(), which resolves the loyalty
# row via getOrCreate() and therefore INSERTS a zeroed row for any customer
# without one. Running this suite mutates the database the same way the live
# endpoint does. Yii 1 is called first so both frameworks see the same rows.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

run_case() {
  local name="$1" a1="$2" a2="$3" q="$4"
  local r1 r2
  r1=$(curl -sS --max-time 180 -X POST "$BASE/api/customer/$a1$q")
  r2=$(curl -sS --max-time 180 -X POST "$BASE/v2/api/customer/$a2$q")
  if [ "$r1" = "$r2" ]; then
    printf "  %-38s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-38s MISMATCH  (yii1=%s yii2=%s bytes)\n" "$name" "${#r1}" "${#r2}"; FAIL=$((FAIL+1))
    echo "      yii1: $(echo "$r1" | head -c 260)"
    echo "      yii2: $(echo "$r2" | head -c 260)"
  fi
}

echo "=== customer API differential (Yii 1 vs Yii 2) ==="
run_case "setting"                      setting     setting      ""
run_case "discounts"                    discounts   discounts    ""
run_case "countryList"                  countryList country-list ""
run_case "stateList (all)"              stateList   state-list   ""
run_case "stateList (country_id=1)"     stateList   state-list   "?id=1"
run_case "stateList (no matches)"       stateList   state-list   "?id=99999"
run_case "cityList (all)"               cityList    city-list    ""
run_case "cityList (state_id=1)"        cityList    city-list    "?id=1"
run_case "cityList (no matches)"        cityList    city-list    "?id=99999"
run_case "get (customer 1)"             get         get          "?id=1"
run_case "get (nonexistent)"            get         get          "?id=99999999"
run_case "index (all customers)"        index       index        ""
# outlet 0 has 322 orders; outlet 5 has 1.58M and neither stack can serve it
# (Yii 1 exhausts memory, Yii 2 times out) - see the note in the commit.
run_case "holdOrderList (orders, outlet 0)"  holdOrderList hold-order-list "?id=0&status=1"
run_case "holdOrderList (held, outlet 0)"    holdOrderList hold-order-list "?id=0&status=2"
run_case "holdOrderList (empty outlet)"      holdOrderList hold-order-list "?id=99999&status=1"
run_case "holdOrderList (bad status)"        holdOrderList hold-order-list "?id=0&status=9"
run_case "orderList (orders, outlet 0)"     orderList     order-list      "?id=0&status=1"
run_case "orderList (held, outlet 0)"       orderList     order-list      "?id=0&status=2"
run_case "orderList (empty outlet)"         orderList     order-list      "?id=99999&status=1"
run_case "getLatestBill (outlet 0)"         GetLatestBill get-latest-bill "?id=0"
run_case "getLatestBill (outlet 5)"         GetLatestBill get-latest-bill "?id=5"
run_case "getLatestBill (no such outlet)"   GetLatestBill get-latest-bill "?id=99999"
for _oid in 1581602 1581598 1581597 1581594 1581593 1581592 99999999; do
  run_case "getOrder ($_oid)" getOrder get-order "?id=$_oid"
done
echo
echo "  passed: $PASS   mismatched: $FAIL"
