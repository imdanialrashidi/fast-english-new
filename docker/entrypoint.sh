#!/bin/sh
# S9a entrypoint: prepare runtime caches with REAL env, never migrate.
# Build-time caches (config/route/view/event/Filament/storage link) prove
# caching works with dummy env; here config is re-cached with the real
# Coolify env so runtime secrets take effect. Migrations NEVER run on
# container start; release runs one explicit `php artisan migrate --force`
# after a fresh backup (docs/ops/RUNBOOK.md section 1 step 4).
set -e

# storage/app/private + storage/app/public are persistent Coolify mounts;
# ensure the public symlink exists even when mounts hide build-time state.
php artisan storage:link --force || true

# Re-cache config with runtime env (real APP_KEY/DB/MAIL). Route/view/event
# caches from the build do not contain env and are kept as-is.
php artisan config:cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf
