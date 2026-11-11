#!/bin/sh
set -e

# Wait for DB DNS/internal network to be ready
sleep 5

# Run database migrations FIRST
php /var/www/html/artisan migrate --force

# Impor dump sekali: set IMPORT_DUMP=true, deploy, lalu HAPUS variabelnya.
if [ "$IMPORT_DUMP" = "true" ]; then
    php /var/www/html/artisan db:import-dump --force || echo "IMPORT DUMP GAGAL"
fi

# Seed admin + data awal (idempotent) jika INITIAL_ADMIN_PASSWORD diisi
if [ -n "$INITIAL_ADMIN_PASSWORD" ]; then
    php /var/www/html/artisan db:seed --class=ProductionSeeder --force
fi

# Data demo (akun, alat, pengajuan) jika SEED_DEMO_DATA=true. Hapus variabel setelah deploy pertama.
if [ "$SEED_DEMO_DATA" = "true" ]; then
    php /var/www/html/artisan db:seed --force
fi

# Tautan public/storage + foto katalog bawaan
php /var/www/html/artisan storage:link --force || true
php /var/www/html/artisan catalog:sync-photos || true

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache config, routes, and views AFTER migration
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:cache

# Start supervisord (nginx + php-fpm + queue + schedule)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf