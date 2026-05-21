#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "[BOOT] ✗ ERROR: APP_KEY is not set" >&2
    echo "[BOOT] ACTION: Set APP_KEY in Render Environment" >&2
    exit 1
fi

echo "[BOOT] ✓ APP_KEY is set"
echo "[BOOT] APP_ENV=${APP_ENV:-not set}"
echo "[BOOT] DB_CONNECTION=${DB_CONNECTION:-not set}"
echo "[BOOT] DB_URL=${DB_URL:-not set}"
echo "[BOOT] DATABASE_URL=${DATABASE_URL:-not set}"

db_url="${DB_URL:-${DATABASE_URL:-}}"

if [ -n "$db_url" ]; then
    db_host=$(echo "$db_url" | sed -E 's|.*://[^@]*@([^/:]+).*|\1|')
    echo "[BOOT] Extracted DB_HOST from URL: $db_host"
fi

if [ -z "$db_url" ]; then
    echo "[BOOT] ✗ ERROR: No database URL found" >&2
    echo "[BOOT] ACTION: Set DB_URL in Render Environment" >&2
    exit 1
fi

echo "[BOOT] ✓ Found database URL, forcing pgsql"
export DB_URL="$db_url"
export DATABASE_URL="$db_url"
export DB_CONNECTION=pgsql
export DB_PORT="${DB_PORT:-5432}"
export DB_SSLMODE="${DB_SSLMODE:-require}"

echo "[BOOT] ✓ DB_CONNECTION=pgsql | DB_PORT=5432"
echo "[BOOT] ✓ Running php artisan config:cache..."

attempt=1
until php artisan migrate --force; do
    if [ "$attempt" -ge 10 ]; then
        echo "[BOOT] ✗ Database migrations failed after 10 attempts." >&2
        exit 1
    fi

    echo "[BOOT] Retry $attempt/10: Waiting for database..."
    attempt=$((attempt + 1))
    sleep 3
done

echo "[BOOT] ✓ Migrations complete"
echo "[BOOT] ✓ Ensuring demo login users exist..."
if [ "${SEED_ON_BOOT:-false}" = "true" ] || [ "${APP_ENV:-}" = "local" ] || [ "${APP_ENV:-}" = "development" ]; then
    php artisan db:seed --class=DemoLoginSeeder --force
else
    echo "[BOOT] Skipping DemoLoginSeeder (SEED_ON_BOOT not enabled and APP_ENV is not local/development)"
fi
echo "[BOOT] ✓ Caching routes..."
php artisan view:cache

echo "[BOOT] ✓ Starting nginx + php-fpm on port 8080..."
export PORT="${PORT:-8080}"
template=""
if [ -f /etc/nginx/nginx.conf.template ]; then
    template=/etc/nginx/nginx.conf.template
elif [ -f /tmp/nginx.conf.template ]; then
    template=/tmp/nginx.conf.template
else
    echo "[BOOT] ✗ ERROR: can't find nginx.conf.template in /etc/nginx or /tmp" >&2
    exit 1
fi

envsubst '$PORT' < "$template" > /tmp/nginx.conf

php-fpm -F &
fpm_ready=0
for i in 1 2 3 4 5 6 7 8 9 10; do
    if php -r '$s=@fsockopen("127.0.0.1",9000,$e,$m,1); if($s){fclose($s); exit(0);} exit(1);'; then
        fpm_ready=1
        break
    fi
    echo "[BOOT] Waiting for php-fpm to accept connections... ($i/10)"
    sleep 1
done

if [ "$fpm_ready" -ne 1 ]; then
    echo "[BOOT] ✗ ERROR: php-fpm did not become ready in time" >&2
    exit 1
fi

exec nginx -e /dev/stderr -c /tmp/nginx.conf -g 'daemon off;'