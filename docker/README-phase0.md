# Phase 0 — Run the current POS app in Docker (baseline)

Goal: get today's app (PHP 5.6, Yii 1.1.14, MySQL 5.7) running unchanged in
containers, so we have a reproducible reference to compare later phases against.
**No application code is modified in this phase.**

## Prerequisites

- Docker Desktop running
- The DB dump at `~/Documents/pos_live.sql.gz` (212 MB) — or the uncompressed
  `pos_live.sql` (1.6 GB)

## 1. Configure env

`.env` is already created (gitignored). Keep `DB_ROOT_PASSWORD=root@123` — the
legacy app hardcodes it in `config/prod-db.php`.

## 2. Build + start

```bash
docker compose -f docker-compose.legacy.yml up -d --build
```

- App:        http://localhost:8082
- phpMyAdmin: http://localhost:8083  (server `db`, user `root`, pass `root@123`)
- MySQL on host port 3309

> Ports are 8082/8083/3309 (not 8092/8093/3307) to avoid clashing with the
> existing `autoservice` stack already running on this machine.

The PHP 5.6 image is Debian Stretch (now archived), so the first build pulls
packages from `archive.debian.org` — expect it to be a little slow.

## 2b. Install PHP dependencies (composer)

`vendor/` is no longer in git — it's restored from `composer.json` / `composer.lock`.
The one dependency is `phpoffice/phpexcel` (used by the GST/stock Excel reports).
After a fresh clone, run once (no local composer needed):

```bash
docker run --rm -v "$(pwd)":/app -w /app composer:2 install --ignore-platform-reqs
```

`--ignore-platform-reqs` is needed because the composer image runs PHP 8 while the
lock targets PHP 5.6 (pinned via `config.platform` in composer.json). `phpoffice/phpexcel`
is abandoned (advisories allowed via `config.policy` in composer.json) — it's a
temporary bridge; the PHP 8 / Yii 2 migration should move Excel to `phpoffice/phpspreadsheet`.

## 3. Import the database

```bash
./docker/import-db.sh ~/Documents/pos_live.sql.gz
```

(Streams the gzip straight into the container. The 1.6 GB uncompressed import can
take several minutes.)

## 4. Fix writable dirs

Yii needs these writable by the web user (`www-data`):

```bash
docker compose -f docker-compose.legacy.yml exec php \
  chown -R www-data:www-data assets wdir/runtime wdir/uploads uploadbills
```

## 5. Smoke-test checklist (the regression baseline)

Run through these by hand and confirm each works. **Every later phase re-runs this
exact list** — that's how we catch regressions from the PHP/MySQL/Yii changes.

- [ ] Login / logout
- [ ] Dashboard / home loads
- [ ] Create + view an order/bill
- [ ] Item list, add item, stock update
- [ ] GST report generates (and the standalone `gstreport.php` if used)
- [ ] PDF print of a bill/invoice (mPDF path)
- [ ] Excel export (PhpSpreadsheet path)
- [ ] API module responds (`/api/...`)
- [ ] A `timer_*.php` cron endpoint runs

Note anything that misbehaves — some pages may reveal existing prod quirks; record
them so we don't mistake them for migration regressions later.

## PHP extensions in the image

`pdo_mysql mysqli gd zip mbstring bcmath intl opcache` (plus `curl`, `iconv`,
`openssl`, `json`, `dom`/`xml` from the base image).

If a page dies with **`Call to undefined function <name>()`**, it's almost always
a missing extension. Find which one provides it, add it to the
`docker-php-ext-install` line in `docker/php56/Dockerfile`, then:

```bash
docker compose -f docker-compose.legacy.yml build php
docker compose -f docker-compose.legacy.yml up -d php
```

## Performance notes (Apple Silicon)

`mysql:5.7` runs under amd64 emulation here, which is the main source of slowness.
Mitigations already applied in `docker-compose.legacy.yml`: `--skip-name-resolve`
(no reverse-DNS per connection), `--innodb-use-native-aio=0`, a 512M buffer pool,
and relaxed log flushing. PHP `opcache` is enabled for web requests. Pages that
load large tables unpaginated (e.g. the ~23k-row item list) may still be slow —
that's app-level, not infra.

## Performance fixes applied (beyond pure "as-is")

The imported prod DB is large (`tbl_order` ~1.3M rows, `tbl_notification` ~1.6M),
which exposed slow queries that were tolerable on prod hardware but crawl under
emulation. Applied:

1. **`Order::getOrderRecord()`** (dashboard chart) rewritten from 12 separate
   full-table `COUNT(*) WHERE MONTH(create_time)=N` scans into one `GROUP BY`
   query, plus opt-in 1h caching. Measured 22.6s → 3.3s → instant (warm cache).
   Same output.
2. **Schema caching enabled** — added a `CFileCache` `cache` component
   (`config/main.php`) pointed at tmpfs `/dev/shm/yii_cache`, so Yii stops
   re-reading metadata for all ~77 tables every request. Global query-result
   caching left **off** (`queryCachingDuration => 0` in `config/prod-db.php`) to
   avoid stale POS data.
3. **Indexes added** — see `db_changes/2026-07-11-phase0-perf-indexes.sql`. The
   big one: the item page ran `SUM(col) WHERE item_id = ?` per item against
   several detail tables with no `item_id` index, full-scanning up to 4.24M rows
   (~9s each) — a classic N+1. Indexed `item_id` on all 10 affected tables
   (`tbl_order_item`, `tbl_purchase_bill_detail`, `tbl_mrn_detail`, etc.); those
   SUMs now hit a covering index (1 row). Also `tbl_order(create_time)` and
   `tbl_online_order(type_id)`.

   > These indexes are **not** in the original dump. After any fresh DB import,
   > re-apply them:
   > ```bash
   > docker compose -f docker-compose.legacy.yml exec -T db \
   >   mysql -uroot -p"$DB_ROOT_PASSWORD" pos_live \
   >   < db_changes/2026-07-11-phase0-perf-indexes.sql
   > ```

4. **RBAC query explosion fixed** — the admin layout calls `checkPermission()`
   ~95x/page; it was uncached (2 queries + full role-permission hydration each
   call = ~190 queries/page). Memoized per-request in `GxActiveRecord.php`:
   ~190 → 2 (measured 11 across a whole page incl. its AJAX widgets).

### Known remaining slowness (view-level N+1, deferred)

After the above, pages are dominated by ordinary per-row N+1 in grid views:
loading the same related record once per row (e.g. `tbl_user id=706` fetched 264x
on one report) and rebuilding dropdown `listData` per row. These are hundreds of
*individually fast* PK lookups (~1ms each, all indexed) — usable, but not free.
The proper fix is eager loading (`with()`) / caching in each controller's data
provider, which is best done per-screen during the Yii2 migration rather than
patched blindly here. Ask if a specific screen is still too slow and it can be
targeted.

To find the next slow query, the slow-query log is armed:

```bash
# after reproducing a slow page, list the worst queries:
docker compose -f docker-compose.legacy.yml exec -T db \
  mysql -uroot -p"$DB_ROOT_PASSWORD" -e \
  "SELECT query_time, rows_examined, LEFT(sql_text,150) sql_text \
   FROM mysql.slow_log ORDER BY query_time DESC LIMIT 20;"
```

(Threshold is 2s; `SET GLOBAL long_query_time=<n>` to change.)

## Handy commands

```bash
# tail php/apache logs
docker compose -f docker-compose.legacy.yml logs -f php

# shell into the app container
docker compose -f docker-compose.legacy.yml exec php bash

# stop (keep data)
docker compose -f docker-compose.legacy.yml down

# stop AND wipe the DB volume (fresh start)
docker compose -f docker-compose.legacy.yml down -v
```

## Cron replacement for timer_*.php

Production hits `timer_0..3.php` via cURL on a schedule. For the container, run
them from the host crontab or a sidecar, e.g.:

```bash
# every 5 min, from host
*/5 * * * * curl -s http://localhost:8082/timer_1.php >/dev/null
```

(We'll fold these into proper Yii console commands in Phase 3.)
