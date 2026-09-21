#!/usr/bin/env python3
"""
The grids' search filters, submitted, on both stacks.

pmui_difftest renders every listing, every detail view and every create and
update form for all 59 ported controllers, and compares what comes back. It
never submits any of them. So a filter that returns nothing, or everything,
or throws, looks exactly like a filter that works - the page rendered, and
that is all the suite asked.

This takes each admin grid, reads the filter inputs the page itself declares,
picks a value out of a row the grid is already showing, and asks both stacks
for the filtered grid. Read-only: filters are GET parameters.

Reported per controller as the number of data rows each stack returns, which
is the thing a person notices when a search "does not work".
"""
import re, subprocess, sys, os, json, urllib.parse

B = 'http://127.0.0.1:8084'
JAR = '/tmp/crud-jar.txt'
REPO = '/root/pos/pos83'


def sh(*args):
    return subprocess.run(args, capture_output=True, text=True).stdout


def get(path, stack):
    url = '%s/%s%s' % (B, 'v2/' if stack == 'port' else '', path)
    return sh('curl', '-sS', '-b', JAR, '--max-time', '180', url)


def ported():
    s = open(REPO + '/app2/components/Ui.php', errors='replace').read()
    names = re.findall(r"'([^']+)'",
                       re.search(r'PORTED = \[(.*?)\];', s, re.S).group(1))
    out = []
    for c in names:
        model = c[0].upper() + c[1:]
        if os.path.exists('%s/protected/models/_base/Base%s.php' % (REPO, model)):
            out.append((c, model))
    return out


def rows(html):
    """Data rows, counted the way each framework marks them."""
    if 'data-key=' in html:
        return len(re.findall(r'<tr[^>]*data-key=', html))
    return len(re.findall(r'<tr[^>]*class="(?:odd|even)"', html))


def filters(html):
    """
    (column index, input name) for the filter row the grid declares.

    The index matters. A filter belongs to the column it sits under, and the
    value to search for has to come from that same column - the first version
    of this took the first filter and the first non-empty cell, which meant
    asking an `id` column for a customer's name. Both stacks answered nothing,
    the two nothings matched, and thirty controllers reported "same" without
    either of them having searched for anything.
    """
    m = re.search(r'class="filters"(.*?)</tr>', html, re.S)
    if not m:
        return []
    out = []
    for i, td in enumerate(re.findall(r'<td[^>]*>(.*?)</td>', m.group(1), re.S)):
        n = re.search(r'<(?:input|select)[^>]*name="([^"]+\[[^\]]+\])"', td)
        if n:
            out.append((i, n.group(1)))
    return out


def first_row_cells(html):
    """The first data row's cells, by column index."""
    m = re.search(r'<tr[^>]*(?:data-key=|class="(?:odd|even)")[^>]*>(.*?)</tr>',
                  html, re.S)
    if not m:
        return []
    return [' '.join(re.sub(r'<[^>]+>', ' ', td).split())
            for td in re.findall(r'<td[^>]*>(.*?)</td>', m.group(1), re.S)]


def pick(html):
    """A filter and a value from its own column, taken off the first row."""
    cells = first_row_cells(html)
    for i, name in filters(html):
        if i < len(cells):
            v = cells[i]
            if v and v != '&nbsp;' and len(v) >= 2 and '<' not in v:
                return name, v
    return None, None


def main():
    results = []
    for ctrl, model in ported():
        base = get('%s/admin' % ctrl, 'yii1')
        if 'LoginForm' in base or not base.strip():
            print('  %-24s SESSION LAPSED - stopping' % ctrl)
            break
        name, value = pick(base)
        if not name:
            results.append((ctrl, 'no filter row, or no rows to take a value from',
                            '', '', ''))
            continue
        q = '%s/admin?%s' % (ctrl, urllib.parse.urlencode({name: value}))
        a, b = get(q, 'yii1'), get(q, 'port')
        ra, rb = rows(a), rows(b)
        verdict = 'same' if ra == rb else 'DIFFERS'
        results.append((ctrl, name.split('[')[-1].rstrip(']'), value[:18], ra, rb, verdict))

    print('  %-24s %-18s %-20s %5s %5s  %s' %
          ('CONTROLLER', 'FILTER', 'VALUE', 'Y1', 'PORT', ''))
    bad = 0
    for r in results:
        if len(r) == 5:
            print('  %-24s %s' % (r[0], r[1]))
            continue
        ctrl, f, v, ra, rb, verdict = r
        if verdict == 'DIFFERS':
            bad += 1
        print('  %-24s %-18s %-20s %5s %5s  %s' % (ctrl, f, v, ra, rb, verdict))
    print('\n  controllers compared: %d   filters differing: %d' %
          (len([r for r in results if len(r) == 6]), bad))
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main())
