#!/usr/bin/env bash
# Run on the server after code is synced to /var/www/html/erp
# Expects .env to already exist (decrypted and rsync'd by GitHub Actions).
set -euo pipefail

APP_DIR="/var/www/html/erp"
cd "$APP_DIR"

if [[ ! -f .env ]]; then
  echo ".env not found — deploy via GitHub Actions or copy .env manually." >&2
  exit 1
fi

rm -f .env.production.encrypted

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/{public,private,backups}
mkdir -p bootstrap/cache

chmod 640 .env 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart || true

if id www-data &>/dev/null; then
  sudo chown -R www-data:www-data storage bootstrap/cache
  sudo chmod -R ug+rwx storage bootstrap/cache
fi

php artisan up || true

echo "Deploy finished: $(date -Is)"
