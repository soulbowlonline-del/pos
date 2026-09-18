#!/bin/bash
# Differential test for order/search.
#
# Read-only, so it runs against the live data. The action is only reachable for
# a user whose row has an employee record - user 11 has one, user 1 does not -
# and those two branches are covered as well as the query itself.
#
# One shape is deliberately not tested alone: CDbCriteria::compare() drops the
# condition entirely when the value is '', so posting only bill_no= would
# select every one of the 1,580,276 orders. It is tested alongside a
# customer_id that bounds the result instead.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

q() { docker exec -i pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE -e \"$1\"" 2>/dev/null; }

EMPUSER=$(q "SELECT u.id FROM tbl_user u JOIN tbl_emp e ON e.id = u.emp_id WHERE u.emp_id <> 0 ORDER BY u.id LIMIT 1;")
NOEMPUSER=$(q "SELECT id FROM tbl_user WHERE emp_id = 0 ORDER BY id LIMIT 1;")
BILLNO=$(q "SELECT bill_no FROM tbl_order WHERE bill_no IS NOT NULL AND bill_no <> '' ORDER BY id DESC LIMIT 1;")
BILLDATE=$(q "SELECT bill_date FROM tbl_order WHERE bill_date IS NOT NULL ORDER BY id DESC LIMIT 1;")
CUSTID=$(q "SELECT customer_id FROM tbl_order WHERE customer_id IS NOT NULL GROUP BY customer_id HAVING COUNT(*) BETWEEN 2 AND 40 ORDER BY customer_id LIMIT 1;")

run_case() {
  local name="$1" login="$2" body="$3"
  local r1; r1=$(curl -sS --max-time 300 -X POST ${login:+-H "userlogin: $login"} ${body:+-d "$body"} "$BASE/api/order/search")
  local r2; r2=$(curl -sS --max-time 300 -X POST ${login:+-H "userlogin: $login"} ${body:+-d "$body"} "$BASE/v2/api/order/search")
  if [ "$r1" = "$r2" ]; then
    printf "  %-46s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-46s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    echo "      yii1: $(echo "$r1"|head -c 260)"; echo "      yii2: $(echo "$r2"|head -c 260)"
  fi
}

if [ -z "$EMPUSER" ] || [ -z "$BILLNO" ] || [ -z "$CUSTID" ]; then
  echo "  LOOKUPS EMPTY - refusing to run (user=$EMPUSER bill=$BILLNO cust=$CUSTID)"
  echo "  passed: 0   mismatched: 1"; exit 1
fi

echo "=== order/search differential  (user $EMPUSER, bill $BILLNO, customer $CUSTID) ==="
run_case "by bill_no"                     "$EMPUSER" "bill_no=$BILLNO"
run_case "by bill_date"                   "$EMPUSER" "bill_date=$BILLDATE"
run_case "by customer_id"                 "$EMPUSER" "customer_id=$CUSTID"
run_case "customer_id and bill_date"      "$EMPUSER" "customer_id=$CUSTID&bill_date=$BILLDATE"
run_case "empty bill_no drops the filter" "$EMPUSER" "customer_id=$CUSTID&bill_no="
run_case "greater-than operator"          "$EMPUSER" "customer_id=$CUSTID&bill_no=>$BILLNO"
run_case "no such bill_no"                "$EMPUSER" "bill_no=999999999"
run_case "no such customer"               "$EMPUSER" "customer_id=999999999"
run_case "no filters posted"              "$EMPUSER" ""
run_case "user without an employee row"   "$NOEMPUSER" "bill_no=$BILLNO"
run_case "unknown user"                   "99999999" "bill_no=$BILLNO"
run_case "no login header"                ""          "bill_no=$BILLNO"

echo
echo "  passed: $PASS   mismatched: $FAIL"
