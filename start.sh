#!/bin/bash
mkdir -p /nix/store/jxpzay1xrcphcd05wb7i7vysy89pkmn8-php-8.1.31/etc

# Get first available non-root user
PHPUSER=$(cat /etc/passwd | grep -v root | grep -v nologin | grep -v halt | grep -v shutdown | awk -F":" '{ print $1 }' | head -1)

# Fallback to www-data if no user found
if [ -z "$PHPUSER" ]; then
  PHPUSER="www-data"
fi

cat > /nix/store/jxpzay1xrcphcd05wb7i7vysy89pkmn8-php-8.1.31/etc/php-fpm.conf << EOF
[global]
daemonize = no

[www]
user = $PHPUSER
group = $PHPUSER
listen = 127.0.0.1:9000
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
clear_env = no
EOF

echo "Starting php-fpm with user: $PHPUSER"
php-fpm &
sleep 2
nginx -g "daemon off;"
