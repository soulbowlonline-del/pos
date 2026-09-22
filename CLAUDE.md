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

What that first day taught is in *The four lessons* below, and it is the most
useful thing in this file: the suites were green throughout while the
application was unusable, because they were all asking about markup and every
fault was in the wiring behind it.

## Where it runs

| | |
|---|---|
| host | `31.97.186.151`, SSH is key-only: `ssh -i ~/.ssh/daspos_key root@31.97.186.151` |
| PHP 5.6 baseline | `/root/pos/pos`, container `pos-php-legacy`, port **8082** |
| the working tree | `/root/pos/pos83`, container `pos-php-83`, port **8084** — this git repo |
| database | container `pos-mysql-8`, schema `pos_live` |
| baseline database | container `pos-mysql-legacy` — **never written to**, see below |
| generators and suites | `/root/pos/*.py`, `/root/pos/*_difftest.sh`, mirrored into `tools/port/` and `tests/port/` |

Within `pos83`, `protected/` is the Yii 1 tree running under PHP 8.3 and
`app2/` is the Yii 2 port. Both are served by the same container.

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
| `tools/port/missing_methods.py` | is a method called on the port that it does not define |

The rest drive HTTP and compare responses, database rows, outbound calls and
rendered pages. `pmui_difftest` is the slow one — 359 pages, about 50 minutes.
`writes_difftest` reads the MySQL binary log to compare what an action *wrote*
rather than what it answered.

## The four lessons, which are the point of this file

Everything painful in this port had the same shape, and the suites did not see
any of it until something forced the question.

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
4. **The live application bugs** in `docs/live-bugs-found.md` — inverted bill
   prefix, `getSgstPercent()` reading the CGST column, `getIgstPercent()`
   always 0, `getMainDiscount()` returning a literal `'0'`, `state_id`
   overwritten with 1 on every API customer write (it is geographic on
   `tbl_customer`, and 1 is Punjab). All reproduced rather than fixed, so the
   two stacks agree. Each was fixed once and reverted on the owner's
   instruction; the diffs are recoverable from this file's history if wanted.

5. **Order SMS is off in both trees.** `Order::SendSms()` returned early on
   the owner's instruction, 21 Sep 2026, in `app2/models/Order.php` and
   `protected/models/Order.php` at the same point, so the stacks keep
   agreeing. The uengage call below the `return` is dead but left in place as
   the record of what it sent. WhatsApp through Interakt is untouched and
   still live. Turning order SMS back on is an owner's decision, and it must
   be made in both files or the suites will diverge.

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
   input and bound nothing to it. See *The four lessons*.
7. The three behaviour sweeps written in response — widget, route and ajax —
   plus `search-parity` and `crud-sweep`. Each exists because something got
   past every suite that already ran.

`docs/web-ui-port.md` is the long form, written as the work happened.
`docs/live-bugs-found.md` records the application's own bugs, found by
comparison and left alone.

## What the owner asked for, and what each request produced

The owner's instructions drove the order of this work, and several of them are
the only record of a decision. Condensed, in sequence:

- *"Finish the port to Yii 2"* — the API first, then the 59 web-UI
  controllers, then the differential suites that verify them.
- *"Fix the live application bugs"* → *"on second thoughts let the bugs be"*
  → *"revert"*. The five bugs in `docs/live-bugs-found.md` were fixed and
  then reverted on this instruction. **They are reproduced deliberately**, so
  that the two stacks agree. Do not "fix" one without fixing Yii 1 in the
  same commit, or every suite comparing it will go red.
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

Two standing instructions from the owner: pushes go to
`phase2/php83-yii1132` only, `main` is untouched; and no real secret is ever
committed — `.env` is gitignored and the code reads `getenv()`.

## Conventions

- Branch `phase2/php83-yii1132`. `main` is untouched.
- Never commit a secret. `.env` is gitignored.
- `POS_STUB_OUTBOUND=1` stubs SMS and webhook calls for the suites. **It must
  not be set in production.**
- Fixture rows are numbered from 9990000. Anything below that is real data.
  Every DELETE in the harness must be scoped to a fixture id, and
  `deletes_difftest` enforces it.
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

## Secrets — outstanding

These were exposed during the work and **have not been rotated**:

- the root SSH password for the host (SSH is key-only now, but the password
  still exists)
- the `admin` application password
- the Firebase server key, the soulbowl dispatch key and the uengage SMS
  token, all of which are in git history

Rotating them is outstanding and is the owner's to do. Passwords in
`tbl_user` are unsalted MD5; changing that is a separate piece of work,
because both stacks must agree on it.
