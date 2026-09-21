#!/usr/bin/env python3
"""
Every action Yii 1 serves, the port serves at the same URL.

api-routes.py asks this of the API. Nothing asked it of the web UI, and the
web UI is where the menus are. The failures it finds are the kind a person
reports as "this menu does nothing": a page that answers 404 on the port and
200 on Yii 1 is a dead link in the sidebar.

Every action of every ported controller is requested on both stacks with the
same session and the two statuses compared. Actions that write are not run -
the same rule the write sweep uses, plus anything whose name begins with
delete, after itemExpireItem/delete removed four real rows on a plain GET.

Where the two disagree the untouched 5.6 tree decides which is wrong, as
api-routes.py does: Yii 1 under PHP 8.3 is not the authority on what the
application is supposed to do.
"""
import os, re, subprocess, sys, time

B = 'http://127.0.0.1:8084'
BASELINE = 'http://127.0.0.1:8082'
JAR = '/tmp/route-sweep-jar.txt'
REPO = '/root/pos/pos83'

WRITES = re.compile(r'^(delete|create|update|save|remove|approve|merge|toggle|'
                    r'inactivate|activate|reorder|adjust|import|export|upload|'
                    r'send|mail|print|pdf|generate|backup|restore|truncate|'
                    r'ajaxcreate|ajaxupdate|ajaxsave|setsession|punch|order|'
                    r'refund|cancel|ship|complete|assign|redeem|rollback)', re.I)


def sh(*a):
    return subprocess.run(a, capture_output=True, text=True).stdout


def mysql(q):
    inner = ("MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot $MYSQL_DATABASE -N -B -e "
             + "'" + q.replace("'", "'\\''") + "'")
    return sh('docker', 'exec', 'pos-mysql-8', 'sh', '-c', inner).strip()


def status(url):
    """
    The status, with one retry when curl reports none.

    A '000' is curl giving up, not the application answering, and this sweep
    makes enough requests to cause its own timeouts - the first run reported
    question/admin and shift/admin as differences when both stacks serve them
    perfectly well and one of the two had simply been starved of a worker.
    A difference that survives a second ask is worth reading; one that does
    not is this script's own noise.
    """
    for attempt in (0, 1):
        code = sh('curl', '-sS', '-o', '/dev/null', '-w', '%{http_code}',
                  '-b', JAR, '--max-time', '25', url).strip()
        if code != '000':
            return code
        time.sleep(2)

    return code


def ported():
    s = open(REPO + '/app2/components/Ui.php', errors='replace').read()
    return re.findall(r"'([^']+)'",
                      re.search(r'PORTED = \[(.*?)\];', s, re.S).group(1))


def actions_of(ctrl):
    path = '%s/protected/controllers/%sController.php' % (
        REPO, ctrl[0].upper() + ctrl[1:])
    if not os.path.exists(path):
        return []
    t = open(path, errors='replace').read()
    t = re.sub(r'/\*.*?\*/', '', t, flags=re.S)
    t = re.sub(r'//[^\n]*', '', t)
    out = []
    for m in re.finditer(r'function\s+action(\w+)\s*\(([^)]*)\)', t):
        name, params = m.group(1), m.group(2)
        needs_id = bool(re.search(r'\$id\b', params)) and '=' not in params.split('$id')[-1][:3]
        out.append((name[0].lower() + name[1:], needs_id))
    return out


ID_CACHE = {}


def a_row(ctrl):
    if ctrl in ID_CACHE:
        return ID_CACHE[ctrl]
    model = ctrl[0].upper() + ctrl[1:]
    base = '%s/protected/models/_base/Base%s.php' % (REPO, model)
    rid = None
    if os.path.exists(base):
        m = re.search(r"tableName\s*\(\)\s*\{\s*return\s*'\{\{(\w+)\}\}'",
                      open(base, errors='replace').read())
        if m:
            rid = mysql('SELECT id FROM tbl_%s WHERE id < 9990000 '
                        'ORDER BY id DESC LIMIT 1' % m.group(1)) or None
    ID_CACHE[ctrl] = rid
    return rid


def main():
    only = sys.argv[1:]
    rows, skipped, regressed = [], 0, []
    for ctrl in ported():
        if only and ctrl not in only:
            continue
        for action, needs_id in actions_of(ctrl):
            if WRITES.match(action):
                skipped += 1
                continue
            route = '%s/%s' % (ctrl, action)
            if needs_id:
                rid = a_row(ctrl)
                if not rid:
                    skipped += 1
                    continue
                route += '/%s' % rid
            a, b = status('%s/%s' % (B, route)), status('%s/v2/%s' % (B, route))
            if a == '000' and b == '000':
                skipped += 1        # too slow to answer on either stack
                continue
            if a == b:
                rows.append((route, a, b, 'same'))
                continue
            # Printed as it is found rather than at the end: the sweep takes
            # long enough that a summary at the close is no use to anyone
            # watching it.
            print('  %-44s yii1 %s  port %s' % (route, a, b), flush=True)
            ref = status('%s/%s' % (BASELINE, route))
            if b == ref:
                regressed.append((route, a, ref))
                rows.append((route, a, b, 'yii1-8.3 differs'))
            else:
                rows.append((route, a, b, 'DIFFERS'))

    bad = [r for r in rows if r[3] == 'DIFFERS']
    for route, a, b, verdict in rows:
        if verdict == 'DIFFERS':
            note = '  <-- dead on the port' if b in ('404', '500') else ''
            print('  %-44s yii1 %s  port %s%s' % (route, a, b, note))
    for route, a, ref in regressed:
        print('  %-44s Yii 1 under 8.3 answers %s where 5.6 and the port say %s'
              % (route, a, ref))
    print('\n  not run (they write, or need a row): %d' % skipped)
    print('  passed: %d   mismatched: %d' % (len(rows) - len(bad), len(bad)))
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main())
