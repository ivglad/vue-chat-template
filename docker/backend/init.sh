#!/bin/sh

cd /var/www/backend

if [ ! -f .env ]; then
    echo "ERROR: .env file not found"
    exit 1
fi

composer install --optimize-autoloader --ignore-platform-reqs || echo "Composer install completed with warnings"

php artisan key:generate --force || echo "Key generation skipped"
php artisan config:cache || echo "Config cache failed"
php artisan route:cache || echo "Route cache failed"  
php artisan view:cache || echo "View cache failed"

php artisan migrate --force || echo "Migration failed"

php artisan storage:link || echo "Storage link failed"

echo "Laravel initialization completed"

/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf 