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

### order/create and order/update render a checkbox list over five million rows — **found, not fixed**

`protected/views/order/_form.php` offers every order item and every refund as
a checkbox:

```php
<?php if (count(OrderItem::model()->findAllAttributes(null, true)) > 0): ?>
    <?php echo $form->checkBoxListRow($model, 'orderItems',
              GxHtml::listDataEx(OrderItem::model()->findAllAttributes(null, true))); ?>
```

`tbl_order_item` holds 4,976,355 rows. Measured on the untouched PHP 5.6
baseline, signed in:

| | |
|---|---|
| `order/create` | 200, after **302 seconds** |
| `order/update` | 500, after **190 seconds** |

So the page has never been usable, and update does not finish at all. The port
behaves the same way, a little differently: create renders in about 220
seconds, update renders. Neither stack produces a page anyone could use — five
million checkboxes is not a form.

Not fixed here: the repair is a design change to that form, which is a product
decision, not a port. It is recorded because the two pages are the last thing
standing between `order` and the rest of the controllers, and because a
comparison suite timing out on them looks exactly like a port defect.

The UI suite gives these two pages a 400-second budget so they are compared
rather than excused; `order/update` is registered as a baseline failure.

### Seventeen pages are refused to every role, including Admin — **found, not fixed**

`GxActiveRecord::checkPermission($url)` builds a `url => id` map from
`tbl_permission` and answers from it:

```php
if (isset($permIdByUrl[$url]))
    return isset($rolePermSet[$roleId][$permIdByUrl[$url]]);
return false;
```

A PHP array lookup is case-sensitive. A MySQL comparison, under this
database's collation, is not. So a controller that asks for a URL spelled
differently from the row that grants it gets `false` — and the page answers
403 for every role, Admin included, while the permission it needs is sitting
in the table.

Auditing all 98 `checkPermission()` calls in the Yii 1 controllers against
`tbl_permission`:

| | |
|---|---|
| match a row exactly | 81 |
| differ only in letter case | **11** |
| no such row at all | **6** |

The eleven:

    CreditNote/admin  create  delete  update  view   ->  creditNote/...
    ItemCompany/create  update  view                 ->  itemCompany/...
    ItemDetail/create   update  view                 ->  itemDetail/...

The six, which have no row under any spelling:

    advancePayment/delete   item/extra   mrn/admin
    mrn/view                purchaseOrder/admin      purchaseOrder/view

So the whole credit-note section, the item-company and item-detail
create/update/view pages, and the MRN and purchase-order admin and view pages
cannot be opened by anybody.

Not fixed here: the repair is either 17 rows of data or 17 string literals, and
which one is right is a product decision — whether those pages are meant to be
reachable at all. The port reproduces the refusal exactly, which is the correct
behaviour for a port.

It is also why the UI suite reports these as **nothing compared** rather than
as passes: both stacks answer 403, and a page neither stack renders has not
been verified by their agreeing about it.

### itemExpireItem/delete removes a row on a plain GET — **found, not fixed**

    GET /itemExpireItem/delete?id=8310
    302 -> /item/expireStock?vendor_id=341&outlet_id=5

and the row is gone. No POST, no confirmation, no token: anything that
follows the link deletes the row - a crawler, a prefetching browser, a link
checker, a preview in a chat client.

Yii 1's generated delete actions guard this with
`if (Yii::app()->request->isPostRequest)`, and most of this application's do.
This one does not, and the port reproduces it.

The write sweep does not run any action whose name begins with `delete` for
exactly this reason. It found this one the hard way: four rows of
`tbl_item_expire_item` were deleted before the guard existed, and restored
from the 5.6 database, which still had them.

Not fixed here: adding a POST check changes what the application does, which
is a decision about the application rather than about porting it.

### order/updateDetail rewrites every order item in one request — **found, not fixed**

```php
public function actionUpdateDetail() {
    $orderItems = OrderItem::model()->findAll();
    foreach ($orderItems as $orderItem) { ... $orderItem->saveAttributes(['create_date']); }
}
```

`tbl_order_item` holds 4,976,355 rows, and the action loads all of them into
memory before writing the first one. It gets 27 seconds in and exhausts a
10 GB limit, on both stacks.

The two report it differently, which is the only reason it shows up as a
difference at all: Yii 1 answers **200** with `Fatal error: Allowed memory
size ... exhausted` in the response body, because the fatal happens after the
headers have gone out. The port answers **500**. Matching Yii 1 here would
mean reproducing a page that claims success while printing a fatal error, so
the action is registered as a known failure instead.

It looks like a maintenance script that ended up on a web route. Whether it is
still needed - and if so, in batches - is a product decision.

### Two report pages print debugging output instead of a report — **found, not fixed**

`order/groupTax` ends like this, on the untouched 5.6 baseline as well as on
8.3:

    </form>
        <div class="col-md-12">
        SELECT * FROM `tbl_order_item` `t` WHERE 0=1 GROUP BY t.tax_id,t.create_date ORDER BY ...

`BaseOrderItem::groupTaxsearch()` carries a line that was meant to be
temporary:

```php
// print_r query for debugging
echo $this->getCommandBuilder()->createFindCommand($this->getTableSchema(), $criteria)->getText(); die;
```

So the page renders its search form, echoes the query it was about to run, and
stops. It has never shown a report.

`item/adjustStock` is the same fault without the `die`: `BaseItem::adjust()`
does `echo "<pre>"; print_r($criteria); echo "</pre>";`, and the page carries a
dump of the CDbCriteria object above the grid.

Not fixed here: deleting a line from the Yii 1 tree is not porting it, and
which of these reports is still wanted is a product question. The port cannot
match either page - reproducing a debug dump is not a port - so `order/groupTax`
is registered in `tests/port/known-action-failures.txt`.

### Six more pages that are 500 on the untouched 5.6 baseline — **found, not fixed**

Measured against a real row, signed in, on both stacks:

| page | 5.6 baseline | PHP 8.3 |
|---|---|---|
| `item/update` | 500 | 500 |
| `mrnDetail/update` | 500 | 500 |
| `mrsDetail/update` | 500 | 500 |
| `purchaseBillDetail/admin` | 500 | 500 |
| `purchaseOrderDetail/update` | 500 | 500 |
| `vendor/update` | 500 | 500 |

The port reproduces each one, so the UI suite records them as *nothing
compared* rather than as passes: neither stack renders a page, and two crashes
agreeing is not agreement.

`itemDetail/view` belongs here too, with a twist: it is **403 on the baseline
and 500 on 8.3**, because the permission that refuses it there is one of the
seventeen the application asks for under a spelling the table does not have.
Grant it - which `perm_fixture.sql` does so the page can be compared at all -
and the page crashes instead of being refused. It has presumably never been
opened.

Not fixed here: each is a bug in the Yii 1 application, not in the port.

### Both B2B sections are dead, and have been — **found, not fixed**

All seven pages of `b2bPurchaseBill`, and all seven of
`b2BPurchaseBillDetail`, answer 500 on the untouched PHP 5.6 baseline as well
as on 8.3:

    :8082 (5.6)  b2bPurchaseBill/admin   500
                 b2bPurchaseBill/index   500
                 b2bPurchaseBill/create  500
                 b2bPurchaseBill/view    500
                 b2bPurchaseBill/update  500

`B2bPurchaseBillController cannot find the requested view "admin"`.

Yii 1 builds a controller's view path from its id, so
`B2bPurchaseBillController` looks in `protected/views/b2bPurchaseBill`. The
directory on disk is `protected/views/b2bpurchaseBill` — lower-case `p`. On a
case-insensitive filesystem, which is what a Windows or macOS development
machine has, those are the same directory. On Linux they are not, and every
one of the fifteen view files is invisible.

`B2BPurchaseBillDetailController` has the same fault: its id is
`b2BPurchaseBillDetail` and its views are in
`protected/views/b2bpurchaseBillDetail`.

So this section of the application has been unusable on the server for as long
as it has been on Linux.

Not fixed here: renaming a directory changes the Yii 1 tree in a way that is
not part of porting it, and the fix is one `git mv`. The port renders all seven
pages, because Yii 2 resolves its own view path and `app2/views/b2bPurchaseBill`
is spelled the way its controller is.

**This was hiding inside a green suite.** `check_pair()` treated two equal
non-200 statuses as agreement — "ok (both 500)" — so for as long as the port
failed in the same way, the controller reported seven passes. It only surfaced
when the port started rendering the pages and the statuses stopped matching.
The suite now counts that as *nothing compared* rather than as a pass.



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

### The web UI enforces its permission table on some URLs and not others — **found, not fixed**

`accessRules()` on 58 of the 60 controllers requires only that the caller is
signed in. Whether `tbl_permission` is actually enforced then depends on
whether the individual action calls `checkPermission()` itself, and that is
inconsistent:

  - **31 controllers, 115 actions** call it and throw a 403 when the role
    lacks the permission. `userRole/update` is one of these.
  - **29 controllers, 559 actions** never call it. There the permission table
    only decides which links and row buttons are *drawn*. A user whose role
    lacks `paymentMode/update` sees no Update button and can still open
    `/paymentMode/update/id/1` by typing it, and the action runs.

The ungated set is not a fringe: it includes `order` (37 actions),
`b2bPurchaseBill` (19), `purchaseBill` (17), `itemReturnItem` (17),
`mrsDetail` (15) and `purchaseOrderDetail` (15).

The Yii 2 port reproduces each controller as it stands rather than picking one
behaviour and applying it everywhere. Enforcing it uniformly is a few lines in
`BaseUiController` — the first draft of the port did exactly that — but it
would start refusing 559 URLs that work today, and which of those refusals are
wanted is the owner's call, not the porter's. Say the word and it is a small
change on both stacks.

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

## Three more pages that crash on the untouched 5.6 baseline

Confirmed with `tools/port/baseline_check.sh`, which fetches the page from
:8082 as well as :8084 - Yii 1 failing in the PHP 8.3 tree alone would only
show that this work broke it.

| page | baseline | 8.3 |
|---|---|---|
| `itemExpire/update/2840` | 500 | 500 |
| `mrn/update/64040` | 500 | 500 |
| `itemStock/admin` | 500 | 500 |

The Yii 2 port renders all three. They are listed in
`tests/port/known-yii1-failures.txt` so the suite stops reporting a mismatch
for a page the port cannot match by crashing too.

These were invisible until the port stopped writing `var_export` output into
its responses: until then the ported page failed as well, the two statuses
agreed, and the comparison passed. A suite can be green because both sides are
broken.

`itemReturnItem/create` is a fourth of the same family but not a crash: it
answers 200 with an empty body on the baseline, on Yii 1 under 8.3, and on the
port. All three agree, so there is nothing to fix - but nothing is compared
either, which the suite now says out loud rather than counting as a pass.

## Maintenance scripts left in controllers, reachable by GET

`order/updatetax` takes an optional `$date` it never reads. It selects every
order item created between two dates written into the source - 2021-08-01 to
2021-08-12, 21,030 rows - recomputes each one's price, sale rate, MRP, the
four tax percentages and amounts, and the line total, and saves it. A plain
GET, no POST, no confirmation, no bound parameter. Anyone who can reach the
admin can rewrite three weeks of billing history by following a link.

It ran during a write sweep. It rewrote 8,925 of those 21,030 rows in the
port's database; the 5.6 baseline was untouched, so the window could be
restored from it, and the four neighbouring windows checksum identical on both
databases, which is what confirmed the damage was exactly this action's range
and nothing else's.

Two things made it hard to see:

  - It outlives its request. `OrderItem::afterSave()` appends a
    `tbl_item_velocity` row per save, and the inserts continued for hours
    after the sweep had given up waiting and moved on - roughly four a second,
    from an Apache worker still running a request nobody was reading. The
    write comparison for every action swept afterwards was unreadable, and it
    looked like a background job nobody could find. There is none; it was this
    request, still going.
  - It leaves no trace in the row it changes. `tbl_order_item` has no
    `update_time` maintained by the model, so a rewritten row is
    indistinguishable from an untouched one by inspection. The only way to
    find what it had changed was to checksum against the other database.

`order/updateDetail` is the same shape and worse - it loops over every order
item in the table - but it exhausts PHP's memory limit before it writes
anything, on both stacks, so it has been sitting in `known-action-failures.txt`
as a pair of matching 500s.

Thirteen more actions save inside a loop over a finder that takes no input.
The write sweep now refuses all of them and names them in its output; see
`loops_over_saves()` in `tests/port/write-sweep.py` for how they are told
apart from the ajax handlers, which also save in a loop but over the rows they
were posted. `itemDetail/barcodes` is in the list and is commented out in the
source - refusing it costs nothing.

None of these are port defects. They are in the Yii 1 application, they are
in production, and they are one click from an admin session.

## `user/delete` cascades through four classes that do not exist

`User::beforeDelete()` deletes through `MerchantStore`, `Category`, `Product`
and `PromotionalAdd`. None of the four is in the Yii 1 tree, in the port, or
in the database - they belong to a different application this code was cut
from. Deleting a user therefore fatals on the first of them and deletes
nothing, which is the only reason orders are not being orphaned.

It is the one Yii 1 lifecycle hook deliberately not ported, because there is
no behaviour to reproduce: copying it across would copy a fatal, and leaving
it out lets the port delete the user and orphan its orders - a third
behaviour, and the worst of the three. `tests/port/hook-parity.py` carries it
as its only allowed exception so that the gap stays visible.

Whoever owns this has to decide what deleting a user should do before either
stack can do it.

## The same delete, written twice

The teardown's `DELETE FROM tbl_credit_note WHERE amt IN (100, 200, 300, 1000)`
was scoped by id, the 601 real credit notes were restored from the 5.6
database, and the row-loss check went green. The next full run took the same
601 rows again.

The predicate was in two files. `refund_difftest.sh` carries its own reset, and
its copy read `WHERE amt IN (0, 100, 200, 300, 1000)` - the same fault plus
`amt = 0`, which is where the 52 zero-value notes in the original loss came
from. Fixing the teardown and not the suite fixed nothing at all.

Two things follow, and the second matters more than the first:

  - `tests/port/delete-scope.py` reads every DELETE in the harness - 261 of
    them across the shell suites and the fixture SQL - and checks that its
    WHERE clause names something the harness made: a fixture id from 9990000
    up, a shell variable holding one, one of the two test phone numbers, or
    the credit-note high-water mark. Anything else is reported. It runs as
    `deletes_difftest`, before any suite writes a row.
  - The row-loss check earned its place. It caught the second deletion on the
    first run after it was introduced, which is exactly the job: the first
    deletion went unnoticed for four days across every green run, because a
    harness that compares two stacks against each other cannot see damage they
    share. Both read the same database. Only the 5.6 copy, which nothing
    writes to, could tell them apart.

The restore is idempotent and still on the server at
`/root/pos/restore/credit_note_BASELINE.sql` - 15,971 REPLACE statements, the
whole table as the baseline holds it.
