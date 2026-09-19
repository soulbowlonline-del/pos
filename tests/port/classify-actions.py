"""
Sort the ported controllers' actions by what a GET to them would actually do -
following the calls the action makes, not only its own body.

The first version read the action body alone. b2bPurchaseBill/checkConsignment
is two lines that call PurchaseBill::getConsignmentData(), and *that* curls an
external licence server and saves a Setting. The sweep requested it, and on the
Yii 1 side the call went out. So a method the action calls is now read too.

One level deep, not a full call graph: that is enough for the shape these
controllers have - a thin action over a model method - and the database
checksum around the sweep remains the backstop for anything it still misses.
"""
import re, os, json, collections

ROOT = '/root/pos/pos83'
PORTED = sorted(set(re.findall(
    r"'([^']+)'",
    re.search(r"PORTED = \[(.*?)\];",
              open(f'{ROOT}/app2/components/Ui.php').read(), re.S).group(1))))

COVERED = {'admin', 'index', 'create', 'view', 'update'}

WRITES = re.compile(r'->\s*(save|delete|deleteAll|updateAll|insert|saveAttributes|'
                    r'updateByPk|deleteByPk|deleteAllByAttributes|updateCounters)\s*\(', re.I)
OUTBOUND = re.compile(r'\b(curl_init|curl_exec|fsockopen|file_get_contents\s*\(\s*[\'"]http|'
                      r'mail\s*\(|CURLOPT|ftp_|smtp)', re.I)
SESSION_WRITE = re.compile(r'(Yii::app\s*\(\s*\)|Yii::\$app)\s*->\s*session\s*\[[^\]]+\]\s*=')


def blocks(src, kind='action'):
    pat = (r'public function action(\w+)\s*\(([^)]*)\)' if kind == 'action'
           else r'function (\w+)\s*\(([^)]*)\)')
    for m in re.finditer(pat, src):
        brace = src.find('{', m.end())
        if brace < 0:
            continue
        i, depth = brace + 1, 1
        while i < len(src) and depth:
            if src[i] == '{':
                depth += 1
            elif src[i] == '}':
                depth -= 1
            i += 1
        yield m.group(1), m.group(2).strip(), src[brace + 1:i - 1]


def strip_unreachable(body):
    out, i = [], 0
    for m in re.finditer(r'if\s*\(\s*isset\s*\(\s*\$_POST[^)]*\)\s*\)\s*\{', body):
        if m.start() < i:
            continue
        out.append(body[i:m.start()])
        j, depth = m.end(), 1
        while j < len(body) and depth:
            if body[j] == '{':
                depth += 1
            elif body[j] == '}':
                depth -= 1
            j += 1
        i = j
    out.append(body[i:])
    text = ''.join(out)
    text = re.sub(r'//[^\n]*', '', text)
    return re.sub(r'/\*.*?\*/', '', text, flags=re.S)


MODEL_SRC = {}


def model_methods(cls):
    if cls in MODEL_SRC:
        return MODEL_SRC[cls]
    text = ''
    for f in (f'{ROOT}/protected/models/{cls}.php',
              f'{ROOT}/protected/models/_base/Base{cls}.php'):
        if os.path.exists(f):
            text += open(f, errors='replace').read()
    if not text:
        for f in (f'{ROOT}/protected/components/GxActiveRecord.php',):
            text += open(f, errors='replace').read() if os.path.exists(f) else ''
    MODEL_SRC[cls] = dict((n, b) for n, _, b in blocks(text, 'any'))
    return MODEL_SRC[cls]


GX = None


def called_bodies(body):
    """One level: the model methods this action calls."""
    global GX
    if GX is None:
        GX = dict((n, b) for n, _, b in
                  blocks(open(f'{ROOT}/protected/components/GxActiveRecord.php',
                              errors='replace').read(), 'any'))
    out = []
    for cls, meth in re.findall(r'\b([A-Z]\w+)\s*::\s*model\s*\(\s*\)\s*->\s*(\w+)\s*\(', body):
        out.append(model_methods(cls).get(meth, ''))
    for cls, meth in re.findall(r'\b([A-Z]\w+)\s*::\s*(\w+)\s*\(', body):
        out.append(model_methods(cls).get(meth, ''))
    for meth in re.findall(r'\$\w+\s*->\s*(\w+)\s*\(', body):
        if meth in GX:
            out.append(GX[meth])
        for src in MODEL_SRC.values():
            if meth in src:
                out.append(src[meth])
                break
    return [t for t in out if t]


rows = []
for c in PORTED:
    model = c[0].upper() + c[1:]
    path = f'{ROOT}/protected/controllers/{model}Controller.php'
    if not os.path.exists(path):
        continue
    src = open(path, errors='replace').read()
    model_methods(model)                       # prime, so $model->x() resolves
    for name, params, body in blocks(src):
        act = name[0].lower() + name[1:]
        if act in COVERED:
            continue
        reachable = strip_unreachable(body)
        deep = reachable + '\n' + '\n'.join(strip_unreachable(t)
                                            for t in called_bodies(reachable))
        why = []
        if WRITES.search(deep):
            why.append('writes')
        if OUTBOUND.search(deep):
            why.append('outbound')
        if SESSION_WRITE.search(deep):
            why.append('sets session')
        needs = [p for p in params.split(',') if p.strip() and '=' not in p]
        rows.append({'ctrl': c, 'action': act, 'args': len(needs), 'blocked': why})

safe = [r for r in rows if not r['blocked']]
blocked = [r for r in rows if r['blocked']]
print('uncovered actions:        %d' % len(rows))
print('safe for a read-only GET: %d' % len(safe))
print('held back:                %d' % len(blocked))
for why, n in collections.Counter(', '.join(r['blocked']) for r in blocked).most_common():
    print('    %-28s %d' % (why, n))

prev = {(r['ctrl'], r['action']) for r in json.load(open('/tmp/sweep-targets.json'))}
now = {(r['ctrl'], r['action']) for r in safe}
dropped = sorted(prev - now)
print('\n=== swept before, held back now (%d) ===' % len(dropped))
for c, a in dropped:
    print('  %s/%s' % (c, a))

json.dump(safe, open('/tmp/sweep-targets.json', 'w'), indent=1)
print('\nwrote /tmp/sweep-targets.json with %d targets' % len(safe))
