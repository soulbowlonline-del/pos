#!/bin/bash
# Creates the test administrator and logs in, leaving a session in $COOKIE_FILE
# (/tmp/uic.txt by default). Used while developing a port; the suites create and
# remove their own.
#
# COOKIE_FILE is honoured rather than ignored: the path used to be hardcoded
# while the caller passed its own, so the script filled one jar, verified that
# same jar, printed "logged in", and handed back an empty one.
docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
    < /root/pos/ui_fixture.sql >/dev/null 2>&1

JAR="${COOKIE_FILE:-/tmp/uic.txt}"
rm -f "$JAR"
curl -sS -o /dev/null -c "$JAR" -b "$JAR" --max-time 60 "http://127.0.0.1:8084/user/login"
curl -sS -o /dev/null -c "$JAR" -b "$JAR" --max-time 60 \
     -d 'LoginForm[username]=porttestadmin' \
     -d 'LoginForm[password]=PortCrawl!9990003' \
     -d 'LoginForm[rememberMe]=0' \
     "http://127.0.0.1:8084/user/login"
curl -sS -b "$JAR" --max-time 60 "http://127.0.0.1:8084/paymentMode/admin" \
  | grep -q 'payment-mode-grid' && echo "logged in" || echo "LOGIN FAILED"
