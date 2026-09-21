#!/bin/bash
# Runs every differential suite, the invoice canary, and the fixture teardown,
# and prints one summary. The suites are order-independent: each reloads the
# fixtures it needs.
cd /root/pos
SUITES="rows_difftest hooks_difftest classrefs_difftest staticcalls_difftest difftest emp_difftest cust_difftest order_difftest tally_difftest item_difftest
        otp_difftest update_difftest cust_last_difftest sendotp_difftest add_difftest
        uploadbill_difftest grn_difftest discount_difftest online_difftest orderwrite_difftest item3_difftest refund_difftest barcode_difftest updstock_difftest adjust_difftest itemorder_difftest ordertest_difftest punch_difftest search_difftest"
FIXTURES="setup_test emp_fixture otp_fixture cust_last_fixture grn_fixture online_fixture refund_fixture adjust_fixture"

mysql_run() { docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < "$1" >/dev/null 2>&1; }

docker exec pos-php-83 sh -c ': > /var/log/php_errors.log'
T=0; BAD=0
for s in $SUITES; do
  [ -x "/root/pos/$s.sh" ] || { printf "  %-21s MISSING\n" "$s"; BAD=1; continue; }
  for f in $FIXTURES; do mysql_run "/root/pos/$f.sql"; done
  r=$(/root/pos/$s.sh 2>&1 | grep -oE 'passed: [0-9]+ +mismatched: [0-9]+')
  n=$(echo "$r" | grep -oE 'passed: [0-9]+' | grep -oE '[0-9]+'); T=$((T+n))
  echo "$r" | grep -q 'mismatched: 0' || BAD=1
  printf "  %-21s %s\n" "$s" "$r"
done
echo "  -----------------------------------"
echo "  TOTAL: $T cases   all green: $([ $BAD -eq 0 ] && echo yes || echo NO)"

if [ -f /root/pos/dumphtml.php ]; then
  cp /root/pos/dumphtml.php /root/pos/pos83/__dumphtml.php
  cp /root/pos/dumphtml.php /root/pos/pos/__dumphtml.php
  IDS="1581602,1581598,1581597,1581594,1581593,1581592"
  docker exec pos-php-legacy php /var/www/html/__dumphtml.php "$IDS" >/dev/null 2>&1
  docker exec pos-php-83     php /var/www/html/__dumphtml.php "$IDS" >/dev/null 2>&1
  rm -rf /root/pos/cmp-html && mkdir -p /root/pos/cmp-html/v56 /root/pos/cmp-html/v83
  docker cp pos-php-legacy:/tmp/billhtml/. /root/pos/cmp-html/v56/ 2>/dev/null
  docker cp pos-php-83:/tmp/billhtml/.     /root/pos/cmp-html/v83/ 2>/dev/null
  p=0; t=0
  for f in /root/pos/cmp-html/v56/*.html; do n=$(basename "$f"); t=$((t+1)); cmp -s "$f" "/root/pos/cmp-html/v83/$n" && p=$((p+1)); done
  echo "  invoice canary: $p / $t"
  rm -f /root/pos/pos83/__dumphtml.php /root/pos/pos/__dumphtml.php
fi

echo "  php error log: $(docker exec pos-php-83 sh -c 'wc -l < /var/log/php_errors.log') lines"
mysql_run /root/pos/teardown.sql
docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/teardown.sql 2>&1 | grep -v Warning | tail -1
