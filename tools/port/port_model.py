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
    from port_views import arrays_to_brackets, dump_as_string
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
    # Both `array(...)` and `[...]`. Only the first was matched, so a search()
    # that wrote `$criteria->with = ['item', 'itemDetail']` produced no join at
    # all - and its `$criteria->order = 'item.title asc'` then referred to a
    # table that was not in the query: "Unknown column 'item.title' in 'order
    # clause'".
    m = re.search(r"\$criteria\s*->\s*with\s*=\s*(array\s*\(|\[)", body)
    if not m:
        # A bare string is legal too: `$criteria->with = 'item';`. CDbCriteria's
        # setter splits it on commas, so 'a, b' means two relations. BaseMrnDetail
        # writes exactly that form, and while it went unhandled the join was
        # dropped - and the `$criteria->order = 'item.title asc'` on the next
        # line then named a table that was not in the query.
        s = re.search(r"\$criteria\s*->\s*with\s*=\s*([\x27\x22])(.*?)\1\s*;", body)
        if s:
            return [x.strip() for x in s.group(2).split(",") if x.strip()]
        return []

    opener = '(' if m.group(1).strip().startswith('array') else '['
    closer = ')' if opener == '(' else ']'
    i = m.end()
    depth = 1
    while i < len(body) and depth:
        if body[i] == opener:
            depth += 1
        elif body[i] == closer:
            depth -= 1
        i += 1
    block = body[m.end():i - 1]

    paths = []

    def walk(text, prefix):
        # 'name' => array('with' => array(...))   - a nested eager load
        for nm in re.finditer(r"'(\w+)'\s*=>\s*(?:array\s*\(|\[)\s*'with'\s*=>\s*"
                              r"(?:array\s*\(|\[)([^)\]]*)[)\]]", text, re.S):
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
        for nested in re.finditer(r"'(\w+)'\s*=>\s*(?:array\s*\(|\[)\s*'with'\s*=>\s*"
                                  r"(?:array\s*\(|\[)([^)\]]*)[)\]]", text, re.S):
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


def port_page_size(body):
    """
    The rows-per-page Yii 1's search() gives its provider.

    Read from the provider's `pagination` => `pageSize`, which the generator
    previously ignored entirely in favour of CPagination's default of 10.
    Item's admin grid asks for 100, so the port was showing a tenth of the
    rows.

    Only the first provider in the method counts: Item's search() has a second
    `return new CActiveDataProvider` after the first, with a different page
    size, which PHP never reaches.
    """
    if not body:
        return None
    stripped = re.sub(r'//[^\n]*', '', body)
    stripped = re.sub(r'/\*.*?\*/', '', stripped, flags=re.S)
    cut = stripped.find('return new CActiveDataProvider')
    if cut >= 0:
        nxt = stripped.find('return new CActiveDataProvider', cut + 1)
        if nxt >= 0:
            stripped = stripped[:nxt]
    # `'pagination' => false` is a third answer, not the absence of one: Yii 1
    # then returns every matching row and the grid has no pager at all.
    # MrnDetail, MrsDetail and PurchaseOrderDetail all switch it off, and
    # reading that as "no page size given" put 10 rows on a page where Yii 1
    # shows 19.
    if re.search(r"'pagination'\s*=>\s*false", stripped):
        return False

    m = re.search(r"'pageSize'\s*=>\s*'?(\d+)'?", stripped)

    return m.group(1) if m else None


def port_search(body, warn):
    """The compare() calls that back the admin grid."""
    # Both halves of the compare, not just the column. Yii 1 writes
    # `$criteria->compare('t.mrs_id', $this->mrs_id)`: the column carries the
    # table alias and the attribute does not. Keeping only the column and
    # reading `$this->{'t.mrs_id'}` gave null for every filter - and because
    # these three detail grids also turn pagination off, an unfiltered page
    # meant the whole table, which is why mrsDetail's comparison sat for
    # fifteen minutes before anyone looked at it.
    exact, partial = [], []
    for m in re.finditer(r"\$criteria->compare\s*\(\s*'([^']+)'\s*,\s*\$this->(\w+)\s*(,\s*true)?\s*\)", body):
        col, attr, is_partial = m.groups()
        (partial if is_partial else exact).append((col, attr))
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


DECL_RE = r'\$(\w+)\s*=\s*new\s+CDbCriteria\s*(?:\(\s*\))?\s*;'


def finder_re(var):
    return (r"(\w+)\s*::\s*model\s*\(\s*\)\s*->\s*(findAll|find|count)\s*\(\s*"
            r"\$" + re.escape(var) + r"\s*\)")


def query_var_for(var):
    """The Yii 2 variable a named criteria becomes."""
    m = re.match(r'criteria(\w*)$', var)
    if m:
        return 'query' + m.group(1)
    if var.endswith('Criteria'):
        return var[:-len('Criteria')] + 'Query'

    return var + 'Query'


def convert_criteria(text):
    """
    Rewrites a CDbCriteria query as a Yii 2 query, statement by statement.

    Per *declaration*, not per variable name, and not once for the method.

    Both shortcuts produced code that looked converted and was not.
    Keying on the name gave every criteria in a method the same `$query`, so
    FreeItemController::actionItemList(), which builds $criteria for the item
    details and $criteria1 for the vendor's items, had the second assignment
    overwrite the first and listed ItemVendor rows where Yii 1 lists ItemDetail
    rows. Converting a name once was worse: CustomerController's
    actionGetCustomerAddress() writes `$criteria = new CDbCriteria` twice, the
    first consumed by Customer::model()->findAll() and the second by
    City::model()->find(), and the single conversion gave the City lookup
    Customer::find() and ->all(), so `$city->id` ran against an array.

    So each `new CDbCriteria` gets its own region - from the declaration to the
    next declaration of the same variable - its own class and finder read from
    inside that region, and its own variable.

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

    decls = list(re.finditer(DECL_RE, text))
    if not decls:
        return original, False, 'no CDbCriteria'

    # Plan every region before rewriting any of it, so that a declaration this
    # cannot read leaves the whole method alone.
    plans = []
    seen = {}
    for i, d in enumerate(decls):
        var = d.group(1)
        end = len(text)
        for later in decls[i + 1:]:
            if later.group(1) == var:
                end = later.start()
                break
        fm = re.search(finder_re(var), text[d.start():end])
        if not fm:
            return original, False, 'the criteria is not passed to a finder this knows'

        # A name free in the method as the application wrote it, and not
        # already handed to another region. actionGetCustomerAddress() has its
        # own $query - the query string for a curl call - and the converted
        # criteria overwrote it. Refusing the method over that would leave it
        # as CDbCriteria, which is a page that dies rather than a page that
        # lies, but it is still a page that dies.
        base = query_var_for(var)
        taken = {pl[3] for pl in plans}
        name, n = base, 1
        while name in taken or re.search(r'\$' + re.escape(name) + r'\b', original):
            n += 1
            name = '%s_%d' % (base, n)
        seen[var] = seen.get(var, 0) + 1

        plans.append((d.start(), end, var, name, fm.group(1), fm.group(2)))

    # Regions of two different criteria interleave, so the edits are collected
    # against the original offsets and applied back to front rather than each
    # region being spliced in on its own.
    edits = []
    for start, end, var, name, cls, kind in plans:
        got, ok, why = region_edits(text, start, end, var, name, cls, kind)
        if not ok:
            return original, False, why
        edits.extend(got)

    out = text
    for span, replacement in sorted(edits, key=lambda e: -e[0][0]):
        out = out[:span[0]] + replacement + out[span[1]:]

    return restore(out), True, ''


def region_edits(text, start, end, var, name, cls, kind):
    """
    Every edit one criteria's region needs, as (span, replacement) pairs
    against `text`.

    Returns (edits, ok, reason). Not applied here: the caller holds the edits
    from every region and applies them in one pass, because two regions can
    overlap.
    """
    V = r'\$' + re.escape(var)
    Q = '$' + name
    region = text[start:end]
    edits = []
    claimed = []

    def take(m, replacement):
        edits.append(((start + m.start(), start + m.end()), replacement))
        claimed.append((m.start(), m.end()))

    decl = re.match(DECL_RE, region)
    scope = default_order_of(cls)
    stmt = Q + ' = ' + cls + '::find();'
    # An explicit order on the criteria, not the word 'order' anywhere in
    # the method. StockAdjustLog's search mentions order_id, which
    # suppressed the model's own id DESC and listed the oldest first.
    if scope and scope != 'null' and not re.search(r'->\s*order\s*=', region):
        stmt += '\n        ' + Q + '->orderBy(' + scope + ');'
    take(decl, stmt)

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
        return Q + '->orderBy([%s]);' % ', '.join(cols)

    # The value may be parenthesised - BaseItemReturn writes
    #  - and without allowing for that
    # the statement was left behind, the conversion refused the whole
    # method, and itemReturn/list died on a missing listsearch().
    for m in re.finditer(V + r"\s*->\s*order\s*=\s*\(?\s*'([^']+)'\s*\)?\s*;", region):
        take(m, order_by(m))
    for m in re.finditer(V + r"\s*->\s*select\s*=\s*([^;]+);", region):
        take(m, Q + '->select(%s);' % m.group(1).strip())
    for m in re.finditer(V + r"\s*->\s*group\s*=\s*([^;]+);", region):
        take(m, Q + '->groupBy(%s);' % m.group(1).strip())
    for m in re.finditer(V + r"\s*->\s*(?:limit|offset)\s*=\s*([^;]+);", region):
        which = 'limit' if '->limit' in m.group(0).replace(' ', '') else 'offset'
        take(m, Q + '->%s(%s);' % (which, m.group(1).strip().strip("'")))
    for m in re.finditer(V + r"\s*->\s*distinct\s*=\s*([^;]+);", region):
        take(m, Q + '->distinct(%s);' % m.group(1).strip())

    # `with` is a join, and the relation keeps its name. Yii 1 aliases an
    # eager-loaded table after the relation, and the order and conditions
    # around it say `item.title`; Yii 2 would join on the real table name and
    # that column would resolve to nothing.
    for m in re.finditer(V + r"\s*->\s*with\s*=\s*([^;]+);", region):
        paths = port_with('$criteria->with = %s;' % m.group(1).strip())
        if not paths:
            return [], False, 'CDbCriteria with a `with` this cannot read'
        take(m, Q + '->joinWith([%s]);' % ', '.join(
            "'%s' => function ($q) { $q->alias('%s'); }" % (x, x.split('.')[-1])
            for x in paths))

    # `condition` plus `params` is one andWhere; `params` on its own adds to
    # whatever conditions the add* calls have already put on the query.
    cond = re.search(V + r"\s*->\s*condition\s*=\s*([^;]+);", region)
    params = re.search(V + r"\s*->\s*params\s*=\s*([^;]+);", region)
    if cond:
        take(cond, Q + '->andWhere(%s%s);' % (
            cond.group(1).strip(), ', ' + params.group(1).strip() if params else ''))
        if params:
            take(params, '')
    elif params:
        take(params, Q + '->addParams(%s);' % params.group(1).strip())

    def helper(m):
        called, args = m.group(1), m.group(2).strip()
        if called == 'addCondition':
            return Q + '->andWhere(%s);' % args
        bits = split_args_php(args)
        if called == 'addInCondition' and len(bits) >= 2:
            return Q + '->andWhere([%s => %s]);' % (bits[0].strip(), bits[1].strip())
        if called == 'addNotInCondition' and len(bits) >= 2:
            return Q + "->andWhere(['not in', %s, %s]);" % (bits[0].strip(), bits[1].strip())
        if called == 'addBetweenCondition' and len(bits) >= 3:
            return Q + "->andWhere(['between', %s, %s, %s]);" % tuple(b.strip() for b in bits[:3])
        if called == 'addSearchCondition' and len(bits) >= 2:
            return Q + "->andWhere(['like', %s, %s]);" % (bits[0].strip(), bits[1].strip())
        if called == 'compare' and len(bits) >= 2:
            partial = ', true' if len(bits) > 2 and 'true' in bits[2] else ''
            return 'Criteria::compare(%s, %s, %s%s);' % (Q, bits[0].strip(), bits[1].strip(), partial)
        return None

    for m in re.finditer(V + r"\s*->\s*(addCondition|addInCondition|addNotInCondition|"
                         r"addBetweenCondition|addSearchCondition|compare)\s*\((.*?)\)\s*;",
                         region, flags=re.S):
        rep = helper(m)
        if rep is None:
            return [], False, 'CDbCriteria %s() with arguments this cannot read' % m.group(1)
        take(m, rep)

    tail = {'findAll': Q + '->all()', 'find': Q + '->one()', 'count': Q + '->count()'}[kind]
    for m in re.finditer(finder_re(var), region):
        take(m, tail)

    # Nothing may mention this criteria that has not been rewritten.
    for m in re.finditer(V + r'\b', region):
        if not any(a <= m.start() < b for a, b in claimed):
            rest = re.match(r'\s*->\s*(\w+)', region[m.end():])
            return [], False, ('CDbCriteria uses ' + rest.group(1) if rest
                               else 'a bare CDbCriteria reference')

    return edits, True, ''

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


def by_attributes_php(m):
    """X::model()->findAllByAttributes(attrs, options) as a Yii 2 query."""
    cls, kind, args = m.group(1), m.group(2), m.group(3)
    bits = split_args_php(args)
    cond = bits[0].strip() if bits else '[]'
    query = cls + '::find()'
    if cond not in ('[]', 'array()', ''):
        query += '->where(' + cond + ')'
    if len(bits) > 1:
        om = re.search(r"'order'\s*=>\s*'([^']+)'", bits[1])
        if om:
            cols = []
            for part in om.group(1).split(','):
                p2 = part.strip().split()
                if not p2:
                    continue
                col = p2[0]
                if col.startswith('t.'):
                    col = col[2:]
                desc = len(p2) > 1 and p2[1].lower().startswith('desc')
                cols.append("'%s' => %s" % (col, 'SORT_DESC' if desc else 'SORT_ASC'))
            if cols:
                query += '->orderBy([' + ', '.join(cols) + '])'

    return query + ('->all()' if kind.lower().startswith('findall') else '->one()')


def dump_as_string(text):
    """
    CVarDumper::dumpAsString($x) is var_export($x, true).

    Without the second argument var_export *prints*. The conversion dropped it,
    so every `Yii::log(CVarDumper::dumpAsString($x), ...)` the application
    carries - 136 of them - echoed its argument into the response body. On a
    page it was cosmetic; anywhere a header followed it was fatal, and the four
    order suites each lost a case to "Headers already sent, output started at
    MrsDetail.php:813".

    The argument is found by matching parentheses, not by regex: some of these
    dump an array literal.
    """
    out = []
    i = 0
    pattern = re.compile(r'CVarDumper::dumpAsString\s*\(')
    while True:
        m = pattern.search(text, i)
        if not m:
            out.append(text[i:])
            break
        out.append(text[i:m.start()])
        j = m.end()
        depth = 1
        while j < len(text) and depth:
            if text[j] == '(':
                depth += 1
            elif text[j] == ')':
                depth -= 1
            j += 1
        # Only the first argument. CVarDumper::dumpAsString($var, $depth,
        # $highlight) takes three; var_export takes two, and passing the depth
        # through gave "var_export() expects at most 2 arguments, 3 given".
        arg = split_args_php(text[m.end():j - 1])
        out.append('var_export(' + (arg[0].strip() if arg else '') + ', true)')
        i = j

    return ''.join(out)


GLOBAL_CLASSES = ('Exception', 'PDO', 'PDOException', 'DateTime', 'DateTimeZone', 'DateInterval', 'ZipArchive', 'SoapClient', 'DOMDocument', 'SimpleXMLElement', 'ReflectionClass', 'NumberFormatter', 'ArrayObject', 'mPDF', 'PHPExcel', 'PHPExcel_IOFactory')


def global_classes(text):
    """
    Put a leading backslash on the global classes a namespaced file names.

    `throw new Exception(...)` inside `namespace app\\controllers` means
    app\\controllers\\Exception, which does not exist; the same for `new PDO`
    and `catch (Exception $e)`. The port carried 78 of these, each a fatal on
    the line that runs it, and none on a page the CRUD suite reaches.

    Only the names below, and only where the reference is not already
    qualified.
    """
    for cls in GLOBAL_CLASSES:
        text = re.sub(r'(?<![\\$\w>])(new\s+)' + cls + r'\b',
                      lambda m: m.group(1) + '\\' + cls, text)
        text = re.sub(r'(?<![\\$\w>])(catch\s*\(\s*)' + cls + r'\b',
                      lambda m: m.group(1) + '\\' + cls, text)
        text = re.sub(r'(?<![\\$\w>])' + cls + r'(::)',
                      lambda m: '\\' + cls + m.group(1), text)

    return text


def scopes_of(cls):
    """A model's Yii 1 named scopes, as name -> condition."""
    path = '%s/protected/models/%s.php' % (ROOT, cls)
    try:
        text = read(path)
    except OSError:
        return {}
    body = parse_block(text, 'scopes')
    if not body:
        return {}

    out = {}
    for m in re.finditer(r"'(\w+)'\s*=>\s*(?:array\s*\(|\[)\s*'condition'\s*=>\s*([^,\)\]]+)",
                         body, re.S):
        out[m.group(1)] = m.group(2).strip()
    return out


def named_scopes(text):
    """
    Yii 1 named scopes, inlined.

    `User::model()->active()->findByAttributes(...)` chains a scope declared in
    scopes() - 'active' => array('condition' => 'state_id=...'). Yii 2 has no
    equivalent short of a query class per model, and only two models here
    declare any, so the condition is inlined at the call site instead. That is
    what Yii 1 does with it: applyScopes merges the condition into the criteria.

    Left alone the call is a fatal - User::model() does not exist - and it sat
    in two shipped models.
    """
    def one(m):
        cls, scope, finder, args = m.group(1), m.group(2), m.group(3), m.group(4)
        cond = scopes_of(cls).get(scope)
        if cond is None:
            return m.group(0)
        #  in the scope means the model that declared it, which is
        # not the class the call sits in: StockAdjustLog calls User's
        # 'active' scope, and self::STATUS_ACTIVE there is a constant that
        # does not exist.
        cond = re.sub(r'\bself::', cls + '::', cond)
        q = "%s::find()->andWhere(%s)" % (cls, cond)
        arg = args.strip()
        if finder.lower() == 'findall' and not arg:
            return q + '->all()'
        if finder.lower() == 'find' and not arg:
            return q + '->one()'
        if finder.lower() == 'count':
            return q + '->count()'
        if finder.lower() == 'findbyattributes':
            return q + '->andWhere(%s)->one()' % arg
        if finder.lower() == 'findallbyattributes':
            return q + '->andWhere(%s)->all()' % arg
        if finder.lower() == 'findbypk':
            return q + '->andWhere([%s::primaryKey()[0] => %s])->one()' % (cls, arg)
        return m.group(0)

    return re.sub(r'\b(\w+)::model\s*\(\s*\)\s*->\s*(\w+)\s*\(\s*\)\s*->\s*'
                  r'(\w+)\s*\((.*?)\)\s*(?=[;,\)\]])', one, text, flags=re.S)

def yii1_idioms(text):
    """
    The Yii 1 -> Yii 2 conversions any carried-over snippet needs.

    Used for the concrete model's methods and for the generated option helpers
    alike. The helpers used to be copied with only their array syntax updated,
    which is why a `Yii::log` inside getMrsVendorOptions() survived every fix
    to the logging rules - the rules were only ever applied to the other half
    of the file.
    """
    # Yii::import() has no Yii 2 equivalent; classes are autoloaded.
    text = re.sub(r"Yii::import\s*\([^;]*\);", '', text)

    text = re.sub(r'Yii::app\s*\(\s*\)\s*->', 'Yii::$app->', text)
    text = re.sub(r'Yii::app\s*\(\s*\)', 'Yii::$app', text)

    text = dump_as_string(text)
    # Qualify the global classes a namespaced file names. The guard that
    # was meant to add this line matched the helper's own definition, so
    # it never went in and only the controllers got it.
    text = global_classes(text)
    text = named_scopes(text)
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
    # See port_views.py: these take an options array Yii 2's findAll/findOne
    # do not, and an empty attributes array means "everything" in Yii 1 and
    # "nothing useful" through findAll().
    text = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*((?i:findAllByAttributes|findByAttributes))"
                  r"\s*\((.*?)\)\s*(?=[;,)\]])", by_attributes_php, text, flags=re.S)
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



# GxActiveRecord's item dropdown helpers, emitted as text rather than as a
# hundred A() calls. A model that defines its own - Discount and FreeItem
# both write a getItemOptions() listing item *detail* ids by bar code - keeps
# its own and does not get these.
SHARED_GET_ITEM_OPTIONS = '    /**\n     * GxActiveRecord::getItemOptions(): the active items, as id => \'title(mrp)\',\n     * for the item dropdowns.\n     *\n     * Restricted to a vendor\'s own items when the signed-in user holds the\n     * Vendor role, and again when a vendor id is passed. Both filters compare\n     * Item.id against ItemVendor.item_detail_id, which is what Yii 1 does. It\n     * reads like a mistake, but it is the list these dropdowns have always\n     * shown, so it is ported as it stands rather than corrected here.\n     *\n     * An empty id list is not "no filter": Yii 1\'s addInCondition() degrades to\n     * 0=1 and [\'id\' => []] does the same, so a vendor with no items gets an\n     * empty dropdown rather than every item in the catalogue.\n     */\n    public function getItemOptions($vendor_id = null)\n    {\n        $query = Item::find();\n\n        $role = UserRole::findOne([\'title\' => \'Vendor\']);\n        $user = Yii::$app->user->model;\n        if ($user && $role && $user->role_id == $role->id) {\n            $query->andWhere([\'id\' => self::vendorItemDetailIds(\n                [\'create_user_id\' => $user->id])]);\n        }\n        if ($vendor_id !== null) {\n            $query->andWhere([\'id\' => self::vendorItemDetailIds([\'id\' => $vendor_id])]);\n        }\n        $query->andWhere(\'status = \' . Item::STATUS_ACTIVE);\n        $query->orderBy(\'title asc\');\n\n        $list = [];\n        foreach ($query->all() as $item) {\n            $list[$item->id] = $item->title . \'(\' . $item->mrp . \')\';\n        }\n\n        return $list;\n    }'

SHARED_IDS_IN_BARCODE = "    /**\n     * GxActiveRecord::getItemOptionIdsInBarcode(): the ids of the items an\n     * itemDetail admin filter matches, which that grid then filters item_id by.\n     *\n     * The values are bound rather than interpolated into the condition as Yii 1\n     * does. For every value the grid can actually produce the two are the same\n     * query; this is not a fix for a reported problem, only a refusal to build\n     * the same hole again.\n     */\n    public function getItemOptionIdsInBarcode($match_item_id, $match_mrp, $match_hsn_code,\n        $match_product_code, $match_purchase_price, $match_company_id, $is_vendor)\n    {\n        $query = Item::find();\n\n        if ($match_item_id != null) {\n            $query->andWhere('title LIKE :title', [':title' => trim($match_item_id) . '%']);\n        }\n        if ($is_vendor == 1) {\n            $user = Yii::$app->user->model;\n            $query->andWhere(['id' => self::vendorItemDetailIds(\n                ['create_user_id' => $user->id])]);\n        }\n        if ($match_company_id != null) {\n            Criteria::compare($query, 'company_id', $match_company_id, true);\n        }\n        if ($match_mrp != null) {\n            $query->andWhere(['mrp' => $match_mrp]);\n        }\n        if ($match_hsn_code != null) {\n            $query->andWhere(['hsn_code' => $match_hsn_code]);\n        }\n        if ($match_product_code != null) {\n            $query->andWhere(['item_code' => $match_product_code]);\n        }\n        if ($match_purchase_price != null) {\n            Criteria::compare($query, 'purchase_price', $match_purchase_price);\n        }\n\n        return $query->select('id')->column();\n    }"

SHARED_MORE_GX = "    /**\n     * GxActiveRecord::getItemOptionIds(): the ids of the items the signed-in\n     * user may see.\n     *\n     * getItemOptions() filters on status and this does not, because Yii 1\n     * does not: the barcode dropdown this feeds lists inactive items too.\n     */\n    public function getItemOptionIds()\n    {\n        $query = Item::find();\n\n        $role = UserRole::findOne(['title' => 'Vendor']);\n        $user = Yii::$app->user->model;\n        if ($user && $role && $user->role_id == $role->id) {\n            $query->andWhere(['id' => self::vendorItemDetailIds(\n                ['create_user_id' => $user->id])]);\n        }\n\n        return $query->select('id')->column();\n    }\n\n    /**\n     * GxActiveRecord::getItemOptionbarcodes(): item detail id => bar code, for\n     * the items getItemOptionIds() allows.\n     */\n    public function getItemOptionbarcodes()\n    {\n        $list = [];\n        foreach (ItemDetail::find()->where(['item_id' => $this->getItemOptionIds()])\n                     ->all() as $itemDetail) {\n            $list[$itemDetail->id] = $itemDetail->bar_code;\n        }\n\n        return $list;\n    }\n\n    /** GxActiveRecord::getItemCustomerName(): the customer on this row's order. */\n    public function getItemCustomerName()\n    {\n        $customer = Customer::findOne($this->order->customer_id);\n\n        return $customer ? $customer->name : '';\n    }\n\n    /**\n     * GxActiveRecord::getSessionStartDate(): 1 April of the selected session's\n     * opening year, or '' when no session is selected.\n     */\n    public function getSessionStartDate()\n    {\n        $years = self::selectedSessionYears();\n\n        return isset($years[0]) ? $years[0] . '-04-01' : '';\n    }\n\n    /** GxActiveRecord::getSessionEndDate(): 31 March of its closing year. */\n    public function getSessionEndDate()\n    {\n        $years = self::selectedSessionYears();\n\n        return isset($years[1]) ? $years[1] . '-03-31' : '';\n    }\n\n    /**\n     * The two years in the selected session's name, which is '<from>-<to>'.\n     * The financial year runs 1 April to 31 March, which is where the two\n     * dates above come from.\n     */\n    private static function selectedSessionYears()\n    {\n        $id = Yii::$app->session['select_session_id'];\n        if ($id === null || $id === '') {\n            return [];\n        }\n        $session = Session::findOne($id);\n\n        return $session ? explode('-', $session->name) : [];\n    }\n\n    /** GxActiveRecord::getVendorDataOptions(): the active vendors, id => name. */\n    public function getVendorDataOptions()\n    {\n        $list = [];\n        $query = Vendor::find()->where(['status' => Vendor::STATUS_ACTIVE]);\n        // Yii 1 reaches these through findAllByAttributes(), which applies the\n        // model's defaultScope; the order is what the dropdown shows.\n        $query->orderBy(Vendor::defaultOrder() ?: []);\n        foreach ($query->all() as $vendor) {\n            $list[$vendor->id] = $vendor->name;\n        }\n\n        return $list;\n    }"

SHARED_VENDOR_ITEM_DETAIL_IDS = "    /** The item_detail_ids ItemVendor holds for the matching vendor. */\n    private static function vendorItemDetailIds($condition)\n    {\n        $vendor = Vendor::findOne($condition);\n        if ($vendor === null) {\n            return [];\n        }\n\n        return ItemVendor::find()->where(['vendor_id' => $vendor->id])\n            ->select('item_detail_id')->column();\n    }"


# Methods that are neither generated nor translatable: a second search() whose
# filters resolve through another table. Written out, keyed by model, and
# skipped where the model turns out to define one itself.
HAND_PORTED = {
    'ItemDetail': ["    /**\n     * BaseItemDetail::adminsearch(): the provider behind itemDetail/admin.\n     *\n     * Not search(), and not a variant of it. Four of this grid's filters -\n     * item title, mrp, hsn code, product code - are columns of Item, not of\n     * item_detail, so they are resolved to a set of item ids first and the\n     * grid is then filtered by item_id. The generator builds search() out of\n     * its compares; this one is not a list of compares, so it is written out.\n     *\n     * Takes no parameters: the action loads the model from the query string\n     * and the view calls this on the loaded model, as Yii 1 does.\n     */\n    public function adminsearch()\n    {\n        $query = self::find();\n\n        $match_mrp = null;\n        $match_purchase_price = null;\n        $match_item_id = null;\n        $match_hsn_code = null;\n        $match_product_code = null;\n\n        if ($this->item_id != null) {\n            // item_id holds a title here, not an id: the action puts the\n            // Item's title in it, and the filter box is a title box.\n            $item = Item::find()\n                ->where('title LIKE :title', [':title' => trim($this->item_id) . '%'])\n                ->one();\n            if ($item) {\n                $first = ItemDetail::find()\n                    ->where('item_id = ' . $item->id)\n                    ->orderBy('id asc')\n                    ->one();\n                if ($first) {\n                    // The first detail row of a matched item is the item\n                    // itself, and the admin grid hides it.\n                    $query->andWhere('id != ' . $first->id);\n                }\n            }\n            $match_item_id = $this->item_id;\n        }\n        if ($this->mrp != null) {\n            $match_mrp = $this->mrp;\n        }\n        if ($this->purchase_price != null) {\n            $match_purchase_price = $this->purchase_price;\n        }\n        if ($this->hsn_code != null) {\n            $match_hsn_code = $this->hsn_code;\n        }\n        if ($this->product_code != null) {\n            $match_product_code = $this->product_code;\n        }\n        $match_company_id = $this->company_id != null ? $this->company_id : null;\n\n        $is_vendor = 0;\n        $role = UserRole::findOne(['title' => 'Vendor']);\n        $user = Yii::$app->user->model;\n        if ($user && $role && $user->role_id == $role->id) {\n            $is_vendor = 1;\n        }\n\n        if ($match_item_id != null || $match_mrp != null || $match_hsn_code != null\n            || $match_product_code != null || $match_purchase_price != null\n            || $match_company_id != null || $is_vendor == 1) {\n            $query->andWhere(['item_id' => $this->getItemOptionIdsInBarcode(\n                $match_item_id, $match_mrp, $match_hsn_code, $match_product_code,\n                $match_purchase_price, $match_company_id, $is_vendor)]);\n        }\n\n        Criteria::compare($query, 'id', $this->id);\n        Criteria::compare($query, 'bar_code', $this->bar_code, true);\n        Criteria::compare($query, 'open_stock_qty', $this->open_stock_qty);\n        Criteria::compare($query, 'reorder_qty', $this->reorder_qty);\n        Criteria::compare($query, 'status', $this->status);\n        Criteria::compare($query, 'type_id', $this->type_id);\n        Criteria::compare($query, 'create_time', $this->create_time, true);\n        Criteria::compare($query, 'tax_id', $this->tax_id);\n        Criteria::compare($query, 'create_user_id', $this->create_user_id);\n        Criteria::compare($query, 'updated_by', $this->updated_by);\n\n        // Yii 1 puts 't.id desc' on the criteria and 'id DESC' on the sort,\n        // and CSort::applyOrder appends one to the other. Both name the same\n        // column in the same direction, so one of them says it.\n        $query->orderBy('id desc');\n\n        return new ActiveDataProvider([\n            'query' => $query,\n            'sort' => ['defaultOrder' => []],\n            'pagination' => ['pageSize' => 10],\n        ]);\n    }"],
}


def convert_search(body, model, page_size, eager, load_params=True):
    """
    search() converted rather than rebuilt, for the ones where rebuilding
    loses something.

    The rebuilt search() is a list of compares, which is all most of these
    methods are. MrnDetail's is not: it drops every row whose status is
    STATUS_DONE, and when the date or vendor filter is set it first resolves
    those to a set of mrn ids and restricts the grid to them. None of that is
    a compare, so the rebuilt method returned rows Yii 1 does not show - and
    with pagination off, that was the whole table.

    Here the criteria converter takes the body as written, control flow and
    all, and only the provider at the end is replaced. Returns None when it
    cannot, and the caller falls back to rebuilding.
    """
    cut = body.find('new CActiveDataProvider')
    if cut < 0:
        return None
    ret = body.rfind('return', 0, cut)
    if ret < 0:
        return None
    var = re.search(r"'criteria'\s*=>\s*\$(\w+)", body[cut:])
    if not var:
        return None

    # The provider is what consumes the criteria in a search(). Handing it to
    # the converter as an ordinary finder means one implementation covers both.
    prepared = body[:ret] + 'return %s::model()->findAll($%s);' % (model, var.group(1))
    out, ok, _ = convert_criteria(prepared)
    if not ok:
        return None

    # The alias, for the same reason the rebuilt search() needs it: Yii 2
    # names the primary table after the table, so a condition on `t.status`
    # refers to an alias the query does not have.
    aliased = "'t." in out

    lines = []
    for line in out.splitlines():
        decl = re.match(r'(\s*)\$query = ' + model + r'::find\(\);\s*$', line)
        if decl:
            lines.append(decl.group(1) + '$query = self::find()%s;'
                         % ("->alias('t')" if aliased else ''))
            continue
        if line.strip() == 'return $query->all();':
            indent = line[:len(line) - len(line.lstrip())]
            lines.append(indent + 'return new ActiveDataProvider([')
            lines.append(indent + "    'query' => $query,")
            lines.append(indent + "    'sort' => ['defaultOrder' => []],")
            if page_size is False:
                lines.append(indent + "    'pagination' => false,")
            elif page_size:
                lines.append(indent + "    'pagination' => ['pageSize' => %s]," % page_size)
            else:
                lines.append(indent + "    'pagination' => ['pageSize' => Ui::PAGE_SIZE],")
            lines.append(indent + ']);')
            continue
        lines.append(line)

    body = yii1_idioms('\n'.join(lines))
    if re.search(YII1_CLASSES, body):
        return None

    # Yii 1's action fills the model before the view calls search(); Yii 2's
    # provider is built from $params, so the load happens here. The other
    # provider methods take their own arguments and the action has already
    # filled the model, exactly as in Yii 1, so they get no load at all.
    if not load_params:
        return body.rstrip()

    return '        $this->load($params, $this->formName());\n' + body.rstrip()


def provider_methods(src):
    """Every method that builds a CActiveDataProvider, as (name, params, body)."""
    out = []
    for m in re.finditer(r'\n[ \t]*(?:public|protected|private)?\s*(?:static\s+)?'
                         r'function\s+(\w+)\s*\(([^)]*)\)', src):
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
        body = src[brace + 1:i - 1]
        if 'new CActiveDataProvider' in body:
            out.append((m.group(1), m.group(2).strip(), body))

    return out


def port_provider_methods(base, concrete, model, taken):
    """
    The search() variants, converted the same way search() is.

    A model rarely has one listing. PurchaseBill has listsearch(),
    PurchaseBillDetail has reportsearch() and purchasesearch(), OrderItem has
    five, and each backs a view of its own. They are ordinary Yii 1 code
    holding a CDbCriteria and a CActiveDataProvider, so the same conversion
    applies - and without it they are dropped as "still contains Yii 1 code"
    and the page dies on a missing method.

    search() itself is not here: it is handled where the provider's sort and
    page size are already known.
    """
    out, warn = [], []
    for src in (base, concrete):
        if not src:
            continue
        for name, params, body in provider_methods(src):
            if name == 'search' or name in taken:
                continue
            taken.add(name)
            converted = convert_search(arrays(body), model,
                                       port_page_size(body), None, load_params=False)
            if converted is None:
                warn.append('%s(): a second listing this could not convert - '
                            'the page that uses it stays on Yii 1' % name)
                continue
            out.append('    /**\n'
                       '     * Yii 1\'s %s(): a listing of its own, converted as written.\n'
                       '     */\n'
                       '    public function %s(%s)\n'
                       '    {\n%s\n    }' % (name, name, params, converted))

    return out, warn

def generate(model, table_alias):
    base = read(f'{ROOT}/protected/models/_base/Base{model}.php')
    concrete = read(f'{ROOT}/protected/models/{model}.php')
    warn = []

    consts = re.findall(r'const\s+(\w+)\s*=\s*([^;]+);', base)
    props = public_properties(base, concrete)

    # The getXOptions helpers. These were copied across with only their array
    # syntax updated, which left any CDbCriteria inside them untouched - and
    # because get*Options is on the list of methods a merge replaces, the
    # unconverted copy overwrote a working one every time the model was
    # refreshed. User::getAllRoleOptions() was repaired by hand and reverted
    # itself twice before anyone looked at why.
    #
    # One that still names a Yii 1 class after conversion is dropped rather
    # than written: a missing method is a page that fails where it is called,
    # and an emitted broken one is a page that fails *and* replaces whatever
    # was there.
    options = []
    # Both `public static function` and plain `public function`:
    # BaseUser::getRoleOptions() is the second, and user/create died
    # looking for it.
    for m in re.finditer(r'(public\s+(?:static\s+)?function\s+get\w+Options\s*\([^)]*\)\s*\{)', base):
        name = re.search(r'function\s+(get\w+Options)', m.group(1)).group(1)
        body = arrays(parse_block(base, name))
        if 'CDbCriteria' in body:
            converted, ok, why = convert_criteria(body)
            if not ok:
                warn.append('%s(): not carried across - %s' % (name, why))
                continue
            body = converted
        body = yii1_idioms(body)
        if re.search(YII1_CLASSES, body):
            left = sorted(set(re.findall(YII1_CLASSES, body)))
            warn.append('%s(): not carried across - still names %s'
                        % (name, ', '.join(left)))
            continue
        options.append((name, body))

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
    # The columns, and only the columns. attributeLabels() also carries
    # relations and form-only fields, and counting those as known dropped
    # the declaration for Discount::\ - which the update
    # action assigns, so the page answered 500 where Yii 1 renders a form.
    known = columns_of(table)
    dropped = [n for n, _ in props if n.lstrip('$') in known]
    props = [(n, d) for n, d in props if n.lstrip('$') not in known]
    for n in dropped:
        warn.append('property %s not declared - it is a column, and declaring '
                    'it would shadow the attribute' % n)
    search_body = parse_block(base, 'search') or ''
    exact, partial = port_search(search_body, warn)
    eager = port_with(search_body)
    search_sort = port_search_sort(search_body)
    page_size = port_page_size(search_body)

    has_before_validate = 'function beforeValidate' in base
    default_order = port_default_scope(base, warn, concrete)

    # The concrete model's own methods. These are hand-written - option lists
    # built from another table, computed columns for a report - and the views
    # call them, so leaving them behind gives a page that renders until it hits
    # one. They are translated with the same rules as the controllers and
    # anything unrecognised is reported.
    concrete_methods, notes = port_concrete(concrete, model)

    # What the concrete model defines itself. GxActiveRecord's helpers below
    # are emitted only where the model does not already have one: Discount and
    # FreeItem write their own getItemOptions(), and emitting the base one as
    # well would put two methods of the same name in one class. Preferring the
    # base one silently would be worse than the parse error - theirs lists
    # item *detail* ids labelled by bar code, the base one lists item ids
    # labelled by mrp, and the dropdown would quietly change.
    own = methods_of('\n'.join(concrete_methods))
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
    A('    /**')
    A("     * GxActiveRecord::__toString(): the representing column's value.")
    A('     *')
    A('     * Empty when that value is null. Yii 1 falls back to the primary key')
    A('     * when representingColumn() itself is empty - which the generator')
    A("     * has already done above, by naming 'id' - and never because the")
    A('     * column happens to be null on this row. Falling back on the value')
    A('     * put an id in every grid cell where Yii 1 shows nothing.')
    A('     */')
    A('    public function __toString()')
    A('    {')
    A("        $value = $this->hasAttribute('%s') ? $this->%s : null;" % (representing, representing))
    A('')
    A("        return $value === null ? '' : (string) $value;")
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
    if 'getItemOptions' not in own:
        A('')
        A(SHARED_GET_ITEM_OPTIONS)
    if 'getItemOptionIdsInBarcode' not in own:
        A('')
        A(SHARED_IDS_IN_BARCODE)
    # The rest of GxActiveRecord's helpers, as one block. They are emitted
    # together because they call one another - getItemOptionbarcodes() is
    # getItemOptionIds() resolved to bar codes - and a model that overrides one
    # of them would otherwise get a half of the set.
    if not any(n in own for n in ('getItemOptionIds', 'getItemOptionbarcodes',
                                  'getItemCustomerName', 'getSessionStartDate',
                                  'getSessionEndDate', 'getVendorDataOptions')):
        A('')
        A(SHARED_MORE_GX)
    A('')
    A(SHARED_VENDOR_ITEM_DETAIL_IDS)
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
    A("     * GxActiveRecord::getCompanyBarcode(): 'readOnly' when the item")
    A("     * detail's bar code is the company's own, and an empty string")
    A('     * otherwise. The grids use the result as an html attribute, so a')
    A('     * barcode belonging to the company cannot be edited in place.')
    A('     */')
    A('    public function getCompanyBarcode($id)')
    A('    {')
    A('        $itemDetail = ItemDetail::findOne($id);')
    A('')
    A('        return $itemDetail && $itemDetail->company_bar_code == ItemDetail::IS_COMPANY')
    A("            ? 'readOnly'")
    A("            : '';")
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
    # Rebuilding search() out of its compares is enough for most of these and
    # silently wrong for the few that filter by anything else. Where Yii 1's
    # search() carries conditions the rebuild cannot express, the whole method
    # is converted instead - and if that conversion fails, the rebuild is
    # still what gets written, with the warning port_search() already raised.
    converted = None
    if re.search(r'\$criteria->(?:addCondition|addInCondition|addNotInCondition|'
                 r'addBetweenCondition|addSearchCondition)\s*\(', search_body or ''):
        converted = convert_search(search_body, model, page_size, eager)
    if converted:
        A(converted)
    else:
        # `t` when the compares name it. Yii 2 aliases the primary table by its
        # table name, so `t.mrs_id` in a condition is an alias that is not in the
        # query; the alias has to be declared for the column to resolve. Only
        # where it is needed, so the 42 controllers already compared against Yii 1
        # keep the SQL they were verified with.
        aliased = any(c.startswith('t.') for c, _ in exact + partial)
        A("        $query = self::find()%s;" % ("->alias('t')" if aliased else ''))
        if eager:
            A('        // Yii 1 eager-loads these, by JOIN, in the same query. That is')
            A('        // part of the result and not just an optimisation: where the')
            A('        // listing has no ORDER BY, the join decides which rows the')
            A('        // first page shows.')
            # Aliased by relation name. Yii 1 names an eager-loaded table after the
            # relation - `item` - and its order and conditions refer to it that
            # way. Yii 2 joins on the real table name instead, so `item.title` in
            # an ORDER BY resolves to nothing: "Unknown column 'item.title'".
            joins = ', '.join(
                "'%s' => function ($q) { $q->alias('%s'); }" % (x, x.split('.')[-1])
                for x in eager)
            A('        $query->joinWith([%s]);' % joins)
        A('        $provider = new ActiveDataProvider([')
        A("            'query' => $query,")
        A("            // The order goes on the query, not on the provider's sort.")
        A('            // Yii 1 sets it on the criteria, and three of these listings')
        A("            // order by a joined column - 'item.title' - which Yii 2's Sort")
        A('            // rejects as a key unless it is declared as a sortable')
        A('            // attribute. orderBy takes it as written.')
        A("            'sort' => ['defaultOrder' => []],")
        if page_size is False:
            A("            // Yii 1 turns pagination off for this one: every matching")
            A('            // row on one page, and no pager.')
            A("            'pagination' => false,")
        elif page_size:
            A("            // The page size Yii 1's search() asks its provider for, which")
            A('            // is not always the framework default.')
            A("            'pagination' => ['pageSize' => %s]," % page_size)
        else:
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
            A('        foreach ([%s] as [$col, $attr]) {'
              % ', '.join("['%s', '%s']" % c for c in exact))
            A('            Criteria::compare($query, $col, $this->$attr);')
            A('        }')
        if partial:
            A('        foreach ([%s] as [$col, $attr]) {'
              % ', '.join("['%s', '%s']" % c for c in partial))
            A('            Criteria::compare($query, $col, $this->$attr, true);')
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
    extra, extra_warn = port_provider_methods(base, concrete, model, set(own))
    warn.extend(extra_warn)
    for text in extra:
        A('')
        A(text)

    for text in HAND_PORTED.get(model, []):
        if re.search(r'function\s+(\w+)', text).group(1) in own:
            continue
        A('')
        A(text)
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



# GxActiveRecord's helpers, which the generator writes into every model as a
# fallback. A model that defines its own keeps it: these are the base class's
# behaviour, not this pipeline's output, so a newer generated one is not a
# better version of the same thing.
SHARED_GX_FALLBACKS = {
    'getItemOptions', 'getItemOptionIds', 'getItemOptionbarcodes',
    'getItemOptionIdsInBarcode', 'getItemCustomerName', 'getSessionStartDate',
    'getSessionEndDate', 'getVendorDataOptions', 'getCompanyBarcode',
    'getTotals', 'isAllowCreate', 'getRelationLabel', 'checkPermission',
    'vendorItemDetailIds', 'selectedSessionYears',
}

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

    # Properties, not only methods. The Yii 1 model declares the form-only
    # attributes - `Discount::$item_detail_id`, which the update action
    # assigns and the form posts back as an array - and Yii 2 throws on an
    # unknown property where Yii 1 declared it. The merge carried the methods
    # across and left these behind, so discount/update answered 500 where
    # Yii 1 renders the form.
    want = re.findall(r'^    public (\$\w+)(\s*=\s*[^;]+)?;', generated, re.M)
    held = set(re.findall(r'^\s*public (\$\w+)', existing, re.M))
    missing = [(n, d) for n, d in want if n not in held]
    if missing:
        decls = '\n'.join('    public %s%s;' % (n, d or '') for n, d in missing)
        block = ('    // Declared on the Yii 1 model and not columns: the forms post\n'
                 '    // to these and the actions assign them.\n' + decls + '\n')
        # After the trait if there is one, so `use` stays at the top of the
        # class as PHP convention has it.
        anchored = re.subn(r'(\n    use LegacyColumnTypes;\n)',
                           lambda m: m.group(1) + '\n' + block, existing, count=1)
        if anchored[1] == 0:
            anchored = re.subn(r'(\nclass \w+ extends [^\n]*\n\{\n)',
                               lambda m: m.group(1) + block + '\n', existing, count=1)
        if anchored[1]:
            existing = anchored[0]
            added.append('properties ' + ', '.join(n for n, _ in missing))
        else:
            warn.append('could not place %s: the class header is not where this '
                        'expects it' % ', '.join(n for n, _ in missing))

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

    #   search() - but only on a full port, never on a presentation-only merge
    #     into a model this pipeline is not porting. search() backs the admin
    #     grid and nothing else: the API does not call it. Keeping an older
    #     generated one meant Item's grid kept a page size of 10 where Yii 1
    #     asks its provider for 100, so the port showed a tenth of the rows.
    if 'presentation-only' not in ' '.join(warn):
        replace.append('search')
    # ...but never one of GxActiveRecord's own helpers. Those are emitted as
    # fallbacks for every model, and two models write their own:
    # Discount::getItemOptions() and FreeItem::getItemOptions() list item
    # *detail* ids labelled by bar code, where the base one lists item ids
    # labelled by mrp. Replacing theirs with the fallback changed what the
    # discount form offers, which is how it was noticed.
    replace += [n for n, _ in method_blocks(generated)
                if re.match(r'get\w*Options$', n) and n not in SHARED_GX_FALLBACKS]
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
                     'getTotals', 'isAllowCreate', 'getCompanyBarcode'}

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
        # The property declarations come across as well. They are the Yii 1
        # model's form-only attributes, they are not columns and nothing in
        # the API assigns them, but the UI actions do - and Yii 2 throws on an
        # unknown property where Yii 1 simply declared it.
        decls = re.findall(r'^    public \$\w+(?:\s*=\s*[^;]+)?;$', src, re.M)
        src = (header + 'class %s extends ActiveRecord\n{\n' % model
               + ('\n'.join(decls) + '\n\n' if decls else '')
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
