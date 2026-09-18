import re, sys
p = '/root/pos/pos83/app2/components/Ui.php'
s = open(p, encoding='utf-8').read()
block = '    public const PORTED = [\n' + ''.join("        '%s',\n" % n for n in sys.argv[1:]) + '    ];'
s = re.sub(r'    public const PORTED = \[.*?\];', lambda m: block, s, flags=re.S)
open(p, 'w', encoding='utf-8').write(s)
print('PORTED = ' + ', '.join(sys.argv[1:]))
