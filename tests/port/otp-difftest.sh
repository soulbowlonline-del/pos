#!/bin/bash
# Differential test for customer/verifyOTP. Verification marks the OTP used, so
# the fixture is rebuilt before each side of every comparison.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

reset() { docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/otp_fixture.sql >/dev/null 2>&1; }
state() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(otp_code,\"|\",attempts,\"|\",is_verified) FROM tbl_customer_otp WHERE customer_id=9990001 ORDER BY id;
    SELECT CONCAT(\"wa:\",is_enable_wa) FROM tbl_customer WHERE id=9990001;"' 2>/dev/null
}

run_case() {
  local name="$1" data="$2"
  # verified_at is stamped at the moment of the call, so the two runs differ by
  # a second or so. Normalised rather than compared.
  norm() { sed -E 's/"verified_at":"[^"]*"/"verified_at":"<TS>"/g'; }
  # Warm-up: the profile in the response embeds the loyalty row, which is
  # created on first read. Touch it first so both sides read it persisted.
  reset; curl -sS --max-time 120 -X POST -d "customer_id=9990001" "$BASE/api/customer/get?id=9990001" >/dev/null 2>&1
  local r1; r1=$(curl -sS --max-time 120 -X POST -d "$data" "$BASE/api/customer/verifyOTP" | norm); local s1; s1=$(state)
  reset; local r2; r2=$(curl -sS --max-time 120 -X POST -d "$data" "$BASE/v2/api/customer/verify-otp" | norm); local s2; s2=$(state)
  if [ "$r1" = "$r2" ] && [ "$s1" = "$s2" ]; then
    printf "  %-34s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-34s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      yii1: $(echo "$r1" | head -c 240)"; echo "      yii2: $(echo "$r2" | head -c 240)"; }
    [ "$s1" != "$s2" ] && { echo "      yii1 state: $(echo $s1|tr '\n' ' ')"; echo "      yii2 state: $(echo $s2|tr '\n' ' ')"; }
  fi
}

echo "=== customer/verifyOTP differential ==="
run_case "valid code"                 "customer_id=9990001&otp_code=123456"
run_case "wrong code"                 "customer_id=9990001&otp_code=000000"
run_case "expired code"               "customer_id=9990001&otp_code=999999"
run_case "by phone number"            "phone_number=9990001234&otp_code=123456"
run_case "by phone, formatted"        "phone_number=999-000-1234&otp_code=123456"
run_case "missing otp_code"           "customer_id=9990001"
run_case "missing customer and phone" "otp_code=123456"
run_case "unknown customer"           "customer_id=98765432&otp_code=123456"
run_case "unknown phone"              "phone_number=1111111111&otp_code=123456"
echo
echo "  passed: $PASS   mismatched: $FAIL"
