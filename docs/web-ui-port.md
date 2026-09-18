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
- **Validation in `search()`.** Yii 1's generated `search()` compares whatever
  is set and never validates. Porting it with Yii 2's usual
  `load(); validate();` shape broke every filter: the `required` rule on
  `title` has no `on` clause, so it applies in the search scenario too, and
  each filtered request failed validation and quietly returned the *unfiltered*
  list with "Title cannot be blank" in the filter row.

## Access control

Worth stating plainly, because the first version of this port got it wrong.

Yii 1 gates these pages with `accessRules()`, and for 58 of the 60 controllers
that rule is exactly "must be signed in". `checkPermission()` - the
`tbl_permission` lookup - decides which sidebar links and row buttons are
*drawn*. It is not a gate on the URL: Yii 1 hides the Update button and still
answers `/paymentMode/update/id/1`.

The port reproduces that, including the hole. Enforcing the permission on the
action instead would be a tightening, and a tightening is a behaviour change
that belongs to the owner, not the porter. It is recorded in
`docs/live-bugs-found.md` as found, not fixed.

## Testing

`tests/port/paymentmode-ui-difftest.py` reduces each page to the data it
carries - grid rows as text, detail-view label/value pairs, form field names
and current values - and compares those between stacks. `pmui_difftest.sh`
wraps it for `run_all.sh`.

The suite is self-contained: it creates its own administrator, logs in as that
account, and deletes it again, so no real credential is used and the database
is left as it was found. It fails loudly if the login did not take, rather than
comparing two login pages to each other and reporting a match.
