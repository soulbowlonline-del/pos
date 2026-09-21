# Porting the web UI to Yii 2

The API port could lean on byte comparison: same request, same JSON, same
bytes. The web UI cannot. Two frameworks never emit identical HTML, so the
question "is this page still right?" has to be asked about what the page
*says* rather than what it renders.

## Scale

| | count |
|---|---|
| controllers | 59 |
| actions | 674 |
| views | 652 |
| view lines | 76,083 |
| controller lines | 29,953 |
| views using YiiBooster widgets | 387 |
| views using `TbActiveForm` | 218 |

This is several times the API surface, and it is being ported controller by
controller rather than in one pass.

## How the two stacks coexist

Yii 1 serves `/`, Yii 2 serves `/v2`, as for the API. What is new here is that
the *same page* has links to controllers on both sides.

- `Ui::PORTED` lists the controllers Yii 2 serves.
- `Ui::to($route, $params)` returns a `/v2/...` URL for those and a Yii 1 URL
  for everything else. Every link in the ported layout goes through it, so a
  half-ported application still navigates.
- `LegacyUrlRule` routes `/v2/paymentMode/admin` to the `payment-mode`
  controller, and declines any controller not in `Ui::PORTED` - an unported
  path 404s rather than resolving to something unexpected.

Yii 1 spells controller and action ids in camelCase; Yii 2 requires lowercase
and hyphens. Both spellings are live at once, so the conversion lives in one
place, `Ui::toYii1Id()` / `Ui::toYii2Id()`, and nothing else repeats it.

## The YiiBooster shims

387 views call `bootstrap.widgets.Tb*`. Rewriting each one's markup would be a
redesign, not a port. Instead `app2/widgets/` holds small classes that accept
the same configuration arrays and emit the same Bootstrap classes:

| Yii 1 | port |
|---|---|
| `TbGridView` | `app\widgets\GridView` |
| `TbDetailView` | `app\widgets\DetailView` |
| `TbActiveForm` | `app\widgets\ActiveForm` (with the `*Row()` helpers) |
| `TbButtonGroup` | `app\widgets\ButtonGroup` |
| `TbButton` | `app\widgets\Button` |

A view then ports by changing how a widget is invoked, not what it renders.

Views keep their Yii 1 directory names - `views/paymentMode`, not
`views/payment-mode` - so each ported view sits beside its original in a diff.
`BaseUiController::getViewPath()` does that mapping.

## How a controller is ported

Three generators, then a comparison. None of them guess: anything a generator
does not recognise is left as it was and reported, so it fails loudly on the
first request rather than rendering a page that is quietly wrong.

| | |
|---|---|
| `port_model.py <Model>` | the model, from the giix `_base` class plus the hand-written methods on the concrete one. Against a model the API port already wrote, `--presentation-only` adds just the display contract - `label()`, `__toString()`, `defaultOrder()`, the option helpers - and never `rules()`, `search()` or `beforeValidate()`, which would change how that model validates and saves. |
| `port_controller.py <Model>` | the controller, translated from the Yii 1 one so its hand edits survive. |
| `port_views.py <controller>` | the views, likewise. |
| `batch_port.py <Model>...` | runs all three for each controller, brings its dependencies up to the display contract, compares every page against Yii 1, and adds it to `Ui::PORTED` **only if it matches**. One that differs stays on Yii 1. |

`tests/port/pmui-difftest.sh` then covers whatever is in `Ui::PORTED`, so a
controller that lands is checked by the regression without a second edit.

## Framework defaults that silently change behaviour

These are the differences that produce a working page showing the wrong thing.
Each was found by the comparison suite, not by reading the code.

- **Row order.** `GxActiveRecord::defaultScope()` puts `ORDER BY id DESC` on
  every model that has an `id`. Yii 1 listings are newest-first even where the
  data provider names no sort. Yii 2 has no such default, and an unordered
  query on MySQL 8 came back ascending - a listing that looked plausible and
  was reversed.
- **Page size.** Yii 1's `CPagination` defaults to 10 rows, Yii 2's to 20. A
  provider that sets no page size lists a different number of rows on each
  stack.
- **Nulls.** `CGridView` prints an empty cell for a null; `CDetailView` prints
  `Not set`. Yii 2 prints `(not set)` in both. The application formatter is set
  to `''` for the grid and `DetailView` carries `Not set` of its own.
- **Row order, again.** `GxActiveRecord::defaultScope()` is overridden by 22
  of the 72 models, mostly to an empty array - meaning no `ORDER BY` at all,
  and rows in whatever order the storage engine produces. `Item` is one of
  them. Applying the inherited `id DESC` to those anyway put every grid and
  every filter dropdown on those tables in an order Yii 1 never showed. Each
  model now carries a `defaultOrder()` read from its own `defaultScope()`.
- **Column types.** Yii 1 runs PDO with `ATTR_STRINGIFY_FETCHES`, so an
  integer 0 arrives as `'0'`. Yii 2 casts it to `0`. The generated option
  helpers all open with `if ($id == null) return $list;` - false for `'0'`,
  true for `0` - so under Yii 2 a status of 0 returned the whole options array
  instead of its label, and the grid raised "Array to string conversion". The
  `LegacyColumnTypes` trait skips the typecast.
- **Unknown properties.** Yii 1 answers null; Yii 2 throws. Views read
  attributes that do not exist - `$data->item` on a model with no `item`
  relation - and Yii 1 rendered an empty cell where Yii 2 returns a 500. The
  same trait restores the null. This hides genuine typos, which is why it is
  written down here.
- **New records carry the column defaults.** Yii 1's `CActiveRecord` fills a
  new record from the table's declared defaults; Yii 2 leaves them null until
  `loadDefaultValues()` is called. Without it a create form shows an empty box
  where Yii 1 shows `0.00`, and an insert writes NULL where Yii 1 writes the
  default.
- **`$this->widget()` echoes even when its result is assigned.** Yii 1's
  widget() writes to the output buffer *and* returns the widget, so
  `$grid = $this->widget(...)` still renders. Yii 2's `::widget()` only
  returns. The page came back 200 with its grid simply absent.
- **Eager loading is part of the result.** Yii 1's `$criteria->with` loads a
  BELONGS_TO relation by JOIN in the same query. Where the listing has no
  `ORDER BY`, that join is what decides which rows the first page shows, so
  dropping it - or using Yii 2's `with()`, which runs a second query - gives a
  page of entirely different rows. The generator emits `joinWith()`.
- **A model needs a search scenario before its grid has filters.** Yii 2 will
  not build a filter input for an attribute that is not safe in the current
  scenario. Models the API port wrote have their own `rules()` with no search
  scenario, so merging the UI into them produced grids with no filters at all
  and no error anywhere.
- **Validation in `search()`.** Yii 1's generated `search()` compares whatever
  is set and never validates. Porting it with Yii 2's usual
  `load(); validate();` shape broke every filter: the `required` rule on
  `title` has no `on` clause, so it applies in the search scenario too, and
  each filtered request failed validation and quietly returned the *unfiltered*
  list with "Title cannot be blank" in the filter row.

- **`var_export($x)` prints.** `CVarDumper::dumpAsString($x)` returns a
  string; `var_export` only does so when passed `true` as its second argument,
  and without it writes to the output. The conversion dropped the argument, so
  all 142 of the application's debug log lines echoed their argument into the
  response. On a page that was invisible among the markup; anywhere a header
  followed it was fatal - "Headers already sent, output started at
  MrsDetail.php:813" cost four API suites a case each.
- **`use Yii;` in a view is a warning, and OPcache hides it.** Views are not
  namespaced, so the import does nothing and PHP says so: *The use statement
  with non-compound name 'Yii' has no effect*. Yii 2's error handler turns that
  E_WARNING into an exception. It is raised at compile time, so OPcache emits
  it once and never again: each regenerated view 500s on its first request and
  looks healthy afterwards, and in production the first visitor after a deploy
  gets the 500.
- **`Yii::$app->request` caches the query string.** `getQueryParams()` copies
  `$_GET` on first read and answers from the copy afterwards. Three admin
  actions write the selected parent id into `$_GET` and *then* read the model
  out of it, which through the cached copy filters nothing: the grid returned
  every row in the table where Yii 1 shows none. These read the superglobal
  directly, which is not a step back from the framework - Yii 2 merges the
  parsed route parameters into `$_GET` itself, in `Request::resolve()`.
- **`'pagination' => false` is an answer.** Reading it as "no page size given"
  put 10 rows on a page where Yii 1 shows all 19.
- **A criteria's column carries the table alias and its attribute does not.**
  Yii 1 writes `$criteria->compare('t.mrs_id', $this->mrs_id)`. Keeping only
  the column and reading `$this->{'t.mrs_id'}` gives null - through the
  `LegacyColumnTypes` trait, silently - so every filter on those grids was
  dead. And because Yii 2 aliases the primary table by its table name, the
  column needs `->alias('t')` on the query before `t.` resolves at all.
- **`CGridColumn::visible` has no Yii 2 equivalent.** A column Yii 1 hides was
  rendered anyway. The `GridView` shim drops them before Yii 2 builds the
  columns, because Yii 2's `DataColumn` rejects the key.
- **A column's `value` is an expression, not an attribute name.** Yii 1
  evaluates it with `$data` and `$row` in scope; a serial-number column is
  `'value' => '++$row'`. Yii 2 reads a string `value` as an attribute name, so
  the serial numbers came out blank.
- **`loadModel($id, 'Other')` means load an Other.** Yii 1's
  `GxController::loadModel` takes the class. Dropping it looked the row up in
  the controller's own table, which usually has no such id: the page answered
  404 where Yii 1 answers 403.

## How CDbCriteria is converted

Per **declaration**, not per variable name, and not once per method. Both
shortcuts produced code that looked converted and was not:

- Keying on the name gave every criteria in a method the same `$query`.
  `FreeItemController::actionItemList()` builds `$criteria` for the item
  details and `$criteria1` for the vendor's items; the second assignment
  overwrote the first, and the page listed `ItemVendor` rows where Yii 1 lists
  `ItemDetail` rows.
- Converting a name once was worse.
  `CustomerController::actionGetCustomerAddress()` writes
  `$criteria = new CDbCriteria` twice - the first consumed by
  `Customer::model()->findAll()`, the second by `City::model()->find()` - and
  the single conversion gave the city lookup `Customer::find()` and `->all()`,
  so `$city->id` ran against an array.

Each `new CDbCriteria` now gets its own region, from the declaration to the
next declaration of the same variable, its own class and finder read from
inside that region, and its own variable. A name the application already uses
is skipped: that same action has its own `$query`, a query string for a curl
call.

`search()` is **rebuilt** from its compares for most models and **converted
whole** where rebuilding would lose something. `MrnDetail::search()` drops
every row whose status is `STATUS_DONE` and, when the date or vendor filter is
set, first resolves those to a set of mrn ids and restricts the grid to them.
None of that is a compare, so the rebuilt method returned rows Yii 1 does not
show - and with pagination off, that was the whole table.

## Access control

Worth stating plainly, because the first version of this port got it wrong.

`accessRules()` is the same on 58 of the 60 controllers: signed in, nothing
further. What differs is whether the controller *also* calls
`checkPermission()` inside its actions, and that splits the application almost
in half:

| | controllers | actions |
|---|---|---|
| at least one action gated by `checkPermission()` | 31 | 115 |
| no gate in any action | 29 | 559 |

So the permission table is enforced on the URL in some controllers and only
drawn from in others. `userRole` throws if the role lacks `userRole/update`;
`paymentMode` hides the Update button and still answers
`/paymentMode/update/id/1`.

The port reproduces each controller's own behaviour rather than picking one and
applying it everywhere - including the 559 actions that are reachable by any
signed-in account. Making those consistent is a behaviour change that belongs
to the owner, not the porter; it is recorded in `docs/live-bugs-found.md` as
found, not fixed.

## Where the port has got to

**All 59 controllers.**

`paymentMode`, `userRole`, `advanceLogs`, `empShift`, `question`, `shift`,
`advancePayment`, `itemExpireItem`, `paymentReport`, `itemCompanyCategory`,
`bill`, `session`, `itemVendor`, `notification`, `creditNote`, `state`,
`city`, `stockLog`, `permission`, `country`, `designation`, `outlet`,
`freeItem`, `mrs`, `organization`, `rolePermission`, `itemTax`, `tax`,
`customer`, `emp`, `orderRefund`, `stockAdjustLog`, `item`, `itemCompany`,
`discount`, `itemCategory`, `itemExpire`, `itemReturn`, `itemReturnItem`,
`itemStock`, `mrn`, `b2bPurchaseBill`, `itemDetail`, `mrnDetail`,
`mrsDetail`, `vendorSchemes`, `orderRefundItem`, `vendor`, `user`,
`purchaseOrder`, `purchaseBillDetail`, `onlineOrder`, `purchaseOrderDetail`,
`site`, `b2bPurchaseBillDetail`, `loyaltyAdmin`, `orderItem`, `purchaseBill`,
`order`.

"All 59" means every controller's pages are compared against Yii 1 and agree,
or are accounted for. What it does not mean is that all 59 are *verified*: a
page that neither stack renders is reported as **nothing compared**, and there
are 49 of those. They are not passes and they are not failures; they are the
suite saying it does not know. Most are pages the permission table refuses to
every role, and those are a live bug, not a gap in the port - see
`docs/live-bugs-found.md`.

`loyaltyAdmin` and `site` have no model, so none of the seven page types the
UI suite is built around exist for them. They are compared as page text
instead, by `tests/port/pagecompare.py`, which the `pages_difftest` suite runs
as part of the regression.

Three controllers took most of the last stretch, and none of the three was
blocked on the port:

- **`purchaseBill`** was blocked on a PHP 8.3 regression in the *Yii 1* tree -
  `TbEditableField::init()` calling `strlen(null)` - not on anything the
  generator did.
- **`order`** renders a checkbox list over 4,976,355 rows on create and
  update. The untouched 5.6 baseline takes 302 seconds on create and fails
  after 190 on update. Both are compared now, with a 400-second budget.
- **`orderItem`** differed in one label, which turned out to depend on the
  database rather than the code.

## A label can depend on the database

`GxActiveRecord::getAttributeLabel()` hands everything to `getRelationLabel()`,
which for a column with no explicit label asks `findRelation()` whether the
column is a foreign key - and `findRelation()` answers from
`$column->isForeignKey`, which is the *schema's* declared constraint, not the
model's `relations()`.

So `BaseOrderItem` declaring `'discount' => array(BELONGS_TO, 'Discount',
'discount_id')` is not enough. If `tbl_order_item` carries no FOREIGN KEY on
`discount_id`, Yii 1 falls through to `generateAttributeLabel()` and prints
"Discount Id"; where the constraint exists it prints the related model's
label, "Discount". The generator reads `relations()` and so always produces
the second.

This was the last difference in `orderItem`, which now matches on all seven
comparisons. It is a reminder that a label in this application is not always a
property of the code.

## The cases the UI suite does not count as passes

Every comparison either matches, mismatches, or is reported as **nothing
compared** - a third outcome, tallied separately, for a page where there was
nothing to be right or wrong about. It exists so that a page which cannot be
checked can never read as a page that was.

Three kinds end up there, and none of them is a way to make a red case green.

**Yii 1 crashes and the port does not.** `itemExpire/update`, `mrn/update` and
`itemStock/admin` return 500 on the untouched 5.6 baseline, confirmed with
`tools/port/baseline_check.sh`; the port renders all three. They are listed in
`known-yii1-failures.txt`, which only accepts a page after it has failed on
:8082 - Yii 1 failing in the 8.3 tree alone would show only that this work
broke it.

These were invisible until the port stopped writing `var_export` output into
its responses. Until then the ported page failed too, the two statuses agreed,
and the comparison passed. A suite can be green because both sides are broken.

**Both stacks render nothing.** `itemReturnItem/create` answers 200 with an
empty body on the baseline, on Yii 1 under 8.3 and on the port. All three
agree, so there is nothing to fix - and nothing was compared either.

**The listing is unordered and paginated.** `stockAdjustLog/admin` has an empty
`defaultScope()` and a `search()` that sets no order, so neither stack defines
which ten of fifty thousand rows appear on page one. Both queries are
equivalent - same joins, same `LIMIT 10`, no `ORDER BY` - but Yii 1 selects
every column of every joined table and Yii 2 selects `t.*`, so MySQL picks a
different plan and a different ten rows. There is no correct answer to match.

`unordered-listings.txt` holds these. Where the whole listing fits on one page
and the two stacks hold the same rows in a different sequence, that is a pass:
the contents agree and only the order, which nothing defines, does not. Where
the rows themselves differ it is *not* a pass - it goes to nothing-compared,
because the page genuinely was not checked. Adding an `ORDER BY` to settle it
would be inventing behaviour Yii 1 does not have; doing exactly that earlier in
this port broke a working Yii 1 page and was reverted in full.

## A form-only property has to be safe, not just declared

`onlineOrder/admin` filters to a date range it keeps in the session: the action
puts it in `$_GET` and loads it back onto the model. The port showed all 51
orders where Yii 1 shows the day's - none, in this data.

Declaring `public $start_date` was not enough. `load()` assigns only attributes
that are *safe* in the current scenario, and `rules()` is never replaced on a
model the API port wrote, because rules decide how that model validates and
saves. So the Yii 1 rule marking these safe never came across, `load()` skipped
them, and the filter silently did not apply. A missing filter does not raise
anything; it just shows more rows.

The merge now adds a `safe` rule for the form-only properties alongside the
existing rules rather than replacing them. These are not columns, so permitting
mass assignment of them cannot write anything to the database - it sets a
public property, which is what Yii 1 does.

**A wrong turn worth recording.** The first diagnosis was that the port blanked
a session key Yii 1 had written, traced through the session file:

    after yii1:   onlineorder_start_date|s:10:"2026-09-19"
    after port:   onlineorder_start_date|s:0:""

That was real but it was a symptom, not the cause, and the write-up of it as a
shared-session defect was wrong. Once the safe rule was in place the key
stopped being blanked. Half an hour went into looking for a session bug that
did not exist, because the observation was striking enough to stop the search
for a duller explanation.

## Access control: what Yii 1 refuses

`BaseUiController` checks only that someone is signed in, on the reading that
Yii 1's `accessRules()` amounts to the same thing. For 43 of the 48 ported
controllers it does, and for a reason worth writing down:
`CAccessRule::isActionMatched` is `empty($this->actions) || in_array(...)`, so
a rule with no action list - or with every entry commented out, which is how
most of these look - matches **every** action. Reading an empty list as
"nothing" is what made a first attempt at this report 59 refused actions, not
one of which was refused.

Read correctly, the application has 22 actions that Yii 1 refuses a signed-in
user outright. Two are in ported controllers: `item/getDiffStocks` and
`item/check`. The other 20 are in `onlineOrder`, `order` and `user`, and will
matter when those are ported.

The generator reads `accessRules()` and writes the refused actions onto each
controller as `deniedActions()`, which `BaseUiController::beforeAction`
enforces. Only actions refused outright are listed - anything a role or an
expression decides is left out rather than guessed at - so the port can refuse
less than Yii 1, never more.

`item/check` remains a difference of a kind: Yii 1 answers 403 and the port
404, because the action does not exist here at all. Both refuse; they disagree
about why.

## Where the sweep has got to

210 actions on both stacks - 176 read-only, 34 whose only side effect is a
session key - and the database checksum around the run is unchanged.

| | |
|---|---|
| answer the same way | 153 |
| **port fails where Yii 1 works** | **0** |
| Yii 1 fails where the port works | 9 |
| both fail | 1 |
| other status mismatch | 0 |
| stray output from the port | 0 |

The nine where Yii 1 fails are its own bugs, each confirmed on the untouched
5.6 baseline and listed in `tests/port/known-action-failures.txt`: the two B2B
sections whose view directories are misspelled, and five `search` actions.
The port renders all nine.

The one that fails on both is `order/userwisePdf`, and only when the session
carries no date range - with one set it renders a PDF on both stacks.

## The action sweep

The UI suite compares six page types per controller. The ported controllers
hold another 208 actions - PDF generation, barcode printing, CSV import, ajax
lookups - and until now no test touched any of them. Every serious fault found
in this port lived there: 142 log lines writing `var_export` output into
responses, a criteria converter handing a second query the wrong table,
`::model()` and `unsetAttributes()` left in ported controllers, Yii 1 scenario
constructors passed to Yii 2 as configuration arrays. The CRUD suite caught
none of them.

`tests/port/action-sweep.py` asks a weaker question of all of them: does the
page answer, and does it answer the way Yii 1 does. That is enough. On its
first run it found 48 actions where the port returned 500 and Yii 1 returned
200 - most of them one bug, `$this->route` in 53 views, which only
`<controller>/search` reaches.

**What it will not request.** `tests/port/classify-actions.py` reads each
action, and the model methods it calls, and holds back anything that writes or
reaches off the server; 99 of the 208 are held back on that basis. Reading only
the action body was not enough - `b2bPurchaseBill/checkConsignment` is two
lines calling a model method that curls a licence server and saves a Setting,
and `customer/sendEmailCustom` sends mail to a hard-coded customer through a
method that `POS_STUB_OUTBOUND` does not intercept. Both were requested before
the classifier followed calls, and the mail attempt reached Gmail with real
credentials.

So the checksum is not decoration: every table is checksummed before and after
and any change is reported. It has been zero on every run, which is the only
reason to trust the static judgement above it.

## Grid headings are compared, and were not

Both of the defects above - the labels and the pager - lived on pages whose
rows matched Yii 1 to the character, through hundreds of green comparisons.
They were in the parts of a grid the suite never read.

`grid_rows()` skips any row containing a `<th>`, which is the heading row and
the filter row both. So a column *heading* had never been compared, and
headings are exactly where a generated label appears. `grid_headers()` now
compares them, one case per ported controller, and found a difference on the
first run it made.

The pager is still not compared by that suite. It is compared for the
model-less controllers, by `pages_difftest`, which reduces a whole page to its
visible text - which is how it was noticed at all.

The general lesson is the one this file keeps relearning: a suite that passes
tells you about the things it looks at, and nothing whatever about the rest.

## What "verified" covers, and what it does not

Each ported controller is compared on **six page types**: the admin grid, its
second page, index, the create form, a view page and an update form.

It is not compared on anything else. The ported controllers hold roughly **230
further actions** - barcode printing, CSV import, PDF generation, stock
adjustment, ajax lookups - and no test exercises any of them. `item` alone has
42. For `paymentMode` the six page types are the whole controller; for `item`
they are a fraction of it.

So a controller listed above has its CRUD pages matching Yii 1. That is not the
same as the controller being proven, and the difference is largest exactly
where the controller is largest.

## Names that exist twice

`Emp`, `Customer`, `Item` and `Order` are each an API controller in
`app2/controllers` *and* a CRUD controller in the web UI. One class cannot be
both, and the API port is the finished one, so the UI controller takes a
suffixed class:

| URL | class |
|---|---|
| `/v2/api/emp/profile` | `EmpController` (the API port) |
| `/v2/emp/admin` | `EmpUiController` |

`Ui::toYii2Id()` maps the route `emp` to the controller id `emp-ui` for these
names, and `Ui::toYii1Id()` strips the suffix again, so views, permissions and
`loadModel()` all still resolve against `emp`. The URL is unchanged.

`customer` and `emp` are ported this way. `item` and `order` are not, for
reasons that have nothing to do with the collision - see below.

## order

`order`'s admin applies a filter the port does not. `item` was in this position
too - its grid listed 101 rows to the port's 11 - and the cause turned out to
be a page size the generator did not read, not anything specific to item. It
is ported and matching now, and every fix it needed generalised.

## Where a listing's order comes from

Three places, and a listing can use any of them:

| | models |
|---|---|
| `$criteria->order` inside `search()` | `itemDetail`, `itemReturn`, `mrnDetail`, `mrsDetail`, `orderRefundItem`, `purchaseOrderDetail` |
| `'defaultOrder'` on the provider's sort | `b2bPurchaseBill`, `item`, `itemExpire`, `itemTax`, `order`, `orderItem`, `purchaseBillDetail`, `stockAdjustLog`, `tax` |
| `defaultScope()` on the model | everything else, inheriting `id DESC` from `GxActiveRecord` |

This document previously said eighteen listings had no `ORDER BY` at all and
asked the owner to add one. That was wrong, three times over, each time from
checking only one of the three places. Fifteen of the eighteen do specify an
order; the generator simply did not read it.

Only three genuinely have none: **`customer`, `orderRefund`,
`itemReturnItem`**. For those, which rows appear on page one is decided by the
query plan, and they cannot be compared between two SQL builders without being
given an order - which is a change to what an operator sees, and so the
owner's call.

Note the orders are not all `id DESC`. `mrnDetail`, `mrsDetail` and
`purchaseOrderDetail` sort by `item.title asc` and `purchaseBillDetail` by
`t.order Asc`, so a blanket `id DESC` would have been wrong for four of them.

Two details the port has to respect:

  - A joined column keeps its qualifier. `item.title` is the right column only
    while qualified; stripped to `title` it is ambiguous or simply another
    table's. Only the main table's alias `t.` is removed, because Yii 2 does
    not use it.
  - `index` and `admin` do not share an order. `index` builds its own data
    provider and never calls `search()`, so it gets only whatever
    `defaultScope()` applies.

## What is not carried across

Three Yii 1 widgets drive JavaScript that is not part of this port: the
bootstrap date picker, the time picker, and the two rich-text editors
(CKEditor and Redactor). The fields render as the plain input underneath -
a text box, a textarea - so the value posted and stored is the same and the
editing experience is not. 107 views use `datepickerRow`, 17 `ckEditorRow`,
16 `redactorRow`.

`CommentPortlet`, which 41 views call, renders nothing - because the Yii 1
class renders nothing either: its `renderContent()` is commented out, and the
running Yii 1 pages emit no portlet markup. Reinstating the comment form it
contains would be a new feature, not a port.

## Testing

`tests/port/ui-difftest.py` reduces each page to the data it carries - grid
rows as text, detail-view label/value pairs, form field names and current
values - and compares those between stacks. `pmui-difftest.sh` runs it for
every controller in `Ui::PORTED` and reports to `run_all.sh`.

An empty comparison is a failure, not a pass. Two pages that both failed to
render, or a session that quietly expired, produce reductions that are equal
and empty; the suite says so rather than reporting a match. Where a page
legitimately refuses - `bill/create` is declared `actionCreate($id)` and
answers 400 without one - the two stacks' status codes are compared instead.

The suite is self-contained: it creates its own administrator, logs in as that
account, and deletes it again, so no real credential is used and the database
is left as it was found. It fails loudly if the login did not take, rather than
comparing two login pages to each other and reporting a match.

## The hooks nothing calls

Six Yii 1 lifecycle hooks were not on the port. They are the one kind of
method whose absence nothing reports: no page references them by name, so the
model loads, the page renders, the suite passes, and the only difference is a
row written on one stack and not the other.

| hook | what it does | what its absence did |
|---|---|---|
| `OrderItem::afterSave` | appends a `tbl_item_velocity` row | the port saved order items and recorded no velocity |
| `Order::afterSave` | `processLoyaltyEarning()` | the port saved orders and credited no points |
| `Order::beforeDelete` | deletes the order's items | orphaned `tbl_order_item` rows |
| `OrderHold::beforeDelete` | deletes the held order's items | orphaned `tbl_order_hold_item` rows |
| `ItemDetail::beforeDelete` | deletes from fourteen tables | orphans in all fourteen |
| `MrsDetail::beforeSave` | computes `ai_qty` on insert | requisition lines stored at the default |

The three cascades stand in for foreign keys the schema does not declare -
`tbl_order_item` has no constraint pointing at `tbl_order` - so nothing but
the hook was removing the dependent rows.

Both of the velocity helpers had come across with `OrderItem`, and both were
dead: `queryRow()` is Yii 1's Command method and Yii 2 spells it `queryOne()`,
and `Yii::log()` does not exist in Yii 2 at all. Either would have been a
fatal on the first call, and neither had ever been called, because the hook
that calls them was the thing that was missing. `merge()` keeps an
API-tracked model's existing methods on purpose - that is what stops a web-UI
port from changing how the API saves - so a method written before an idiom
existed keeps the spelling it was written with. Both idioms are now in
`dao_idioms()`, which all three translators call, and `repair.py` applied them
to the four files that still carried them.

`tests/port/hook-parity.py` checks the whole set on every run. It is a name
check; whether the bodies agree is what the write sweep is for.

## The write sweep refuses to run a maintenance script

`order/updatetax` rewrote 8,925 order items during a sweep and kept writing
for hours after the request was abandoned. `loops_over_saves()` now refuses
an action that saves inside a loop, unless it either reads the request - which
is what an ajax handler does, over the rows it was posted - or takes a
required parameter, which scopes it to one row. Fourteen actions are refused
and named in the output rather than folded into the skip count.

The restored rows came from the 5.6 database, which this sweep never touches.
That is the second time the baseline has served as the backup for a sweep that
wrote something it should not have; the first was four `tbl_item_expire_item`
rows, and the guard added then only covered actions whose name begins with
`delete`.

## Dead code hides a fatal, and porting a hook wakes it

Adding `OrderItem::afterSave` and `Order::afterSave` broke the three order
suites at once - 23 cases, every one of them a 500 from the port's order API.
Nothing about the hooks was wrong. They were the first thing ever to call the
methods beside them, and those methods had four separate faults between them,
each one fatal on the first line that ran:

| in | written | PHP 8.3 in `app\models` |
|---|---|---|
| `Order::processLoyaltyEarning` | `LoyaltyService::` | `app\models\LoyaltyService` - it is a component |
| `LoyaltyService` | ported without `processOrderEarn` | undefined method |
| `OrderItem::updateItemVelocity` | `PDO::PARAM_INT` | `app\models\PDO` - PHP does not fall back to the global namespace for a class |
| `OrderItem::updateItemVelocity` | `catch (Exception $e)` | `app\models\Exception`, so the catch never matches |

Each had been in the tree since the model was written. The suites were green
the whole time, the action sweep answered 200 on every page that loads an
order, and none of it meant anything, because nothing reached the lines.

That is the shape of the risk in a port this size: a method that is never
called is not ported, it is only copied, and the copy is not checked by
anything until the day something calls it. Two checks now ask the questions
that do not need a caller:

  - `tests/port/class-refs.py` resolves every class name in `app2` the way PHP
    would, through the file's namespace and `use` statements, and reports four
    outcomes separately - the class is in the port under another namespace or
    another capitalisation (`B2BPurchaseBill` against `app\models\B2bPurchaseBill`,
    four call sites, fixed); the Yii 1 tree has it and the port does not
    (`TblItemNewTax`, now ported); it is named without the leading backslash a
    global class needs; or it is in neither tree, which is 30 names belonging
    to other applications this code was cut from - `Nozzle`, `Tank`,
    `Journey`, `MerchantStore` - where both stacks fail the same way and there
    is nothing to reproduce. 1,885 references, 0 outstanding.
  - `tests/port/static-calls.py` resolves the method too, following `extends`
    and traits, and stops at the first class whose ancestry leaves the port so
    that it never guesses about a Yii base class. 4,147 calls, 0 outstanding.

`UserIdentity` is recorded in the first as a deliberate exception. The port
does not own login: `BridgedUser` reads the signed-in user id out of the Yii 1
session and is configured with `enableSession = false`, so a Yii 2 login would
have nowhere to write. The consequence is worth stating plainly - **a POST to
`/v2/user/login` fatals.** GET renders the form, which is why the page
comparison passes, and the suites sign in through the bridge rather than
through the form, which is why nothing else noticed. Users log in at `/`.

## What the write sweep will not run

Nineteen actions are refused and named in the output rather than counted as
skips. Each saves models inside a loop and does not read the request, which is
the shape of a maintenance script rather than a form handler - an ajax handler
also saves in a loop, but over the rows it was posted, and does nothing
without them.

A required parameter was briefly taken to mean an action is scoped to one row.
It does not. `order/updateOrders($id)` takes a required `$id` and then walks
`id >= $id` a thousand rows at a time, rewriting each order's total from its
lines; the sweep ran it before the exemption was withdrawn, and those thousand
totals only happened to be right already - they checksum identical to the 5.6
database. A parameter says where a loop starts, not how far it goes.

So six ordinary row-scoped actions - `mrn/approve`, `onlineOrder/hold`,
`itemDetail/makeInactive` and their kind - are refused along with the thirteen
that deserve it, and their writes go uncompared. That is the trade, taken
deliberately: a wrong refusal costs coverage, a wrong run costs rows.

## An action that ends the session has to be measured twice over

`user/logout` was the sweep's last difference: Yii 1 updated `tbl_user` and the
port wrote nothing. Both stacks run the same two lines - `$user->logout()`
sets `last_action_time` when the caller is not a guest - and the port's were
right.

The sweep runs Yii 1 first and the port second, from one cookie jar. Yii 1's
logout ended the session, so the port's half arrived as a guest, redirected,
and wrote nothing. The session is now re-established before each half rather
than once per action, and before the log position is noted, so that signing in
does not show up as a write of the action's own.

It is worth saying what this was not: nothing in the port was wrong, and the
sweep reported a difference for four days. A differential harness is a program
like any other, and its own state is as capable of producing a difference as
the code it is comparing.

## The port owns login

Until now Yii 2 only observed: `BridgedUser` read the signed-in user id out of
the Yii 1 session, `enableSession` was false, and `POST /v2/user/login` was a
fatal. The port could not be exercised without Yii 1 in front of it.

It can now. `app\components\UserIdentity` is the port of Yii 1's
`CUserIdentity` subclass, split the way Yii 2 splits it: this class checks the
credentials and carries the error code the login controller switches on, and
`app\models\Identity` is what gets logged in. Both call sites - the login form
and the API's auth-code resume in `AuthSession` - log in the Identity for the
id that was resolved, so the signed-in identity is the same type on the login
request as on every request after it.

`BridgedUser` keeps its own session now and falls back to the Yii 1 bridge:

  - Yii 2's session keys are read first. Reading the bridge first would let a
    stale Yii 1 session override a login performed here.
  - The bridge is still read, and still with `setIdentity()` rather than
    `switchIdentity()`, so observing Yii 1's state writes nothing. A session
    made at `/` continues to work at `/v2`.
  - It does not work the other way. Yii 1's `WebUser::init()` rewrites
    `pos_bridge_auth` on every request and clears it whenever Yii 1 thinks it
    is a guest, so anything Yii 2 wrote there would not survive the next
    request to `/`. **A login at `/v2` signs you in to `/v2` only; a login at
    `/` signs you in to both.** That asymmetry goes away when Yii 1 does.
  - `enableAutoLogin` stays off. `Identity::getAuthKey()` returns null and
    `validateAuthKey()` returns false, because `tbl_user` has no column to hold
    one, so an auto-login cookie could not be validated if one were issued. The
    visible effect is that "remember me" does not survive closing the browser
    on `/v2`. Giving it one is a schema change.

Three things had to be fixed before the form could render at all, and all three
are the same story as the lifecycle hooks - code that was copied, never called,
and therefore never checked:

  - `new UserIdentity(...)` resolved to `app\controllers\UserIdentity`.
  - `BaseUiController::beforeAction()` sent **every** guest to `/user/login`,
    including a guest asking for `/v2/user/login`, so the port's own form
    answered 302 to Yii 1's whoever asked. There is now a `guestActions()`
    hook - empty for every controller but `User`, which returns the actions
    Yii 1's first `accessRules()` entry grants to `'*'` - and a guest is sent
    to the login form of the stack they were already on.
  - `column1` wrapped `main.php`, which is the port of `admin_layout.php` and
    reads the signed-in user's `role_id`. Yii 1's `column1` wraps the theme's
    `main.php`, a plain shell with no user in it. That file is now ported as
    `guest.php`, and the three pages a guest can reach - login, recover,
    passwordexpired - render in it, as they do in Yii 1.

A fourth turned up once the suite could try a wrong password:
`Yii::$app->request->getUserHostAddress()`, Yii 1's name for `getUserIP()`, on
the `ERROR_PASSWORD_INVALID` branch. A correct password never reached it.

`tools/port/login_difftest.sh` covers the flow on both stacks: the form
renders, the right credentials make a session that works, a session made at `/`
still reaches `/v2`, a wrong password and an unknown user are refused with no
session and the same message, a POST with no CSRF token is refused by the port,
logout ends the session, and a guest on `/v2` is sent to the port's own form.
Eight cases, and before them the login path had none - which is the whole
reason four fatals could sit in it.

The `UserIdentity` entry in `class-refs.py`'s exception list is gone rather
than reworded. An exception that outlives its reason is how a check stops
checking.
