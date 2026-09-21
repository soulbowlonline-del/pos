#!/bin/bash
# Every API action answers at the URL Yii 1 gives it, which is the URL the
# .NET application and the Android app send. See pos83/tests/port/api-routes.py -
# the port answered only hyphenated action ids, so every multi-word API
# endpoint was a 404 for both clients while every suite stayed green.
exec python3 /root/pos/pos83/tests/port/api-routes.py
