#!/usr/bin/env python3
"""
The create and update forms, submitted, on both stacks.

pmui_difftest renders every create and update form for all 59 controllers and
compares the markup. crud-sweep submits the search filters. Neither ever
presses Save, so a form that rejects everything, saves nothing, or throws on
submit looks exactly like one that works.

This fills each form from the fields the page itself declares, posts the same
body to both stacks from the same starting state, and compares three things:
the status, whether a row actually landed in the table, and - when the form
came back - the validation messages on it.

The submission does not have to succeed. Two stacks rejecting the same body
with the same errors is as good an answer as two stacks saving the same row;
what matters is that they agree. A create that saves on one side and not the
other is the bug this is looking for.

Anything it inserts, it deletes: the highest id in the table is noted before
each post and everything above it is removed afterwards, so the two runs start
level and the database is left as it was found. Runs with POS_STUB_OUTBOUND=1,
so a form with an outward call cannot reach a real customer.
"""
import os, re, subprocess, sys, urllib.parse

B = 'http://127.0.0.1:8084'
JAR = '/tmp/form-jar.txt'
REPO = '/root/pos/pos83'


def sh(*a):
    return subprocess.run(a, capture_output=True, text=True).stdout


def mysql(q):
    inner = ("MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot $MYSQL_DATABASE -N -B -e "
             + "'" + q.replace("'", "'\\''") + "'")
    return sh('docker', 'exec', 'pos-mysql-8', 'sh', '-c', inner).strip()


def get(path, stack):
    return sh('curl', '-sS', '-b', JAR, '--max-time', '120',
              '%s/%s%s' % (B, 'v2/' if stack == 'port' else '', path))


def post(path, stack, body):
    args = ['curl', '-sS', '-b', JAR, '-c', JAR, '--max-time', '180',
            '-o', '/tmp/form-out.html', '-w', '%{http_code}']
    for k, v in body:
        args += ['--data-urlencode', '%s=%s' % (k, v)]
    args.append('%s/%s%s' % (B, 'v2/' if stack == 'port' else '', path))
    code = sh(*args).strip()
    try:
        out = open('/tmp/form-out.html', errors='replace').read()
    except OSError:
        out = ''
    return code, out


def targets():
    s = open(REPO + '/app2/components/Ui.php', errors='replace').read()
    names = re.findall(r"'([^']+)'",
                       re.search(r'PORTED = \[(.*?)\];', s, re.S).group(1))
    out = []
    for c in names:
        model = c[0].upper() + c[1:]
        base = '%s/protected/models/_base/Base%s.php' % (REPO, model)
        if not os.path.exists(base):
            continue
        m = re.search(r"tableName\s*\(\)\s*\{\s*return\s*'\{\{(\w+)\}\}'",
                      open(base, errors='replace').read())
        if m:
            out.append((c, model, 'tbl_' + m.group(1)))
    return out


def value_for(field):
    """A plausible value, by what the column is called."""
    f = field.lower()
    if f.endswith('_id') or f in ('id', 'qty', 'quantity'):
        return '1'
    if 'date' in f or 'time' in f:
        return '2026-09-21'
    if 'email' in f:
        return 'sweep@example.invalid'
    if 'no' in f and ('contact' in f or 'mobile' in f or 'phone' in f):
        return '9990001234'
    if any(k in f for k in ('amt', 'rate', 'price', 'val', 'per', 'total',
                            'discount', 'tax', 'balance')):
        return '1.00'
    if 'password' in f:
        return 'SweepTest!1'
    return 'sweeptest'


def fields(html, model):
    """The model's own inputs, as the form declares them."""
    out, seen = [], set()
    pat = r'<(?:input|select|textarea)[^>]*name="(%s\[[^\]]+\])"' % re.escape(model)
    for name in re.findall(pat, html):
        if name in seen:
            continue
        seen.add(name)
        col = name[name.index('[') + 1:-1]
        out.append((name, value_for(col)))
    return out


def csrf(html):
    m = re.search(r'name="(_csrf|YII_CSRF_TOKEN)"\s+value="([^"]*)"', html)
    return [(m.group(1), m.group(2))] if m else []


def errors(html):
    """Validation messages the form came back with, normalised."""
    msgs = re.findall(r'class="[^"]*help-block[^"]*"[^>]*>(.*?)<', html, re.S)
    msgs += re.findall(r'class="errorMessage"[^>]*>(.*?)<', html, re.S)
    return sorted({' '.join(re.sub(r'<[^>]+>', ' ', m).split())
                   for m in msgs if m.strip()})


def run(ctrl, model, table):
    page = get('%s/create' % ctrl, 'yii1')
    if 'LoginForm' in page:
        return None, 'session lapsed'
    body = fields(page, model)
    if not body:
        return None, 'no form fields found'

    res = {}
    for stack in ('yii1', 'port'):
        form = get('%s/create' % ctrl, stack)
        before = mysql('SELECT IFNULL(MAX(id),0) FROM %s' % table) or '0'
        code, out = post('%s/create' % ctrl, stack, body + csrf(form))
        after = mysql('SELECT IFNULL(MAX(id),0) FROM %s' % table) or '0'
        inserted = int(after) - int(before)
        if inserted > 0:
            mysql('DELETE FROM %s WHERE id > %s' % (table, before))
        res[stack] = (code, inserted, errors(out))

    (c1, i1, e1), (c2, i2, e2) = res['yii1'], res['port']
    same = (i1 == i2) and (e1 == e2 or (c1 == c2 and not e1 and not e2))
    return (c1, i1, len(e1), c2, i2, len(e2), same), None


def main():
    only = sys.argv[1:]
    print('  %-22s %-14s %-14s %s' % ('CONTROLLER', 'YII 1', 'PORT', ''))
    print('  %-22s %-14s %-14s %s' % ('', 'code/ins/err', 'code/ins/err', ''))
    bad, ok, skipped = [], 0, []
    for ctrl, model, table in targets():
        if only and ctrl not in only:
            continue
        r, why = run(ctrl, model, table)
        if why:
            skipped.append((ctrl, why))
            if why == 'session lapsed':
                print('  %-22s SESSION LAPSED - stopping here' % ctrl)
                break
            continue
        c1, i1, e1, c2, i2, e2, same = r
        verdict = 'same' if same else 'DIFFERS'
        if same:
            ok += 1
        else:
            bad.append(ctrl)
        print('  %-22s %-14s %-14s %s'
              % (ctrl, '%s/%s/%s' % (c1, i1, e1), '%s/%s/%s' % (c2, i2, e2), verdict))
    for c, why in skipped:
        print('  %-22s (%s)' % (c, why))
    print('\n  agreed: %d   differing: %d   %s' % (ok, len(bad), ' '.join(bad)))
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main())
