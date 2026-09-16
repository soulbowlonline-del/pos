#!/usr/bin/env bash
# Import a MySQL dump into the legacy (Phase 0) db container.
# Streams a .sql or .sql.gz straight into the container — no need to copy the
# 1.6 GB file inside first.
#
# Usage:
#   ./docker/import-db.sh ~/Documents/pos_live.sql.gz
#   ./docker/import-db.sh ~/Documents/pos_live.sql
#
set -euo pipefail

DUMP="${1:-}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.legacy.yml}"
SERVICE="db"

if [[ -z "$DUMP" || ! -f "$DUMP" ]]; then
  echo "Usage: $0 <path-to-dump.sql[.gz]>" >&2
  exit 1
fi

# Read only the two DB values we need from .env — WITHOUT shell-sourcing it.
# (Sourcing would try to execute values that contain spaces, e.g. an SMTP app
#  password like "tjuu aevc tpcb abls".) Strips optional surrounding quotes.
read_env() {
  sed -n "s/^$1=//p" ./.env | tail -1 | sed -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'\$/\1/"
}
DB_ROOT_PASSWORD=$(read_env DB_ROOT_PASSWORD)
DB_NAME=$(read_env DB_NAME)
: "${DB_ROOT_PASSWORD:?set in .env}"
: "${DB_NAME:?set in .env}"

echo ">> Waiting for MySQL to accept connections..."
until docker compose -f "$COMPOSE_FILE" exec -T "$SERVICE" \
      mysqladmin ping -uroot -p"$DB_ROOT_PASSWORD" --silent >/dev/null 2>&1; do
  sleep 2
done

echo ">> Ensuring database '$DB_NAME' exists..."
docker compose -f "$COMPOSE_FILE" exec -T "$SERVICE" \
  mysql -uroot -p"$DB_ROOT_PASSWORD" \
  -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8 COLLATE utf8_general_ci;"

echo ">> Importing $DUMP into '$DB_NAME' (this can take several minutes for large dumps)..."
if [[ "$DUMP" == *.gz ]]; then
  gunzip -c "$DUMP" | docker compose -f "$COMPOSE_FILE" exec -T "$SERVICE" \
    mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_NAME"
else
  docker compose -f "$COMPOSE_FILE" exec -T "$SERVICE" \
    mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_NAME" < "$DUMP"
fi

echo ">> Done. Tables in '$DB_NAME':"
docker compose -f "$COMPOSE_FILE" exec -T "$SERVICE" \
  mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_NAME" -e "SHOW TABLES;" | head -20
