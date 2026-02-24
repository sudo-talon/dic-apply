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

php artisan migrate --force || true
php-fpm &
sleep 2
nginx -g "daemon off;"
