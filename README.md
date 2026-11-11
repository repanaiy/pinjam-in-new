# PINJAM-IN — versi final

Sistem peminjaman sarpras SMKN 5 Surakarta (Laravel 11). Paket ini adalah **overlay**: kode di sini ditimpakan ke proyek Laravel 11 + Breeze baru oleh `setup.sh`.

## Instalasi
Prasyarat: PHP 8.2+ (ext: pdo_mysql, mbstring, gd, zip, xml — gd/zip dipakai ekspor/impor Excel dan PDF), Composer 2, Node 18+, MySQL 8.
Untuk `php artisan test` dibutuhkan juga `pdo_sqlite` (test memakai SQLite in-memory).

```bash
bash setup.sh pinjam-alat-app
mysql -u root -e "CREATE DATABASE pinjam_alat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
cd pinjam-alat-app
# sesuaikan DB_USERNAME / DB_PASSWORD di .env bila perlu
php artisan migrate --seed
npm run dev          # terminal 1
php artisan serve    # terminal 2  -> http://localhost:8000
php artisan test
```

## Akun demo (hanya untuk pengembangan — ganti sebelum produksi)
| Peran | Username | Password |
|---|---|---|
| Admin (Ibu Maya Sari, ADM-004) | `maya.sari` | `admin12345` |
| Siswa (Raka Pratama, XII IPA 2, NIS 20240182) | `raka.pratama` | `siswa12345` |
| Siswa lain | `nadia.putri`, `bagas.aji`, `salsa.nur`, `rizky.akbar`, `alya.rahma` | `siswa12345` |

## Scheduler
Tambahkan satu entri cron (zona waktu aplikasi: Asia/Jakarta). Job berjalan sinkron lewat `schedule:run`, **tidak perlu queue worker**:

```
* * * * * cd /path/ke/pinjam-alat-app && php artisan schedule:run >> /dev/null 2>&1
```

| Job | Jadwal | Tugas |
|---|---|---|
| `PromoteReadyPickup` | harian 06:00 | `approved` dengan `pickup_date` hari ini -> `ready_pickup` |
| `SendDueReminders` | harian 07:00 | pengingat H-1 (hari kerja sebelumnya) dan hari-H untuk `borrowed` |
| `MarkOverdue` | harian 15:30 | `borrowed` dengan `end_date` < hari ini -> `overdue` |
| `ExpireUnclaimed` | harian 15:30 | `approved`/`ready_pickup` lewat jadwal + toleransi -> `expired` |
| `RemindAdminPending` | tiap jam 07–15, Senin–Jumat | `pending` melewati SLA -> notifikasi ringkas ke admin |

Uji manual: `php artisan schedule:list` dan `php artisan schedule:test`.

Panduan produksi lengkap (Nginx, `php.ini`, email, cadangan, pemecahan masalah): **`DEPLOY.md`**.

## Pemecahan masalah
| Gejala | Penyebab & solusi |
|---|---|
| `setup.sh` berhenti: "ekstensi PHP belum aktif" | Aktifkan `gd`, `zip`, `pdo_mysql`, `mbstring`, `xml`, `fileinfo` di `php.ini` (Laragon: Menu > PHP > Ekstensi) |
| `Interface "Maatwebsite\Excel\Concerns\FromArray" not found` | Paket Excel belum terpasang: `composer require maatwebsite/excel barryvdh/laravel-dompdf`. Test terkait akan otomatis di-*skip* dengan pesan yang sama bila paket tidak ada |
| `Call to undefined method App\Models\...::factory()` | Model belum memakai `HasFactory` (sudah diperbaiki; `FactoriesTest` menjaganya) |
| `php artisan test` gagal membuat database | Aktifkan `pdo_sqlite` (test memakai SQLite in-memory) |

## Revisi akhir
- **Foto sementara**: seluruh foto demo memakai foto kamera Zenit 11 (`database/seeders/assets/demo/`): 3 foto per alat (utuh + potongan lensa/bodi/tripod), placeholder kartu pelajar (privat), dan bukti inspeksi serah terima/pengembalian demo. Ganti dengan foto asli lewat **Inventaris → Ubah → Foto alat**. Seeder tidak menimpa foto yang sudah diunggah admin.
- **Gambar bisa di-zoom** di semua halaman bergambar: penampil layar penuh (`<x-image-viewer>`) dengan zoom roda mouse/cubit/tombol (1×–6×), geser, klik dua kali, galeri + thumbnail, panah/Esc papan ketik, dan pemutar video; plus zoom hover di galeri detail alat. Dipakai di: detail alat, form inventaris, kartu pelajar (verifikasi, serah terima, lacak siswa), perbandingan sebelum/sesudah pengembalian, dan "Foto kondisi alat" di halaman lacak siswa.
- **Video panduan**: tautan YouTube (mode privasi `youtube-nocookie`), Vimeo, atau file MP4/WebM diputar di halaman; pemutar pihak ketiga baru dimuat setelah diklik.
- **Terjemahan Indonesia lengkap** (`lang/id`, `lang/id.json`): pesan validasi, email reset password, dan label atribut. Sebelumnya pesan validasi bawaan Laravel bisa tampil sebagai kunci mentah.
- **Lupa password** siap dicoba: siswa demo punya email, `MAIL_MAILER=log` menulis email ke `storage/logs/laravel.log`, login menampilkan pemberitahuan setelah reset.
- **Paginasi** untuk hari libur, kategori, dan unit per alat; kartu pengguna di sidebar menjadi tautan ke Profil (admin juga).
- **ProductionSeeder** (`php artisan db:seed --class=ProductionSeeder`) dan **DEPLOY.md** (Nginx, `.env` produksi, cron, `php.ini`, backup, daftar periksa keamanan).
- **Perbaikan bug** yang ditemukan lewat verifikasi baru: `@php(...)` inline yang berpasangan dengan `@endphp` blok membuat halaman Notifikasi gagal dikompilasi.

### Menjalankan test
```bash
php artisan test          # test PHP (PHPUnit)
npm run test:js           # test JS: kalender, penampil gambar, dan integrasi Alpine di jsdom
```

## Isi Fase 5 (inventaris, siswa, laporan, pengaturan)
- **Inventaris**: CRUD alat (kode, nama, kategori, ringkasan, spesifikasi dinamis, isi paket, checklist template, lokasi, maks. hari, maks. unit per pinjam, stok minimum, video panduan, status), CRUD unit fisik, foto (sampul), CRUD kategori. Hapus selalu soft delete dan ditolak bila masih ada pengajuan/peminjaman aktif
- **Laporan kerusakan**: `open -> in_repair -> resolved / written_off`; `resolved` mengembalikan unit ke `available`, `written_off` menonaktifkan unit
- **Manajemen siswa**: CRUD, aktif/nonaktif, reset password (password sementara tampil sekali), hapus (soft delete, riwayat tetap utuh)
- **Import siswa** CSV/XLSX/XLS (nama, NIS, kelas, no. HP): baris valid dibuat, baris tidak valid dilaporkan; username dibuat dari nama, password acak per siswa tampil sekali dengan tombol unduh kredensial; template CSV tersedia
- **Laporan**: filter tanggal pinjam, kategori, status, rentang cepat (hari ini / 7 hari / 30 hari / semester ini), 4 kartu ringkasan, tabel berpaginasi, **ekspor PDF** dan **Excel** yang mengikuti filter aktif (satu sumber: `ReportService`)
- **Pengaturan**: hari & jam layanan, maks. hari pinjam, SLA peninjauan, toleransi pengambilan, ambang keterlambatan, tahun ajaran/semester, hari libur — semuanya langsung dipakai validasi, scheduler, dan layout
- **Bukti peminjaman PDF** untuk siswa (dan admin) setelah pengajuan disetujui

## Revisi final
- **Foto sementara** (kamera Zenit 11) dipakai di seluruh data demo: 3 foto per alat (utuh, lensa, bodi/tripod; sampul bergantian), placeholder kartu pelajar, dan bukti inspeksi serah terima/pengembalian. Seeder idempoten dan tidak menimpa foto yang diunggah admin. Cara menggantinya: lihat `DEPLOY.md` bagian 10
- **Penampil gambar** (`<x-image-viewer>`, terpasang di layout): zoom 1×–6× (roda mouse, tombol, cubit di layar sentuh, klik dua kali/ketuk dua kali), geser, galeri dengan thumbnail dan panah, keyboard (←/→, +/−, 0, Esc), tautan ukuran asli, serta pemutar video. Dibuka lewat event: `$dispatch('open-viewer', { items: [{ src, alt, caption, type: 'image'|'video' }], index: 0 })`
- Dipasang di: detail alat (galeri + zoom hover), form inventaris, kartu pelajar (verifikasi, serah terima, halaman siswa), perbandingan foto sebelum/sesudah saat pengembalian, dan "Foto kondisi alat" di halaman lacak siswa. Kartu pelajar berformat PDF tetap dibuka sebagai PDF
- **Video panduan**: tautan YouTube (mode privasi), Vimeo, atau berkas MP4/WebM tampil sebagai pemutar yang baru dimuat setelah diklik; tautan lain tampil sebagai tombol "Tonton"
- **Terjemahan Indonesia lengkap** (`lang/id`, `lang/id.json`): pesan validasi, reset password, dan teks email
- **Lupa password** siap dipakai: siswa demo punya email; email dikirim ke log (`MAIL_MAILER=log`) sampai SMTP diatur
- Paginasi untuk hari libur, kategori, dan unit per alat; tautan profil di sidebar untuk admin dan siswa
- Perbaikan bug: `@php(...)` inline pada `notifikasi.blade.php` yang merusak kompilasi view, galat desimal pada skala zoom, penutupan penampil oleh klik pada gambar
- **Verifikasi tambahan** yang bisa dijalankan tanpa server: `npm run test:js` (kalender, penampil, dan penampil di DOM dengan Alpine asli), `tests/Feature/ViewsCompileTest.php` (semua view terkompilasi menjadi PHP valid)

## Pemetaan 10 feature test wajib
| # | Skenario | File |
|---|---|---|
| 1 | Ketersediaan per rentang tanggal beririsan | `tests/Feature/Siswa/AvailabilityServiceTest.php` |
| 2 | Dua pengajuan untuk stok terakhir (satu berhasil)* | `tests/Feature/Siswa/LoanSubmissionTest.php` |
| 3 | Pembatalan melepas reservasi | `tests/Feature/Siswa/LoanLifecycleTest.php` |
| 4 | Penolakan melepas reservasi | `LoanLifecycleTest`, `tests/Feature/Admin/VerifikasiTest.php` |
| 5 | Pengembalian Baik / Rusak / Hilang (hanya unit terkait) | `tests/Feature/Admin/ReturnTest.php` |
| 6 | Keterlambatan (`is_late`, `late_days`, `late_count`) | `ReturnTest`, `SchedulerTest` |
| 7 | Expire otomatis pengajuan tidak diambil | `tests/Feature/Admin/SchedulerTest.php` |
| 8 | Ajukan ulang setelah ditolak | `LoanLifecycleTest` |
| 9 | Akses file privat antar-role | `tests/Feature/Siswa/FileAccessTest.php`, `HandoverTest`, `ReturnTest` |
| 10 | Transisi status ilegal ditolak | `LoanLifecycleTest` |
| + | Semua view Blade terkompilasi valid | `tests/Feature/ViewsCompileTest.php` |
| + | Lupa/reset password | `tests/Feature/Auth/PasswordResetTest.php` |
| + | Foto demo, penampil gambar, video | `DemoSeederTest`, `MediaViewerTest`, `ItemVideoTest` |

\* Dijalankan berurutan dalam satu proses; kunci `lockForUpdate()` yang menjamin keamanan paralel hanya efektif di MySQL.

Ekspor PDF memakai render dompdf sungguhan di test; ekspor Excel diuji dengan `Excel::fake()` (memeriksa isi dan filter ekspor).

## Isi Fase 4 (pengembalian, keterlambatan, scheduler)
- Layar Pengembalian: cari peminjaman aktif (ID, siswa, alat, kode unit), filter "jatuh tempo hari ini"/"terlambat", badge ketepatan waktu
- Inspeksi pengembalian: perbandingan foto serah terima vs bukti baru (pratinjau), checklist dinamis, kondisi **per unit** (Baik/Rusak/Hilang), bukti JPG/PNG/MP4 <= 20 MB, panel "Pembaruan stok otomatis", ringkasan konfirmasi
- `ReturnService`: status loan mengikuti kondisi terburuk (hilang > rusak > baik); hanya unit rusak -> `maintenance` / hilang -> `lost` yang berubah dan dibuatkan `damage_reports`; unit lain tetap `available`
- Keterlambatan: `is_late`, `late_days` (hari kalender; kembali di hari jatuh tempo = tepat waktu), `users.late_count` + 1
- Siswa menerima notifikasi bukti pengembalian dan melihat kartu "Bukti pengembalian" di halaman lacak
- 5 job scheduler lengkap + notifikasi pengingat jatuh tempo, terlambat, kedaluwarsa, dan pengingat SLA admin

## Isi Fase 3 (alur admin awal)
- Dashboard Sarpras: 4 kartu + sparkline, grafik Chart.js 7 hari (dipinjam vs dikembalikan), panel "Perlu perhatian" (jatuh tempo hari ini, terlambat, stok minimum, laporan kerusakan terbuka), utilisasi 30 hari, antrean verifikasi
- Verifikasi dua kolom: cari ID/siswa/alat, filter tanggal & status, urut terlama/terbaru; detail dengan preview kartu pelajar, cek otomatis, "Periksa ketersediaan & kelayakan"
- Membuka detail pengajuan `pending` -> `reviewing` (lock); admin lain melihat peringatan dan dapat "Ambil alih tinjauan"
- Setujui (tanggal/jam/lokasi/catatan, stok dicek ulang) dan Tolak (alasan wajib + chip alasan cepat); tombol "Tandai siap diambil"
- Serah terima: verifikasi kartu asli, pilih unit fisik, checklist dinamis dari template alat, foto/video kondisi awal (private), `loan_units` + inspeksi `handover` -> `borrowed`
- `HandoverService`, `DashboardService`; notifikasi disetujui/ditolak/siap diambil/serah terima; admin menerima notifikasi pengajuan baru
- Audit Log read-only (filter pengajuan, aktor, aksi, tanggal)
- Tombol "Tambah Item" di dashboard muncul otomatis setelah route inventaris ada (Fase 5)

> Unggah video bukti inspeksi hingga 20 MB butuh `upload_max_filesize=20M` dan `post_max_size=64M` di `php.ini`.

## Isi Fase 2 (alur siswa)
- `AvailabilityService` (stok dihitung dari status, identik query acuan SQL), `LoanService` (ajukan dengan `lockForUpdate`, `transition()` sebagai satu-satunya jalur ubah status, batalkan), `AuditService`, `NotificationService`, `ServiceCalendar`
- Katalog: cari, chip kategori + jumlah, urut, filter "hanya yang tersedia", paginasi
- Detail alat + form pengajuan (stepper jumlah, kalender rentang dengan tanggal penuh/libur/akhir pekan nonaktif, dropzone kartu pelajar)
- Pengajuan Saya, halaman lacak (stepper 5 langkah, jadwal pengambilan, aktivitas), batalkan, ajukan ulang
- Riwayat (filter status), Notifikasi (tandai dibaca / semua), Profil (data, avatar, ganti password)
- Berkas privat (kartu pelajar, lampiran) dilayani lewat `LoanPolicy`
- `DemoLoanSeeder`: 10 contoh pengajuan di berbagai status
- Tombol "Unduh bukti peminjaman" muncul otomatis setelah route `pengajuan.bukti` dibuat (Fase 5)

## Isi Fase 1
- Migration identik dengan `database_pinjam_alat.sql`
- Enum, model + relasi, seeder (pengaturan, libur, pengguna, kategori, alat + unit)
- Login 2 kolom (pemilih peran hanya UX; otorisasi tetap `users.role`), rate limit 5/menit, lupa/reset password
- Middleware `role`, layout portal (sidebar, topbar, bottom nav siswa), Blade components
- Katalog sederhana (siswa) dan dashboard ringkas (admin) sebagai landing page
