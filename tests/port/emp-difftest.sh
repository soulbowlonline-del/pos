#!/bin/bash
# Differential test for the emp API: same request to Yii 1 and Yii 2, responses
# compared. login mutates device_token, so the fixture is reset around it.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
FIXTURE=9990002

mysqlq() { docker exec -i pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE" 2>/dev/null; }
reset_fixture() { echo "UPDATE tbl_user SET device_token=NULL WHERE id=$FIXTURE;" | mysqlq; }
state() { echo "SELECT CONCAT('device_token:', IFNULL(device_token,'NULL')) FROM tbl_user WHERE id=$FIXTURE;" | mysqlq; }

run_case() {
  local name="$1" a1="$2" a2="$3" data="$4" hdr="$5"
  local H=(); [ -n "$hdr" ] && H=(-H "$hdr")

  reset_fixture >/dev/null
  local r1 s1
  r1=$(curl -sS --max-time 60 -X POST "${H[@]}" -d "$data" "$BASE/api/emp/$a1")
  s1=$(state)

  reset_fixture >/dev/null
  local r2 s2
  r2=$(curl -sS --max-time 60 -X POST "${H[@]}" -d "$data" "$BASE/v2/api/emp/$a2")
  s2=$(state)

  if [ "$r1" = "$r2" ] && [ "$s1" = "$s2" ]; then
    printf "  %-38s OK  (%s bytes)\n" "$name" "${#r1}"; PASS=$((PASS+1))
  else
    printf "  %-38s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    if [ "$r1" != "$r2" ]; then
      echo "      yii1: $(echo "$r1" | head -c 300)"
      echo "      yii2: $(echo "$r2" | head -c 300)"
    fi
    [ "$s1" != "$s2" ] && { echo "      yii1 state: $s1"; echo "      yii2 state: $s2"; }
  fi
}

echo "=== emp API differential (Yii 1 vs Yii 2) ==="
run_case "profile (valid header, user 1)"   profile     profile     ""                                   "userlogin: 1"
run_case "profile (fixture user)"           profile     profile     ""                                   "userlogin: $FIXTURE"
run_case "profile (no header)"              profile     profile     ""                                   ""
run_case "profile (nonexistent user)"       profile     profile     ""                                   "userlogin: 98765432"
run_case "picker (valid header)"            picker      picker      ""                                   "userlogin: 1"
run_case "deliveryboy (valid header)"       deliveryboy deliveryboy ""                                   "userlogin: 1"
run_case "deliveryboy (no header)"          deliveryboy deliveryboy ""                                   ""
run_case "login (no data)"                  login       login       "x=1"                                ""
run_case "login (unknown user)"             login       login       "username=nosuchuser&password=x"     ""
run_case "login (wrong password)"           login       login       "username=porttestuser&password=wrong" ""
run_case "login (correct, fixture)"         login       login       "username=porttestuser&password=PortTest!9990002" ""
run_case "login (correct + deviceID)"       login       login       "username=porttestuser&password=PortTest!9990002&deviceID=DEV-XYZ" ""
run_case "recover (empty email)"            recover     recover     "email="                             ""
run_case "recover (unregistered email)"     recover     recover     "email=nobody@example.invalid"       ""
echo
echo "  passed: $PASS   mismatched: $FAIL"
