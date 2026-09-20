#!/bin/bash
# Web-UI differential suite: every controller in Ui::PORTED.
#
# Unlike the API suites this one needs a session - every page redirects a guest
# to the login form. It creates its own administrator, logs in as that account,
# compares the pages, and removes the account again, so no real credential is
# used and the database is left as it was found.
#
# The controller list is read from Ui::PORTED rather than repeated here: a
# controller that lands is then covered by this suite without a second edit,
# and one that is rolled back stops being checked.
cd /root/pos
REPO=/root/pos/pos83

COOKIE=$(mktemp /tmp/uisuite-cookie.XXXXXX)
cleanup() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
      < /root/pos/ui_teardown.sql >/dev/null 2>&1
  rm -f "$COOKIE"
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' < /root/pos/perm_teardown.sql >/dev/null 2>&1
}
trap cleanup EXIT

docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
    < /root/pos/ui_fixture.sql >/dev/null 2>&1

# The permission rows the application asks for and does not have. Without them
# about thirty pages answer 403 on both stacks and the suite can only report
# that it compared nothing. See perm_fixture.sql.
docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
    < /root/pos/perm_fixture.sql >/dev/null 2>&1

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
  echo "uisuite: login failed, nothing compared"
  echo "passed: 0 mismatched: 1"
  exit 1
fi

P=0; M=0; N=0
for ctrl in $(python3 - <<'PY'
import os, re
s = open('/root/pos/pos83/app2/components/Ui.php').read()
ported = re.findall(r"'([^']+)'", re.search(r'PORTED = \[(.*?)\];', s, re.S).group(1))

# Only the controllers that have a model. Every case this suite runs is built
# around one - an admin grid, a detail view, a create and an update form - so
# for `site` and `loyaltyAdmin` all six 404 on both stacks, and "both 404"
# reads as a pass. They are compared as page text by pages_difftest instead.
def has_model(c):
    model = c[0].upper() + c[1:]
    return os.path.exists('/root/pos/pos83/protected/models/_base/Base%s.php' % model)

print(' '.join(c for c in ported if has_model(c)))
PY
); do
  model=$(python3 -c "import sys; c=sys.argv[1]; print(c[0].upper()+c[1:])" "$ctrl")
  grid=$(python3 - "$ctrl" <<'PY'
import re, sys, os
c = sys.argv[1]
for v in ('admin', '_list'):
    p = f'/root/pos/pos83/protected/views/{c}/{v}.php'
    if os.path.exists(p):
        m = re.search(r"'id'\s*=>\s*'([^']+)'", open(p, errors='replace').read())
        if m:
            print(m.group(1)); break
PY
)
  table=$(python3 - "$model" <<'PY'
import re, sys
p = f'/root/pos/pos83/protected/models/_base/Base{sys.argv[1]}.php'
m = re.search(r"tableName\s*\(\)\s*\{\s*return\s*'\{\{(\w+)\}\}'", open(p, errors='replace').read())
print(m.group(1) if m else '')
PY
)
  rid=$(docker exec pos-mysql-8 sh -c \
        "mysql -uroot -p\$MYSQL_ROOT_PASSWORD \$MYSQL_DATABASE -N -e \
         'SELECT id FROM tbl_$table ORDER BY id DESC LIMIT 1'" 2>/dev/null | tr -d '\r')
  # The session outlives a controller but not the whole suite: PHP's default
  # lifetime is 24 minutes and this run is longer than that. Once it lapses,
  # Yii 1 answers the login page for every remaining controller and the port
  # redirects, which reads as a mismatch - discount was reported as three
  # failures that way while all three of its pages render correctly.
  probe=$(curl -sS -b "$COOKIE" --max-time 60 \
          "http://127.0.0.1:8084/v2/paymentMode/admin" -o /dev/null -w '%{http_code}')
  [ "$probe" = "200" ] || bash /root/pos/uilogin.sh >/dev/null 2>&1

  out=$(COOKIE_FILE="$COOKIE" python3 "$REPO/tests/port/ui-difftest.py" "$ctrl" "$model" "$grid" "$rid" 2>&1)
  p=$(echo "$out" | grep -c '^  ok ')
  m=$(echo "$out" | grep -c '^  FAIL ')
  # Cases where both stacks rendered nothing. Neither passed nor mismatched:
  # printing them keeps a page that compares nothing from reading as coverage.
  n=$(echo "$out" | grep -c '^  none ')
  P=$((P+p)); M=$((M+m)); N=$((N+n))
  if [ "$n" -gt 0 ]; then
    printf "    %-22s ok=%-3s fail=%-3s nothing=%s\n" "$ctrl" "$p" "$m" "$n"
  else
    printf "    %-22s ok=%-3s fail=%s\n" "$ctrl" "$p" "$m"
  fi
  [ "$m" -gt 0 ] && echo "$out" | grep -A 3 '^  FAIL '
  # Why a case compared nothing, not just how many did. Without this the
  # count is a number nobody can act on - and these are the cases where the
  # suite is saying it does not know.
  [ "$n" -gt 0 ] && echo "$out" | grep '^  none '
done

echo "passed: $P mismatched: $M nothing-compared: $N"
[ "$M" -eq 0 ]
