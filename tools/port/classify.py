#!/usr/bin/env python3
"""
Where each listing gets its order from - checking every place one can live.

Three earlier attempts at this got it wrong by looking in only one place:
first defaultScope(), then the provider's sort. A search() can also set
$criteria->order directly, and three of the models called "unordered" do
exactly that.
"""
import re, os
ROOT = '/root/pos/pos83'
MODELS = ['B2bPurchaseBill', 'Customer', 'Item', 'ItemDetail', 'ItemExpire',
          'ItemReturn', 'ItemReturnItem', 'ItemTax', 'MrnDetail', 'MrsDetail',
          'Order', 'OrderItem', 'OrderRefund', 'OrderRefundItem',
          'PurchaseBillDetail', 'PurchaseOrderDetail', 'StockAdjustLog', 'Tax']


def block(path, name):
    if not os.path.exists(path):
        return None
    src = open(path, encoding='utf-8', errors='replace').read()
    m = re.search(r'function\s+' + name + r'\s*\([^)]*\)\s*\{', src)
    if not m:
        return None
    i, depth = m.end(), 1
    while i < len(src) and depth:
        if src[i] == '{':
            depth += 1
        elif src[i] == '}':
            depth -= 1
        i += 1
    return src[m.end():i - 1]


def clean(s):
    s = re.sub(r'//[^\n]*', '', s or '')
    return re.sub(r'/\*.*?\*/', '', s, flags=re.S)


for mdl in MODELS:
    concrete = f'{ROOT}/protected/models/{mdl}.php'
    base = f'{ROOT}/protected/models/_base/Base{mdl}.php'

    search = clean(block(concrete, 'search') or block(base, 'search'))
    scope = clean(block(concrete, 'defaultScope'))
    if scope is None or scope == '':
        scope = clean(block(base, 'defaultScope'))

    sources = []
    m = re.search(r"\$criteria\s*->\s*order\s*=\s*\(?\s*'([^']+)'", search)
    if m:
        sources.append(('criteria->order', m.group(1)))
    m = re.search(r"'defaultOrder'\s*=>\s*'([^']+)'", search)
    if m:
        sources.append(('provider sort', m.group(1)))
    if scope:
        m = re.search(r"'order'\s*=>\s*'([^']+)'", scope)
        if m:
            sources.append(('defaultScope', m.group(1)))
        else:
            sources.append(('defaultScope', '(empty - overrides the inherited id DESC)'))
    else:
        sources.append(('defaultScope', 'inherited id DESC'))

    # An explicit order in search(), or a defaultScope that actually names one.
    # The scope's *description* must not be searched for 'DESC' - the text
    # "(empty - overrides the inherited id DESC)" contains it, which reported
    # three unordered models as ordered.
    ordered = any(k in ('criteria->order', 'provider sort') for k, _ in sources)
    if not ordered:
        ordered = any(k == 'defaultScope' and not v.startswith('(empty')
                      for k, v in sources)
    why = ['%s: %s' % (k, v) for k, v in sources]
    print('%-22s %-9s %s' % (mdl, 'ORDERED' if ordered else 'UNORDERED', '; '.join(why)))
