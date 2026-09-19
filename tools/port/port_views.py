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
sys.path.insert(0, '/root/pos')

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
        # The word-boundary has to be tested against the *original* string, not
        # the slice: re.match on src[i:] sees the slice's start as a boundary,
        # so the `array(` inside `in_array(` matched and became `in_[`. Every
        # in_array, is_array and array_merge in a generated file was being
        # corrupted this way.
        m = re.match(r'array\s*\(', src[i:])
        if m and (i == 0 or not (src[i - 1].isalnum() or src[i - 1] == '_')):
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
    'CJuiRadioButtonList': 'CJuiRadioButtonList',
    'CJuiDatePicker': 'CJuiDatePicker',
    'TbTypeAhead': 'TbTypeAhead',
    'TbEditableColumn': 'EditableColumn',
    'EChosenWidget': 'EChosenWidget',
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
        # nothing at all - a page missing its grid but raising no error.
        #
        # Only when the call is a statement, though. The views also use it as
        # an expression - 'filter' => $this->widget('...CJuiDatePicker', ...) -
        # where Yii 1 returned the widget object and an echo is a parse error.
        before = src[:m.start()].rstrip()
        expression = before.endswith(('=>', '=', '(', ',', '.', 'return'))
        prefix = already_echoed or ('' if expression else 'echo ')
        return prefix + BOOSTER[name] + '::widget('

    # `$grid = $this->widget(...)` - Yii 1's widget() writes to the output
    # buffer *and* returns the widget, so the assignment form still renders.
    # Yii 2 only returns, so without the echo the page is silently missing its
    # grid; the assignment is kept because the view passes it on to
    # renderExportGridButton().
    def assigned(m):
        var, name = m.group(1), m.group(2)
        if name not in BOOSTER:
            unknown.append(name)
            return m.group(0)
        return 'echo ' + var + ' = ' + BOOSTER[name] + '::widget('

    src = re.sub(r"(\$\w+)\s*=\s*\$this\s*->\s*widget\s*\(\s*'(?:[\w.]*\.)?([A-Za-z]+)'\s*,\s*",
                 assigned, src)

    src = re.sub(r"(echo\s+)?\$this\s*->\s*widget\s*\(\s*'(?:[\w.]*\.)?([A-Za-z]+)'\s*,\s*",
                 repl, src)
    # the begin/end pair used by forms
    # TbActiveForm and plain CActiveForm alike. item/importTaxData uses the
    # second, which was left as $this->beginWidget() - a View method that does
    # not exist - and the page died on it. The shim takes the same
    # configuration either way.
    src = re.sub(r"\$form\s*=\s*\$this\s*->\s*beginWidget\s*\(\s*'(?:bootstrap\.widgets\.TbActiveForm|CActiveForm)'\s*,\s*",
                 '$form = ActiveForm::begin(', src)
    src = re.sub(r"\$this\s*->\s*endWidget\s*\(\s*\)", 'ActiveForm::end()', src)
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

import port_model
from port_model import split_args_php, dump_as_string, resolve_path


def rewrite(src, ctrl, unknown):
    src = arrays_to_brackets(src)
    src = widgets(src, unknown)

    # $form->widget() cannot keep its name in Yii 2 - see ActiveForm.
    src = re.sub(r'\$form\s*->\s*widget\s*\(', '$form->renderWidget(', src)

    # Yii::t('app', 'X') -> 'X'   (no translations are configured; Yii 1
    # returns the message unchanged)
    src = re.sub(r"Yii::t\(\s*'[^']*'\s*,\s*('(?:[^'\\]|\\.)*')\s*\)", r'\1', src)

    # URLs
    # These files are written both `Yii::app()->createUrl(` and
    # `Yii::app ()->createUrl (`. Every pattern here has to allow the space, or
    # the call survives the transform and the page dies on first request.
    src = re.sub(r"Yii::app\s*\(\s*\)\s*->\s*createUrl\s*\(", 'Ui::to(', src)
    src = re.sub(r"Yii::app\s*\(\s*\)\s*->\s*controller\s*->\s*createUrl\s*\(", 'Ui::to(', src)
    src = re.sub(r"\$this\s*->\s*createUrl\s*\(", 'Ui::to(', src)
    # CController::createUrl() - a static call on the framework base class,
    # which Yii 1 allowed because createUrl is not static there either.
    src = re.sub(r"\bCController::createUrl\s*\(", 'Ui::to(', src)

    # html helpers
    src = re.sub(r"\b(?:Gx|C)Html::encode\s*\(", 'Html::encode(', src)
    src = re.sub(r"\b(?:Gx|C)Html::link\s*\(", 'Html::a(', src)
    src = re.sub(r"\b(?:Gx|C)Html::image\s*\(", 'Html::img(', src)
    src = re.sub(r"\b(?:Gx|C)Html::activeTextField\s*\(", 'Html::activeTextInput(', src)
    src = re.sub(r"\b(?:Gx|C)Html::activeTextArea\s*\(", 'Html::activeTextarea(', src)
    src = re.sub(r"\b(?:Gx|C)Html::activeDropDownList\s*\(", 'Html::activeDropDownList(', src)
    src = re.sub(r"\b(?:Gx|C)Html::activeHiddenField\s*\(", 'Html::activeHiddenInput(', src)
    src = re.sub(r"\b(?:Gx|C)Html::dropDownList\s*\(", 'Html::dropDownList(', src)
    src = re.sub(r"\b(?:Gx|C)Html::submitButton\s*\(", 'Html::submitButton(', src)
    src = re.sub(r"\b(?:Gx|C)Html::textField\s*\(", 'Html::textInput(', src)
    # Html::activeListBox with multiple => true also emits Yii 2's hidden
    # "nothing selected" input, which Yii 1 does not. ActiveForm::noUnselect()
    # turns it off; the raw calls in the views go through it too.
    src = re.sub(r"\b(?:Gx|C)Html::activeListBox\s*\(", 'Html::activeListBox(', src)
    src = re.sub(r"(Html::activeListBox\s*\((?:[^()]|\([^()]*\))*?,\s*)(\[[^\[\]]*'multiple'[^\[\]]*\])(\s*\))",
                 lambda m: m.group(1) + 'ActiveForm::noUnselect(' + m.group(2) + ')' + m.group(3), src)
    src = re.sub(r"\b(?:Gx|C)Html::listBox\s*\(", 'Html::listBox(', src)
    src = re.sub(r"\b(?:Gx|C)Html::activeCheckBoxList\s*\(", 'Html::activeCheckboxList(', src)
    src = re.sub(r"\b(?:Gx|C)Html::activeRadioButtonList\s*\(", 'Html::activeRadioList(', src)
    src = re.sub(r"\b(?:Gx|C)Html::activeFileField\s*\(", 'Html::activeFileInput(', src)
    src = re.sub(r"\b(?:Gx|C)Html::activePasswordField\s*\(", 'Html::activePasswordInput(', src)
    src = re.sub(r"\b(?:Gx|C)Html::hiddenField\s*\(", 'Html::hiddenInput(', src)
    src = re.sub(r"\b(?:Gx|C)Html::button\s*\(", 'Html::button(', src)
    src = re.sub(r"\b(?:Gx|C)Html::label\s*\(", 'Html::label(', src)
    src = re.sub(r"\b(?:Gx|C)Html::tag\s*\(", 'Html::tag(', src)
    src = re.sub(r"\b(?:Gx|C)Html::ajaxLink\s*\(", 'Html::a(', src)
    src = re.sub(r"\bGxHtml::encodeEx\s*\(", 'Gx::encodeEx(', src)
    # listDataEx over an already-loaded array of models. The rename runs
    # before the argument is rewritten below, so both forms end up as
    # Gx::listData(...) - a class name or a list of models, either of which it
    # accepts.
    # `count(X::model()->findAllAttributes(null, true)) > 0` is asking whether
    # the table has a row. Yii 1 answers it by loading every row - but only two
    # columns of each, the key and the representing column, which is why it
    # survives. Converting the finder to `X::find()->all()` drops that narrow
    # select and loads whole rows: order/create asked it of tbl_order_item and
    # exhausted a ten-gigabyte limit.
    #
    # exists() is the same question and the same answer.
    src = re.sub(r"count\s*\(\s*(\w+)::model\s*\(\s*\)\s*->\s*findAllAttributes\s*\("
                 r"[^)]*\)\s*\)\s*>\s*0",
                 lambda m: "%s::find()->exists()" % m.group(1), src)

    src = re.sub(r"\bGxHtml::listDataEx\s*\(", 'Gx::listData(', src)

    # X::model()->findAllAttributes(...) is Yii 1's "every row, two columns".
    #
    # Inside Gx::listData() it reduces to the class name, because listData does
    # that selection itself and needs to know the model's ordering. Anywhere
    # else the expression really is a list of rows - `count(...) > 0` is the
    # common one - and reducing it to a class name turns a row count into
    # count() of a string.
    src = re.sub(r"Gx::listData\s*\(\s*(\w+)::model\s*\(\s*\)\s*->\s*findAllAttributes\s*\([^)]*\)\s*\)",
                 lambda m: 'Gx::listData(' + m.group(1) + '::class)', src)
    src = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*findAllAttributes\s*\([^)]*\)",
                 lambda m: m.group(1) + '::find()->all()', src)

    # GxHtml::valueEx($model) is the model's __toString
    src = re.sub(r"GxHtml::valueEx\s*\(\s*(\$[A-Za-z_][\w>()\-\$\[\]']*)\s*\)", r'Gx::str(\1)', src)
    # GxHtml::listDataEx(Model::model()->findAllAttributes(null, true))


    src = re.sub(r"GxActiveRecord::extractPkValue\s*\(\s*(\$[\w>\-()\$\[\]']*)\s*,[^)]*\)",
                 lambda m: 'Gx::pk(' + m.group(1) + ')', src)

    # A Yii 1 url is a route string or ['route', 'k' => v]; Gx::url() sends it
    # through Ui so it reaches whichever stack serves that controller now.
    src = wrap_urls(src)

    # Controller methods and properties the views reach for. In Yii 1 a view
    # runs with $this bound to the controller; in Yii 2 it is bound to the
    # View, and the controller is $this->context.
    for meth in ('StartPanel', 'AddPanel', 'AddNewPanel', 'EndPanel',
                 'EndPanelLeft', 'EndPanelRight', 'updateMenuItems',
                 'loadModel', 'isAllowed', 'richTextEditor', 'isExportRequest',
                 'exportCSV', 'renderExportGridButton'):
        src = re.sub(r'\$this\s*->\s*' + meth + r'\s*\(', '$this->context->' + meth + '(', src)
    src = re.sub(r'\$this->pageCaption\b', '$this->context->pageCaption', src)
    src = re.sub(r'\$this->pageTitle\b', '$this->title', src)

    # CJavaScriptExpression marks a string that must reach the page as raw
    # JavaScript rather than as a quoted value. Yii 2 calls it JsExpression.
    # A lambda, not a replacement string: backslashes in a re.sub template are
    # escape sequences, and \y is not one.
    # written CJavaScriptExpression and CJavascriptExpression in different views
    src = re.sub(r"\bnew\s+(?i:CJavaScriptExpression)\s*\(",
                 lambda m: 'new \\yii\\web\\JsExpression(', src)

    # `X::model()->find*` in a view, which a few of them do directly.
    # findAllByAttributes/findByAttributes take a *second* argument in Yii 1 -
    # an options array that usually carries 'order'. Mapping them onto Yii 2's
    # findAll()/findOne(), which take a single condition, silently dropped the
    # order and, where the attributes array was empty, returned nothing at all:
    # a grid filter with no options in it.
    def by_attributes(m):
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
                    cols.append('%s %s' % (col, 'DESC' if desc else 'ASC'))
                if cols:
                    # A string, not an array. The grid-column rename below
                    # turns every `'name' =>` into `'attribute' =>`, and it
                    # runs after this one - so an order on a column called
                    # `name` came out as `orderBy(['attribute' => SORT_ASC])`
                    # and orderItem's admin grid died on "Unknown column
                    # 'attribute' in 'order clause'".
                    query += "->orderBy('" + ', '.join(cols) + "')"
        return query + ('->all()' if kind.startswith('findAll') else '->one()')

    src = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*((?i:findAllByAttributes|findByAttributes))"
                 r"\s*\((.*?)\)\s*(?=[;,)\]])", by_attributes, src, flags=re.S)

    # Yii 1's *options* form: findAll(array('order' => 'name ASC')) asks for
    # every row in that order, not for the rows whose `order` column holds
    # that string - which is what the condition rule below would have made of
    # it. loyaltyAdmin/adjustPoints lists its customer dropdown that way.
    src = re.sub(r"\b(\w+)::model\s*\(\s*\)\s*->\s*((?i:findAll|find))\s*\("
                 r"\s*(?:array\s*\(|\[)((?:[^()\[\]]|\[[^\[\]]*\]|\([^()]*\))*)"
                 r"[)\]]\s*\)", port_model.options_finder, src, flags=re.S)

    M = r"(\w+)::model\s*\(\s*\)\s*->\s*"
    src = re.sub(M + r"(?i:findByPk)\s*\(", lambda m: m.group(1) + '::findOne(', src)
    src = re.sub(M + r"(?i:findByAttributes)\s*\(", lambda m: m.group(1) + '::findOne(', src)
    src = re.sub(M + r"(?i:findAllByAttributes)\s*\(", lambda m: m.group(1) + '::findAll(', src)
    src = re.sub(M + r"findAll\s*\(\s*\)", lambda m: m.group(1) + '::find()->all()', src)

    # The DAO query builder and CJSON, from the one place all three
    # translators share. Four loyaltyAdmin views query the database
    # directly and died on a Command that has no select().
    src = port_model.dao_idioms(src)

    # controller state the views set or read
    src = re.sub(r"\$this->breadcrumbs\s*=", "$this->params['breadcrumbs'] =", src)
    src = re.sub(r"\$this->menu\b", '$this->context->menu', src)
    src = re.sub(r"\$this->actions\b", '$this->context->actions', src)

    # inline script
    src = re.sub(r"Yii::app\s*\(\s*\)\s*->\s*clientScript\s*->\s*registerScript\s*\(\s*'[^']*'\s*,\s*",
                 '$this->registerJs(', src)

    # CDbCriteria written inline in a view - a few of them build their own
    # query for a filter dropdown. Same converter as the models and the
    # controllers use.
    if 'CDbCriteria' in src:
        converted, ok, _ = port_model.convert_criteria(src)
        if ok:
            src = converted

    # Yii::import() has no Yii 2 equivalent: classes are autoloaded, and the
    # generated `use` statements name them. The call is dropped rather than
    # translated - there is nothing for it to become.
    src = re.sub(r"(?m)^[ \t]*Yii::import\s*\([^;]*\);[ \t]*\n", '', src)
    src = re.sub(r"Yii::import\s*\([^;]*\);", '', src)

    # Yii 1's logger, which a few views call directly. Same mapping the models
    # and controllers use: the level becomes the method name.
    # In a Yii 1 view $this is the controller; in Yii 2 it is the View, and
    # the controller is $this->context. The search partials all read
    # $this->route for the form's action, which threw "Getting unknown
    # property: yii\\web\\View::route" on 53 views - and because only
    # <controller>/search reaches them, the CRUD suite never saw it.
    src = re.sub(r'\$this\s*->\s*route\b', '$this->context->route', src)

    # Yii 1's pager, in both shapes the views use it: as a widget call and as
    # a 'class' entry in a grid's pager configuration. Left alone it is a class
    # that does not exist, and user/admin died on it. app\widgets\LinkPager
    # takes CLinkPager's option names.
    #
    # Through a lambda, not a replacement string: a backslash in the second
    # argument to re.sub is an escape, and \a in a class name is a bell.
    pager = '\\app\\widgets\\LinkPager'
    src = re.sub(r"'class'\s*=>\s*'CLinkPager'",
                 lambda m: "'class' => %s::class" % pager, src)
    src = re.sub(r"\$this\s*->\s*widget\s*\(\s*'CLinkPager'\s*,\s*",
                 lambda m: 'echo %s::widget(' % pager, src)

    # A button's `visible`, which Yii 1 evaluates per row with $data in scope.
    # Left as a string it is simply truthy, so every conditional button showed
    # on every row - user's grid offered both "activate" and "inactivate"
    # where Yii 1 offers whichever applies.
    def visible_expr(m):
        # Already a closure - the value was converted on an earlier pass over
        # this file. Running the rule again wrapped it in a second closure,
        # which is an object and therefore always truthy: every conditional
        # button came back on every row.
        if m.group(1).lstrip().startswith('function'):
            return m.group(0)
        code = expression_to_code(m.group(1))
        if code is None or '$data' not in code:
            return m.group(0)
        return "'visible' => function ($data) { return %s; }" % code

    src = re.sub(r"'visible'\s*=>\s*((?:'(?:[^'\\]|\\.)*'|[^,\n])+)", visible_expr, src)

    # The `name` key is written with double quotes on purpose: the
    # grid-column rename below turns every `'name' =>` into `'attribute' =>`
    # and runs after this, which left the widget with no name at all -
    # "Either 'name', or 'model' and 'attribute' properties must be specified."
    # Yii 1's captcha. The guard asks whether GD is present, which Yii 2
    # spells the same way on its own action class. The widget renders only the
    # image in Yii 1 - the text field beside it is written out separately - so
    # the template is narrowed to match, and the action route is the ported
    # controller's own.
    # CCaptcha::checkRequirements() asks whether GD is available; Yii 2 has
    # no equivalent static - its CaptchaAction throws from init() instead -
    # so the question is asked directly, which is what Yii 1 is asking.
    src = re.sub(r"\bCCaptcha::checkRequirements\s*\(\s*\)",
                 lambda m: "function_exists('imagecreatetruecolor')", src)
    src = re.sub(r"\$this\s*->\s*widget\s*\(\s*'CCaptcha'\s*\)",
                 lambda m: ("echo \\yii\\captcha\\Captcha::widget([\"name\" => 'verifyCode', "
                            "'captchaAction' => Ui::toYii2Id('%s') . '/captcha', "
                            "'template' => '{image}'])" % ctrl), src)

    src = dump_as_string(src)
    src = re.sub(r"Yii::log\s*\(([^;]*?),\s*CLogger::LEVEL_ERROR\s*,\s*('[^']*')\s*\)",
                 lambda m: 'Yii::error(' + m.group(1) + ', ' + m.group(2) + ')', src)
    src = re.sub(r"Yii::log\s*\(([^;]*?),\s*CLogger::LEVEL_\w+\s*,\s*('[^']*')\s*\)",
                 lambda m: 'Yii::warning(' + m.group(1) + ', ' + m.group(2) + ')', src)

    # Whatever Yii::app() calls are left after the specific rewrites above -
    # user, session, params, request. The accessor differs; the shape does not.
    # The theme component does not exist in Yii 2; its assets are served from
    # the same directory as before, beside the Yii 1 application.
    src = re.sub(r"Yii::app\s*\(\s*\)\s*->\s*theme\s*->\s*baseUrl", "'/themes/bar'", src)
    src = re.sub(r"Yii::app\s*\(\s*\)\s*->\s*theme\s*->\s*basePath", "'/themes/bar'", src)

    src = re.sub(r'Yii::app\s*\(\s*\)\s*->', 'Yii::$app->', src)
    src = re.sub(r'Yii::app\s*\(\s*\)', 'Yii::$app', src)

    # Partials. Yii 1's renderPartial() writes to the output buffer; Yii 2's
    # render() returns the string. Without the echo the partial is rendered and
    # thrown away - the page comes back 200 with its grid or its form simply
    # absent, which no status check would catch.
    src = re.sub(r"(=\s*|echo\s+|return\s+)?\$this\s*->\s*renderPartial\s*\(",
                 lambda m: (m.group(1) or 'echo ') + '$this->render(', src)

    # grid/detail column keys. filterHtmlOptions is the filter input's tag
    # attributes, which Yii 2 spells filterInputOptions; htmlOptions on a
    # column is the data cell's, which Yii 2 spells contentOptions.
    src = re.sub(r"'name'\s*=>", "'attribute' =>", src)
    src = re.sub(r"'filterHtmlOptions'\s*=>", "'filterInputOptions' =>", src)
    src = re.sub(r"'headerHtmlOptions'\s*=>", "'headerOptions' =>", src)
    src = re.sub(r"'footerHtmlOptions'\s*=>", "'footerOptions' =>", src)
    src = re.sub(r"'type'\s*=>\s*'raw'", "'format' => 'raw'", src)

    # 'value' => '$data->foo' - a string expression evaluated per row in Yii 1.
    #
    # $row as well as $data. Yii 1 evaluates the expression with both in
    # scope, and a serial-number column is written `'value' => '++$row''.
    # Matching only $data left that one a plain string, which Yii 2 reads as an
    # attribute name: every serial number came out blank, which is the single
    # cell that stopped mrsDetail's grid from matching.
    def value_expr(m):
        expr = arrays_to_brackets(m.group(1).replace("\\'", "'"))
        # Yii 2 hands the closure the row index; Yii 1's $row is the same
        # number, and `++$row` on a parameter reads the same either way.
        expr = re.sub(r'\$row\b', '$index', expr)
        return "'value' => function ($data, $key, $index) { return " + expr + '; }'
    src = re.sub(r"'value'\s*=>\s*'((?:[^'\\]|\\.)*\$(?:data|row)(?:[^'\\]|\\.)*)'",
                 value_expr, src)

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
    src = re.sub(r"'class'\s*=>\s*'(?:bootstrap\.widgets\.)?TbEditableColumn'",
                 lambda m: "'class' => EditableColumn::class", src)
    src = re.sub(r"'class'\s*=>\s*'(?:bootstrap\.widgets\.)?(?:CCheckBoxColumn|CheckBoxColumn)'",
                 lambda m: "'class' => CheckboxColumn::class", src)
    src = re.sub(r"'class'\s*=>\s*'(?:bootstrap\.widgets\.)?(?:TbButtonColumn|CxButtonColumn|FaButtonColumn|CButtonColumn)'",
                 lambda m: "'class' => ActionColumn::class", src)

    # $model->search() keeps its name; $data->getXOptions stays a method call
    return src



def expression_to_code(value):
    """
    A Yii 1 expression written as a concatenation, as PHP code.

    CButtonColumn evaluates a button's `visible` as PHP with $data in scope,
    and the views build it by concatenation:

        'visible' => '$data->state_id==' . User::STATUS_INACTIVE

    The string literal is only the first term. Turning the whole thing into a
    closure means emitting each literal's *contents* as code and each other
    term as itself, which is what Yii 1's eval sees. Returns None when a term
    cannot be read, and the caller then leaves the value alone.
    """
    out, i, n = [], 0, len(value)
    while i < n:
        while i < n and value[i] in ' \t\n':
            i += 1
        if i >= n:
            break
        if value[i] == "'":
            j = i + 1
            buf = []
            while j < n:
                if value[j] == '\\' and j + 1 < n:
                    buf.append(value[j + 1])
                    j += 2
                    continue
                if value[j] == "'":
                    break
                buf.append(value[j])
                j += 1
            if j >= n:
                return None
            out.append(''.join(buf))
            i = j + 1
        else:
            j = i
            depth = 0
            while j < n:
                c = value[j]
                if c in '([':
                    depth += 1
                elif c in ')]':
                    depth -= 1
                elif c == '.' and depth == 0:
                    break
                j += 1
            out.append(value[i:j].strip())
            i = j
        while i < n and value[i] in ' \t\n':
            i += 1
        if i < n and value[i] == '.':
            i += 1
        elif i < n:
            return None

    return ''.join(out) if out else None

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
    # Deliberately no `use Yii;`. Views are not namespaced, so the import is a
    # no-op - and PHP says so, with "The use statement with non-compound name
    # 'Yii' has no effect", which is an E_WARNING that Yii 2's error handler
    # turns into an exception. It is a compile-time warning, so OPcache hides
    # it after the first request: every regenerated view 500s once and then
    # looks fine, and in production the first visitor after a deploy gets the
    # 500.
    for w in set(BOOSTER.values()):
        if re.search(r'\b' + w + r'::', src):
            need.append('use app\\widgets\\' + w + ';')
    if 'ActionColumn::' in src:
        need.append('use app\\widgets\\ActionColumn;')
    if 'CheckboxColumn::' in src:
        need.append('use app\\widgets\\CheckboxColumn;')
    if 'EditableColumn::' in src:
        need.append('use app\\widgets\\EditableColumn;')
    if 'ActiveForm::' in src:
        need.append('use app\\widgets\\ActiveForm;')
    # Model classes the file names, in any form: a static call, a constant,
    # ::class. An earlier version matched only get*/label/model/class and left
    # `State::findAll(...)` and `State::STATUS_ACTIVE` unimported, which is a
    # fatal error at the point the page is rendered rather than parsed.
    #
    # A short name already imported from somewhere else - a widget, a helper -
    # must not be imported again: two use statements for one alias is fatal.
    taken = {u.rsplit('\\', 1)[-1].rstrip(';') for u in need}
    for cls in set(re.findall(r'\b([A-Z]\w+)::', src)):
        if cls in taken or cls in ('Html', 'Ui', 'Access', 'Gx', 'Yii', 'self',
                                   'static', 'parent'):
            continue
        if not is_model(cls):
            continue
        need.append('use app\\models\\' + cls + ';')
        taken.add(cls)
    return sorted(set(need))


def is_model(cls):
    """Whether this name is one of the application's models."""
    return (os.path.exists('/root/pos/pos83/protected/models/_base/Base%s.php' % cls)
            or os.path.exists('/root/pos/pos83/protected/models/%s.php' % cls)
            or os.path.exists('/root/pos/pos83/app2/models/%s.php' % cls))


def port_file(path, ctrl):
    src = open(path, encoding='utf-8', errors='replace').read()
    unknown = []
    out = rewrite(src, ctrl, unknown)
    uses = imports(out, ctrl)

    header = ('<?php\n/**\n * Ported from protected/views/%s/%s.\n */\n\n%s\n?>\n'
              % (ctrl, os.path.basename(path), '\n'.join(uses)))
    # The header has already closed PHP, so the tag is re-opened only for a
    # file that opened with one itself. Deciding by "it does not start with
    # markup" instead wrapped a view that is plain text in <?php and made it
    # code: protected/views/user/driver.php is the six bytes "Driver", and the
    # port of it would not parse.
    if out.lstrip().startswith('<?php'):
        out = '<?php\n' + re.sub(r'^\s*<\?php\s*', '', out, count=1)
    return header + out, unknown


if __name__ == '__main__':
    ctrl = sys.argv[1]
    only = sys.argv[sys.argv.index('--only') + 1] if '--only' in sys.argv else None
    # With --keep-existing, a view already in the tree is left alone: it may
    # have been ported by hand and tuned against a test.
    keep = '--keep-existing' in sys.argv
    # The view directory is not always spelled as the controller is:
    # B2BPurchaseBillDetailController's views live in
    # protected/views/b2bpurchaseBillDetail.
    src_dir = resolve_path('/root/pos/pos83/protected/views/' + ctrl)
    # Case-insensitively, like src_dir. Generating under a different
    # spelling of the controller's own name - LoyaltyAdmin for
    # loyaltyAdmin - built a second directory beside the live one and
    # left the pages being served untouched: the views looked
    # regenerated and were not.
    dst_dir = resolve_path('/root/pos/pos83/app2/views/' + ctrl)
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
