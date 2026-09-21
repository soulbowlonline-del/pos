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



_CASE_CACHE = {}


def resolve_path(path):
    """
    The file on disk, whatever case its name is written in.

    This application spells the same thing three ways: the controller is
    B2BPurchaseBillDetailController, the model B2bPurchaseBillDetail, and the
    view directory b2bpurchaseBillDetail. The generators derive one name from
    another and then open it verbatim, so that controller could not be ported
    from either spelling - the model lookup missed with the controller's, and
    the controller lookup missed with the model's.

    Exact matches are returned untouched, so nothing else changes.
    """
    if os.path.exists(path):
        return path

    # Every component, not just the last. The mismatch is as often in a
    # directory as in a file name: B2BPurchaseBillDetailController's views live
    # in protected/views/b2bpurchaseBillDetail, so resolving only the basename
    # left the lookup pointing at a directory that does not exist.
    parts = path.split(os.sep)
    resolved = parts[0] or os.sep
    for part in parts[1:]:
        if not part:
            continue
        candidate = os.path.join(resolved, part)
        if os.path.exists(candidate):
            resolved = candidate
            continue
        if not os.path.isdir(resolved):
            return path
        if resolved not in _CASE_CACHE:
            _CASE_CACHE[resolved] = {e.lower(): e for e in os.listdir(resolved)}
        match = _CASE_CACHE[resolved].get(part.lower())
        if match is None:
            return path
        resolved = os.path.join(resolved, match)

    return resolved

def read(p):
    return open(resolve_path(p), encoding='utf-8',
                errors='replace').read()


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


def block_params(src, name):
    """
    The parameter list a Yii 1 method declares, as written.

    search() is emitted with Yii 2's signature, `search($params = [])`, and
    where the whole body is converted rather than rebuilt that threw away any
    parameter the application had declared. BasePurchaseBill::search($val =
    false) tests $val twice in its body, so the generated method referred to a
    variable it no longer had: "Undefined variable $val", and purchaseBill's
    admin grid answered 500.
    """
    m = re.search(r'function\s+' + name + r'\s*\(([^)]*)\)\s*\{', src)
    if not m or not m.group(1).strip():
        return []

    out = []
    for bit in m.group(1).split(','):
        bit = bit.strip()
        if not bit:
            continue
        # Anything this cannot read - a type hint, a by-reference parameter -
        # is reported rather than guessed at.
        pm = re.match(r'^(\$\w+)\s*(=\s*(.+))?$', bit)
        out.append((pm.group(1), pm.group(3)) if pm else (bit, None))

    return out


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


def port_labels(body, relations_body, table=None):
    """
    Yii 1's attributeLabels(), with its null entries resolved.

    A null label does not mean "no label". GxActiveRecord::getRelationLabel()
    falls back to the *related* model's label() for a relation or a foreign
    key, and only otherwise to generateAttributeLabel(). So `advancePayment`
    and `advance_payment_id` both show as "AdvancePayment", not as
    "Advance Payment" - which is what a naive fallback produces, and what the
    comparison against Yii 1 catches immediately.

    "Foreign key" there means the schema's constraint, not the model's
    BELONGS_TO - see foreign_keys_of(). A column the model relates but the
    table does not constrain falls through to generateAttributeLabel() like
    any other column.
    """
    rel = {}          # relation name -> (kind, class)
    fk = {}           # foreign key column -> class
    for m in re.finditer(r"'(\w+)'\s*=>\s*array\s*\(\s*self::(\w+)\s*,\s*'(\w+)'\s*,\s*'(\w+)'",
                         relations_body or '', re.S):
        name, kind, cls, key = m.groups()
        rel[name] = (kind, cls)
        if kind == 'BELONGS_TO':
            fk[key] = cls

    # Commented-out entries are not entries. BaseMrn carries
    #     'createUser' =>  Yii::t('app', 'Create User'),
    #     ...
    #   //    'createUser' => null,
    # and the second one was being read as real. It came last, so in the
    # generated array it won, and mrn/view labelled the row "User" where Yii 1
    # says "Create User".
    body = re.sub(r'//[^\n]*', '', body)
    body = re.sub(r'/\*.*?\*/', '', body, flags=re.S)

    out = []
    for m in re.finditer(r"'(\w+)'\s*=>\s*(?:Yii::t\s*\(\s*'[^']*'\s*,\s*'([^']*)'\s*\)|(null))", body, re.S):
        name, label, isnull = m.groups()
        if not isnull:
            out.append((name, label))
        elif name in rel:
            kind, cls = rel[name]
            out.append((name, model_label(cls, kind in ('HAS_MANY', 'MANY_MANY'))))
        elif name in fk and (table is None or name in foreign_keys_of(table)):
            out.append((name, model_label(fk[name])))
        else:
            out.append((name, attr_label(name)))

    # A key the source really does declare twice keeps the last value, which is
    # what PHP does with the array - but it is emitted once, so the generated
    # method cannot disagree with itself.
    seen = {}
    for name, label in out:
        seen[name] = label

    return list(seen.items())


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


_FK_CACHE = {}


def foreign_keys_of(table):
    """
    The columns of a table that carry a declared FOREIGN KEY.

    Not the same thing as the BELONGS_TO relations the model declares, and the
    difference is visible on a page. GxActiveRecord::getAttributeLabel() hands
    a column with no explicit label to getRelationLabel(), which asks
    findRelation() whether it is a foreign key - and findRelation() answers
    `if (!$column->isForeignKey) return null;`, reading the *schema*. So
    `discount_id`, which BaseOrderItem relates to Discount but which
    tbl_order_item declares no constraint on, gets generateAttributeLabel() -
    "Discount Id" - while `order_id`, which does carry one, gets the related
    model's label, "Discount"... or rather "Order". orderItem's detail view
    differed in exactly that one cell.
    """
    if table in _FK_CACHE:
        return _FK_CACHE[table]

    sql = ("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE "
           "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_%s' "
           "AND REFERENCED_TABLE_NAME IS NOT NULL" % table)
    out = subprocess.run(
        ['docker', 'exec', 'pos-mysql-8', 'sh', '-c',
         'MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot $MYSQL_DATABASE -N -e "%s"' % sql],
        capture_output=True, text=True)
    keys = {c.strip() for c in out.stdout.split() if c.strip()}
    _FK_CACHE[table] = keys

    return keys


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


def port_with_full(body):
    """
    port_with(), plus the `together` option and whether the block was readable.

    `together` is not decoration. Yii 1 runs an eager load either as a JOIN in
    the same query or as a second query, and `together` is what picks: false
    means a second query, and a condition or order written against the
    relation's alias would then name a table the main query does not have.
    Loading every relation with joinWith() - which is what this did, because
    an options array was simply unreadable and the method was refused - would
    turn loyaltyAdmin/customers' plain listing into a join.

    Returns (paths, together, ok, joins). `together` carries only the relations where
    the application set it explicitly; anything else keeps the join this has
    always emitted. `ok` is False when a relation carries an option this
    cannot model, so the caller can refuse the method rather than emit a query
    that quietly drops it.
    """
    paths = port_with(body)
    together, joins, bad = {}, {}, []

    # Only the entries that are an options array with no nested array inside -
    # a nested `'with' => array(...)` is the eager-load form port_with()
    # already walked, and it never carries `together` in this application.
    for nm in re.finditer(r"'(\w+)'\s*=>\s*(?:array\s*\(|\[)([^()\[\]]*)[)\]]", body, re.S):
        keys = re.findall(r"'(\w+)'\s*=>", nm.group(2))
        if not keys:
            continue
        # `select` limits which columns of the related table are added to the
        # query. Ignored here: it changes how many columns come back, never
        # which rows or what any of them say, and the comparison is of what
        # the page says. `joinType` is honoured below.
        unknown = [k for k in keys if k not in ('together', 'select', 'joinType')]
        if unknown:
            bad.extend(unknown)
            continue
        jt = re.search(r"'joinType'\s*=>\s*'([^']+)'", nm.group(2))
        if jt:
            joins[nm.group(1)] = jt.group(1)
        name = nm.group(1)
        if name not in paths:
            paths.append(name)
        t = re.search(r"'together'\s*=>\s*(true|false)", nm.group(2), re.I)
        if t:
            together[name] = t.group(1).lower() == 'true'

    return paths, together, not bad, joins


def port_search_sort(body, aliased=False):
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
        # `t` is the main table's alias. Yii 2 does not use it unless the
        # query was given it - and where the query *was* given it, the
        # qualifier has to stay: order/b2bReport joins a relation that also
        # has a tax_id, and the bare column was "ambiguous in order clause".
        # Any other qualifier names a joined relation and always stays.
        if col.startswith('t.') and not aliased:
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
    in_block = False
    for i, line in enumerate(lines):
        stripped = line.strip()
        hide = in_block
        if in_block:
            if '*/' in line:
                in_block = False
        elif stripped.startswith('//') or stripped.startswith('#') or stripped.startswith('*'):
            hide = True
        elif stripped.startswith('/*'):
            # A block comment. Only the lines it spans are hidden, and the
            # block is tracked across them: without this, everything inside
            # `/* ... */` counted as live code. B2bPurchaseBillDetail's
            # itemwisesearch() has a commented-out
            # `OrderItem::model()->findAll($criteria)` above its real
            # consumer, and the converter read the class off it - the query
            # was built on the wrong model, and order/b2bItemWise died on a
            # relation OrderItem does not have.
            hide = True
            in_block = '*/' not in line[line.index('/*') + 2:]
        if hide:
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
    # `->resetScope()` may sit between the two. It says "ignore
    # defaultScope() for this query", so it has to be matched here rather than
    # stripped beforehand: the whole call is replaced, and convert_criteria
    # uses its presence to leave the scope order off.
    return (r"(\w+)\s*::\s*model\s*\(\s*\)\s*(?:->\s*resetScope\s*\(\s*\)\s*)?"
            r"->\s*(findAll|find|count)\s*\(\s*"
            r"\$" + re.escape(var) + r"\s*\)")


def provider_re(var):
    """
    The other consumer of a criteria: a data provider built around it.

    convert_criteria() only ever looked for a finder, so an action that handed
    its criteria to a CActiveDataProvider - which is how every Yii 1 listing
    that is not search() is written - was refused whole, and the four
    loyaltyAdmin pages kept their Yii 1 code and died on a class Yii 2 does
    not have.
    """
    # The class may be written as a literal, as $this, or as
    # get_class($this) - BaseB2bPurchaseBill::userwisesearch() uses the
    # second. When it is not a literal the caller supplies the class.
    return (r"new\s+CActiveDataProvider\s*\(\s*"
            r"(?:'(\w+)'|\$this|get_class\s*\(\s*\$this\s*\))\s*,\s*"
            r"(?:array\s*\(|\[)"
            r"[^;]*?'criteria'\s*=>\s*\$" + re.escape(var) + r"\b")


def query_var_for(var):
    """The Yii 2 variable a named criteria becomes."""
    m = re.match(r'criteria(\w*)$', var)
    if m:
        return 'query' + m.group(1)
    if var.endswith('Criteria'):
        return var[:-len('Criteria')] + 'Query'

    return var + 'Query'


def convert_criteria(text, cls_hint=None):
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
    shared = {}          # consumer offset -> the query variable it is built in
    for i, d in enumerate(decls):
        var = d.group(1)
        end = len(text)
        for later in decls[i + 1:]:
            if later.group(1) == var:
                end = later.start()
                break
        # The consumer, looked for from this declaration to the end of the
        # method rather than only to the next declaration of the same name.
        #
        # A criteria is often built differently in two branches and consumed
        # once after them - site/search builds one set of conditions when the
        # search term contains a dot and another when it does not, then calls
        # Locator::model()->findAll($criteria) below both. Stopping at the
        # next declaration meant the first branch had no consumer in its own
        # region and the whole method was refused.
        #
        # Declarations that resolve to the same consumer are alternative
        # constructions of one query, so they share its variable. That is
        # faithful whether the branches are exclusive, where exactly one
        # assignment runs, or sequential, where the later one overwrites the
        # earlier - which is what Yii 1 does with `$criteria = new
        # CDbCriteria()` twice.
        fm = re.search(finder_re(var), text[d.start():])
        pv = re.search(provider_re(var), text[d.start():])
        if fm and pv:
            fm, pv = (fm, None) if fm.start() < pv.start() else (None, pv)
        if fm:
            cls, kind = fm.group(1), fm.group(2)
            at = d.start() + fm.start()
        elif pv:
            cls = pv.group(1) or cls_hint
            if not cls:
                return original, False, ('a CActiveDataProvider whose model is '
                                         'not written as a class name')
            kind = 'provider'
            at = d.start() + pv.start()
        else:
            return original, False, 'the criteria is not passed to a finder this knows'

        # A name free in the method as the application wrote it, and not
        # already handed to another region. actionGetCustomerAddress() has its
        # own $query - the query string for a curl call - and the converted
        # criteria overwrote it. Refusing the method over that would leave it
        # as CDbCriteria, which is a page that dies rather than a page that
        # lies, but it is still a page that dies.
        if at in shared:
            name = shared[at]
        else:
            base = query_var_for(var)
            taken = set(shared.values())
            name, n = base, 1
            while name in taken or re.search(r'\$' + re.escape(name) + r'\b', original):
                n += 1
                name = '%s_%d' % (base, n)
            shared[at] = name
        seen[var] = seen.get(var, 0) + 1

        # defaultScope() does not apply to a query that reset it.
        reset = re.search(r'resetScope\s*\(\s*\)', text[d.start():end]) is not None
        plans.append((d.start(), end, var, name, cls, kind, reset))

    # Regions of two different criteria interleave, so the edits are collected
    # against the original offsets and applied back to front rather than each
    # region being spliced in on its own.
    edits = []
    for start, end, var, name, cls, kind, reset in plans:
        got, ok, why = region_edits(text, start, end, var, name, cls, kind, reset)
        if not ok:
            return original, False, why
        edits.extend(got)

    out = text
    for span, replacement in sorted(edits, key=lambda e: -e[0][0]):
        out = out[:span[0]] + replacement + out[span[1]:]

    return restore(out), True, ''


def region_edits(text, start, end, var, name, cls, kind, reset=False):
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

    def take_span(a, b, replacement):
        edits.append(((start + a, start + b), replacement))
        claimed.append((a, b))

    def take(m, replacement):
        take_span(m.start(), m.end(), replacement)

    decl = re.match(DECL_RE, region)
    # resetScope() asks for the model's defaultScope() to be ignored, and what
    # that scope contributes here is the ORDER BY. Adding it anyway would give
    # site/search a row order Yii 1 never produced.
    scope = None if reset else default_order_of(cls)
    # `t` when this criteria's own conditions name it. Yii 2 aliases the
    # primary table after the table, so a condition on `t.bill_date` refers to
    # an alias the query does not have: "Unknown column 't.bill_date' in 'where
    # clause'", which is what order and orderItem died on. The rebuilt search()
    # already did this; a criteria converted anywhere else did not.
    alias = "->alias('t')" if re.search(r"['\"]t\.", region) else ''
    stmt = Q + ' = ' + cls + '::find()' + alias + ';'
    # An explicit order on the criteria, not the word 'order' anywhere in
    # the method. StockAdjustLog's search mentions order_id, which
    # suppressed the model's own id DESC and listed the oldest first.
    if scope and scope != 'null' and not re.search(r'->\s*order\s*=', region):
        stmt += '\n        ' + Q + '->orderBy(' + scope + ');'
    take(decl, stmt)

    def order_by(m):
        cols = []
        # The last group is the order text: the pattern captures the quote
        # character first so both styles match.
        for part in m.groups()[-1].split(','):
            bits = part.strip().split()
            if not bits:
                continue
            col = bits[0]
            # The `t.` prefix is kept when the query carries that alias. Yii 1
            # writes `t.tax_id` precisely so the column is unambiguous once a
            # relation is joined, and dropping it gave "Column 'tax_id' in
            # order clause is ambiguous" on order/b2bReport - but only when
            # the session held no date range, which is the branch that joins.
            if col.startswith('t.') and not alias:
                col = col[2:]
            desc = len(bits) > 1 and bits[1].lower().startswith('desc')
            cols.append("'%s' => %s" % (col, 'SORT_DESC' if desc else 'SORT_ASC'))
        return Q + '->orderBy([%s]);' % ', '.join(cols)

    # The value may be parenthesised - BaseItemReturn writes
    #  - and without allowing for that
    # the statement was left behind, the conversion refused the whole
    # method, and itemReturn/list died on a missing listsearch().
    # Either quote style. BaseItemReturnItem::reportsearch() writes
    # `$criteria->order ="id DESC";` and the single-quote-only pattern left it
    # behind, which refused the whole method - so itemReturnItem/report died
    # on a missing reportsearch().
    for m in re.finditer(V + r"\s*->\s*order\s*=\s*\(?\s*(['\"])([^'\"]+)\1\s*\)?\s*;",
                         region):
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
    # Yii 1 *assigns* `$criteria->with`, so a second assignment replaces the
    # first - loyaltyAdmin/customers sets a plain eager load and then, inside
    # the search branch, replaces it with a join. Yii 2's joinWith() only ever
    # adds, so where a region assigns more than once the property is cleared
    # first. Emitted only for those regions, so the output for every method
    # that assigns once is unchanged.
    assigns = len(re.findall(V + r"\s*->\s*with\s*=", region))
    joined_before = False
    for m in re.finditer(V + r"\s*->\s*with\s*=\s*([^;]+);", region):
        paths, together, ok, joins = port_with_full('$criteria->with = %s;'
                                                   % m.group(1).strip())
        if not ok or not paths:
            return [], False, 'CDbCriteria with a `with` this cannot read'
        stmts = []
        # Unless the application said `together => false`, this joins - which
        # is what it has always done, and what the 55 controllers already
        # compared against were generated with.
        joined = [x for x in paths if together.get(x, True)]
        lazy = [x for x in paths if not together.get(x, True)]
        if joined:
            if assigns > 1:
                stmts.append(Q + '->with = [];')
            # Grouped by join type: Yii 2 takes one per joinWith() call, and
            # Yii 1 declares it per relation. Everything without an explicit
            # one keeps Yii 2's default, which is Yii 1's too.
            for jt in sorted({joins.get(x) for x in joined}, key=lambda v: (v is not None, v)):
                group = [x for x in joined if joins.get(x) == jt]
                arg = ', '.join(
                    "'%s' => function ($q) { $q->alias('%s'); }" % (x, x.split('.')[-1])
                    for x in group)
                stmts.append(Q + '->joinWith([%s]%s);'
                             % (arg, ", true, '%s'" % jt if jt else ''))
        if lazy and joined_before:
            return [], False, ('a CDbCriteria whose `with` replaces a join - '
                               'Yii 2 cannot drop one')
        if joined:
            joined_before = True
        if lazy:
            # The property, not with(): Yii 1 assigns to $criteria->with and
            # the assignment replaces, while Yii 2's with() appends.
            stmts.append(Q + '->with = [%s];' % ', '.join("'%s'" % x for x in lazy))
        take(m, ('\n' + ' ' * 8).join(stmts))

    # `$criteria->scopes` names one of the model's Yii 1 named scopes, which
    # Yii 2 has no equivalent for. The scope's condition is inlined instead -
    # User::searchByName() asks for 'active', which is `state_id=1`.
    for m in re.finditer(V + r"\s*->\s*scopes\s*=\s*([^;]+);", region):
        raw = m.group(1).strip()
        names = re.findall(r"'(\w+)'", raw)
        if not names:
            return [], False, 'CDbCriteria uses a scope this cannot read'
        known = scopes_of(cls)
        conds = [known.get(n) for n in names]
        if any(c is None for c in conds):
            return [], False, ('CDbCriteria uses the named scope %s, which is not '
                               'declared as a condition' % ', '.join(
                                   n for n, c in zip(names, conds) if c is None))
        # `self::` in a scopes() body means the model; here it would mean
        # whatever class the criteria is being built in.
        conds = [re.sub(r'\bself::', cls + '::', c) for c in conds]
        take(m, ('\n' + ' ' * 8).join(Q + '->andWhere(%s);' % c for c in conds))

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
            return Q + '->andWhere([%s => %s]);' % (column_literal(bits[0]), bits[1].strip())
        if called == 'addNotInCondition' and len(bits) >= 2:
            return Q + "->andWhere(['not in', %s, %s]);" % (column_literal(bits[0]), bits[1].strip())
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

    # Every consumer in the region, not just the one the plan found. A single
    # criteria is often handed to a count *and* to the data provider that
    # lists the same rows - item/printBarcode does exactly that - and
    # rewriting only the first left the other referring to a variable that no
    # longer existed, which the check below then refused the whole method for.
    tails = {'findAll': '->all()', 'find': '->one()', 'count': '->count()'}
    for m in re.finditer(finder_re(var), region):
        take(m, Q + tails[m.group(2)])
    got, ok, why = provider_edits(region, var, cls, Q, take_span, required=False)
    if not ok:
        return [], False, why

    # A criteria handed to a dump - `Yii::log(CVarDumper::dumpAsString($criteria))`
    # - is diagnostic, not behaviour. The variable is renamed so the method can
    # still be converted; what the log then holds is a Yii 2 query rather than
    # a CDbCriteria, which is the correct thing for it to hold.
    #
    # BaseItem::reportstocksearch() logs its criteria this way, and the whole
    # method was being refused over it, so item/report died on a missing
    # method.
    for m in re.finditer(r'(?:CVarDumper::dumpAsString|var_export|print_r|var_dump)'
                         r'\s*\(\s*' + V + r'\s*(?=[,)])', region):
        take(m, m.group(0).replace('$' + var, Q))

    # Nothing may mention this criteria that has not been rewritten.
    for m in re.finditer(V + r'\b', region):
        # Not a mention inside a string. Yii 1's logging calls carry the
        # variable's own name as the category - `Yii::log(..., '$criteria')` -
        # and counting that as unrewritten code refused the whole method.
        if m.start() and region[m.start() - 1] in ('\'', '"'):
            continue
        if not any(a <= m.start() < b for a, b in claimed):
            rest = re.match(r'\s*->\s*(\w+)', region[m.end():])
            return [], False, ('CDbCriteria uses ' + rest.group(1) if rest
                               else 'a bare CDbCriteria reference')

    return edits, True, ''

def order_array(sql):
    """A SQL order fragment as Yii 2's Sort spells it."""
    cols = []
    for part in sql.split(','):
        bits = part.strip().split()
        if not bits:
            continue
        col = bits[0][2:] if bits[0].startswith('t.') else bits[0]
        desc = len(bits) > 1 and bits[1].lower().startswith('desc')
        cols.append("'%s' => %s" % (col, 'SORT_DESC' if desc else 'SORT_ASC'))

    return '[%s]' % ', '.join(cols)


def provider_edits(region, var, cls, Q, take_span, required=True):
    """
    Rewrite `new CActiveDataProvider('X', array('criteria' => $criteria, ...))`
    as Yii 2's ActiveDataProvider over the converted query.

    Every option other than `criteria` is carried through untouched: the two
    this application uses - `pagination => array('pageSize' => N)` and
    `sort => false` - mean the same thing in both frameworks, and rewriting
    them would have replaced a page size of 20 with the default.
    """
    pat = (r"new\s+CActiveDataProvider\s*\(\s*"
           r"(?:'" + re.escape(cls) + r"'|\$this|get_class\s*\(\s*\$this\s*\))"
           r"\s*,\s*(array\s*\(|\[)")
    found = False
    for m in re.finditer(pat, region):
        opener = '(' if m.group(1).strip().startswith('array') else '['
        closer = ')' if opener == '(' else ']'
        i, depth = m.end(), 1
        while i < len(region) and depth:
            if region[i] == opener:
                depth += 1
            elif region[i] == closer:
                depth -= 1
            i += 1
        if depth:
            return [], False, 'a CActiveDataProvider this cannot read'
        block = region[m.end():i - 1]
        close = re.match(r'\s*\)', region[i:])
        if not close:
            return [], False, 'a CActiveDataProvider this cannot read'

        opts = [b.strip() for b in split_args_php(block)
                if b.strip() and not re.match(r"'criteria'\s*=>", b.strip())]
        # Yii 1 takes the provider's defaultOrder as a SQL fragment; Yii 2's
        # Sort takes an array, and a string there is silently ignored.
        opts = [re.sub(r"('defaultOrder'\s*=>\s*)'([^']+)'",
                       lambda mm: mm.group(1) + order_array(mm.group(2)), o)
                for o in opts]
        if len(opts) == len(split_args_php(block)):
            continue          # some other provider in the same region
        # A grouped query needs its own count. Yii 2 counts by wrapping the
        # select list - `SELECT COUNT(*) FROM (...)` - and this application's
        # grouped listings select `t.*` alongside `SUM(approved_qty) AS
        # approved_qty`, so the derived table has that column twice and MySQL
        # refuses it: order/b2bItemWise died on "Duplicate column name
        # 'approved_qty'". Counting a constant counts the same groups and has
        # nothing to collide.
        head = ["'query' => " + Q]
        # The Yii 1 spelling: the region is still the original text here, and
        # the group -> groupBy edit is collected rather than applied.
        grouped = (re.search(r'\$' + re.escape(var) + r'\s*->\s*group\s*=', region)
                   or re.search(re.escape(Q) + r'\s*->\s*groupBy\s*\(', region))
        if grouped:
            head.append("'totalCount' => (clone %s)->select(new \\yii\\db\\Expression('1'))->count()" % Q)
        take_span(m.start(), i + close.end(),
                  'new ActiveDataProvider([%s])' % ', '.join(head + opts))
        found = True

    if not found and required:
        return [], False, 'a CActiveDataProvider this cannot read'

    return [], True, ''


def column_literal(arg):
    """
    A column name argument, with the padding inside the quotes removed.

    Yii 1 builds these conditions by concatenation - `$column . ' IN (...)'` -
    so `addInCondition('id ', $ids)` produces `id  IN (...)` and the space is
    harmless. Yii 2 takes the array key as a column name and quotes it, so the
    same argument became `` `id ` `` and the query failed on a column that
    does not exist. site/search is written that way.
    """
    arg = arg.strip()
    m = re.match(r"^(['\"])(.*)\1$", arg, re.S)

    return '%s%s%s' % (m.group(1), m.group(2).strip(), m.group(1)) if m else arg


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


def sub_balanced(head, fn, text):
    """
    Like re.sub, but the argument list is matched by balancing parentheses.

    `head` must end where the arguments open. The rules that used a non-greedy
    match for the argument list instead stopped at the first `)` followed by a
    delimiter, which is the wrong one the moment an argument spans lines:

        UserRole::model()->findByAttributes(array(
                'title' => 'Vendor'
        ));

    came out as `where(array('title' => 'Vendor')->orderBy(...)->one())` - the
    array closed, the call left open - and item/barCode died on "Call to a
    member function orderBy() on array". Four models carried that.
    """
    out, i = [], 0
    while True:
        m = head.search(text, i)
        if not m:
            out.append(text[i:])
            break
        j, depth = m.end(), 1
        while j < len(text) and depth:
            if text[j] == '(':
                depth += 1
            elif text[j] == ')':
                depth -= 1
            j += 1
        if depth:
            out.append(text[i:m.end()])      # unbalanced source; leave it alone
            i = m.end()
            continue
        out.append(text[i:m.start()])
        rep = fn(m, text[m.end():j - 1])
        # A rule that declines returns the head it was given. The arguments
        # are not part of that head, so emitting it alone dropped them:
        # `Vendor::model()->findAll(` followed by the `;` that came after the
        # arguments. Four models stopped generating, and the generator's own
        # parse check was the only thing that caught it.
        out.append(text[m.start():j] if rep == m.group(0) else rep)
        i = j

    return ''.join(out)


def by_attributes_php(m, args=None):
    """X::model()->findAllByAttributes(attrs, options) as a Yii 2 query."""
    cls, kind = m.group(1), m.group(2)
    if args is None:
        args = m.group(3)
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
                cols.append('%s %s' % (col, 'DESC' if desc else 'ASC'))
            if cols:
                # As a string, not an array. port_views renames every
                # `'name' =>` key to `'attribute' =>` for the grid columns, and
                # that rule runs *after* this one - so an order on a column
                # called `name` came out as `orderBy(['attribute' => SORT_ASC])`
                # and orderItem's admin grid died on "Unknown column
                # 'attribute' in 'order clause'". Yii 2 parses this form and
                # quotes the column itself.
                query += "->orderBy('" + ', '.join(cols) + "')"

    return query + default_order_call(cls, query) + \
        ('->all()' if kind.lower().startswith('findall') else '->one()')


def default_order_call(cls, query):
    """
    `->orderBy(...)` for a finder that inherits Yii 1's defaultScope().

    Yii 1 applies defaultScope() to every finder, so
    `PaymentMode::model()->findAllByAttributes(['type_id' => 0])` comes back
    id DESC. Yii 2 has no such thing, and the converted query came back in
    whatever order the storage engine gave - which for these was ascending,
    so order/admin's filter dropdowns listed the same names as Yii 1 in
    exactly the opposite order.

    Not caught by the UI suite: a filter dropdown lives in the grid's <th>
    row, which grid_rows() skips.

    The order is read from the model's own defaultScope() and emitted as a
    literal, so nothing at runtime has to know what defaultOrder() is - and a
    model that overrides the scope to no ordering gets no orderBy at all.
    """
    if '->orderBy(' in query:
        return ''                    # the options array already gave one
    order = default_order_of(cls)

    return '->orderBy(%s)' % order if order else ''


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

    text = dao_idioms(text)

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

    text = finder_idioms(text)

    return text


def chtml_tag_wrap(m, args):
    """CHtml::tag's arguments, reordered for Html::tag."""
    bits = split_args_php(args)
    if len(bits) == 1:
        return '\\yii\\helpers\\Html::tag(%s)' % bits[0].strip()
    if len(bits) == 2:
        return "\\yii\\helpers\\Html::tag(%s, '', %s)" % (bits[0].strip(), bits[1].strip())
    if len(bits) >= 3:
        return '\\yii\\helpers\\Html::tag(%s, %s, %s)' % (
            bits[0].strip(), bits[2].strip(), bits[1].strip())

    return m.group(0)


def log_idioms(text):
    """
    Yii 1's logger, which Yii 2 does not have.

    `Yii::log($msg, $level, $category)` became four functions named for the
    level. There is no Yii::log() in Yii 2 at all, so anything still spelled
    that way is a fatal the first time the line runs - which is why these
    lasted: every one of them sits on a branch that had not been reached.
    OrderItem's pair are in the velocity writer, whose caller (afterSave) had
    not been ported, and ItemUi's is in a catch block.
    """
    LEVEL = {'error': 'Yii::error', 'warning': 'Yii::warning',
             'info': 'Yii::info', 'trace': 'Yii::debug', 'profile': 'Yii::info'}

    def one(m, args_text):
        args = split_args_php(args_text)
        msg = args[0] if args else "''"
        level = args[1].strip().strip("'\"").lower() if len(args) > 1 else 'info'
        level = re.sub(r'^clogger::level_', '', level)
        fn = LEVEL.get(level, 'Yii::info')
        cat = args[2].strip() if len(args) > 2 else None
        return '%s(%s%s)' % (fn, msg, ', ' + cat if cat else '')

    return sub_balanced(re.compile(r"\bYii::log\s*\("), one, text)


def dao_idioms(text):
    """
    The framework calls that are neither a model nor a query: Yii 1's DAO
    query builder, and CJSON.

    Shared by all three translators. These lived in the model translator
    alone, then in the model and controller translators, and each time the
    same page found the copy that was missing: loyaltyAdmin/index died on
    "Calling unknown method: Command::select()" from its *view*, which
    had neither. An idiom belongs in one place that all three call.
    """
    # Yii 1's DAO query builder hangs off createCommand(); Yii 2's is a Query
    # object, and its Command has no select().
    #
    # The chain's last call has to move with it. Yii 1 ends one of these with
    # queryScalar()/queryAll()/queryRow()/queryColumn(), which are Command's
    # methods - and Yii 2's Command has all four, so rewriting them everywhere
    # would break the places that really do call a Command. Only the chains
    # this rule has just turned into a Query are followed.
    FETCH = {'queryScalar': 'scalar', 'queryAll': 'all',
             'queryRow': 'one', 'queryColumn': 'column'}
    # An optional `->cache(N)` in front. Yii 1 spells query caching fluently -
    # `$db->cache(3600)->createCommand()...` - and Yii 2 takes a closure and
    # the duration instead. Dropping it would be a behaviour change: the one
    # site that uses it caches a grouped scan of 1.3M rows for an hour,
    # deliberately, and without it the dashboard does that scan on every load.
    start = re.compile(r"Yii::(?:app\s*\(\s*\)|\$app)\s*->\s*db\s*"
                       r"(?:->\s*cache\s*\(\s*([^()]*?)\s*\)\s*)?"
                       r"->\s*createCommand\s*\(\s*\)\s*->\s*select\s*\(")
    out, i = [], 0
    while True:
        m = start.search(text, i)
        if not m:
            out.append(text[i:])
            break
        out.append(text[i:m.start()])
        cached = m.group(1)
        out.append(('Yii::$app->db->cache(function ($db) {\n            return '
                    if cached else '')
                   + '(new \\yii\\db\\Query())->select(')
        # Walk the chain from the open parenthesis this just emitted, so the
        # fetch call that ends it is the one belonging to this query and not
        # some later statement's.
        j, depth = m.end(), 1
        while j < len(text) and depth:
            if text[j] == '(':
                depth += 1
            elif text[j] == ')':
                depth -= 1
            j += 1
        out.append(text[m.end():j])          # select()'s own arguments
        while True:
            link = re.match(r"\s*->\s*(\w+)\s*\(", text[j:])
            if not link:
                break
            called = link.group(1)
            k, depth = j + link.end(), 1
            while k < len(text) and depth:
                if text[k] == '(':
                    depth += 1
                elif text[k] == ')':
                    depth -= 1
                k += 1
            if called in FETCH:
                # Inside the closure the fetch takes the connection it was
                # handed, so the cached query runs on that one.
                tail = text[j + link.end(1):k]
                if cached:
                    tail = re.sub(r'\(\s*\)$', '($db)', tail)
                out.append(text[j:j + link.start(1)] + FETCH[called] + tail)
                if cached:
                    out.append('; }, %s)' % cached)
                j = k
                break
            out.append(text[j:k])
            j = k
        i = j

    text = ''.join(out)

    # CDbCommand::group() is Query::groupBy().
    text = re.sub(r"(\)\s*\n?\s*)->\s*group\s*\(", lambda m: m.group(1) + '->groupBy(', text)

    # A Command that was never a Query still fetches the Yii 1 way. The rule
    # above only follows the chains it has just converted, because Yii 2's
    # Command really does have queryScalar/queryAll/queryColumn - but it has
    # no queryRow() under any spelling, so one left here is always Yii 1's.
    text = re.sub(r"->\s*queryRow\s*\(", '->queryOne(', text)

    text = log_idioms(text)

    # CJSON is PHP's own json_* in Yii 2. decode() returns an array in Yii 1,
    # so the second argument is not optional if the result is used as one.
    text = re.sub(r"\bCJSON::encode\s*\(", 'json_encode(', text)
    text = re.sub(r"\bCJSON::decode\s*\(([^;]*?)\)(\s*[;,\)])",
                  lambda m: 'json_decode(' + m.group(1) + ', true)' + m.group(2), text)

    # `Yii::app()` is `Yii::$app`. The translators each had their own rule for
    # this; the shared set did not, so a method brought across by a merge -
    # B2bPurchaseBill::userwisesearch(), which reads two session keys - kept
    # the Yii 1 spelling and died on a static method that does not exist.
    text = re.sub(r"\bYii::app\s*\(\s*\)", lambda m: 'Yii::$app', text)

    # A missing application parameter is null in Yii 1 and an error in Yii 2.
    #
    # `Yii::app()->params['listPerPage']` reads a key that is not in
    # config/params.php - nor anywhere else in the Yii 1 tree - so Yii 1
    # yields null and CPagination falls back to its default. Yii 2 turns the
    # warning into an exception, and item/printBarcode answered 500 where
    # Yii 1 answers 200. Three parameters are read that way.
    #
    # Not for an assignment target, and not for one that already has a
    # fallback.
    text = re.sub(r"(Yii::\$app->params)\s*\[\s*('(?:\w+)')\s*\](?!\s*(?:=[^=]|\?\?))",
                  lambda m: '(%s[%s] ?? null)' % (m.group(1), m.group(2)), text)

    # CDataProvider::getData() is ActiveDataProvider::getModels().
    # item/printBarcode's view iterates the provider that way.
    text = re.sub(r"(\$\w*(?i:dataProvider)\w*)\s*->\s*getData\s*\(\s*\)",
                  lambda m: m.group(1) + '->getModels()', text)

    # Yii 1's ePdf extension is a wrapper round the same mPDF this uses
    # directly - see ItemController::punchGenerateBillAndSend(), which was
    # hand-ported that way. The component does not exist here, so
    # order/userwisePdf died on "Getting unknown property: Application::ePdf".
    #
    # The temp directory matches the Yii 1 configuration, which points
    # _MPDF_TEMP_PATH at the application runtime directory. 35 call sites
    # across ten controllers, in two shapes.
    TMP = "'tempDir' => Yii::getAlias('@runtime')"
    text = re.sub(r"Yii::\$app\s*->\s*ePdf\s*->\s*mpdf\s*\(\s*\)",
                  lambda m: 'new \\Mpdf\\Mpdf([%s])' % TMP, text)
    text = re.sub(r"Yii::\$app\s*->\s*ePdf\s*->\s*mpdf\s*\(\s*'[^']*'\s*,\s*"
                  r"('[^']*')\s*\)",
                  lambda m: "new \\Mpdf\\Mpdf(['format' => %s, %s])"
                            % (m.group(1), TMP), text)
    # CActiveRecord::saveAttributes() is updateAttributes(): both write the
    # named attributes straight to the row, without validating and without
    # firing the save events. Eight actions across five controllers call it,
    # and every one of them was a 500 - none was covered by any suite, because
    # they write and the read-only sweep would not request them.
    text = re.sub(r"->\s*saveAttributes\s*\(", '->updateAttributes(', text)

    # CHtml::link is Html::a, with the same argument order.
    text = re.sub(r"\bCHtml::link\s*\(", lambda m: '\\yii\\helpers\\Html::a(', text)

    # CHtml::tag is Html::tag, and the arguments are *not* in the same order:
    # Yii 1 takes (tag, htmlOptions, content), Yii 2 takes (tag, content,
    # options). Renaming without swapping would put the attributes in the
    # body and the body in the attributes.
    text = sub_balanced(re.compile(r"\bCHtml::tag\s*\("),
                        lambda m, a: chtml_tag_wrap(m, a), text)

    # CDbExpression is yii\db\Expression. The application uses it for NOW()
    # and other raw SQL in an assignment, where a quoted string would be
    # written to the column literally.
    text = re.sub(r"\bnew\s+CDbExpression\s*\(",
                  lambda m: 'new \\yii\\db\\Expression(', text)

    # CArrayDataProvider takes the rows as its first argument; Yii 2 takes a
    # configuration array with allModels.
    text = re.sub(r"\bnew\s+CArrayDataProvider\s*\(\s*(\$\w+)\s*\)",
                  lambda m: "new \\yii\\data\\ArrayDataProvider(['allModels' => %s])" % m.group(1),
                  text)

    # CPagination is yii\data\Pagination, and it takes the row count as a
    # configuration key rather than a constructor argument.
    text = re.sub(r"\bnew\s+CPagination\s*\(\s*(\$\w+)\s*\)",
                  lambda m: "new \\yii\\data\\Pagination(['totalCount' => %s])" % m.group(1),
                  text)

    return text


# The keys Yii 1's CActiveRecord::findAll($options) understands. An array of
# these is a *criteria*, not a set of column values - `findAll(['order' =>
# 'name ASC'])` asks for every row in name order, and converting it as a
# condition asked Yii 2 for the rows whose `order` column is 'name ASC'. Not
# an error, just the wrong rows, which is why it has to be recognised rather
# than left to the condition rule.
FINDER_OPTIONS = ('order', 'limit', 'offset', 'condition', 'params', 'select',
                  'with', 'group', 'having', 'distinct', 'index', 'together',
                  'join', 'alias', 'scopes')


def options_finder(m, args=None):
    cls, kind = m.group(1), m.group(2)
    if args is None:
        args = m.group(3)
    args = args.strip()
    # The argument list as written, with the outer array unwrapped if it has
    # one - the balanced match hands over everything between the parentheses.
    inner = re.match(r'^(?:array\s*\(|\[)(.*)[)\]]$', args, re.S)
    if inner:
        args = inner.group(1)
    keys = re.findall(r"'(\w+)'\s*=>", args)
    if not keys or any(k not in FINDER_OPTIONS for k in keys):
        return m.group(0)
    # Only the options this can express. Anything else is left as Yii 1 code,
    # which is a page that fails loudly rather than one that lists the wrong
    # rows.
    if any(k not in ('order', 'limit', 'offset', 'select', 'condition', 'params')
           for k in keys):
        return m.group(0)

    query = cls + '::find()'
    # `condition` and its `params` are one where(). Order::toApiArray() reads
    # a loyalty transaction this way.
    cond = re.search(r"'condition'\s*=>\s*((?:'[^']*'|\"[^\"]*\"))", args)
    if cond:
        prm = re.search(r"'params'\s*=>\s*((?:array\s*\(|\[)"
                        r"(?:[^()\[\]]|\[[^\[\]]*\]|\([^()]*\))*[)\]])", args, re.S)
        query += '->where(%s%s)' % (cond.group(1),
                                    ', ' + prm.group(1) if prm else '')
    elif 'condition' in keys:
        return m.group(0)          # a condition this cannot read
    sel = re.search(r"'select'\s*=>\s*('[^']*')", args)
    if sel:
        query += '->select(%s)' % sel.group(1)
    om = re.search(r"'order'\s*=>\s*'([^']+)'", args)
    if om:
        cols = []
        for part in om.group(1).split(','):
            bits = part.strip().split()
            if not bits:
                continue
            col = bits[0][2:] if bits[0].startswith('t.') else bits[0]
            desc = len(bits) > 1 and bits[1].lower().startswith('desc')
            cols.append('%s %s' % (col, 'DESC' if desc else 'ASC'))
        if cols:
            # A string, for the reason by_attributes_php() gives.
            query += "->orderBy('%s')" % ', '.join(cols)
    for which in ('limit', 'offset'):
        w = re.search(r"'" + which + r"'\s*=>\s*(\d+)", args)
        if w:
            query += '->%s(%s)' % (which, w.group(1))

    return query + default_order_call(cls, query) + \
        ('->all()' if kind.lower().startswith('findall') else '->one()')


def normalise_self(text, cls):
    """
    `self::model()` written inside the model itself, as that model's name.

    The conversions read the class out of the call - `User::model()->findAll()`
    - so a method that says `self::model()` handed them the class "self", and
    scopes_of('self') found nothing: User::searchByName() was refused over a
    named scope that is declared perfectly well on User.

    None of these models is subclassed, so self, static and the class name are
    the same thing here.
    """
    return re.sub(r"\b(?:self|static)::model\s*\(\s*\)",
                  lambda m: cls + '::model()', text)


def finder_idioms(text):
    """
    `X::model()->find*` as Yii 2 spells it.

    Shared, because the controller translator has its own rule set and does not
    call yii1_idioms(): these conversions lived here only, so a controller kept
    every `X::model()` it was written with - five of them in LoyaltyAdmin - and
    died on the first one to run.
    """
    M = r"(\w+)::model\s*\(\s*\)\s*->\s*"
    text = re.sub(M + r"(?i:findByPk)\s*\(", lambda m: m.group(1) + '::findOne(', text)
    # See port_views.py: these take an options array Yii 2's findAll/findOne
    # do not, and an empty attributes array means "everything" in Yii 1 and
    # "nothing useful" through findAll().
    text = sub_balanced(
        re.compile(r"\b(\w+)::model\s*\(\s*\)\s*->\s*"
                   r"((?i:findAllByAttributes|findByAttributes))\s*\(", re.S),
        by_attributes_php, text)
    text = sub_balanced(
        re.compile(r"\b(\w+)::model\s*\(\s*\)\s*->\s*((?i:findAll|find))\s*\(", re.S),
        lambda m, a: options_finder(m, a), text)

    # `deleteAllByAttributes` and `countByAttributes`, which have no Yii 2
    # spelling at all. Both take the same attributes array as the finders
    # above, so an empty one means "every row" in Yii 1 - which is why the
    # array is passed through rather than defaulted.
    text = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*(?i:deleteAllByAttributes)\s*\("
                  r"\s*((?:array\s*\(|\[)(?:[^()\[\]]|\[[^\[\]]*\]|\([^()]*\))*[)\]])\s*\)",
                  lambda m: '%s::deleteAll(%s)' % (m.group(1), m.group(2)), text)
    text = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*(?i:countByAttributes)\s*\("
                  r"\s*((?:array\s*\(|\[)(?:[^()\[\]]|\[[^\[\]]*\]|\([^()]*\))*[)\]])\s*\)",
                  lambda m: '%s::find()->where(%s)->count()' % (m.group(1), m.group(2)), text)

    # A finder given a condition and its parameters:
    #   User::model()->find('username = :username', [':username' => $u])
    # Yii 2 puts both on where().
    text = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*((?i:findAll|find))\s*\(\s*"
                  r"((?:'[^']*'|\"[^\"]*\"))\s*,\s*"
                  r"((?:array\s*\(|\[)(?:[^()\[\]]|\[[^\[\]]*\]|\([^()]*\))*[)\]])\s*\)",
                  lambda m: '%s::find()->where(%s, %s)%s%s'
                            % (m.group(1), m.group(3), m.group(4),
                               default_order_call(m.group(1), ''),
                               '->all()' if m.group(2).lower().startswith('findall') else '->one()'),
                  text)
    # The same with only a condition.
    text = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*((?i:findAll|find))\s*\(\s*"
                  r"((?:'[^']*'|\"[^\"]*\"))\s*\)",
                  lambda m: '%s::find()->where(%s)%s%s'
                            % (m.group(1), m.group(3),
                               default_order_call(m.group(1), ''),
                               '->all()' if m.group(2).lower().startswith('findall') else '->one()'),
                  text)

    # `resetScope()` says "ignore defaultScope() for this query". Yii 2 has no
    # default scope, so the reset itself is a no-op - but the order that scope
    # implied is exactly what the conversions below would otherwise add, so
    # these are converted here, without one.
    text = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*resetScope\s*\(\s*\)\s*->\s*"
                  r"((?i:findAll|find|count))\s*\(\s*\)",
                  lambda m: '%s::find()->%s()'
                            % (m.group(1),
                               {'findall': 'all', 'find': 'one',
                                'count': 'count'}[m.group(2).lower()]), text)

    # `X::model()->tableName()` is a static in Yii 2.
    text = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*tableName\s*\(\s*\)",
                  lambda m: m.group(1) + '::tableName()', text)

    # Yii 1 applies defaultScope() to a bare finder too, so `find()` returns
    # the *last* row by id, not the first. Setting::model()->find() picks the
    # newest settings row; Setting::find()->one() picked whichever the storage
    # engine handed back first.
    text = re.sub(M + r"findAll\s*\(\s*\)",
                  lambda m: '%s::find()%s->all()'
                            % (m.group(1), default_order_call(m.group(1), '')), text)
    text = re.sub(M + r"count\s*\(\s*\)", lambda m: m.group(1) + '::find()->count()', text)
    # `X::model()->with(...)->findByAttributes(...)` - a finder reached
    # through a chain rather than directly, which the rules above skip because
    # they want the finder next to model(). loyaltyAdmin/viewTransactions is
    # written that way and kept its Yii 1 call.
    def chained(m):
        cls, withargs, kind, args = m.groups()
        paths, together, ok, _joins = port_with_full('$criteria->with = %s;' % withargs)
        if not ok or not paths:
            return m.group(0)
        eager = ', '.join("'%s'" % x for x in paths)
        # A relation the application asked not to join stays a second query.
        join = [x for x in paths if together.get(x, True)]
        load = ("->joinWith([%s])" % ', '.join(
            "'%s' => function ($q) { $q->alias('%s'); }" % (x, x.split('.')[-1])
            for x in join)) if join else ''
        lazy = [x for x in paths if not together.get(x, True)]
        if lazy:
            load += '->with([%s])' % ', '.join("'%s'" % x for x in lazy)
        tail = '->all()' if kind.lower().startswith('findall') else '->one()'
        return '%s::find()%s->where(%s)%s' % (cls, load, args.strip(), tail)

    text = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*with\s*\((.*?)\)\s*->\s*"
                  r"((?i:findAllByAttributes|findByAttributes))\s*\((.*?)\)\s*(?=[;,)\]])",
                  chained, text, flags=re.S)

    text = re.sub(M + r"find\s*\(\s*\)",
                  lambda m: '%s::find()%s->one()'
                            % (m.group(1), default_order_call(m.group(1), '')), text)
    text = re.sub(M + r"exists\s*\(", lambda m: m.group(1) + '::find()->exists(', text)

    # `findBySql`/`findAllBySql` are statics in Yii 2, and they hand back a
    # query rather than a row, so the fetch has to be spelled out.
    text = sub_balanced(
        re.compile(r"\b(\w+)::model\s*\(\s*\)\s*->\s*"
                   r"((?i:findAllBySql|findBySql))\s*\(", re.S),
        lambda m, a: '%s::findBySql(%s)%s'
                     % (m.group(1), a.strip(),
                        '->all()' if m.group(2).lower().startswith('findall')
                        else '->one()'),
        text)

    # A bare `X::model()` with nothing chained onto it. Yii 1 hands back a
    # shared instance, used for the things that do not depend on a row -
    # attributeLabels(), checkPermission(), building a menu. Yii 2 has no such
    # thing, and a fresh instance answers all of them the same way.
    #
    # Last, so every chained form above has already been converted and this
    # cannot swallow one.
    text = re.sub(r"\b(\w+)::model\s*\(\s*\)(?!\s*->)",
                  lambda m: 'new %s()' % m.group(1), text)

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
        # No `return` before the provider to cut at, which is how
        # userwisesearch() and two of OrderItem's are written: the provider is
        # assigned to a variable and returned further down. The criteria
        # converter understands a provider as a consumer in its own right, so
        # it can take the method whole.
        #
        # Only as a fallback. Run first, it also took methods the substitution
        # below handles correctly and produced worse output for them - four
        # models stopped generating at all.
        direct, ok, _ = convert_criteria(body, cls_hint=model)
        if ok and 'CActiveDataProvider' not in direct and 'CDbCriteria' not in direct:
            return direct
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
            # The order can live on the provider's sort rather than on the
            # criteria - PurchaseBillDetail's listing is ordered by
            # `t.order Asc` there and nowhere else - and carrying only the
            # criteria's order left that listing in storage order.
            sort = port_search_sort(body, aliased)
            if sort:
                lines.append(indent + '$query->orderBy(%s);' % sort)
                lines.append('')
            lines.append(indent + 'return new ActiveDataProvider([')
            lines.append(indent + "    'query' => $query,")
            # A grouped listing needs its own count. Yii 2 counts by wrapping
            # the select list - `SELECT COUNT(*) FROM (...)` - and these
            # select `t.*` alongside `SUM(approved_qty) AS approved_qty`, so
            # the derived table has that column twice and MySQL refuses it:
            # order/b2bItemWise died on "Duplicate column name
            # 'approved_qty'". Counting a constant counts the same groups and
            # has nothing to collide.
            if re.search(r'\$query\s*->\s*groupBy\s*\(', '\n'.join(lines)):
                lines.append(indent + "    'totalCount' => (clone $query)"
                                      "->select(new \\yii\\db\\Expression('1'))->count(),")
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

    # Case-insensitively: two of these models spell it `Const`, which PHP
    # accepts and this pattern did not - so Setting::SETTING_NO was never
    # carried across and user/timer died on an undefined constant.
    consts = re.findall(r'(?i:const)\s+(\w+)\s*=\s*([^;]+);', base)
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

    # After `table`: a null label is resolved against the schema's foreign
    # keys, not the model's relations().
    labels = port_labels(parse_block(base, 'attributeLabels') or '',
                         parse_block(base, 'relations') or '', table)

    rep = re.search(r"representingColumn\s*\(\)\s*\{\s*return\s*'(\w+)'", base)
    representing = rep.group(1) if rep else 'id'

    lbl = re.search(r"label\s*\(\s*\$n\s*=\s*1\s*\)\s*\{\s*return\s*Yii::t\s*\(\s*'app'\s*,\s*'([^|]*)\|([^']*)'", base)
    singular, plural = (lbl.group(1), lbl.group(2)) if lbl else (model, model + 's')

    rules = port_rules(parse_block(base, 'rules') or '', warn)
    rels = port_relations(parse_block(base, 'relations') or '', warn)

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
    # Whether the rebuilt search() will declare the `t` alias - the same test
    # it makes below - so the sort can keep the qualifier when it does.
    search_aliased = any(c.startswith('t.') for c, _ in exact + partial)
    search_sort = port_search_sort(search_body, search_aliased)
    page_size = port_page_size(search_body)

    has_before_validate = 'function beforeValidate' in base
    default_order = port_default_scope(base, warn, concrete)

    # The concrete model's own methods. These are hand-written - option lists
    # built from another table, computed columns for a report - and the views
    # call them, so leaving them behind gives a page that renders until it hits
    # one. They are translated with the same rules as the controllers and
    # anything unrecognised is reported.
    concrete_methods, notes = port_concrete(concrete, model)

    # The base model's own helpers, where it has any beyond giix's.
    # BaseVendor declares getAllItems(), a static the vendor/item form calls to
    # list the items a vendor does not already carry - it is application code
    # that happens to live in the generated base, and nothing was carrying it
    # across, so vendor/item died on a method the port did not have.
    #
    # Only names the concrete model and the generator do not already provide,
    # and never the giix boilerplate, which is generated from the schema above.
    GIIX = {'model', 'tableName', 'label', 'representingColumn', 'rules',
            'relations', 'pluralize', 'attributeLabels', 'search', 'defaultScope',
            'primaryKey', 'behaviors', 'scopes', 'getRelationLabel'}
    base_methods, base_notes = port_concrete(base, model)
    taken_names = methods_of('\n'.join(concrete_methods)) | GIIX
    extra = []
    for text in base_methods:
        m = re.search(r'function\s+(\w+)\s*\(', text)
        if not m or m.group(1) in taken_names or m.group(1).lower().endswith('search'):
            continue
        if re.match(r'get\w*Options$', m.group(1)):
            continue          # handled above, from the same source
        if re.search(YII1_CLASSES, text):
            # Same rule the presentation-only merge uses: a method the
            # translator could not finish is left out rather than carried in
            # half-converted. BaseItem::adjust() is one - it writes, and its
            # criteria is consumed in a way convert_criteria cannot read, so
            # item/adjustStock fails on a missing method instead of on a
            # class that does not exist three lines into it.
            notes.append('%s(): not shared from the base - still contains '
                         'Yii 1 code' % m.group(1))
            continue
        taken_names.add(m.group(1))
        extra.append(text)
    concrete_methods = concrete_methods + extra
    notes += [n for n in base_notes if 'not ported' in n and 'base method' not in n]

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
    A("     * GxActiveRecord::isAllowed(): whether this row belongs to the")
    A('     * operator who is signed in.')
    A('     *')
    A("     * False for a model with no create_user_id, which is what Yii 1")
    A('     * answers. bill/delete asks it before deleting, and died on a')
    A('     * method the port did not have.')
    A('     */')
    A('    public function isAllowed()')
    A('    {')
    A("        if (!$this->hasAttribute('create_user_id')) {")
    A('            return false;')
    A('        }')
    A('')
    A('        return $this->create_user_id == Yii::$app->user->id;')
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
    # Yii 1's own parameters, after Yii 2's. Nothing in the port calls
    # search() with arguments - the controllers call $model->search() - so
    # they only ever take their defaults, which is what Yii 1 did too. A
    # parameter Yii 1 declared without one gets null, because it would
    # otherwise be a required parameter following an optional one.
    extra = [pair for pair in block_params(base, 'search')
             if pair[0] not in ('$params',)]
    sig = '$params = []'
    for name, default in extra:
        if not name.startswith('$'):
            warn.append('search(%s): parameter this cannot read, dropped' % name)
            continue
        sig += ', %s = %s' % (name, default if default else 'null')
        if not default:
            warn.append('search(): %s had no default in Yii 1; it gets null here'
                        % name)
    A('    public function search(%s)' % sig)
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
    'getTotals', 'isAllowCreate', 'isAllowed', 'getRelationLabel', 'checkPermission',
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
    # The form-only properties must also be *safe*, or load() will not set
    # them. rules() is never replaced on a model the API port wrote - it
    # decides how that model validates and saves - so the safe rule is added
    # alongside rather than swapped in. These attributes are not columns, so
    # permitting mass assignment of them cannot write anything to the database;
    # it only sets a public property, which is what Yii 1 does.
    #
    # onlineOrder/admin filters on a date range it keeps in the session, puts
    # it in $_GET, and loads it back. start_date was not safe here, so the
    # filter silently never applied and the grid showed every order instead of
    # the day's.
    props_wanted = [n for n, _ in re.findall(r'^    public (\$\w+)(\s*=\s*[^;]+)?;',
                                             generated, re.M)]
    if props_wanted:
        names = [n.lstrip('$') for n in props_wanted]
        # Every safe rule, not the first one. With re.search() a model that
        # had already been given two of these - OrderItem has `refund_qty,
        # bill_date` and `mode_of_payment, columns, ...` - only ever matched
        # one of them, so the names in the other counted as missing and were
        # added again on each run. OrderItem had accumulated fifteen copies.
        safe_now = set()
        for grp in re.findall(r"\[\[([^\]]*)\],\s*'safe'\]", existing):
            safe_now.update(re.findall(r"'(\w+)'", grp))
        missing = [n for n in names if n not in safe_now]
        if missing and 'public function rules' in existing:
            rule = ("            [['%s'], 'safe'],  // form-only, declared on the "
                    "Yii 1 model\n" % "', '".join(missing))
            patched, n = re.subn(r"(public function rules\(\)\s*\{\s*return \[\n)",
                                 lambda m: m.group(1) + rule, existing, count=1)
            if n:
                existing = patched
                added.append('safe rule for ' + ', '.join(missing))

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
    #   label() and representingColumn() - both read straight out of the
    #     Yii 1 model and used only for display. An older generated label()
    #     survived every refresh and put 'Payment Mode' in the page title
    #     where Yii 1 writes 'PaymentMode'. The difftest compares grid rows
    #     and form fields, never the title, so 54 controllers passed with
    #     the wrong one.
    replace = ['attributeLabels', 'defaultOrder', 'listingOrder', 'init',
               'label', 'representingColumn']

    #   search() - but only on a full port, never on a presentation-only merge
    #     into a model this pipeline is not porting. search() backs the admin
    #     grid and nothing else: the API does not call it. Keeping an older
    #     generated one meant Item's grid kept a page size of 10 where Yii 1
    #     asks its provider for 100, so the port showed a tenth of the rows.
    if 'presentation-only' not in ' '.join(warn):
        replace.append('search')
        #   and the other listings - listsearch(), reportsearch(),
        #     itemwisesearch(), userwisesearch(). port_provider_methods()
        #     produces them from the same Yii 1 source by the same rules, so
        #     an older one is stale in exactly the way an older search() is.
        #     B2bPurchaseBillDetail::itemwisesearch() kept a query built on
        #     the wrong model - the class had been read off a commented-out
        #     finder - through every later fix, and order/b2bItemWise died on
        #     a relation OrderItem does not have.
        replace += [n for n, _ in method_blocks(generated)
                    if n.lower().endswith('search') and n != 'search']
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
                     'getTotals', 'isAllowCreate', 'isAllowed', 'getCompanyBarcode'}

# Never added to a model the API port wrote, whatever else says otherwise.
# These decide how the model validates, what it saves and what a listing
# returns, and the whole premise of presentation-only is that it cannot change
# any of that. The read-only test is not enough on its own: rules() and
# search() read nothing and write nothing, and adding them to Order anyway
# would change what the order API validates on every save.
NEVER_SHARED = {'rules', 'search', 'beforeValidate', 'init', 'scenarios',
                'behaviors', 'transactions', 'primaryKey', 'tableName',
                'optimisticLock', 'attributeHints',
                # Yii 2 declares toArray() on Model itself, with a different
                # signature and a different meaning - it returns fields(), not
                # the hand-built JSON shape Yii 1's toArray() returns. The API
                # port carries Yii 1's as toApiArray() for exactly that
                # reason, and copying the original back in would override the
                # framework's on a model the API serialises.
                'toArray'}


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
