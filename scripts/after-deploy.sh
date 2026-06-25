#!/usr/bin/env bash
# Run on the LIVE SERVER after git pull or FTP upload.
# Clears Laravel caches so new code is used (not old cached routes/views/config).

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "Deploy cleanup in: $ROOT"

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Done. Server is using the latest deployed code."
