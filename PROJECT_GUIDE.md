# PINJAM-IN Project Guide

## Struktur Folder Utama

```
app/
├── Http/Controllers/           # Controller utama
│   ├── Auth/                   # Login, Register, Password Reset
│   ├── Siswa/                  # Fitur Siswa (Peminjaman, Riwayat)
│   ├── Admin/                  # Fitur Admin (Dashboard, Inventaris, dll)
│   └── Api/                    # API Endpoints
├── Models/                     # Eloquent Models
├── Services/                   # Business Logic (LoanService, dll)
├── Policies/                   # Authorization Policies
├── View/Components/            # Blade Components
├── Exceptions/                 # Custom Exceptions
└── Providers/                  # Service Providers

resources/
├── views/                      # Blade Templates
│   ├── layouts/                # Layout utama (app, guest, auth)
│   ├── auth/                   # Login, Register, Password Reset
│   ├── siswa/                  # Halaman Siswa
│   ├── admin/                  # Halaman Admin
│   └── components/             # Reusable components
├── css/                        # CSS Source (app.css)
├── js/                         # JavaScript Source (app.js)
└── lang/                       # Translations (id, en)

database/
├── migrations/                 # Database Migrations
├── seeders/                    # Database Seeders
└── factories/                  # Model Factories

config/
├── app.php                     # App configuration
├── auth.php                    # Auth configuration
├── database.php                # Database configuration
├── mail.php                    # Mail configuration
└── ...

public/
├── build/                      # Vite compiled assets (CSS/JS)
└── storage/                    # Symbolic link to storage/app/public
```

---

## Lokasi Fungsi Utama

### 1. Authentication (Login, Register, Password)

| Fungsi | File |
|--------|------|
| **Login Page** | `resources/views/auth/login.blade.php` |
| **Login Logic** | `app/Http/Controllers/Auth/AuthenticatedSessionController.php` |
| **Register Page** | `resources/views/auth/register.blade.php` |
| **Register Logic** | `app/Http/Controllers/Auth/RegisteredUserController.php` |
| **Password Reset Request** | `app/Http/Controllers/Auth/PasswordResetLinkController.php` |
| **Password Reset Form** | `app/Http/Controllers/Auth/NewPasswordController.php` |
| **Password Update** | `app/Http/Controllers/Auth/PasswordController.php` |
| **Logout** | `AuthenticatedSessionController@destroy` |
| **Auth Middleware** | `app/Http/Middleware/Authenticate.php` |
| **Redirect After Login** | `app/Providers/RouteServiceProvider.php` → `HOME` constant |

### 2. Siswa (Student) Features

| Fungsi | Controller | View |
|--------|------------|------|
| **Dashboard Siswa** | `Siswa\DashboardController` | `resources/views/siswa/dashboard.blade.php` |
| **Katalog Alat** | `KatalogController@index` | `resources/views/siswa/katalog.blade.php` |
| **Form Peminjaman** | `Siswa\LoanController@create` | `resources/views/siswa/loan/create.blade.php` |
| **Submit Peminjaman** | `Siswa\LoanController@store` | - |
| **Riwayat Peminjaman** | `Siswa\RiwayatController@index` | `resources/views/siswa/riwayat.blade.php` |
| **Detail Peminjaman** | `Siswa\LoanController@show` | `resources/views/siswa/loan/show.blade.php` |
| **Batal Peminjaman** | `Siswa\LoanController@cancel` | - |
| **Profil Siswa** | `ProfilController` | `resources/views/siswa/profil.blade.php` |

### 3. Admin Features

| Fungsi | Controller | View |
|--------|------------|------|
| **Dashboard Admin** | `Admin\DashboardController@index` | `resources/views/admin/dashboard.blade.php` |
| **Verifikasi Peminjaman** | `Admin\VerifikasiController` | `resources/views/admin/verifikasi.blade.php` |
| **Pengembalian Alat** | `Admin\ReturnController` | `resources/views/admin/return.blade.php` |
| **Inventaris (CRUD Alat)** | `Admin\InventarisController` | `resources/views/admin/inventaris/*.blade.php` |
| **Kategori Alat** | `Admin\KategoriController` | `resources/views/admin/kategori/*.blade.php` |
| **Kerusakan/Lost** | `Admin\KerusakanController` | `resources/views/admin/kerusakan.blade.php` |
| **Denda** | `Admin\FineController` | `resources/views/admin/fine.blade.php` |
| **Laporan** | `Admin\LaporanController` | `resources/views/admin/laporan.blade.php` |
| **Pengaturan** | `Admin\PengaturanController` | `resources/views/admin/pengaturan.blade.php` |
| **Audit Log** | `Admin\AuditLogController` | `resources/views/admin/audit-log.blade.php` |
| **Manajemen User** | `Admin\UserController` | `resources/views/admin/user/*.blade.php` |

### 4. Core Services

| Service | File | Fungsi |
|---------|------|--------|
| **LoanService** | `app/Services/LoanService.php` | Transisi status peminjaman, validasi stok |
| **AvailabilityService** | `app/Services/AvailabilityService.php` | Cek ketersediaan alat |
| **HandoverService** | `app/Services/HandoverService.php` | Proses serah terima alat |
| **NotificationService** | `app/Services/NotificationService.php` | Kirim notifikasi (email, database) |

### 5. Models Penting

| Model | File | Relasi Utama |
|-------|------|--------------|
| **User** | `app/Models/User.php` | hasMany Loan, hasOne Profile |
| **Item** | `app/Models/Item.php` | belongsTo Category, hasMany Loan |
| **Loan** | `app/Models/Loan.php` | belongsTo User, Item; hasMany Attachment |
| **Category** | `app/Models/Category.php` | hasMany Item |
| **Attachment** | `app/Models/Attachment.php` | belongsTo Loan |
| **Fine** | `app/Models/Fine.php` | belongsTo Loan, User |

---

## Cara Ubah Tampilan (UI)

### 1. Layout Utama

| Layout | File | Digunakan Oleh |
|--------|------|----------------|
| **App Layout (Logged In)** | `resources/views/layouts/app.blade.php` | Semua halaman authenticated |
| **Guest Layout** | `resources/views/layouts/guest.blade.php` | Login, Register, Password Reset |
| **Auth Layout** | `resources/views/layouts/auth.blade.php` | Halaman auth khusus |
| **Admin Layout** | `resources/views/layouts/admin.blade.php` | Halaman admin (jika terpisah) |

### 2. CSS & Styling

| File | Fungsi |
|------|--------|
| `resources/css/app.css` | Entry point CSS (import Tailwind, custom styles) |
| `tailwind.config.js` | Konfigurasi Tailwind (colors, fonts, content paths) |
| `postcss.config.js` | PostCSS plugins (Tailwind, Autoprefixer) |
| `vite.config.js` | Vite build configuration |

**Cara Ubah Warna/Tema:**
```javascript
// tailwind.config.js
theme: {
    extend: {
        colors: {
            primary: '#047857',     // emerald-700
            sidebar: '#1E293B',     // slate-800
            background: '#F8FAFC',  // slate-50
        },
        fontFamily: {
            sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
        },
    },
}
```

**Setelah ubah CSS/JS, jalankan:**
```bash
npm run build
```

### 3. Component Blade Reusable

| Component | File | Cara Pakai |
|-----------|------|------------|
| **Button** | `resources/views/components/button.blade.php` | `<x-button>Submit</x-button>` |
| **Input** | `resources/views/components/input.blade.php` | `<x-input name="email" />` |
| **Modal** | `resources/views/components/modal.blade.php` | `<x-modal>Content</x-modal>` |
| **Table** | `resources/views/components/table.blade.php` | `<x-table :data="$loans" />` |
| **Badge** | `resources/views/components/badge.blade.php` | `<x-badge color="green">Active</x-badge>` |
| **Card** | `resources/views/components/card.blade.php` | `<x-card>Content</x-card>` |
| **Image Viewer** | `resources/views/components/image-viewer.blade.php` | Auto via `$dispatch('open-viewer')` |

### 4. Halaman Khusus (Contoh Edit)

| Halaman | File View |
|---------|-----------|
| **Login** | `resources/views/auth/login.blade.php` |
| **Register** | `resources/views/auth/register.blade.php` |
| **Dashboard Siswa** | `resources/views/siswa/dashboard.blade.php` |
| **Dashboard Admin** | `resources/views/admin/dashboard.blade.php` |
| **Katalog** | `resources/views/siswa/katalog.blade.php` |
| **Form Peminjaman** | `resources/views/siswa/loan/create.blade.php` |
| **Verifikasi Admin** | `resources/views/admin/verifikasi.blade.php` |
| **Pengembalian** | `resources/views/admin/return.blade.php` |

### 5. JavaScript (Alpine.js + Vite)

| File | Fungsi |
|------|--------|
| `resources/js/app.js` | Entry point JS, init Alpine, Echo, components |
| `resources/js/echo.js` | Konfigurasi Laravel Echo (Reverb/Pusher) |
| `resources/js/components/` | Custom Alpine components |

**Contoh Tambah Interaktivitas:**
```javascript
// resources/js/app.js
import './components/loan-form.js'  // custom component
```

---

## Database & Migration

### Tabel Utama
```bash
php artisan migrate:status
```

| Tabel | Deskripsi |
|-------|-----------|
| `users` | User (admin, siswa, petugas) |
| `items` | Inventaris alat |
| `categories` | Kategori alat |
| `loans` | Peminjaman (status: pending, approved, rejected, borrowed, returned, overdue) |
| `attachments` | File bukti (KTP, foto alat) |
| `fines` | Denda keterlambatan/kerusakan |
| `handovers` | Record serah terima |
| `notifications` | Notifikasi in-app |
| `audit_logs` | Log aktivitas admin |

### Seeder
```bash
php artisan db:seed                    # Development seeder
php artisan db:seed --class=ProductionSeeder  # Production seeder
```

---

## Testing

| Test | Command |
|------|---------|
| **PHP Unit Tests** | `php artisan test` |
| **JavaScript Tests** | `npm run test:js` |
| **Specific Test** | `php artisan test --filter=LoanLifecycleTest` |

**Test Penting:**
- `tests/Feature/Siswa/LoanLifecycleTest.php` - Siklus peminjaman
- `tests/Feature/Admin/VerifikasiTest.php` - Verifikasi admin
- `tests/Feature/Admin/ReturnTest.php` - Pengembalian
- `tests/Feature/Siswa/AvailabilityServiceTest.php` - Cek stok

---

## Deployment (Railway/Docker)

### Environment Variables Penting
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://domain.railway.app

DB_URL=${MYSQL.MYSQL_URL}  # atau MYSQL_PRIVATE_URL

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...

VITE_REVERB_APP_KEY=...
VITE_REVERB_HOST=...
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

### Build & Deploy
```bash
# Local test Docker
docker build -t pinjam-alat .
docker run -p 8080:8080 --env-file .env pinjam-alat

# Deploy ke Railway
git push origin master
# Railway auto-deploy via Dockerfile
```

---

## Troubleshooting Umum

| Masalah | Solusi |
|---------|--------|
| **CSS/JS tidak load** | `npm run build` → cek `public/build/manifest.json` |
| **Migration gagal** | Cek `DB_URL` di Railway, pastikan MySQL service connected |
| **Reverb error** | Pastikan `REVERB_*` dan `VITE_REVERB_*` di-set, service Reverb running |
| **File upload error** | `php artisan storage:link`, cek `FILESYSTEM_DISK=local` |
| **Queue tidak jalan** | `QUEUE_CONNECTION=database`, jalankan `php artisan queue:work` |
| **Scheduler tidak jalan** | Cron `* * * * * php artisan schedule:run` di server |

---

## Quick Reference: Route Names

```bash
php artisan route:list --name=siswa
php artisan route:list --name=admin
```

| Name Prefix | Area |
|-------------|------|
| `siswa.*` | Halaman Siswa |
| `admin.*` | Halaman Admin |
| `login`, `register`, `password.*` | Auth |
| `loan.*` | Peminjaman |
| `item.*` | Inventaris |
| `category.*` | Kategori |
| `verification.*` | Verifikasi |
| `return.*` | Pengembalian |
| `fine.*` | Denda |
| `report.*` | Laporan |
| `audit.*` | Audit Log |
| `profile.*` | Profil |
| `settings.*` | Pengaturan |