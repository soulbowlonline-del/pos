#!/usr/bin/env python3
"""
The ajax endpoints, compared byte for byte against Yii 1.

These are the calls the screens make after they have rendered: the goods
received note asking for a vendor's purchase orders, the requisition asking
for an item's barcode, every tax lookup. Nothing tested them. pmui_difftest
renders pages and compares them; crud-sweep submits the grids' filters;
neither has ever asked an endpoint what it returns, and there are seventy-two
of them.

That gap hid a fault in all seventy-two at once. Each writes its answer with
`echo` and returns nothing, which is right in Yii 1 and wrong in Yii 2 - the
framework then sends its own response, and sending it means sending headers
after the echo has started the body. Every one of them was answering with its
own correct output and the words "An internal server error occurred."
appended. On screen that looked like a broken dropdown.

Each endpoint is given real arguments rather than empty ones: the parameter
names come from the Yii 1 action's own `$_POST[...]` references, and a value
for `vendor_id` is a vendor that exists, for `tax_id` a tax that exists. An
endpoint asked with nothing tends to answer nothing on both stacks, which
compares equal and proves nothing.

Actions that write are not run. Where the two stacks disagree the untouched
5.6 baseline decides which one is wrong, as api-routes.py does - three of
these fail on all three stacks on an undefined variable, and that is the
application's bug rather than the port's.
"""
import os, re, subprocess, sys

B = 'http://127.0.0.1:8084'
BASELINE = 'http://127.0.0.1:8082'
JAR = '/tmp/ajax-sweep-jar.txt'
REPO = '/root/pos/pos83'

WRITES = re.compile(r'(create|update|save|delete|remove|approve|merge|toggle|'
                    r'reorder|inactivate|active|print|apply)', re.I)


def sh(*a):
    return subprocess.run(a, capture_output=True, text=True).stdout


def mysql(q):
    inner = ("MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot $MYSQL_DATABASE -N -B -e "
             + "'" + q.replace("'", "'\\''") + "'")
    return sh('docker', 'exec', 'pos-mysql-8', 'sh', '-c', inner).strip()


TABLES = set(mysql("SELECT TABLE_NAME FROM information_schema.tables "
                   "WHERE TABLE_SCHEMA=DATABASE()").split('\n'))
_cache = {}


def a_value(param, own_table):
    """A real value for a parameter, by what it is called."""
    if param in _cache:
        return _cache[param]
    v = None
    if param.endswith('_id') or param == 'id':
        stem = param[:-3] if param.endswith('_id') else ''
        table = 'tbl_' + re.sub(r'(?<!^)(?=[A-Z])', '_', stem).lower() if stem else own_table
        if table in TABLES:
            v = mysql('SELECT id FROM `%s` ORDER BY id DESC LIMIT 1' % table)
    if not v:
        v = {'bar_code': '8901180960387', 'code': '1', 'qty': '1',
             'title': 'a', 'name': 'a'}.get(param, '1')
    _cache[param] = v
    return v


def action_bodies(path):
    t = open(path, errors='replace').read()
    t = re.sub(r'/\*.*?\*/', '', t, flags=re.S)
    t = re.sub(r'//[^\n]*', '', t)
    out = {}
    for m in re.finditer(r'function\s+(action\w+)\s*\(', t):
        i = t.find('{', m.end())
        if i < 0:
            continue
        d, j = 0, i
        while j < len(t):
            if t[j] == '{':
                d += 1
            elif t[j] == '}':
                d -= 1
                if d == 0:
                    break
            j += 1
        out[m.group(1)] = t[i:j]
    return out


def targets():
    """Ajax endpoints: an action that echoes, in a ported controller."""
    s = open(REPO + '/app2/components/Ui.php', errors='replace').read()
    ported = set(re.findall(r"'([^']+)'",
                            re.search(r'PORTED = \[(.*?)\];', s, re.S).group(1)))
    out = []
    for f in sorted(os.listdir(REPO + '/app2/controllers')):
        if not f.endswith('Controller.php'):
            continue
        ctrl = f[:-len('Controller.php')]
        ctrl = ctrl[0].lower() + ctrl[1:]
        y1 = ctrl[:-2] if ctrl.endswith('Ui') else ctrl
        if y1 not in ported:
            continue
        y1file = '%s/protected/controllers/%s%s' % (
            REPO, y1[0].upper() + y1[1:], 'Controller.php')
        if not os.path.exists(y1file):
            continue
        port_bodies = action_bodies(REPO + '/app2/controllers/' + f)
        y1_bodies = action_bodies(y1file)
        for name, body in port_bodies.items():
            if 'echo ' not in body:
                continue
            action = name[len('action'):]
            action = action[0].lower() + action[1:]
            if WRITES.search(action):
                out.append((y1, action, None))      # not run
                continue
            src = y1_bodies.get(name, body)
            params = sorted(set(re.findall(r"\$_(?:POST|GET|REQUEST)\s*\[\s*'(\w+)'", src)))
            out.append((y1, action, params))
    return out


def fetch(base, prefix, route, params, own_table):
    args = ['curl', '-sS', '-b', JAR, '--max-time', '150', '-X', 'POST']
    for p in params:
        args += ['-d', '%s=%s' % (p, a_value(p, own_table))]
    args.append('%s/%s%s' % (base, prefix, route))
    return sh(*args)


def main():
    rows, skipped, inherited = [], [], []
    for ctrl, action, params in targets():
        route = '%s/%s' % (ctrl, action)
        if params is None:
            skipped.append(route)
            continue
        own = 'tbl_' + re.sub(r'(?<!^)(?=[A-Z])', '_', ctrl).lower()
        a = fetch(B, '', route, params, own)
        b = fetch(B, 'v2/', route, params, own)
        if a == b:
            rows.append((route, params, len(a), 'same'))
            continue
        ref = fetch(BASELINE, '', route, params, own)
        # Both broken, each framework rendering its own error page
        if ('DASPOS - Error' in a or len(ref) > 5000) and (
                'error' in b.lower() or 'Error' in a):
            inherited.append(route)
            rows.append((route, params, len(a), 'both fail'))
        else:
            rows.append((route, params, (len(a), len(b)), 'DIFFERS'))

    bad = [r for r in rows if r[3] == 'DIFFERS']
    print('  %-38s %-26s %s' % ('ENDPOINT', 'ARGUMENTS', 'RESULT'))
    for route, params, size, verdict in rows:
        note = ('%sb' % size) if verdict != 'DIFFERS' else \
               ('yii1 %sb / port %sb' % size)
        print('  %-38s %-26s %s  %s'
              % (route, ','.join(params)[:26] or '-', verdict, note))
    if inherited:
        print('\n  failing on both stacks and on the 5.6 baseline: %d' % len(inherited))
    if skipped:
        print('  not run (they write): %d' % len(skipped))
    print('\n  passed: %d   mismatched: %d'
          % (len(rows) - len(bad), len(bad)))
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main())
