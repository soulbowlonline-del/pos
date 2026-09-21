#!/usr/bin/env python3
"""
Every Yii 1 lifecycle hook has a counterpart on the port.

A hook is the one kind of method that nothing calls by name, so nothing
notices when it does not come across: the model still loads, the page still
renders, and the only sign is a row that was written on one stack and not the
other. Six were missing when this was written.

    OrderItem::afterSave      records the item's sales velocity
    Order::afterSave          credits the customer's loyalty points
    Order::beforeDelete       deletes the order's items
    OrderHold::beforeDelete   deletes the held order's items
    ItemDetail::beforeDelete  deletes fourteen tables' worth of detail rows
    MrsDetail::beforeSave     computes the AI requisition quantity

Three of those - the delete cascades - stand in for foreign keys the schema
does not declare, so the port was deleting a row and orphaning everything
that pointed at it.

The check is on the name only. Whether the body matches is what the write
sweep is for.
"""
import os, re, sys

Y1 = '/root/pos/pos83/protected/models'
Y2 = '/root/pos/pos83/app2/models'
HOOKS = ['beforeSave', 'afterSave', 'beforeDelete', 'afterDelete',
         'beforeValidate', 'afterValidate', 'afterFind', 'beforeFind']

# User::beforeDelete is not ported, and deliberately. It deletes through four
# classes - MerchantStore, Category, Product, PromotionalAdd - that are in
# neither tree: they belong to a different application this code was cut from.
# Yii 1 therefore fatals on any user delete rather than cascading, so there is
# no behaviour here to reproduce. See docs/live-bugs-found.md.
ALLOWED = {('User.php', 'beforeDelete')}


def hooks_in(path):
    if not os.path.exists(path):
        return None
    t = open(path, errors='replace').read()
    t = re.sub(r'/\*.*?\*/', '', t, flags=re.S)
    t = re.sub(r'//[^\n]*', '', t)
    return {h for h in HOOKS if re.search(r'function\s+%s\s*\(' % h, t)}


def main():
    passed, missing = 0, []
    for f in sorted(os.listdir(Y1)):
        if not f.endswith('.php'):
            continue
        a = hooks_in(os.path.join(Y1, f))
        b = hooks_in(os.path.join(Y2, f))
        if a is None or b is None:
            continue
        for h in sorted(a):
            if h in b or (f, h) in ALLOWED:
                passed += 1
            else:
                missing.append('%s::%s' % (f[:-4], h))

    for m in missing:
        print('  not on the port: %s' % m)
    print('  passed: %d   mismatched: %d' % (passed, len(missing)))
    return 1 if missing else 0


if __name__ == '__main__':
    sys.exit(main())
