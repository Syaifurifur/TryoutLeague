#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

if [ ! -L public/storage ] && [ -d storage/app/public ]; then
  su -s /bin/sh www-data -c "php artisan storage:link" || true
fi

# Do not run migrations automatically here. Run them once as a controlled
# Dokploy deployment/release command after the database is healthy.
if [ "$#" -gt 0 ]; then
  exec "$@"
fi

exec supervisord -n -c /etc/supervisor/supervisord.conf
