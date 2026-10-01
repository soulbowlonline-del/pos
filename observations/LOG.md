# POS observation log

Newest entry at the bottom. One entry per day. A day with no activity still gets a
one-line entry, so a missing day means the observer didn't run.

---

## 2026-10-01 (Thu): baseline

**Repository state**
- Branches: `main` and `claude/gifted-rubin-fpbyxs`, both at `950ee56`.
- 6 commits in total, all by SoulBowl, from 12 Sep to 27 Sep 2026:
  - 12 Sep `28144bc` first commit (Yii 1.1.14 / PHP 5.6 app, Docker Phase 0)
  - 16 Sep `88a40cf` import-db.sh reads .env safely
  - 26 Sep `efa8883`, `cdbdc85`, `07c85b6` bind posted ids in SQL instead of concatenating `$_POST` (SQL-injection hardening)
  - 27 Sep `950ee56` atomic stock UPDATE so GRNs and sales running at the same time stop overwriting each other
- 0 pull requests, 0 issues, 0 releases, no GitHub Actions workflows (`.github/` is absent).
- Upgrade status (per `UPGRADE-PLAN.md`): Phase 0 (Docker baseline) done. Phase 1 (MySQL 8) not started.

**Automation candidates seen in the code today**
1. **Scheduled jobs are bare cURL scripts.** `timer_0.php` to `timer_3.php` call controller actions
   (`user/timer`, `item/addItems`, `onlineOrder/online`, `item/online`, `user/reorder`) with
   hard-coded hosts (`daspos.com`, `localhost:8091`, `192.168.100.16:8091`).
   `timer_0.php:6` has a broken URL, `http:/daspos.com/user/timer` (single slash), so that call
   probably never reaches the server. Suggestion: run them from a cron container in
   `docker-compose.legacy.yml` (or as Yii console commands), with the host taken from `.env`
   and failures logged.
2. **No CI.** There are no automatic checks on push. A small GitHub Actions workflow could
   run `php -l` on PHP 5.6 and 8.2 and grep for the PHP 8 blockers listed in `UPGRADE-PLAN.md`.
   It could also flag new SQL built by joining `$_POST`/`$_GET` strings, the bug class fixed on 26 Sep.
3. **Database migrations are applied by hand.** The `db_changes/*.sql` files are dated but nothing
   records which ones a database has had. Suggestion: a `schema_migrations` table and a one-command
   runner. The README already notes the perf-index file has to be re-applied after every import.
4. **GST reports are one-off scripts in the web root.** `gstreport*.php` and `b2bgstreport.php` are
   candidates for a monthly scheduled export (PDF/XLSX) sent by email.

**Note:** the container is replaced on every run, so "this computer" has no lasting local
state to watch. Each daily entry covers GitHub activity only.
