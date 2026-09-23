#!/usr/bin/env python3
"""
Every view the port renders is a file that exists.

app2/views/mail did not exist. Not a file of it missing - the directory was
never created, while Yii 1 has eight views there and the port kept ten live
renderPartial('/mail/...') calls. Each one raised ViewNotFoundException.

That alone would have been a 500 and easy to find. What made it expensive is
where the render sits: MrsDetailController::actionAjaxupdate builds the mail
body inside the transaction that saves an MRS, under a catch(\\Exception) that
rolls back and says nothing. So the MRS, its details, the MRN and its lines
were discarded and the browser got 200 with an empty body - which is exactly
what it gets when the save succeeds. Nine other call sites failed the same
silent way: password recovery, the two customer emails, raising an MRS, the
MRN report, and three purchase-order mails.

No suite saw any of it. pmui and the page sweeps render pages and compare
them, and none of these views is reachable from a page. class-refs.py asks
the same shape of question about classes, but only of .php files under
app2 that declare a namespace - it does not resolve a view name to a file.

So this is the view half of class-refs: static, indifferent to whether
anything calls the line, and it reports a name that resolves to no file.

A name the port cannot resolve is reported against the Yii 1 tree as well,
because the two cases need different work. If Yii 1 has the view, the port
dropped it and the feature is broken here and works there. If neither tree
has it, the call was already dead in Yii 1 and the port only copied it - the
stacks agree, and it is not this port's bug to fix.
"""
import os
import re
import sys

REPO = os.environ.get('POS_REPO', '/root/pos/pos83')
PORT = os.path.join(REPO, 'app2')
YII1 = os.path.join(REPO, 'protected')

# $this->render('x'), renderPartial, renderAjax - and renderFile, which takes
# a path rather than a view name and so is resolved differently below.
CALL = re.compile(
    r'->\s*(render|renderPartial|renderAjax|renderFile)\s*\(\s*'
    r'(?:(?P<q>[\'"])(?P<name>[^\'"]*)(?P=q)|(?P<dyn>[\$A-Za-z_]))'
)


def controller_view_dir(filename):
    """
    The directory a controller's relative view names resolve against.

    BaseUiController::getViewPath() is the module's view path plus
    Ui::toYii1Id($this->id), which strips a trailing '-ui' and camelCases what
    is left - so CustomerUiController renders out of views/customer, not
    views/customer-ui, and the ported views sit where the originals do.
    """
    name = filename[:-len('Controller.php')]
    if name.endswith('Ui'):
        name = name[:-2]
    return name[:1].lower() + name[1:]


def resolve(name, from_file, ctx_dir):
    """
    Where Yii 2's View::findViewFile() would look, as a path under app2.

    Returns None for a name this check cannot follow - an alias outside the
    application, or a name built at runtime.
    """
    if name.startswith('@'):
        if name.startswith('@app/'):
            return os.path.join(PORT, name[len('@app/'):]) + '.php'
        return None                                   # some other alias
    if name.startswith('//'):
        return os.path.join(PORT, 'views', name.lstrip('/')) + '.php'
    if name.startswith('/'):
        # the module's view path; this application has one module, the app
        return os.path.join(PORT, 'views', name.lstrip('/')) + '.php'
    if ctx_dir is None:
        return None
    return os.path.join(ctx_dir, name) + '.php'


def yii1_counterpart(port_path):
    """The same relative path under protected/, which is where it came from."""
    rel = os.path.relpath(port_path, PORT)
    return os.path.join(YII1, rel)


def blank_comments(src):
    """
    The same source with comments blanked out, character for character.

    Two of the mail renders are commented out - ItemReturnItemController and
    MrnController both keep a dead copy - and a commented call cannot raise
    anything. Counting them would put two entries in a report whose whole
    value is that every line in it is a real fault.

    Offsets are preserved so line numbers still come out right, and quoted
    strings are stepped over so that '//layouts/main' and 'http://...' are not
    mistaken for the start of a comment.
    """
    out = list(src)
    i, n = 0, len(src)
    while i < n:
        c = src[i]
        if c in ('"', "'"):
            quote, i = c, i + 1
            while i < n:
                if src[i] == '\\':
                    i += 2
                    continue
                if src[i] == quote:
                    i += 1
                    break
                i += 1
            continue
        if c == '/' and i + 1 < n and src[i + 1] == '/':
            while i < n and src[i] != '\n':
                out[i] = ' '
                i += 1
            continue
        if c == '#':
            while i < n and src[i] != '\n':
                out[i] = ' '
                i += 1
            continue
        if c == '/' and i + 1 < n and src[i + 1] == '*':
            while i < n and not (src[i] == '*' and i + 1 < n and src[i + 1] == '/'):
                if src[i] != '\n':
                    out[i] = ' '
                i += 1
            for _ in range(2):
                if i < n:
                    out[i] = ' '
                    i += 1
            continue
        i += 1
    return ''.join(out)


def php_files(root):
    for dirpath, dirnames, files in os.walk(root):
        dirnames[:] = [d for d in dirnames if d not in ('runtime', 'assets')]
        for f in sorted(files):
            if f.endswith('.php'):
                yield os.path.join(dirpath, f)


def main():
    missing, dead, skipped = [], [], []
    checked = 0

    targets = []
    ctrl_dir = os.path.join(PORT, 'controllers')
    if os.path.isdir(ctrl_dir):
        for f in sorted(os.listdir(ctrl_dir)):
            if f.endswith('Controller.php'):
                targets.append((os.path.join(ctrl_dir, f),
                                os.path.join(PORT, 'views', controller_view_dir(f))))
    # A view rendering another view: a relative name resolves against the
    # directory of the view doing the rendering, not the controller's.
    for path in php_files(os.path.join(PORT, 'views')):
        targets.append((path, os.path.dirname(path)))

    for path, ctx_dir in targets:
        rel = os.path.relpath(path, REPO)
        src = blank_comments(open(path, encoding='utf-8', errors='replace').read())
        for m in CALL.finditer(src):
            line = src.count('\n', 0, m.start()) + 1
            if m.group('dyn'):
                skipped.append((rel, line, m.group(1)))
                continue
            name = m.group('name')
            if not name:
                skipped.append((rel, line, m.group(1)))
                continue
            # A quoted fragment glued to a variable - renderPartial('/' . $view
            # . '/_list') - is a runtime name, and the literal part of it is
            # not a view. Without this the leading '/' reads as the whole name.
            if re.match(r'\s*\.', src[m.end():m.end() + 16]):
                skipped.append((rel, line, m.group(1)))
                continue
            if m.group(1) == 'renderFile':
                resolved = (os.path.join(PORT, name[len('@app/'):]) + '.php'
                            if name.startswith('@app/') else None)
                if resolved is None:
                    skipped.append((rel, line, 'renderFile'))
                    continue
            else:
                resolved = resolve(name, path, ctx_dir)
            if resolved is None:
                skipped.append((rel, line, m.group(1)))
                continue
            checked += 1
            if os.path.isfile(resolved):
                continue
            where = os.path.relpath(resolved, REPO)
            if os.path.isfile(yii1_counterpart(resolved)):
                missing.append((rel, line, name, where))
            else:
                dead.append((rel, line, name, where))

    if missing:
        print('  the port renders a view it does not have, and Yii 1 does:')
        for rel, line, name, where in missing:
            print('    %s:%d  %-34s -> %s' % (rel, line, name, where))
    if dead:
        print('  in neither tree, so dead on both stacks: %d' % len(dead))
        for rel, line, name, where in dead:
            print('    %s:%d  %-34s -> %s' % (rel, line, name, where))
    if skipped:
        print('  not checked, the name is built at runtime or is an outside '
              'alias: %d' % len(skipped))

    print('  passed: %d   mismatched: %d' % (checked - len(missing), len(missing)))
    return 1 if missing else 0


if __name__ == '__main__':
    sys.exit(main())
