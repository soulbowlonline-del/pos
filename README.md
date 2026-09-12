# POS (DASPOS)

Point-of-sale application built on **Yii 1.1.14 / PHP 5.6 / MySQL**, run in Docker.
This is the legacy baseline being prepared for migration to PHP 8 + Yii 2 + MySQL 8
(see `UPGRADE-PLAN.md`).

## Quick start (Docker)

```bash
# 1. Configure environment
cp .env.example .env          # then fill in real secrets

# 2. Install PHP dependencies (vendor/ is not committed)
docker run --rm -v "$(pwd)":/app -w /app composer:2 install --ignore-platform-reqs

# 3. Build + start the stack
docker compose -f docker-compose.legacy.yml up -d --build

# 4. Import the database (see docker/README-phase0.md)
./docker/import-db.sh ~/path/to/pos_live.sql.gz

# 5. Re-apply the performance indexes (not in the dump)
docker compose -f docker-compose.legacy.yml exec -T db \
  mysql -uroot -p"$DB_ROOT_PASSWORD" pos_live < db_changes/2026-07-11-phase0-perf-indexes.sql
```

- App: http://localhost:8082
- phpMyAdmin: http://localhost:8083
- MySQL: host port 3309

Full setup, performance notes, and the smoke-test checklist: **`docker/README-phase0.md`**.

## Configuration & secrets

All credentials (DB, SMTP, Interakt API, FTP) live in **`.env`** (gitignored) and
are read by `config/` via a small loader in `common.php`. Never commit real
secrets — copy `.env.example` and fill it in locally.

## Layout

- `protected/` — Yii application (models, controllers, views, components, modules)
- `framework/` — bundled Yii 1.1.14
- `ext-prod/` — bundled extensions actually used: `bootstrap`, `mpdf`
- `config/` — application config (secrets externalized to `.env`)
- `db_changes/` — SQL migrations
- `docker/` — Dockerfile, compose helpers, Phase 0 docs
- `vendor/` — composer-managed (`composer install`); not committed
