#!/bin/bash
mkdir -p /nix/store/jxpzay1xrcphcd05wb7i7vysy89pkmn8-php-8.1.31/etc

cat > /nix/store/jxpzay1xrcphcd05wb7i7vysy89pkmn8-php-8.1.31/etc/php-fpm.conf << 'EOF'
[global]
daemonize = no

[www]
user = nobody
group = nobody
listen = 127.0.0.1:9000
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
clear_env = no
EOF

php-fpm &
sleep 2
nginx -g "daemon off;"
