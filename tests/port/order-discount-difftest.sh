#!/bin/bash
# Differential test for order/discount.
#
# The endpoint takes no parameters - what it returns depends entirely on the
# rows in tbl_discount and on the clock. So each case rewrites the table and
# then calls both stacks.
#
# The eleven rows that ship with the demo database are parked (status set
# inactive) for the duration and restored at the end, so a case can assert on
# an empty result. The original statuses are kept in tbl_discount_status_bak;
# the teardown restores from it too, in case this script is interrupted.
#
# Time windows are absolute rather than relative to now: 00:00:01..23:59:59 is
# inside for every instant of the day except midnight exactly, and the two
# narrow windows at either end of the day are outside for all but two seconds.
# Relative windows would have been ambiguous - this ran first at 00:13.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
U=1   # create_user_id is NOT NULL with a foreign key

sql() { docker exec pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD \$MYSQL_DATABASE -e \"$1\"" >/dev/null 2>&1; }

park_live_rows() {
  sql "CREATE TABLE IF NOT EXISTS tbl_discount_status_bak AS SELECT id, status FROM tbl_discount WHERE id < 9990000;
       UPDATE tbl_discount SET status = 1 WHERE id < 9990000;"
}
restore_live_rows() {
  sql "UPDATE tbl_discount d JOIN tbl_discount_status_bak b ON b.id = d.id SET d.status = b.status;
       DROP TABLE IF EXISTS tbl_discount_status_bak;
       DELETE FROM tbl_discount WHERE id >= 9990000;"
}

# insert a test discount: id, title, type(0=order,1=item), status(0=active),
# start_date, end_date, start_time, end_time
add() {
  sql "INSERT INTO tbl_discount
       (id,title,amount,applicable_amt,start_time,end_time,start_date,end_date,status,type_id,discount_type,create_user_id)
       VALUES ($1,'$2',10.00,100.00,'$7','$8','$5','$6',$4,1,$3,$U);"
}
clear_test_rows() { sql "DELETE FROM tbl_discount WHERE id >= 9990000;"; }

TODAY=$(docker exec pos-php-83 php -r 'echo date("Y-m-d");')
PAST=$(docker exec pos-php-83 php -r 'echo date("Y-m-d", strtotime("-10 days"));')
FUTURE=$(docker exec pos-php-83 php -r 'echo date("Y-m-d", strtotime("+10 days"));')
YESTERDAY=$(docker exec pos-php-83 php -r 'echo date("Y-m-d", strtotime("-1 day"));')
TOMORROW=$(docker exec pos-php-83 php -r 'echo date("Y-m-d", strtotime("+1 day"));')

run_case() {
  local name="$1"
  local r1; r1=$(curl -sS --max-time 120 -X POST "$BASE/api/order/discount")
  local r2; r2=$(curl -sS --max-time 120 -X POST "$BASE/v2/api/order/discount")
  if [ "$r1" = "$r2" ]; then
    printf "  %-44s OK\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-44s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    echo "      yii1: $(echo "$r1" | head -c 400)"
    echo "      yii2: $(echo "$r2" | head -c 400)"
  fi
}

echo "=== order/discount differential ==="
park_live_rows
trap restore_live_rows EXIT

clear_test_rows
run_case "no discounts at all"

clear_test_rows
add 9990010 "All day"     0 0 "$PAST" "$FUTURE" "00:00:00" "00:00:00"
run_case "all-day window included"

clear_test_rows
add 9990011 "Wide window" 0 0 "$PAST" "$FUTURE" "00:00:01" "23:59:59"
run_case "wide time window included"

clear_test_rows
add 9990012 "Ended"       0 0 "$PAST" "$FUTURE" "00:00:01" "00:00:02"
run_case "time window already over - OK with no list"

clear_test_rows
add 9990013 "Not started" 0 0 "$PAST" "$FUTURE" "23:59:58" "23:59:59"
run_case "time window not yet open - OK with no list"

clear_test_rows
add 9990010 "All day"     0 0 "$PAST" "$FUTURE" "00:00:00" "00:00:00"
add 9990012 "Ended"       0 0 "$PAST" "$FUTURE" "00:00:01" "00:00:02"
run_case "one in window, one out - only the first listed"

clear_test_rows
add 9990014 "Item type"   1 0 "$PAST" "$FUTURE" "00:00:00" "00:00:00"
run_case "item-type discount excluded"

clear_test_rows
add 9990015 "Inactive"    0 1 "$PAST" "$FUTURE" "00:00:00" "00:00:00"
run_case "inactive discount excluded"

clear_test_rows
add 9990016 "Expired"     0 0 "$PAST" "$YESTERDAY" "00:00:00" "00:00:00"
run_case "end_date in the past excluded"

clear_test_rows
add 9990016 "Not yet"     0 0 "$TOMORROW" "$FUTURE" "00:00:00" "00:00:00"
run_case "start_date in the future excluded"

clear_test_rows
add 9990016 "Starts today" 0 0 "$TODAY" "$TODAY" "00:00:00" "00:00:00"
run_case "single-day discount, today, included"

clear_test_rows
add 9990013 "Third"  0 0 "$PAST" "$FUTURE" "00:00:01" "23:59:59"
add 9990011 "First"  0 0 "$PAST" "$FUTURE" "00:00:01" "23:59:59"
add 9990012 "Second" 0 0 "$PAST" "$FUTURE" "00:00:01" "23:59:59"
run_case "several discounts come back in id order"

echo
echo "  passed: $PASS   mismatched: $FAIL"
