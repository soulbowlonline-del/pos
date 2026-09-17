#!/bin/bash
# Fetches each route with Yii's custom errorAction disabled, so its debug page
# renders with the message and the frame that raised it, and prints one line
# per route. Restores errorAction on the way out, including on interrupt.
set -u
JAR=/tmp/cj8084.txt
BASE=http://127.0.0.1:8084

python3 /root/pos/toggle_err.py off >/dev/null
trap 'python3 /root/pos/toggle_err.py on >/dev/null' EXIT

for r in "$@"; do
  code=$(curl -sS -b $JAR -c $JAR -o /tmp/diag.html -w "%{http_code}" --max-time 40 "$BASE$r")
  if [ "$code" = "200" ]; then
    printf "  %-34s 200 ok\n" "$r"
    continue
  fi
  msg=$(sed -e 's/<[^>]*>//g' /tmp/diag.html | grep -vE '^\s*$' \
        | grep -viE '^(body|html|ol|h[0-9]|table|blockquote|del|ins|:focus|font|color|margin|border|background)' \
        | grep -iE 'deprecated|error|exception|undefined|null|TypeError|not found|Call to' | head -1 | sed 's/^[ \t]*//')
  loc=$(grep -oE '/var/www/html/[A-Za-z0-9/_.-]+\.php\([0-9]+\)' /tmp/diag.html | head -1)
  printf "  %-34s %s  %s\n     %s\n" "$r" "$code" "$loc" "${msg:0:150}"
done
