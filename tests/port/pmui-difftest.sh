#!/bin/bash
# paymentMode web-UI differential suite.
#
# Unlike the API suites this one needs a session: every page redirects a guest
# to the login form. It creates its own administrator (ui_fixture.sql), logs in
# as that account, compares the pages, and removes the account again - so the
# suite leaves the database as it found it and never uses a real credential.
cd /root/pos

COOKIE=$(mktemp /tmp/pmui-cookie.XXXXXX)
cleanup() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
      < /root/pos/ui_teardown.sql >/dev/null 2>&1
  rm -f "$COOKIE"
}
trap cleanup EXIT

docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
    < /root/pos/ui_fixture.sql >/dev/null 2>&1

curl -sS -o /dev/null -c "$COOKIE" -b "$COOKIE" --max-time 60 \
     "http://127.0.0.1:8084/user/login"
curl -sS -o /dev/null -c "$COOKIE" -b "$COOKIE" --max-time 60 \
     -d 'LoginForm[username]=porttestadmin' \
     -d 'LoginForm[password]=PortCrawl!9990003' \
     -d 'LoginForm[rememberMe]=0' \
     "http://127.0.0.1:8084/user/login"

# Fail loudly rather than comparing two login pages to each other.
if ! curl -sS -b "$COOKIE" --max-time 60 "http://127.0.0.1:8084/paymentMode/admin" \
     | grep -q 'payment-mode-grid'; then
  echo "pmui_difftest: login failed, nothing compared"
  echo "passed: 0 mismatched: 1"
  exit 1
fi

OUT=$(COOKIE_FILE="$COOKIE" python3 /root/pos/pos83/tests/port/paymentmode-ui-difftest.py 1 2>&1)
echo "$OUT"
P=$(echo "$OUT" | grep -c '^  ok ')
M=$(echo "$OUT" | grep -c '^  FAIL ')
echo "passed: $P mismatched: $M"
[ "$M" -eq 0 ]
