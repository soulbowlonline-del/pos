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

It is not reached at all unless the caller's user row has an employee record —
every user in this database has `emp_id = 0`, so in practice every call stops
one branch earlier at "No employee found". That is why a completely broken
endpoint has gone unnoticed.

The fix is one word: query `Order` rather than `OrderItem`. But `Order::toArray()`
and `OrderItem::toArray()` return quite different payloads, so that choice
defines the endpoint's contract, and whatever client calls `order/search`
expects one of them. **Decision needed: what should order/search return?**

Until then it is not ported — porting it would mean either reproducing a
guaranteed 500 or inventing an API contract.

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
