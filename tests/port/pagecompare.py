#!/usr/bin/env python3
"""
Compare a controller's pages by what they say, for controllers with no model.

The UI suite compares six page types built around a model: an admin grid, a
detail view, a create and update form. `site` and `loyaltyAdmin` have no model,
so none of those exist and none of that applies - but their pages still have to
say the same thing on both stacks.

So each action that a read-only GET can reach is fetched from Yii 1 and from
the port, reduced to its visible text, and compared. Markup is dropped, because
two frameworks never emit the same markup; runs of whitespace are collapsed;
what is left is what a reader sees.

    pagecompare.py <controller> <action> [<action> ...]

Nothing is reported as passing if both sides came back empty: that is the
failure mode the whole suite exists to avoid, and it is counted separately.
"""
import os
import re
import subprocess
import sys

BASE = 'http://127.0.0.1:8084'
COOKIE = os.environ.get('COOKIE_FILE', '/tmp/uic.txt')

FAIL, NOTHING = [], []


def fetch(url):
    r = subprocess.run(['curl', '-s', '-b', COOKIE, '--max-time', '90',
                        '-w', '\n%{http_code}', url], capture_output=True, text=True)
    text = r.stdout
    return text.rsplit('\n', 1)[-1].strip(), text.rsplit('\n', 1)[0]


def visible(html):
    """What a reader sees: no markup, no script, no collapsed whitespace."""
    html = re.sub(r'(?is)<(script|style)\b.*?</\1>', ' ', html)
    html = re.sub(r'(?s)<!--.*?-->', ' ', html)
    # CGridView writes the data keys into a hidden <div class="keys"> for its
    # own JavaScript to read. It is display:none, so no reader sees it, and
    # the port does not ship that JavaScript - nothing on either stack reads
    # it here. Named rather than dropping every display:none element, because
    # the port deliberately reproduces one of those: CActiveForm's hidden
    # error-summary placeholder, which a blanket rule would stop checking.
    html = re.sub(r'(?is)<div[^>]*class="keys".*?</div>', ' ', html)
    html = re.sub(r'(?s)<[^>]+>', ' ', html)
    html = re.sub(r'&nbsp;?', ' ', html)
    # A csrf token and the session id change every load and are not content.
    html = re.sub(r'\b[0-9a-f]{32,40}\b', '', html)
    return re.sub(r'\s+', ' ', html).strip()


def main():
    ctrl = sys.argv[1]
    actions = sys.argv[2:]
    for action in actions:
        name = '%s/%s' % (ctrl, action)
        c1, b1 = fetch('%s/%s' % (BASE, name))
        c2, b2 = fetch('%s/v2/%s' % (BASE, name))

        if c1 != c2:
            FAIL.append(name)
            print('  FAIL  %-28s yii1 answered %s, yii2 answered %s' % (action, c1, c2))
            continue
        if c1 != '200':
            print('  ok    %-28s (both %s)' % (action, c1))
            continue

        t1, t2 = visible(b1), visible(b2)
        if not t1 and not t2:
            NOTHING.append(name)
            print('  none  %-28s both pages are empty - nothing compared' % action)
            continue
        if t1 == t2:
            print('  ok    %-28s (%d characters)' % (action, len(t1)))
            continue

        FAIL.append(name)
        print('  FAIL  %s' % action)
        for i in range(min(len(t1), len(t2))):
            if t1[i] != t2[i]:
                lo = max(0, i - 60)
                print('        first difference at character %d:' % i)
                print('          yii1: ...%s' % t1[lo:i + 60])
                print('          yii2: ...%s' % t2[lo:i + 60])
                break
        else:
            print('        one is a prefix of the other: yii1 %d chars, yii2 %d'
                  % (len(t1), len(t2)))

    print()
    if NOTHING:
        print('NOTHING COMPARED %d: %s' % (len(NOTHING), ', '.join(NOTHING)))
    if FAIL:
        print('FAILED %d: %s' % (len(FAIL), ', '.join(FAIL)))
        sys.exit(1)
    print('all %s pages match' % ctrl)


main()
