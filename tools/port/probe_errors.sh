#!/bin/bash
# Prints the first error each ported page returns, so a batch's failures can be
# read all at once instead of one controller at a time.
for c in "$@"; do
  for p in admin index create view; do
    body=$(curl -sS -b /tmp/uic.txt --max-time 60 "http://127.0.0.1:8084/v2/$c/$p" | head -c 200)
    case "$body" in
      *'"ok":false'*) printf "  %-22s %-7s %s\n" "$c" "$p" "$(echo "$body" | sed -e 's/.*"error":"//' -e 's/","type.*//' | head -c 110)" ;;
    esac
  done
done
