#!/usr/bin/env python3
"""
Generates a Yii 2 model from the giix base class.

The _base models are generated code and completely regular, which is what makes
this safe: every one declares its rules, relations, labels and search() the
same way. Anything irregular - a hand-written method on the concrete model, a
rule validator with no Yii 2 equivalent - is reported rather than guessed at.
"""
import re, sys, os

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


def port_default_scope(base, warn):
    """
    The ordering Yii 1 applies to every query on this model.

    GxActiveRecord::defaultScope() returns `id DESC` for any model with an id.
    22 of the 72 models override it, mostly to an empty array, which means no
    ORDER BY at all - the rows come back in whatever order the storage engine
    produces for that query. Applying `id DESC` to those anyway puts the grid
    and every filter dropdown in an order Yii 1 never showed.

    Returns a PHP expression for the sort, or 'null' for no ordering.
    """
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


def port_search(body, warn):
    """The compare() calls that back the admin grid."""
    exact, partial = [], []
    for m in re.finditer(r"\$criteria->compare\s*\(\s*'([^']+)'\s*,\s*\$this->(\w+)\s*(,\s*true)?\s*\)", body):
        col, _, is_partial = m.groups()
        (partial if is_partial else exact).append(col)
    if 'addCondition' in body or 'with' in body:
        warn.append('search() has conditions beyond compare(); check it by hand')
    return exact, partial


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

        text = arrays(text)
        text = re.sub(r'Yii::app\(\)->', 'Yii::$app->', text)
        text = re.sub(r'Yii::app\(\)', 'Yii::$app', text)

        # CDbCriteria -> a query built the Yii 2 way
        crit = re.search(r'\$criteria\s*=\s*new\s+CDbCriteria\s*\(\s*\)\s*;', text)
        if crit:
            order = re.search(r"\$criteria->order\s*=\s*'([^']+)'\s*;", text)
            limit = re.search(r"\$criteria->limit\s*=\s*'?(\d+)'?\s*;", text)
            conds = re.findall(r"\$criteria->addCondition\(\s*(.+?)\s*\)\s*;", text)
            find = re.search(r"(\w+)::model\(\)->(findAll|find)\s*\(\s*\$criteria\s*\)", text)
            if find:
                cls, kind = find.group(1), find.group(2)
                q = cls + '::find()'
                for c in conds:
                    q += "\n            ->andWhere(" + c + ")"
                if order:
                    cols = []
                    for part in order.group(1).split(','):
                        bits = part.strip().split()
                        col = bits[0]
                        desc = len(bits) > 1 and bits[1].lower().startswith('desc')
                        cols.append("'%s' => %s" % (col, 'SORT_DESC' if desc else 'SORT_ASC'))
                    q += "\n            ->orderBy([" + ', '.join(cols) + "])"
                if limit:
                    q += "\n            ->limit(" + limit.group(1) + ")"
                q += "\n            ->" + ('all()' if kind == 'findAll' else 'one()')
                # drop the criteria plumbing and replace the fetch
                text = re.sub(r"\s*\$criteria\s*=\s*new\s+CDbCriteria\s*\(\s*\)\s*;", '', text)
                text = re.sub(r"\s*\$criteria->(order|limit)\s*=[^;]+;", '', text)
                text = re.sub(r"\s*\$criteria->addCondition\([^;]+\);", '', text)
                text = re.sub(r"\w+::model\(\)->(?:findAll|find)\s*\(\s*\$criteria\s*\)",
                              lambda mm: q, text)

        text = re.sub(r'\b(?:Gx|C)Html::encode\(', 'Html::encode(', text)
        text = re.sub(r'\b(?:Gx|C)Html::link\(', 'Html::a(', text)
        text = re.sub(r'\b(?:Gx|C)Html::image\(', 'Html::img(', text)
        text = re.sub(r'\bGxHtml::valueEx\(', 'Gx::str(', text)

        text = re.sub(r"(\w+)::model\(\)->findByPk\(", lambda mm: mm.group(1) + '::findOne(', text)
        text = re.sub(r"(\w+)::model\(\)->findByAttributes\(", lambda mm: mm.group(1) + '::findOne(', text)
        text = re.sub(r"(\w+)::model\(\)->findAllByAttributes\(", lambda mm: mm.group(1) + '::findAll(', text)
        text = re.sub(r"(\w+)::model\(\)->findAll\(\s*\)", lambda mm: mm.group(1) + '::find()->all()', text)

        leftover = set(re.findall(r'\b(C[A-Z]\w+)\b', text)) | set(re.findall(r'Yii::app\(\)', text))
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
    exact, partial = port_search(parse_block(base, 'search') or '', warn)

    has_before_validate = 'function beforeValidate' in base
    default_order = port_default_scope(base, warn)

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
    A('        $provider = new ActiveDataProvider([')
    A("            'query' => $query,")
    A("            'sort' => ['defaultOrder' => self::defaultOrder() ?: []],")
    A("            'pagination' => ['pageSize' => Ui::PAGE_SIZE],")
    A('        ]);')
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
    body = existing.rstrip()
    assert body.endswith('}'), 'model does not end in a class brace'
    body = body[:-1].rstrip()

    for name, text in method_blocks(generated):
        if name in have:
            warn.append('kept the existing %s(); the generated one was discarded' % name)
            continue
        body += '\n\n' + text.strip('\n')
        added.append(name)

    # constants the generated model declares and the existing one lacks
    for m in re.finditer(r'    public const (\w+) = ([^;]+);', generated):
        if ('const ' + m.group(1)) not in existing:
            body = re.sub(r'(class\s+\w+\s+extends\s+\w+\s*\{)',
                          lambda mm: mm.group(1) + '\n    public const %s = %s;' % (m.group(1), m.group(2)),
                          body, count=1)
            added.append('const ' + m.group(1))

    if 'LegacyColumnTypes' not in existing and 'presentation' not in ' '.join(warn):
        body = re.sub(r'(class\s+\w+\s+extends\s+\w+\s*\{)',
                      lambda mm: mm.group(1) +
                      '\n    // Yii 1 hands out column values as strings; the option helpers'
                      '\n    // compare them loosely and answer wrongly for an integer 0.'
                      '\n    use LegacyColumnTypes;\n',
                      body, count=1)
        added.append('use LegacyColumnTypes')

    # use statements the added code needs
    for u in ('use app\\components\\Criteria;', 'use app\\components\\Ui;',
              'use Yii;', 'use yii\\data\\ActiveDataProvider;'):
        if u not in body and u.split('\\')[-1].rstrip(';') in body:
            body = re.sub(r'(namespace app\\models;\n)', lambda mm: mm.group(1) + '\n' + u + '\n',
                          body, count=1)
    return body + '\n}\n', added


# Methods a model needs only so that *other* models' pages can display it: its
# name, its string form, the permission helper the views call. Deliberately not
# rules(), search() or beforeValidate() - those change how the model validates
# and saves, and these models are already in use by the ported API.
PRESENTATION_ONLY = {'label', 'representingColumn', '__toString', 'checkPermission',
                     'getRelationLabel', 'defaultOrder'}


if __name__ == '__main__':
    deps_only = '--presentation-only' in sys.argv
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    model = args[0]
    src, warn = generate(model, model.lower())

    if deps_only:
        keep = []
        # Constants come across too. They declare no behaviour - another
        # model's query that says Vendor::STATUS_ACTIVE needs the constant to
        # exist, and adding it cannot change how Vendor itself validates or
        # saves.
        for m in re.finditer(r'    public const \w+ = [^;]+;', src):
            keep.append(m.group(0))
        for name, text in method_blocks(src):
            if name in PRESENTATION_ONLY:
                keep.append(text)
        header = src.split('class ')[0]
        src = (header + 'class %s extends ActiveRecord\n{\n' % model
               + '\n\n'.join(t.strip('\n') for t in keep) + '\n}\n')
        warn = ['presentation-only merge: rules(), search() and beforeValidate() '
                'were not added, because this model is used elsewhere']
    out = f'{ROOT}/app2/models/{model}.php'

    if os.path.exists(out):
        existing = read(out)
        merged, added = merge(existing, src, model, warn)
        open(out, 'w', encoding='utf-8').write(merged)
        print('merged into app2/models/%s.php: %s'
              % (model, ', '.join(added) if added else 'nothing to add'))
    else:
        open(out, 'w', encoding='utf-8').write(src)
        print('wrote app2/models/%s.php' % model)

    for x in warn:
        print('  NOTE: ' + x)
