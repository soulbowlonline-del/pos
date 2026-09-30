# DASPOS — Yii 1 / PHP 5.6 to Yii 2 / PHP 8.3

Handover for whoever picks this up next, human or otherwise. It says what
exists, how to run it, what has been decided, and what has not. The reasoning
behind individual changes is in `docs/`, not here.

**Nothing in this file is a credential.** See *Secrets* at the end.

---

## What this is

A point-of-sale application, ten years old, being moved from Yii 1.1 on
PHP 5.6 to Yii 2 on PHP 8.3 with MySQL 8. The move is done by the strangler
pattern: both applications serve from one container against one database, Yii 1
at `/` and the port at `/v2`, so any page can be compared against its original
at the same moment on the same data.

The port is complete and has been through a first day of real use. 59 of 59
controllers, 69 of 69 API actions, **7,382 differential cases green**, and a
sweep confirming that every action Yii 1 serves the port serves at the same
URL. The invoice canary matches byte for byte and the PHP error log is empty.

What that first day taught is in *The lessons* below, and it is the most
useful thing in this file: the suites were green throughout while the
application was unusable, because they were all asking about markup and every
fault was in the wiring behind it.

## Where it runs

| | |
|---|---|
| host | `31.97.186.151`, SSH is key-only: `ssh -i ~/.ssh/daspos_key root@31.97.186.151` |
| PHP 5.6 baseline | `/root/pos/pos`, container `pos-php-legacy`, port **8082** |
| the working tree | `/root/pos/pos83`, container `pos-php-83`, port **8084** — this git repo |
| database | container `pos-mysql-8`, schema `pos_live`, on India time (`+05:30`) since 27 Sep 2026 |
| baseline database | container `pos-mysql-legacy` — **never written to**, see below |
| generators and suites | `/root/pos/*.py`, `/root/pos/*_difftest.sh`, mirrored into `tools/port/` and `tests/port/` |

Within `pos83`, `protected/` is the Yii 1 tree running under PHP 8.3 and
`app2/` is the Yii 2 port. Both are served by the same container.

**This host is the test server.** Production is the store's own machine,
`61.2.241.71` (on the store's LAN, `192.168.225.51`), running `main`-era code.
The owner's standing instruction, 27 Sep 2026: **never change anything on
production.** It appears in this server's logs only as a client IP — the
store's connection uses this test server too.

The `:8082` baseline matters more than it looks. It is the only copy of the
application and its data that predates both this work and PHP 8.3, so it is
the tiebreaker whenever the two stacks disagree, and it is where deleted rows
have been recovered from twice. Do not write to it.

## URLs

| | |
|---|---|
| the port, web | `http://31.97.186.151:8084/v2` |
| the port, login | `http://31.97.186.151:8084/v2/user/login` |
| the port, API | `http://31.97.186.151:8084/v2/api/<controller>/<action>` |
| Yii 1, for comparison | the same without `/v2` |
| production | `http://61.2.241.71/pos/` — a different machine, untouched by any of this |

The API answers Yii 1's URL spelling, camelCase and path-format parameters
alike: `/v2/api/item/search/name//rate//title/pepsi` works, because that is
what the .NET client sends. See `docs/web-ui-port.md`.

## Running the suites

```bash
ssh -i ~/.ssh/daspos_key root@31.97.186.151
cd /root/pos && ./run_all.sh          # everything, about 2 hours
./itemorder_difftest.sh               # one suite
python3 pos83/tests/port/api-routes.py
```

`run_all.sh` reloads the fixtures before each suite and tears them down at the
end; it prints one `passed / mismatched` line per suite. A suite reports
`nothing-compared` separately for cases where neither stack produced anything
to compare — those are not passes, and the count is there so a coverage gap
cannot hide inside a green run.

The last full green run: **7,382 cases, 0 mismatched**, invoice canary 6/6,
PHP error log empty, 0 fixture rows left behind.

The last run, 26 Sep 2026 (after the SQL-injection fixes), was **not** green
and is not a baseline: 7,370 cases, `rows_difftest` 1 mismatch and
`itemorder` / `ordertest` / `punch` 7 / 6 / 8. An earlier run that day was
killed part-way, skipped the teardown, and left its fixture orders behind —
the exact case the comment at the top of `teardown.sql` describes. The
teardown then aborted on a foreign key (fixture refunds pointing at fixture
orders). Rerun on a clean database before reading anything into those numbers.

**Do not run `run_all.sh` against `pos_live` while anyone is using the
server.** It is not a test database: the store has been doing real GRNs, MRS
and app orders on `:8084` since 17 Sep. See lesson 5 — on 26 Sep the teardown
deleted real orders, a refund and a credit note, and a fixture GRN made the
store's GRN and MRS screens unusable while the suite ran.

### The suites, and what each is for

The first seven are structural and need no HTTP:

| suite | asks |
|---|---|
| `rows_difftest` | has any table lost rows since the 5.6 copy |
| `deletes_difftest` | is every DELETE in the harness scoped to a fixture |
| `hooks_difftest` | does every Yii 1 lifecycle hook have a counterpart |
| `classrefs_difftest` | does every class name in `app2` resolve |
| `staticcalls_difftest` | does every static call have a method behind it |
| `login_difftest` | does login work on both stacks |
| `apiroutes_difftest` | does every API action answer at *Yii 1's* URL |
| `searchparity_difftest` | does every search filter Yii 1 applies get applied |

And five more that are run by hand rather than by the runner, because each
takes a while and none of them needs fixtures. The last three exist because
something reached the owner that every suite above had passed:

| check | asks |
|---|---|
| `tests/port/widget-sweep.py` | does a widget register a script where Yii 1's does |
| `tests/port/route-sweep.py` | does every web action answer at the same URL |
| `tests/port/ajax-sweep.py` | do the 72 ajax endpoints answer byte for byte |
| `tests/port/crud-sweep.py` | do the grids' search filters return the same rows |
| `tests/port/view-refs.py` | does every view the port renders exist on disk |
| `tools/port/missing_methods.py` | is a method called on the port that it does not define |

The rest drive HTTP and compare responses, database rows, outbound calls and
rendered pages. `pmui_difftest` is the slow one — 359 pages, about 50 minutes.
`writes_difftest` reads the MySQL binary log to compare what an action *wrote*
rather than what it answered.

## The lessons, which are the point of this file

Everything painful in this port had the same shape, and the suites did not see
any of it until something forced the question. The first four came from the
port; five and six from 26–27 Sep 2026.

**1. A green suite can mean nothing.** Two stacks reading one database cannot
see damage they share. A fixture teardown scoped by value rather than by id
deleted 601 real credit notes, and every run stayed green for four days
because both halves saw the same missing rows. `rows_difftest` exists for
this, and it caught the second occurrence on its first run.

**2. Code that is never called is not ported, only copied.** Six Yii 1
lifecycle hooks were missing. Porting them woke four fatals that had sat in
the tree since the models were written — a class resolved into the wrong
namespace, a method that was never ported, `PDO::` without a leading
backslash. `classrefs_difftest` and `staticcalls_difftest` ask those questions
without needing a caller.

**3. A differential test compares what it is told to compare.** The API suites
called each action twice under two different names, the Yii 1 spelling for one
stack and the port's for the other, so 69 of 69 green comparisons exercised
URLs no client would ever send. Every multi-word API endpoint was a 404 for
the .NET application and the Android app. `apiroutes_difftest` asks the other
question.

**4. A page that renders is not a page that works.** Every fault found on the
first day of real use had the same shape: correct markup, nothing behind it.
Element ids the javascript could not find, CSRF the javascript did not send,
seventy-two actions appending an error to their own output, six widgets that
rendered an input and bound nothing to it. Every page comparison passed
throughout, because a page comparison reads what the markup carries and all of
that was right. `docs/web-ui-port.md` has the full account under "The markup
was never the problem"; the three sweeps above are what ask about behaviour
instead.

**5. "Above 9990000 is a fixture" stopped being true.** The fixtures insert rows
with explicit ids around 9,990,000, and MySQL moves the table's AUTO_INCREMENT
past the highest id. Every real order, GRN and MRS created after that got an id
in the fixture range, and `teardown.sql` deletes by that range. On 26 Sep it
deleted 2 real app orders, 3 held orders, their lines, a store refund and its
credit note. They were restored from the binary log (see *Data changed directly
in `pos_live`*). The counters are still in the fixture range — open decision 6.

**6. An error response can be load-bearing.** The GRN (`purchaseBillDetail/index`)
and MRS screens call `ajaxItems` / `ajaxMrsNo` on page load with an empty id.
Under the old concatenated SQL that was a syntax error and a 500, which the
javascript ignored. Binding the id turned it into a quiet 200 with no match;
the page then checked an empty barcode, alerted "Scanned Item is not of
Active" and reloaded forever. `PostId::get()` now answers 400 for a
non-integer id so those screens behave as before. When hardening a legacy
input, keep the failure a failure.

When something is reported broken from outside, read the container's access
log before writing a test:

```bash
docker logs --since 1h pos-php-83 2>&1 | grep -E '^<their-ip>' | tail -40
```

Both API failures above were sitting in it with their 404s.

## Decisions still open — these need the owner, not a developer

1. **`user/delete`** cascades through `MerchantStore`, `Category`, `Product`
   and `PromotionalAdd`, none of which exist in either tree. Yii 1 fatals
   rather than cascading, which is the only reason orders are not being
   orphaned. Deliberately not ported; it is the one allowed exception in
   `hook-parity.py`.
2. **`order/updatetax` and `order/updateDetail`** are maintenance scripts left
   in a controller, reachable by plain GET, that rewrite billing history —
   `updatetax` rewrites every order item in a fortnight of 2021 hard-coded in
   its body. Eleven more of the same shape are listed by `writes_difftest`,
   which refuses to run them.
3. **`POST /v2/user/login` works, but a `/v2` login does not sign the user in
   to `/`.** Yii 1 clears the bridge key on every request where it thinks it
   is a guest. This resolves itself when Yii 1 is retired; until then, log in
   at `/` to be signed in to both.
4. **The live application bugs** in `docs/live-bugs-found.md` were fixed on
   27 Sep 2026, in both trees, on the owner's instruction (see *What the owner
   asked for*): bill prefix, `getSgstPercent()`, `getMainDiscount()` and the
   API customer `state_id`. `getIgstPercent()` returning 0 turned out to be
   deliberate - a till sale charges IGST as CGST + SGST - and is left, with a
   comment. The stacks still agree: every fix went to both in one commit.

5. **Order SMS is off in both trees.** `Order::SendSms()` returned early on
   the owner's instruction, 21 Sep 2026, in `app2/models/Order.php` and
   `protected/models/Order.php` at the same point, so the stacks keep
   agreeing. The uengage call below the `return` is dead but left in place as
   the record of what it sent. WhatsApp through Interakt is untouched and
   still live. Turning order SMS back on is an owner's decision, and it must
   be made in both files or the suites will diverge.

Added 27 Sep 2026:

6. **AUTO_INCREMENT counters are in the fixture range** (lesson 5). On 27 Sep:
   `tbl_order` ~9,994,426, `tbl_order_item` ~9,993,687, `tbl_purchase_bill`
   9,990,017, `tbl_purchase_bill_detail` 9,990,080, `tbl_mrs` 9,990,603,
   `tbl_mrs_detail` 9,990,819. They cannot simply be lowered, because real rows
   now hold ids up there (e.g. purchase bills 9990013–9990016, orders 9994376
   and 9994377). Either move the fixtures to their own database, or make every
   teardown delete by an explicit fixture-id list and never by range. Until
   then, do not run `run_all.sh` on this server.
7. **Deploy the stock fix to the store's own server** (`192.168.225.51/pos`,
   also reached as `61.2.241.71/pos`). The lost-stock bug was reported from
   there; it is fixed only in these trees. Which codebase that server runs
   has not been confirmed from here.
8. **Review the stock errors the bug left behind.** `/root/pos/dumps/stock_lost_items.tsv`
   (158 items) and `stock_lost_incidents.tsv` (353 incidents, Jan 2019 –
   16 Sep 2026, from the production copy): 75 GRNs erased (~2,590 units never
   added) and 278 sales never deducted (~600 units). Worst: BANANA, VERKA MILK
   GREEN 500ML, EGG, VERKA DAHI 170GM, VERKA PANEER 200GM. Physical counts may
   already have corrected some; check against a count before adjusting.
   Anything after 16 Sep is only on the store's server.
9. **Access control on Yii 1 ajax actions.** One of them answered an
   unauthenticated request (it returned the new 400, so the action ran)
   although `accessRules()` restricts it to `@`. The SQL behind it is fixed,
   but the access question is open; check how `accessControl` treats these
   actions before relying on it anywhere.
10. **Application bugs noticed, not changed:** `ItemStockController::actionCreate`
    doubles `purchase_qty` on an existing batch (it `setAttributes()` the posted
    quantity, then adds it again; the balance is correct); vendor-return
    (`type_id` 3) stock-log rows carry wrong previous/current figures;
    `api/item/updateStock` builds an `ItemStock` row and never saves it (the
    `save()` is commented out).

## Known coverage gaps

- 26 `pmui` comparisons verify nothing. The suite prints the reason for each,
  which is the point of counting them separately: ten answered 500 on both
  stacks, eight 403, four 404, two 302, one 400, and one renders an empty
  page on both. Not port defects — the two stacks agree, and the 500s are on
  the untouched 5.6 baseline as well — but not coverage either. Run
  `pmui_difftest.sh` and read the `none` lines for the current list.
- The `toArray()` of ten models is not ported. Nothing on the port calls them:
  the payload builders that would (`Order::toArray1`, `ItemDetail::toArray1`,
  `toOnlineOrderArray`) are themselves unreached. Latent, not live — but this
  is exactly the shape that produced four fatals when the lifecycle hooks were
  put back, so treat wiring any of them up as work that needs its own pass.
- 19 write actions are refused by `writes_difftest` because they save inside a
  loop without reading the request. Thirteen deserve it; six are ordinary
  row-scoped work whose writes nobody has compared.
- Actions whose name begins with `delete` are refused outright, since
  `itemExpireItem/delete` removed four real rows on a plain GET.
- Three `creditNote` actions answer 403 on both stacks and nobody has
  explained why. The permission rows exist under both spellings of the
  controller name and both are granted to role 1. Unresolved, and both stacks
  agree, so no suite reports it.
- The owner reported the search button failing on `mrsDetail/admin` after the
  element-id fix went in; it could not be reproduced from here — the filter
  posts and the rows come back. Most likely a cached copy of the old
  javascript in the browser. If it recurs, get the browser console error
  before writing a test: this was the shape of every fault in lesson 4, and
  none of them was visible in the markup.

## What was done, in order

The API port came first and is described in the commits before this branch's
web-UI work. Then, roughly:

1. The 59 web-UI controllers, generated by `tools/port/port_model.py`,
   `port_controller.py` and `port_views.py` and corrected by hand where the
   generators could not tell.
2. Action sweeps — every routable action requested on both stacks and compared
   by status, then by what it wrote, using the binary log.
3. The lifecycle hooks, and the four latent fatals porting them exposed.
4. Login: `UserIdentity`, the guest layout, `guestActions()`, and the eight
   `login_difftest` cases.
5. The API URL contract: `ApiUrlRule`, camelCase action ids, and Yii 1's
   path-format parameters — which is what the .NET client and the APK send.
6. The first day of real use, which found six systemic faults in a day and is
   worth reading as a group rather than a list: element ids the application's
   own javascript could not find, CSRF the port validated and Yii 1 never
   sends, seventy-two actions appending an error to their own output, path and
   numeric-id routes answering 404 and 400, and six widgets that rendered an
   input and bound nothing to it. See *The lessons*.
7. The three behaviour sweeps written in response — widget, route and ajax —
   plus `search-parity` and `crud-sweep`. Each exists because something got
   past every suite that already ran.

8. **26–27 Sep 2026, fixes on both stacks.** Unlike the port work these change
   behaviour on purpose, and each went to both trees in the same commit so the
   suites keep agreeing. On `phase2/php83-yii1132` (and, at the owner's
   request, the matching baseline commits on `main` — see *Conventions*):
   - `c564d05`, `77165a6`, `f02da79` — SQL injection: every
     `"<col> =" . $_POST[...]` / `"<col> !=" . $_POST[...]` condition in the
     controllers (`ajaxItems` in seven controllers, `Item` ajaxUpdate /
     ajaxExpireItems, vendor filters, `idList` lookups, `grn_no`, the
     "every user except" lists) now goes through `PostId::get()`
     (`protected/components/PostId.php`, `app2/components/PostId.php`): an
     integer is bound, anything else is a 400. `f02da79` is the correction
     for lesson 6. Checked with `php -l` only; `run_all.sh` was not green,
     see *Running the suites*.
   - `c564d05` also sets `restart: unless-stopped` on the php services of both
     compose files. Only MySQL restarted on its own; a host reboot left the
     POS down.
   - `bba1003` — `OrderItem::beforeValidate()` stamps `create_date` again. The
     port had kept `create_time` and `create_user_id` and dropped it, so lines
     saved through `/v2/api/item/order` had `create_date` 0000-00-00 and
     `/v2/order/groupTax` (and the HSN report) showed no tax for them.
   - `f860517` — `update_time` / `mrs_update_date` stamped on update in eight
     v2 models, with each Yii 1 model's own condition (always, or only when
     empty).
   - `c08eb31` — **stock lost-update fix.** Every stock change read the
     `tbl_item_stock` row, computed the new balance in PHP and saved the whole
     row, so a GRN and a sale of the same batch in the same moment erased one
     another (reported from the store: item 994, batch 27864, GRN +144 logged
     -59 → 85, next sale logged -59 → -61). The `SELECT ... FOR UPDATE` already
     there ran outside a transaction and after the save. Now
     `ItemStock::addToBalance($delta, $purchaseDelta)` applies
     `balance_qty = balance_qty + delta` in one UPDATE, and
     `saveExceptQty()` / `saveExcept()` save the other columns without writing
     the quantities back. Used by GRN approval, `Order` / `B2bOrder`
     `UpdateStock` (all three branches), stock adjust (web and app),
     `itemStock/create`, expiry, vendor returns and refunds, in `protected/`
     and `app2/`. Reproduced first in a scratch database with the real
     `Order::UpdateStock`: 1 GRN + 20 sales at once was 11–15 units wrong in
     5/5 rounds; 2 sales just before a GRN lost the GRN in 3/10. After the
     fix, 27/27 rounds exact on PHP 5.6 Yii 1, PHP 8.3 Yii 1 and the port;
     single-request results unchanged for negative, zero and multi-batch
     stock. The scratch databases were dropped afterwards.
9. **27 Sep 2026, deployed to this test server: `7a67ab6` → `2c89118`, then
   `004592a`.** `2c89118` is the twelve commits of the overnight audit (the
   six `Port …` commits, row order, the known bugs, the web-root
   `.htaccess` rules, MySQL on India time, the duplicate-bill guard).
   - ~10:51 IST, ahead of the rest: `/.env` and `/.git` were **downloadable
     from the internet** (the repo is the document root; both answered 200).
     The eleven `.htaccess` files of `16b3611` were applied alone first; after
     that `/.env` and `/CLAUDE.md` answer 403 and `/.git/*` 404, and every
     asset of both login pages and the uploaded bills still load. No request
     for `/.env` or `/.git` from outside the host appears in the container
     log, which starts at 17 Sep; before that is unknown (see *Secrets*).
   - Deploy, ~10:52–10:58 IST: full dump
     `/root/pos/dumps/pos_live-before-deploy-20260927-1052.sql.gz` (369 MB,
     81 tables); `git pull --ff-only`; `php -l` clean on all 186 changed PHP
     files; `db_changes/2026-09-27-order-request.sql` (creates
     `tbl_order_request`, nothing else); `docker compose -f
     docker-compose.php83.yml up -d db`, which recreated only `pos-mysql-8`
     on its `pos83_db_data_8` volume — MySQL was down ~32 s (one
     `countOrders` poll failed at 10:57:18), and `NOW()` now matches IST.
   - Checked after, by the owner and from the logs: GRN and MRS tabs, a bill
     print, an MRS edit (68211), an MRN edit and PDF (64050), a GRN approval
     through `/v2` (bill 62824, RGST-6011 — every stock-log row adds up), and
     one till-app sale (`/v2/api/item/order`, bill 96794): one request, one
     order, stock deducted once, `create_date` set. `tbl_order_request` stays
     empty because the apps send no request id yet; the content-based
     10-second check covers them meanwhile. The 400s the GRN/MRS/MRN pages
     log on load are the empty-id lookups of lesson 6, as designed. **Stock
     adjust was not tested** — the owner has no access to the Android app.
   - `004592a`: `/v2/purchaseOrder/printPdf` answered 500 after the deploy.
     Not a regression: the email view `mail/purchase_order_pdf` has read
     `$pomodel` since the first commit while both controllers pass `po`; Yii 1
     only logged a warning, Yii 2 raises it. Both controllers now pass both
     names; the owner confirmed the PDF downloads. `main` is unchanged here —
     it only logs the warning there.
   - Rollback, if ever needed: `git checkout 7a67ab6 -- .` for the code and
     the dump above for data; the table and the time zone are additive.

`docs/web-ui-port.md` is the long form, written as the work happened.
`docs/live-bugs-found.md` records the application's own bugs, found by
comparison and left alone.

## Data changed directly in `pos_live`, 26–27 Sep 2026

None of this is in git. Backups are in `/root/pos/dumps/`.

- **Full dump before any restore:** `pos_live-before-restore-20260926-1818.sql.gz`.
- **Deleted by `teardown.sql`** (lesson 5) and then **restored** row for row
  from the binary log (`log_bin` is on, `ROW`/`FULL`, 30-day retention;
  `mysqlbinlog` was installed on the host from `mysql-server-core-8.0`):
  orders 9994376 and 9994377 (bills 96792, 96793), their 15 lines, held
  orders 9990336–9990338 and 8 hold lines, refund 13239 (₹210, of bill
  96793) with 3 refund lines and credit note 16124, and MRS line 9990815.
  34 rows; the SQL is `restore_34.sql`. Real vs fixture was decided by
  matching each row's create time to a non-harness request in the access log.
- **Fixture data removed:** the leftover fixture orders and refunds (via
  `teardown.sql` after deleting 7 fixture refunds that blocked it on a foreign
  key — one of the 7, refund 13239, turned out to be real and is restored
  above), and 12 purchase orders 63884–63895 with 24 lines created overnight
  by `porttestadmin` (backup `test-POs-63884-63895-20260927-0010.sql`).
- **Backfilled:** `create_date = DATE(create_time)` on the 15 lines of orders
  9994376 and 9994377 (see `bba1003`).
- **Not deleted, and real:** purchase bills 9990013–9990016 — GRNs entered by
  the store after the counters moved into the fixture range — and, from the
  27 Sep testing, purchase bill 9990017 ("test 1234", still open) and order
  9994426 (bill 96794). New rows keep landing in the fixture range until open
  decision 6 is settled.
- **Added on 27 Sep by the deploy:** table `tbl_order_request` (empty).

## What the owner asked for, and what each request produced

The owner's instructions drove the order of this work, and several of them are
the only record of a decision. Condensed, in sequence:

- *"Finish the port to Yii 2"* — the API first, then the 59 web-UI
  controllers, then the differential suites that verify them.
- *"Fix the live application bugs"* → *"on second thoughts let the bugs be"*
  → *"revert"*. The five bugs in `docs/live-bugs-found.md` were fixed and
  then reverted on this instruction, and reproduced deliberately until
  27 Sep 2026, when the owner asked for them to be fixed (below). The rule
  stands: never fix one stack without the other in the same commit, or every
  suite comparing them goes red.
- *"Show me the URL where I can access the migrated code"* — cutover scope was
  set here: port the login, keep both stacks running. Hence `/` and `/v2`
  side by side, and open decision 3.
- *"The /api is not working in the .NET application and the Android APK"* —
  produced `ApiUrlRule` and lesson 3. The clients were never pointed at the
  port during the API work; they send camelCase action ids and path-format
  parameters, and every multi-word endpoint was a 404.
- *"Items are not coming for billing, I can't access the item master"* — the
  generated `Item::search()` had lost its hand-written conditions. Restored:
  prefix `LIKE`, the session item name, vendor scoping, the barcode lookup
  through `ItemDetail`, the tax subquery.
- *"Most of the menus inside the ERP are broken"*, *"the search button is not
  working"*, *"the GRN is showing the wrong vendor's items"* — one report
  after another, each a different symptom of the six faults in lesson 4. They
  are listed together there because they were found separately and are one
  problem.
- *"Not getting any WhatsApp bill"* → *"turn it off, I will only test with my
  personal number"* → *"remove the uengage API"*. See open decision 5 and the
  stub note under *Before this goes to production*.
- *"Test all the menus with search, add, save, update"* → *"everything, in one
  sweep"* — `crud-sweep.py`, `form-sweep.py`, `search-parity.py`.
- *"Port the ckeditor and redactor widgets too"* → *"sweep the rest of the
  widgets for the same thing"* — the six stub widgets, and `widget-sweep.py`
  so that the next stub is found by a test rather than by the owner.
- *"Ensure every button, every menu, every widget and every function is
  working the way it is supposed to"* — the route, ajax and widget sweeps run
  across all 59 controllers. Four differences, none of them a page the port
  gets wrong: `shift/search`, `itemExpire/index` and `itemExpire/search`
  answer 500 on Yii 1 — confirmed on the untouched 5.6 baseline — where the
  port answers 200; and `item/check`, where Yii 1 did not answer at all
  (curl 000, it is slow) and the port refused it with a 403. That last one is
  unresolved rather than explained.

26–27 Sep 2026:

- *"Fix the ajaxItems SQL injection"* → *"fix the other 40 SQL lines too"* →
  *"commit and push both"* — the `PostId` commits, on `phase2` and, by this
  instruction, on `main`.
- *"GRN or MRS tabs are not working, 'Scanned item is not of active' pops up"*
  — lesson 6 and `f02da79`. Earlier the same symptom came from a fixture GRN
  while `run_all.sh` was running; *"it's not a live server, it's a test
  server"* was the owner's answer when asked about running the suite there,
  but the store does use it — see *Running the suites*.
- *"Set the POS containers to restart automatically"* — `unless-stopped`.
- *"Tax amount and value is not showing in /v2/order/groupTax"* → *"check if
  other v2 reports have the same issue"* → *"do both"* — `bba1003`,
  `f860517`, and the removal of the crawler's 12 purchase orders. No other
  report was affected: every other zero date written since 17 Sep was either
  normal (GRN `receiving_date` is empty on all 858 older GRNs too) or fixture
  data.
- *"The item was not added in the inventory despite the GRN being saved…
  review it and test before fixing"* → *"do 1 and 2, then commit and push,
  and list the items where this bug erased stock"* — `c08eb31` and open
  decision 8.

27 Sep 2026 (after an overnight audit of the port):

- *"Fix wrong stock batch, crashes, wrong data and display first; after
  testing commit and push"* — six `Port …` commits: finders return the newest
  row as Yii 1's id DESC default scope does (`LegacyActiveRecord::findByCondition`),
  bill/PO/GRN PDFs, imports that queried the wrong tables, user saves, report
  scenarios, GRN GST recalculation, create_time stamped as Yii 1 does.
- *"Also fix the database clock, row order and known bugs in both apps"* —
  the MySQL container runs on +05:30; the 22 `id asc` finders added during
  porting that reversed production's order are restored in both trees; and
  the known bugs are fixed in both trees: the four display bugs above, loyalty
  earned once per order and changed atomically, credit notes and refunds that
  cannot be over-used, a locked bill-number allocation (`GET_LOCK('pos_bill_no')`),
  held orders resumed once, B2B GRN stock through `addToBalance()`, PO GST
  fields, blank-qty GRN approval, payment import, opening-stock re-saves that
  erased sales, item-detail deletes that erased history, and smaller ones - see
  the commit messages.
- *"Also fix the web root hardening"* — `.htaccess` rules deny `.env`, `.git`,
  logs, docs and internal directories, and no script runs from the upload
  folders. *"Nothing goes on the main branch as that is live"* — none of this
  is on `main`.
- Tested on a local MariaDB built from the code's own schema knowledge (no
  production data): every read-only web action before/after, plus targeted
  and concurrent tests on both stacks. `run_all.sh` was not run (see lesson 5).
- *"Do 3, start with option 1 now"* → *"do the full deploy now"* — the
  `.htaccess` rules first, alone, then `2c89118` on this server (*What was
  done*, item 9). *"Make sure you do no changes to the main server on
  61.2.241.71. That is live and production. Don't touch it"* — nothing in this
  work reaches it.
- *"Yes fix the PO PDF, commit and push"* — `004592a`.
- *"Can you update the .NET and Android code if I upload it on git"* — agreed;
  waiting for the repositories. The change they need: send a unique
  `request_id` (or `X-Request-Id`) with each `item/order`, `ordertest` and
  `punchorder`, and resend the same one on a retry, so the duplicate-bill
  guard answers from `tbl_order_request` instead of guessing by content.

29–30 Sep 2026:

- *"Fix the duplicate bill issue"* → *"can you fix the dot net issue at the PHP
  end without modifying DOT NET?"* — `f824ddd`: a till's late retry is answered
  with the bill it never received (`tbl_order_attempt`, deployed). The .NET
  repository (`soulbowlonline-del/pos_dotnet`) was checked: its tills point at
  `/v2/api`; branch `claude/point-tills-to-v2` makes that the only address in
  `App.config`. Its per-click `Idempotency-Key` and a MySQL password in
  `commonclass.cs` are reported, not changed.
- *"In purchaseBillDetail/index All vendor product tick box is not coming in the
  new Yii 2 port"* — it is in neither tree nor in git history: it exists only in
  the code on the store's server (61.2.241.71). Waiting for a copy of that
  code; nothing on that server is to be touched.
- *"Build the AI Native features in the Web PHP ... improve the UI to make it
  more modern without changing the menu options"* (earlier: *"it won't modify
  the existing formula, calculations"*; .NET and Android later):
  - **AI screens at `/v2/ai`**, port only, admins only: Insights (twelve
    rule-based daily checks, no AI cost), Ask DASPOS (questions answered
    through ten read-only lookups, twelve since the profit ones below), Read a vendor bill (photo/PDF → matched and
    checked lines; nothing saved), a written summary of the checks, and a
    usage/spend page. Official Anthropic PHP SDK (`composer install` adds 11
    packages), `db_changes/2026-09-30-ai-log.sql`, `ANTHROPIC_API_KEY` in
    `.env`, a monthly budget. Nothing in them writes to the database except
    their own log. See `docs/ai-features.md`. Deployed 30 Sep; the first real
    questions ran on Opus 5.5 at about ₹3.5 each, then the owner asked for
    Sonnet 5.5 (half the price per token), now the default. *"For reading the
    GRN bill if Sonnet 5.5 fails then fall back to Opus 5.5"* - a bill whose
    Sonnet reading errors, has no lines or does not add up to its total is
    read once more by Opus 5.5 (`POS_AI_BILL_FALLBACK_MODEL`).
  - *"It gave 0 as the total GRN amount"* - the vendor-purchases lookup summed
    `tbl_purchase_bill.total_amount`, which the GRN screens never fill. It now
    sums `net_bill_amount` of approved GRNs, as `Vendor::getVendorPurchaseTotalAmount()`
    does, with pending GRNs valued from their lines; the pending-GRN check too.
  - **A modern look for `/v2`**: `v2/css/modern.css` and `v2/js/modern-ui.js`,
    scoped under `html.ui-modern`, three lines in each layout. Same menu, same
    words, same ids; a half-circle icon in the top bar switches back to the old
    look (remembered per browser). Also a Ctrl+K "Go to…" list built from the
    user's own menu. Yii 1 at `/` is unchanged.
  - Both were checked with the suites' own rule: every read-only page of the
    port, 790, compared before/after for visible text and element ids - the
    same result as before the change (four known differences: `printPdf`
    error pages that print each copy's own path). Anything new in the shared
    layout is an icon with a title, or created by javascript, for that reason.

- *"The read a vendor bill should be in the goods receipt note menu below the
  merge button, once a bill is uploaded there in PDF it should autofill the
  grid with the values from the bill"* (30 Sep) - **Read a vendor bill** on
  `/v2/purchaseBillDetail/index`, under Merge, for the AI roles. The reading
  is typed into the grid's own cells and their `change` events fired, so the
  screen's own `gridcalculation()` works out every amount; nothing is saved
  until Update. Lines of the bill with no line on the GRN go through the
  screen's Add Item form. `GrnBillFill`, `v2/js/grn-bill.js`,
  `views/ai/_grn_bill.php`; see `docs/ai-features.md`, which also says what
  was and was not tested. Yii 1's screen is unchanged, and the port's page
  markup is too (the button is drawn by javascript).

- *"It should match line wise as the android app is used to receive items and
  they are reordered line wise in the Goods Received Note ... the field names
  dont always match exactly"* (30 Sep) - the bill's lines are now aligned, in
  order, with the GRN lines the Android app numbered on receiving
  (`tbl_purchase_bill_detail.order`, from `entry_position`); name, MRP and
  rate only decide where the two lists fall out of step. A pair resting on
  its place alone is filled and flagged.
- *"Its not being able to answer questions on my profitability and how to
  optimise inventory"* / *"the cost is the purchase price, you also have the
  GST so please calculate the margins and profit"* (30 Sep) - the calls had
  all succeeded; there was no lookup that knew a cost. `AiProfit`:
  `profit_summary` and `item_profit` for Ask DASPOS. Gross profit = sales
  excluding GST less refunds, minus units sold x the purchase cost from the
  item's GRNs (the month's and the six before). A new figure: DASPOS has no
  profit report of its own.
- *"Also add these AI native features for Sagar Giri, Anil Giri and Kajal Bist
  users"* (30 Sep) - `POS_AI_USERS=sagargiri,kajalbist,anilgiri` in `.env`
  lets named usernames in whatever their role (two are Managers, a role the
  stock-adjustment logins share; one is Online Order Manager). Sagar Giri's
  second login, `sagargirigrn` (Employee, last used 2019), is not included.

- *"Claude cannot process the request to upload the PDF, error 400"* (30 Sep)
  - the owner's first three bills. The API refuses a structured-output schema
  with more than 16 nullable fields; the bill schema had 20, and had never met
  the real API. Fixed in `BillReader::schema()`. A stand-in API does not check
  this: anything that changes a schema or a tool list needs one real call.

- *"This was the bill which was uploaded, it is not matching with the GRN"*
  (30 Sep) - the reading was right in every figure; the use of it was not.
  The bill counts boxes at prices with GST in, the grid counts pieces at a
  rate before GST, and two flavours had been swapped by a name match in the
  item master. `GrnBillFill::reading()` now converts (pack size, GST), and
  names are compared word by word with the GRN's own lines. Verified by
  replaying that reading against its GRN: the grid comes to the bill's
  8,790. Lesson, the same as lesson 4: a reading that is right and a page
  that fills are not a GRN that is right - test with a real bill against a
  real GRN.

- *"This is the bill for Amarjit Singh, please note that crat means crate and
  one crate has 24pcs ... SM Milk 500 ML means Verka Milk Green, DTM Milk
  500ML means Verka Milk Yellow and Full Cream Milk 500ML means Verka Milk
  Gold. Please map this as this is true everytime the bill comes"* (30 Sep)
  - `app2/config/ai-bill-names.php`, keyed by vendor id (310): the vendor's
  unit and the vendor's names with the items' barcodes. The owner will give
  more of these; each is an entry in that file, a test in the style of the
  AMARJIT SINGH one, and a deploy.

- *"Pressed update ... it has not come in GRN details"* (30 Sep) - the GRN
  (9990022, AMARJIT SINGH) had saved and approved correctly: 8,022.00, stock
  +168 / +72 / +24. It was missing from `/v2/purchaseBillDetail/list` because
  of a port bug as old as the port: Yii 1's `PurchaseBill::search($val = false)`
  is called by the view as `search($val = true)` to include the cash vendors;
  the port's `search($params = [], $val = false)` took that `true` as
  `$params`, so every cash vendor's GRN (8 vendors, 559 GRNs this financial
  year) was left out. `PurchaseBill::search()` and `B2bPurchaseBill::search()`
  now accept the Yii 1 call. The page sweeps did not see it: they compare a
  list's first rows on fixture data, where no vendor is a cash vendor. Worth
  a sweep of its own: every ported method whose Yii 1 signature had a
  positional flag where the port's now has `$params`.

- *"This is the Rana enterprises bill today. In each box the qty is written
  in black next to the boxes. This is true for every bill of this vendor.
  Ignore the blue handwritten number on the left"* (30 Sep) - vendor 617 in
  `ai-bill-names.php`: a `reading` sentence that goes to Claude with this
  vendor's bills, and `pack_size_is_certain`. Checked with one real reading
  of the owner's photo: box sizes 24, 18, 18, 18, 18, 24, 12, the blue marks
  ignored, the grid coming to the bill's 8,790. (Vendor 600, "RANA
  ENTERPRISESGHJGH", is a stray duplicate with the same GSTIN and has no
  entry.)

- *"It picked up everything correctly except that it picked up amul ice cream
  which was added by mistake ... it should keep the items not in the bill on
  the top row and highlight the same and make them 0qty"* (30 Sep, the DUA
  ENTERPRISES bill) - GRN lines the bill does not have are moved to the top
  of the grid, marked red and set to 0 by `grn-bill.js`; not zeroed when the
  lines read do not add up to the bill's total. Checked in a browser on that
  GRN and reading: the grid's Bill Amount 4,715 -> 3,669, the bill's total.

Two standing instructions from the owner: pushes go to
`phase2/php83-yii1132`, and `main` only when the owner says so (see
*Conventions*); and no real secret is ever committed — `.env` is gitignored
and the code reads `getenv()`.

## Siddhant session, 26–30 Sep 2026 — where things stand

The long Claude Code session with the owner (Siddhant) that produced the work
from `2c89118` to `35acbec`. Written at the owner's request so the next
session can carry on from here. The requests and what each produced are
under *What the owner asked for* above; this is the state and the loose ends.

**How the owner works, and what a session can reach.**
- The owner works from a Mac terminal and deploys by pasting command blocks
  into an SSH session: `ssh -i ~/.ssh/daspos_key root@31.97.186.151` first,
  then the commands **on the server**. The prompt tells them apart
  (`siddhant@…` is the Mac, `root@srv901415` the server). Several times the
  command ran on the Mac, or the expected output was typed as a command, or
  "deployed" arrived without the output: ask for the pasted output, never
  assume it.
- The cloud environment **cannot reach 31.97.186.151** (network policy) and
  holds no SSH key, so every deploy and every check on real data goes through
  the owner. **Proposed, awaiting the owner's go-ahead:** a deploy-only SSH key
  whose `authorized_keys` entry forces a gate script allowing only
  `deploy` (ff-only pull of `phase2`, `php -l`, `composer install` when the
  lock changed), `status`, `ai-log` and `timing`; the owner would allow
  `31.97.186.151` under the environment's Network access and store the private
  key as the environment variable `DASPOS_DEPLOY_KEY` (in the environment
  settings, never in chat). SQL files would still need the owner. Not written
  yet. The alternative offered, a cron pull on the server, was not preferred.
- Pushes go to `phase2/php83-yii1132` and to the session branch
  `claude/trusting-thompson-0ca6h2` (identical). GitHub offers "Compare & pull
  request" for the session branch; the owner was told not to create it (it
  would target `main`). `main` untouched.
- Pasting into hidden input (`read -s`) did not work from the owner's Mac;
  visible `read -rp` or `nano` did.

**On the test server (last confirmed).**
- `e709259` deployed 30 Sep: the modern look, `/v2/ai`, `composer install`
  (11 packages: `anthropic-ai/sdk`, Guzzle and their dependencies, nothing else
  changed), `db_changes/2026-09-30-ai-log.sql`. `/.env` 403, both logins 200.
- `82700ea` deployed: the reorder check went from 10.92 s to 0.22 s on the
  store's data (all twelve checks 13.09 s → 0.78 s), same 845 rows.
- `ANTHROPIC_API_KEY` is in the server's `.env`. First real calls, 30 Sep, on
  Opus 5.5: 5 questions, 10 calls, all `ok`, $0.206, prompt cache working
  (3,241 cached tokens per call).
- **Not confirmed on the server:** `0d3e819` (GRN totals from
  `net_bill_amount`; Sonnet 5.5 default) and `35acbec` (bills read by Sonnet,
  then by Opus on failure). The owner's last paste showed the prompt in
  `/root/pos/pos83` but not `git log`; check with `git log --oneline -1`
  before anything else.

**Open items, in the owner's order of interest.**
1. Check `0d3e819` answers: ask Ask DASPOS for a GRN total and compare with
   the vendor purchase report for the same dates (the owner's report of "0"
   was the `total_amount` bug). Then try **Summarise for me** and **Read a
   vendor bill** with a real bill — neither has had a real Claude call yet.
2. **"All vendor product" tick box** on `purchaseBillDetail/index`: exists
   only in the live store server's code (61.2.241.71), in no tree and no
   branch here. The owner was asked to upload the live
   `protected/views/purchaseBillDetail/index.php`,
   `protected/controllers/ItemController.php` and
   `protected/controllers/PurchaseBillDetailController.php` (ideally all of
   `protected/`) to a new branch under `live-copy/` — read-only for the live
   server, never `main`. With the whole folder, diff it against `protected/`
   here for every other live-only change. The one-shot reminder routine for
   this has fired.
3. The deploy-only key above.
4. What Insights found on real data, 30 Sep, for the owner to act on: 2,037
   batches below zero (open decision 8), 41 bill numbers used twice in 90
   days (check whether any are after the 27 Sep bill-number lock), 22 lines
   sold above MRP, 1 possible duplicate bill, 1 credit note over-used, 16 GRNs
   waiting over 5 days, 845 items running low, 1,897 not selling for 60 days.
5. The .NET till repository `soulbowlonline-del/pos_dotnet`: branch
   `claude/point-tills-to-v2` makes `/v2/api` the only `apiurl`. Reported, not
   changed: a new `Idempotency-Key` per click (the server ignores it on
   purpose), a MySQL password in `App_Code/commonclass.cs` (~line 1358, host
   10.1.1.125), committed `.pfx`, `bin/`, `obj/`. The owner cannot build
   Windows apps; .NET and Android AI features are "for later".
6. Reported earlier, not fixed: the API answers without authentication; Yii 1
   anonymous password reset (`user/passwordExpired`) on production; OTP
   brute force; the new-customer loyalty race; occasional deadlocks on
   parallel sales of the same item; the server asks for a reboot (at a quiet
   time).

**Second session, 30 Sep 2026, on the owner's Mac.** It reaches the test
server itself (`ssh -i ~/.ssh/daspos_key root@31.97.186.151`), so the
deploy-only key (item 3) is needed only for cloud sessions. The Mac has no
GitHub login, no PHP and no Docker: commits are sent to the server as a git
bundle, fast-forwarded there and pushed with the server's GitHub key (the
owner's choice), and PHP is linted and unit-tested by piping it into
`docker exec -i pos-php-83 php`, which writes nothing.
- The server was on `35acbec` (so `0d3e819` and `35acbec` were deployed), then
  `2df5796`. No Claude call has run since `0d3e819`: `tbl_ai_log` holds the 10
  Opus calls of the morning and nothing else - item 1's real question, summary
  and bill are still to do.
- Item 1, checked in SQL instead: the lookup's approved net equals the vendor
  report's for Aug and Sep 2026 (₹38,40,158 and ₹18,57,656; the old field is
  0). The report also counts the net of GRNs not yet approved; the lookup
  values those from their lines, so Sep differs by the 9 pending GRNs that
  have no net amount yet.
- Item 4: all 41 repeated bill numbers are from before 17 Sep (copied from
  production: two different bills seconds apart, outlet 5); none since the
  27 Sep lock, which only 5 orders have exercised here. The "credit note
  over-used" was note 14630, value -89,931 and nothing used; `2df5796` makes
  the check ask for `amt_used > 0`.
- The host was rebooted on 29 Sep 15:58 IST; every container came back.

**How this session tested, since none of it is in the repository.** A local
MariaDB built from the models' own schema knowledge (no production data), two
`php -S` servers (working tree and HEAD) and a stand-in Anthropic API that
records every request and answers from scripts (tool calls, a bill, 500, 429,
refusal, fallback). On top: 56 scripted AI checks, 10 bill-fallback
scenarios, and a comparison of all 790 read-only port pages between HEAD and
the working tree for visible text and element ids (the pages suites' own
rule; four known differences, `printPdf` error pages printing each copy's
path). They lived in the session's scratchpad and are gone with its
container; rebuild them before trusting a change to shared layout or to
`/v2/ai`.

## Conventions

- Branch `phase2/php83-yii1132`. `main` is the PHP 5.6 baseline
  (`/root/pos/pos`, the same GitHub repo) and was untouched until 26–27 Sep
  2026, when the owner asked for the security and stock fixes there too:
  `efa8883`, `cdbdc85`, `07c85b6`, `950ee56` (rebased onto the existing
  `88a40cf`). The baseline's older uncommitted hardening edits
  (`config/main.php`, the GST reports, the debugger controller) are still
  uncommitted, deliberately. Anything else on `main` needs the owner's say-so.
- Never commit a secret. `.env` is gitignored.
- `POS_STUB_OUTBOUND=1` stubs SMS and webhook calls for the suites. **It must
  not be set in production.**
- Fixture rows are numbered from 9990000, but **an id above 9990000 is no
  longer proof of a fixture** (lesson 5): real orders, GRNs and MRS created
  since the fixtures ran have ids there too. Every DELETE in the harness must
  be scoped to an explicit fixture id, never a range; `deletes_difftest`
  checks the scoping, not the range problem.
- Stock quantities change only through `ItemStock::addToBalance()`. Never
  compute `balance_qty` / `purchase_qty` in PHP and `save()` it; a new batch
  row is the one place a plain `save()` sets them.
- Much of `protected/` has CRLF or mixed line endings. Patch without
  normalising them, or the diff becomes the whole file.
- The generators are mirrored between `/root/pos/` and `tools/port/`; change
  one and copy it, or the next regeneration will surprise you.

## Before this goes to production

Two things exist only for testing and must be removed at cutover:

- `POS_STUB_OUTBOUND=1` in `pos83/.env`. It makes `lib/PosOutbound.php`
  record outbound calls instead of placing them. The database is a copy of
  production with real customer phone numbers in it, so with the stub off
  every test order messages a real customer — which is why it is on. In
  production it must be **off**, and nothing should depend on it being set.
- `porttestadmin`, user id 9990003, role 1. Created for the crawls and still
  present in `pos_live.tbl_user`. Delete it before cutover; the sweeps that
  need it recreate it.

And, since 27 Sep 2026:

- The stock fix (`c08eb31` / `950ee56`) must reach the store's server before
  the port does, or the port inherits a database whose stock keeps drifting.
- The AUTO_INCREMENT question (open decision 6) must be settled before cutover,
  or production ids start at ~9,990,000.
- The repo is the document root. Since 27 Sep the `.htaccess` rules of
  `16b3611` keep `.env`, `.git`, `CLAUDE.md`, `docs/`, `tests/`, `tools/`,
  `config/`, `lib/` and the compose/SQL files off the web, and no script runs
  from the upload folders. They depend on `AllowOverride All` for
  `/var/www/` (set in the image's `conf-enabled`); a production web server
  without it would serve all of them again. Moving the application out of
  the repository root is the lasting fix.
- The .NET and Android clients should send a request id on checkout (see
  *What the owner asked for*); until they do, duplicate protection is the
  content match only.

## Secrets — outstanding

These were exposed during the work and **have not been rotated**:

- the root SSH password for the host (SSH is key-only now, but the password
  still exists)
- the `admin` application password
- the Firebase server key, the soulbowl dispatch key and the uengage SMS
  token, all of which are in git history
- **everything in `pos83/.env`** — the database, SMTP and Interakt
  credentials and `POS_V2_COOKIE_KEY`. `/.env` and `/.git` were downloadable
  over HTTP until 27 Sep 2026 ~10:51 IST. The container log (from 17 Sep)
  shows no outside request for either; earlier access cannot be ruled out.

Rotating them is outstanding and is the owner's to do. Passwords in
`tbl_user` are unsalted MD5; changing that is a separate piece of work,
because both stacks must agree on it.
