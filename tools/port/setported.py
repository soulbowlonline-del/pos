"""
Set Ui::PORTED to exactly the controllers named on the command line.

It REPLACES the list; it does not add to it. batch_port.py wants that - it
routes one controller at a time while it compares - but used by hand it is a
foot-gun: `setported.py loyaltyAdmin` dropped 55 controllers back to Yii 1,
and the next comparison passed because both stacks were then serving Yii 1.
Pass --add to append instead.
"""
import re, sys
p = '/root/pos/pos83/app2/components/Ui.php'
s = open(p, encoding='utf-8').read()
names = [a for a in sys.argv[1:] if not a.startswith('--')]
if '--add' in sys.argv:
    have = re.findall(r"'([^']+)'", re.search(r'PORTED = \[(.*?)\];', s, re.S).group(1))
    names = have + [n for n in names if n not in have]
block = '    public const PORTED = [\n' + ''.join("        '%s',\n" % n for n in names) + '    ];'
s = re.sub(r'    public const PORTED = \[.*?\];', lambda m: block, s, flags=re.S)
open(p, 'w', encoding='utf-8').write(s)
print('%d ported' % len(names))
