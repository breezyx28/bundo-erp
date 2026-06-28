#!/usr/bin/env bash
# Ensure the SQLite database file exists and is writable by the web server.
set -euo pipefail

APP_DIR="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$APP_DIR"

db_path="database/database.sqlite"

if [[ -f .env ]]; then
  line="$(grep -E '^DB_DATABASE=' .env | tail -1 || true)"
  if [[ -n "$line" ]]; then
    value="${line#DB_DATABASE=}"
    value="${value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"
    if [[ -n "$value" ]]; then
      db_path="$value"
    fi
  fi
fi

if [[ "$db_path" != /* ]]; then
  db_path="${APP_DIR}/${db_path}"
fi

db_dir="$(dirname "$db_path")"
mkdir -p "$db_dir"

if [[ ! -f "$db_path" ]]; then
  touch "$db_path"
  echo "Created SQLite database: $db_path"
fi

chmod 664 "$db_path" 2>/dev/null || true
chmod 775 "$db_dir" 2>/dev/null || true

if id www-data &>/dev/null; then
  if command -v sudo &>/dev/null; then
    sudo chown www-data:www-data "$db_path" "$db_dir" 2>/dev/null || true
  else
    chown www-data:www-data "$db_path" "$db_dir" 2>/dev/null || true
  fi
fi

echo "SQLite ready: $db_path"
