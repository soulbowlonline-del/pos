#!/usr/bin/env python3
"""
Web-UI differential crawl: PHP 5.6 (:8082) against PHP 8.3 (:8084).

The API has a differential harness; the web UI has none, and it is the bulk of
the application - 60 controllers and 663 views. This does the first thing worth
doing: find out which pages PHP 8 broke.

It requests each action on both stacks with a logged-in session and compares
the status codes. Bodies are not compared: the two stacks have separate
databases, so the HTML differs for reasons that have nothing to do with PHP.
A page that is 200 on 5.6 and 500 on 8.3 is a regression this upgrade caused.

SAFETY. Yii 1 routes answer GET, and plenty of actions that mutate will happily
do so on a GET - actionDelete among them. So this does NOT crawl everything it
finds. Only actions whose names match READ_PATTERNS are requested, and anything
matching DESTRUCTIVE is refused even if it also matches a read pattern. The
cost is coverage: roughly a third of the actions are never visited, and a
mutating action that is broken on PHP 8 will not be found this way. That needs
a fixture-backed harness per controller, not a crawl.
"""
import re, os, sys, json, subprocess, time
from collections import defaultdict

ROOT = '/root/pos/pos83/protected/controllers'
LEGACY = 'http://127.0.0.1:8082'
PHP83 = 'http://127.0.0.1:8084'
TIMEOUT = 12

READ_PATTERNS = re.compile(
    r'^(index|admin|view|list|show|search|dashboard|detail|details|summary|history'
    r'|report|reports|print|export|csv|excel|chart|graph|stat|stats|log|logs'
    r'|get[A-Z_a-z]*|.*report.*|.*excel.*|.*csv.*|.*print.*|.*list.*|.*view.*)$', re.I)

DESTRUCTIVE = re.compile(
    r'delete|remove|destroy|drop|truncate|purge|clear|reset|restore|backup|install'
    r'|approve|reject|cancel|refund|save|create|update|edit|add|import|upload|send'
    r'|login|logout|register|assign|ship|complete|punch|adjust|pay|generate|mail',
    re.I)

def controller_id(filename):
    name = filename[:-len('Controller.php')]
    return name[0].lower() + name[1:]

def actions(path):
    src = open(path, encoding='utf-8', errors='replace').read()
    out = []
    for m in re.finditer(r'public\s+function\s+action([A-Za-z0-9_]+)\s*\(([^)]*)\)', src):
        name, args = m.group(1), m.group(2)
        required = len([a for a in args.split(',') if a.strip() and '=' not in a])
        out.append((name, required))
    return out

def fetch(base, route, jar):
    try:
        r = subprocess.run(
            ['curl', '-sS', '-o', '/dev/null', '-w', '%{http_code}',
             '--max-time', str(TIMEOUT), '-b', jar, '-c', jar, base + route],
            capture_output=True, text=True, timeout=TIMEOUT + 10)
        return r.stdout.strip() or 'ERR'
    except subprocess.TimeoutExpired:
        return 'TIMEOUT'

rows = []
skipped = defaultdict(list)
files = sorted(f for f in os.listdir(ROOT) if f.endswith('Controller.php'))
for f in files:
    cid = controller_id(f)
    for name, required in actions(os.path.join(ROOT, f)):
        route = '/%s/%s' % (cid, name[0].lower() + name[1:])
        if DESTRUCTIVE.search(name):
            skipped['mutating'].append(route); continue
        if not READ_PATTERNS.match(name):
            skipped['not-a-read'].append(route); continue
        if required > 0:
            skipped['needs-arguments'].append(route); continue
        rows.append(route)

print('controllers: %d   routes to crawl: %d   skipped: %s'
      % (len(files), len(rows),
         ', '.join('%s %d' % (k, len(v)) for k, v in sorted(skipped.items()))))
sys.stdout.flush()

results = []
for i, route in enumerate(rows, 1):
    a = fetch(LEGACY, route, '/tmp/cj8082.txt')
    b = fetch(PHP83, route, '/tmp/cj8084.txt')
    results.append((route, a, b))
    flag = '' if a == b else '   <-- differs'
    if a != b or b.startswith('5') or b == 'TIMEOUT':
        print('  %-52s 5.6=%-7s 8.3=%-7s%s' % (route, a, b, flag))
        sys.stdout.flush()

json.dump({'results': results, 'skipped': skipped}, open('/root/pos/uicrawl.json', 'w'), indent=1)

same = sum(1 for _, a, b in results if a == b)
worse = [(r, a, b) for r, a, b in results if a != b and (b.startswith('5') or b == 'TIMEOUT')]
better = [(r, a, b) for r, a, b in results if a != b and (a.startswith('5') or a == 'TIMEOUT') and not (b.startswith('5') or b == 'TIMEOUT')]
print('\n  identical status: %d / %d' % (same, len(results)))
print('  worse on 8.3:     %d' % len(worse))
print('  better on 8.3:    %d' % len(better))
