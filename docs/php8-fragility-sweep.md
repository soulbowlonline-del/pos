# PHP 8 fragility sweep

PHP 5.6 let a program read a variable that was never assigned: it emitted a
notice and carried on with null. PHP 8 raises a warning, and Yii 1's error
handler turns a warning into a rendered 500. So code that limped along for
years now takes an endpoint down - and only on the paths where the variable
really is unset, which is why it does not show up in ordinary use.

This is a sweep for that one family of defect. It is not an attempt to make the
codebase pass static analysis.

## Method

PHPStan at level 2 over `protected/` and `config/`, with `framework/` scanned
but not analysed. Two messages matter:

  - `Undefined variable: $x`        - read, never assigned anywhere in scope
  - `Variable $x might not be defined.` - assigned on some paths only

Level 2 sees variables, not array keys, so `$_POST['id']` read without a check
is invisible to it. That class is covered separately below.

Counts as of the latest run: **22 definite**, **256 conditional**.

## Triage

The conditional list is mostly noise, and the noise has a shape. Each finding
is classified by where the variable is written relative to where it is read,
using brace depth rather than indentation:

| bucket | count | meaning |
| --- | --- | --- |
| noise | 34 | `$this` inside a view; the framework always provides it |
| column-builder | 35 | `getXxxColumns()` builds `$columns` inside `if ($selected)`, and `$selected` falls back to a non-empty literal, so the loop always runs. PHPStan cannot see that the literal is non-empty |
| assigned-before | 129 | a plain assignment appears earlier in the function at the same or lower depth as the read |
| write-encloses-read | 4 | every write sits in a block that also contains the read, so the read is unreachable without one |
| **reachable** | **54** | the read can be reached with no write having run. This is the group that needs eyes |

The script that produces this is `tests/port/triage-sweep.py`; it reads a
PHPStan run and writes the buckets, so the classification can be re-derived
rather than trusted.

## Definite: read, never assigned anywhere in scope

  - protected/components/GxActiveRecord.php:612  $this
  - protected/components/GxActiveRecord.php:614  $this
  - protected/components/GxActiveRecord.php:659  $this
  - protected/controllers/MrnDetailController.php:503  $existmrs
  - protected/controllers/MrnDetailController.php:504  $existmrs
  - protected/controllers/MrnDetailController.php:505  $existmrs
  - protected/controllers/MrnDetailController.php:506  $existmrs
  - protected/controllers/MrnDetailController.php:751  $mrsid
  - protected/controllers/PurchaseOrderDetailController.php:641  $mrsid
  - protected/controllers/PurchaseOrderDetailController.php:896  $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:900  $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:904  $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:908  $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:912  $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:916  $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:919  $mrsIdAll
  - protected/controllers/PurchaseOrderDetailController.php:922  $mrsIdAll
  - protected/models/OrderItem.php:756  $month
  - protected/models/OrderItem.php:757  $tank
  - protected/models/OrderItem.php:758  $year
  - protected/models/OrderItem.php:852  $month
  - protected/models/OrderItem.php:864  $month

Three of these are `$this` inside `GxActiveRecord`, which is an analyser
artefact. The rest are real, and all of them are in the web UI rather than the
API:

  - `MrnDetailController` builds a `PurchaseOrder` out of `$existmrs`, which is
    never assigned. Every field it copies is a read of an undefined variable
    followed by a property read on null.
  - `PurchaseOrderDetailController` reads `$mrsid`, `$mrnIdAll` and `$mrsIdAll`,
    none of them assigned.
  - `OrderItem::getcsvexcel()` reads `$month`, `$tank` and `$year`, and calls
    `Tank::findOne()` and `Nozzle::find()->where()` - Yii 2 syntax, in a Yii 1
    model, for two classes that do not exist in this application at all. It is
    called from `OrderController::actionorderexcel()` as
    `$ordermodel = new Order(); $ordermodel->getcsvexcel();`, and `Order` does
    not define that method either. `/order/orderexcel` cannot ever have worked.

None are fixed here. Each needs a decision about what the code was meant to do,
and they are in the part of the application that has no differential harness
yet. They are listed so the work is visible.

## Reachable: the read can happen with nothing assigned

54 findings. Seven are in the API module, where there is a harness:

  - protected/modules/api/controllers/EmpController.php:243  $list
  - protected/modules/api/controllers/EmpController.php:275  $list
  - protected/modules/api/controllers/ItemController.php:433  $outlet_id
  - protected/modules/api/controllers/ItemController.php:1819  $outlet
  - protected/modules/api/controllers/OrderController.php:821  $refundmodel
  - protected/modules/api/controllers/OrderController.php:822  $refundmodel

Of those:

  - `EmpController` twice, and `ItemController::adjustitemtozero` - not
    triggerable. The Emp loops sit inside `if ($users)`, so they always run at
    least once, and the outlet lookup only fails if `tbl_outlet` is empty.
  - `ItemController::getGRN` - `$outlet_id` is set inside `if ($outlet)`. No
    user in this database is in that state today, so it cannot be reached here,
    but a user whose employee row points at a missing outlet would 500.
  - `OrderController::actionRefund` - `$refundmodel` is written inside the item
    loop and read after it, so a refund posted with an `item_details` array
    that decodes to nothing reaches the read with nothing assigned. **Open.**
    actionRefund is the one order action still on Yii 1 only; this should be
    fixed as part of porting it, with a test that posts an empty array.
  - `OrderController::actionReprint` - **fixed**. `$taxarr` was only written
    inside the loop, so an order with no lines returned a 500. Both stacks now
    initialise it to null, which is what 5.6 emitted.

The remaining 48 are in the web UI, by file:

    4  protected/controllers/OrderController.php
    3  protected/controllers/B2BPurchaseBillDetailController.php
    3  protected/controllers/MrnDetailController.php
    3  protected/controllers/PurchaseBillDetailController.php
    3  protected/controllers/PurchaseBillDetailController_11_1_22_kara.php
    3  protected/models/OrderItem.php
    3  protected/modules/backup/views/default/upload.php
    2  protected/controllers/AdvancePaymentController.php
    2  protected/controllers/B2bPurchaseBillController.php
    2  protected/controllers/ItemController.php
    2  protected/controllers/PurchaseOrderDetailController.php
    2  protected/models/OnlineOrder.php
    2  protected/modules/backup/views/layouts/admin_layout.php
    1  protected/components/GxActiveRecord.php
    1  protected/controllers/ItemExpireItemController.php
    1  protected/controllers/ItemReturnItemController.php
    1  protected/controllers/ItemStockController.php
    1  protected/controllers/MrsDetailController.php
    1  protected/controllers/OnlineOrderController.php
    1  protected/models/B2bPurchaseBill.php
    1  protected/models/PurchaseBill.php
    1  protected/modules/backup/views/default/_list.php
    1  protected/modules/backup/views/default/index.php
    1  protected/modules/backup/views/layouts/column1.php
    1  protected/modules/backup/views/layouts/column2.php
    1  protected/modules/backup/views/layouts/column3.php
    1  protected/modules/backup/views/layouts/main.php

## A different class: unchecked request keys

PHPStan at level 2 reports undefined *variables*, not undefined *array keys*,
so reads straight out of `$_POST` and `$_FILES` are invisible to the sweep
above. They matter just as much on PHP 8: what was a notice returning null in
5.6 is a warning now, and Yii 1's error handler turns a warning into a rendered
500, so an endpoint that used to limp along returns an error page.

Found so far, all reproduced in the ports rather than fixed, so the two stacks
behave identically and the difference can be decided deliberately later:

  - `customer/uploadbill` - `$_POST['id']` and `$_FILES['file']['name']`.
  - `item/scannedItem` - `$_POST['computer_name']` and `$_POST['user_id']` are
    tested without `isset`, so a request missing either returns a 500 rather
    than the "details are missing" reply the code appears to offer. `count()`
    on a failed `json_decode` is a TypeError for the same reason.

To find the rest of this class, PHPStan level 5 or higher is needed. It has not
been run: at level 2 the reachable list above is already the work in hand.

## Excluded as noise

- `Access to an undefined property <Model>::$x` - Yii 1 resolves ActiveRecord
  attributes through `__get` at runtime, so static analysis cannot see them.
  ~1,400 findings, none actionable.
- `Undefined variable: $this` - analyser artefact.

## Reproducing

```bash
docker run --rm -v $PWD:/app -v /root/pos/tools:/tools -w /app php:8.3-cli \
  php -d memory_limit=3G /tools/phpstan.phar analyse --no-progress \
  --error-format=raw -c phpstan.neon > phpstan.txt

python3 tests/port/triage-sweep.py phpstan.txt
```
