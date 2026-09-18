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

### item/barcode 500s for every bar code that matches something

`BaseItem::relations()` declared the item-vendor relation as

```php
'itemVendors' => array(self::HAS_MANY, 'itemVendor', 'item_detail_id')
```

with a lowercase class name. The model is `ItemVendor`, in `ItemVendor.php`, and
Yii's autoloader includes `<class>.php` - so this resolved only on a
case-insensitive filesystem. On this host:

```
include(itemVendor.php): Failed to open stream: No such file or directory
```

`/api/item/barcode` reads that relation for any bar code that matches an item
detail, so the endpoint worked only for bar codes that matched nothing.

**Fixed**: corrected to `ItemVendor`.

Left alone: the relation's foreign key is `item_detail_id` on a relation
declared on `Item`, so it matches `tbl_item_vendor` rows whose `item_detail_id`
equals the *item's* id. That column holds item detail ids everywhere else, so
this looks like a second mistake in the same line - but changing which vendor an
item reports is a decision about the data. **Decision needed.**

### Twelve web UI pages broken by PHP 8, and 55 that never worked

Covered in full in `web-ui-php8.md`, including the four causes and what was
done about each. Summarised here so this file remains the index:

  - Twelve pages were 200 on PHP 5.6 and 500 on 8.3 - null passed to `strcmp`,
    `trim`, `strpos` and `strlen`, `count(null)` reaching the framework, and
    `date(create_time) < ""` which MySQL 8 rejects. **All fixed.**
  - Every `/<controller>/search` began `new Job('search')`, a scaffold
    placeholder for a model that does not exist. 55 endpoints, returning 500
    since they were written, on 5.6 as much as 8.3. **All fixed.**
  - Nineteen further pages fail identically on both stacks - missing view files,
    a relation that does not exist, an import-path problem. **Not fixed**; each
    needs someone who knows what the page was meant to show.

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

### order/search queried the wrong table — **fixed**

`actionSearch()` filtered `OrderItem` by `bill_date`, `bill_no` and
`customer_id`:

```
SELECT * FROM `tbl_order_item` `t` WHERE bill_no =:ycp0
-> SQLSTATE[42S22]: Unknown column 'bill_no' in 'where clause'
```

All three columns exist on `tbl_order`; none exists on `tbl_order_item`. So the
endpoint threw for every request that reached it. Of 590 users, 190 have
`emp_id = 0` and 388 have it NULL, and those stop a branch earlier at "No
employee found" — but **12 have a valid employee row**, and for them it was a
live 500.

Fixed by querying `Order`, which is the one-word change this file already
identified: the local variable, the response key and every sibling action in
the controller already said `orders`. The payload is `Order::toArray()`, the
same shape `order/get` returns. Ported to Yii 2 and covered by twelve
differential cases.

### item/billUpdate flipped one hardcoded row — **fixed**

It loaded purchase bill 97 — a literal id — set its status to 0 for any caller,
and answered OK whether or not the row existed or the save worked.

It now takes `purchase_bill_id` from the request and reports what happened:
`purchase_bill_id is required` with none, `Purchase bill not found` for an
unknown one, OK only when the save succeeds. Changed identically on both
stacks.

Still unauthenticated — that was not part of the change, and anyone who can
reach the API can still set a bill back to unapproved.

### order/online and order/getOnlineOrder were not authenticated — **fixed**

Both read the caller id from a request header and then overwrote it with the
literal `'1'`, so the check below could never fail. Between them that exposed
every online order in the date window, and any single order by id, to anyone
who could reach the endpoint — customer names, addresses and phone numbers
included.

The overwrite is gone from both actions on both stacks. The header decides now,
and a request without one gets "Please login".

### The web UI's permission table is not enforced on URLs — **found, not fixed**

`tbl_permission` decides which sidebar links and which row buttons a role is
shown. It does not decide what that role can reach.

Every CRUD controller's `accessRules()` is the same: signed in, no further
condition. `checkPermission()` is called only from the views. So a user whose
role has no `paymentMode/update` permission sees no Update button and can still
open `/paymentMode/update/id/1` by typing it, and the action runs.

That is 58 controllers and roughly 670 actions, all of them reachable by any
authenticated account regardless of role.

The Yii 2 port reproduces this rather than closing it. Enforcing the permission
on the action is a one-line change in `BaseUiController` — it was written that
way first — but it would deny URLs that work today, and which of those denials
are wanted is the owner's call, not the porter's. Say the word and it is a
small change on both stacks.

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
