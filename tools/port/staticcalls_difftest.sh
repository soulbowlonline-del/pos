#!/bin/bash
# Every static call has a method behind it. See pos83/tests/port/static-calls.py -
# LoyaltyService was ported without processOrderEarn, and nothing called it.
exec python3 /root/pos/pos83/tests/port/static-calls.py
