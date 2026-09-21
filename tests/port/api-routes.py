#!/usr/bin/env python3
"""
Every API action answers at the URL Yii 1 gives it.

The clients are built against Yii 1's spelling. The .NET application and the
Android app send /api/customer/countryList, because that is the URL that has
always existed - and the port answered only /v2/api/customer/country-list,
because Yii 2 prefers hyphens and the six API routes passed the action id
through untouched.

Every multi-word action in the API was a 404 for those clients, and every
suite was green, because the suites were written against the port's spelling
rather than the one the clients use. A differential test only compares what
it thinks to ask for.

So this asks for the Yii 1 name, every one of them, and compares the status
the two stacks return. Status only: most of these need parameters, a body or
a session, and what matters here is that the route resolves - a 404 from the
port against anything else from Yii 1 means the client cannot reach it.

POST-only actions are asked with POST and no body, which both stacks answer
the same way.
"""
import re, os, subprocess, sys

BASE = 'http://127.0.0.1:8084'
# The untouched PHP 5.6 tree. Nothing writes to it, and it is the only
# reference that predates both this work and PHP 8.3.
BASELINE = 'http://127.0.0.1:8082'
Y1 = '/root/pos/pos83/protected/modules/api/controllers'
POST_ONLY = {'loyalty', 'emp'}
# Actions that change data. The point here is whether the route resolves, and
# that can be settled without running them.
SKIP = re.compile(r'^(add|update|delete|cancel|ship|complete|assign|refund|'
                  r'adjust|order|punch|billupdate|updatestock|redeem|'
                  r'preredeem|rollback|sendotp|verifyotp|sentwhat|verifywhat|'
                  r'stockreturn|ordertest|adjustitemtozero|scanneditem|'
                  r'reprint|orderupdate|login|recover)', re.I)


def actions():
    out = []
    for f in sorted(os.listdir(Y1)):
        if not f.endswith('Controller.php'):
            continue
        ctrl = f[:-len('Controller.php')]
        ctrl = ctrl[0].lower() + ctrl[1:]
        if ctrl == 'default':
            continue
        text = open(os.path.join(Y1, f), errors='replace').read()
        text = re.sub(r'/\*.*?\*/', '', text, flags=re.S)
        text = re.sub(r'//[^\n]*', '', text)
        for m in re.finditer(r'function\s+action([A-Za-z0-9_]+)\s*\(', text):
            a = m.group(1)
            out.append((ctrl, a[0].lower() + a[1:]))
    return sorted(set(out))


def body(url):
    """The response itself, for the cases where the payload is the point."""
    r = subprocess.run(['curl', '-sS', '--max-time', '120', url],
                       capture_output=True, text=True)
    return r.stdout


def status(url, method):
    r = subprocess.run(['curl', '-sS', '-o', '/dev/null', '-w', '%{http_code}',
                        '--max-time', '90', '-X', method, url],
                       capture_output=True, text=True)
    return r.stdout.strip()


def main():
    passed, bad, skipped, regressed = 0, [], 0, []
    for ctrl, action in actions():
        if SKIP.match(action):
            skipped += 1
            continue
        method = 'POST' if ctrl in POST_ONLY else 'GET'
        a = status('%s/api/%s/%s' % (BASE, ctrl, action), method)
        b = status('%s/v2/api/%s/%s' % (BASE, ctrl, action), method)
        if a == b:
            passed += 1
            continue

        # Yii 1 in the PHP 8.3 tree is not the authority. tally/cashsale,
        # tally/b2btaxwise and tally/paymentreport answer 200 on the 5.6
        # baseline and throw a PDOException under 8.3, and the port returns
        # what 5.6 returns, byte for byte. Asking the baseline settles which
        # of the two is wrong without anyone having to maintain a list.
        ref = status('%s/api/%s/%s' % (BASELINE, ctrl, action), method)
        if b == ref:
            regressed.append((ctrl, action, a, ref))
            passed += 1
        else:
            bad.append((ctrl, action, a, b))

    for ctrl, action, a, b in bad:
        note = ' - the port does not route it' if b == '404' else ''
        print('  /api/%s/%s  yii1 %s, port %s%s' % (ctrl, action, a, b, note))
    # Yii 1's path-format parameters, which is how the .NET application asks.
    # CUrlManager appends GET arguments to the route as alternating name and
    # value segments, so the billing screen's item search sends
    # /api/item/search/name//rate//title/pepsi on every keystroke. The port
    # answered 404 to all of them - its rule took exactly three segments - and
    # no item ever reached the till, while every suite stayed green because
    # they all ask with a query string instead.
    for path in ('item/search/name//rate//title/pepsi',
                 'item/search/name//rate//title/pe',
                 'order/modes/type/1',
                 'customer/index/id/1'):
        a = body('%s/api/%s' % (BASE, path))
        b = body('%s/v2/api/%s' % (BASE, path))
        if a == b:
            passed += 1
        else:
            bad.append(('path-format', path, 'yii1 %db' % len(a),
                        'port %db' % len(b)))

    for ctrl, action, a, ref in regressed:
        print('  /api/%s/%s  Yii 1 under 8.3 answers %s where the 5.6 baseline'
              ' and the port both answer %s' % (ctrl, action, a, ref))
    print('  not run (they write): %d' % skipped)
    print('  passed: %d   mismatched: %d' % (passed, len(bad)))
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main())
