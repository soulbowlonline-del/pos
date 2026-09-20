import os, re, collections

ROOT = '/root/pos/pos83/app2'

# Classes and idioms that only exist in Yii 1. `Criteria` and `Gx` are this
# port's own shims and are not leftovers; `CException` appears inside comments
# quoting Yii 1 source.
PATTERNS = [
    (r'\bnew\s+CDbCriteria\b',                    'CDbCriteria'),
    (r'\bnew\s+CActiveDataProvider\b',            'CActiveDataProvider'),
    (r'\bnew\s+CArrayDataProvider\b',             'CArrayDataProvider'),
    (r'\b\w+::model\s*\(\s*\)',                   'X::model()'),
    (r'\bYii::app\s*\(\s*\)',                     'Yii::app()'),
    (r'\bCHtml::',                                'CHtml::'),
    (r'\bCJSON::',                                'CJSON::'),
    (r'\bCVarDumper::',                           'CVarDumper::'),
    (r'\bCMap::',                                 'CMap::'),
    (r'\bGxHtml::',                               'GxHtml::'),
    (r'\bGxActiveRecord::',                       'GxActiveRecord::'),
    (r'\bnew\s+CHttpException\b',                 'CHttpException'),
    (r'\bCDbExpression\b',                        'CDbExpression'),
]


def mask(src):
    """Blank out comments and string literals so only live code is scanned."""
    out, i, n = [], 0, len(src)
    while i < n:
        c = src[i]
        if c == '/' and i + 1 < n and src[i + 1] == '/':
            j = src.find('\n', i)
            j = n if j < 0 else j
            out.append(blank(src[i:j])); i = j
        elif c == '#' and not src.startswith('#[', i):
            j = src.find('\n', i)
            j = n if j < 0 else j
            out.append(blank(src[i:j])); i = j
        elif c == '/' and i + 1 < n and src[i + 1] == '*':
            j = src.find('*/', i + 2)
            j = n if j < 0 else j + 2
            out.append(blank(src[i:j])); i = j
        elif c in ('"', "'"):
            q, j = c, i + 1
            while j < n:
                if src[j] == '\\':
                    j += 2
                    continue
                if src[j] == q:
                    j += 1
                    break
                j += 1
            out.append(blank(src[i:j])); i = j
        else:
            out.append(c); i += 1
    return ''.join(out)


def blank(text):
    """Same length, same newlines - so line numbers survive masking."""
    return re.sub(r'[^\n]', ' ', text)


found = collections.defaultdict(list)
for dirpath, _, files in os.walk(ROOT):
    for f in sorted(files):
        if not f.endswith('.php'):
            continue
        path = os.path.join(dirpath, f)
        rel = path[len('/root/pos/pos83/'):]
        src = mask(open(path, encoding='utf-8', errors='replace').read())
        for pat, name in PATTERNS:
            for m in re.finditer(pat, src):
                line = src.count('\n', 0, m.start()) + 1
                found[rel].append((line, name))

total = sum(len(v) for v in found.values())
print('%d live Yii 1 references in %d files\n' % (total, len(found)))
for rel in sorted(found, key=lambda r: -len(found[r])):
    kinds = collections.Counter(k for _, k in found[rel])
    print('  %-46s %s' % (rel, ', '.join('%s x%d' % (k, n) for k, n in kinds.most_common())))
