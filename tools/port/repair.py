"""
Apply the shared Yii 1 idioms to the already-generated app2 files.

Regenerating does not reach these. port_model.merge() deliberately keeps the
methods an API-tracked model already has - that is what stops a web-UI port
from changing how the API validates and saves - so an idiom added to the
generator after a model was first written never lands in those methods.

The idioms are mechanical and keyed on Yii 1 spellings (`X::model()`,
`CDbCriteria`, `CJSON::`) that converted code does not contain, so running
them over generated output is idempotent by construction.
"""
import os, re, subprocess, sys
sys.path.insert(0, '/root/pos')
import port_model as pm
import importlib.util

# Read before port_controller is imported: loading it replaces sys.argv, and
# the flag was being read from the replacement, so --apply never took effect.
APPLY = '--apply' in sys.argv

spec = importlib.util.spec_from_file_location('pc', '/root/pos/port_controller.py')
pc = importlib.util.module_from_spec(spec)
sys.argv = ['port_controller.py']
try:
    spec.loader.exec_module(pc)
except (SystemExit, IndexError):
    pass

ROOT = '/root/pos/pos83/app2'


def code_spans(text):
    """
    The (start, end) ranges of `text` that are code rather than comment.

    Comments only. Splitting strings out as well broke the rules that balance
    parentheses: `LoyaltyTransaction::model()->findBySql(<<multi-line SQL>>)`
    has its closing parenthesis in a different span from its opening one, so
    the conversion could not see the end of the call and declined.
    """
    out, i, n, run = [], 0, len(text), 0
    while i < n:
        c = text[i]
        if c == '/' and i + 1 < n and text[i + 1] == '/':
            j = text.find('\n', i)
            j = n if j < 0 else j
        elif c == '#' and not text.startswith('#[', i):
            j = text.find('\n', i)
            j = n if j < 0 else j
        elif c == '/' and i + 1 < n and text[i + 1] == '*':
            j = text.find('*/', i + 2)
            j = n if j < 0 else j + 2
        else:
            i += 1
            continue
        if run < i:
            out.append((run, i))
        run = i = j
    if run < n:
        out.append((run, n))

    return out


def on_code(fn, text):
    """
    Apply a rewrite to the code in `text`, leaving comments and strings alone.

    The idioms are text substitutions, and run over a whole file they also
    rewrite prose. `Yii::app()` appears in two comments that explain what the
    *Yii 1* code did, and converting those made the explanations wrong.
    """
    out, prev = [], 0
    for a, b in code_spans(text):
        out.append(text[prev:a])
        out.append(fn(text[a:b]))
        prev = b
    out.append(text[prev:])

    return ''.join(out)


def add_t_alias(text):
    """
    Give a query the `t` alias when its own conditions name it.

    Yii 1 aliases the primary table `t`; Yii 2 aliases it by table name. The
    generator has emitted `->alias('t')` for a query whose conditions mention
    `t.` for a long time - but merge() preserves the methods an API-tracked
    model already has, so a method first written before that rule existed
    still carries a query without it. order/b2bReport and order/grouphsntax
    died on "Unknown column 't.bill_date' in 'where clause'".

    Scoped to one method at a time, and to the span between this assignment
    and the next one to the same variable, so an alias is only added where the
    conditions that need it actually are.
    """
    out = []
    for piece in re.split(r'(?=\n[ \t]*(?:public|protected|private)?\s*'
                          r'(?:static\s+)?function\s+\w+\s*\()', text):
        edits = []
        for m in re.finditer(r'(\$\w+)\s*=\s*(\w+)::find\(\)', piece):
            var, decl_end = m.group(1), m.end()
            if re.match(r"\s*->\s*alias\s*\(", piece[decl_end:]):
                continue
            nxt = re.search(re.escape(var) + r'\s*=\s*\w+::find\(\)',
                            piece[decl_end:])
            span = piece[decl_end:decl_end + (nxt.start() if nxt else len(piece))]
            uses = re.search(re.escape(var) + r'\s*->[^;]*?[\'"]t\.', span, re.S)
            if uses:
                edits.append(decl_end)
        for at in reversed(edits):
            piece = piece[:at] + "->alias('t')" + piece[at:]
        out.append(piece)

    return ''.join(out)


def close_where(text):
    """
    Move the parenthesis an unbalanced by-attributes conversion put in the
    wrong place.

    The old rule matched the argument list with a non-greedy `(.*?)`, which
    stops at the first `)` followed by a delimiter. For an argument spanning
    lines that is the array's own closing parenthesis, not the call's, so

        UserRole::model()->findByAttributes(array(
            'title' => 'Vendor'
        ))

    became `where(array('title' => 'Vendor')->orderBy(...)->one())`: the query
    chain ended up *inside* where(), applied to the array. item/barCode died
    on "Call to a member function orderBy() on array", and four models carried
    it. The generator balances its arguments now; this repairs the output it
    wrote before it did.
    """
    out, i = [], 0
    while True:
        k = text.find('->where(', i)
        if k < 0:
            out.append(text[i:])
            break
        q = k + len('->where(') - 1
        j, depth = q + 1, 1
        while j < len(text) and depth:
            if text[j] == '(':
                depth += 1
            elif text[j] == ')':
                depth -= 1
            j += 1
        if depth:
            out.append(text[i:j])
            i = j
            continue
        inner = text[q + 1:j - 1]

        # The first balanced element of the argument, and whatever follows it.
        m = re.match(r'\s*(array\s*\(|\[)', inner)
        if not m:
            out.append(text[i:j])
            i = j
            continue
        closer = ')' if m.group(1).strip().startswith('array') else ']'
        d, e = 1, m.end()
        while e < len(inner) and d:
            if inner[e] in '([':
                d += 1
            elif inner[e] in ')]':
                d -= 1
            e += 1
        head, rest = inner[:e], inner[e:]
        if not re.match(r'\s*->\s*\w+\s*\(', rest):
            out.append(text[i:j])
            i = j
            continue

        out.append(text[i:k])
        out.append('->where(%s)%s' % (head, rest))
        i = j

    return ''.join(out)

changed, failed, skipped = [], [], []
for dirpath, _, files in os.walk(ROOT):
    for f in sorted(files):
        if not f.endswith('.php'):
            continue
        path = os.path.join(dirpath, f)
        rel = path[len('/root/pos/pos83/'):]
        src = open(path, encoding='utf-8', errors='replace').read()

        out = src
        # `self::model()` inside a model is that model. The conversions read
        # the class out of the call, so without this they were handed "self".
        if dirpath.endswith('/models'):
            out = pm.normalise_self(out, f[:-4])
        if 'CDbCriteria' in out:
            try:
                out = pc.convert_criteria_blocks(out)
            except Exception as e:
                skipped.append((rel, 'criteria: %s' % e))
        out = on_code(pm.finder_idioms, out)
        out = on_code(pm.dao_idioms, out)
        out = close_where(out)
        out = add_t_alias(out)
        if out == src:
            continue

        # Never write something that does not parse.
        tmp = '/tmp/repair_check.php'
        open(tmp, 'w', encoding='utf-8').write(out)
        lint = subprocess.run(['php', '-l', tmp], capture_output=True, text=True)
        if lint.returncode != 0:
            failed.append((rel, lint.stdout.strip().split('\n')[0][:110]))
            continue

        changed.append(rel)
        if APPLY:
            open(path, 'w', encoding='utf-8').write(out)

print('%s %d files' % ('repaired' if APPLY else 'would repair', len(changed)))
for r in changed:
    print('   ' + r)
if failed:
    print('\nREFUSED (the result would not parse):')
    for r, why in failed:
        print('   %-46s %s' % (r, why))
if skipped:
    print('\ncriteria conversion could not run:')
    for r, why in skipped:
        print('   %-46s %s' % (r, why))
