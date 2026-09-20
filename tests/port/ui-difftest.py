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
import re, subprocess, sys, json, os, time

BASE = 'http://127.0.0.1:8084'
# The suite script logs in and passes its own cookie jar; the default is there
# so a comparison can also be run by hand against an existing session.
COOKIE = os.environ.get('COOKIE_FILE', '/tmp/uic.txt')


# Pages that take longer than the default budget on *both* stacks, and the
# reason. Without an entry here the port reads as a timeout - curl reports 000
# - and a page that renders correctly in four minutes is recorded as a
# mismatch rather than compared.
#
# order/create renders a checkbox list over the whole of tbl_order_item,
# 4,976,355 rows. Yii 1 takes about 108 seconds and sometimes exhausts memory
# instead; the port takes about 220. The page is unusable on either stack -
# see docs/live-bugs-found.md - but it can still be compared, and comparing it
# is better than excusing it.
SLOW = ('/order/create', '/order/update')


def budget(path):
    return '400' if any(p in path for p in SLOW) else '120'


def fetch(path):
    out = subprocess.run(
        ['curl', '-sS', '-b', COOKIE, '--max-time', budget(path), BASE + path],
        capture_output=True, text=True)
    return out.stdout


def status(path):
    out = subprocess.run(
        ['curl', '-sS', '-o', '/dev/null', '-w', '%{http_code}',
         '-b', COOKIE, '--max-time', budget(path), BASE + path],
        capture_output=True, text=True)
    return out.stdout.strip()


def known_yii1_failures():
    """
    Pages confirmed to fail on the untouched 5.6 baseline.

    Keyed by "<controller>/<case name>". A page here is not counted as a
    mismatch when Yii 1 is the side that fails, because the port cannot match a
    page that crashes. Confirmed with tools/port/baseline_check.sh before being
    listed - Yii 1 failing on :8084 alone would not be evidence, since this
    work could have caused that.
    """
    path = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                        'known-yii1-failures.txt')
    known = {}
    if not os.path.exists(path):
        return known
    for line in open(path, encoding='utf-8'):
        line = line.strip()
        if not line or line.startswith('#'):
            continue
        # Split on a run of spaces, not on one. The case name can itself
        # contain a space - "admin page 2" - and splitting on single spaces
        # made the key "itemStock/admin page", which never matched.
        parts = re.split(r'\s{2,}', line, maxsplit=1)
        if parts[0]:
            known[parts[0].strip()] = parts[1].strip() if len(parts) > 1 else ''
    return known


KNOWN = known_yii1_failures()


def unordered_listings():
    """
    Listings where neither stack defines an order.

    `GxActiveRecord::defaultScope()` puts `id DESC` on most models, but some
    override it to an empty array and their search() sets no order either. For
    those the row *sequence* is whatever the storage engine returns, and the
    two engines disagree - so comparing sequences asserts something Yii 1 does
    not promise. The rows themselves are still the contract and are still
    compared.

    This is not a way to make a red case green: a page is listed here only
    after reading the model and confirming that defaultOrder() is null and
    search() sets no order. If either does, a sequence difference is a real
    difference and must stay a failure.
    """
    path = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                        'unordered-listings.txt')
    out = {}
    if not os.path.exists(path):
        return out
    for line in open(path, encoding='utf-8'):
        line = line.strip()
        if not line or line.startswith('#'):
            continue
        parts = re.split(r'\s{2,}', line, maxsplit=1)
        if parts[0]:
            out[parts[0].strip()] = parts[1].strip() if len(parts) > 1 else ''
    return out


UNORDERED = unordered_listings()


def volatile_fields(url):
    """
    Form fields whose value changes between two loads of the same page.

    Some fields are generated fresh each time - Item's create form puts a
    random item_code in a hidden input - so they can never match across two
    stacks, and comparing them reports a difference that is not one.

    Rather than keep a list of exceptions, the page is loaded twice on the
    *same* stack and whatever changed is excluded. A field that is stable in
    Yii 1 is still compared.
    """
    first, second = form_fields(fetch(url)), form_fields(fetch(url))

    return {k for k in first if first.get(k) != second.get(k)}


def check_pair(name, y1, y2, reduce_fn, require='nonempty'):
    """
    Compare two pages, allowing for ones that legitimately refuse.

    Some actions take a required id and answer 400 without one -
    bill/create is declared actionCreate($id). Both stacks then render no
    form, and the right question is whether they refused the same way, not
    whether the empty reductions match.
    """
    keep_session()
    s1, s2 = status(y1), status(y2)
    if s1 != '200' or s2 != '200':
        if s1 == s2:
            # Neither page rendered, so there was nothing to compare. Two
            # error pages agreeing is not agreement, and counting it as a pass
            # is the exact failure this suite exists to avoid: every one of
            # b2bPurchaseBill's seven pages is 500 on both stacks - its view
            # directory is spelled protected/views/b2bpurchaseBill and Yii 1
            # looks for b2bPurchaseBill - and the controller was reported
            # green for as long as the port happened to fail too. The moment
            # the port started rendering them, the "agreement" became seven
            # mismatches.
            NOTHING.append(name)
            print(f'  none  {name}: both stacks answered {s1}; nothing compared')
            return
        key = CTRL + '/' + name
        if s1 == '500' and key in KNOWN:
            print(f'  ok    {name}: known - {KNOWN[key]}')
            return
        FAIL.append(name)
        print(f'  FAIL  {name}: yii1 answered {s1}, yii2 answered {s2}')
        return
    check(name, reduce_fn(fetch(y1)), reduce_fn(fetch(y2)), require)


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


def grid_headers(html, grid_id):
    """
    The column headings of the named grid.

    grid_rows() drops every row containing a <th>, so a heading has never been
    compared - and headings are where the generated labels show. Eleven models
    were printing the related model's label where Yii 1 prints the column's
    own: "Item" against Yii 1's "Item Id", on grids whose rows matched to the
    character. The suite was green throughout.

    The filter row is a <th> row as well, and holds inputs rather than text,
    so the first heading row with any text in it is the one taken.
    """
    html = strip(html)
    m = re.search(r'id="' + re.escape(grid_id) + r'"(.*?)(?:</table>)', html, re.S)
    if not m:
        return None
    for tr in re.findall(r'(?is)<tr[^>]*>(.*?)</tr>', m.group(1)):
        cells = re.findall(r'(?is)<th[^>]*>(.*?)</th>', tr)
        if not cells:
            continue
        out = []
        for th in cells:
            text = re.sub(r'(?is)<[^>]+>', ' ', th).replace('&nbsp;', ' ')
            out.append(' '.join(text.split()))
        if any(out):
            return out
    return None


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
NOTHING = []
CTRL = ''


def check(name, a, b, require='nonempty'):
    """
    Compare, and refuse to call an empty comparison a pass.

    Two pages that both failed to render, or a session that quietly expired,
    produce equal and empty reductions - the failure mode that makes a suite
    look green while testing nothing. But an empty result is not always that:
    a listing with no rows is a real observation, and both stacks should agree
    on it. So the two are distinguished.

      'notnone'  - the thing had to be *found* (grid_rows returns None when
                   the grid is not on the page at all, [] when it is there
                   with no data rows).
      'nonempty' - the reduction also had to have content, for pages like a
                   detail view where emptiness means it did not render.
      None       - no requirement; the case may legitimately be empty.
    """
    if require == 'notnone' and a is None:
        FAIL.append(name + ' (nothing compared)')
        print(f'  FAIL  {name}: yii1 has no such grid on the page - '
              f'nothing was compared')
        return
    if require == 'nonempty' and not a:
        # Both sides empty, with both having answered 200 upstream, is the two
        # stacks agreeing - itemReturnItem/create renders nothing on the 5.6
        # baseline too. That is not a mismatch, and it is not a verified case
        # either: nothing was compared. It gets its own outcome so it can never
        # be counted as coverage.
        if not b:
            NOTHING.append(name)
            print(f'  none  {name}: both stacks render an empty page - '
                  f'nothing was compared')
            return
        FAIL.append(name + ' (nothing compared)')
        print(f'  FAIL  {name}: yii1 produced nothing to compare - the page did '
              f'not render, or the session is not logged in')
        return
    if a == b:
        print(f'  ok    {name}')
        return
    key = CTRL + '/' + name
    if key in UNORDERED and isinstance(a, list) and isinstance(b, list):
        if sorted(a) == sorted(b):
            # The whole listing is here and both stacks hold the same rows.
            # Only the sequence differs, and neither stack defines one.
            print(f'  ok    {name}: same rows in a different order - '
                  f'{UNORDERED[key]}')
            return
        if len(a) == len(b):
            # Paginated as well as unordered: which ten of fifty thousand rows
            # land on page one is not defined by either stack, so there is
            # nothing here to be right or wrong about. Not a pass - nothing was
            # compared - and it is counted as such.
            NOTHING.append(name)
            print(f'  none  {name}: unordered paginated listing, {len(a)} rows '
                  f'each - which rows appear is undefined ({UNORDERED[key]})')
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


def signed_in():
    """
    Whether the cookie jar still has a session.

    PHP's session lifetime here is 24 minutes and a long batch runs past it.
    An expired session makes Yii 1 answer the login form, whose reduction is
    empty - so every page looks like a mismatch and the controller looks like
    a regression. Checking once, up front, turns that into one clear message.
    """
    # The status, not a string in the body: Yii 1's error page for a guest
    # renders a source excerpt that happens to contain the grid's id, so
    # searching the body reports a guest as signed in.
    return status('/paymentMode/admin') == '200'


def keep_session():
    """
    Re-establish the session if it has lapsed, before comparing a page.

    Checking once at the start is not enough. PHP's session lifetime here is
    24 minutes and a single controller can run longer than that on its own -
    order/create and order/update take about four minutes each. When it lapses
    mid-controller, Yii 1 answers a 500 (its admin layout reads role_id on a
    null user) while the port redirects, and the case is recorded as a
    mismatch. Two of those appeared in a run where every page was in fact
    correct.

    The probe is one request, so it is rate-limited rather than run before
    every case.
    """
    now = time.time()
    if now - keep_session.checked < 120:
        return
    keep_session.checked = now
    if signed_in():
        return
    subprocess.run(['bash', '/root/pos/uilogin.sh'],
                   capture_output=True, text=True)
    keep_session.checked = time.time()


keep_session.checked = 0.0


def main():
    if len(sys.argv) < 4:
        print(__doc__)
        sys.exit(2)

    if not signed_in():
        print('  NOT LOGGED IN: the session in %s has expired or was never '
              'established.\n  Nothing was compared. Run uilogin.sh and retry.'
              % COOKIE)
        sys.exit(2)
    global CTRL
    ctrl, model, grid = sys.argv[1], sys.argv[2], sys.argv[3]
    CTRL = ctrl
    row_id = sys.argv[4] if len(sys.argv) > 4 else None
    filters = [a for a in sys.argv[5:] if '=' in a]

    y1, y2 = f'/{ctrl}', f'/v2/{ctrl}'

    print(f'{ctrl}/admin  - grid rows')
    check_pair('admin rows', y1 + '/admin', y2 + '/admin',
               lambda h: grid_rows(h, grid), 'notnone')

    print(f'{ctrl}/admin  - grid headings')
    check_pair('admin headings', y1 + '/admin', y2 + '/admin',
               lambda h: grid_headers(h, grid), 'notnone')

    # Page 2 is where an ordering difference shows that page 1 hides: with no
    # ORDER BY the two stacks can agree on the first ten rows and still
    # disagree on which rows are left over.
    print(f'{ctrl}/admin  - page 2')
    check_pair('admin page 2', f'{y1}/admin/{model}_page/2', f'{y2}/admin?page=2',
               lambda h: grid_rows(h, grid),
               # a table of ten rows or fewer has no second page, and that is
               # not a broken test - but page 1 above must have had a grid
               None)

    for f in filters:
        k, v = f.split('=', 1)
        print(f'{ctrl}/admin  - filtered {k}={v}')
        q = f'?{model}%5B{k}%5D={v}'
        check_pair(f'admin filter {k}', y1 + '/admin' + q, y2 + '/admin' + q,
                   lambda h: grid_rows(h, grid), None)

    print(f'{ctrl}/index  - list rows')
    # index renders the _list partial, whose grid may carry a different id
    # from the admin one; find whichever id the Yii 1 page actually used.
    index_grid = grid
    y1_index = fetch(y1 + '/index')
    if grid_rows(y1_index, grid) is None:
        m = re.search(r'id="([a-z0-9-]*grid[a-z0-9-]*)"', y1_index)
        if m:
            index_grid = m.group(1)
    check_pair('index rows', y1 + '/index', y2 + '/index',
               lambda h: grid_rows(h, index_grid), 'notnone')

    print(f'{ctrl}/create - form fields')
    volatile = volatile_fields(y1 + '/create')
    if volatile:
        print('        ignoring fields Yii 1 regenerates each load: '
              + ', '.join(sorted(volatile)))
    check_pair('create form', y1 + '/create', y2 + '/create',
               lambda h: sorted((k, v) for k, v in form_fields(h).items()
                                if k not in volatile))

    if row_id:
        print(f'{ctrl}/view/{row_id} - detail pairs')
        check_pair('view detail', f'{y1}/view/id/{row_id}', f'{y2}/view?id={row_id}',
                   detail_pairs)

        print(f'{ctrl}/update/{row_id} - form values')
        volatile = volatile_fields(f'{y1}/update/id/{row_id}')
        if volatile:
            print('        ignoring fields Yii 1 regenerates each load: '
                  + ', '.join(sorted(volatile)))
        check_pair('update form', f'{y1}/update/id/{row_id}', f'{y2}/update?id={row_id}',
                   lambda h: sorted((k, v) for k, v in form_fields(h).items()
                                    if k not in volatile))

    print()
    if NOTHING:
        print(f'NOTHING COMPARED {len(NOTHING)}: {", ".join(NOTHING)}')
    if FAIL:
        print(f'FAILED {len(FAIL)}: {", ".join(FAIL)}')
        sys.exit(1)
    print(f'all {ctrl} UI comparisons match')


main()
