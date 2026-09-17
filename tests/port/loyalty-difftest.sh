#!/bin/bash
# Differential test for the loyalty write paths.
#
# Each case is run twice from an identical fixture - once against Yii 1
# (/api/loyalty/...) and once against Yii 2 (/v2/api/loyalty/...) - and both the
# JSON response and the resulting database state are compared. Auto-increment
# ids are normalised, since they legitimately differ between the two runs.

BASE=http://127.0.0.1:8084
CUST=9990001
ORD=9990001
PASS=0; FAIL=0

mysqlq() { docker exec -i pos-mysql-8 sh -c "mysql -uroot -p\$MYSQL_ROOT_PASSWORD -N \$MYSQL_DATABASE" 2>/dev/null; }

reset_fixture() {
  cat <<SQL | mysqlq
DELETE FROM tbl_loyalty_transactions WHERE customer_id=$CUST;
-- Upsert rather than UPDATE: an UPDATE silently affects zero rows when the
-- loyalty row is absent, and the endpoint then creates a fresh zeroed one -
-- so the suite would run against 0 points instead of 1000 and report a
-- mismatch that looks like a port defect.
DELETE FROM tbl_customer_loyalty WHERE customer_id=$CUST;
INSERT INTO tbl_customer_loyalty (customer_id, total_points, lifetime_earned, lifetime_redeemed)
VALUES ($CUST, 1000, 1000, 0);
SQL
}

capture_state() {
  cat <<SQL | mysqlq
SELECT CONCAT('loyalty:', total_points, '|', lifetime_earned, '|', lifetime_redeemed) FROM tbl_customer_loyalty WHERE customer_id=$CUST;
SELECT CONCAT('txn:', transaction_type, '|', points, '|', IFNULL(order_id,'NULL'), '|', description) FROM tbl_loyalty_transactions WHERE customer_id=$CUST ORDER BY id;
SQL
}

# normalise ids that legitimately differ between runs
norm() { sed -E 's/"redemption_id":"?[0-9]+"?/"redemption_id":<ID>/g; s/"id":[0-9]+/"id":<ID>/g'; }

run_case() {
  local name="$1" path1="$2" path2="$3" data="$4" prep="$5"

  reset_fixture >/dev/null
  local pre1=""; [ -n "$prep" ] && pre1=$(curl -sS --max-time 30 -X POST -d "$prep" "$BASE/api/loyalty/preRedeemPoints" |  grep -oE '"redemption_id":"?[0-9]+"?' | grep -oE '[0-9]+')
  local d1="${data//__RID__/$pre1}"
  local r1=$(curl -sS --max-time 30 -X POST -d "$d1" "$BASE/api/loyalty/$path1" | norm)
  local s1=$(capture_state)

  reset_fixture >/dev/null
  local pre2=""; [ -n "$prep" ] && pre2=$(curl -sS --max-time 30 -X POST -d "$prep" "$BASE/v2/api/loyalty/pre-redeem-points" |  grep -oE '"redemption_id":"?[0-9]+"?' | grep -oE '[0-9]+')
  local d2="${data//__RID__/$pre2}"
  local r2=$(curl -sS --max-time 30 -X POST -d "$d2" "$BASE/v2/api/loyalty/$path2" | norm)
  local s2=$(capture_state)

  if [ "$r1" = "$r2" ] && [ "$s1" = "$s2" ]; then
    printf "  %-34s OK\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-34s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      yii1 resp: $r1"; echo "      yii2 resp: $r2"; }
    [ "$s1" != "$s2" ] && { echo "      yii1 state: $(echo $s1 | tr '\n' ' ')"; echo "      yii2 state: $(echo $s2 | tr '\n' ' ')"; }
  fi
}

echo "=== loyalty write-path differential (Yii 1 vs Yii 2) ==="
run_case "preRedeem happy (100)"        preRedeemPoints          pre-redeem-points           "customer_id=$CUST&points=100"      ""
run_case "preRedeem below minimum (10)" preRedeemPoints          pre-redeem-points           "customer_id=$CUST&points=10"       ""
run_case "preRedeem insufficient"       preRedeemPoints          pre-redeem-points           "customer_id=$CUST&points=99999"    ""
run_case "preRedeem missing params"     preRedeemPoints          pre-redeem-points           "customer_id=&points=0"             ""
run_case "redeemPoints happy (100)"     redeemPoints             redeem-points               "order_id=$ORD&points=100"          ""
run_case "redeemPoints below minimum"   redeemPoints             redeem-points               "order_id=$ORD&points=10"           ""
run_case "redeemPoints bad order"       redeemPoints             redeem-points               "order_id=99999999&points=100"      ""
run_case "refundDeduct happy (50)"      refundDeductPoints       refund-deduct-points        "order_id=$ORD&earn_points=50"      ""
run_case "refundDeduct over balance"    refundDeductPoints       refund-deduct-points        "order_id=$ORD&earn_points=999999"  ""
run_case "refundDeduct bad params"      refundDeductPoints       refund-deduct-points        "order_id=$ORD&earn_points=0"       ""
run_case "transaction history"          getTransactionHistory    get-transaction-history     "customer_id=$CUST&limit=5"         ""
run_case "updateRedemption w/ order"    updateRedemptionWithOrder update-redemption-with-order "redemption_id=__RID__&order_id=$ORD" "customer_id=$CUST&points=100"
run_case "updateRedemption bad id"      updateRedemptionWithOrder update-redemption-with-order "redemption_id=99999999&order_id=$ORD" ""
run_case "rollback redemption"          rollbackRedemption       rollback-redemption          "redemption_id=__RID__"             "customer_id=$CUST&points=100"
run_case "rollback bad id"              rollbackRedemption       rollback-redemption          "redemption_id=99999999"            ""
echo
echo "  passed: $PASS   mismatched: $FAIL"
