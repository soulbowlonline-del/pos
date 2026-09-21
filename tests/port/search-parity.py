#!/usr/bin/env python3
"""
Every attribute Yii 1's search() filters on, the port filters on too.

Two models had lost theirs. Item's Yii 1 search is hand-written - a prefix
LIKE on the title, a barcode looked up through ItemDetail, a tax subquery,
vendor scoping - and the generated version kept the plain compares and dropped
all of it, so the item master's search box narrowed nothing: type a title, get
the first hundred items. ItemDetail handed six of its boxes to
getItemOptionIdsInBarcode(), which was ported and then never called.

Neither showed up anywhere. pmui_difftest renders the grid and compares it;
crud-sweep submits a filter and compares the row count - and a count is capped
at the page size, so an unfiltered hundred and a filtered hundred look the
same. Only the total says otherwise, and nothing was reading it.

This does not run anything. It reads both search() bodies and compares the
attributes each mentions. Yii 1 names them directly, `$this->title`; the port
loops over [['column', 'attribute'], ...] pairs, so the attribute is the
second element rather than a literal after `$this->` - reading only the latter
reports every model as having lost everything, which is what the first version
of this did.

A name here is not proof the filter works, only that something reads it. The
row comparison in crud-sweep is what checks the answer.
"""
import os, re
REPO = '/root/pos/pos83'

def body(text, name='search'):
    m = re.search(r'function\s+%s\s*\([^)]*\)' % name, text)
    if not m:
        return None
    i = text.find('{', m.end())
    if i < 0:
        return None
    d, j = 0, i
    while j < len(text):
        if text[j] == '{': d += 1
        elif text[j] == '}':
            d -= 1
            if d == 0: break
        j += 1
    return text[i:j]

def strip(b):
    b = re.sub(r'/\*.*?\*/', '', b, flags=re.S)
    return re.sub(r'//[^\n]*', '', b)


def attrs(b):
    """Yii 1 names the attribute directly: $this->title."""
    if not b:
        return set()
    return set(re.findall(r'\$this->(\w+)', strip(b)))


def port_attrs(b):
    """
    The port loops: foreach ([['col', 'attr'], ...] as [$col, $attr]) with
    Criteria::compare($query, $col, $this->$attr). The attribute never appears
    as a literal after $this->, so reading that pattern alone finds two names
    reports every model as having lost everything. The pairs are the filters.
    """
    if not b:
        return set()
    b = strip(b)
    found = set(re.findall(r'\$this->(\w+)', b))
    found |= {m[1] for m in re.findall(r"\[\s*'([^']+)'\s*,\s*'([^']+)'\s*\]", b)}
    return found

def yii1_search(model):
    for p in ('%s/protected/models/%s.php' % (REPO, model),
              '%s/protected/models/_base/Base%s.php' % (REPO, model)):
        if os.path.exists(p):
            b = body(open(p, errors='replace').read())
            if b:
                return b
    return None

s = open(REPO + '/app2/components/Ui.php', errors='replace').read()
ported = re.findall(r"'([^']+)'", re.search(r'PORTED = \[(.*?)\];', s, re.S).group(1))

print('  %-24s %-6s %-6s  %s' % ('CONTROLLER', 'YII1', 'PORT',
                                  'ATTRIBUTES THE PORT NO LONGER FILTERS ON'))
gaps = 0
checked = 0
for c in ported:
    model = c[0].upper() + c[1:]
    p2 = '%s/app2/models/%s.php' % (REPO, model)
    if not os.path.exists(p2):
        continue
    b1 = yii1_search(model)
    b2 = body(open(p2, errors='replace').read())
    if b1 is None or b2 is None:
        continue
    checked += 1
    a1, a2 = attrs(b1), port_attrs(b2)
    missing = sorted(a1 - a2 - {'attributes', 'scenario', 'isNewRecord'})
    if missing:
        gaps += 1
        print('  %-24s %-6d %-6d  %s' % (c, len(a1), len(a2), ', '.join(missing)[:78]))
print('  passed: %d   mismatched: %d' % (checked - gaps, gaps))
import sys; sys.exit(1 if gaps else 0)
