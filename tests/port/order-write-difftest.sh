#!/bin/bash
# Differential test for the online-order write paths and order/reprint.
#
# cancelOrder, assignOrder and orderUpdate all change a row, so the fixture is
# reloaded before each call and the row is compared afterwards as well as the
# response. The row comparison is the point: BaseOnlineOrder has a `default`
# rule that rewrites empty attributes to NULL on save, so saving one column
# quietly rewrites several others, and only a row-level check catches that.
#
# reprint reads a real POS order and writes nothing.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
REAL_ORDER=1581602

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/online_fixture.sql >/dev/null 2>&1
  docker exec pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE -e "DELETE FROM tbl_outbound_stub_log;"' >/dev/null 2>&1
}

# every mutable column of the fixture rows, so a stray rewrite shows up
rowstate() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT_WS(\"|\", id, order_id, IFNULL(first_name,\"<N>\"), IFNULL(last_name,\"<N>\"),
           IFNULL(street,\"<N>\"), IFNULL(city,\"<N>\"), IFNULL(telephone,\"<N>\"),
           IFNULL(zip_code,\"<N>\"), IFNULL(country,\"<N>\"), IFNULL(delivery_slot,\"<N>\"),
           IFNULL(ship_name,\"<N>\"), IFNULL(item_count,\"<N>\"), IFNULL(grand_total,\"<N>\"),
           IFNULL(type_id,\"<N>\"), IFNULL(status,\"<N>\"), IFNULL(order_status,\"<N>\"),
           IFNULL(is_shipped,\"<N>\"), IFNULL(picker_id,\"<N>\"), IFNULL(delivery_boy_id,\"<N>\"),
           IFNULL(create_user_id,\"<N>\"), IFNULL(updated_by,\"<N>\"))
      FROM tbl_online_order WHERE id IN (9990100, 9990101) ORDER BY id;"' 2>/dev/null
}

outbound() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(channel,\" |\",target,\" |\",payload) FROM tbl_outbound_stub_log ORDER BY id;"' 2>/dev/null \
    | sed -E 's/stub-[0-9a-f]+/stub-<FP>/g'
}

run_case() {
  local name="$1" y1="$2" y2="$3" login="$4" body="$5"
  reset
  local r1; r1=$(curl -sS --max-time 300 -X POST ${login:+-H "userlogin: $login"} ${body:+-d "$body"} "$BASE/api/order/$y1")
  local s1; s1=$(rowstate); local o1; o1=$(outbound)
  reset
  local r2; r2=$(curl -sS --max-time 300 -X POST ${login:+-H "userlogin: $login"} ${body:+-d "$body"} "$BASE/v2/api/order/$y2")
  local s2; s2=$(rowstate); local o2; o2=$(outbound)

  if [ "$r1" = "$r2" ] && [ "$s1" = "$s2" ] && [ "$o1" = "$o2" ]; then
    printf "  %-44s OK  (response, row, outbound)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1" | head -c 240)"; echo "      resp yii2: $(echo "$r2" | head -c 240)"; }
    [ "$s1" != "$s2" ] && { echo "      row  yii1: $(echo $s1 | tr '\n' ' ')"; echo "      row  yii2: $(echo $s2 | tr '\n' ' ')"; }
    [ "$o1" != "$o2" ] && { echo "      out  yii1: $(echo "$o1" | head -c 240)"; echo "      out  yii2: $(echo "$o2" | head -c 240)"; }
  fi
}

# The id on reprint has no default on either stack, so a request without one is
# a framework-rendered error: same status, different body. Compare the status
# and the rows instead.
run_error_case() {
  local name="$1" y1="$2" y2="$3"
  reset; local c1; c1=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST -H "userlogin: 1" "$BASE/api/order/$y1")
  local s1; s1=$(rowstate)
  reset; local c2; c2=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 -X POST -H "userlogin: 1" "$BASE/v2/api/order/$y2")
  local s2; s2=$(rowstate)
  if [ "$c1" = "$c2" ] && [ "$s1" = "$s2" ]; then
    printf "  %-44s OK  (status %s, row)\n" "$name" "$c1"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH  (%s vs %s)\n" "$name" "$c1" "$c2"; FAIL=$((FAIL+1))
  fi
}

echo "=== order write paths + reprint differential ==="

run_case "cancelOrder, fixture order"   "cancelOrder?id=9990100"   "cancel-order?id=9990100"   1 ""
run_case "cancelOrder, already shipped" "cancelOrder?id=9990101"   "cancel-order?id=9990101"   1 ""
run_case "cancelOrder, unknown id"      "cancelOrder?id=99999999"  "cancel-order?id=99999999"  1 ""
run_case "cancelOrder, no id"           "cancelOrder"              "cancel-order"              1 ""
run_case "cancelOrder, no login"        "cancelOrder?id=9990100"   "cancel-order?id=9990100"   "" ""

run_case "assignOrder, picker only"     "assignOrder?id=9990100"   "assign-order?id=9990100"   1 "picker_id=9990002"
run_case "assignOrder, rider only"      "assignOrder?id=9990100"   "assign-order?id=9990100"   1 "delivery_boy_id=9990002"
run_case "assignOrder, both"            "assignOrder?id=9990100"   "assign-order?id=9990100"   1 "picker_id=9990002&delivery_boy_id=9990002"
run_case "assignOrder, empty values"    "assignOrder?id=9990100"   "assign-order?id=9990100"   1 "picker_id=&delivery_boy_id="
run_case "assignOrder, neither posted"  "assignOrder?id=9990100"   "assign-order?id=9990100"   1 ""
run_case "assignOrder, unknown id"      "assignOrder?id=99999999"  "assign-order?id=99999999"  1 ""
run_case "assignOrder, no login"        "assignOrder?id=9990100"   "assign-order?id=9990100"   "" "picker_id=9990002"

run_case "orderUpdate, fixture order"   "orderUpdate?id=9990100"   "order-update?id=9990100"   1 ""
run_case "orderUpdate, unknown id"      "orderUpdate?id=99999999"  "order-update?id=99999999"  1 ""
run_case "orderUpdate, no id"           "orderUpdate"              "order-update"              1 ""
run_case "orderUpdate, no login"        "orderUpdate?id=9990100"   "order-update?id=9990100"   "" ""

# shipOrder and completeOrder both call the webshop's dispatch endpoint and
# push to devices; with POS_STUB_OUTBOUND=1 those are recorded, and the
# recordings are compared along with the row.
run_case "shipOrder, fixture order"     "shipOrder?id=9990100"     "ship-order?id=9990100"     1 ""
run_case "shipOrder, already shipped"   "shipOrder?id=9990101"     "ship-order?id=9990101"     1 ""
run_case "shipOrder, unknown id"        "shipOrder?id=99999999"    "ship-order?id=99999999"    1 ""
run_case "shipOrder, no login"          "shipOrder?id=9990100"     "ship-order?id=9990100"     "" ""

run_case "completeOrder, by its rider"  "completeOrder?id=9990100" "complete-order?id=9990100" 9990002 ""
run_case "completeOrder, wrong caller"  "completeOrder?id=9990100" "complete-order?id=9990100" 1 ""
run_case "completeOrder, shipped order" "completeOrder?id=9990101" "complete-order?id=9990101" 9990002 ""
run_case "completeOrder, unknown id"    "completeOrder?id=99999999" "complete-order?id=99999999" 9990002 ""
run_case "completeOrder, no login"      "completeOrder?id=9990100" "complete-order?id=9990100" "" ""

run_case "reprint, real order"          "reprint?id=$REAL_ORDER"   "reprint?id=$REAL_ORDER"    1 ""
run_case "reprint, fixture order"       "reprint?id=9990001"       "reprint?id=9990001"        1 ""
run_case "reprint, unknown id"          "reprint?id=99999999"      "reprint?id=99999999"       1 ""
run_error_case "reprint, no id (required both sides)" "reprint" "reprint"

echo
echo "  passed: $PASS   mismatched: $FAIL"
