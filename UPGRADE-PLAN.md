# POS Upgrade Plan — PHP 5.6/Yii 1.1.14/MySQL 5.x → PHP 8.2/Yii 2/MySQL 8, on Docker

## Current state (surveyed 2026-07-10)

- Yii **1.1.14** framework bundled in `framework/` (loaded via `common.php`, `yiilite.php` in prod)
- ~78 controllers, ~102 models (+75 Giix `_base` classes), ~692 views, theme `themes/bar`
- Modules: `api`, `backup`, `debugger`, `gii`
- Extensions: PHPMailer 5.x, mPDF (old), html2pdf, yii-pdf, Bootstrap 2/3 ext, barcode generator
- `vendor/` has PhpSpreadsheet + legacy PHPExcel side by side; **no `composer.json`**
- Config in `config/` (`main.php`, `prod.php`, `prod-db.php`) — DB host is already `db` (docker-friendly), credentials and SMTP/API keys hardcoded
- One-off scripts in webroot: `adminer.php`, `phpinfo.php`, `gstreport*.php`, `timer_*.php` (cron via cURL), `b2bgstreport.php`
- PHP 8 blockers in app code are small: 3 real `each()` calls, ~83 curly-brace string offsets `$str{0}`, `preg_replace /e` only inside bundled PHPMailer

## Strategy: staged, not big-bang

A direct Yii1→Yii2 rewrite of 78 controllers/692 views while also changing PHP and MySQL versions is high-risk. Do it in phases, each independently shippable:

| Phase | Outcome | Risk | Effort |
|---|---|---|---|
| 0 | Current app runs in Docker (PHP 5.6 + MySQL 5.7) — reproducible baseline | Low | 1–2 days |
| 1 | MySQL 8.0 under the existing app | Low-Med | ~1 week |
| 2 | PHP 8.2 with **Yii 1.1.31** (drop-in framework update) | Medium | 3–5 weeks |
| 3 | Yii 2 migration (strangler pattern, module by module) | High | 3–6 months |
| 4 | Hardening & cleanup | Low | ongoing |

Phases 0–2 get you to **PHP 8 + MySQL 8 in Docker within ~6 weeks** with the app still on Yii 1.1 (fully supported by the community-maintained 1.1.31 release). Phase 3 is then a pure framework migration with no infra variables changing underneath it.

---

## Phase 0 — Containerize the current app as-is

Goal: freeze a working baseline before touching anything. Use `php:5.6-apache` + `mysql:5.7` so behavior is identical to production today.

> **Status: implemented & booting (2026-07-10).** Files live in `docker/php56/` and
> `docker-compose.legacy.yml`; run instructions in `docker/README-phase0.md`. The
> app serves `/user/login` (HTTP 200, title "DASPOS - User") on PHP 5.6.40 with
> `gd/mbstring/mysqli/pdo_mysql/zip` loaded. Real-world adjustments made during
> setup:
> - `php:5.6-apache` is Debian **Stretch** (archived). The Dockerfile rewrites
>   `sources.list` to `deb [trusted=yes] http://archive.debian.org/debian stretch main`
>   and sets `Acquire::Check-Valid-Until "false"` (dead `stretch-updates` + expired
>   GPG keys otherwise break `apt-get update`).
> - Host is Apple Silicon (arm64); `mysql:5.7` has no arm64 image, so the `db`
>   service is pinned `platform: linux/amd64` (runs under emulation).
> - Ports moved to **8082 (app) / 8083 (phpMyAdmin) / 3309 (MySQL)** because the
>   existing `autoservice` and `it_park_pos` stacks already hold 8092/8093/3307/3308.
> - Secrets moved out of the compose file into a gitignored `.env` (root password
>   stays `root@123` to match the value hardcoded in `config/prod-db.php`).
> - DB imported: 66 base tables, ~23.7k items / 4k customers. The dump is a
>   `--databases` dump (self-contained `CREATE DATABASE`/`USE`), latin1.
> - Extensions the app needs beyond the base image: **bcmath** (money math in
>   `Item.php` etc.), **intl** (html2pdf), plus **opcache** enabled for web.
>   `curl`/`iconv` are already in the base image.
> - MySQL tuned for the emulated environment (`skip-name-resolve`, no native AIO,
>   512M buffer pool) — this and opcache are the main fixes for the initial slowness.

`docker/php56/Dockerfile`:

```dockerfile
FROM php:5.6-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev zlib1g-dev \
    && docker-php-ext-configure gd --with-jpeg-dir=/usr --with-freetype-dir=/usr \
    && docker-php-ext-install pdo_mysql mysqli gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
```

`docker-compose.legacy.yml` (adapted from your example):

```yaml
version: "3.8"
services:
  php:
    build: ./docker/php56
    container_name: pos-php-legacy
    ports: ["8092:80"]
    volumes: ["./:/var/www/html/"]
    depends_on: [db]
    networks: [pos-net]
    environment:
      TZ: Asia/Kolkata

  db:                       # service name "db" matches existing prod-db.php host
    image: mysql:5.7
    container_name: pos-mysql-legacy
    restart: always
    ports: ["3307:3306"]
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: pos_live
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes: ["db_data_legacy:/var/lib/mysql"]
    networks: [pos-net]

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: pos-phpmyadmin
    ports: ["8093:80"]
    environment: { PMA_HOST: db }
    depends_on: [db]
    networks: [pos-net]

volumes: { db_data_legacy: }
networks: { pos-net: }
```

Steps:
1. Add the files above + a `.env` for compose (`DB_ROOT_PASSWORD`, `DB_USER`, `DB_PASSWORD`). **Do not commit real passwords** (your example compose had them inline — put them in `.env`, gitignore it).
2. Import `pos_live.sql` (in `~/Documents`) into the container: `docker compose -f docker-compose.legacy.yml exec -T db mysql -uroot -p pos_live < pos_live.sql`.
3. Fix file permissions for `assets/`, `wdir/runtime/`, `wdir/uploads/`, `uploadbills/` (www-data writable).
4. Replace `timer_*.php` cURL cron hacks with host cron or an Ofelia/cron sidecar container hitting the same URLs.
5. Smoke-test the critical flows: login, create order/bill, item stock, GST reports, PDF print, Excel export, API module. **Write these down as the regression checklist** — every later phase re-runs it.

## Phase 1 — MySQL 5.7 → 8.0

MySQL 8 works fine with the legacy app if you handle these:

1. **Auth plugin**: PHP 5.6's mysqlnd cannot do `caching_sha2_password` (MySQL 8 default). Run MySQL 8 with `--default-authentication-plugin=mysql_native_password` until Phase 2 is done, then remove it.
2. **sql_mode**: the strict mode in your example (`NO_ZERO_DATE`, `NO_ZERO_IN_DATE`, `ERROR_FOR_DIVISION_BY_ZERO`) **will break legacy data/inserts** — this schema has `0000-00-00` dates and defaults like `float(10,2) DEFAULT '0.00'`. Start with `sql_mode=""`, get the app working, then clean data (`UPDATE ... SET date_col=NULL WHERE date_col='0000-00-00'`) and enable strict flags one at a time.
3. **ONLY_FULL_GROUP_BY**: the ~89 raw `createCommand()` queries (reports especially) likely have non-aggregated GROUP BY columns. Disable this flag initially; fix queries during Phase 2.
4. **Reserved words**: MySQL 8 reserved `rank`, `groups`, `rows`, `function`, etc. Grep the schema and raw SQL for collisions; backtick-quote them.
5. **Charset**: dump/reload as `utf8mb4` (`utf8mb4_0900_ai_ci` or `utf8mb4_general_ci` to keep sorting closer to old behavior). Update `charset` in `prod-db.php`.
6. **Query cache is removed** in MySQL 8 — irrelevant to app code, but drop any `SQL_CACHE` hints if present.

Migration path: `mysqldump` from the 5.7 container → import into a fresh `mysql:8.0` service (new volume) → point `prod-db.php` at it → run the regression checklist. Keep the 5.7 volume around for rollback.

## Phase 2 — PHP 8.2 while staying on Yii 1.1

Yii 1.1 is community-maintained through **1.1.31** with official PHP 8.2/8.3 compatibility. This is the key shortcut: swap the bundled `framework/` (1.1.14 → 1.1.31), fix app-level deprecations, and you're on PHP 8 without touching the MVC code structure.

1. **Framework**: replace `framework/` with Yii 1.1.31 (github.com/yiisoft/yii). It's designed as a drop-in update; `yiilite.php` still exists.
2. **Scan before fixing**: run `PHPCompatibility` (PHP_CodeSniffer ruleset) against `protected/`, `config/`, root scripts targeting PHP 8.2 to get a definitive punch list. Use **Rector** (`LevelSetList::UP_TO_PHP_82`) for the mechanical fixes.
3. Known app-level fixes:
   - 3 × `each()` → `foreach`/`key()`+`next()`
   - ~83 × `$str{0}` curly-brace offsets → `$str[0]` (pure mechanical, Rector handles it)
   - Implicit float→int conversions, `count(null)`, string↔int comparisons (PHP 8 changed `0 == "foo"`) — these surface at runtime; rely on the regression checklist + enabling `YII_DEBUG` logging
4. **Extension replacements** (the real work of this phase):
   - PHPMailer 5.x (has `preg_replace /e` → fatal on PHP 7+): upgrade to PHPMailer 6 via Composer; the `Smtpmail`/`JPhpMailer` wrapper needs its API calls updated
   - mPDF old → **mPDF 8.x** (Composer); update `yii-pdf`/`ePdf` component glue. html2pdf → latest `spipu/html2pdf` if still used, else consolidate everything on mPDF
   - PHPExcel (dead, PHP 8-incompatible) → finish migrating remaining usages to **PhpSpreadsheet** (already in `vendor/`), then delete PHPExcel
   - Delete the dead copies: `yii-pdf-old`, `mpdf-old`, `bootstrap1`, `api-old`, `api-15-07`, `*-old.php` / dated model backups — they'll otherwise trip the compatibility scanners
5. **Introduce a real `composer.json`** at this point: PHPMailer 6, mPDF 8, PhpSpreadsheet, and later Yii2. One `vendor/`, one autoloader.
6. New PHP 8.2 image, `docker/php82/Dockerfile`:

```dockerfile
FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev \
        libonig-dev unzip git \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_mysql mysqli gd zip intl mbstring opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# php.ini overrides: memory_limit=512M (not 10G as in .htaccess!), max_input_vars as needed
```

   Run it as a **second compose service on a different port (e.g. 8094)** against the same MySQL 8 DB, so you can A/B the legacy 5.6 container vs the 8.2 container during the fix cycle, page by page.
7. Exit criteria: full regression checklist green on PHP 8.2, error log clean of deprecations, 5.6 container deleted.

## Phase 3 — Yii 1.1 → Yii 2 (strangler pattern)

Now the platform (PHP 8.2 + MySQL 8 + Docker) is stable; only the framework changes. A big-bang rewrite of 692 views is not realistic — use Yii 2's **official side-by-side mode** (Yii 2 guide: "Upgrading from Version 1.1" → "Using Yii 1.1 and 2.x together"): both frameworks run in one process, Yii 2 boots first and routes; unmigrated routes fall through to the Yii 1 app.

Order of migration (smallest coupling first):
1. **Skeleton**: create a Yii 2 basic-template app (`app2/` or a new `web/` root) via Composer, wired to the same DB, sharing session (same session name/save path) and auth (a Yii 2 `User` identity reading the same `tbl_user` table + a bridge so login state is shared with `WebUser`).
2. **Models**: regenerate with Yii 2 Gii into a new namespace (`app\models`). The Giix `_base` pattern maps cleanly. Port validation rules/relations as models are touched — don't port all 102 up front, do it per-module.
3. **API module first** (`protected/modules/api`) — JSON in/out, no views, easiest win, and Yii 2's REST support replaces most boilerplate.
4. **Reports** (GST reports, `gstreport*.php` root scripts) — fold the standalone root scripts into proper Yii 2 controllers/console commands while porting. Raw SQL mostly copies over via `Yii::$app->db->createCommand()`.
5. **Core POS flows** (orders, billing, items, stock) — the bulk. Port controller by controller; views go from `CActiveForm`/`CGridView`/bootstrap-ext widgets to `ActiveForm`/`GridView` + Bootstrap 5 (`yiisoft/yii2-bootstrap5`). This is the long tail; budget most of the phase here.
6. **Backup module, debugger** — replace with `yii2-app` tooling (`yii2-debug` module, mysqldump console command or `fossar/yii2-backup`-style approach).
7. Kill switch: when no routes fall through to Yii 1, remove `framework/`, `protected/`, `themes/bar`, and the bridge.

Yii1→Yii2 mechanical mapping cheat-sheet: `Yii::app()`→`Yii::$app`, `CActiveRecord`→`yii\db\ActiveRecord`, `model()->findByPk()`→`::findOne()`, `CDbCriteria`→`ActiveQuery`, `CHtml`→`Html`, `renderPartial`→`render`/`renderPartial`, `beforeSave()` signature change, scenarios via `rules()` 'on' → `scenarios()`.

## Phase 4 — Hardening & cleanup (start during Phase 0, finish by Phase 3)

- **Remove from webroot now**: `adminer.php`, `adminer-4.7.7-mysql.php`, `phpinfo.php`, `new 1.txt`, `Products.txt` / `SBProducts*.txt` (8 MB data dumps), dated backup scripts. phpMyAdmin container replaces adminer.
- **Secrets to environment**: DB password, Gmail SMTP creds, and the Interakt API key are hardcoded in `config/main.php` / `prod-db.php`. Read them from `getenv()` and inject via compose `environment:`/`.env`.
- Move the webroot: Yii 2 target layout serves only `web/` publicly, so `config/`, `protected/`, dumps, etc. are outside the docroot — fixes the whole class of "stray PHP file in webroot" problems.
- `.htaccess`: drop `memory_limit 10G`; set sane values in the image's php.ini instead.
- Add CI (even just a GitHub Action or local script): `composer validate`, PHPCompatibility/PHPStan, and the smoke tests.

## Target docker-compose (end state)

```yaml
version: "3.8"
services:
  php:
    build: ./docker/php82
    container_name: pos-php
    ports: ["8092:80"]
    volumes: ["./:/var/www/html/"]
    depends_on: [db]
    networks: [pos-net]
    environment:
      TZ: Asia/Kolkata
      DB_HOST: db
      DB_NAME: pos_live
      DB_USER: ${DB_USER}
      DB_PASSWORD: ${DB_PASSWORD}

  db:
    image: mysql:8.0
    container_name: pos-mysql
    restart: always
    ports: ["3307:3306"]
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: pos_live
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    # start permissive; tighten to STRICT_TRANS_TABLES,... after data cleanup (Phase 1)
    command: --sql-mode="" --default-authentication-plugin=mysql_native_password
    volumes: ["db_data:/var/lib/mysql"]
    networks: [pos-net]

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: pos-phpmyadmin
    ports: ["8093:80"]
    environment: { PMA_HOST: db }
    depends_on: [db]
    networks: [pos-net]

volumes: { db_data: }
networks: { pos-net: }
```

## Risk register

| Risk | Mitigation |
|---|---|
| Legacy data (zero dates, invalid enums) breaks under MySQL 8 strict mode | Start with `sql_mode=""`, clean data, tighten incrementally |
| PDF output differs after mPDF upgrade (invoices/bills are business-critical) | Golden-file compare: render the same bill on old/new stack, diff visually |
| PHP 8 behavior changes (`==` string/int comparisons) silently change report totals | Run old and new containers side by side against the same DB; diff report output |
| Session/auth breaks mid-Phase-3 with two frameworks | Shared session config + identity bridge; test login/logout early |
| Scope creep in Phase 3 | Strict module order; no redesigns during port — port first, improve after |
