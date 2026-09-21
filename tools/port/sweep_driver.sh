#!/bin/bash
# Runs route-sweep.py one controller at a time. A single pass over all 59 ties
# up every Apache worker for long enough that the application stops answering
# anything else, which makes the sweep useless for the person watching it.
cd /root/pos
LOG=/root/pos/routesweep.log
: > "$LOG"
CTRLS=$(python3 -c "
import re
s=open('/root/pos/pos83/app2/components/Ui.php').read()
print(' '.join(re.findall(r\"'([^']+)'\", re.search(r'PORTED = \[(.*?)\];', s, re.S).group(1))))
")
for c in $CTRLS; do
  echo "### $c" >> "$LOG"
  timeout 300 python3 -u pos83/tests/port/route-sweep.py "$c" 2>&1 \
    | grep -vE '^\s*$|not run|passed:' >> "$LOG"
  sleep 1
done
echo "### done" >> "$LOG"
