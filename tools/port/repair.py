"""
Apply the shared Yii 1 idioms to the already-generated app2 files.

Regenerating does not reach these. port_model.merge() deliberately keeps the
methods an API-tracked model already has - that is what stops a web-UI port
from changing how the API validates and saves - so an idiom added to the
generator after a model was first written never lands in those methods.

The idioms are mechanical and keyed on Yii 1 spellings (`X::model()`,
`CDbCriteria`, `CJSON::`) that converted code does not contain, so running
them over generated output is idempotent by construction.
"""
import os, re, subprocess, sys
sys.path.insert(0, '/root/pos')
import port_model as pm
import importlib.util

# Read before port_controller is imported: loading it replaces sys.argv, and
# the flag was being read from the replacement, so --apply never took effect.
APPLY = '--apply' in sys.argv

spec = importlib.util.spec_from_file_location('pc', '/root/pos/port_controller.py')
pc = importlib.util.module_from_spec(spec)
sys.argv = ['port_controller.py']
try:
    spec.loader.exec_module(pc)
except (SystemExit, IndexError):
    pass

ROOT = '/root/pos/pos83/app2'

changed, failed, skipped = [], [], []
for dirpath, _, files in os.walk(ROOT):
    for f in sorted(files):
        if not f.endswith('.php'):
            continue
        path = os.path.join(dirpath, f)
        rel = path[len('/root/pos/pos83/'):]
        src = open(path, encoding='utf-8', errors='replace').read()

        out = src
        # `self::model()` inside a model is that model. The conversions read
        # the class out of the call, so without this they were handed "self".
        if dirpath.endswith('/models'):
            out = pm.normalise_self(out, f[:-4])
        if 'CDbCriteria' in out:
            try:
                out = pc.convert_criteria_blocks(out)
            except Exception as e:
                skipped.append((rel, 'criteria: %s' % e))
        out = pm.finder_idioms(out)
        out = pm.dao_idioms(out)
        if out == src:
            continue

        # Never write something that does not parse.
        tmp = '/tmp/repair_check.php'
        open(tmp, 'w', encoding='utf-8').write(out)
        lint = subprocess.run(['php', '-l', tmp], capture_output=True, text=True)
        if lint.returncode != 0:
            failed.append((rel, lint.stdout.strip().split('\n')[0][:110]))
            continue

        changed.append(rel)
        if APPLY:
            open(path, 'w', encoding='utf-8').write(out)

print('%s %d files' % ('repaired' if APPLY else 'would repair', len(changed)))
for r in changed:
    print('   ' + r)
if failed:
    print('\nREFUSED (the result would not parse):')
    for r, why in failed:
        print('   %-46s %s' % (r, why))
if skipped:
    print('\ncriteria conversion could not run:')
    for r, why in skipped:
        print('   %-46s %s' % (r, why))
