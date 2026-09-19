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
import os
import re, sys, os, subprocess

ROOT = '/root/pos/pos83'
sys.path.insert(0, '/root/pos')
from port_views import arrays_to_brackets
from port_model import dump_as_string, global_classes, resolve_path, finder_idioms, dao_idioms


def lcfirst(s):
    return s[0].lower() + s[1:]



def access_rules(src):
    """Each accessRules() entry as (kind, actions, users, undecidable)."""
    m = re.search(r'function\s+accessRules\s*\(\s*\)', src)
    if not m:
        return None
    i = src.find('{', m.end())
    depth, start = 1, i + 1
    i += 1
    while i < len(src) and depth:
        if src[i] == '{':
            depth += 1
        elif src[i] == '}':
            depth -= 1
        i += 1
    body = src[start:i - 1]
    body = re.sub(r'/\*.*?\*/', '', body, flags=re.S)
    body = re.sub(r'//[^\n]*', '', body)

    out = []
    for r in re.finditer(r"(?:array\s*\(|\[)\s*'(allow|deny)'(.*?)"
                         r"(?=(?:array\s*\(|\[)\s*'(?:allow|deny)'|$)", body, re.S):
        kind, rest = r.group(1), r.group(2)
        am = re.search(r"'actions'\s*=>\s*(?:array\s*\(|\[)(.*?)(?:\)|\])", rest, re.S)
        actions = [a.lower() for a in re.findall(r"'(\w+)'", am.group(1))] if am else None
        um = re.search(r"'users'\s*=>\s*(?:array\s*\(|\[)(.*?)(?:\)|\])", rest, re.S)
        users = re.findall(r"'([^']+)'", um.group(1)) if um else None
        undecidable = bool(re.search(r"'(roles|expression)'\s*=>", rest))
        out.append((kind, actions, users, undecidable))

    return out


def denied_actions(src):
    """
    The actions Yii 1's accessRules() refuses a signed-in user.

    CAccessControlFilter walks the rules in order and the first match decides;
    if none matches it runs the action, which is why the deny-all that ends
    most of these lists is what makes them exhaustive.

    CAccessRule::isActionMatched is `empty($this->actions) || in_array(...)`,
    so a rule with no actions - or with all of them commented out, which is
    common here - matches *every* action. Reading that as "nothing" is what
    made an earlier attempt at this report 59 denied actions, none of which
    were denied.

    A rule decided by a role or an expression cannot be read, so its actions
    are left out rather than guessed at: this list only ever holds actions
    refused outright.
    """
    rules = access_rules(src)
    if rules is None:
        return []

    out = []
    for m in re.finditer(r'public function action(\w+)\s*\(', src):
        act = m.group(1)[0].lower() + m.group(1)[1:]
        for kind, actions, users, undecidable in rules:
            if actions and act.lower() not in actions:
                continue
            if users is not None and not ({'*', '@'} & set(users)):
                continue
            if undecidable:
                break
            if kind == 'deny':
                out.append(act)
            break

    return out

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
    #
    # The class is kept, not stripped: `loadModel($model->item_id, 'Item')`
    # means load an Item, and without it the base method loads the
    # controller's own model by that id - a row that usually does not exist,
    # so the page answered 404 where Yii 1 answers 403. Only the controller's
    # own class is dropped, where the argument says nothing.
    body = re.sub(r"\$this->loadModel\s*\(\s*([^,]+?)\s*,\s*'(\w+)'\s*\)",
                  lambda m: ('$this->loadModel(%s)' % m.group(1)) if m.group(2) == model
                  else ('$this->loadModel(%s, %s::class)' % (m.group(1), m.group(2))),
                  body)

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
    # Yii 1's request getters. getQuery('term') is get('term') in Yii 2, and
    # leaving it produced "Calling unknown method: yii\\web\\Request::getQuery()"
    # on the three item-lookup endpoints the autocomplete fields call.
    body = re.sub(r"request\s*->\s*getQuery\s*\(", 'request->get(', body)
    body = re.sub(r"request\s*->\s*getParam\s*\(", 'request->get(', body)
    body = re.sub(r"request\s*->\s*getPost\s*\(", 'request->post(', body)
    # Yii 1's error handler hands the view an array; Yii 2's holds the
    # exception. site/error is the only place that reads it.
    body = re.sub(r'Yii::app\s*\(\s*\)\s*->\s*errorHandler\s*->\s*error',
                  'Ui::errorArray()', body)
    body = re.sub(r'Yii::app\s*\(\s*\)\s*->', 'Yii::$app->', body)
    body = re.sub(r'Yii::app\s*\(\s*\)', 'Yii::$app', body)

    # form input.
    #
    # Through $_GET and $_POST, not Yii::$app->request. yii\web\Request caches
    # the query and body params the first time they are read, and three admin
    # actions - MrnDetail's, MrsDetail's, PurchaseOrderDetail's - write the
    # selected parent id into $_GET and then read the model out of it. Against
    # the cached copy those writes are invisible, and the grid came back with
    # every row in the table where Yii 1 correctly shows none.
    #
    # Reading the superglobal is not a step back from the framework: Yii 2
    # merges the parsed route parameters into $_GET itself, in
    # Request::resolve(), before the action runs.
    #
    # The isset() tests are left exactly as Yii 1 writes them, for the same
    # reason.
    #
    # Any variable and any form name, not just $model and this controller's
    # own. ItemReturn's view action builds an $itemReturnItem and fills it
    # from $_GET['ItemReturnItem'], and a rule that only knew $model left that
    # one as Yii 1 code.
    body = re.sub(r"\$(\w+)\s*->\s*setAttributes\s*\(\s*\$_(GET|POST)\s*\[\s*'(\w+)'\s*\]\s*\)",
                  r"$\1->load($_\2, '\3')", body)

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

    # The search scenario, for any model the action builds - not only the
    # controller's own. ItemReturn's view action builds an ItemReturnItem for
    # the related grid, and leaving that one alone gave Yii 2 a string where it
    # expects a configuration array: "foreach() argument must be of type
    # array|object, string given", from inside Yii::configure().
    #
    # Restricted to real model classes. Yii 1's CModel takes the scenario as
    # its constructor argument, so for those a bare string is always one; for
    # anything else - new DateTime('now') - it is not.
    def scenario(m):
        cls, arg = m.group(1), m.group(2)
        if not os.path.exists(resolve_path(f'{ROOT}/protected/models/{cls}.php')):
            return m.group(0)
        return "new %s(['scenario' => '%s'])" % (cls, arg)

    body = re.sub(r"new\s+(\w+)\s*\(\s*'(\w+)'\s*\)", scenario, body)
    # unsetAttributes() cleared the defaults a new record starts with; a Yii 2
    # model built for a scenario has no defaults to clear.
    # Any variable, for the same reason: `$itemReturnItem->unsetAttributes()`
    # is a Yii 1 method that does not exist in Yii 2, and leaving it in is a
    # fatal on a page that otherwise works.
    body = re.sub(r"\s*\$\w+->unsetAttributes\s*\(\s*\)\s*;", '', body)

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

    # Yii::import() has no Yii 2 equivalent; classes are autoloaded.
    body = re.sub(r"(?m)^[ \t]*Yii::import\s*\([^;]*\);[ \t]*\n", '', body)
    body = re.sub(r"Yii::import\s*\([^;]*\);", '', body)

    # CDbCriteria in an action body. The same converter the models use - it was
    # only ever called from there, so a controller that built a query itself
    # kept its Yii 1 code and died on a class that does not exist in Yii 2.
    body = convert_criteria_blocks(body)

    # `X::model()->find*` - the Yii 1 way of reaching a finder, written both
    # tightly and with spaces.
    M = r"(\w+)::model\s*\(\s*\)\s*->\s*"
    body = re.sub(M + r"(?i:findByPk)\s*\(", lambda m: m.group(1) + '::findOne(', body)
    body = re.sub(M + r"(?i:findByAttributes)\s*\(", lambda m: m.group(1) + '::findOne(', body)
    body = re.sub(M + r"(?i:findAllByAttributes)\s*\(", lambda m: m.group(1) + '::findAll(', body)
    body = re.sub(M + r"findAll\s*\(\s*\)", lambda m: m.group(1) + '::find()->all()', body)

    # Yii 1's logger. Yii 2 splits the level into the method name, and
    # CVarDumper::dumpAsString is var_export.
    # The framework actions a controller declares in actions(). Yii 2 has
    # both, under different names, and left alone they are classes that do not
    # exist - site/contact died on the captcha.
    body = re.sub(r"'class'\s*=>\s*'CCaptchaAction'",
                  lambda m: "'class' => \\yii\\captcha\\CaptchaAction::class", body)
    body = re.sub(r"'class'\s*=>\s*'CViewAction'",
                  lambda m: "'class' => \\yii\\web\\ViewAction::class", body)

    body = dao_idioms(body)

    body = finder_idioms(body)
    body = dump_as_string(body)
    body = global_classes(body)
    # Only the level marker; the second argument is dump_as_string's job. This
    # rule predates it and was adding a second `, true`, so every log line in a
    # controller came out as var_export($x, true, true) - three arguments,
    # which is a fatal. It cost order, orderItem and purchaseOrderDetail their
    # admin grids.
    body = re.sub(r"(var_export\([^;]*?\))(\s*),(\s*)CLogger::LEVEL_\w+",
                  lambda m: m.group(1) + m.group(2) + ',' + m.group(3) + 'LEVEL', body)
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


def convert_criteria_blocks(src):
    """
    Run the model generator's CDbCriteria converter over every method body.

    It works on one method at a time - it has to, because it reads the whole
    query from declaration to fetch - so the source is split on method
    boundaries and each piece is offered to it. A piece it cannot convert comes
    back unchanged, and the leftover scan below reports it.
    """
    import port_model

    pieces = re.split(r'(?=\n[ \t]*(?:public|protected|private)?\s*'
                      r'(?:static\s+)?function\s+\w+\s*\()', src)
    out = []
    for piece in pieces:
        if 'CDbCriteria' in piece:
            converted, ok, _ = port_model.convert_criteria(piece)
            piece = converted if ok else piece
        out.append(piece)

    return ''.join(out)


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
    src = open(resolve_path(f'{ROOT}/protected/controllers/{model}Controller.php'),
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
    # Every way a controller can name a model: a static call, `new X`, a type
    # hint, instanceof. Matching only `X::` missed `new ItemExpireItem` and the
    # action died looking for it in app\controllers.
    # The port's own components, which are not models and so are not picked up
    # by the model scan below. The criteria converter emits Criteria::compare()
    # into controller bodies as readily as into models, and without the import
    # PHP looked for app\controllers\Criteria - freeItem/itemList died on it,
    # on a page the CRUD suite does not reach.
    for cls in ('Criteria', 'Gx', 'Access'):
        if re.search(r'\b' + cls + r'::', body) and 'use app\\components\\%s;' % cls not in header:
            header = header.replace('use app\\components\\Ui;',
                                    'use app\\components\\%s;\nuse app\\components\\Ui;' % cls, 1)

    named = set(re.findall(r'\b([A-Z]\w+)::', body))
    named |= set(re.findall(r'\bnew\s+([A-Z]\w+)\s*[(;]', body))
    named |= set(re.findall(r'\binstanceof\s+([A-Z]\w+)', body))
    # Spelled as the model file is, and deduplicated case-insensitively. This
    # application writes the same class both ways - B2BPurchaseBill:: in one
    # place and B2bPurchaseBill:: in another - and importing both is a fatal:
    # "Cannot use app\models\B2bPurchaseBill as B2bPurchaseBill because the
    # name is already in use". PHP resolves the reference either way once one
    # import is there, since class names are case-insensitive.
    canonical = {}
    for c in named:
        if c == model or c in ('Yii', 'Ui', 'Html', 'ActiveDataProvider', 'SORT_DESC'):
            continue
        base = resolve_path(f'{ROOT}/protected/models/_base/Base{c}.php')
        if os.path.exists(base):
            # Base<Name>.php -> <Name>, as the file spells it
            real = os.path.basename(base)[4:-4]
            canonical[real.lower()] = real
            continue
        # A model with no generated base: the form models, CFormModel in
        # Yii 1. ContactForm is one, and site/contact died looking for it in
        # app\controllers because the scan only knew about ActiveRecords.
        plain = resolve_path(f'{ROOT}/protected/models/{c}.php')
        if os.path.exists(plain):
            real = os.path.basename(plain)[:-4]
            canonical[real.lower()] = real
    others = sorted(canonical.values())
    keep = {c.lower(): c for c in others}
    # The controller's own model, but only if it has one. LoyaltyAdmin is a
    # controller with no model behind it, and importing app\models\LoyaltyAdmin
    # named a class that does not exist - harmless while nothing referenced it,
    # and a fatal the moment something did.
    if os.path.exists(resolve_path(f'{ROOT}/protected/models/{model}.php')):
        keep.setdefault(model.lower(), model)
    header = header.replace(
        'use app\\models\\' + model + ';',
        '\n'.join(['use app\\models\\' + c + ';' for c in sorted(keep.values())])
        if keep else '')

    # Yii 1's accessRules(), as a list the base controller enforces. The port
    # checks only that someone is signed in, which is what Yii 1's rules amount
    # to for most controllers - but not all: item/getDiffStocks is refused to
    # everybody there and was reachable here.
    denied = denied_actions(src)
    if denied:
        body = body.rstrip().rstrip('}').rstrip() + '''

	/**
	 * Actions Yii 1's accessRules() refuses to a signed-in user.
	 *
	 * Read from accessRules() when this file was generated, not enforced by
	 * duplicating the rules: only actions refused outright are listed, and a
	 * rule decided by a role or an expression is left out.
	 */
	public function deniedActions()
	{
		return [%s];
	}
}
''' % ', '.join("'%s'" % d for d in denied)

    # A name the API port already uses gets a suffixed controller class. The
    # URL is unchanged - Ui::toYii2Id() maps /v2/item/admin to item-ui/admin -
    # so only the class differs, and the verified API controller is untouched.
    API_NAMES = {'Emp', 'Customer', 'Item', 'Order', 'Loyalty', 'Tally', 'Site'}
    cls = model + ('Ui' if model in API_NAMES else '')
    body = re.sub(r'\bclass\s+' + model + r'Controller\b', 'class ' + cls + 'Controller', body)

    # Case-insensitively, like the read above. A controller generated under
    # the wrong spelling of its own name - loyaltyAdmin for LoyaltyAdmin -
    # wrote a second file beside the real one, and PSR-4 went on loading the
    # stale one: the port looked regenerated and was not.
    out = resolve_path(f'{ROOT}/app2/controllers/{cls}Controller.php')
    if os.path.basename(out) != f'{cls}Controller.php':
        cls = os.path.basename(out)[:-len('Controller.php')]

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
        # Keep what it produced. Two lines of php -l and no file to read
        # is not enough to fix the rule that produced it.
        open(out + '.bad', 'w', encoding='utf-8').write(header + body)
        print('REFUSED to write app2/controllers/%sController.php: it does not parse' % cls)
        print('  the output is at %s.bad' % out)
        for line in (check.stdout or check.stderr).splitlines()[:2]:
            if line.strip():
                print('  ' + line.strip())
        sys.exit(5)

    print('wrote app2/controllers/%sController.php' % cls)
    for x in sorted(set(warn)):
        print('  NOTE: ' + x)


main()
