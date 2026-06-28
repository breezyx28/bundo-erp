#!/usr/bin/env bash
# Encrypt local .env.production for safe commit + server-side decrypt on deploy.
#
# One-time: copy and edit production values
#   cp .env.example .env.production
#
# Encrypt (generates a key if you omit --key; save it as GitHub secret LARAVEL_ENV_ENCRYPTION_KEY):
#   ./scripts/encrypt-env-production.sh
#   ./scripts/encrypt-env-production.sh --key=base64:YOUR_KEY
#
# Commit the encrypted file only:
#   git add .env.production.encrypted
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env.production ]]; then
  echo "Missing .env.production — create it from .env.example first." >&2
  exit 1
fi

ARGS=(--env=production --force)
if [[ -n "${1:-}" ]]; then
  ARGS+=("$1")
fi

php artisan env:encrypt "${ARGS[@]}"

echo ""
echo "Next steps:"
echo "  1. Store the encryption key in GitHub → Settings → Secrets → LARAVEL_ENV_ENCRYPTION_KEY"
echo "  2. git add .env.production.encrypted && git commit -m 'Update encrypted production env'"
echo "  3. GitHub Actions decrypts .env in CI, rsyncs it to the server, and removes .env.production.encrypted there"
