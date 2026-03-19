#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1"
    exit 1
  fi
}

for cmd in php composer node npm mysql; do
  require_cmd "$cmd"
done

if [[ ! -f .env ]]; then
  if [[ -f .env.example ]]; then
    cp .env.example .env
    echo "Created .env from .env.example"
  else
    echo "Missing .env and .env.example"
    exit 1
  fi
fi

set -a
source .env
set +a

: "${DB_CONNECTION:=mysql}"
: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=3306}"
: "${DB_DATABASE:=shop}"
: "${DB_USERNAME:=root}"
: "${DB_PASSWORD:=}"
: "${DB_SOCKET:=}"

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

composer install --no-interaction --prefer-dist
npm install
npm run build

php artisan optimize:clear
php artisan storage:link || true

if [[ "$DB_CONNECTION" == "mysql" && "${SKIP_DB_IMPORT:-0}" != "1" ]]; then
  MYSQL_ARGS=(-u "$DB_USERNAME")

  if [[ -n "$DB_SOCKET" ]]; then
    MYSQL_ARGS+=(--socket="$DB_SOCKET")
  else
    MYSQL_ARGS+=(-h "$DB_HOST" -P "$DB_PORT")
  fi

  MYSQL_PWD="$DB_PASSWORD" mysql "${MYSQL_ARGS[@]}" \
    -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

  if [[ -f shop.sql ]]; then
    echo "Importing shop.sql into $DB_DATABASE (this can take a while)..."
    MYSQL_PWD="$DB_PASSWORD" mysql "${MYSQL_ARGS[@]}" "$DB_DATABASE" < shop.sql
  else
    echo "shop.sql not found; skipping SQL import."
  fi
else
  echo "Skipping MySQL import (DB_CONNECTION=$DB_CONNECTION, SKIP_DB_IMPORT=${SKIP_DB_IMPORT:-0})."
fi

echo "Starting app at http://127.0.0.1:8000"
php artisan serve --host=127.0.0.1 --port=8000
