# The web UI on PHP 8

The API has a differential harness. The web UI had none, and it is the bulk of
the application: 59 controllers, 652 actions, 663 views. This is what was done
about that, what it found, and what is left.

## The crawl

`tests/port/ui-crawl.py` requests each action on the PHP 5.6 stack (:8082) and
the PHP 8.3 stack (:8084) with a logged-in session, and compares status codes.

Bodies are not compared. The two stacks have separate databases, so their HTML
differs for reasons that have nothing to do with PHP. What the comparison is
for is the one question worth asking first: **which pages did the upgrade
break?** A page that is 200 on 5.6 and 500 on 8.3 is this upgrade's doing.

### What it does not cover

Yii 1 routes answer GET, and plenty of actions that mutate will do so on a GET -
`actionDelete` among them. So the crawl visits only actions whose names match a
read-shaped allowlist, and refuses anything matching a destructive pattern even
if it also looks like a read.

That is 215 of 652 actions. The other 437 are skipped: 240 as mutating, 133 as
not read-shaped, 64 because they need arguments. **A mutating action broken on
PHP 8 will not be found this way.** Those need a fixture-backed harness per
controller, like the API suites - which is the obvious next piece of work.

It also authenticates as one Admin. Anything gated to another role is not
reached.

Running it needs `tests/port/ui-fixture-setup.sql` loaded into *both*
databases - it creates a test administrator, because the only real Admin is a
live account and its password is not something a test should need. Remove it
afterwards with `ui-fixture-teardown.sql`.

## What it found, and what was done

### Twelve pages the upgrade broke - fixed

    /customer/admin          /item/admin        /item/hsnCodeList
    /item/index              /item/printBarcode /item/report
    /paymentReport/admin     /purchaseBill/admin
    /purchaseBill/printBarcode                  /purchaseBillDetail/list
    /stockAdjustLog/admin    /tax/admin

Four causes between them:

1. **`strcmp($value, null)`** in `TbActiveForm`, and `strpos`/`strlen` on a null
   `afterAjaxUpdate` in `TbEditableColumn`. PHP 8.1 deprecates passing null to a
   non-nullable string parameter, and Yii 1's error handler renders a
   deprecation as a 500. Six pages.

2. **`trim(null)`** in `BaseItem` and the HSN-code lookup - nine call sites.

3. **`count(null)`** reaching `CDbCriteria::addInCondition()` from two places
   that passed a session key straight in. A warning-and-zero on PHP 7; a
   TypeError on PHP 8. In `ItemController` the fix was to pass
   `$item_detail_ids`, which the code builds for that call, defaults to
   `array()`, and then ignores.

4. **`date(create_time) < ""`**, which MySQL 5.7 warned about and matched
   nothing and MySQL 8 rejects with error 1525. One inverted condition caused
   it: the branch defaulting the report dates to today fires only when the
   session key is *set and empty*, and on a first visit it is not set at all.
   `!isset || empty` fixed ten query sites at once.

Every fix reproduces what PHP 7 and MySQL 5.7 did rather than choosing
something better. A re-crawl confirms zero regressions remain.

Two of the four are in `ext-prod/bootstrap`, a 2013 YiiBooster build with no
PHP 8 release. Those files are now patched in place - worth knowing if the
extension is ever replaced.

### Fifty-five search endpoints that never worked - fixed

Every `/<controller>/search` began `$model = new Job('search');`. There is no
`Job` model in this application. The endpoints have returned a 500 since they
were written, on 5.6 as much as on 8.3.

The intended model was not a guess: each method reads `$_GET['<Model>']` two
lines below, so the key names the class the scaffold meant to write. All 55 had
exactly one such key and in every case the model existed - including the ones a
controller-name heuristic would get wrong, such as
`B2BPurchaseBillDetailController`, whose key is `B2bPurchaseBillDetail`.

### Nineteen pages still broken on both stacks - not fixed

Not this upgrade's doing, and each needs a decision rather than a patch:

| page | cause |
| --- | --- |
| `/b2bPurchaseBill/*` (5 routes) | `B2bPurchaseBillController cannot find the requested view "index"` - the view file was never written |
| `/b2BPurchaseBillDetail/*` (6 routes) | same family |
| `/itemExpire/index`, `/itemExpire/search` | `Property "ItemExpire.item" is not defined` - a relation that does not exist |
| `/shift/search`, `/itemCompanyCategory/search` | `include(CJuiInputWidget.php): Failed to open stream` - the file is present in `framework/zii/widgets/jui/`, so this is an import-path problem, not a missing file |
| `/item/vendorList` | `Undefined array key "id"` |
| `/purchaseBillDetail/admin`, `/purchaseBillDetail/search` | `Attempt to read property "id" on null` |
| `/purchaseBill/printPDF`, `/b2bPurchaseBill/printPDF` | same family |

### Ten pages that time out only on 8.3 - not fixed

`/order/admin`, `/order/index`, `/order/b2bReport`, `/orderItem/*`,
`/orderRefund/*`. These are unpaginated grids over `tbl_order` (1,580,276 rows)
and `tbl_order_item`. The same missing-pagination problem is already recorded
against the API's `orderList`. They are slow on 5.6 too - 40 further routes time
out on both stacks - but the 8.3 database holds more data, so more of them cross
the timeout. This is a pagination question, not a PHP 8 one.

## Where this leaves the web UI

Nothing in the crawled set is broken by the upgrade any more. That is a smaller
claim than "the web UI works on PHP 8": two thirds of the actions were never
visited, and status codes do not prove a page rendered *correctly* - only that
it rendered.

The next piece of work, in order:

1. A fixture-backed harness for the mutating actions, so the other 437 can be
   exercised without a crawl guessing what is safe to click.
2. Body comparison for the read paths. That needs both stacks on the same
   database, which means pointing the 5.6 container at the MySQL 8 instance for
   a read-only run.
3. Pagination on the order grids.
4. The nineteen pre-existing failures above, each of which needs someone who
   knows what the page was supposed to show.
