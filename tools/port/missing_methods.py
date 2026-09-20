import os, re, sys, collections
sys.path.insert(0, '/root/pos')
exec(open('/root/pos/leftovers.py').read().split('found = collections')[0])

Y1 = '/root/pos/pos83/protected/models'
PORT = '/root/pos/pos83/app2/models'


def methods(text):
    return set(re.findall(r'function\s+(\w+)\s*\(', text))


def read(*paths):
    out = ''
    for p in paths:
        if os.path.exists(p):
            out += open(p, encoding='utf-8', errors='replace').read()
    return out


# every method name called anywhere in app2, as `Foo::bar(` or `->bar(`
called = collections.Counter()
for dirpath, _, files in os.walk('/root/pos/pos83/app2'):
    for f in sorted(files):
        if not f.endswith('.php'):
            continue
        live = mask(open(os.path.join(dirpath, f), encoding='utf-8',
                         errors='replace').read())
        for m in re.finditer(r'->\s*(\w+)\s*\(', live):
            called[m.group(1)] += 1
        for m in re.finditer(r'\b[A-Z]\w+::(\w+)\s*\(', live):
            called[m.group(1)] += 1

rows = []
for f in sorted(os.listdir(PORT)):
    if not f.endswith('.php'):
        continue
    cls = f[:-4]
    y1 = read(f'{Y1}/{cls}.php', f'{Y1}/_base/Base{cls}.php')
    if not y1:
        continue
    missing = sorted(m for m in methods(y1) - methods(read(f'{PORT}/{f}'))
                     if called.get(m) and not m.startswith('__'))
    if missing:
        rows.append((cls, missing))

total = sum(len(m) for _, m in rows)
print('%d methods the port is missing and something calls, across %d models\n'
      % (total, len(rows)))
for cls, missing in sorted(rows, key=lambda r: -len(r[1])):
    print('  %-24s %s' % (cls, ', '.join(missing)[:110]))
