#!/bin/bash
# Differential test for customer/update. It writes, so the fixture is rebuilt
# before each side of every comparison.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/setup_test.sql >/dev/null 2>&1
  docker exec pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE -e "UPDATE tbl_customer SET contact_no=\"9990001234\", address=\"orig\", city_id=2, state_id=7, country_id=3, zip_code=\"000000\", name=\"PORT TEST\" WHERE id=9990001;"' >/dev/null 2>&1
}
state() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(name,\"|\",IFNULL(address,\"\"),\"|\",city_id,\"|\",state_id,\"|\",country_id,\"|\",zip_code,\"|\",contact_no) FROM tbl_customer WHERE id=9990001;"' 2>/dev/null
}

run_case() {
  local name="$1" data="$2" q="$3"
  reset; local r1; r1=$(curl -sS --max-time 120 -X POST -d "$data" "$BASE/api/customer/update$q"); local s1; s1=$(state)
  reset; local r2; r2=$(curl -sS --max-time 120 -X POST -d "$data" "$BASE/v2/api/customer/update$q"); local s2; s2=$(state)
  if [ "$r1" = "$r2" ] && [ "$s1" = "$s2" ]; then
    printf "  %-38s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-38s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      yii1: $(echo "$r1" | head -c 240)"; echo "      yii2: $(echo "$r2" | head -c 240)"; }
    [ "$s1" != "$s2" ] && { echo "      yii1 state: $s1"; echo "      yii2 state: $s2"; }
  fi
}

FULL='name=Updated&address=NewAddr&city_id=1&state_id=3&country_id=1&zip_code=160059&contact_no=9990001234'
echo "=== customer/update differential ==="
run_case "same phone (the common case)"  "$FULL"                                  "?id=9990001"
run_case "changed phone"                 "${FULL/9990001234/9990009999}"          "?id=9990001"
run_case "phone belonging to someone else" "${FULL/9990001234/91-6677}"           "?id=9990001"
run_case "missing required field"        "name=X&address=Y"                       "?id=9990001"
run_case "unknown customer"              "$FULL"                                  "?id=98765432"
run_case "with optional fields"          "$FULL&email=t@example.invalid&credit_limit=500&payment_days=7" "?id=9990001"
echo
echo "  passed: $PASS   mismatched: $FAIL"
