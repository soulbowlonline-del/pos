#!/usr/bin/env python3
"""
Every class the port names can be found.

Yii 1 had no namespaces: `LoyaltyService::processOrderEarn()` meant the one
class of that name, wherever its file sat. The port puts models in
`app\\models` and components in `app\\components`, and an unqualified name
resolves against the file's own namespace - so that same line, carried into
`app\\models\\Order`, asks for `app\\models\\LoyaltyService`, which is not
anything.

PHP does not complain until the line runs. Order::processLoyaltyEarning() and
the two loyalty getters beside it carried that fault from the day the model
was written, and the suites stayed green the whole time, because nothing
called them: the hook that does was itself not ported. Porting the hook turned
three dead methods into a 500 on every order the API saved.

So the check is static, and it does not care whether anything calls the line.
It reads each file's namespace and `use` statements, resolves every class name
it sees the way PHP would, and asks whether a file exists to define it.
"""
import os, re, sys

ROOT = '/root/pos/pos83/app2'
VENDOR = '/root/pos/pos83/vendor'

# Names PHP or an extension defines, and the two dynamic ones.
BUILTIN = {
    'self', 'static', 'parent', 'Yii', 'Exception', 'Error', 'Throwable',
    'ErrorException', 'TypeError', 'ValueError', 'ArgumentCountError',
    'RuntimeException', 'LogicException', 'InvalidArgumentException',
    'OutOfRangeException', 'DomainException', 'LengthException',
    'UnexpectedValueException', 'DateTime', 'DateTimeImmutable',
    'DateInterval', 'DateTimeZone', 'PDO', 'PDOException', 'PDOStatement',
    'ArrayObject', 'ArrayAccess', 'Countable', 'Iterator', 'IteratorAggregate',
    'ArrayIterator', 'Traversable', 'JsonSerializable', 'Closure', 'Generator',
    'stdClass', 'SplFileInfo', 'SplStack', 'SplQueue', 'SplObjectStorage',
    'ZipArchive', 'DOMDocument', 'SimpleXMLElement', 'NumberFormatter',
    'IntlDateFormatter', 'Collator', 'ReflectionClass', 'ReflectionMethod',
    'Normalizer', 'finfo', 'CURLFile', 'Redis', 'Memcached', 'Serializable',
    'Stringable', 'WeakMap', 'SessionHandlerInterface',
}


def declared_classes():
    """Every class the port and its vendor tree define, as a set of FQNs."""
    found = set()
    for base in (ROOT, VENDOR):
        for dirpath, _, files in os.walk(base):
            for f in files:
                if not f.endswith('.php'):
                    continue
                path = os.path.join(dirpath, f)
                try:
                    head = open(path, errors='replace').read(60000)
                except OSError:
                    continue
                ns = re.search(r'^\s*namespace\s+([\w\\]+)\s*;', head, re.M)
                ns = ns.group(1) if ns else ''
                for m in re.finditer(r'^\s*(?:final\s+|abstract\s+)*'
                                     r'(?:class|interface|trait|enum)\s+(\w+)',
                                     head, re.M):
                    found.add((ns + '\\' + m.group(1)) if ns else m.group(1))
    return found


def strip_comments(t):
    t = re.sub(r'/\*.*?\*/', '', t, flags=re.S)
    return re.sub(r'//[^\n]*', '', t)


def main():
    known = declared_classes()
    passed, bad = 0, []
    unqualified = []

    for dirpath, _, files in os.walk(ROOT):
        if 'runtime' in dirpath or 'vendor' in dirpath or 'web/assets' in dirpath:
            continue
        for f in sorted(files):
            if not f.endswith('.php'):
                continue
            path = os.path.join(dirpath, f)
            text = strip_comments(open(path, errors='replace').read())

            m = re.search(r'^\s*namespace\s+([\w\\]+)\s*;', text, re.M)
            if not m:
                continue                      # a view; it has no namespace
            ns = m.group(1)

            alias = {}
            for u in re.finditer(r'^\s*use\s+([\w\\]+)(?:\s+as\s+(\w+))?\s*;',
                                 text, re.M):
                fq = u.group(1)
                alias[u.group(2) or fq.rsplit('\\', 1)[-1]] = fq

            names = set()
            for pat in (r'\b([A-Z]\w*)\s*::', r'\bnew\s+([A-Z]\w*)\s*\(',
                        r'\binstanceof\s+([A-Z]\w*)\b',
                        r'\bcatch\s*\(\s*\\?([A-Z]\w*)\b',
                        r'\bextends\s+([A-Z]\w*)\b',
                        r'\bimplements\s+([A-Z]\w*)\b'):
                names |= {x for x in re.findall(pat, text)}

            # A name written with a leading backslash is absolute and resolves
            # on its own; the patterns above only match the bare form.
            # PHP resolves an unqualified *class* name against the current
            # namespace and does not fall back to the global one, the way it
            # does for functions. So `PDO::PARAM_INT` inside app\models asks
            # for app\models\PDO and fatals. OrderItem::updateItemVelocity()
            # had exactly that, one line below a `\PDO::PARAM_INT` that was
            # written correctly, and it survived because nothing called it.
            for n in sorted(names & BUILTIN):
                if n in ('self', 'static', 'parent', 'Yii') or n in alias:
                    #  at the top of a namespaced file imports
                    # the global class, and the bare name is then correct.
                    continue
                if re.search(r'(?<![\\\w])%s\s*(?:::|\()' % n, text) or \
                        re.search(r'\bnew\s+%s\b' % n, text) or \
                        re.search(r'\bcatch\s*\(\s*%s\b' % n, text):
                    unqualified.append((os.path.relpath(path, ROOT), n,
                                        ns + '\\' + n))
                else:
                    passed += 1

            for n in sorted(names - BUILTIN):
                if re.search(r'\\%s\s*::' % n, text) and n not in alias:
                    continue                  # only ever seen fully qualified
                fq = alias.get(n, ns + '\\' + n)
                if fq in known or n in known:
                    passed += 1
                else:
                    bad.append((os.path.relpath(path, ROOT), n, fq))

    # Two different faults wear the same shape.
    #
    # If Yii 1 has no such class either, the line is dead on both stacks and
    # fails the same way on both - `Nozzle`, `Tank`, `Journey`, `MerchantStore`
    # and the rest belong to other applications this code was cut from, and
    # reproducing them is not this port's business. If Yii 1 does have the
    # class, the port has it too and is simply looking in the wrong namespace:
    # that is a port defect, and a page that reaches the line answers 500
    # where Yii 1 answers.
    # Three faults wear the same shape, and only two of them are this
    # port's.
    #
    #   misplaced  the port has the class, under another namespace or another
    #              capitalisation - Yii 1 forgave the case, PSR-4 does not.
    #              B2BPurchaseBill against app\models\B2bPurchaseBill.
    #   missing    Yii 1 has the class and the port does not, so the line runs
    #              there and fatals here.
    #   inherited  neither has it. Nozzle, Tank, Journey, MerchantStore and
    #              the rest belong to other applications this code was cut
    #              from; both stacks fail the same way and reproducing that is
    #              not this port's business.
    #
    # Only the port's own classes count as a match. A vendor class that
    # happens to share a short name - yii\db\Transaction against the
    # `Transaction` of some dispatch application - is a coincidence, not a
    # resolution.
    own = {}
    for fq in known:
        if fq.startswith('app\\'):
            own.setdefault(fq.rsplit('\\', 1)[-1].lower(), set()).add(fq)
    y1 = yii1_classes()

    # Nothing here is excused any more. UserIdentity used to be: the port did
    # not own login, BridgedUser only observed Yii 1's session, and a POST to
    # /v2/user/login was a fatal that no suite could reach. It is ported now,
    # and the entry is gone rather than kept as a comment, because an
    # exception list that outlives its reason is how a check stops checking.
    ALLOWED = set()

    misplaced, missing, inherited = [], [], []
    for rel, n, fq in bad:
        if (rel, n) in ALLOWED:
            passed += 1
            continue
        near = own.get(n.lower())
        if near:
            misplaced.append((rel, n, fq, near))
        elif n in y1:
            missing.append((rel, n, fq))
        else:
            inherited.append((rel, n))

    for rel, n, fq, near in misplaced:
        print('  %s names %s, resolved as %s - the class is %s'
              % (rel, n, fq, ' or '.join(sorted(near))))
    for rel, n, fq in missing:
        print('  %s names %s, which the Yii 1 tree has and the port does not'
              % (rel, n))
    for rel, n, fq in unqualified:
        print('  %s names %s without a leading backslash, so it resolves to'
              ' %s - PHP does not fall back to the global namespace for a'
              ' class' % (rel, n, fq))
    if inherited:
        print('  in neither tree, so dead on both stacks: %d - %s'
              % (len(inherited), ', '.join(sorted({n for _, n in inherited}))))
    print('  passed: %d   mismatched: %d'
          % (passed + len(inherited),
             len(misplaced) + len(missing) + len(unqualified)))
    return 1 if (misplaced or missing or unqualified) else 0


def yii1_classes():
    """Every class name the Yii 1 tree defines. Yii 1 has no namespaces."""
    found = set()
    for base in ('/root/pos/pos83/protected',):
        for dirpath, _, files in os.walk(base):
            if 'runtime' in dirpath:
                continue
            for f in files:
                if f.endswith('.php'):
                    found.add(f[:-4])
    return found


if __name__ == '__main__':
    sys.exit(main())
