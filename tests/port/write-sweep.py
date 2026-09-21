#!/usr/bin/env python3
"""
Compare what a write action *writes*, on both stacks.

The read-only sweep asks whether a page answers. For an action whose whole
purpose is to change a row, that is the wrong question: two stacks can both
answer 200 and write different things, or one can write nothing at all.

So each action is run twice, from the same reloaded fixture state, and what
it wrote is read out of the binary log - which is in ROW format here, so the
server itself will list the table and the operation for every change. That
costs time proportional to what changed rather than to the size of the
database, which matters when one table has five million rows in it.

    reload fixtures -> note the log position -> request Yii 1 -> read back
    reload fixtures -> note the log position -> request the port -> read back

and the two sequences of (table, operation) are compared, along with the
status. Only actions the classifier says write and do nothing else are run:
anything that also reaches outward stays on the excluded list, because a
stubbed call is still a call.

A GET to most of these does nothing - they guard their writes behind
`isset($_POST[...])` - and that is a real answer too: both stacks must do
nothing in the same way.
"""
import json, os, re, shlex, subprocess, sys

BASE = 'http://127.0.0.1:8084'
COOKIE = '/tmp/write-sweep-cookie.txt'
FIXTURES = ['setup_test', 'emp_fixture', 'otp_fixture', 'cust_last_fixture',
            'grn_fixture', 'online_fixture', 'refund_fixture', 'adjust_fixture',
            'ui_fixture', 'perm_fixture']


def sh(cmd):
    return subprocess.run(cmd, shell=True, capture_output=True, text=True).stdout


def mysql(q, db=True):
    """
    Run one statement, without a shell in the way.

    The query is quoted for the `sh -c` inside the container with
    shlex.quote() and the outer command is a list, so nothing here has to
    guess at escaping. It used to be one long shell string, and a query
    containing a single quote - `SHOW BINLOG EVENTS IN 'binlog.000004'` -
    closed the shell's own quoting and returned nothing at all. That is the
    query this whole comparison rests on, so every action looked as though it
    had written nothing.

    The database has to be selected for anything but SHOW MASTER STATUS.
    """
    inner = 'MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot %s -N -B -e %s' % (
        '$MYSQL_DATABASE' if db else '', shlex.quote(q))
    r = subprocess.run(['docker', 'exec', 'pos-mysql-8', 'sh', '-c', inner],
                       capture_output=True, text=True)

    return r.stdout


def reset():
    for f in FIXTURES:
        sh("docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD "
           "$MYSQL_DATABASE' < /root/pos/%s.sql >/dev/null 2>&1" % f)


def master_pos():
    row = mysql('SHOW MASTER STATUS', db=False).split()
    return (row[0], row[1]) if len(row) >= 2 else (None, None)


OPS = {'Write_rows': 'insert', 'Update_rows': 'update', 'Delete_rows': 'delete'}


def changes_since(logfile, pos):
    """The (table, operation) pairs written since that log position."""
    out = mysql("SHOW BINLOG EVENTS IN '%s' FROM %s" % (logfile, pos))
    seq, table = [], None
    for line in out.split('\n'):
        m = re.search(r'Table_map.*?\(([^)]+)\)', line)
        if m:
            table = m.group(1)
            continue
        for ev, op in OPS.items():
            if ev in line and table:
                seq.append((table, op))
    return seq


def fetch(url, jar):
    r = subprocess.run(['curl', '-s', '-b', jar, '-c', jar, '--max-time', '90',
                        '-o', '/dev/null', '-w', '%{http_code}', url],
                       capture_output=True, text=True)
    return r.stdout.strip()


def keep_session(jar):
    """
    Re-establish the session if it has lapsed.

    This sweep makes two requests and two fixture reloads per action, so a run
    is long: user/reorder and user/timer were recorded as 200 against 302
    because the port had started redirecting to the login form while Yii 1 had
    not yet noticed.
    """
    if fetch('%s/v2/paymentMode/admin' % BASE, jar) != '200':
        sh('cd /root/pos && COOKIE_FILE=%s ./uilogin.sh >/dev/null 2>&1' % jar)


def run(route, jar):
    reset()
    # Before the log position is noted, not after: signing in writes
    # tbl_user.last_action_time, and that write belongs to the harness rather
    # than to the action being measured.
    #
    # Both halves need this, not just the first. user/logout ends the session,
    # so the Yii 1 half ran signed in and wrote, and the port half arrived a
    # guest, redirected, and wrote nothing - a difference in the sweep, not in
    # the port. Any action that ends a session has the same shape.
    keep_session(jar)
    logfile, pos = master_pos()
    code = fetch('%s/%s' % (BASE, route), jar) if '/v2/' not in route \
        else fetch('%s%s' % (BASE, route), jar)
    return code, changes_since(logfile, pos)


TABLE_CACHE = {}


def table_of(ctrl):
    """The table behind a controller's model, from its giix base class."""
    if ctrl in TABLE_CACHE:
        return TABLE_CACHE[ctrl]
    model = ctrl[0].upper() + ctrl[1:]
    path = '/root/pos/pos83/protected/models/_base/Base%s.php' % model
    table = None
    if os.path.exists(path):
        m = re.search(r"tableName\s*\(\)\s*\{\s*return\s*'\{\{(\w+)\}\}'",
                      open(path, errors='replace').read())
        table = m.group(1) if m else None
    TABLE_CACHE[ctrl] = table
    return table


def row_id(ctrl):
    """
    A real row to act on - not a fixture row.

    The fixtures are reloaded between the two runs, so a fixture row would be
    reset underneath the comparison; a real row is the same for both.
    """
    table = table_of(ctrl)
    if not table:
        return None
    out = mysql('SELECT id FROM tbl_%s WHERE id < 9990000 ORDER BY id DESC LIMIT 1'
                % table).strip()
    return out or None


ACTION_SRC_CACHE = {}


def loops_over_saves(ctrl, action):
    """
    True when the Yii 1 action saves models inside a loop.

    An action that saves one model is a form handler. An action that saves
    inside a `foreach` is a maintenance script somebody left in a controller,
    and running it rewrites the table.

    order/updatetax is the one that made this necessary. It ignores its
    argument, selects every order item created between two dates hard-coded in
    the source - 2021-08-01 to 2021-08-12, 21,030 rows - recomputes each one's
    price, tax and total and saves it. On a plain GET, with no confirmation.
    Sweeping it rewrote 8,925 of those rows in the port's database and left
    afterSave() appending a tbl_item_velocity row per save for hours after the
    request that started it had been abandoned; that is what made a whole
    sweep's write comparison unreadable. The rows were restored from the 5.6
    database, which this sweep never touches.
    """
    key = ctrl.lower()
    if key not in ACTION_SRC_CACHE:
        name = ctrl[0].upper() + ctrl[1:]
        path = '/root/pos/pos83/protected/controllers/%sController.php' % name
        ACTION_SRC_CACHE[key] = (open(path, errors='replace').read()
                                 if os.path.exists(path) else '')
    src = ACTION_SRC_CACHE[key]

    m = re.search(r'function\s+action%s\s*\(' % re.escape(action), src, re.I)
    if not m:
        return False
    # A required parameter was taken to mean the action is scoped to one row -
    # mrn/approve($id) saves the lines of one note, not of every note. It does
    # not mean that. order/updateOrders($id) takes a required $id and then
    # walks `id >= $id` a thousand rows at a time, rewriting each order's
    # total from its lines; the sweep ran it, and it is only luck that those
    # thousand totals were already right. A parameter says where a loop
    # starts, not how far it goes, and nothing in the source reliably says
    # that.
    #
    # So the exemption is gone. Fourteen actions became twenty, and six of
    # those twenty are ordinary row-scoped work whose writes now go
    # uncompared. That is the trade, taken deliberately: they are listed by
    # name in the output, and a wrong refusal costs coverage while a wrong
    # run costs rows.

    # The body, by brace counting from the first `{` after the signature.
    i = src.find('{', m.end())
    if i < 0:
        return False
    depth, j = 0, i
    while j < len(src):
        if src[j] == '{':
            depth += 1
        elif src[j] == '}':
            depth -= 1
            if depth == 0:
                break
        j += 1
    body = src[i:j]

    for f in re.finditer(r'\bforeach\s*\(', body):
        k = body.find('{', f.end())
        if k < 0:
            continue
        d, e = 0, k
        while e < len(body):
            if body[e] == '{':
                d += 1
            elif body[e] == '}':
                d -= 1
                if d == 0:
                    break
            e += 1
        if re.search(r'->\s*(save|saveAttributes|updateByPk|deleteByPk)\s*\(',
                     body[k:e]):
            saves_in_loop = True
            break
    else:
        return False

    # An ajax handler also saves in a loop - over the rows it was posted - and
    # refusing those would cost most of this sweep's coverage. What separates
    # them is input: a form handler reads the request and does nothing without
    # it, and a maintenance script takes none, because its scope is written
    # into the source. Of the 38 actions that save in a loop, 8 read nothing.
    if re.search(r'\$_(POST|GET|REQUEST|FILES)\b|->\s*request\s*->', body):
        return False
    return saves_in_loop


def query_for(t):
    """The query string an action needs, or None when this cannot supply one."""
    if loops_over_saves(t['ctrl'], t['action']):
        return None
    if t['action'].lower().startswith('delete'):
        # Not run. itemExpireItem/delete removes the row on a plain GET - see
        # docs/live-bugs-found.md - and the row it would be given is a real
        # one, because a fixture row is reset between the two halves of the
        # comparison and a real one is not. Four rows were deleted before this
        # guard existed; they were restored from the 5.6 database, which still
        # had them.
        return None
    if not t['args']:
        return ''
    if t['args'] != 1 or not t.get('params'):
        return None
    rid = row_id(t['ctrl'])
    return '?%s=%s' % (t['params'][0], rid) if rid else None


def main():
    all_targets = json.load(open('/tmp/sweep-write-targets.json'))
    targets = []
    skipped = []
    bulk = []
    for t in all_targets:
        name = '%s/%s' % (t['ctrl'], t['action'])
        if loops_over_saves(t['ctrl'], t['action']):
            bulk.append(name)
            continue
        q = query_for(t)
        if q is None:
            skipped.append(name)
            continue
        t['query'] = q
        targets.append(t)
    sh('cd /root/pos && COOKIE_FILE=%s ./uilogin.sh >/dev/null 2>&1' % COOKIE)
    if fetch('%s/paymentMode/admin' % BASE, COOKIE) != '200':
        print('  not signed in - nothing swept')
        sys.exit(2)

    # Failures already confirmed against the 5.6 baseline, as the read-only
    # sweep does. order/updateDetail exhausts memory on both stacks and they
    # only differ in how they say so.
    known = set()
    kp = '/root/pos/pos83/tests/port/known-action-failures.txt'
    if os.path.exists(kp):
        for line in open(kp):
            line = line.strip()
            if line and not line.startswith('#'):
                known.add(re.split(r'\s{2,}', line, maxsplit=1)[0].strip())

    same, status_diff, write_diff, wrote = 0, [], [], 0
    for t in targets:
        keep_session(COOKIE)
        route = '%s/%s%s' % (t['ctrl'], t['action'], t['query'])
        c1, w1 = run(route, COOKIE)
        c2, w2 = run('/v2/' + route, COOKIE)
        if w1 or w2:
            wrote += 1
        plain = '%s/%s' % (t['ctrl'], t['action'])
        if c1 != c2:
            if plain in known:
                same += 1
            else:
                status_diff.append((route, c1, c2))
        elif w1 != w2:
            write_diff.append((route, w1, w2))
        else:
            same += 1

    print('swept %d write actions on both stacks' % len(targets))
    if bulk:
        print('  not run (rewrite a table on a GET): %d' % len(bulk))
        for name in sorted(set(bulk)):
            print('      %s' % name)
    if skipped:
        print('  not run (no row, or deletes): %d' % len(skipped))
    print('  answered and wrote the same:  %d' % same)
    print('  status differs:               %d' % len(status_diff))
    print('  wrote different things:       %d' % len(write_diff))
    print('  actions that wrote at all:    %d' % wrote)

    for title, rows in (('STATUS DIFFERS', status_diff),
                        ('WROTE DIFFERENT THINGS', write_diff)):
        if rows:
            print('\n=== %s ===' % title)
            for r in rows:
                if title == 'STATUS DIFFERS':
                    print('  %-42s %s %s' % r)
                else:
                    route, a, b = r
                    print('  %s' % route)
                    print('     yii1: %s' % (a or 'nothing'))
                    print('     port: %s' % (b or 'nothing'))

    reset()


if __name__ == '__main__':
    main()
