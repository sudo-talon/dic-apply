#!/bin/bash
set -e

echo "============================================"
echo " Starting DIC Apply Application"
echo "============================================"

# ── 1. Fix PATH to find nginx ─────────────────────────────────────
export PATH="$PATH:/root/.nix-profile/bin:/usr/sbin:/usr/local/sbin:/nix/var/nix/profiles/default/bin"

# ── 2. Find nginx binary ──────────────────────────────────────────
echo "[1/7] Locating nginx..."
NGINX_BIN=$(which nginx 2>/dev/null || find /nix /usr/sbin /usr/local/sbin -name nginx -type f 2>/dev/null | head -1)

if [ -z "$NGINX_BIN" ]; then
  echo "ERROR: nginx binary not found. Aborting."
  exit 1
fi

echo "      nginx found at: $NGINX_BIN"

# ── 3. Detect and write nginx config path ────────────────────────
echo "[2/7] Detecting nginx config path..."
NGINX_CONF=$($NGINX_BIN -V 2>&1 | grep -o -- '--conf-path=[^ ]*' | cut -d= -f2)

if [ -z "$NGINX_CONF" ]; then
  echo "      Could not detect nginx conf path, using default."
  NGINX_CONF="/etc/nginx/nginx.conf"
fi

echo "$NGINX_CONF" > /app/nginx_conf_path.txt
echo "      nginx conf path: $NGINX_CONF"

# ── 4. Write nginx config ─────────────────────────────────────────
echo "[3/7] Writing nginx configuration..."
mkdir -p /var/log/nginx /var/cache/nginx /run
mkdir -p "$(dirname $NGINX_CONF)"

cat > "$NGINX_CONF" <<'EOF'
user root;
worker_processes auto;
pid /run/nginx.pid;
error_log /var/log/nginx/error.log warn;

events {
    worker_connections 1024;
}

http {
    include       /etc/nginx/mime.types;
    default_type  application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;

    sendfile        on;
    keepalive_timeout 65;
    client_max_body_size 100M;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    server {
        listen 80;
        server_name _;
        root /app/public;
        index index.php index.html;

        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-Content-Type-Options "nosniff";
        add_header X-XSS-Protection "1; mode=block";

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        error_page 404 /index.php;

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_read_timeout 300;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
EOF

echo "      nginx config written to: $NGINX_CONF"

# ── 5. Laravel bootstrap ──────────────────────────────────────────
echo "[4/7] Running Laravel bootstrap..."

cd /app

mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

chmod -R 775 storage bootstrap/cache

if [ -n "$APP_KEY" ]; then
  echo "      Caching config, routes and views..."
  php artisan config:cache || echo "WARN: config:cache failed"
  php artisan route:cache  || echo "WARN: route:cache failed"
  php artisan view:cache   || echo "WARN: view:cache failed"
else
  echo "WARN: APP_KEY not set, skipping artisan cache commands."
fi

# ── 6. Start PHP-FPM ─────────────────────────────────────────────
echo "[5/7] Starting PHP-FPM..."
PHP_FPM_BIN=$(which php-fpm81 2>/dev/null || which php-fpm 2>/dev/null || find /nix -name 'php-fpm*' -type f 2>/dev/null | head -1)

if [ -z "$PHP_FPM_BIN" ]; then
  echo "ERROR: php-fpm not found. Aborting."
  exit 1
fi

echo "      php-fpm found at: $PHP_FPM_BIN"
$PHP_FPM_BIN -D
echo "      PHP-FPM started."

# ── 7. Start nginx ────────────────────────────────────────────────
echo "[6/7] Starting nginx..."
echo "============================================"
echo " Application is live on port 80"
echo " URL: https://apply.dic.gov.ng"
echo "============================================"

$NGINX_BIN -g "daemon off;"
