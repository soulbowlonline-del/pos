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
import re, sys, os

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
    body = body.replace("Yii::app()->getRequest()->getIsPostRequest()", 'Yii::$app->request->isPost')
    body = body.replace("Yii::app()->getRequest()->getIsAjaxRequest()", 'Yii::$app->request->isAjax')
    body = body.replace("Yii::app()->request->isAjaxRequest", 'Yii::$app->request->isAjax')
    body = body.replace("Yii::app()->user->id", 'Yii::$app->user->id')
    body = body.replace("Yii::app()->end()", 'Yii::$app->end()')

    # form input
    body = re.sub(r"isset\(\s*\$_POST\['" + model + r"'\]\s*\)",
                  "Yii::$app->request->post('" + model + "') !== null", body)
    body = re.sub(r"isset\(\s*\$_GET\['" + model + r"'\]\s*\)",
                  "Yii::$app->request->get('" + model + "') !== null", body)
    body = re.sub(r"\$model->setAttributes\(\s*\$_POST\['" + model + r"'\]\s*\)",
                  "$model->load(Yii::$app->request->post())", body)
    body = re.sub(r"\$model->setAttributes\(\s*\$_GET\['" + model + r"'\]\s*\)",
                  "$model->load(Yii::$app->request->queryParams)", body)

    # data provider
    body = re.sub(r"new\s+CActiveDataProvider\s*\(\s*'" + model + r"'\s*\)",
                  ("new ActiveDataProvider(['query' => " + model + "::find(),\n"
                   "            // GxActiveRecord::defaultScope() orders every model by\n"
                   "            // id DESC, and Yii 1 paginates 10 rows at a time.\n"
                   "            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],\n"
                   "            'pagination' => ['pageSize' => Ui::PAGE_SIZE]])"), body)

    # the search scenario
    body = re.sub(r"new\s+" + model + r"\s*\(\s*'search'\s*\)",
                  "new " + model + "(['scenario' => 'search'])", body)
    # unsetAttributes() cleared the defaults a new record starts with; a Yii 2
    # model built for a scenario has no defaults to clear.
    body = re.sub(r"\s*\$model->unsetAttributes\s*\(\s*\)\s*;", '', body)

    # exceptions
    body = re.sub(r"throw\s+new\s+CHttpException\(\s*400\s*,\s*([^)]+)\)",
                  r'throw new BadRequestHttpException(\1)', body)
    body = re.sub(r"throw\s+new\s+CHttpException\(\s*404\s*,\s*([^)]+)\)",
                  r'throw new NotFoundHttpException(\1)', body)
    body = re.sub(r"throw\s+new\s+CHttpException\(\s*403\s*,\s*([^)]+)\)",
                  r'throw new ForbiddenHttpException(\1)', body)

    # Yii::t with no translations configured returns the message unchanged
    body = re.sub(r"Yii::t\(\s*'[^']*'\s*,\s*('(?:[^'\\]|\\.)*')\s*\)", r'\1', body)

    # render/redirect return a response in Yii 2
    body = re.sub(r'(?m)^(\s*)\$this->render\(', r'\1return $this->render(', body)
    body = re.sub(r'(?m)^(\s*)\$this->renderPartial\(', r'\1return $this->renderPartial(', body)
    body = re.sub(r'(?m)^(\s*)\$this->redirect\(', r'\1return $this->redirect(', body)

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

    for leftover in re.findall(r'Yii::app\(\)[->\w]*', body):
        warn.append('unconverted: ' + leftover)
    # strip comments before scanning, or the translator's own explanatory
    # comments get reported as unconverted Yii 1 code
    scan = re.sub(r'/\*.*?\*/', '', body, flags=re.S)
    scan = re.sub(r'//[^\n]*', '', scan)
    for leftover in set(re.findall(r'\b(C[A-Z]\w+)\b', scan)):
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
    out = f'{ROOT}/app2/controllers/{model}Controller.php'
    open(out, 'w', encoding='utf-8').write(header + body)
    print('wrote app2/controllers/%sController.php' % model)
    for x in sorted(set(warn)):
        print('  NOTE: ' + x)


main()
