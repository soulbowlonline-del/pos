#!/usr/bin/env python3
"""
A widget that registers javascript in Yii 1 registers it here too.

Four widgets were found stubbed one at a time, each by someone using the
application and finding a screen that did nothing: the datepicker (63 views),
CKEditor (12), Redactor (11), the typeahead (10). Every one rendered its input
and bound nothing to it. The markup was right, so every page comparison
passed, and the screen was unusable.

They share a shape that can be looked for instead of waited for. The Yii 1
widget calls registerScript, registerScriptFile, registerCssFile or publishes
an asset; the port's counterpart calls none of Yii 2's equivalents. That is
not proof - a widget can legitimately need no script - but it is the short
list worth reading, and it is two orders of magnitude shorter than the views.

The same question is asked of ActiveForm's *Row helpers, which is where
datepickerRow, ckEditorRow and redactorRow were hiding.
"""
import os, re, sys

REPO = '/root/pos/pos83'
PORT = REPO + '/app2/widgets'
Y1_DIRS = [REPO + '/ext-prod/bootstrap/widgets',
           REPO + '/ext-prod/bootstrap/widgets/input',
           REPO + '/protected/components',
           REPO + '/protected/extensions']

PORT_REG = re.compile(r'register(Js|JsFile|Css|CssFile|AssetBundle)\s*\(')
Y1_REG = re.compile(r'register(Script|ScriptFile|Css|CssFile|CoreScript|Asset\w*)\s*\(')


def read(p):
    try:
        return open(p, errors='replace').read()
    except OSError:
        return ''


def find_yii1(name):
    """The Yii 1 widget of the same name, wherever it lives."""
    for d in Y1_DIRS:
        for root, _, files in os.walk(d):
            for f in files:
                if f[:-4].lower() == name.lower() and f.endswith('.php'):
                    return os.path.join(root, f)
    return None


def body_of(text, fn):
    m = re.search(r'function\s+%s\s*\(' % re.escape(fn), text)
    if not m:
        return None
    i = text.find('{', m.end())
    if i < 0:
        return None
    d, j = 0, i
    while j < len(text):
        if text[j] == '{':
            d += 1
        elif text[j] == '}':
            d -= 1
            if d == 0:
                break
        j += 1
    return text[i:j]


def uses(name, kind):
    """How many ported views use this widget or form helper."""
    n = 0
    for root, _, files in os.walk(REPO + '/app2/views'):
        for f in files:
            if not f.endswith('.php'):
                continue
            t = read(os.path.join(root, f))
            if (kind == 'widget' and (name + '::widget') in t) or \
               (kind == 'row' and name in t):
                n += 1
    return n


def main():
    rows = []

    for f in sorted(os.listdir(PORT)):
        if not f.endswith('.php'):
            continue
        name = f[:-4]
        port = read(os.path.join(PORT, f))
        if PORT_REG.search(port):
            continue                       # it registers something
        y1path = find_yii1(name)
        if not y1path:
            continue                       # nothing to compare against
        if not Y1_REG.search(read(y1path)):
            continue                       # Yii 1 registers nothing either
        rows.append(('widget', name, uses(name, 'widget'),
                     os.path.relpath(y1path, REPO)))

    form = read(PORT + '/ActiveForm.php')
    for m in re.finditer(r'function\s+(\w+Row)\s*\(', form):
        fn = m.group(1)
        b = body_of(form, fn) or ''
        if PORT_REG.search(b):
            continue
        y1 = read(REPO + '/ext-prod/bootstrap/widgets/TbActiveForm.php')
        yb = body_of(y1, fn)
        if yb is None:
            continue
        # TbActiveForm delegates to TbInput; a row whose type has a *Js
        # renderer in TbInput is one that binds something.
        m2 = re.search(r'TYPE_(\w+)', yb)
        if not m2:
            continue
        kind = m2.group(1).lower()
        tb = read(REPO + '/ext-prod/bootstrap/widgets/input/TbInputHorizontal.php')
        if not re.search(r'function\s+%s\w*\s*\(' % kind, tb, re.I):
            continue
        rb = body_of(tb, kind) or body_of(tb, kind + 'Js') or ''
        if 'widget(' not in rb and 'register' not in rb:
            continue
        rows.append(('form row', fn, uses(fn, 'row'), 'TbActiveForm::' + fn))

    print('  %-9s %-22s %-6s %s' % ('KIND', 'NAME', 'VIEWS', 'YII 1 COUNTERPART'))
    for kind, name, n, src in rows:
        print('  %-9s %-22s %-6s %s' % (kind, name, n, src))
    print('\n  registers nothing where Yii 1 registers something: %d' % len(rows))
    return 1 if rows else 0


if __name__ == '__main__':
    sys.exit(main())
