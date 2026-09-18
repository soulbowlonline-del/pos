#!/usr/bin/env python3
"""
Transforms a Yii 1 CRUD view into the Yii 2 equivalent.

The views are Gii output with hand edits on top - commented-out columns,
custom filters, reordered fields. Regenerating them from the model metadata
would produce tidy views that show the wrong columns, so each one is
transformed instead and the hand edits survive.

What this does NOT do is guess. Anything it does not recognise is left alone
and reported, so the unconverted call shows up as a PHP error on the first
request rather than as a page that renders and is quietly wrong.
"""
import re, sys, os

# --- array(...) -> [...] ------------------------------------------------------

def arrays_to_brackets(src):
    """Rewrite array(...) as [...], respecting strings and comments."""
    out = []
    i = 0
    n = len(src)
    stack = []          # open paren positions that came from array(
    while i < n:
        c = src[i]
        # skip over strings so array( inside one is untouched
        if c in ('"', "'"):
            q = c
            out.append(c)
            i += 1
            while i < n:
                if src[i] == '\\':
                    out.append(src[i:i + 2]); i += 2; continue
                out.append(src[i])
                if src[i] == q:
                    i += 1; break
                i += 1
            continue
        if src.startswith('//', i) or src.startswith('#', i):
            j = src.find('\n', i)
            j = n if j < 0 else j
            out.append(src[i:j]); i = j; continue
        if src.startswith('/*', i):
            j = src.find('*/', i)
            j = n if j < 0 else j + 2
            out.append(src[i:j]); i = j; continue
        m = re.match(r'\barray\s*\(', src[i:])
        if m:
            out.append('[')
            stack.append(True)
            i += m.end()
            continue
        if c == '(':
            stack.append(False)
            out.append(c); i += 1; continue
        if c == ')':
            if stack and stack.pop():
                out.append(']')
            else:
                out.append(')')
            i += 1; continue
        out.append(c); i += 1
    return ''.join(out)


# --- widget calls -------------------------------------------------------------

BOOSTER = {
    'TbGridView': 'GridView',
    'TbExtendedGridView': 'GridView',
    'TbDetailView': 'DetailView',
    'TbButtonGroup': 'ButtonGroup',
    'TbButton': 'Button',
    'TbMenu': 'Menu',
    'TbTabs': 'Tabs',
    # not a YiiBooster widget, but the views call it the same way and it is
    # ported under the same name
    'CommentPortlet': 'CommentPortlet',
    'CGridView': 'GridView',
}


def widgets(src, unknown):
    """$this->widget('bootstrap.widgets.TbX', [...]) -> X::widget([...])"""
    def repl(m):
        already_echoed, name = m.group(1), m.group(2)
        if name not in BOOSTER:
            unknown.append(name)
            return m.group(0)
        # Yii 1's $this->widget() writes to the output buffer; Yii 2's
        # X::widget() returns a string. Without the echo the widget renders
        # nothing at all - a page that is missing its grid but raises no error.
        prefix = already_echoed or 'echo '
        return prefix + BOOSTER[name] + '::widget('

    src = re.sub(r"(echo\s+)?\$this->widget\(\s*'(?:bootstrap\.widgets\.|zii\.widgets\.\w+\.)?([A-Za-z]+)'\s*,\s*",
                 repl, src)
    # the begin/end pair used by forms
    src = re.sub(r"\$form\s*=\s*\$this->beginWidget\(\s*'bootstrap\.widgets\.TbActiveForm'\s*,\s*",
                 '$form = ActiveForm::begin(', src)
    src = re.sub(r"\$this->endWidget\(\s*\)", 'ActiveForm::end()', src)
    return src


def split_args(src, start):
    """Given the index just after an opening '(', return the argument spans."""
    depth = 0
    args = []
    cur = start
    i = start
    n = len(src)
    while i < n:
        c = src[i]
        if c in ('"', "'"):
            q = c
            i += 1
            while i < n:
                if src[i] == '\\':
                    i += 2; continue
                if src[i] == q:
                    break
                i += 1
            i += 1
            continue
        if c in '([{':
            depth += 1
        elif c in ')]}':
            if depth == 0:
                args.append((cur, i))
                return args, i
            depth -= 1
        elif c == ',' and depth == 0:
            args.append((cur, i))
            cur = i + 1
        i += 1
    return args, n


def wrap_urls(src):
    """Html::a(label, ['route', ...]) -> Html::a(label, Gx::url([...]))"""
    out = src
    for fn in ('Html::a(',):
        pos = 0
        while True:
            i = out.find(fn, pos)
            if i < 0:
                break
            args, end = split_args(out, i + len(fn))
            if len(args) >= 2:
                a, b = args[1]
                arg = out[a:b]
                if arg.strip().startswith('['):
                    out = out[:a] + ' Gx::url(' + arg.strip() + ')' + out[b:]
                    pos = i + len(fn)
                    continue
            pos = i + len(fn)
    return out


# --- everything else ----------------------------------------------------------

def rewrite(src, ctrl, unknown):
    src = arrays_to_brackets(src)
    src = widgets(src, unknown)

    # $form->widget() cannot keep its name in Yii 2 - see ActiveForm.
    src = re.sub(r'\$form->widget\(', '$form->renderWidget(', src)

    # Yii::t('app', 'X') -> 'X'   (no translations are configured; Yii 1
    # returns the message unchanged)
    src = re.sub(r"Yii::t\(\s*'[^']*'\s*,\s*('(?:[^'\\]|\\.)*')\s*\)", r'\1', src)

    # URLs
    src = re.sub(r"Yii::app\(\)->createUrl\(", 'Ui::to(', src)
    src = re.sub(r"Yii::app\(\)->controller->createUrl\(", 'Ui::to(', src)
    src = re.sub(r"\$this->createUrl\(", 'Ui::to(', src)

    # html helpers
    src = re.sub(r"\bGxHtml::encode\(", 'Html::encode(', src)
    src = re.sub(r"\bCHtml::encode\(", 'Html::encode(', src)
    src = re.sub(r"\bGxHtml::link\(", 'Html::a(', src)
    src = re.sub(r"\bCHtml::link\(", 'Html::a(', src)

    # GxHtml::valueEx($model) is the model's __toString
    src = re.sub(r"GxHtml::valueEx\(\s*(\$[A-Za-z_][\w>()\-\$\[\]']*)\s*\)", r'Gx::str(\1)', src)
    # GxHtml::listDataEx(Model::model()->findAllAttributes(null, true))
    src = re.sub(r"GxHtml::listDataEx\(\s*([A-Za-z_]\w*)::model\(\)->findAllAttributes\([^)]*\)\s*\)",
                 lambda m: "Gx::listData(" + m.group(1) + "::class)", src)

    src = re.sub(r"GxActiveRecord::extractPkValue\(\s*(\$[\w>\-()\$\[\]']*)\s*,[^)]*\)",
                 lambda m: 'Gx::pk(' + m.group(1) + ')', src)

    # A Yii 1 url is a route string or ['route', 'k' => v]; Gx::url() sends it
    # through Ui so it reaches whichever stack serves that controller now.
    src = wrap_urls(src)

    # Controller methods and properties the views reach for. In Yii 1 a view
    # runs with $this bound to the controller; in Yii 2 it is bound to the
    # View, and the controller is $this->context.
    for meth in ('StartPanel', 'AddPanel', 'AddNewPanel', 'EndPanel',
                 'EndPanelLeft', 'EndPanelRight', 'updateMenuItems',
                 'loadModel', 'isAllowed'):
        src = re.sub(r'\$this->' + meth + r'\s*\(', '$this->context->' + meth + '(', src)
    src = re.sub(r'\$this->pageCaption\b', '$this->context->pageCaption', src)
    src = re.sub(r'\$this->pageTitle\b', '$this->title', src)

    # controller state the views set or read
    src = re.sub(r"\$this->breadcrumbs\s*=", "$this->params['breadcrumbs'] =", src)
    src = re.sub(r"\$this->menu\b", '$this->context->menu', src)
    src = re.sub(r"\$this->actions\b", '$this->context->actions', src)

    # inline script
    src = re.sub(r"Yii::app\(\)->clientScript->registerScript\(\s*'[^']*'\s*,\s*",
                 '$this->registerJs(', src)

    # Whatever Yii::app() calls are left after the specific rewrites above -
    # user, session, params, request. The accessor differs; the shape does not.
    src = re.sub(r'Yii::app\(\)->', 'Yii::$app->', src)
    src = re.sub(r'Yii::app\(\)', 'Yii::$app', src)

    # Partials. Yii 1's renderPartial() writes to the output buffer; Yii 2's
    # render() returns the string. Without the echo the partial is rendered and
    # thrown away - the page comes back 200 with its grid or its form simply
    # absent, which no status check would catch.
    src = re.sub(r"(=\s*|echo\s+|return\s+)?\$this->renderPartial\(",
                 lambda m: (m.group(1) or 'echo ') + '$this->render(', src)

    # grid/detail column keys
    src = re.sub(r"'name'\s*=>", "'attribute' =>", src)
    src = re.sub(r"'type'\s*=>\s*'raw'", "'format' => 'raw'", src)

    # 'value' => '$data->foo' - a string expression evaluated per row in Yii 1
    def value_expr(m):
        expr = arrays_to_brackets(m.group(1).replace("\\'", "'"))
        return "'value' => function ($data) { return " + expr + '; }'
    src = re.sub(r"'value'\s*=>\s*'((?:[^'\\]|\\.)*\$data(?:[^'\\]|\\.)*)'", value_expr, src)

    # 'url' => 'Yii::app()->controller->createUrl(...)' - a PHP expression that
    # Yii 1 evaluated once per row with $data in scope. The same per-row
    # evaluation in Yii 2 is a closure.
    def url_expr(m):
        expr = arrays_to_brackets(m.group(1).replace("\\'", "'"))
        return "'url' => function ($data) { return " + expr + '; }'
    src = re.sub(r"'url'\s*=>\s*'((?:[^'\\]|\\.)*\$data(?:[^'\\]|\\.)*)'", url_expr, src)

    # 'visible' => '$data->checkPermission ("x/y")=="true"'
    def visible_expr(m):
        return "'visible' => Access::check('" + m.group(1) + "')"
    src = re.sub(r"'visible'\s*=>\s*'\$data->checkPermission\s*\(\s*\"([^\"]+)\"\s*\)\s*==\s*\"true\"'",
                 visible_expr, src)

    # the button columns
    src = re.sub(r"'class'\s*=>\s*'(?:bootstrap\.widgets\.)?(?:TbButtonColumn|CxButtonColumn|FaButtonColumn|CButtonColumn)'",
                 lambda m: "'class' => ActionColumn::class", src)

    # $model->search() keeps its name; $data->getXOptions stays a method call
    return src


def imports(src, ctrl):
    """The use statements the rewritten view needs."""
    need = []
    if 'Ui::' in src:
        need.append('use app\\components\\Ui;')
    if 'Access::' in src:
        need.append('use app\\components\\Access;')
    if 'Gx::' in src:
        need.append('use app\\components\\Gx;')
    if 'Html::' in src:
        need.append('use yii\\helpers\\Html;')
    if 'Yii::' in src:
        need.append('use Yii;')
    for w in set(BOOSTER.values()):
        if re.search(r'\b' + w + r'::', src):
            need.append('use app\\widgets\\' + w + ';')
    if 'ActionColumn::' in src:
        need.append('use app\\widgets\\ActionColumn;')
    if 'ActiveForm::' in src:
        need.append('use app\\widgets\\ActiveForm;')
    # Model classes referenced statically. A short name already imported from
    # somewhere else - a widget, a helper - must not be imported again from
    # app\models: two use statements for the same alias is a fatal error.
    taken = {u.rsplit('\\', 1)[-1].rstrip(';') for u in need}
    for cls in set(re.findall(r'\b([A-Z]\w+)::(?:get\w+|label|model|class)\b', src)):
        if cls in taken or cls in ('Html', 'Ui', 'Access', 'Gx', 'Yii'):
            continue
        need.append('use app\\models\\' + cls + ';')
        taken.add(cls)
    return sorted(set(need))


def port_file(path, ctrl):
    src = open(path, encoding='utf-8', errors='replace').read()
    unknown = []
    out = rewrite(src, ctrl, unknown)
    uses = imports(out, ctrl)

    header = ('<?php\n/**\n * Ported from protected/views/%s/%s.\n */\n\n%s\n?>\n'
              % (ctrl, os.path.basename(path), '\n'.join(uses)))
    # drop the original leading <?php if the file opened with one
    out = re.sub(r'^\s*<\?php\s*', '', out, count=1) if out.lstrip().startswith('<?php') else out
    if not out.lstrip().startswith('<?') and not out.lstrip().startswith('<'):
        out = '<?php\n' + out
    return header + out, unknown


if __name__ == '__main__':
    ctrl = sys.argv[1]
    only = sys.argv[sys.argv.index('--only') + 1] if '--only' in sys.argv else None
    # With --keep-existing, a view already in the tree is left alone: it may
    # have been ported by hand and tuned against a test.
    keep = '--keep-existing' in sys.argv
    src_dir = '/root/pos/pos83/protected/views/' + ctrl
    dst_dir = '/root/pos/pos83/app2/views/' + ctrl
    os.makedirs(dst_dir, exist_ok=True)
    allunknown = {}
    for f in sorted(os.listdir(src_dir)):
        if not f.endswith('.php') or (only and f != only):
            continue
        if keep and os.path.exists(os.path.join(dst_dir, f)):
            print('  kept existing ' + f)
            continue
        body, unknown = port_file(os.path.join(src_dir, f), ctrl)
        open(os.path.join(dst_dir, f), 'w', encoding='utf-8').write(body)
        if unknown:
            allunknown[f] = unknown
    print('ported %d views for %s' % (len(os.listdir(dst_dir)), ctrl))
    for f, u in allunknown.items():
        print('  UNCONVERTED in %s: %s' % (f, ', '.join(sorted(set(u)))))
