#!/bin/bash
# The write actions, compared by what they write.
#
# See tests/port/write-sweep.py. Reported in the suite's own format so
# run_all.sh can count it with everything else.
cd /root/pos
out=$(python3 /root/pos/pos83/tests/port/write-sweep.py 2>&1)
echo "$out" | grep -E '^  (STATUS|WROTE)|^=== |^  [a-zA-Z0-9]+/'
p=$(echo "$out" | grep -oE 'answered and wrote the same:\s+[0-9]+' | grep -oE '[0-9]+$')
s=$(echo "$out" | grep -oE 'status differs:\s+[0-9]+' | grep -oE '[0-9]+$')
w=$(echo "$out" | grep -oE 'wrote different things:\s+[0-9]+' | grep -oE '[0-9]+$')
n=$(echo "$out" | grep -oE 'no row to act on, skipped:\s+[0-9]+' | grep -oE '[0-9]+$')
echo "passed: ${p:-0}   mismatched: $(( ${s:-0} + ${w:-0} ))"
[ "${n:-0}" -gt 0 ] && echo "nothing-compared: $n"
exit 0
