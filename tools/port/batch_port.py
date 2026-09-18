#!/usr/bin/env python3
"""
Ports a list of CRUD controllers and verifies each against Yii 1.

Each one goes through the same three generators and is then compared page by
page. A controller that does not compare clean is reported and left out of
Ui::PORTED, so it stays on Yii 1 rather than shipping a page nobody checked.
"""
import subprocess, sys, os, re, json

ROOT = '/root/pos/pos83'


def run(cmd, **kw):
    return subprocess.run(cmd, shell=True, capture_output=True, text=True, **kw)


def lcfirst(s):
    return s[0].lower() + s[1:]


def grid_id(model):
    """The grid id the Yii 1 admin view uses, which the comparison keys on."""
    for v in ('admin', '_list'):
        p = f'{ROOT}/protected/views/{lcfirst(model)}/{v}.php'
        if os.path.exists(p):
            m = re.search(r"'id'\s*=>\s*'([^']+)'", open(p, encoding='utf-8', errors='replace').read())
            if m:
                return m.group(1)
    return None


def table_of(model):
    p = f'{ROOT}/protected/models/_base/Base{model}.php'
    m = re.search(r"tableName\(\)\s*\{\s*return\s*'\{\{(\w+)\}\}'",
                  open(p, encoding='utf-8', errors='replace').read())
    return m.group(1) if m else None


def a_row_id(table):
    q = (f"docker exec pos-mysql-8 sh -c "
         f"'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE -N -e "
         f'"SELECT id FROM tbl_{table} ORDER BY id DESC LIMIT 1"\' 2>/dev/null')
    out = run(q).stdout.strip()
    return out if out.isdigit() else None


def set_ported(names):
    p = f'{ROOT}/app2/components/Ui.php'
    s = open(p, encoding='utf-8').read()
    block = '    public const PORTED = [\n' + ''.join(
        "        '%s',\n" % n for n in names) + '    ];'
    s = re.sub(r'    public const PORTED = \[.*?\];', lambda m: block, s, flags=re.S)
    open(p, 'w', encoding='utf-8').write(s)


def current_ported():
    s = open(f'{ROOT}/app2/components/Ui.php', encoding='utf-8').read()
    m = re.search(r'public const PORTED = \[(.*?)\];', s, re.S)
    return re.findall(r"'([^']+)'", m.group(1))


def panel_views(ctrl):
    """
    Other controllers whose partials this one's views render.

    The argument list is not split on commas: the arguments contain commas of
    their own - $model->getRelatedDataProvider('items') - and splitting
    naively picked the wrong one and ported the wrong file. The view name is
    matched positionally on the quoted arguments instead.
    """
    out = set()
    vdir = f'{ROOT}/protected/views/{ctrl}'
    if not os.path.isdir(vdir):
        return out
    for f in os.listdir(vdir):
        if not f.endswith('.php'):
            continue
        src = open(os.path.join(vdir, f), encoding='utf-8', errors='replace').read()
        src = re.sub(r'/\*.*?\*/', '', src, flags=re.S)
        src = re.sub(r'//[^\n]*', '', src)
        for m in re.finditer(r"AddP?\w*Panel\s*\((.*?)\)\s*;", src, re.S):
            quoted = re.findall(r"'([^']*)'", m.group(1))
            for q in quoted:
                if (q and q != ctrl and not q.startswith('_')
                        and os.path.isdir(f'{ROOT}/protected/views/{q}')):
                    out.add(q)
    return out


def dependencies(model, ctrl):
    """Other models named by this one's relations or its views."""
    names = set()
    for p in [f'{ROOT}/protected/models/_base/Base{model}.php']:
        if os.path.exists(p):
            src = open(p, encoding='utf-8', errors='replace').read()
            m = re.search(r'function relations\(\)(.*?)\n\t\}', src, re.S)
            if m:
                names |= set(re.findall(r"self::\w+\s*,\s*'(\w+)'", m.group(1)))
    vdir = f'{ROOT}/protected/views/{ctrl}'
    if os.path.isdir(vdir):
        for f in os.listdir(vdir):
            if f.endswith('.php'):
                src = open(os.path.join(vdir, f), encoding='utf-8', errors='replace').read()
                names |= set(re.findall(r'\b([A-Z]\w+)::model\(\)', src))
                names |= set(re.findall(r'\b([A-Z]\w+)::get\w+', src))
    out = []
    for n in sorted(names):
        if n == model:
            continue
        if os.path.exists(f'{ROOT}/protected/models/_base/Base{n}.php'):
            out.append(n)
    return out


def main():
    models = sys.argv[1:]
    base = current_ported()
    results = []

    for model in models:
        ctrl = lcfirst(model)
        notes = []

        # Models this one's pages display - a relation shown in a grid, a
        # dropdown filled from another table. They need a string form and a
        # label, and nothing more: they are already in use by the API port, so
        # their validation and save behaviour is left exactly as it is.
        for dep in dependencies(model, ctrl):
            r = run(f'cd /root/pos && python3 port_model.py {dep} --presentation-only')
            if 'nothing to add' not in r.stdout:
                notes.append('dependency %s: %s' % (dep, r.stdout.strip().splitlines()[0]))

        for script in ('port_model.py', 'port_controller.py'):
            r = run(f'cd /root/pos && python3 {script} {model}')
            notes += [l.strip() for l in r.stdout.splitlines() if 'NOTE' in l]
            if r.returncode:
                results.append((model, 'GENERATOR FAILED', (r.stderr or r.stdout)[-300:]))
                break
        else:
            # Remove only views this pipeline generated. A hand-ported view
            # can already live in that directory - app2/views/item/_pdf.php is
            # the invoice template the punchorder API renders - and blowing the
            # directory away replaced it with a mechanical translation that
            # broke a suite which had been green for weeks.
            tracked = run(f'cd {ROOT} && git ls-files app2/views/{ctrl}').stdout.split()
            if os.path.isdir(f'{ROOT}/app2/views/{ctrl}'):
                for f in os.listdir(f'{ROOT}/app2/views/{ctrl}'):
                    rel = f'app2/views/{ctrl}/{f}'
                    if rel not in tracked:
                        os.remove(f'{ROOT}/{rel}')
            r = run(f'cd /root/pos && python3 port_views.py {ctrl} --keep-existing')
            notes += [l.strip() for l in r.stdout.splitlines() if 'UNCONVERTED' in l]

            # A view page's tabs render another controller's _list partial -
            # AddPanel($model->getRelatedDataProvider('items'), ..., 'item').
            # That file has to exist in the Yii 2 tree even though the item
            # controller itself is still served by Yii 1.
            for other in panel_views(ctrl):
                vdir = f'{ROOT}/protected/views/{other}'
                if not os.path.isdir(vdir):
                    continue
                for f in sorted(os.listdir(vdir)):
                    # Only the partials. A panel renders _list or _view from
                    # the other controller's directory; its full pages belong
                    # to that controller's own port.
                    if not f.startswith('_') or not f.endswith('.php'):
                        continue
                    if os.path.exists(f'{ROOT}/app2/views/{other}/{f}'):
                        continue
                    run(f'cd /root/pos && python3 port_views.py {other} --only {f}')
                    notes.append('also ported %s/%s for a relation panel' % (other, f))

            # syntax first - a parse error is not a comparison failure
            bad = run(f'for f in {ROOT}/app2/models/{model}.php '
                      f'{ROOT}/app2/controllers/{model}Controller.php '
                      f'{ROOT}/app2/views/{ctrl}/*.php; do '
                      f'docker exec pos-php-83 php -l "/var/www/html/${{f#{ROOT}/}}" '
                      f'>/dev/null 2>&1 || echo "$f"; done').stdout.strip()
            if bad:
                results.append((model, 'PARSE ERROR', bad.replace(ROOT + '/', '')))
                continue

            gid = grid_id(model)
            table = table_of(model)
            rid = a_row_id(table) if table else None
            if not gid:
                results.append((model, 'NO GRID ID', 'could not find the admin grid id'))
                continue

            set_ported(base + [ctrl])
            cmp = run(f'cd {ROOT} && COOKIE_FILE=/tmp/uic.txt python3 tests/port/ui-difftest.py '
                      f'{ctrl} {model} {gid} {rid or ""}')
            if cmp.returncode == 0:
                base.append(ctrl)
                results.append((model, 'MATCHES', notes))
            else:
                set_ported(base)      # leave it on Yii 1
                fails = [l.strip() for l in cmp.stdout.splitlines()
                         if l.strip().startswith('FAIL') or l.strip().startswith('first')
                         or l.strip().startswith('yii1:') or l.strip().startswith('yii2:')
                         or l.strip().startswith('only in') or l.strip().startswith('row count')]
                results.append((model, 'DIFFERS', fails[:10]))

    set_ported(base)
    print('\n================ batch result ================')
    ok = [r for r in results if r[1] == 'MATCHES']
    print('ported and matching: %d of %d' % (len(ok), len(models)))
    for model, status, detail in results:
        print('\n%-22s %s' % (model, status))
        if isinstance(detail, list):
            for d in detail:
                print('    ' + d[:160])
        elif detail:
            print('    ' + str(detail)[:300])
    print('\nUi::PORTED is now: ' + ', '.join(base))


main()
