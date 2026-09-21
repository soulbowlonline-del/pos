#!/usr/bin/env python3
"""
Every DELETE in the harness is scoped to something the harness created.

A teardown that names a value instead of a fixture id deletes rows the fixture
never made. `DELETE FROM tbl_credit_note WHERE amt IN (100,200,300,1000)` took
601 real credit notes, and it turned out to be written twice - once in the
teardown and once in the refund suite's own reset, where it also matched
amt = 0. Fixing one copy and not the other cost the same 601 rows on the very
next run.

A scope is recognised when the WHERE clause mentions a fixture id (anything
from 9990000 up), a shell variable holding one, the test phone numbers, or the
credit-note high-water mark. Anything else is reported for a human to read.
"""
import glob, re, sys

FILES = (glob.glob('/root/pos/*.sh') + glob.glob('/root/pos/*.sql')
         + glob.glob('/root/pos/pos83/tests/port/*.sh')
         + glob.glob('/root/pos/pos83/tests/port/*.sql'))

# A fixture id, a shell variable holding one (USER_ID is 9990002), one of
# the two test phone numbers, or the credit-note high-water mark.
SCOPED = re.compile(r'999\d{4}|\$CUST|\$ITEM|\$ORDER|\$USER_ID|\$id\b'
                    r'|9995550001|9990001234|15972')
# Up to the semicolon, quotes and all: stopping at a quote truncated the
# clause before the value that scopes it, and every teardown keyed on a
# test phone number looked unscoped.
STMT = re.compile(r'DELETE\s+FROM\s+([`\w]+)([^;]*)', re.I)

# Tables the port owns outright: nothing in them predates the harness.
OURS = {'tbl_outbound_stub_log'}

passed, loose = 0, []
for path in sorted(set(FILES)):
    try:
        text = open(path, errors='replace').read()
    except OSError:
        continue
    for m in STMT.finditer(text):
        table, where = m.group(1).strip('`'), m.group(2)
        line = text[:m.start()].count('\n') + 1
        if table in OURS:
            passed += 1
        elif 'WHERE' not in where.upper():
            loose.append((path, line, table, '(no WHERE clause at all)'))
        elif SCOPED.search(where):
            passed += 1
        else:
            loose.append((path, line, table, ' '.join(where.split())[:70]))

for path, line, table, why in loose:
    print('  %s:%d deletes from %s scoped by %s' % (path, line, table, why))
print('  passed: %d   mismatched: %d' % (passed, len(loose)))
sys.exit(1 if loose else 0)
