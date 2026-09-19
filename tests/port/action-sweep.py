#!/usr/bin/env python3
"""
Request every ported action the UI suite does not reach, on both stacks.

This is a smoke sweep, not a comparison: it asks whether the page answers at
all, and whether the port answers the same way Yii 1 does. That is a weaker
question than the difftest asks, and it is the question that would have caught
every bug found in the port so far - a method that does not exist, a Yii 1
class left in the output, 142 log lines writing var_export into the response
body.

Only actions a read-only GET cannot change anything through are requested; see
classify_actions.py. Because that judgement is made by reading code, every
table is checksummed before and after and any change is reported as a finding
in its own right - an action that writes on a GET is worth knowing about.
"""
import json, subprocess, sys, re, os

BASE = 'http://127.0.0.1:8084'
COOKIE = '/tmp/sweep-cookie.txt'
TARGETS = json.load(open('/tmp/sweep-targets.json'))


def sh(cmd):
    return subprocess.run(cmd, shell=True, capture_output=True, text=True).stdout


def mysql(q):
    return sh("docker exec pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD "
              '$MYSQL_DATABASE -N -e "%s"\' 2>/dev/null' % q.replace('"', '\\"'))


def checksums():
    tables = [t for t in mysql('SHOW TABLES').split() if t]
    out = {}
    for t in tables:
        r = mysql('CHECKSUM TABLE `%s`' % t).split()
        if len(r) >= 2:
            out[t] = r[-1]
    return out


def fetch(url):
    r = subprocess.run(['curl', '-s', '-b', COOKIE, '-c', COOKIE, '--max-time', '90',
                        '-w', '\n%{http_code}', url], capture_output=True, text=True)
    text = r.stdout
    code = text.rsplit('\n', 1)[-1].strip()
    return code, text.rsplit('\n', 1)[0]


def stray_output(body):
    """Something printed before the real response."""
    s = body.lstrip()
    if not s:
        return False
    return not (s.startswith('<') or s.startswith('{') or s.startswith('['))


def main():
    sh('cd /root/pos && COOKIE_FILE=%s ./uilogin.sh >/dev/null 2>&1' % COOKIE)
    code, _ = fetch(f'{BASE}/paymentMode/admin')
    if code != '200':
        print('  not signed in (paymentMode/admin answered %s) - nothing swept' % code)
        sys.exit(2)

    before = checksums()
    sh("docker exec pos-php-83 sh -c ': > /var/log/php_errors.log'")

    port_bugs, yii1_bugs, both, mismatched, stray, ok = [], [], [], [], [], 0
    for t in TARGETS:
        if t['args']:
            continue
        path = '%s/%s' % (t['ctrl'], t['action'])
        c1, b1 = fetch(f'{BASE}/{path}')
        c2, b2 = fetch(f'{BASE}/v2/{path}')
        if stray_output(b2) and not stray_output(b1):
            stray.append((path, b2.lstrip()[:60].replace('\n', ' ')))
        if c1 == c2:
            if c1 == '500':
                both.append(path)
            else:
                ok += 1
            continue
        if c1 == '200' and c2 == '500':
            port_bugs.append((path, c1, c2))
        elif c1 == '500' and c2 == '200':
            yii1_bugs.append((path, c1, c2))
        else:
            mismatched.append((path, c1, c2))

    after = checksums()
    changed = [t for t in after if before.get(t) != after.get(t)]

    print('swept %d actions on both stacks' % len([t for t in TARGETS if not t['args']]))
    print('  same status:                 %d' % ok)
    print('  port answers 500, Yii 1 200: %d' % len(port_bugs))
    print('  Yii 1 answers 500, port 200: %d' % len(yii1_bugs))
    print('  both answer 500:             %d' % len(both))
    print('  other status mismatch:       %d' % len(mismatched))
    print('  stray output from the port:  %d' % len(stray))
    print('  tables changed by the sweep: %d %s'
          % (len(changed), ', '.join(changed[:6]) if changed else ''))

    for title, rows in (('PORT FAILS WHERE YII 1 WORKS', port_bugs),
                        ('STATUS MISMATCH', mismatched),
                        ('STRAY OUTPUT', stray),
                        ('BOTH FAIL', both),
                        ('YII 1 FAILS, PORT WORKS', yii1_bugs)):
        if not rows:
            continue
        print('\n=== %s ===' % title)
        for r in rows[:40]:
            print('  ' + (r if isinstance(r, str) else
                          '%-40s %s' % (r[0], ' '.join(str(x) for x in r[1:]))))

    errs = sh("docker exec pos-php-83 sh -c 'wc -l < /var/log/php_errors.log'").strip()
    print('\nphp error log: %s lines' % errs)


main()
