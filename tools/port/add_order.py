#!/usr/bin/env python3
"""
Gives the eight genuinely unordered listings an explicit `id DESC`.

Approved by the owner. These eight grids have no ORDER BY at all - their model
overrides GxActiveRecord::defaultScope() with an empty array and their search()
names no sort - so which rows appear on page one is decided by the query plan.
That is why they could not be compared between the two stacks.

The order is added to the *listings* only: the data provider behind the admin
grid and the one behind index. Not to defaultScope(), which would apply to
every query on the model including the ones the API makes.

`id DESC` is the order every other listing in this application already has,
inherited from defaultScope(), and it is what the ten listings that do name a
sort have chosen. So this makes the eight consistent with the rest rather than
introducing a new convention.

Applied to Yii 1. The Yii 2 port reads the sort out of search(), so it follows
automatically - which is the point: the two stacks stay in step.
"""
import re, os, sys

ROOT = '/root/pos/pos83'
MODELS = ['Customer', 'ItemReturn', 'ItemReturnItem', 'MrnDetail', 'MrsDetail',
          'OrderRefund', 'OrderRefundItem', 'PurchaseOrderDetail']

NOTE = ("\t\t\t// Added deliberately: without an ORDER BY, which ten rows this\n"
        "\t\t\t// grid shows is decided by the query plan. id DESC is the order\n"
        "\t\t\t// every other listing here already has.\n")

changed, skipped = [], []

for model in MODELS:
    path = None
    for p in (f'{ROOT}/protected/models/{model}.php',
              f'{ROOT}/protected/models/_base/Base{model}.php'):
        if os.path.exists(p) and 'function search' in open(p, encoding='utf-8', errors='replace').read():
            path = p
            break
    if path is None:
        skipped.append((model, 'no search() found'))
        continue

    src = open(path, encoding='utf-8', errors='replace').read()
    if "'defaultOrder'" in src:
        skipped.append((model, 'already names a sort'))
        continue

    m = re.search(r"(return new CActiveDataProvider\s*\(\s*\$this\s*,\s*array\s*\(\s*\n?\s*'criteria'\s*=>\s*\$criteria\s*,?)", src)
    if not m:
        skipped.append((model, 'the provider is not built the usual way'))
        continue

    insert = m.group(1).rstrip(',') + ",\n" + NOTE + "\t\t\t'sort' => array('defaultOrder' => 'id DESC'),"
    src = src[:m.start(1)] + insert + src[m.end(1):]
    open(path, 'w', encoding='utf-8').write(src)
    changed.append((model, os.path.relpath(path, ROOT)))

# the index action builds its own provider
for model in MODELS:
    path = f'{ROOT}/protected/controllers/{model}Controller.php'
    if not os.path.exists(path):
        skipped.append((model, 'no controller'))
        continue
    src = open(path, encoding='utf-8', errors='replace').read()
    pattern = r"new\s+CActiveDataProvider\s*\(\s*'" + model + r"'\s*\)"
    if not re.search(pattern, src):
        continue
    src = re.sub(pattern,
                 lambda m: ("new CActiveDataProvider('%s', array(\n"
                            "\t\t\t\t// see the note in search(): this listing has no other order\n"
                            "\t\t\t\t'sort' => array('defaultOrder' => 'id DESC'),\n"
                            "\t\t))" % model), src)
    open(path, 'w', encoding='utf-8').write(src)
    changed.append((model, 'controllers/%sController.php (index)' % model))

print('ordered %d listings:' % len(changed))
for model, where in changed:
    print('  %-22s %s' % (model, where))
if skipped:
    print('\nnot changed:')
    for model, why in skipped:
        print('  %-22s %s' % (model, why))
