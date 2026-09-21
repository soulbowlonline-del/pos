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

The port is complete. 59 of 59 controllers and 69 of 69 API actions, verified
by 35 differential suites.

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

The last full green run: **7,186 cases, 0 mismatched**, invoice canary 6/6,
PHP error log empty. The two suites added after it bring the count higher.

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

And three more that are run by hand rather than in the runner, because each
takes a while and none of them needs fixtures:

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

## The three lessons, which are the point of this file

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

## Known coverage gaps

- 27 `pmui` comparisons verify nothing — both stacks refuse or crash on those
  pages, so the case passes without asserting anything.
- 19 write actions are refused by `writes_difftest` because they save inside a
  loop without reading the request. Thirteen deserve it; six are ordinary
  row-scoped work whose writes nobody has compared.
- Actions whose name begins with `delete` are refused outright, since
  `itemExpireItem/delete` removed four real rows on a plain GET.

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

`docs/web-ui-port.md` is the long form, written as the work happened.
`docs/live-bugs-found.md` records the application's own bugs, found by
comparison and left alone.

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
