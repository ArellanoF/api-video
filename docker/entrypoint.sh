#!/usr/bin/env bash
set -e

wait_for_db() {
  until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'OK'; } catch(Exception \$e) { exit(1); }" >/dev/null 2>&1
  do
    echo "DB not ready yet, sleeping 2s..."
    sleep 2
  done
  echo "Database ready!"
}

if [ -n "$DB_HOST" ] && [ -n "$DB_CONNECTION" ]; then
  wait_for_db
fi

# Migrations (only if AUTO_MIGRATE=1)
if [ "$AUTO_MIGRATE" = "1" ]; then
  php artisan migrate || true
  php artisan queue:table || true
  php artisan migrate || true
fi

# Configure cron for Laravel scheduler
# Run schedule loop in background (every 1 minute)
( while true; do
    cd /var/www/html
    php artisan schedule:run --verbose
    sleep 60
  done ) &


# Launch apache as PID1
exec apache2-foreground