#!/usr/bin/env python3
"""
Generates a Yii 2 model from the giix base class.

The _base models are generated code and completely regular, which is what makes
this safe: every one declares its rules, relations, labels and search() the
same way. Anything irregular - a hand-written method on the concrete model, a
rule validator with no Yii 2 equivalent - is reported rather than guessed at.
"""
import re, sys, os, subprocess

ROOT = '/root/pos/pos83'


def read(p):
    return open(p, encoding='utf-8', errors='replace').read()


def arrays(src):
    """array(...) -> [...] using the view transformer's scanner."""
    sys.path.insert(0, '/root/pos')
    from port_views import arrays_to_brackets
    return arrays_to_brackets(src)


def attr_label(name):
    """Yii 1's CModel::generateAttributeLabel()."""
    s = re.sub(r'(?<![A-Z])[A-Z]', lambda m: ' ' + m.group(0), name)
    s = s.replace('-', ' ').replace('_', ' ').replace('.', ' ')
    return ' '.join(w.capitalize() for w in s.strip().lower().split())


def parse_block(src, name):
    """The body of a `function name()` block, brace-matched."""
    m = re.search(r'function\s+' + name + r'\s*\([^)]*\)\s*\{', src)
    if not m:
        return None
    i = m.end()
    depth = 1
    while i < len(src) and depth:
        if src[i] == '{':
            depth += 1
        elif src[i] == '}':
            depth -= 1
        i += 1
    return src[m.end():i - 1]


def port_rules(body, warn):
    """Yii 1 validation rules -> Yii 2."""
    out = []
    # `array (` with a space appears throughout these generated files, so the
    # pattern cannot require `array(`. Getting this wrong produced models with
    # no rules at all - and a model with no `safe` rule renders no grid filters,
    # which is exactly how it was noticed.
    for m in re.finditer(r"array\s*\(\s*'([^']*)'\s*,\s*'(\w+)'\s*((?:,[^)]*)?)\)", body, re.S):
        attrs = [a.strip() for a in m.group(1).split(',') if a.strip()]
        rule = m.group(2)
        rest = m.group(3)
        alist = '[' + ', '.join("'%s'" % a for a in attrs) + ']'

        if rule == 'required':
            out.append("[%s, 'required']" % alist)
        elif rule == 'numerical':
            out.append("[%s, 'integer']" % alist
                       if 'integerOnly' in rest else "[%s, 'number']" % alist)
        elif rule == 'length':
            mx = re.search(r"'max'\s*=>\s*(\d+)", rest)
            out.append("[%s, 'string', 'max' => %s]" % (alist, mx.group(1)) if mx
                       else "[%s, 'string']" % alist)
        elif rule == 'default':
            # setOnEmpty is Yii 1's way of writing NULL back for a blank field;
            # Yii 2's default rule already only fires on an empty value.
            out.append("[%s, 'default', 'value' => null]" % alist)
        elif rule == 'safe':
            on = re.search(r"'on'\s*=>\s*'(\w+)'", rest)
            out.append("[%s, 'safe'%s]" % (alist, ", 'on' => '%s'" % on.group(1) if on else ''))
        elif rule in ('email', 'url', 'unique'):
            out.append("[%s, '%s']" % (alist, rule))
        else:
            warn.append('rule not converted: ' + rule)
    return out


def port_relations(body, warn):
    """Yii 1 relations() -> Yii 2 relation getters."""
    out = []
    # The relation arrays are written both on one line and spread over five,
    # so the pattern has to span newlines - a single-line pattern silently
    # produced models with relations missing.
    for m in re.finditer(r"'(\w+)'\s*=>\s*array\s*\(\s*self::(\w+)\s*,\s*'(\w+)'\s*,\s*'(\w+)'",
                         body, re.S):
        name, kind, cls, fk = m.groups()
        getter = 'get' + name[0].upper() + name[1:]
        if kind == 'BELONGS_TO':
            out.append((getter, "        return $this->hasOne(%s::class, ['id' => '%s']);" % (cls, fk)))
        elif kind == 'HAS_MANY':
            out.append((getter, "        return $this->hasMany(%s::class, ['%s' => 'id']);" % (cls, fk)))
        elif kind == 'HAS_ONE':
            out.append((getter, "        return $this->hasOne(%s::class, ['%s' => 'id']);" % (cls, fk)))
        else:
            warn.append('relation kind not converted: %s %s' % (kind, name))
    return out


def model_label(cls, plural=False):
    """Another model's label(), read from its giix base class."""
    p = f'{ROOT}/protected/models/_base/Base{cls}.php'
    if os.path.exists(p):
        m = re.search(r"label\s*\(\s*\$n\s*=\s*1\s*\)\s*\{\s*return\s*Yii::t\s*\(\s*'app'\s*,\s*'([^|]*)\|([^']*)'",
                      read(p))
        if m:
            return m.group(2) if plural else m.group(1)
    return cls + ('s' if plural else '')


def port_labels(body, relations_body):
    """
    Yii 1's attributeLabels(), with its null entries resolved.

    A null label does not mean "no label". GxActiveRecord::getRelationLabel()
    falls back to the *related* model's label() for a relation or a foreign
    key, and only otherwise to generateAttributeLabel(). So `advancePayment`
    and `advance_payment_id` both show as "AdvancePayment", not as
    "Advance Payment" - which is what a naive fallback produces, and what the
    comparison against Yii 1 catches immediately.
    """
    rel = {}          # relation name -> (kind, class)
    fk = {}           # foreign key column -> class
    for m in re.finditer(r"'(\w+)'\s*=>\s*array\s*\(\s*self::(\w+)\s*,\s*'(\w+)'\s*,\s*'(\w+)'",
                         relations_body or '', re.S):
        name, kind, cls, key = m.groups()
        rel[name] = (kind, cls)
        if kind == 'BELONGS_TO':
            fk[key] = cls

    out = []
    for m in re.finditer(r"'(\w+)'\s*=>\s*(?:Yii::t\s*\(\s*'[^']*'\s*,\s*'([^']*)'\s*\)|(null))", body, re.S):
        name, label, isnull = m.groups()
        if not isnull:
            out.append((name, label))
        elif name in rel:
            kind, cls = rel[name]
            out.append((name, model_label(cls, kind in ('HAS_MANY', 'MANY_MANY'))))
        elif name in fk:
            out.append((name, model_label(fk[name])))
        else:
            out.append((name, attr_label(name)))
    return out


WRITES = re.compile(r'->\s*(?:save|delete|insert|update|updateAll|deleteAll|'
                    r'updateCounters|saveAttributes)\s*\(|\$this->\w+\s*=[^=]|'
                    r'->\s*execute\s*\(')


def read_only(text):
    """
    Whether a method only reads.

    A read-only helper can be added to a model the API port wrote without any
    risk to how that model validates or saves - which is the whole reason
    presentation-only exists. Item::getAllActiveVendors() is one: a page that
    lists vendor schemes calls it, and leaving it out gives a 500 on a method
    that could not have changed anything.

    The test is deliberately crude and errs towards keeping the method out:
    any assignment to $this, any save/delete/update/insert, any ->execute()
    disqualifies it.
    """
    return not WRITES.search(text)


_COLUMN_CACHE = {}


def columns_of(table):
    """The column names of a table, straight from the database."""
    if table in _COLUMN_CACHE:
        return _COLUMN_CACHE[table]

    sql = ("SELECT COLUMN_NAME FROM information_schema.COLUMNS "
           "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_%s'" % table)
    out = subprocess.run(
        ['docker', 'exec', 'pos-mysql-8', 'sh', '-c',
         'MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot $MYSQL_DATABASE -N -e "%s"' % sql],
        capture_output=True, text=True)
    cols = {c.strip() for c in out.stdout.split() if c.strip()}
    _COLUMN_CACHE[table] = cols

    return cols


def public_properties(base, concrete):
    """
    The public properties a Yii 1 model declares.

    These are attributes that are not columns - Emp declares $shift_id,
    $username and $password - and the forms post to them, the rules validate
    them and the actions assign them. Without the declaration Yii 2 throws
    "Setting unknown property" the moment the action runs.

    Only simple declarations are taken; anything with a default expression is
    left, because the expression may be Yii 1 code.
    """
    found = []
    for src in (base, concrete):
        for m in re.finditer(r'(?m)^[ \t]*public\s+(\$\w+)\s*(?:=\s*([^;]+))?;', src or ''):
            name, default = m.group(1), m.group(2)
            if any(name == n for n, _ in found):
                continue
            found.append((name, default.strip() if default else None))
    return found


def relations_of(model):
    """The relation names and kinds declared by the giix base class."""
    p = f'{ROOT}/protected/models/_base/Base{model}.php'
    if not os.path.exists(p):
        return []
    body = parse_block(read(p), 'relations') or ''
    return [(m.group(1), m.group(2)) for m in
            re.finditer(r"'(\w+)'\s*=>\s*array\s*\(\s*self::(\w+)", body, re.S)]


def port_default_scope(base, warn, concrete=''):
    """
    The ordering Yii 1 applies to every query on this model.

    GxActiveRecord::defaultScope() returns `id DESC` for any model with an id.
    22 of the 72 models override it, mostly to an empty array, which means no
    ORDER BY at all - the rows come back in whatever order the storage engine
    produces for that query. Applying `id DESC` to those anyway puts the grid
    and every filter dropdown in an order Yii 1 never showed.

    Returns a PHP expression for the sort, or 'null' for no ordering.
    """
    # The concrete model can override the base class's override. OrderRefund
    # does exactly that - an empty defaultScope() on protected/models, nothing
    # in _base - and reading only the base gave every listing an `id DESC` that
    # Yii 1 does not apply.
    body = parse_block(concrete, 'defaultScope')
    if body is None:
        body = parse_block(base, 'defaultScope')
    if body is None:
        return "['id' => SORT_DESC]"          # the inherited default

    stripped = re.sub(r'//[^\n]*', '', body)
    stripped = re.sub(r'/\*.*?\*/', '', stripped, flags=re.S)
    m = re.search(r"'order'\s*=>\s*'([^']+)'", stripped)
    if not m:
        return 'null'                          # overridden to no ordering

    cols = []
    for part in m.group(1).split(','):
        bits = part.strip().split()
        col = bits[0].split('.')[-1]
        desc = len(bits) > 1 and bits[1].lower().startswith('desc')
        cols.append("'%s' => %s" % (col, 'SORT_DESC' if desc else 'SORT_ASC'))
    return '[' + ', '.join(cols) + ']'


def port_with(body):
    """
    The relations Yii 1's search() eager-loads.

    `$criteria->with` is not a convenience here. For a BELONGS_TO relation
    Yii 1 loads it by JOIN in the same query, so it changes the plan - and
    where the listing has no ORDER BY, the plan is what decides which ten of
    25,000 rows the first page shows. Dropping it gave a page of entirely
    different rows.

    Yii 2's with() runs a second query and would not reproduce that; joinWith()
    does. Nested `'a' => ['with' => ['b']]` becomes the path 'a.b'.
    """
    m = re.search(r"\$criteria->with\s*=\s*array\s*\(", body)
    if not m:
        return []

    i = m.end()
    depth = 1
    while i < len(body) and depth:
        if body[i] == '(':
            depth += 1
        elif body[i] == ')':
            depth -= 1
        i += 1
    block = body[m.end():i - 1]

    paths = []

    def walk(text, prefix):
        # 'name' => array('with' => array(...))   - a nested eager load
        for nm in re.finditer(r"'(\w+)'\s*=>\s*array\s*\(\s*'with'\s*=>\s*array\s*\(([^)]*)\)",
                              text, re.S):
            base = prefix + nm.group(1)
            inner = re.findall(r"'(\w+)'", nm.group(2))
            if inner:
                for x in inner:
                    paths.append(base + '.' + x)
            else:
                paths.append(base)
        # Bare 'name' entries. Skip the keys handled above, and skip anything
        # that is already the tail of a nested path - the 'item' inside
        # 'itemDetail' => ['with' => ['item']] is that relation, not a second
        # one on the root model.
        consumed = set(re.findall(r"'(\w+)'\s*=>", text))
        for nested in re.finditer(r"'(\w+)'\s*=>\s*array\s*\(\s*'with'\s*=>\s*array\s*\(([^)]*)\)",
                                  text, re.S):
            consumed.update(re.findall(r"'(\w+)'", nested.group(2)))
        for nm in re.findall(r"'(\w+)'", text):
            if nm in consumed or nm == 'with':
                continue
            if prefix + nm in paths:
                continue
            if any(pp.startswith(prefix + nm + '.') for pp in paths):
                continue
            paths.append(prefix + nm)

    walk(block, '')
    return paths


def port_search_sort(body):
    """
    The order Yii 1's search() gives its data provider.

    Read from the CActiveDataProvider's `sort` => `defaultOrder`, which is
    where ten of these models put it. An earlier version of this generator
    looked only at defaultScope() and concluded those listings had no order at
    all - they do, and the grids were being compared against a Yii 2 provider
    that had none because the sort was never ported.

    The column may carry the query alias ('t.id DESC'); Yii 2 wants the bare
    column.
    """
    if not body:
        return None
    stripped = re.sub(r'//[^\n]*', '', body)
    stripped = re.sub(r'/\*.*?\*/', '', stripped, flags=re.S)

    # Two places, both of which decide the grid's order and neither of which
    # this generator originally read. $criteria->order wins where both are
    # present, because Yii 1 applies it to the criteria the provider is built
    # from. Six models use the first, nine the second, and they are not all
    # `id DESC` - three sort by `item.title asc` and one by `t.order Asc`.
    m = (re.search(r"\$criteria\s*->\s*order\s*=\s*\(?\s*'([^']+)'", stripped)
         or re.search(r"'defaultOrder'\s*=>\s*'([^']+)'", stripped))
    if not m:
        return None

    cols = []
    for part in m.group(1).split(','):
        bits = part.strip().split()
        if not bits:
            continue
        col = bits[0]
        # `t` is the main table's alias, which Yii 2 does not use; any other
        # qualifier names a joined relation and has to stay, or the column is
        # ambiguous - or simply the wrong one.
        if col.startswith('t.'):
            col = col[2:]
        desc = len(bits) > 1 and bits[1].lower().startswith('desc')
        cols.append("'%s' => %s" % (col, 'SORT_DESC' if desc else 'SORT_ASC'))

    return '[' + ', '.join(cols) + ']' if cols else None


def port_search(body, warn):
    """The compare() calls that back the admin grid."""
    exact, partial = [], []
    for m in re.finditer(r"\$criteria->compare\s*\(\s*'([^']+)'\s*,\s*\$this->(\w+)\s*(,\s*true)?\s*\)", body):
        col, _, is_partial = m.groups()
        (partial if is_partial else exact).append(col)
    if 'addCondition' in body:
        warn.append('search() has conditions beyond compare(); check it by hand')
    return exact, partial


# Names Yii 2's ActiveRecord/Model already define with an incompatible
# signature, so a Yii 1 method of the same name cannot simply be carried over.
# Yii 2 declares these public on BaseActiveRecord. A Yii 1 model declares the
# same methods protected, and PHP will not let a subclass narrow visibility.
YII2_PUBLIC_HOOKS = {
    'beforeSave', 'afterSave', 'beforeDelete', 'afterDelete', 'afterFind',
    'beforeValidate', 'afterValidate', 'afterRefresh',
}

CLASHES_WITH_YII2 = {
    'toArray', 'fields', 'extraFields', 'attributes', 'load', 'validate',
    'save', 'delete', 'refresh', 'init', 'behaviors', 'scenarios',
    'formName', 'primaryKey', 'find', 'findOne', 'findAll', 'updateAll',
    'deleteAll', 'instantiate', 'populateRecord',
    # Yii 1 constructs with no Yii 2 counterpart. defaultScope() is read by
    # the generator to build defaultOrder(); carrying the method itself across
    # would leave dead code that looks like it still governs ordering.
    'defaultScope', 'relations', 'pivotModels', 'attributeNames', 'behaviors',
}

# init() appears in the list above because a Yii 1 model's own init() cannot be
# carried over unchanged. The generator writes its own, which calls parent.


YII1_CLASSES = (r'\b(CDbCriteria|CActiveDataProvider|CArrayDataProvider|CDbExpression|'
                r'CHtml|CException|CHttpException|CJSON|CVarDumper|CLogger|CUploadedFile|'
                r'CDataProviderIterator|CActiveRecord|CModel|CSort|CPagination|CMap|'
                r'CTypeValidator|CWidget|CController)\b')


# The only $criteria members the translator knows how to rewrite. A method
# using anything else is left exactly as it is.
CRITERIA_HANDLED = re.compile(r'\$criteria->(\w+)')
CRITERIA_KNOWN = {'order', 'limit', 'addCondition'}


def criteria_is_simple(text):
    """
    Whether every use of $criteria in this method is one the translator
    rewrites.

    This has to be all or nothing. A first version stripped the constructs it
    knew - order, limit, addCondition - along with the `new CDbCriteria()` line
    and left the rest, so a method using addBetweenCondition() came out
    referring to a variable that no longer existed. Broken code that looks
    converted is worse than code that still says CDbCriteria: the second is
    reported and kept out of shared models, the first fails at runtime with no
    clue where it came from.
    """
    return all(m in CRITERIA_KNOWN for m in CRITERIA_HANDLED.findall(text))


def mask_comments(text):
    """
    Hide whole-line comments from the rewriters.

    Commented-out code is still code to a regex. A fetch inside a `//` line was
    rewritten into a multi-line expression whose continuation escaped the
    comment, and the resulting file did not parse - in a model the API
    depends on. The rewriters have no business changing text that does not run.

    Returns (masked text, restore function).
    """
    lines = text.split('\n')
    saved = {}
    for i, line in enumerate(lines):
        stripped = line.strip()
        if stripped.startswith('//') or stripped.startswith('#') or stripped.startswith('*'):
            token = '/*__MASKED_%d__*/' % i
            saved[token] = line
            lines[i] = token

    def restore(s2):
        for token, line in saved.items():
            s2 = s2.replace(token, line)
        return s2

    return '\n'.join(lines), restore


def default_order_of(cls):
    """The order another model's defaultScope() applies, as a PHP expression."""
    base = f'{ROOT}/protected/models/_base/Base{cls}.php'
    concrete = f'{ROOT}/protected/models/{cls}.php'
    if not os.path.exists(base) and not os.path.exists(concrete):
        return None
    body = None
    for path in (concrete, base):
        if os.path.exists(path):
            body = parse_block(read(path), 'defaultScope')
            if body is not None:
                break
    if body is None:
        return "['id' => SORT_DESC]"        # the inherited default
    stripped = re.sub(r'//[^\n]*', '', body)
    m = re.search(r"'order'\s*=>\s*'([^']+)'", stripped)
    if not m:
        return None                          # overridden to no ordering
    bits = m.group(1).split()
    col = bits[0]
    if col.startswith('t.'):
        col = col[2:]
    desc = len(bits) > 1 and bits[1].lower().startswith('desc')

    return "['%s' => %s]" % (col, 'SORT_DESC' if desc else 'SORT_ASC')


def convert_criteria(text):
    """
    Rewrites a CDbCriteria query as a Yii 2 query, statement by statement.

    The variable is whatever the method calls it. It is $criteria most of the
    time, but $criteria1 and $criteria2 appear too, and hardcoding the common
    name meant those methods were reported as having no CDbCriteria at all -
    then left as Yii 1 code, then excluded from the model, and the page died on
    a missing method rather than on anything to do with criteria.

    The declaration is also written both `new CDbCriteria()` and
    `new CDbCriteria;` - 107 of the latter across the models.

    In place, not hoisted: the application applies some conditions inside an
    `if`, and a condition built there refers to variables that only exist
    there.

    All or nothing. Every use is rewritten or the method is returned untouched
    for the caller to report, because code that looks converted and is not is
    worse than code that still says CDbCriteria.

    Returns (text, converted, reason).
    """
    original = text
    text, restore = mask_comments(text)

    decls = re.findall(r'\$(\w+)\s*=\s*new\s+CDbCriteria\s*(?:\(\s*\))?\s*;', text)
    if not decls:
        return original, False, 'no CDbCriteria'
    if len(set(decls)) > 1:
        return original, False, 'more than one criteria variable (%s)' % ', '.join(sorted(set(decls)))

    var = decls[0]
    V = r'\$' + re.escape(var)

    find = re.search(r"(\w+)::model\s*\(\s*\)\s*->\s*(findAll|find|count)\s*\(\s*" + V + r"\s*\)", text)
    if not find:
        return original, False, 'the criteria is not passed to a finder this knows'
    cls, kind = find.group(1), find.group(2)

    out = text

    scope = default_order_of(cls)
    start_stmt = '$query = ' + cls + '::find();'
    if scope and scope != 'null' and 'order' not in text.lower():
        start_stmt += '\n        $query->orderBy(' + scope + ');'
    out = re.sub(V + r'\s*=\s*new\s+CDbCriteria\s*(?:\(\s*\))?\s*;',
                 lambda m: start_stmt, out)

    def order_by(m):
        cols = []
        for part in m.group(1).split(','):
            bits = part.strip().split()
            if not bits:
                continue
            col = bits[0]
            if col.startswith('t.'):
                col = col[2:]
            desc = len(bits) > 1 and bits[1].lower().startswith('desc')
            cols.append("'%s' => %s" % (col, 'SORT_DESC' if desc else 'SORT_ASC'))
        return '$query->orderBy([%s]);' % ', '.join(cols)

    out = re.sub(V + r"\s*->\s*order\s*=\s*'([^']+)'\s*;", order_by, out)
    out = re.sub(V + r"\s*->\s*select\s*=\s*([^;]+);",
                 lambda m: '$query->select(%s);' % m.group(1).strip(), out)
    out = re.sub(V + r"\s*->\s*group\s*=\s*([^;]+);",
                 lambda m: '$query->groupBy(%s);' % m.group(1).strip(), out)
    out = re.sub(V + r"\s*->\s*limit\s*=\s*'?(\d+)'?\s*;",
                 lambda m: '$query->limit(%s);' % m.group(1), out)

    cond = re.search(V + r"\s*->\s*condition\s*=\s*([^;]+);", out)
    if cond:
        params = re.search(V + r"\s*->\s*params\s*=\s*([^;]+);", out)
        out = out.replace(cond.group(0), '$query->andWhere(%s%s);' % (
            cond.group(1).strip(), ', ' + params.group(1).strip() if params else ''))
        if params:
            out = out.replace(params.group(0), '')

    def helper(m):
        name, args = m.group(1), m.group(2).strip()
        if name == 'addCondition':
            return '$query->andWhere(%s);' % args
        bits = split_args_php(args)
        if name == 'addInCondition' and len(bits) >= 2:
            return '$query->andWhere([%s => %s]);' % (bits[0].strip(), bits[1].strip())
        if name == 'addBetweenCondition' and len(bits) >= 3:
            return "$query->andWhere(['between', %s, %s, %s]);" % tuple(b.strip() for b in bits[:3])
        if name == 'addSearchCondition' and len(bits) >= 2:
            return "$query->andWhere(['like', %s, %s]);" % (bits[0].strip(), bits[1].strip())
        if name == 'compare' and len(bits) >= 2:
            partial = ', true' if len(bits) > 2 and 'true' in bits[2] else ''
            return 'Criteria::compare($query, %s, %s%s);' % (bits[0].strip(), bits[1].strip(), partial)
        return m.group(0)

    out = re.sub(V + r"\s*->\s*(addCondition|addInCondition|addBetweenCondition|"
                 r"addSearchCondition|compare)\s*\((.*?)\)\s*;", helper, out, flags=re.S)

    tail = {'findAll': '$query->all()', 'find': '$query->one()', 'count': '$query->count()'}[kind]
    out = re.sub(r"\w+::model\s*\(\s*\)\s*->\s*(?:findAll|find|count)\s*\(\s*" + V + r"\s*\)",
                 lambda m: tail, out)

    if re.search(V + r'\b', out):
        left = sorted(set(re.findall(V + r'\s*->\s*(\w+)', out))) or ['a bare reference']
        return original, False, 'CDbCriteria uses ' + ', '.join(left)

    return restore(out), True, ''


def split_two(args):
    bits = split_args_php(args)
    return (bits[0].strip(), bits[1].strip()) if len(bits) >= 2 else (args, "''")


def split_args_php(args):
    """Split a PHP argument list on top-level commas."""
    out, depth, cur = [], 0, ''
    i = 0
    while i < len(args):
        c = args[i]
        if c in ('"', "'"):
            q = c
            cur += c
            i += 1
            while i < len(args):
                cur += args[i]
                if args[i] == '\\':
                    i += 2
                    if i <= len(args):
                        cur += args[i - 1]
                    continue
                if args[i] == q:
                    break
                i += 1
            i += 1
            continue
        if c in '([{':
            depth += 1
        elif c in ')]}':
            depth -= 1
        if c == ',' and depth == 0:
            out.append(cur)
            cur = ''
        else:
            cur += c
        i += 1
    if cur.strip():
        out.append(cur)
    return out


def yii1_idioms(text):
    """
    The Yii 1 -> Yii 2 conversions any carried-over snippet needs.

    Used for the concrete model's methods and for the generated option helpers
    alike. The helpers used to be copied with only their array syntax updated,
    which is why a `Yii::log` inside getMrsVendorOptions() survived every fix
    to the logging rules - the rules were only ever applied to the other half
    of the file.
    """
    text = re.sub(r'Yii::app\s*\(\s*\)\s*->', 'Yii::$app->', text)
    text = re.sub(r'Yii::app\s*\(\s*\)', 'Yii::$app', text)

    text = re.sub(r"CVarDumper::dumpAsString\s*\(", 'var_export(', text)
    text = re.sub(r"Yii::log\s*\(([^;]*?),\s*CLogger::LEVEL_ERROR\s*,\s*('[^']*')\s*\)",
                  lambda m: 'Yii::error(' + m.group(1) + ', ' + m.group(2) + ')', text)
    text = re.sub(r"Yii::log\s*\(([^;]*?),\s*CLogger::LEVEL_\w+\s*,\s*('[^']*')\s*\)",
                  lambda m: 'Yii::warning(' + m.group(1) + ', ' + m.group(2) + ')', text)

    text = re.sub(r'\b(?:Gx|C)Html::encode\s*\(', 'Html::encode(', text)
    text = re.sub(r'\b(?:Gx|C)Html::link\s*\(', 'Html::a(', text)
    text = re.sub(r'\b(?:Gx|C)Html::image\s*\(', 'Html::img(', text)
    text = re.sub(r'\bGxHtml::valueEx\s*\(', 'Gx::str(', text)

    M = r"(\w+)::model\s*\(\s*\)\s*->\s*"
    text = re.sub(M + r"(?i:findByPk)\s*\(", lambda m: m.group(1) + '::findOne(', text)
    text = re.sub(M + r"(?i:findByAttributes)\s*\(", lambda m: m.group(1) + '::findOne(', text)
    text = re.sub(M + r"(?i:findAllByAttributes)\s*\(", lambda m: m.group(1) + '::findAll(', text)
    text = re.sub(M + r"findAll\s*\(\s*\)", lambda m: m.group(1) + '::find()->all()', text)
    text = re.sub(M + r"count\s*\(\s*\)", lambda m: m.group(1) + '::find()->count()', text)

    return text


def port_concrete(src, model):
    """
    Translates the hand-written methods on protected/models/<Model>.php.

    These are ordinary Yii 1 application code: CDbCriteria queries, findAll,
    Yii::app(). The same conversions the controllers get apply, and whatever is
    left unconverted is reported rather than guessed at.
    """
    notes = []
    out = []
    # The models are indented with tabs in some files and spaces in others, so
    # this cannot anchor on a tab: doing so skipped every method in those files
    # and produced a model that looked complete and was missing its helpers.
    # The parameter list is matched by finding the brace that opens the body,
    # not by [^)]*: a default of `array()` contains a closing paren, and the
    # naive pattern stopped there and skipped the method entirely. getColumns()
    # is declared that way on nine models.
    for m in re.finditer(r'\n[ \t]+((?:public|protected|private)?\s*(?:static\s+)?'
                         r'function\s+(\w+)\s*\()', src):
        name = m.group(2)
        if name == 'model':
            continue        # Yii 1's static model() has no Yii 2 equivalent
        if name in CLASHES_WITH_YII2:
            # Yii 2's ActiveRecord already declares these, with signatures the
            # Yii 1 versions do not match - City::toArray() against
            # Model::toArray(array $fields = [], ...) is a fatal error. The API
            # port renamed the ones it needed (toArray became toApiArray).
            notes.append('%s(): not ported - the name is a Yii 2 base method' % name)
            continue
        # walk the parameter list to its closing paren
        i = m.end(1)
        depth = 1
        while i < len(src) and depth:
            if src[i] == '(':
                depth += 1
            elif src[i] == ')':
                depth -= 1
            i += 1
        brace = src.find('{', i)
        if brace < 0:
            continue
        i = brace + 1
        depth = 1
        while i < len(src) and depth:
            if src[i] == '{':
                depth += 1
            elif src[i] == '}':
                depth -= 1
            i += 1
        text = src[m.start(1):i]

        if name in YII2_PUBLIC_HOOKS:
            # No lifecycle hook is carried over, whether it writes or not.
            # Yii 1 and Yii 2 declare them with different signatures -
            # afterSave() against afterSave($insert, $changedAttributes) - and
            # different visibility, so the Yii 1 version is never a valid
            # override. Order::afterSave() reached the model the API uses twice
            # this way: once failing on visibility, once on the signature, and
            # both times the class could not be loaded at all.
            notes.append('%s(): not ported - Yii 2 declares this hook differently' % name)
            continue

        text = arrays(text)
        text = re.sub(r'Yii::app\(\)->', 'Yii::$app->', text)
        text = re.sub(r'Yii::app\(\)', 'Yii::$app', text)

        # CDbCriteria -> a query built the Yii 2 way
        text, converted, why = convert_criteria(text)
        if not converted and '$criteria' in text:
            notes.append('%s(): %s, left as it was' % (name, why))

        # Yii 1 declares the lifecycle hooks protected; Yii 2 declares them
        # public, and PHP refuses to narrow a method's visibility - the class
        # then fails to load at all, taking down every page that touches the
        # model. The hook still runs; only the keyword changes.
        if name in YII2_PUBLIC_HOOKS:
            text = re.sub(r'\b(?:protected|private)(\s+(?:static\s+)?function\s+' + name + r'\b)',
                          lambda mm: 'public' + mm.group(1), text)

        # The shared conversions - logging, the Html helpers, the finders.
        # These used to be repeated here; they are one function now, because a
        # copy of them in the other half of the file is how a Yii::log survived
        # every fix to the logging rules.
        text = yii1_idioms(text)

        # Named explicitly: "C followed by a capital" also matches this
        # application's own constants - CGST, CESS - and reported them as
        # unconverted framework classes on every model with a tax column.
        leftover = set(re.findall(YII1_CLASSES, text)) | set(re.findall(r'Yii::app\s*\(', text))
        leftover |= set(re.findall(r'(\w+)::model\(\)', text))
        if leftover:
            notes.append('%s(): still contains Yii 1 code - %s'
                         % (name, ', '.join(sorted(str(x) for x in leftover))))

        # reindent from one tab to four spaces
        text = '\n'.join(('    ' + ln.replace('\t', '    ')).rstrip() if ln.strip() else ''
                         for ln in text.splitlines())
        out.append(text)
    return out, notes


def generate(model, table_alias):
    base = read(f'{ROOT}/protected/models/_base/Base{model}.php')
    concrete = read(f'{ROOT}/protected/models/{model}.php')
    warn = []

    consts = re.findall(r'const\s+(\w+)\s*=\s*([^;]+);', base)
    props = public_properties(base, concrete)

    # the getXOptions helpers, copied across with their array syntax updated
    options = []
    for m in re.finditer(r'(public\s+static\s+function\s+get\w+Options\s*\([^)]*\)\s*\{)', base):
        body = parse_block(base, re.search(r'function\s+(get\w+Options)', m.group(1)).group(1))
        name = re.search(r'function\s+(get\w+Options)', m.group(1)).group(1)
        options.append((name, arrays(body)))

    tbl = re.search(r"tableName\s*\(\)\s*\{\s*return\s*'\{\{(\w+)\}\}'", base)
    table = tbl.group(1) if tbl else table_alias

    rep = re.search(r"representingColumn\s*\(\)\s*\{\s*return\s*'(\w+)'", base)
    representing = rep.group(1) if rep else 'id'

    lbl = re.search(r"label\s*\(\s*\$n\s*=\s*1\s*\)\s*\{\s*return\s*Yii::t\s*\(\s*'app'\s*,\s*'([^|]*)\|([^']*)'", base)
    singular, plural = (lbl.group(1), lbl.group(2)) if lbl else (model, model + 's')

    rules = port_rules(parse_block(base, 'rules') or '', warn)
    rels = port_relations(parse_block(base, 'relations') or '', warn)
    labels = port_labels(parse_block(base, 'attributeLabels') or '',
                         parse_block(base, 'relations') or '')

    # A property whose name is also a column must not be declared. Yii 2 reads
    # and writes columns through __get/__set on the attribute array, and a real
    # declared property shadows that entirely - the column is then never
    # populated from the row. Outlet::$bill_prefix was declared this way and
    # its update form came back empty where Yii 1 shows the stored value.
    #
    # The Yii 1 base declares both kinds together, so the column list decides:
    # anything in attributeLabels() is a column or a relation, and only what is
    # left is a genuine extra property.
    # The column list comes from the database, because nothing in the source
    # is complete. attributeLabels() does not mention every column, and the
    # giix docblock is stale - Outlet's omits bill_prefix, a column added after
    # the model was generated. Declaring a property for a real column shadows
    # Yii 2's attribute handling and the column is never populated, which is
    # what emptied Outlet's update form.
    known = columns_of(table) | {n for n, _ in labels}
    dropped = [n for n, _ in props if n.lstrip('$') in known]
    props = [(n, d) for n, d in props if n.lstrip('$') not in known]
    for n in dropped:
        warn.append('property %s not declared - it is a column, and declaring '
                    'it would shadow the attribute' % n)
    search_body = parse_block(base, 'search') or ''
    exact, partial = port_search(search_body, warn)
    eager = port_with(search_body)
    search_sort = port_search_sort(search_body)

    has_before_validate = 'function beforeValidate' in base
    default_order = port_default_scope(base, warn, concrete)

    # The concrete model's own methods. These are hand-written - option lists
    # built from another table, computed columns for a report - and the views
    # call them, so leaving them behind gives a page that renders until it hits
    # one. They are translated with the same rules as the controllers and
    # anything unrecognised is reported.
    concrete_methods, notes = port_concrete(concrete, model)
    warn += notes

    L = []
    A = L.append
    A('<?php')
    A('namespace app\\models;')
    A('')
    A('use app\\components\\Criteria;')
    A('use app\\components\\Gx;')
    A('use app\\components\\Ui;')
    A('use Yii;')
    A('use yii\\data\\ActiveDataProvider;')
    A('use yii\\db\\ActiveRecord;')
    A('use yii\\helpers\\Html;')
    A('')
    A('/**')
    A(' * Ported from protected/models/%s.php and its giix base class.' % model)
    A(' */')
    A('class %s extends ActiveRecord' % model)
    A('{')
    A('    // Yii 1 hands out column values as strings; the option helpers below')
    A('    // compare them loosely and give the wrong answer for an integer 0.')
    A('    use LegacyColumnTypes;')
    A('')
    for n, v in consts:
        A('    public const %s = %s;' % (n, v.strip()))
    if consts:
        A('')
    if props:
        A('    // Declared on the Yii 1 model and not columns: the forms post to')
        A('    // these and the actions assign them. Yii 2 throws on an unknown')
        A('    // property, so the declarations have to come across.')
        for n, default in props:
            A('    public %s%s;' % (n, ' = ' + default if default else ''))
        A('')
    A('    public static function tableName()')
    A('    {')
    A("        return '{{%%%s}}';" % table)
    A('    }')
    A('')
    A("    /** Yii 1's label(): the model's name, singular or plural. */")
    A('    public static function label($n = 1)')
    A('    {')
    A("        return $n == 1 ? '%s' : '%s';" % (singular, plural))
    A('    }')
    A('')
    A('    /** The column that stands for the whole row in a link or a breadcrumb. */')
    A('    public static function representingColumn()')
    A('    {')
    A("        return '%s';" % representing)
    A('    }')
    A('')
    A('    /** GxActiveRecord::__toString(): the representing column, or the id. */')
    A('    public function __toString()')
    A('    {')
    A("        $value = $this->hasAttribute('%s') ? $this->%s : null;" % (representing, representing))
    A('')
    A("        return (string) ($value === null || $value === '' ? $this->id : $value);")
    A('    }')
    A('')
    A('    /**')
    A("     * The ordering Yii 1's defaultScope() put on every query for this")
    A('     * model. Null means Yii 1 applied none, and neither should this:')
    A('     * an order Yii 1 never applied is an order the user never saw.')
    A('     */')
    A('    public static function defaultOrder()')
    A('    {')
    A('        return %s;' % default_order)
    A('    }')
    A('')
    A('    /**')
    A("     * Yii 1's CActiveRecord fills a new record with the column defaults")
    A('     * declared by the table; Yii 2 leaves them null until asked. Without')
    A("     * this a create form shows an empty box where Yii 1 shows 0.00, and")
    A('     * an insert writes NULL where Yii 1 writes the default.')
    A('     */')
    A('    public function init()')
    A('    {')
    A('        parent::init();')
    A('')
    A("        // Not in the search scenario. Yii 1 loaded the defaults and then")
    A("        // the admin action called unsetAttributes() to clear them; a")
    A('        // search model that keeps them filters the grid by every column')
    A('        // that has a default, which showed 4 rows where Yii 1 shows 11.')
    A("        if ($this->isNewRecord && $this->scenario !== 'search') {")
    A('            $this->loadDefaultValues();')
    A('        }')
    A('    }')
    A('')
    A('    /**')
    A("     * GxActiveRecord::isAllowCreate(): whether the session the operator")
    A('     * has selected is the current financial year.')
    A('     *')
    A("     * The year runs April to March, so a month past April belongs to")
    A("     * year..year+1 and anything earlier to year-1..year. Session names")
    A("     * are '<from>-<to>'. False when no session is selected, which is what")
    A('     * stops the create button appearing.')
    A('     */')
    A('    public function isAllowCreate()')
    A('    {')
    A("        $month = (int) date('m');")
    A("        $year = $month > 4 ? (int) date('Y') : (int) date('Y') - 1;")
    A('        $yearadd = $year + 1;')
    A('')
    A("        $selected = Yii::$app->session['select_session_id'];")
    A("        if ($selected === null || $selected === '') {")
    A('            return false;')
    A('        }')
    A('')
    A('        $session = Session::findOne($selected);')
    A('        if ($session === null) {')
    A('            return false;')
    A('        }')
    A("        $parts = explode('-', $session->name);")
    A('')
    A('        return isset($parts[0], $parts[1])')
    A('            && $parts[0] == $year && $parts[1] == $yearadd;')
    A('    }')
    A('')
    A('    /**')
    A("     * The order this model's listings use.")
    A('     *')
    A("     * The grid's own sort when search() names one, otherwise whatever")
    A('     * defaultScope() applies. Both the admin grid and the index listing')
    A('     * read this, so the two cannot drift apart.')
    A('     */')
    A('    public static function listingOrder()')
    A('    {')
    A('        return %s;' % (search_sort or 'self::defaultOrder()'))
    A('    }')
    A('')
    A('    /** Views ask the model whether the current role may reach a route. */')
    A('    public function checkPermission($url)')
    A('    {')
    A('        return \\app\\components\\Access::check($url);')
    A('    }')
    A('')
    A('    /**')
    A("     * GxActiveRecord::getRelationLabel(). The generated attributeLabels()")
    A('     * above already resolves a relation or foreign key to the related')
    A("     * model's label, so this is the attribute label.")
    A('     */')
    A('    public function getRelationLabel($name, $n = null)')
    A('    {')
    A('        return $this->getAttributeLabel($name);')
    A('    }')
    A('')
    A('    /**')
    A('     * GxActiveRecord::getTotals(): the SUM of one column over a set of')
    A('     * ids, which the grids use for a footer row.')
    A('     *')
    A('     * The column and table names are interpolated, as in Yii 1 - the')
    A('     * call sites pass literals. The ids are bound, which Yii 1 did not:')
    A('     * they come from the data provider rather than the request, so this')
    A('     * is not a fix for anything, only a refusal to build the same hole')
    A('     * again.')
    A('     */')
    A('    public function getTotals($ids, $columnname, $tablename)')
    A('    {')
    A('        if (empty($ids)) {')
    A('            return null;')
    A('        }')
    A('')
    A('        $placeholders = [];')
    A('        $params = [];')
    A('        foreach (array_values($ids) as $i => $id) {')
    A("            $placeholders[] = ':id' . $i;")
    A("            $params[':id' . $i] = $id;")
    A('        }')
    A('')
    A('        return Yii::$app->db->createCommand(')
    A("            'SELECT SUM(' . $columnname . ') FROM ' . $tablename")
    A("            . ' WHERE id IN (' . implode(',', $placeholders) . ')', $params)")
    A('            ->queryScalar();')
    A('    }')
    A('')
    A('    /** GxActiveRecord::getRelatedDataProvider(): the rows of a relation. */')
    A('    public function getRelatedDataProvider($relation, $config = [])')
    A('    {')
    A('        $getter = \'get\' . ucfirst($relation);')
    A('        if (!method_exists($this, $getter)) {')
    A('            throw new \\yii\\base\\InvalidArgumentException(')
    A("                get_class($this) . ' does not have relation \"' . $relation . '\".');")
    A('        }')
    A('')
    A('        return new ActiveDataProvider(array_merge(')
    A("            ['query' => $this->$getter(), 'pagination' => ['pageSize' => Ui::PAGE_SIZE]],")
    A('            $config));')
    A('    }')
    for name, body in options:
        # Yii 1 writes `if ($id == null) return $list;`, which is correct only
        # because its PDO hands out strings: '0' == null is false. Yii 2 casts
        # the column to int 0, which *is* == null, and the helper then returns
        # the whole options array - rendered as the word "Array", or as an
        # "Array to string conversion" error. Making the test explicit gives
        # Yii 1's answer for a string, an int and a real null alike.
        body = yii1_idioms(body)
        body = re.sub(r'\$id\s*==\s*null', "$id === null || $id === ''", body)
        body = re.sub(r'\$id\s*!=\s*null', "$id !== null && $id !== ''", body)
        A('')
        A('    public static function %s($id = null)' % name)
        A('    {' + body.rstrip() + '\n    }')
    if has_before_validate:
        A('')
        A('    /**')
        A('     * Port of the base model\'s beforeValidate(): stamps the row with who')
        A('     * created or changed it and when. Yii 1 ran this on every save, so a')
        A('     * row written by the port has to carry the same stamps.')
        A('     */')
        A('    public function beforeValidate()')
        A('    {')
        A('        if (!parent::beforeValidate()) {')
        A('            return false;')
        A('        }')
        A('        if ($this->isNewRecord) {')
        A("            if ($this->hasAttribute('create_time') && !isset($this->create_time)) {")
        A("                $this->create_time = date('Y-m-d H:i:s');")
        A('            }')
        A("            if ($this->hasAttribute('create_user_id') && !isset($this->create_user_id)) {")
        A('                $this->create_user_id = Yii::$app->user->id;')
        A('            }')
        A("        } elseif ($this->hasAttribute('updated_by') && !isset($this->updated_by)) {")
        A('            $this->updated_by = Yii::$app->user->id;')
        A('        }')
        A('')
        A('        return true;')
        A('    }')
    A('')
    A('    public function rules()')
    A('    {')
    A('        return [')
    for r in rules:
        A('            %s,' % r)
    A('        ];')
    A('    }')
    A('')
    A('    public function attributeLabels()')
    A('    {')
    A('        return [')
    for n, v in labels:
        A("            '%s' => '%s'," % (n, v.replace("'", "\\'")))
    A('        ];')
    A('    }')
    A('')
    A('    /**')
    A('     * Backs the admin grid.')
    A('     *')
    A("     * The comparison rules are Yii 1's, and there is deliberately no")
    A('     * validate() call: the generated search() compares whatever is set and')
    A('     * never validates, and a required rule with no `on` clause would')
    A('     * otherwise reject every filtered request and return the full list.')
    A('     */')
    A('    public function search($params = [])')
    A('    {')
    A('        $query = self::find();')
    if eager:
        A('        // Yii 1 eager-loads these, by JOIN, in the same query. That is')
        A('        // part of the result and not just an optimisation: where the')
        A('        // listing has no ORDER BY, the join decides which rows the')
        A('        // first page shows.')
        A("        $query->joinWith([%s]);" % ', '.join("'%s'" % x for x in eager))
    A('        $provider = new ActiveDataProvider([')
    A("            'query' => $query,")
    A("            // The order goes on the query, not on the provider's sort.")
    A('            // Yii 1 sets it on the criteria, and three of these listings')
    A("            // order by a joined column - 'item.title' - which Yii 2's Sort")
    A('            // rejects as a key unless it is declared as a sortable')
    A('            // attribute. orderBy takes it as written.')
    A("            'sort' => ['defaultOrder' => []],")
    A("            'pagination' => ['pageSize' => Ui::PAGE_SIZE],")
    A('        ]);')
    A('')
    A('        if (self::listingOrder()) {')
    A('            $query->orderBy(self::listingOrder());')
    A('        }')
    A('')
    A('        $this->load($params, $this->formName());')
    A('')
    if exact:
        A('        foreach ([%s] as $attr) {' % ', '.join("'%s'" % c for c in exact))
        A('            Criteria::compare($query, $attr, $this->$attr);')
        A('        }')
    if partial:
        A('        foreach ([%s] as $attr) {' % ', '.join("'%s'" % c for c in partial))
        A('            Criteria::compare($query, $attr, $this->$attr, true);')
        A('        }')
    A('')
    A('        return $provider;')
    A('    }')
    for text in concrete_methods:
        A('')
        A(text.rstrip())
    for getter, body in rels:
        A('')
        A('    public function %s()' % getter)
        A('    {')
        A(body)
        A('    }')
    A('}')
    return '\n'.join(L) + '\n', warn


def methods_of(src):
    return set(re.findall(r'function\s+(\w+)\s*\(', src))


def method_blocks(src):
    """Each method in the generated model, as (name, text including doc comment)."""
    out = []
    for m in re.finditer(r'\n(    (?:/\*\*.*?\*/\n    )?(?:public|protected|private)?\s*'
                         r'(?:static\s+)?function\s+(\w+)\s*\([^)]*\)\s*\{)', src, re.S):
        start = m.start(1)
        i = m.end(1)
        depth = 1
        while i < len(src) and depth:
            if src[i] == '{':
                depth += 1
            elif src[i] == '}':
                depth -= 1
            i += 1
        out.append((m.group(2), src[start:i]))
    return out


def replace_method(src, name, new_text):
    """
    Swap one method out of a file.

    Uses method_blocks() rather than its own pattern. A first version matched
    the method with a regex whose optional leading doc-comment could start the
    match above the class declaration, and replacing from there deleted the
    class line and every method before it - a file that no longer parsed.
    """
    for mname, text in method_blocks(src):
        if mname != name:
            continue
        i = src.find(text)
        if i < 0:
            return src
        return src[:i] + new_text.strip('\n') + src[i + len(text):]

    return src


def search_rule_of(generated):
    """The `safe` rule for the search scenario, which backs the grid filters."""
    body = None
    for name, text in method_blocks(generated):
        if name == 'rules':
            body = text
            break
    if body is None:
        return None
    m = re.search(r"(\[\[[^\]]*\],\s*'safe',\s*'on'\s*=>\s*'search'\])", body, re.S)

    return m.group(1) if m else None


def merge(existing, generated, model, warn):
    """
    Add what the UI needs to a model the API port already wrote.

    The API models were ported by hand and carry behaviour the generator knows
    nothing about, so nothing existing is replaced - only the methods that are
    missing are appended. Where both define a method the hand-written one wins
    and the difference is reported, because a generated search() silently
    replacing a hand-tuned one is exactly the kind of change that passes its
    own tests and breaks something else.
    """
    have = methods_of(existing)
    added = []

    # Two methods are replaced rather than kept. Both are derived wholly from
    # the Yii 1 source and neither affects how the model validates or saves:
    #
    #   attributeLabels() - an earlier run of this generator wrote these with a
    #     naive fallback, so a relation showed as "Create User" where Yii 1
    #     shows "User". Keeping that would preserve the bug.
    #   defaultOrder() - likewise derived from defaultScope().
    #   get*Options() - label lists read straight out of the Yii 1 model. An
    #     earlier merge appended one of these into a hand-written model, and it
    #     then kept the Yii 1 logging calls inside it through every later fix,
    #     because a file-level provenance check cannot see that one method in a
    #     hand-written file came from here.
    #   init() - the generator's own, which loads the column defaults. It is
    #     derived entirely from this pipeline's rules, and an earlier version
    #     of it loaded defaults in the search scenario too, which filtered the
    #     grid by every column that has one.
    replace = ['attributeLabels', 'defaultOrder', 'listingOrder', 'init']
    replace += [n for n, _ in method_blocks(generated) if re.match(r'get\w*Options$', n)]
    for name in replace:
        if name not in have:
            continue
        for gname, gtext in method_blocks(generated):
            if gname != name:
                continue
            existing = replace_method(existing, name, gtext)
            # `have` deliberately keeps the name: the method is now the
            # generated one, and the append loop below must not add a second
            # copy of it.
            added.append(name + ' (replaced)')
            break
    body = existing.rstrip()
    assert body.endswith('}'), 'model does not end in a class brace'
    body = body[:-1].rstrip()

    for name, text in method_blocks(generated):
        if name in have:
            warn.append('kept the existing %s(); the generated one was discarded' % name)
            continue
        body += '\n\n' + text.strip('\n')
        added.append(name)

    # public properties the generated model declares and the existing one
    # lacks. Emp declares $username and $password - not columns, but the form
    # posts to them and the update action assigns them, and Yii 2 throws
    # "Setting unknown property" the moment it does.
    # Only the properties the generated model actually declares reach here -
    # the column-shadowing ones were already filtered out above.
    for m in re.finditer(r'(?m)^    public (\$\w+)(\s*=\s*[^;]+)?;', generated):
        name = m.group(1)
        if re.search(r'(?m)^\s*public\s+' + re.escape(name) + r'\s*[;=]', body):
            continue
        body = re.sub(r'(class\s+\w+\s+extends\s+\w+\s*\{)',
                      lambda mm: mm.group(1) + '\n    public %s%s;' % (name, m.group(2) or ''),
                      body, count=1)
        added.append('property ' + name)

    # constants the generated model declares and the existing one lacks
    for m in re.finditer(r'    public const (\w+) = ([^;]+);', generated):
        if ('const ' + m.group(1)) not in existing:
            body = re.sub(r'(class\s+\w+\s+extends\s+\w+\s*\{)',
                          lambda mm: mm.group(1) + '\n    public const %s = %s;' % (m.group(1), m.group(2)),
                          body, count=1)
            added.append('const ' + m.group(1))

    # A model the API port wrote has its own rules(), which governs saving and
    # is left alone. But without the search-scenario `safe` rule the grid
    # renders no filters at all - Yii 2 will not build a filter input for an
    # attribute that is not safe in the current scenario. That one entry is
    # additive: it applies only in the search scenario, which nothing but the
    # grid uses.
    if 'rules' in have and "'on' => 'search'" not in body:
        rule = search_rule_of(generated)
        if rule:
            m = re.search(r"(function\s+rules\s*\([^)]*\)\s*\{\s*return\s*\[)", body)
            if m:
                body = body[:m.end()] + '\n            ' + rule + ',' + body[m.end():]
                added.append('the search-scenario safe rule')
            else:
                warn.append('rules() is not a plain return [ ... ]; the search '
                            'rule was not added, so the grid will have no filters')

    if 'LegacyColumnTypes' not in existing and 'presentation' not in ' '.join(warn):
        body = re.sub(r'(class\s+\w+\s+extends\s+\w+\s*\{)',
                      lambda mm: mm.group(1) +
                      '\n    // Yii 1 hands out column values as strings; the option helpers'
                      '\n    // compare them loosely and answer wrongly for an integer 0.'
                      '\n    use LegacyColumnTypes;\n',
                      body, count=1)
        added.append('use LegacyColumnTypes')

    # The use statements the added methods need. The test is on what the file
    # now references, not on a substring of the import itself - an earlier
    # version compared against the last segment of the namespace and so never
    # added `use Yii;`, which made every merged model that calls Yii::$app look
    # for app\models\Yii.
    needed = [
        ('use Yii;', r'\bYii::'),
        ('use yii\\data\\ActiveDataProvider;', r'\bActiveDataProvider\b'),
        ('use app\\components\\Criteria;', r'\bCriteria::'),
        ('use app\\components\\Ui;', r'\bUi::'),
        ('use app\\components\\Gx;', r'\bGx::'),
        ('use yii\\helpers\\Html;', r'\bHtml::'),
    ]
    for stmt, pattern in needed:
        if stmt in body or not re.search(pattern, body):
            continue
        body = re.sub(r'(namespace app\\models;\n)',
                      lambda mm: mm.group(1) + '\n' + stmt + '\n', body, count=1)

    return body + '\n}\n', added


# Methods a model needs only so that *other* models' pages can display it: its
# name, its string form, the permission helper the views call. Deliberately not
# rules(), search() or beforeValidate() - those change how the model validates
# and saves, and these models are already in use by the ported API.
PRESENTATION_ONLY = {'label', 'representingColumn', '__toString', 'checkPermission',
                     'getRelationLabel', 'defaultOrder', 'listingOrder',
                     'getTotals', 'isAllowCreate'}

# Never added to a model the API port wrote, whatever else says otherwise.
# These decide how the model validates, what it saves and what a listing
# returns, and the whole premise of presentation-only is that it cannot change
# any of that. The read-only test is not enough on its own: rules() and
# search() read nothing and write nothing, and adding them to Order anyway
# would change what the order API validates on every save.
NEVER_SHARED = {'rules', 'search', 'beforeValidate', 'init', 'scenarios',
                'behaviors', 'transactions', 'primaryKey', 'tableName',
                'optimisticLock', 'attributeHints'}


def write_model(path, content, what):
    """
    Write a model only if the result parses.

    The generators rewrite code they do not fully understand, and a rewrite
    that produces a syntax error takes the whole application down rather than
    one page. It happened: a fetch inside a commented-out line was rewritten
    into a multi-line expression, whose continuation escaped the `//` and broke
    app2/models/Order.php - a model the *API* depends on. Nothing in the
    pipeline noticed, because the batch only linted the model belonging to the
    controller it was porting.

    So every write is checked, and a bad one is refused rather than saved.
    """
    previous = None
    if os.path.exists(path):
        previous = read(path)

    open(path, 'w', encoding='utf-8').write(content)

    rel = os.path.relpath(path, ROOT)

    # Syntax first...
    check = subprocess.run(
        ['docker', 'exec', 'pos-php-83', 'php', '-l', '/var/www/html/' + rel],
        capture_output=True, text=True)
    if check.returncode != 0:
        return refuse(path, previous, what, check)

    # ...then actually load the class. php -l does not catch a fatal that only
    # happens at class-load time, and the one that matters here is visibility:
    # Yii 1 declares the lifecycle hooks protected, Yii 2 declares them public,
    # and PHP refuses to narrow. The file parsed perfectly and the class could
    # not be loaded, which took ten suites down at once.
    cls = 'app\\models\\' + os.path.basename(path)[:-4]
    load = subprocess.run(
        ['docker', 'exec', 'pos-php-83', 'php', '-r',
         'require "/var/www/html/vendor/autoload.php";'
         'require "/var/www/html/vendor/yiisoft/yii2/Yii.php";'
         'Yii::setAlias("@app", "/var/www/html/app2");'
         'spl_autoload_register(function($c){'
         '  $f = "/var/www/html/app2/" . str_replace("\\\\", "/", substr($c, 4)) . ".php";'
         '  if (is_file($f)) require_once $f;'
         '});'
         'new ReflectionClass("' + cls + '");'],
        capture_output=True, text=True)
    if load.returncode != 0 and 'Fatal error' in (load.stdout + load.stderr):
        return refuse(path, previous, what, load)

    return True

    return True


def refuse(path, previous, what, result):
    """Put the file back as it was and say why the new one was rejected."""
    if previous is None:
        os.remove(path)
    else:
        open(path, 'w', encoding='utf-8').write(previous)
    print('  REFUSED to write %s: the generated code does not load' % what)
    for line in (result.stdout or result.stderr).splitlines()[:2]:
        if line.strip():
            print('    ' + line.strip())
    return False


if __name__ == '__main__':
    deps_only = '--presentation-only' in sys.argv
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    model = args[0]
    src, warn = generate(model, model.lower())

    if deps_only:
        rels = relations_of(model)
        # Computed before the rebuild below, which removes them - otherwise the
        # note reports that nothing was skipped, which is the opposite of what
        # happened.
        denied = sorted({n for n, _ in method_blocks(src)} & NEVER_SHARED)
        keep = []
        warn = list(warn)
        # Constants come across too. They declare no behaviour - another
        # model's query that says Vendor::STATUS_ACTIVE needs the constant to
        # exist, and adding it cannot change how Vendor itself validates or
        # saves.
        for m in re.finditer(r'    public const \w+ = [^;]+;', src):
            keep.append(m.group(0))
        relation_getters = {'get' + n[0].upper() + n[1:] for n, _ in rels}
        for name, text in method_blocks(src):
            # Three things come across. The get*Options() helpers are static
            # label lists that other models' grids call. The relation getters
            # are navigation - a page that lists role permissions reads
            # $data->role. Neither reads nor changes how this model validates
            # or saves, so both are as safe to add as a constant.
            if name in NEVER_SHARED:
                continue
            if re.match(r'(?:before|after)[A-Z]', name):
                # A lifecycle hook is behaviour, not display: copying
                # beforeDelete() into a model the API port wrote would change
                # what deleting one does. (Yii 2 also requires them public,
                # which Yii 1 does not, so they cannot be copied verbatim.)
                continue
            if re.search(YII1_CLASSES, text):
                # Still contains Yii 1 code the translator did not recognise.
                # Adding it gives a page that fails at the point it calls the
                # method; leaving it out gives the same failure, one call
                # earlier and with a name attached.
                warn.append('%s(): not shared - still contains Yii 1 code' % name)
                continue
            if (name in PRESENTATION_ONLY
                    or re.match(r'get\w*Options$', name)
                    or name in relation_getters
                    or read_only(text)):
                keep.append(text)
        header = src.split('class ')[0]
        src = (header + 'class %s extends ActiveRecord\n{\n' % model
               + '\n\n'.join(t.strip('\n') for t in keep) + '\n}\n')
        warn = ['presentation-only merge into a model used elsewhere; not added: '
                + (', '.join(denied) if denied else 'nothing in the deny list was present')]
    out = f'{ROOT}/app2/models/{model}.php'

    if os.path.exists(out):
        existing = read(out)
        merged, added = merge(existing, src, model, warn)
        if write_model(out, merged, 'app2/models/%s.php' % model):
            print('merged into app2/models/%s.php: %s'
                  % (model, ', '.join(added) if added else 'nothing to add'))
        else:
            sys.exit(3)
    else:
        if write_model(out, src, 'app2/models/%s.php' % model):
            print('wrote app2/models/%s.php' % model)
        else:
            sys.exit(3)

    for x in warn:
        print('  NOTE: ' + x)
