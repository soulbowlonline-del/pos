#!/usr/bin/env python3
"""
Triage a PHPStan run for the one defect family the sweep is about: reads of
variables that may never have been assigned.

    python3 tests/port/triage-sweep.py phpstan.txt [--show BUCKET]

PHPStan reports every "might not be defined" it cannot prove, and most of them
are shapes it simply cannot see through - a fallback array literal that is
never empty, a loop guarded by a truthiness check on the thing it iterates.
This sorts them by where the variable is written relative to where it is read,
using brace depth rather than indentation, so the list that needs human
attention is the last bucket rather than all 257 of them.

Run it from the application root. docs/php8-fragility-sweep.md is generated
from its output.
"""
import sys

import re, os, json
from collections import defaultdict

ROOT = os.getcwd()
ARG = sys.argv[1] if len(sys.argv) > 1 else 'phpstan.txt'
raw = open(ARG, encoding='utf-8', errors='replace').read()

definite, conditional = [], []
for line in raw.split('\n'):
    m = re.match(r'/app/(\S+?):(\d+):(.*)$', line.strip())
    if not m:
        continue
    path, lineno, msg = m.group(1), int(m.group(2)), m.group(3)
    mv = re.match(r'Undefined variable: \$(\w+)', msg)
    if mv:
        definite.append((path, lineno, mv.group(1)))
        continue
    mv = re.match(r'Variable \$(\w+) might not be defined\.', msg)
    if mv:
        conditional.append((path, lineno, mv.group(1)))

cache = {}
def lines_of(path):
    if path not in cache:
        p = os.path.join(ROOT, path)
        cache[path] = open(p, encoding='utf-8', errors='replace').read().split('\n') if os.path.exists(p) else None
    return cache[path]

def enclosing_function(lines, lineno):
    for i in range(lineno - 1, -1, -1):
        m = re.search(r'function\s+(\w+)\s*\(', lines[i])
        if m:
            d, seen = 0, False
            for j in range(i, len(lines)):
                d += lines[j].count('{') - lines[j].count('}')
                if '{' in lines[j]: seen = True
                if seen and d <= 0:
                    return m.group(1), i, j + 1
            return m.group(1), i, len(lines)
    return '', 0, len(lines)

def depths(body):
    out, d = [], 0
    for ln in body:
        out.append(d)
        d += re.sub(r'(//|#).*$', '', ln).count('{') - re.sub(r'(//|#).*$', '', ln).count('}')
    return out

buckets = defaultdict(list)
for path, lineno, var in conditional:
    lines = lines_of(path)
    if lines is None or lineno > len(lines):
        buckets['reachable'].append((path, lineno, var)); continue
    if var == 'this':
        buckets['noise'].append((path, lineno, var)); continue

    fname, fs, fe = enclosing_function(lines, lineno)
    body = lines[fs:fe]
    dep = depths(body)
    ridx = lineno - 1 - fs
    if not (0 <= ridx < len(body)):
        buckets['reachable'].append((path, lineno, var)); continue

    joined = '\n'.join(body)
    if ('olumns' in (fname or '') and var == 'columns'
            and re.search(r'\$selected\s*=\s*array\s*\(', joined)
            and re.search(r'if\s*\(\s*\$selected\s*\)', joined)):
        buckets['column-builder'].append((path, lineno, var)); continue

    W = re.compile(r'(?<![\w$])\$%s\s*(\[[^\]]*\]\s*)?(=(?!=)|\.=|\+=)' % re.escape(var))
    P = re.compile(r'(?<![\w$])\$%s\s*=(?!=)' % re.escape(var))
    writes = [i for i, ln in enumerate(body) if W.search(ln) and i != ridx]
    if not writes:
        buckets['reachable'].append((path, lineno, var)); continue

    rd = dep[ridx]
    if any(P.search(body[i]) and i < ridx and dep[i] <= rd for i in writes):
        buckets['assigned-before'].append((path, lineno, var))
    elif min(dep[i] for i in writes) > rd:
        buckets['reachable'].append((path, lineno, var))
    else:
        buckets['write-encloses-read'].append((path, lineno, var))

print('definite:    %d' % len(definite))
print('conditional: %d' % len(conditional))
for k in ('noise', 'column-builder', 'assigned-before', 'write-encloses-read', 'reachable'):
    print('  %-22s %d' % (k, len(buckets[k])))

api = [f for f in buckets['reachable'] if '/modules/api/' in f[0]]
print('\n  of the reachable ones, %d are in the API module and %d elsewhere'
      % (len(api), len(buckets['reachable']) - len(api)))

out = {'definite': definite, **{k: v for k, v in buckets.items()}}
json.dump(out, open('sweep-triage.json', 'w'), indent=1)
print('\nwritten: sweep-triage.json')

if '--show' in sys.argv:
    bucket = sys.argv[sys.argv.index('--show') + 1]
    for path, lineno, var in sorted(out.get(bucket, [])):
        lines = lines_of(path)
        print('=' * 72)
        print('%s:%d   $%s' % (path, lineno, var))
        if lines:
            for i in range(max(0, lineno - 7), min(len(lines), lineno + 2)):
                print(' %s %5d %s' % ('>>' if i == lineno - 1 else '  ', i + 1, lines[i][:135]))
