#!/bin/bash
# Differential test for the ported item catalogue list.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
for pg in "" "?page=1" "?page=2" "?page=3" "?page=5" "?page=99999"; do
  r1=$(curl -sS --max-time 240 -X POST "$BASE/api/item/list$pg")
  r2=$(curl -sS --max-time 240 -X POST "$BASE/v2/api/item/list$pg")
  lbl=${pg:-"(no page)"}
  if [ "$r1" = "$r2" ]; then printf "  list %-14s OK  (%s bytes)\n" "$lbl" "${#r1}"; PASS=$((PASS+1))
  else printf "  list %-14s MISMATCH (yii1=%s yii2=%s)\n" "$lbl" "${#r1}" "${#r2}"; FAIL=$((FAIL+1)); fi
done
echo
echo "  passed: $PASS   mismatched: $FAIL"
