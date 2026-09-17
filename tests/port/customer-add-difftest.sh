#!/bin/bash
# Differential test for customer/add and customer/sentwhatappotp.
#
# Both reach the WhatsApp CRM, so they run against the outbound stub - the call
# is recorded rather than made, and that recording is compared. add() also
# creates a customer, so the test phone numbers are cleared before each call and
# the new auto-increment id is normalised.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
TESTPHONE=9995550001

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/setup_test.sql >/dev/null 2>&1
  docker exec pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE -e "
    UPDATE tbl_customer SET contact_no=\"9990001234\", is_enable_wa=0 WHERE id=9990001;
    DELETE FROM tbl_customer_otp_verification WHERE customer_id=9990001;
    DELETE FROM tbl_customer WHERE contact_no=\"9995550001\";
    DELETE FROM tbl_outbound_stub_log;
    DELETE FROM tbl_whatsapp_logs WHERE number IN (\"9995550001\",\"9990001234\");"' >/dev/null 2>&1
}

norm() {
  sed -E 's/"id":"[0-9]+"/"id":"<ID>"/g;
           s/"customer_id":"[0-9]+"/"customer_id":"<ID>"/g;
           s/cust:([^|]*)\|/cust:\1|/g;
           s/\b[0-9]{6}\b/<OTP>/g;
           s/stub-[0-9a-f]+/stub-<FP>/g'
}

outbound() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(channel,\" |\",target,\" |\",payload) FROM tbl_outbound_stub_log ORDER BY id;"' 2>/dev/null | norm
}
state() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(\"cust:\",name,\"|\",contact_no,\"|\",city_id,\"|\",state_id,\"|\",country_id,\"|\",zip_code,\"|\",IFNULL(email,\"\")) FROM tbl_customer WHERE contact_no=\"9995550001\";
    SELECT CONCAT(\"wa:\",is_enable_wa) FROM tbl_customer WHERE id=9990001;
    SELECT CONCAT(\"otpv:\",is_verified) FROM tbl_customer_otp_verification WHERE customer_id=9990001 ORDER BY id;
    SELECT CONCAT(\"log:\",number,\"|\",status,\"|\",template_name) FROM tbl_whatsapp_logs WHERE number IN (\"9995550001\",\"9990001234\") ORDER BY id;"' 2>/dev/null | norm
}

run_case() {
  local name="$1" a1="$2" a2="$3" data="$4" q="$5"
  reset; local r1; r1=$(curl -sS --max-time 120 -X POST -d "$data" "$BASE/api/customer/$a1$q" | norm)
  local o1; o1=$(outbound); local s1; s1=$(state)
  reset; local r2; r2=$(curl -sS --max-time 120 -X POST -d "$data" "$BASE/v2/api/customer/$a2$q" | norm)
  local o2; o2=$(outbound); local s2; s2=$(state)

  if [ "$r1" = "$r2" ] && [ "$o1" = "$o2" ] && [ "$s1" = "$s2" ]; then
    printf "  %-38s OK  (response, outbound, state)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-38s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1" | head -c 230)"; echo "      resp yii2: $(echo "$r2" | head -c 230)"; }
    [ "$o1" != "$o2" ] && { echo "      out  yii1: $(echo "$o1" | head -c 260)"; echo "      out  yii2: $(echo "$o2" | head -c 260)"; }
    [ "$s1" != "$s2" ] && { echo "      state yii1: $(echo $s1|tr '\n' ' ')"; echo "      state yii2: $(echo $s2|tr '\n' ' ')"; }
  fi
}

echo "=== customer/add + sentwhatappotp differential (outbound stubbed) ==="
run_case "add minimal"                add add "name=Stub Test&contact_no=$TESTPHONE"                       ""
run_case "add with email + optionals" add add "name=Stub Test&contact_no=$TESTPHONE&email=s@example.invalid&credit_limit=250&payment_days=14" ""
run_case "add with address fields"    add add "name=Stub Test&contact_no=$TESTPHONE&city_id=6&state_id=3&country_id=1&zip_code=110001" ""
run_case "add duplicate phone"        add add "name=Dup&contact_no=9990001234"                              ""
run_case "add missing contact_no"     add add "name=NoPhone"                                                ""
run_case "sentwhatappotp valid"       sentwhatappotp sentwhatappotp ""                                      "?id=9990001"
run_case "sentwhatappotp unknown"     sentwhatappotp sentwhatappotp ""                                      "?id=98765432"
echo
echo "  passed: $PASS   mismatched: $FAIL"
