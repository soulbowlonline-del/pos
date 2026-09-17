#!/bin/bash
# Differential test for customer/sendOTP.
#
# This is the first case where what matters is not only the response and the
# database, but *the message the application would have sent*. With
# POS_STUB_OUTBOUND=1 nothing leaves the server; the attempt lands in
# tbl_outbound_stub_log and is compared here.
#
# The generated code is random and row ids are auto-increment, so both are
# normalised - what is compared is the shape of the outbound call, its target
# and everything else in the payload.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/setup_test.sql >/dev/null 2>&1
  docker exec pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE -e "
    UPDATE tbl_customer SET contact_no=\"9990001234\", is_enable_wa=0 WHERE id=9990001;
    DELETE FROM tbl_customer_otp WHERE customer_id=9990001;
    DELETE FROM tbl_outbound_stub_log;
    DELETE FROM tbl_whatsapp_logs WHERE number=\"9990001234\";"' >/dev/null 2>&1
}

# ids, the random code and timestamps vary by construction
norm() { sed -E 's/"otp_id":"?[0-9]+"?/"otp_id":<ID>/g; s/[0-9]{6}/<OTP>/g; s/stub-[0-9a-f]+/stub-<FP>/g'; }

outbound() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(channel,\" |\",target,\" |\",payload) FROM tbl_outbound_stub_log ORDER BY id;"' 2>/dev/null | norm
}
state() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(\"otp:\",phone_number,\"|\",attempts,\"|\",is_verified) FROM tbl_customer_otp WHERE customer_id=9990001 ORDER BY id;
    SELECT CONCAT(\"wa:\",is_enable_wa) FROM tbl_customer WHERE id=9990001;
    SELECT CONCAT(\"log:\",number,\"|\",status,\"|\",template_name) FROM tbl_whatsapp_logs WHERE number=\"9990001234\" ORDER BY id;"' 2>/dev/null | norm
}

run_case() {
  local name="$1" data="$2"
  reset; local r1; r1=$(curl -sS --max-time 120 -X POST -d "$data" "$BASE/api/customer/sendOTP" | norm)
  local o1; o1=$(outbound); local s1; s1=$(state)
  reset; local r2; r2=$(curl -sS --max-time 120 -X POST -d "$data" "$BASE/v2/api/customer/send-otp" | norm)
  local o2; o2=$(outbound); local s2; s2=$(state)

  if [ "$r1" = "$r2" ] && [ "$o1" = "$o2" ] && [ "$s1" = "$s2" ]; then
    printf "  %-36s OK  (response, outbound and state match)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-36s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1" | head -c 200)"; echo "      resp yii2: $(echo "$r2" | head -c 200)"; }
    [ "$o1" != "$o2" ] && { echo "      out  yii1: $(echo "$o1" | head -c 260)"; echo "      out  yii2: $(echo "$o2" | head -c 260)"; }
    [ "$s1" != "$s2" ] && { echo "      state yii1: $(echo $s1|tr '\n' ' ')"; echo "      state yii2: $(echo $s2|tr '\n' ' ')"; }
  fi
}

echo "=== customer/sendOTP differential (with outbound stub) ==="
run_case "valid, by phone"              "phone_number=9990001234"
run_case "valid, by customer_id"        "customer_id=9990001&phone_number=9990001234"
run_case "no phone number"              "customer_id=9990001"
run_case "malformed phone (too short)"  "phone_number=12345"
run_case "phone with separators"        "phone_number=999-000-1234"
run_case "unknown phone"                "phone_number=1231231231"
echo
echo "  passed: $PASS   mismatched: $FAIL"
