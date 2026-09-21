#!/bin/bash
# Differential test for the login flow, on both stacks.
#
# Nothing covered login until now. The suites authenticate through Yii 1 and
# let the session bridge carry them into /v2, so the port's own form was never
# asked for - which is how it came to be a 500 that no green run could see:
# `new UserIdentity(...)` resolved to app\controllers\UserIdentity, and the
# guest layout it needed had never been ported either.
#
# Each case is run against Yii 1 at / and the port at /v2 and the two answers
# are compared, except where the comparison is about the session rather than
# the response - a login either produces one that works or it does not, and
# that is asserted on each stack directly.
#
# The fixture's administrator (9990003 / porttestadmin) is created by
# ui_fixture.sql and removed by the teardown, as everywhere else.
B=http://127.0.0.1:8084
USERNAME=porttestadmin
PASSWORD='PortCrawl!9990003'
PASS=0; FAIL=0

reset() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE' \
      < /root/pos/ui_fixture.sql >/dev/null 2>&1
}

# Yii 2 validates a CSRF token on every POST; Yii 1's login form does not.
csrf_of() {
  grep -o 'name="_csrf" value="[^"]*"' "$1" | head -1 | sed 's/.*value="//; s/"$//'
}

# login <jar> <prefix> <username> <password>  -> prints the POST status
login() {
  local jar="$1" pre="$2" u="$3" p="$4"
  rm -f "$jar"
  curl -sS -o /tmp/lf.html -c "$jar" -b "$jar" --max-time 60 "$B$pre/user/login"
  local token; token=$(csrf_of /tmp/lf.html)
  curl -sS -o /tmp/lp.html -w '%{http_code}' -c "$jar" -b "$jar" --max-time 60 \
       --data-urlencode "_csrf=$token" \
       -d "LoginForm[username]=$u" -d "LoginForm[password]=$p" \
       -d 'LoginForm[rememberMe]=0' "$B$pre/user/login"
}

# signed_in <jar> <prefix>  -> yes|no, by the status of a page behind the session
#
# item/admin, and the status rather than the markup. The first version asked
# paymentMode/admin and grepped for its grid id: that page answers 500 to a
# guest on Yii 1 - it does so on the untouched 5.6 baseline too - and Yii 1's
# error page prints the source of the view it failed in, grid id and all. So
# the probe reported every signed-out Yii 1 session as signed in, and three
# cases failed against a fault in the test rather than in either stack.
signed_in() {
  local code
  code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$1" --max-time 120 \
         "$B$2/item/admin" 2>/dev/null)
  [ "$code" = "200" ] && echo yes || echo no
}

ok()   { printf "  %-46s OK  (%s)\n" "$1" "$2"; PASS=$((PASS+1)); }
bad()  { printf "  %-46s MISMATCH  %s\n" "$1" "$2"; FAIL=$((FAIL+1)); }

echo "=== login differential  (Yii 1 at /, the port at /v2) ==="

# 1. the form itself renders on both, and only the port carries a CSRF token
reset
c1=$(curl -sS -o /tmp/f1.html -w '%{http_code}' --max-time 60 "$B/user/login")
c2=$(curl -sS -o /tmp/f2.html -w '%{http_code}' --max-time 60 "$B/v2/user/login")
f1=$(grep -c 'LoginForm\[username\]\|LoginForm_username' /tmp/f1.html)
f2=$(grep -c 'LoginForm\[username\]\|LoginForm_username' /tmp/f2.html)
if [ "$c1" = "200" ] && [ "$c2" = "200" ] && [ "$f1" -gt 0 ] && [ "$f2" -gt 0 ]; then
  ok "the form renders for a guest" "200 on both, username field present"
else
  bad "the form renders for a guest" "yii1 $c1/$f1  port $c2/$f2"
fi

# 2. the right credentials produce a session that works
reset
s1=$(login /tmp/l1.txt ''    "$USERNAME" "$PASSWORD"); i1=$(signed_in /tmp/l1.txt '')
s2=$(login /tmp/l2.txt '/v2' "$USERNAME" "$PASSWORD"); i2=$(signed_in /tmp/l2.txt '/v2')
if [ "$i1" = yes ] && [ "$i2" = yes ]; then
  ok "correct credentials sign the user in" "$s1 / $s2, both sessions render the grid"
else
  bad "correct credentials sign the user in" "yii1 $s1 session=$i1   port $s2 session=$i2"
fi

# 3. a Yii 1 session still reaches the port - the bridge is intact
if [ "$(signed_in /tmp/l1.txt '/v2')" = yes ]; then
  ok "a session made at / still reaches /v2" "the bridge is intact"
else
  bad "a session made at / still reaches /v2" "the port did not accept it"
fi

# 4. the wrong password signs nobody in, and both say so the same way
reset
w1=$(login /tmp/w1.txt ''    "$USERNAME" 'not-the-password'); n1=$(signed_in /tmp/w1.txt '')
m1=$(grep -c 'Username or Password is incorrect' /tmp/lp.html)
w2=$(login /tmp/w2.txt '/v2' "$USERNAME" 'not-the-password'); n2=$(signed_in /tmp/w2.txt '/v2')
m2=$(grep -c 'Username or Password is incorrect' /tmp/lp.html)
if [ "$n1" = no ] && [ "$n2" = no ] && [ "$m1" -gt 0 ] && [ "$m2" -gt 0 ]; then
  ok "the wrong password is refused" "no session, both show the same message"
else
  bad "the wrong password is refused" "yii1 session=$n1 msg=$m1   port session=$n2 msg=$m2"
fi

# 5. an unknown user signs nobody in
reset
u1=$(login /tmp/u1.txt ''    'no-such-user-9990009' "$PASSWORD"); n1=$(signed_in /tmp/u1.txt '')
u2=$(login /tmp/u2.txt '/v2' 'no-such-user-9990009' "$PASSWORD"); n2=$(signed_in /tmp/u2.txt '/v2')
if [ "$n1" = no ] && [ "$n2" = no ]; then
  ok "an unknown user is refused" "no session on either"
else
  bad "an unknown user is refused" "yii1 session=$n1   port session=$n2"
fi

# 6. a POST without the token is refused by the port, which is Yii 2's own
#    protection and has no Yii 1 counterpart
reset
rm -f /tmp/n.txt
curl -sS -o /dev/null -c /tmp/n.txt -b /tmp/n.txt --max-time 60 "$B/v2/user/login" >/dev/null
nc=$(curl -sS -o /dev/null -w '%{http_code}' -c /tmp/n.txt -b /tmp/n.txt --max-time 60 \
     -d "LoginForm[username]=$USERNAME" -d "LoginForm[password]=$PASSWORD" "$B/v2/user/login")
if [ "$nc" = "400" ]; then
  ok "the port refuses a POST with no CSRF token" "400"
else
  bad "the port refuses a POST with no CSRF token" "got $nc, expected 400"
fi

# 7. logging out ends the session on each stack
reset
login /tmp/o1.txt ''    "$USERNAME" "$PASSWORD" >/dev/null
curl -sS -o /dev/null -b /tmp/o1.txt -c /tmp/o1.txt --max-time 60 "$B/user/logout"
login /tmp/o2.txt '/v2' "$USERNAME" "$PASSWORD" >/dev/null
curl -sS -o /dev/null -b /tmp/o2.txt -c /tmp/o2.txt --max-time 60 "$B/v2/user/logout"
g1=$(signed_in /tmp/o1.txt ''); g2=$(signed_in /tmp/o2.txt '/v2')
if [ "$g1" = no ] && [ "$g2" = no ]; then
  ok "logout ends the session" "neither stack renders the grid afterwards"
else
  bad "logout ends the session" "yii1 still-in=$g1   port still-in=$g2"
fi

# 8. a guest asking the port for a page behind the session is sent to the
#    port's own form, not Yii 1's
rm -f /tmp/g.txt
loc=$(curl -sS -o /dev/null -D- -c /tmp/g.txt --max-time 60 "$B/v2/item/admin" 2>/dev/null \
      | grep -i '^location:' | tr -d '\r' | awk '{print $2}')
case "$loc" in
  */v2/user/login) ok "a guest on /v2 is sent to the port's form" "$loc" ;;
  *)               bad "a guest on /v2 is sent to the port's form" "went to ${loc:-nowhere}" ;;
esac

echo
echo "  passed: $PASS   mismatched: $FAIL"
