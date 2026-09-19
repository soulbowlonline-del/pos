import os, re, subprocess, collections

ROOT = '/root/pos/pos/protected/controllers'
asked = collections.defaultdict(set)          # url -> controllers asking for it
for f in sorted(os.listdir(ROOT)):
    if not f.endswith('Controller.php'):
        continue
    src = open(os.path.join(ROOT, f), encoding='utf-8', errors='replace').read()
    for m in re.finditer(r"checkPermission\s*\(\s*'([^']+)'", src):
        asked[m.group(1)].add(f[:-len('Controller.php')])

out = subprocess.run(
    ['docker', 'exec', 'pos-mysql-8', 'sh', '-c',
     'MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot $MYSQL_DATABASE -N -B '
     '-e "SELECT url FROM tbl_permission"'],
    capture_output=True, text=True)
have = {l.strip() for l in out.stdout.splitlines() if l.strip()}
lower = {u.lower(): u for u in have}

exact, casewrong, absent = [], [], []
for url in sorted(asked):
    if url in have:
        exact.append(url)
    elif url.lower() in lower:
        casewrong.append((url, lower[url.lower()]))
    else:
        absent.append(url)

print('checkPermission() urls in the Yii 1 controllers: %d' % len(asked))
print('  match a tbl_permission row exactly : %d' % len(exact))
print('  differ only in letter case         : %d   <- refused to every role' % len(casewrong))
print('  no such row at all                 : %d   <- refused to every role' % len(absent))
print()
print('case mismatches (asked -> stored):')
for a, b in casewrong:
    print('  %-34s -> %s   [%s]' % (a, b, ', '.join(sorted(asked[a]))))
print()
print('absent entirely:')
for a in absent:
    print('  %-34s [%s]' % (a, ', '.join(sorted(asked[a]))))
