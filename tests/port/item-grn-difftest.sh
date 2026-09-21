#!/bin/bash
# Differential test for item/getGRN and item/getGRNItems.
#
# Both are read paths, so only the response is compared. The caller's outlet
# comes from a header-identified user, which the fixture supplies; the bills
# themselves are cloned real rows (see grn_fixture.sql).
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0

run_case() {
  local name="$1" a1="$2" a2="$3" q="$4"; shift 4
  local r1; r1=$(curl -sS --max-time 120 "$@" "$BASE/api/item/$a1$q")
  local r2; r2=$(curl -sS --max-time 120 "$@" "$BASE/v2/api/item/$a2$q")
  if [ "$r1" = "$r2" ]; then
    printf "  %-40s OK\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-40s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    echo "      yii1: $(echo "$r1" | head -c 320)"
    echo "      yii2: $(echo "$r2" | head -c 320)"
  fi
}

# For cases where both stacks error, the bodies are each framework's own error
# page and cannot match; compare the status code instead.
run_status_case() {
  local name="$1" a1="$2" a2="$3" q="$4"; shift 4
  local c1; c1=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 "$@" "$BASE/api/item/$a1$q")
  local c2; c2=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 "$@" "$BASE/v2/api/item/$a2$q")
  if [ "$c1" = "$c2" ]; then
    printf "  %-40s OK  (status %s)\n" "$name" "$c1"; PASS=$((PASS+1))
  else
    printf "  %-40s MISMATCH  yii1=%s yii2=%s\n" "$name" "$c1" "$c2"; FAIL=$((FAIL+1))
  fi
}

echo "=== item/getGRN + getGRNItems differential ==="
run_case "getGRN, emp with an outlet"      getGRN getGRN "" -H "userlogin: 9990004"
run_case "getGRN via login_id header"      getGRN getGRN "" -H "login_id: 9990004"
run_case "getGRN, userlogin empty falls back" getGRN getGRN "" -H "userlogin;" -H "login_id: 9990004"
run_case "getGRN, user with no emp row"    getGRN getGRN "" -H "userlogin: 1"
run_case "getGRN, unknown user"            getGRN getGRN "" -H "userlogin: 98765432"
run_case "getGRN, no header at all"        getGRN getGRN ""
run_case "getGRN, non-numeric user"        getGRN getGRN "" -H "userlogin: abc"

run_case "getGRNItems, bill with 3 lines"  getGRNItems getGRNItems "?id=9990010" -H "userlogin: 9990004"
run_case "getGRNItems, bill with no lines" getGRNItems getGRNItems "?id=9990011" -H "userlogin: 9990004"
run_case "getGRNItems, unknown bill"       getGRNItems getGRNItems "?id=98765432" -H "userlogin: 9990004"
run_case "getGRNItems, id=0"               getGRNItems getGRNItems "?id=0" -H "userlogin: 9990004"
run_case "getGRNItems, non-numeric id"     getGRNItems getGRNItems "?id=abc" -H "userlogin: 9990004"
run_status_case "getGRNItems, id omitted"  getGRNItems getGRNItems "" -H "userlogin: 9990004"
echo
echo "  passed: $PASS   mismatched: $FAIL"
