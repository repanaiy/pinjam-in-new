# PINJAM-IN Agent Guidelines

Web + mobile (Flutter) loan management system. Web is a single-package Laravel 11 app (Breeze + Alpine + Vite) that is **overlaid** onto a
separate Laravel 11 + Breeze project by `scripts/setup.sh`. Mobile is a Flutter app in `mobile/` using Provider + http package.
This repo holds web code (migrations, seeders, tests, assets), mobile code, and the setup script; the target web project is
created/merged by the setup script.

## Repository layout

- `app/` – code: `Http/Controllers/*` (admin + `Siswa/`), `Services/*`,
  `Models/*`, `Policies/`, `View/Components/*`, `Exceptions/*`.
- `routes/web.php` – entrypoint for the web UI. Roles are enforced by the
  `role` middleware (alias `App\Http\Middleware\EnsureRole`). Controllers:
  `admin.*` routes under `role:admin`, `pengajuan.*` under `role:siswa`,
  `berkas.*` / `notifikasi.*` / `profil.*` under `role:siswa,admin`
  (authorization per row is in `LoanPolicy`, not routes).
- `database/migrations/` – 8 up migrations. Loans rows have a `CHECK (end_date >= start_date)`
  added only for MySQL/MariaDB; SQLite tests cannot use `ALTER TABLE ... ADD CONSTRAINT`.
- `database/seeders/` – `DatabaseSeeder` runs: `SettingsSeeder`, `HolidaySeeder`,
  `UserSeeder`, `CategorySeeder`, `ItemSeeder`, `ItemPhotoSeeder`, `DemoLoanSeeder`.
  Demo loans deliberately reuse the `REQ-YYYY-NNNN` code pattern.
- `tests/Feature` – PHPUnit feature suite (PHPUnit 11, `phpunit.xml` sets
  `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `QUEUE_CONNECTION=sync`,
  `MAIL_MAILER=array`, `CACHE_STORE=array`, `APP_ENV=testing`).
  `npm run test:js` runs `node --test` over `tests/js/*.test.mjs` (Alpine/Vite assets).
- `scripts/build-assets.js` – production asset builder. It reads `VITE_REVERB_*`
  from the shell env > `.env.production` > `DEFAULTS`, then runs `npx vite build`
  with those values injected. `npm run build:prod` uses `.env.production`.
- `mobile/` – Flutter app (Dart 3, SDK `^3.3.0`). Layout:
  - `lib/models/` – plain `fromJson` models (no codegen). `User`, `Item`, `Loan`,
    `TrackerStep`, `AuthResponse`.
  - `lib/services/services.dart` – `ApiService`, the only HTTP layer. Reads the
    token from `SharedPreferences` (`auth_token`) and sends `Authorization:
    Bearer <token>`.
  - `lib/providers/` – `AuthProvider`, `ItemProvider`, `LoanProvider`
    (`ChangeNotifier`, wired with `provider` package).
  - `lib/screens/screens.dart` – **all** screens live in one file (login, home,
    catalog, item detail, loan form, loan list, loan detail). Named routes are
    registered in `lib/main.dart` via `onGenerateRoute` (`/items/{id}`,
    `/loans/{id}`, `/loans/form/{itemId}`).
  - `lib/main.dart` – `MultiProvider` setup + route table.

## Setup / commands

- Prerequisites: PHP 8.2+ (`pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`, `fileinfo`);
  `pdo_sqlite` is required only for `php artisan test`; Composer 2; Node 18+ & npm.
- Web app bootstrap is done by the setup script into a separate target directory
  (`bash setup.sh pinjam-alat-app`). Inside that target:
  - `mysql -u root -e "CREATE DATABASE pinjam_alat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"`
  - `cd pinjam-alat-app`; edit `.env` `DB_USERNAME` / `DB_PASSWORD`.
  - `php artisan migrate --seed`
  - `npm run dev` (Vite watch) and `php artisan serve` in two terminals.
- Run tests: `php artisan test` (all suites). Suites: `Unit` and `Feature` from
  `tests/`, plus `npm run test:js`. For a single PHPUnit file:
  `./vendor/bin/phpunit tests/Feature/Siswa/LoanSubmissionTest.php`.
- JS: `npm run build` (dev), `npm run build:prod` (production, via
  `scripts/build-assets.js`), `npm run dev`.
- Linting/formatting: project uses `laravel/pint` (dev dependency).
- Mobile (run inside `mobile/`):
  - `flutter pub get`
  - `flutter run` (device / emulator) – assumes a reachable Laravel API.
  - `flutter test`
  - Change the API base URL: edit `lib/main.dart` line 16
    (`ApiService(baseUrl: 'http://<host>:8000/api')`) or make it load from
    `const String.fromEnvironment('API_BASE_URL')` before running with
    `--dart-define API_BASE_URL=...`.

## Architecture / important constraints

- **Stock is derived from loan statuses, not a column.** `AvailabilityService`
  computes `availableQty = active units − SUM(quantity) of `Loan::reserving()`
  rows whose date range overlaps `[start, end]`. No manual increment/decrement.
  Race safety for last-stock comes from `Item::lockForUpdate()` inside the
  submit transaction (MySQL only, since `lockForUpdate` is not available on
  SQLite).
- **Status lifecycle is centralized.** `LoanService::transition()` is the only
  way to change loan status: it `lockForUpdate()`s the row, checks
  `LoanStatus::allowedTransitions()`, writes the audit log and dispatches
  `LoanStatusUpdated`. Invalid transitions throw `IllegalTransitionException`.
  Direct `where()->update()` on loans is not the intended path.
- `LoanStatus::reserving()` = `pending, reviewing, approved, ready_pickup,
  borrowed, overdue` (these hold stock). `approved` and `ready_pickup` expire
  via the scheduler; `rejected/cancelled/expired/returned*` are final.
- **Admin review lock.** Opening `admin/verifikasi/{loan}` calls `LoanService::
  startReview()` (`pending → reviewing` + `reviewed_by`). Only the admin in
  `reviewed_by` may approve/reject (`assertCanDecide()` throws
  `LoanLockedException` otherwise); an admin can `takeover()` a `Reviewing`
  loan held by another admin.
- **Loan `resubmitted_from_id`** links a resubmitted loan to its original. The
  submission API accepts `$resubmitSource`; the card is copied (not re-uploaded)
  and the request code is regenerated as `REQ-YYYY-NNNN`.
- **Blocked transitions at the UI level:**
  - `loan->status === 'borrowed'/'overdue'` only for return, extension and
    fine payment.
  - `pending/rejected/cancelled/expired` loans return 404 for `pengajuan.store`
    (active items only).
  - `admin.berkas.*` routes are guarded by `LoanPolicy::viewFile()` (user's own
    loans, or admin), same rule as `view`.
- **File storage.** Private files (student card, attachment, inspection media,
  photos) go to `local` disk with hashed names. Public assets are only the
  `public/` symlinks/linked via `storage:link`; serve from `storage/` through
  the app (e.g. `FotoController::cover` for item covers).
- **Video guidance.** `Item::videoType()` returns `youtube|vimeo|file|link`
  from the URL; `videoEmbedUrl()` returns a privacy-enhanced YouTube embed
  (`youtube-nocookie`), a Vimeo player embed, or the file URL. Embedding
  happens client-side from `<x-image-viewer>`/related views. File uploads are
  capped at 20 MB (see `php.ini` and `DEPLOY.md` §11).
- **Scheduler jobs** (cron `* * * * * cd /path && php artisan schedule:run`,
  synchronous, no queue worker required):
  - `PromoteReadyPickup` (06:00 daily) `approved` → `ready_pickup` when
    `pickup_date` is today.
  - `SendDueReminders` (07:00 daily) H-1 and due-today reminders for `borrowed`.
  - `MarkOverdue` (15:30 daily) `borrowed` with `end_date < today` → `overdue`.
  - `ExpireUnclaimed` (15:30 daily) `approved`/`ready_pickup` past SLA +
    grace → `expired`.
  - `RemindAdminPending` (hourly 07–15, Mon–Fri) `pending` past `review_sla`.
  Jobs run synchronously; `php artisan schedule:list` / `php artisan schedule:test`
  for manual checks.
- **Payment/fines** use `users.late_count` and `loans.fine_amount` /
  `damage_reports.fine_amount`. Return decision is per unit: worst condition
  wins for the loan status (`hilang → returned_lost` > `rusak →
  returned_damaged` > `baik → returned`), and only non-`baik` units flip to
  `maintenance`/`lost`; other units stay `available`.

## Mobile / API constraints

- **API endpoints** are in `routes/api.php` (`AuthController`, `ItemController`,
  `LoanController`). Auth uses Laravel Sanctum (`auth:sanctum` middleware).
  - `POST /api/login` → returns `{token, user}`.
  - `GET /api/user` → current user (token required).
  - `POST /api/logout` → revoke token.
  - `GET /api/items?q=&kategori=` → paginated catalog.
  - `GET /api/items/{id}` → detail.
  - `GET /api/loans` → user's own loans.
  - `POST /api/loans` → submit (fields: `item_id`, `quantity`, `start_date`,
    `end_date`, `purpose`, optional `student_card`/`attachment` file uploads).
  - `GET /api/loans/{id}` → detail (owner or admin via `LoanPolicy::view`).
- **Flutter base URL** is hard-coded in `lib/main.dart` line 16. If the device
  and server are on different networks (e.g. emulator vs localhost), the URL
  must be reachable from the device (use `10.0.2.2:8000` for Android emulator,
  or the LAN IP for physical devices).
- **Token persistence** is handled by `SharedPreferences` (`auth_token` key).
  `ApiService` loads it on startup; `AuthProvider.loadUser()` only calls
  `api.loadToken()` — it does **not** re-fetch the user object, so the app
  relies on the token still being valid. If the token is expired, every
  authenticated request will return 401 and the UI shows generic error messages.
- **No push notifications** in the Flutter app yet. Notifications are only
  visible inside the web UI (Reverb + Laravel Echo).

## Environment / deployment gotchas

- `APP_URL` is normalized to HTTPS automatically in production (`config/app.php`).
- `phpunit.xml` pins SQLite (`:memory:`), sync queue, array cache/sessions, and
  `MAIL_MAILER=array` so tests never hit an SMTP server.
- `Storage::fake('local')` is used in feature tests that upload files.
- `public/` is the document root; `npm run build:prod` writes Vite output to
  `public/build/` plus `public/build/build-info.json` (used by `DEPLOY.md`).
- Production seeder: `php artisan db:seed --class=ProductionSeeder`.
- Demo accounts (dev only, change before production):
  - Admin `maya.sari` / `admin12345` (employee `ADM-004`).
  - Siswa `raka.pratama`, `nadia.putri`, `bagas.aji`, `salsa.nur`,
    `rizky.akbar`, `alya.rahma` / `siswa12345`.
- Demo photos are placeholders (`Zenit 11` files in
  `database/seeders/assets/demo/`). Seeders never overwrite admin-uploaded
  photos; replace them via Inventaris → Ubah → Foto alat.
