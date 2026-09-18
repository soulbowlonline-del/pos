#!/bin/bash
# Creates the test administrator and logs in, leaving a session in /tmp/uic.txt.
# Used while developing a port; the suites create and remove their own.
docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
    < /root/pos/ui_fixture.sql >/dev/null 2>&1
rm -f /tmp/uic.txt
curl -sS -o /dev/null -c /tmp/uic.txt -b /tmp/uic.txt --max-time 60 "http://127.0.0.1:8084/user/login"
curl -sS -o /dev/null -c /tmp/uic.txt -b /tmp/uic.txt --max-time 60 \
     -d 'LoginForm[username]=porttestadmin' \
     -d 'LoginForm[password]=PortCrawl!9990003' \
     -d 'LoginForm[rememberMe]=0' \
     "http://127.0.0.1:8084/user/login"
curl -sS -b /tmp/uic.txt --max-time 60 "http://127.0.0.1:8084/paymentMode/admin" \
  | grep -q 'payment-mode-grid' && echo "logged in" || echo "LOGIN FAILED"
