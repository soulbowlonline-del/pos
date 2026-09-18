#!/usr/bin/env python3
"""
Data-level comparison of a ported web-UI page against the Yii 1 one.

Markup cannot be compared byte for byte: two frameworks never emit the same
HTML, and the API suites' approach does not carry over. What must match is what
the page *says* - the rows in the grid, the values in the detail table, the
fields and current values in the form. So each page is reduced to that, and the
reductions are compared.
"""
import re, subprocess, sys, json, os

BASE = 'http://127.0.0.1:8084'
# The suite script logs in and passes its own cookie jar; the default is there
# so the comparison can also be run by hand against an existing session.
COOKIE = os.environ.get('COOKIE_FILE', '/tmp/uic.txt')

def fetch(path):
    out = subprocess.run(
        ['curl', '-sS', '-b', COOKIE, '--max-time', '120', BASE + path],
        capture_output=True, text=True)
    return out.stdout

def strip(html):
    html = re.sub(r'(?is)<script.*?</script>', ' ', html)
    html = re.sub(r'(?is)<style.*?</style>', ' ', html)
    return html

def grid_rows(html, grid_id):
    """The data cells of the named grid, row by row."""
    html = strip(html)
    # the table that follows the grid container id
    m = re.search(r'id="' + re.escape(grid_id) + r'"(.*?)(?:</table>)', html, re.S)
    if not m:
        return None
    body = m.group(1)
    rows = []
    for tr in re.findall(r'(?is)<tr[^>]*>(.*?)</tr>', body):
        if '<th' in tr.lower():
            continue          # header and filter rows
        cells = []
        for td in re.findall(r'(?is)<td[^>]*>(.*?)</td>', tr):
            # keep the link targets out; only the visible text is compared
            text = re.sub(r'(?is)<[^>]+>', ' ', td)
            text = re.sub(r'&nbsp;', ' ', text)
            cells.append(' '.join(text.split()))
        if cells:
            rows.append(cells)
    return rows

def detail_pairs(html):
    """label/value pairs of a detail table."""
    html = strip(html)
    pairs = []
    for tr in re.findall(r'(?is)<tr[^>]*>(.*?)</tr>', html):
        th = re.findall(r'(?is)<th[^>]*>(.*?)</th>', tr)
        td = re.findall(r'(?is)<td[^>]*>(.*?)</td>', tr)
        if len(th) == 1 and len(td) == 1:
            clean = lambda s: ' '.join(re.sub(r'(?is)<[^>]+>', ' ', s).split())
            pairs.append((clean(th[0]), clean(td[0])))
    return pairs

def form_fields(html):
    """input/select names and their current values."""
    html = strip(html)
    out = {}
    for tag in re.findall(r'(?is)<(?:input|select|textarea)[^>]*>', html):
        name = re.search(r'name="([^"]+)"', tag)
        if not name:
            continue
        n = name.group(1)
        if n.startswith('_') or n.lower() in ('yii_csrf_token',):
            continue
        val = re.search(r'value="([^"]*)"', tag)
        out[n] = val.group(1) if val else ''
    return out

FAIL = []
def check(name, a, b):
    if a == b:
        print(f'  ok    {name}')
    else:
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
    pm_id = sys.argv[1] if len(sys.argv) > 1 else None

    print('paymentMode/admin  - grid rows')
    check('admin rows',
          grid_rows(fetch('/paymentMode/admin'), 'payment-mode-grid'),
          grid_rows(fetch('/v2/paymentMode/admin'), 'payment-mode-grid'))

    print('paymentMode/admin  - filtered by type_id=1')
    q = '?PaymentMode%5Btype_id%5D=1'
    check('admin filtered',
          grid_rows(fetch('/paymentMode/admin' + q), 'payment-mode-grid'),
          grid_rows(fetch('/v2/paymentMode/admin' + q), 'payment-mode-grid'))

    print('paymentMode/admin  - filtered by title')
    q = '?PaymentMode%5Btitle%5D=a'
    check('admin title filter',
          grid_rows(fetch('/paymentMode/admin' + q), 'payment-mode-grid'),
          grid_rows(fetch('/v2/paymentMode/admin' + q), 'payment-mode-grid'))

    print('paymentMode/admin  - page 2')
    # Page 2 is where an ordering difference shows up that page 1 hides: with
    # no ORDER BY the two stacks can agree on the first ten rows and still
    # disagree on which rows are left.
    check('admin page 2',
          grid_rows(fetch('/paymentMode/admin/PaymentMode_page/2'), 'payment-mode-grid'),
          grid_rows(fetch('/v2/paymentMode/admin?page=2'), 'payment-mode-grid'))

    print('paymentMode/index  - list rows')
    check('index rows',
          grid_rows(fetch('/paymentMode/index'), 'payment-mode-grid'),
          grid_rows(fetch('/v2/paymentMode/index'), 'payment-mode-grid'))

    print('paymentMode/create - form fields')
    check('create form',
          sorted(form_fields(fetch('/paymentMode/create')).items()),
          sorted(form_fields(fetch('/v2/paymentMode/create')).items()))

    if pm_id:
        print(f'paymentMode/view/{pm_id} - detail pairs')
        check('view detail',
              detail_pairs(fetch(f'/paymentMode/view/id/{pm_id}')),
              detail_pairs(fetch(f'/v2/paymentMode/view?id={pm_id}')))

        print(f'paymentMode/update/{pm_id} - form values')
        check('update form',
              sorted(form_fields(fetch(f'/paymentMode/update/id/{pm_id}')).items()),
              sorted(form_fields(fetch(f'/v2/paymentMode/update?id={pm_id}')).items()))

    print()
    if FAIL:
        print(f'FAILED {len(FAIL)}: {", ".join(FAIL)}')
        sys.exit(1)
    print('all paymentMode UI comparisons match')

main()
