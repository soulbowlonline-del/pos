# Live bugs found while porting the API

Bugs in the running application, found because a differential test made the
Yii 1 and Yii 2 stacks disagree. Each entry says whether it is fixed, and if
not, what decision is needed.

This is separate from `php8-fragility-sweep.md`, which lists code that *may*
break on PHP 8. Everything here is confirmed broken by a request.

---

## Fixed

### order/getDescriptionByGrn — 500 on a NULL approved_qty

`$approvedQty = isset($detail->approved_qty) ? $detail->approved_qty : $detail->qty;`

`tbl_purchase_bill_detail` has no `qty` column, and a direct read of a
property Yii 1 does not know raises `Property "PurchaseBillDetail.qty" is not
defined`. The line below it reads `$detail->product_id`, which does not exist
either, whenever a line's `item_id` matches no item.

Not one of the 795,752 purchase bill lines is in either state, which is why
this has never been seen to fail. The GRN test fixture creates both.

Fixed in both the Yii 1 action and the Yii 2 port: `item_id` is the column
that was meant, and there is no second quantity column, so the fallback is
`null`. The PHP 5.6 baseline is deliberately left alone — it is the
byte-comparison reference and the bug is unreachable in real data.

### tally/cashsale returned a 500 for 23 dates on PHP 8

`actionCashsale` and `actionB2btaxwise` both compute five GST figures inside a
branch:

```php
$row13 = ...queryRow();          // the tbl_tax row for this tax_id
if($row13){
    $cgst = ...; $sgst = ...; $cess = ...; $igst = ...; $gst = ...;
    $total_amt = $taxable + $gst;
}
$json_list [] = array(..., 'Gst'=>$gst, 'Cgst'=>$cgst, ..., 'Amount'=>$total_amt);
```

Nothing sets them when the tax row is missing. 32 order items carry
`tax_id = 0` and there is no `tbl_tax` row with id 0; the query groups and
orders by `tax_id`, so 0 sorts first and those five are read before they have
ever been assigned. On PHP 5.6 that was a notice and five nulls. On PHP 8 it is
a warning, which Yii 1's error handler renders as a 500 - so the report was
down for all 23 dates those items fall on:

```
POST /api/tally/cashsale?date=2020-04-30   ->  500
POST /api/tally/cashsale?date=2026-09-16   ->  200
```

Found by triaging the conditional findings in `php8-fragility-sweep.md`, not by
a test - the suite only used dates where every tax row resolved.

**Fixed** on both stacks by clearing the five per iteration, which restores the
PHP 5.6 output (nulls) and removes the 500.

One deliberate difference from 5.6: on the second and later iterations those
variables previously held the *previous* row's values, so a tax row that failed
to resolve mid-list reported another row's GST as its own. Clearing per
iteration reports null instead. That only changes output where 5.6 was already
wrong, but it is a change to a tax report, so it is called out here. Moving the
one added line above the loop would restore the old carry-over exactly.

### Five mail functions with the statement terminator commented out

`protected/models/User.php` — a trailing `.` where a `;` belonged swallowed the
`mail()` call that followed, so every one of these sent nothing. Fixed.

### customer/update could never succeed

The duplicate-phone check matched the customer being edited against itself, so
a valid edit always failed as a duplicate. Verified empirically, then fixed.

### round(null) on PHP 8

`Order::getOrderAfterRefundQty()` passed a NULL `SUM()` to `round()`, which is
fatal on PHP 8. Affected 407 real orders. Fixed with a null guard.

---

## Found, not fixed — needs a product decision

### order/search queries the wrong table, and cannot work at all

`actionSearch()` filters `OrderItem` by `bill_date`, `bill_no` and
`customer_id`:

```
SELECT * FROM `tbl_order_item` `t` WHERE bill_no =:ycp0
-> SQLSTATE[42S22]: Unknown column 'bill_no' in 'where clause'
```

All three columns exist on `tbl_order`; none exists on `tbl_order_item`. So
the endpoint throws a `CDbException` for every request that reaches the query.

It is only reached if the caller's user row has an employee record, and most
do not: of 590 users, 190 have `emp_id = 0` and 388 have it NULL. But **12 have
a valid employee row**, and for those the endpoint is live-broken today:

```
curl -X POST -H 'userlogin: 11' -d 'bill_no=1' /api/order/search   -> 500 CDbException
curl -X POST -H 'userlogin: 1'  -d 'bill_no=1' /api/order/search   -> {"message":"No employee found"}
```

(An earlier version of this file said every user had `emp_id = 0`, which was
wrong - it was read off a partial query. The endpoint is reachable, and it
returns a 500 rather than never being called.)

The fix is one word: query `Order` rather than `OrderItem`. But `Order::toArray()`
and `OrderItem::toArray()` return quite different payloads, so that choice
defines the endpoint's contract, and whatever client calls `order/search`
expects one of them. **Decision needed: what should order/search return?**

The evidence points one way - the local variable is `$orders`, the response key
is `orders`, and every other order-listing action in this controller returns
`Order::toArray()` under that key - but "points one way" is not the same as
knowing what the client parses, so this is not a call to make while porting.

Until it is decided the action is not ported: porting it would mean either
reproducing a guaranteed 500 or inventing an API contract. It is the only
action in the controller left unported for this reason.

### item/billUpdate flips one hardcoded row, for anyone who asks

```php
public function actionBillUpdate(){
    $purchaseBill = PurchaseBill::model()->findByPk('97');
    if($purchaseBill){ $purchaseBill->status = 0; $purchaseBill->save(); }
    $arr ['status'] = 'OK';
```

That is the whole action. It takes no parameters, checks no caller, and sets
the status of purchase bill 97 - a literal id - to 0. It answers OK whether or
not the row exists and whether or not the save worked.

It reads like a debug leftover that shipped. It is live on /api/item/billUpdate
today and anyone who can reach the API can call it.

Ported as-is so the two stacks agree, and left in place: deleting a live
endpoint is the owner's call, not the porter's. **Decision needed: remove it,
or is something calling it?**

### order/online and order/getOnlineOrder are not authenticated

Both read the caller id from a header and then overwrite it:

```php
$loginid = isset ( $headers ['userlogin'] ) ? $headers ['userlogin'] : null;
...
$loginid = '1';
```

so the `if ($loginid)` below can never fail. Every online order in the date
window is readable by anyone who can reach the endpoint, with customer names,
addresses and phone numbers. order/cancelOrder has a real login check but no
ownership check, so any logged-in caller can cancel any online order.

Reproduced in the port rather than fixed, because tightening an endpoint the
delivery app calls is a change that needs testing against that app.

### item/adjustitemtozero logs every adjustment against the first outlet

The outlet is not a parameter. Yii 1 takes the first outlet by id and writes
that on the stock adjustment log regardless of where the adjustment happened,
so the log cannot distinguish outlets. Reproduced; worth deciding whether the
log should take the outlet from the caller.

---

## Documented elsewhere

  - `customer/uploadbill` reads `$_POST['id']` and `$_FILES['file']['name']`
    unchecked, so a request missing either returns a rendered 500 on PHP 8.
    Reproduced on both stacks; see `php8-fragility-sweep.md`.
  - `getOrderBillNo()` has its prefix logic inverted, `getSgstPercent()` reads
    `tax_val1`, `getIgstPercent()` always returns 0, `getMainDiscount()`
    returns the literal `'0'`, and `state_id` is overwritten with 1 whenever a
    customer is saved. All reproduced rather than fixed, so the two stacks
    agree; each needs a decision before it is changed.
  - MySQL runs in UTC and PHP in Asia/Kolkata, so `hasPendingOTP()` compares a
    PHP-written timestamp with MySQL's `NOW()` and locks a customer out for
    five and a half hours.
