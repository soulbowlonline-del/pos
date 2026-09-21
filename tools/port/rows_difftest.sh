#!/bin/bash
# No table has lost a row since the 5.6 database was copied. See
# pos83/tests/port/baseline-rows.py - a teardown took 601 real credit notes.
exec python3 /root/pos/pos83/tests/port/baseline-rows.py
