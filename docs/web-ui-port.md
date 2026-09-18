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

33 of the 59 controllers, 200 comparison cases:

`paymentMode`, `userRole`, `advanceLogs`, `empShift`, `question`, `shift`,
`advancePayment`, `itemExpireItem`, `paymentReport`, `itemCompanyCategory`,
`bill`, `session`, `itemVendor`, `permission`, `notification`, `creditNote`,
`state`, `city`, `stockLog`, `country`, `designation`, `outlet`, `freeItem`,
`mrs`, `organization`, `rolePermission`, `itemTax`, `tax`, `customer`, `emp`,
`orderRefund`, `stockAdjustLog`, `item`.

26 remain: 363 actions, 283 view files, ~42,800 view lines.

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
