#!/usr/bin/env python3
"""
No table has lost a row since the 5.6 database was copied.

The suites write, and they tear down what they wrote. A teardown is a DELETE
with a WHERE clause, and a WHERE clause that names a value instead of a
fixture id will match rows the fixture never created:

    DELETE FROM tbl_credit_note WHERE amt IN (100, 200, 300, 1000);

The refund suite raises credit notes for round amounts. So does the
application. That line ran at the end of every full run from the day it was
written and took 601 real credit notes with it - the oldest from 2018, 96,700
between them - and nothing noticed, because a suite that checks the port
against Yii 1 sees both stacks reading the same database and agreeing.

They were recoverable only because the 5.6 database is never written to. This
check is what makes that recoverable state visible: the count is taken over
the baseline's own id range, so fixture rows, which are all numbered from
9990000 and above every table's real maximum, cannot mask a loss.

It does not compare contents. `order/updatetax` rewrote 8,925 order items
without changing the count of them, and no row-count check would have seen it;
that is what the write sweep and the value comparisons are for.
"""
import shlex, subprocess, sys

PORT = ('pos-mysql-8', 'pos_live')
BASE = ('pos-mysql-legacy', '$MYSQL_DATABASE')


def q(where, sql):
    container, db = where
    inner = 'MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot %s -N -B -e %s' % (
        db, shlex.quote(sql))
    return subprocess.run(['docker', 'exec', container, 'sh', '-c', inner],
                          capture_output=True, text=True).stdout.strip()


def main():
    tables = q(PORT, "SELECT TABLE_NAME FROM information_schema.tables "
                     "WHERE TABLE_SCHEMA='pos_live' AND TABLE_TYPE='BASE TABLE' "
                     "ORDER BY TABLE_NAME").split('\n')
    passed, lost = 0, []
    for t in tables:
        hi = q(BASE, 'SELECT MAX(id) FROM `%s`' % t)
        if not hi or hi == 'NULL':
            continue          # not in the baseline, or empty there
        sql = 'SELECT COUNT(*) FROM `%s` WHERE id <= %s' % (t, hi)
        a, b = q(PORT, sql), q(BASE, sql)
        if not a or not b:
            continue
        if int(a) < int(b):
            lost.append((t, int(b) - int(a), a, b))
        else:
            passed += 1

    for t, n, a, b in lost:
        print('  %s has lost %d rows (port %s, baseline %s)' % (t, n, a, b))
    if lost:
        print('  restore from the 5.6 database before running anything else.')
    print('  passed: %d   mismatched: %d' % (passed, len(lost)))
    return 1 if lost else 0


if __name__ == '__main__':
    sys.exit(main())
