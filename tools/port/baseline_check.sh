#!/bin/bash
# Checks whether a page already fails on the untouched PHP 5.6 baseline.
#
# The baseline is the reference the whole port is measured against, so "Yii 1
# answers 500" is only interesting if it is 500 there too - that makes it a
# pre-existing bug rather than anything this work caused.
set -u
JAR=$(mktemp /tmp/base-cookie.XXXXXX)
trap 'docker exec -i pos-mysql-legacy sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD \$MYSQL_DATABASE" < /root/pos/ui_teardown.sql >/dev/null 2>&1; rm -f "$JAR"' EXIT

docker exec -i pos-mysql-legacy sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
    < /root/pos/ui_fixture.sql >/dev/null 2>&1

curl -sS -o /dev/null -c "$JAR" -b "$JAR" --max-time 60 "http://127.0.0.1:8082/user/login"
curl -sS -o /dev/null -c "$JAR" -b "$JAR" --max-time 60 \
     -d 'LoginForm[username]=porttestadmin' \
     -d 'LoginForm[password]=PortCrawl!9990003' \
     -d 'LoginForm[rememberMe]=0' \
     "http://127.0.0.1:8082/user/login"

if [ "$(curl -sS -o /dev/null -w '%{http_code}' -b "$JAR" --max-time 60 \
        'http://127.0.0.1:8082/paymentMode/admin')" != "200" ]; then
  echo "  could not sign in to the 5.6 baseline; results below would be meaningless"
  exit 1
fi

for u in "$@"; do
  printf "  %-34s baseline=%s  8084=%s\n" "$u" \
    "$(curl -sS -o /dev/null -w '%{http_code}' -b "$JAR" --max-time 90 "http://127.0.0.1:8082/$u")" \
    "$(curl -sS -o /dev/null -w '%{http_code}' -b /tmp/uic.txt --max-time 90 "http://127.0.0.1:8084/$u")"
done
