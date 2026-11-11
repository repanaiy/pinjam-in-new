# Panduan Deploy Produksi — PINJAM.ALAT

## 1. Prasyarat Server

- Ubuntu 22.04+ atau sistem operasi serupa
- PHP 8.2+ dengan ekstensi: `pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`, `fileinfo`, `pdo_sqlite` (untuk test), `bcmath`, `opcache`
- Nginx
- MySQL 8.0+
- Composer 2
- Node.js 18+ & npm (untuk build asset)
- Supervisor (opsional, untuk queue worker)
- Redis (opsional, untuk cache/queue)

## 2. Persiapan Kode

```bash
git clone <repo-url> /var/www/pinjam-alat
cd /var/www/pinjam-alat
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 3. Konfigurasi Environment

Salin dan edit `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

Konfigurasi produksi penting:

```env
APP_NAME="PINJAM-IN"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pinjam-alat.sman-nusantara.sch.id

APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pinjam_alat
DB_USERNAME=pinjam_user
DB_PASSWORD=strong_password_here

QUEUE_CONNECTION=database    # atau 'redis' untuk performa lebih
CACHE_STORE=redis            # atau 'file' jika tidak ada Redis
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=sarpras@sman-nusantara.sch.id
MAIL_PASSWORD=app_password_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="sarpras@sman-nusantara.sch.id"
MAIL_FROM_NAME="${APP_NAME}"

FILESYSTEM_DISK=local
```

## 4. Database

Buat database dan user:

```sql
CREATE DATABASE pinjam_alat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pinjam_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON pinjam_alat.* TO 'pinjam_user'@'localhost';
FLUSH PRIVILEGES;
```

Jalankan migrasi dan seeder produksi:

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
```

## 5. Nginx

Buat konfigurasi `/etc/nginx/sites-available/pinjam-alat`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name pinjam-alat.sman-nusantara.sch.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name pinjam-alat.sman-nusantara.sch.id;

    root /var/www/pinjam-alat/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/pinjam-alat.sman-nusantara.sch.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pinjam-alat.sman-nusantara.sch.id/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
```

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/pinjam-alat /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 6. SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d pinjam-alat.sman-nusantara.sch.id
sudo certbot renew --dry-run
```

## 7. PHP Configuration

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 64M
memory_limit = 256M
max_execution_time = 60
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.validate_timestamps = 0    # Set 1 di development, 0 di production
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
```

## 8. Scheduler (Cron)

Tambahkan cron untuk user `www-data` atau user yang menjalankan aplikasi:

```bash
sudo crontab -u www-data -e
```

Tambahkan baris:

```cron
* * * * * cd /var/www/pinjam-alat && php artisan schedule:run >> /dev/null 2>&1
```

Cek daftar job scheduler:

```bash
php artisan schedule:list
php artisan schedule:test
```

Job scheduler:
| Job | Jadwal | Tugas |
|---|---|---|
| `PromoteReadyPickup` | harian 06:00 | `approved` dengan `pickup_date` hari ini → `ready_pickup` |
| `SendDueReminders` | harian 07:00 | pengingat H-1 dan hari-H untuk `borrowed` |
| `MarkOverdue` | harian 15:30 | `borrowed` dengan `end_date` < hari ini → `overdue` |
| `ExpireUnclaimed` | harian 15:30 | `approved`/`ready_pickup` lewat jadwal + toleransi → `expired` |
| `RemindAdminPending` | tiap jam 07–15, Senin–Jumat | `pending` melewati SLA → notifikasi ke admin |

## 9. Queue Worker (Supervisor)

Jika menggunakan queue untuk email/notifikasi latar belakang, pasang Supervisor:

```bash
sudo apt install supervisor
```

Buat konfigurasi `/etc/supervisor/conf.d/pinjam-alat-worker.conf`:

```ini
[program:pinjam-alat-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/pinjam-alat/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/pinjam-alat/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pinjam-alat-worker:*
```

## 10. Backup Otomatis

Pasang package backup:

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Konfigurasi `config/backup.php`:

```php
return [
    'backup' => [
        'name' => 'pinjam-alat',
        'source' => [
            'files' => [
                'include' => [
                    storage_path('app'),
                ],
            ],
            'databases' => ['mysql'],
        ],
        'destination' => [
            'disks' => ['local'],
        ],
    ],
    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 30,
            'keep_monthly_backups_for_months' => 3,
        ],
    ],
];
```

Tambahkan ke scheduler (`app/Console/Kernel.php` atau route):

```php
$schedule->command('backup:clean')->daily()->at('01:00');
$schedule->command('backup:run --only-db')->daily()->at('02:00');
```

Backup manual:

```bash
php artisan backup:run
```

File backup tersimpan di `storage/app/pinjam-alat/`.

## 11. Keamanan

Checklist:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` dihasilkan dan unik
- [ ] Database password kuat
- [ ] SSL/HTTPS aktif
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] File `.env` tidak bisa diakses publik (Nginx sudah blok `.`)
- [ ] Folder `storage` dan `bootstrap/cache` writable:
  ```bash
  sudo chown -R www-data:www-data /var/www/pinjam-alat/storage
  sudo chown -R www-data:www-data /var/www/pinjam-alat/bootstrap/cache
  sudo chmod -R 775 /var/www/pinjam-alat/storage
  sudo chmod -R 775 /var/www/pinjam-alat/bootstrap/cache
  ```
- [ ] Rate limiting login aktif (default: 5 per menit)
- [ ] Password default akun demo diganti
- [ ] Foto demo diganti dengan foto asli (`database/seeders/assets/demo/`)

## 12. Monitoring & Log

- Log Laravel: `storage/logs/laravel.log`
- Log Nginx: `/var/log/nginx/error.log`
- Log Supervisor: `storage/logs/worker.log`
- Monitor queue: `php artisan queue:monitor database`

Rotasi log otomatis:

```bash
sudo apt install logrotate
```

Konfigurasi sudah ada di `/etc/logrotate.d/`.

## 13. Pemecahan Masalah

| Gejala | Penyebab & Solusi |
|---|---|
| 500 error di semua halaman | Cek `storage/logs/laravel.log`. Biasanya permission atau `.env` salah |
| Upload video gagal | Naikkan `upload_max_filesize` dan `post_max_size` di `php.ini` |
| Email tidak terkirim | Cek `MAIL_*` di `.env`. Test dengan `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('email@domain.com'))` |
| Scheduler tidak jalan | Pastikan cron terpasang. Cek dengan `php artisan schedule:list` |
| Queue tidak diproses | Pastikan Supervisor jalan: `sudo supervisorctl status` |
| Asset tidak load | Jalankan `npm run build` dan pastikan `public/build/manifest.json` ada |
| `Interface "Maatwebsite\Excel\..." not found` | Jalankan `composer require maatwebsite/excel barryvdh/laravel-dompdf` |

## 14. Update Aplikasi

```bash
cd /var/www/pinjam-alat
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.2-fpm
```

## 15. CI/CD (GitHub Actions)

Buat `.github/workflows/test.yml`:

```yaml
name: Test

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: pinjam_alat_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, mysql, gd, zip, pdo_sqlite
          coverage: xdebug

      - name: Copy .env
        run: php -r "file_exists('.env') || copy('.env.example', '.env');"

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Generate key
        run: php artisan key:generate

      - name: Directory Permissions
        run: chmod -R 777 storage bootstrap/cache

      - name: Run Tests
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: :memory:
        run: php artisan test

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '18'
          cache: 'npm'

      - name: Install Node Dependencies
        run: npm ci

      - name: Run JS Tests
        run: npm run test:js
```

## 16. QR/Barcode untuk Serah Terima (Opsional)

Pasang library:

```bash
composer require simplesoftwareio/simple-qrcode
```

Contoh: Generate QR di `LoanService` atau `HandoverController`:

```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$qrCode = QrCode::size(200)->generate(route('pengajuan.show', $loan->id));
```

Tampilkan di bukti peminjaman PDF atau halaman serah terima. Scan dengan aplikasi QR scanner atau implementasikan scanner di frontend (library JS: `html5-qrcode`).

## 17. Real-time Notifikasi (Opsional)

Gunakan Pusher atau Soketi:

```bash
composer require pusher/pusher-php-server
npm install --save-dev laravel-echo pusher-js
```

Konfigurasi `.env`:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

Di frontend (`resources/js/bootstrap.js`):

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});
```

Listen notifikasi di Blade/JS:

```js
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log(notification);
    });
```

## 18. API untuk Mobile (Opsional)

Pasang Sanctum:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Konfigurasi `config/sanctum.php` dan buat API routes di `routes/api.php`:

```php
use App\Http\Controllers\Api\LoanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loans', [LoanController::class, 'store']);
    // ...
});
```

---

## Checklist Deploy

- [ ] Server siap (PHP, Nginx, MySQL)
- [ ] Kode di-clone dan dependency terpasang
- [ ] `.env` dikonfigurasi benar
- [ ] Database dibuat dan migrasi dijalankan
- [ ] SSL aktif
- [ ] Cron scheduler terpasang
- [ ] Queue worker jalan (jika pakai queue)
- [ ] Backup otomatis aktif
- [ ] Monitoring log aktif
- [ ] CI/CD terpasang (opsional)
- [ ] Keamanan dicek
- [ ] Foto demo diganti dengan foto asli
- [ ] Password akun demo diganti
