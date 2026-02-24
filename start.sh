#!/bin/bash

# Create www-data user if it doesn't exist
if ! id -u www-data > /dev/null 2>&1; then
    adduser --system --no-create-home --group www-data 2>/dev/null || \
    adduser -S -G www-data www-data 2>/dev/null || \
    useradd -r -s /bin/false www-data 2>/dev/null
fi

mkdir -p /nix/store/jxpzay1xrcphcd05wb7i7vysy89pkmn8-php-8.1.31/etc

cat > /nix/store/jxpzay1xrcphcd05wb7i7vysy89pkmn8-php-8.1.31/etc/php-fpm.conf << 'EOF'
[global]
daemonize = no

[www]
user = www-data
group = www-data
listen = 127.0.0.1:9000
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
clear_env = no
EOF

# Find nginx config directory
NGINX_CONF_DIR=""
if [ -d /etc/nginx/conf.d ]; then
    NGINX_CONF_DIR="/etc/nginx/conf.d"
elif [ -d /etc/nginx/sites-enabled ]; then
    NGINX_CONF_DIR="/etc/nginx/sites-enabled"
else
    # Find nginx config location
    NGINX_CONF_DIR=$(nginx -T 2>/dev/null | grep "conf.d" | head -1 | awk '{print $2}' | xargs dirname 2>/dev/null)
    mkdir -p "$NGINX_CONF_DIR"
fi

echo "Using nginx config dir: $NGINX_CONF_DIR"

# Remove default configs
rm -f "$NGINX_CONF_DIR/default"
rm -f "$NGINX_CONF_DIR/default.conf"

# Write nginx config
cat > "$NGINX_CONF_DIR/laravel.conf" << 'EOF'
server {
    listen 80 default_server;
    server_name _;
    root /app/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

php artisan migrate --force || true
php-fpm &
sleep 2
nginx -g "daemon off;"
