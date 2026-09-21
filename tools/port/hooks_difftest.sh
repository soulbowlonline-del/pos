#!/bin/bash
# Yii 1's lifecycle hooks all have a counterpart on the port. See
# pos83/tests/port/hook-parity.py for why this needs its own check.
exec python3 /root/pos/pos83/tests/port/hook-parity.py
