#!/usr/bin/env sh
set -e

mkdir -p \
  bootstrap/cache \
  public/fotos_perfil/pacientes \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ -z "${APP_KEY:-}" ] && ! grep -Eq '^APP_KEY=base64:.+' .env; then
  php artisan key:generate --force --no-ansi
fi

php artisan storage:link --force --no-ansi >/dev/null 2>&1 || true
php artisan config:clear --no-ansi >/dev/null 2>&1 || true

chown -R www-data:www-data bootstrap/cache public/fotos_perfil storage 2>/dev/null || true

exec "$@"
