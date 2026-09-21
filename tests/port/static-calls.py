#!/usr/bin/env python3
"""
Every static call the port makes has a method behind it.

Its companion, class-refs.py, asks whether a named class exists. This asks the
next question, which is the one that actually bit: the class was there and the
method was not.

`LoyaltyService::processOrderEarn()` is the case. The service was ported with
eight of its nine methods; the ninth was left out because its only caller,
Order::afterSave(), was not ported either. Both gaps were invisible for as
long as they were gaps together - and the moment the hook was put back, every
order the API saved answered 500.

So this reads no source for intent. For each `Class::method(` in the port it
resolves the class through the file's namespace and `use` statements, follows
`extends` and the traits the class pulls in, and asks whether the method is
there. A class whose ancestry leaves the port - anything under yii\\ - is
skipped rather than guessed at, so this reports only calls that must fatal.
"""
import os, re, sys

ROOT = '/root/pos/pos83/app2'


def strip(t):
    t = re.sub(r'/\*.*?\*/', '', t, flags=re.S)
    return re.sub(r'//[^\n]*', '', t)


def index():
    """class FQN -> its own method names, plus @extends/@trait markers."""
    defined = {}
    for dp, _, fs in os.walk(ROOT):
        if 'runtime' in dp:
            continue
        for f in fs:
            if not f.endswith('.php'):
                continue
            t = strip(open(os.path.join(dp, f), errors='replace').read())
            ns = re.search(r'^\s*namespace\s+([\w\\]+)\s*;', t, re.M)
            ns = ns.group(1) if ns else ''
            for m in re.finditer(r'^\s*(?:final\s+|abstract\s+)*(?:class|trait)\s+(\w+)'
                                 r'(?:\s+extends\s+([\w\\]+))?', t, re.M):
                fq = (ns + '\\' + m.group(1)) if ns else m.group(1)
                defined[fq] = {x.lower()
                               for x in re.findall(r'function\s+(\w+)\s*\(', t)}
                for u in re.findall(r'^\s{4,}use\s+(\w+)\s*;', t, re.M):
                    defined[fq].add('@trait:' + u)
                if m.group(2):
                    defined[fq].add('@extends:' + m.group(2))
    return defined


def reachable(defined, fq, seen=None):
    """(methods, left_the_port) - the second says the answer is incomplete."""
    seen = seen or set()
    if fq in seen or fq not in defined:
        return set(), fq not in defined
    seen.add(fq)
    out, unknown = set(), False
    for m in defined[fq]:
        if m.startswith('@extends:') or m.startswith('@trait:'):
            parent = m.split(':', 1)[1].rsplit('\\', 1)[-1]
            near = [k for k in defined if k.rsplit('\\', 1)[-1] == parent]
            if near:
                sub, u = reachable(defined, near[0], seen)
                out |= sub
                unknown = unknown or u
            else:
                unknown = True          # a Yii base class, not ours to judge
        else:
            out.add(m)
    return out, unknown


def main():
    defined = index()
    passed, bad = 0, set()

    for dp, _, fs in os.walk(ROOT):
        if 'runtime' in dp:
            continue
        for f in sorted(fs):
            if not f.endswith('.php'):
                continue
            path = os.path.join(dp, f)
            t = strip(open(path, errors='replace').read())
            ns = re.search(r'^\s*namespace\s+([\w\\]+)\s*;', t, re.M)
            if not ns:
                continue                # a view
            ns = ns.group(1)

            alias = {}
            for u in re.finditer(r'^\s*use\s+([\w\\]+)(?:\s+as\s+(\w+))?\s*;',
                                 t, re.M):
                alias[u.group(2) or u.group(1).rsplit('\\', 1)[-1]] = u.group(1)

            for m in re.finditer(r'\b([A-Z]\w*)::(\w+)\s*\(', t):
                cls, meth = m.group(1), m.group(2)
                if cls in ('self', 'static', 'parent', 'Yii'):
                    continue
                fq = alias.get(cls, ns + '\\' + cls)
                if fq not in defined:
                    continue            # class-refs.py answers for this one
                have, unknown = reachable(defined, fq)
                if unknown or meth.lower() in have or '__callstatic' in have:
                    passed += 1
                else:
                    bad.add((os.path.relpath(path, ROOT),
                             fq.rsplit('\\', 1)[-1], meth))

    for rel, cls, meth in sorted(bad):
        print('  %s calls %s::%s - the class is ported and the method is not'
              % (rel, cls, meth))
    print('  passed: %d   mismatched: %d' % (passed, len(bad)))
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main())
