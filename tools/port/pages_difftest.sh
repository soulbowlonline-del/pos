#!/bin/bash
# The controllers with no model, compared as page text.
#
# The UI suite builds its cases around a model - an admin grid, a detail view,
# a create and an update form - so `site` and `loyaltyAdmin` have no cases
# there at all and were being carried by hand. Their pages still have to say
# what Yii 1 says, so they are compared here and counted with everything else.
cd /root/pos/pos83

# The session outlives a single suite but not a whole run, and an expired
# cookie makes both stacks redirect to the login form - which compares equal
# and reports as a pass.
probe=$(curl -sS -b /tmp/uic.txt --max-time 60 "http://127.0.0.1:8084/v2/paymentMode/admin" -o /dev/null -w '%{http_code}')
[ "$probe" = "200" ] || bash /root/pos/uilogin.sh >/dev/null 2>&1

P=0; M=0; N=0
run() {
  out=$(COOKIE_FILE=/tmp/uic.txt python3 tests/port/pagecompare.py "$@" 2>&1)
  echo "$out" | grep -E '^  (FAIL|DIFF|nothing)' 
  P=$((P + $(echo "$out" | grep -c '^  ok')))
  M=$((M + $(echo "$out" | grep -c '^  FAIL')))
  N=$((N + $(echo "$out" | grep -c 'nothing compared')))
}

run site index about
run loyaltyAdmin index customers settings reports \
    'adjustPoints' 'adjustPoints?customer_id=1' 'viewTransactions?id=1'

echo "passed: $P   mismatched: $M"
[ "$N" -gt 0 ] && echo "nothing-compared: $N"
exit 0
