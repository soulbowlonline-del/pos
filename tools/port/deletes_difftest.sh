#!/bin/bash
# Every DELETE in the harness is scoped to something the harness created.
# See pos83/tests/port/delete-scope.py - one that was not took 601 credit notes,
# twice, because the predicate was written in two files and only one was fixed.
exec python3 /root/pos/pos83/tests/port/delete-scope.py
