#!/usr/bin/env python3
"""
Data-level comparison of a ported web-UI controller against the Yii 1 one.

Markup cannot be compared byte for byte: two frameworks never emit the same
HTML, so the API suites' approach does not carry over. What must match is what
the page *says* - the rows in the grid, the values in the detail table, the
fields and current values in the form. Each page is reduced to that, and the
reductions are compared.

    ui-difftest.py <controller> <ModelClass> <grid-id> [row-id] [filter=value ...]

e.g. ui-difftest.py paymentMode PaymentMode payment-mode-grid 1 type_id=1
"""
import re, subprocess, sys, json, os

BASE = 'http://127.0.0.1:8084'
# The suite script logs in and passes its own cookie jar; the default is there
# so a comparison can also be run by hand against an existing session.
COOKIE = os.environ.get('COOKIE_FILE', '/tmp/uic.txt')


def fetch(path):
    out = subprocess.run(
        ['curl', '-sS', '-b', COOKIE, '--max-time', '120', BASE + path],
        capture_output=True, text=True)
    return out.stdout


def status(path):
    out = subprocess.run(
        ['curl', '-sS', '-o', '/dev/null', '-w', '%{http_code}',
         '-b', COOKIE, '--max-time', '120', BASE + path],
        capture_output=True, text=True)
    return out.stdout.strip()


def check_pair(name, y1, y2, reduce_fn):
    """
    Compare two pages, allowing for ones that legitimately refuse.

    Some actions take a required id and answer 400 without one -
    bill/create is declared actionCreate($id). Both stacks then render no
    form, and the right question is whether they refused the same way, not
    whether the empty reductions match.
    """
    s1, s2 = status(y1), status(y2)
    if s1 != '200' or s2 != '200':
        if s1 == s2:
            print(f'  ok    {name} (both {s1})')
        else:
            FAIL.append(name)
            print(f'  FAIL  {name}: yii1 answered {s1}, yii2 answered {s2}')
        return
    check(name, reduce_fn(fetch(y1)), reduce_fn(fetch(y2)))


def strip(html):
    html = re.sub(r'(?is)<script.*?</script>', ' ', html)
    html = re.sub(r'(?is)<style.*?</style>', ' ', html)
    return html


def grid_rows(html, grid_id):
    """The data cells of the named grid, row by row."""
    html = strip(html)
    m = re.search(r'id="' + re.escape(grid_id) + r'"(.*?)(?:</table>)', html, re.S)
    if not m:
        return None
    rows = []
    for tr in re.findall(r'(?is)<tr[^>]*>(.*?)</tr>', m.group(1)):
        if '<th' in tr.lower():
            continue          # header and filter rows
        cells = []
        for td in re.findall(r'(?is)<td[^>]*>(.*?)</td>', tr):
            # link targets differ by design; only the visible text is compared
            text = re.sub(r'(?is)<[^>]+>', ' ', td).replace('&nbsp;', ' ')
            cells.append(' '.join(text.split()))
        if cells:
            rows.append(cells)
    return rows


def detail_pairs(html):
    """label/value pairs of a detail table."""
    pairs = []
    clean = lambda s: ' '.join(re.sub(r'(?is)<[^>]+>', ' ', s).split())
    for tr in re.findall(r'(?is)<tr[^>]*>(.*?)</tr>', strip(html)):
        th = re.findall(r'(?is)<th[^>]*>(.*?)</th>', tr)
        td = re.findall(r'(?is)<td[^>]*>(.*?)</td>', tr)
        if len(th) == 1 and len(td) == 1:
            pairs.append((clean(th[0]), clean(td[0])))
    return pairs


def form_fields(html):
    """input/select names and their current values."""
    out = {}
    for tag in re.findall(r'(?is)<(?:input|select|textarea)[^>]*>', strip(html)):
        name = re.search(r'name="([^"]+)"', tag)
        if not name:
            continue
        n = name.group(1)
        if n.startswith('_') or n.lower() in ('yii_csrf_token', '_csrf'):
            continue
        val = re.search(r'value="([^"]*)"', tag)
        out[n] = val.group(1) if val else ''
    return out


FAIL = []


def check(name, a, b, require=True):
    """
    Compare, and refuse to call an empty comparison a pass.

    Two pages that both failed to render, or a session that quietly expired,
    produce equal and empty reductions. That is the failure mode that makes a
    suite look green while testing nothing, so emptiness is a failure unless
    the case is explicitly allowed to be empty.
    """
    if require and not a:
        FAIL.append(name + ' (nothing compared)')
        print(f'  FAIL  {name}: yii1 produced nothing to compare - the page did '
              f'not render, or the session is not logged in')
        return
    if a == b:
        print(f'  ok    {name}')
        return
    FAIL.append(name)
    print(f'  FAIL  {name}')
    if isinstance(a, list) and isinstance(b, list):
        if len(a) != len(b):
            print(f'        row count: yii1={len(a)} yii2={len(b)}')
        for i in range(min(len(a), len(b))):
            if a[i] != b[i]:
                print(f'        first differing row {i}:')
                print(f'          yii1: {json.dumps(a[i])}')
                print(f'          yii2: {json.dumps(b[i])}')
                break
        for extra, who in ((a[len(b):], 'yii1'), (b[len(a):], 'yii2')):
            for row in extra[:3]:
                print(f'        only in {who}: {json.dumps(row)}')
    else:
        print(f'        yii1: {json.dumps(a)[:400]}')
        print(f'        yii2: {json.dumps(b)[:400]}')


def main():
    if len(sys.argv) < 4:
        print(__doc__)
        sys.exit(2)
    ctrl, model, grid = sys.argv[1], sys.argv[2], sys.argv[3]
    row_id = sys.argv[4] if len(sys.argv) > 4 else None
    filters = [a for a in sys.argv[5:] if '=' in a]

    y1, y2 = f'/{ctrl}', f'/v2/{ctrl}'

    print(f'{ctrl}/admin  - grid rows')
    check('admin rows',
          grid_rows(fetch(y1 + '/admin'), grid),
          grid_rows(fetch(y2 + '/admin'), grid))

    # Page 2 is where an ordering difference shows that page 1 hides: with no
    # ORDER BY the two stacks can agree on the first ten rows and still
    # disagree on which rows are left over.
    print(f'{ctrl}/admin  - page 2')
    check('admin page 2',
          grid_rows(fetch(f'{y1}/admin/{model}_page/2'), grid),
          grid_rows(fetch(f'{y2}/admin?page=2'), grid),
          # a table with ten rows or fewer has no second page, and that is not
          # a broken test - but page 1 above must have produced rows
          require=False)

    for f in filters:
        k, v = f.split('=', 1)
        print(f'{ctrl}/admin  - filtered {k}={v}')
        q = f'?{model}%5B{k}%5D={v}'
        check(f'admin filter {k}',
              grid_rows(fetch(y1 + '/admin' + q), grid),
              grid_rows(fetch(y2 + '/admin' + q), grid),
              require=False)

    print(f'{ctrl}/index  - list rows')
    check('index rows',
          grid_rows(fetch(y1 + '/index'), grid),
          grid_rows(fetch(y2 + '/index'), grid))

    print(f'{ctrl}/create - form fields')
    check_pair('create form', y1 + '/create', y2 + '/create',
               lambda h: sorted(form_fields(h).items()))

    if row_id:
        print(f'{ctrl}/view/{row_id} - detail pairs')
        check_pair('view detail', f'{y1}/view/id/{row_id}', f'{y2}/view?id={row_id}',
                   detail_pairs)

        print(f'{ctrl}/update/{row_id} - form values')
        check_pair('update form', f'{y1}/update/id/{row_id}', f'{y2}/update?id={row_id}',
                   lambda h: sorted(form_fields(h).items()))

    print()
    if FAIL:
        print(f'FAILED {len(FAIL)}: {", ".join(FAIL)}')
        sys.exit(1)
    print(f'all {ctrl} UI comparisons match')


main()
