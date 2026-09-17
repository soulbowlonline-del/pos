#!/bin/bash
# Differential test for customer/verifywhatappotp and customer/getOrderHold.
# Both mutate - the first marks a code used, the second deletes the held order -
# so the fixture is rebuilt before each side of every comparison.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/setup_test.sql >/dev/null 2>&1
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/cust_last_fixture.sql >/dev/null 2>&1
}
state() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(otp_code,\"|\",is_verified) FROM tbl_customer_otp_verification WHERE customer_id=9990001 ORDER BY id;
    SELECT CONCAT(\"wa:\",is_enable_wa) FROM tbl_customer WHERE id=9990001;
    SELECT CONCAT(\"holds:\",COUNT(*)) FROM tbl_order_hold WHERE id=9990002;"' 2>/dev/null
}

run_case() {
  local name="$1" a1="$2" a2="$3" q="$4"
  reset; local r1; r1=$(curl -sS --max-time 120 -X POST "$BASE/api/customer/$a1$q"); local s1; s1=$(state)
  reset; local r2; r2=$(curl -sS --max-time 120 -X POST "$BASE/v2/api/customer/$a2$q"); local s2; s2=$(state)
  if [ "$r1" = "$r2" ] && [ "$s1" = "$s2" ]; then
    printf "  %-38s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-38s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      yii1: $(echo "$r1" | head -c 220)"; echo "      yii2: $(echo "$r2" | head -c 220)"; }
    [ "$s1" != "$s2" ] && { echo "      yii1 state: $(echo $s1|tr '\n' ' ')"; echo "      yii2 state: $(echo $s2|tr '\n' ' ')"; }
  fi
}

echo "=== verifywhatappotp / getOrderHold differential ==="
run_case "verifywhatappotp valid"      verifywhatappotp verifywhatappotp "?id=9990001&otp=654321"
run_case "verifywhatappotp wrong code" verifywhatappotp verifywhatappotp "?id=9990001&otp=000000"
run_case "verifywhatappotp expired"    verifywhatappotp verifywhatappotp "?id=9990001&otp=111111"
run_case "verifywhatappotp no user"    verifywhatappotp verifywhatappotp "?id=98765432&otp=654321"
run_case "getOrderHold (consumes it)"  getOrderHold     get-order-hold   "?id=9990002"
run_case "getOrderHold unknown"        getOrderHold     get-order-hold   "?id=98765432"
echo
echo "  passed: $PASS   mismatched: $FAIL"
