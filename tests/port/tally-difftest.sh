#!/bin/bash
# Differential test for the ported tally cashsale action.
BASE=http://127.0.0.1:8084
PASS=0; FAIL=0
for d in 2026-09-15 2026-09-14 2026-09-13 2026-08-20 2026-08-01 1990-01-01; do
  r1=$(curl -sS --max-time 240 -X POST "$BASE/api/tally/cashsale?date=$d")
  r2=$(curl -sS --max-time 240 -X POST "$BASE/v2/api/tally/cashsale?date=$d")
  if [ "$r1" = "$r2" ]; then printf "  cashsale %-12s OK  (%s bytes)\n" "$d" "${#r1}"; PASS=$((PASS+1))
  else printf "  cashsale %-12s MISMATCH (yii1=%s yii2=%s)\n" "$d" "${#r1}" "${#r2}"; FAIL=$((FAIL+1))
       echo "      yii1: $(echo "$r1" | head -c 240)"; echo "      yii2: $(echo "$r2" | head -c 240)"; fi
done
for d in 2026-09-16 2026-09-15 2026-09-14 2026-09-12 1990-01-01; do
  r1=$(curl -sS --max-time 300 -X POST "$BASE/api/tally/stockreturn?date=$d")
  r2=$(curl -sS --max-time 300 -X POST "$BASE/v2/api/tally/stockreturn?date=$d")
  if [ "$r1" = "$r2" ]; then printf "  stockreturn %-12s OK  (%s bytes)\n" "$d" "${#r1}"; PASS=$((PASS+1))
  else printf "  stockreturn %-12s MISMATCH (yii1=%s yii2=%s)\n" "$d" "${#r1}" "${#r2}"; FAIL=$((FAIL+1)); fi
done
for q in "?date=2026-09-16" "?date=2026-09-15" "?date=2026-09-14" "?date=2026-09-13" "?date=1990-01-01" "?date=2026-09-15&id=796000" "?date=2026-09-15&id=99999999"; do
  r1=$(curl -sS --max-time 400 -X POST "$BASE/api/tally/paymentreport$q")
  r2=$(curl -sS --max-time 400 -X POST "$BASE/v2/api/tally/paymentreport$q")
  if [ "$r1" = "$r2" ]; then printf "  paymentreport %-28s OK  (%s bytes)\n" "$q" "${#r1}"; PASS=$((PASS+1))
  else printf "  paymentreport %-28s MISMATCH (yii1=%s yii2=%s)\n" "$q" "${#r1}" "${#r2}"; FAIL=$((FAIL+1)); fi
done
# An empty date is not compared: it is broken on both stacks in different ways
# (Yii 1 throws CDbException, Yii 2 would run to the execution limit), and the
# port deliberately short-circuits it. See the commit message.
echo
echo "  passed: $PASS   mismatched: $FAIL"
