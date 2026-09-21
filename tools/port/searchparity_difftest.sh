#!/bin/bash
# Every attribute Yii 1's search() filters on, the port filters on too. See
# pos83/tests/port/search-parity.py - the item master's search box narrowed
# nothing, and no suite could see it because a capped row count hid the total.
exec python3 /root/pos/pos83/tests/port/search-parity.py
