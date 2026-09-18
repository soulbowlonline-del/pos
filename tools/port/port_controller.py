#!/usr/bin/env python3
"""
Translates a Yii 1 CRUD controller into the Yii 2 equivalent.

The 25 plain CRUD controllers are giix output with small hand edits - a
different menu, an extra redirect, a commented-out permission check - so they
are translated rather than regenerated from a template, and the edits survive.

Anything the translator does not recognise is left as it was and reported. An
unconverted Yii 1 call then fails loudly on the first request, which is the
intended outcome: a translator that guesses produces a page that renders and is
quietly wrong.
"""
import re, sys, os, subprocess

ROOT = '/root/pos/pos83'
sys.path.insert(0, '/root/pos')
from port_views import arrays_to_brackets


def lcfirst(s):
    return s[0].lower() + s[1:]


def translate(src, model, ctrl, warn):
    body = arrays_to_brackets(src)

    # class declaration
    body = re.sub(r'class\s+(\w+)Controller\s+extends\s+\w+',
                  r'class \1Controller extends BaseUiController', body)

    # access is handled once in the base controller; these two are Yii 1 plumbing
    for fn in ('filters', 'accessRules'):
        body = drop_method(body, fn)

    # loadModel takes the class name in Yii 1
    # Yii 1 is written with a space before the paren in places -
    # `$model->unsetAttributes ()`, `new CActiveDataProvider ( 'ItemTax' )` -
    # so every one of these patterns has to tolerate it.
    body = re.sub(r"\$this->loadModel\s*\(\s*([^,]+?)\s*,\s*'\w+'\s*\)", r'$this->loadModel(\1)', body)

    # request
    # These files are written both `Yii::app()->x` and `Yii::app ()->x`, so
    # none of these can be a plain string replace.
    A = r'Yii::app\s*\(\s*\)\s*->\s*'
    body = re.sub(A + r'getRequest\s*\(\s*\)\s*->\s*getIsPostRequest\s*\(\s*\)',
                  'Yii::$app->request->isPost', body)
    body = re.sub(A + r'getRequest\s*\(\s*\)\s*->\s*getIsAjaxRequest\s*\(\s*\)',
                  'Yii::$app->request->isAjax', body)
    body = re.sub(A + r'request\s*->\s*isAjaxRequest', 'Yii::$app->request->isAjax', body)
    body = re.sub(A + r'request\s*->\s*isPostRequest', 'Yii::$app->request->isPost', body)
    body = re.sub(A + r'end\s*\(\s*\)', 'Yii::$app->end()', body)
    body = re.sub(A + r'createUrl\s*\(', 'Ui::to(', body)
    # whatever else is left has the same shape in Yii 2
    body = re.sub(r'Yii::app\s*\(\s*\)\s*->', 'Yii::$app->', body)
    body = re.sub(r'Yii::app\s*\(\s*\)', 'Yii::$app', body)

    # form input
    body = re.sub(r"isset\s*\(\s*\$_POST\s*\[\s*'" + model + r"'\s*\]\s*\)",
                  "Yii::$app->request->post('" + model + "') !== null", body)
    body = re.sub(r"isset\s*\(\s*\$_GET\s*\[\s*'" + model + r"'\s*\]\s*\)",
                  "Yii::$app->request->get('" + model + "') !== null", body)
    body = re.sub(r"\$model\s*->\s*setAttributes\s*\(\s*\$_POST\s*\[\s*'" + model + r"'\s*\]\s*\)",
                  "$model->load(Yii::$app->request->post())", body)
    body = re.sub(r"\$model\s*->\s*setAttributes\s*\(\s*\$_GET\s*\[\s*'" + model + r"'\s*\]\s*\)",
                  "$model->load(Yii::$app->request->queryParams)", body)

    # data provider
    # Both `new CActiveDataProvider('X')` and the two-argument form that
    # carries a sort. Note the brackets rather than array(): the array
    # rewriter has already run, so the second argument reads `[ ... ]` here
    # even though the Yii 1 source says `array( ... )`. Matching only the
    # one-argument form left the call unconverted the moment a sort was
    # added to those index actions, and the page died on a class that does
    # not exist in Yii 2.
    body = re.sub(r"new\s+CActiveDataProvider\s*\(\s*'" + model +
                  r"'\s*(?:,\s*\[(?:[^\[\]]|\[[^\[\]]*\])*\]\s*)?\)",
                  ("new ActiveDataProvider(['query' => " + model + "::find(),\n"
                   "            // The model's own defaultScope() decides the order - most\n"
                   "            // inherit `id DESC`, but 22 of them override it to none.\n"
                   "            // Hardcoding id DESC here listed rows Yii 1 never showed.\n"
                   "            // defaultOrder, not listingOrder: index builds its own\n"
                   "            // provider and never calls search(), so the order the admin\n"
                   "            // grid gets from the criteria does not apply here.\n"
                   "            'sort' => ['defaultOrder' => " + model + "::defaultOrder() ?: []],\n"
                   "            'pagination' => ['pageSize' => Ui::PAGE_SIZE]])"), body)

    # the search scenario
    body = re.sub(r"new\s+" + model + r"\s*\(\s*'search'\s*\)",
                  "new " + model + "(['scenario' => 'search'])", body)
    # unsetAttributes() cleared the defaults a new record starts with; a Yii 2
    # model built for a scenario has no defaults to clear.
    body = re.sub(r"\s*\$model->unsetAttributes\s*\(\s*\)\s*;", '', body)

    # exceptions
    body = re.sub(r"throw\s+new\s+CHttpException\s*\(\s*400\s*,\s*([^)]+)\)",
                  r'throw new BadRequestHttpException(\1)', body)
    body = re.sub(r"throw\s+new\s+CHttpException\s*\(\s*404\s*,\s*([^)]+)\)",
                  r'throw new NotFoundHttpException(\1)', body)
    body = re.sub(r"throw\s+new\s+CHttpException\s*\(\s*403\s*,\s*([^)]+)\)",
                  r'throw new ForbiddenHttpException(\1)', body)

    # Yii::t with no translations configured returns the message unchanged
    body = re.sub(r"Yii::t\(\s*'[^']*'\s*,\s*('(?:[^'\\]|\\.)*')\s*\)", r'\1', body)

    # render/redirect return a response in Yii 2
    body = re.sub(r'(?m)^(\s*)\$this\s*->\s*render\s*\(', r'\1return $this->render(', body)
    body = re.sub(r'(?m)^(\s*)\$this\s*->\s*renderPartial\s*\(', r'\1return $this->renderPartial(', body)
    body = re.sub(r'(?m)^(\s*)\$this\s*->\s*redirect\s*\(', r'\1return $this->redirect(', body)

    # `X::model()->find*` - the Yii 1 way of reaching a finder, written both
    # tightly and with spaces.
    M = r"(\w+)::model\s*\(\s*\)\s*->\s*"
    body = re.sub(M + r"(?i:findByPk)\s*\(", lambda m: m.group(1) + '::findOne(', body)
    body = re.sub(M + r"(?i:findByAttributes)\s*\(", lambda m: m.group(1) + '::findOne(', body)
    body = re.sub(M + r"(?i:findAllByAttributes)\s*\(", lambda m: m.group(1) + '::findAll(', body)
    body = re.sub(M + r"findAll\s*\(\s*\)", lambda m: m.group(1) + '::find()->all()', body)

    # Yii 1's logger. Yii 2 splits the level into the method name, and
    # CVarDumper::dumpAsString is var_export.
    body = re.sub(r"CVarDumper::dumpAsString\s*\(", 'var_export(', body)
    body = re.sub(r"(var_export\([^;]*?)\)(\s*),(\s*)CLogger::LEVEL_\w+",
                  lambda m: m.group(1) + ', true)' + m.group(2) + ',' + m.group(3) + 'LEVEL', body)
    body = re.sub(r"Yii::log\s*\(([^;]*?),\s*LEVEL\s*,\s*('[^']*')\s*\)",
                  lambda m: 'Yii::warning(' + m.group(1) + ', ' + m.group(2) + ')', body)
    body = re.sub(r"Yii::log\s*\(([^;]*?),\s*CLogger::LEVEL_ERROR\s*,\s*('[^']*')\s*\)",
                  lambda m: 'Yii::error(' + m.group(1) + ', ' + m.group(2) + ')', body)
    body = re.sub(r"Yii::log\s*\(([^;]*?),\s*CLogger::LEVEL_\w+\s*,\s*('[^']*')\s*\)",
                  lambda m: 'Yii::warning(' + m.group(1) + ', ' + m.group(2) + ')', body)

    # menu urls: array('view', 'id' => $x) -> Ui::to('ctrl/view', ['id' => $x])
    def menu_url(m):
        route, rest = m.group(1), m.group(2)
        params = rest.strip().lstrip(',').strip()
        if params:
            return "'url' => Ui::to('%s/%s', [%s])" % (ctrl, route, params)
        return "'url' => Ui::to('%s/%s')" % (ctrl, route)
    body = re.sub(r"'url'\s*=>\s*\[\s*'(\w+)'((?:\s*,\s*[^\]]*)?)\]", menu_url, body)

    # ajax validation is a Yii 1 helper on GxController
    # performAjaxValidation is ported onto the base controller, so the calls
    # stay; only the spacing is normalised.
    body = re.sub(r"\$this->performAjaxValidation\s*\(", '$this->performAjaxValidation(', body)
    body = re.sub(r"\$this->isExportRequest\s*\(", '$this->isExportRequest(', body)
    body = re.sub(r"\$this->exportCSV\s*\(", '$this->exportCSV(', body)

    for leftover in re.findall(r'Yii::app\s*\([^)]*\)[->\w]*', body):
        warn.append('unconverted: ' + leftover)
    # strip comments before scanning, or the translator's own explanatory
    # comments get reported as unconverted Yii 1 code
    scan = re.sub(r'/\*.*?\*/', '', body, flags=re.S)
    scan = re.sub(r'//[^\n]*', '', scan)
    yii1_classes = (r'\b(CDbCriteria|CActiveDataProvider|CArrayDataProvider|CDbExpression|'
                    r'CHtml|CException|CHttpException|CJSON|CVarDumper|CLogger|CUploadedFile|'
                    r'CDataProviderIterator|CActiveRecord|CModel|CSort|CPagination|CMap|'
                    r'CTypeValidator|CWidget|CController)\b')
    for leftover in set(re.findall(yii1_classes, scan)):
        warn.append('unconverted Yii 1 class: ' + leftover)

    return body


def drop_method(src, name):
    m = re.search(r'\n\tpublic function\s+' + name + r'\s*\([^)]*\)\s*\{', src)
    if not m:
        return src
    i = m.end()
    depth = 1
    while i < len(src) and depth:
        if src[i] == '{':
            depth += 1
        elif src[i] == '}':
            depth -= 1
        i += 1
    return src[:m.start()] + src[i:]


def main():
    model = sys.argv[1]
    ctrl = lcfirst(model)
    src = open(f'{ROOT}/protected/controllers/{model}Controller.php',
               encoding='utf-8', errors='replace').read()
    warn = []
    body = translate(src, model, ctrl, warn)

    body = re.sub(r'^\s*<\?php\s*', '', body, count=1)
    header = f'''<?php
namespace app\\controllers;

use app\\components\\Ui;
use app\\models\\{model};
use Yii;
use yii\\data\\ActiveDataProvider;
use yii\\helpers\\Html;
use yii\\web\\BadRequestHttpException;
use yii\\web\\ForbiddenHttpException;
use yii\\web\\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/{model}Controller.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
'''
    # Models the controller names besides its own - VendorSchemesController
    # reads Item::find(). Without the import PHP looks for them in
    # app\controllers and the action dies at the point it runs.
    others = sorted({c for c in re.findall(r'\b([A-Z]\w+)::', body)
                     if c != model
                     and c not in ('Yii', 'Ui', 'Html', 'ActiveDataProvider', 'SORT_DESC')
                     and os.path.exists(f'{ROOT}/protected/models/_base/Base{c}.php')})
    if others:
        header = header.replace('use app\\models\\' + model + ';',
                                '\n'.join(['use app\\models\\' + c + ';' for c in
                                           sorted(others + [model])]))

    # A name the API port already uses gets a suffixed controller class. The
    # URL is unchanged - Ui::toYii2Id() maps /v2/item/admin to item-ui/admin -
    # so only the class differs, and the verified API controller is untouched.
    API_NAMES = {'Emp', 'Customer', 'Item', 'Order', 'Loyalty', 'Tally', 'Site'}
    cls = model + ('Ui' if model in API_NAMES else '')
    body = re.sub(r'\bclass\s+' + model + r'Controller\b', 'class ' + cls + 'Controller', body)

    out = f'{ROOT}/app2/controllers/{cls}Controller.php'

    # Refuse to overwrite a controller this pipeline did not write.
    #
    # Several names exist twice in this application: Emp, Customer, Item and
    # Order are API controllers under app2/controllers *and* CRUD controllers
    # in the web UI. Writing by name clobbered the hand-ported API
    # EmpController with a generated UI one, and every /v2/api/emp/* route
    # started answering 404 - emp_difftest went from 14 green to 0 while the
    # UI stayed green, because nothing in the UI touches those routes.
    if os.path.exists(out):
        with open(out, encoding='utf-8', errors='replace') as fh:
            head = fh.read(1200)
        if 'Yii 2 port of protected/controllers/' not in head:
            print('REFUSED to write app2/controllers/%sController.php: it was '
                  'not generated by this pipeline' % cls)
            print('  (probably the API controller of the same name - the UI '
                  'controller needs a different route or a different name)')
            sys.exit(4)

    previous = open(out, encoding='utf-8').read() if os.path.exists(out) else None
    open(out, 'w', encoding='utf-8').write(header + body)

    # The same guard the models have. The translator rewrites code it does not
    # fully understand, and a rewrite that does not parse takes down every page
    # the controller serves - ItemUiController came out with `in_[` where the
    # source said `in_array(`, and nothing noticed until the page was fetched.
    check = subprocess.run(
        ['docker', 'exec', 'pos-php-83', 'php', '-l',
         '/var/www/html/app2/controllers/%sController.php' % cls],
        capture_output=True, text=True)
    if check.returncode != 0:
        if previous is None:
            os.remove(out)
        else:
            open(out, 'w', encoding='utf-8').write(previous)
        print('REFUSED to write app2/controllers/%sController.php: it does not parse' % cls)
        for line in (check.stdout or check.stderr).splitlines()[:2]:
            if line.strip():
                print('  ' + line.strip())
        sys.exit(5)

    print('wrote app2/controllers/%sController.php' % cls)
    for x in sorted(set(warn)):
        print('  NOTE: ' + x)


main()
