<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FineController;
use App\Http\Controllers\Admin\FotoController;
use App\Http\Controllers\Admin\HandoverController;
use App\Http\Controllers\Admin\InventarisController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\KerusakanController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PengembalianController;
use App\Http\Controllers\Admin\QRCodeController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\VerifikasiController;
use App\Http\Controllers\BerkasController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\Siswa\LoanController;
use App\Http\Controllers\Siswa\RiwayatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->role->homeRoute())
        : redirect()->route('login');
});

// ---- Siswa ----
Route::middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog.index');
    Route::get('/katalog/{item}', [KatalogController::class, 'show'])->name('katalog.show');
    Route::post('/katalog/{item}/ajukan', [LoanController::class, 'store'])->name('pengajuan.store');

    Route::get('/pengajuan', [LoanController::class, 'index'])->name('pengajuan.index');
    Route::get('/pengajuan/{loan}', [LoanController::class, 'show'])->name('pengajuan.show');
    Route::post('/pengajuan/{loan}/batal', [LoanController::class, 'cancel'])->name('pengajuan.cancel');
    Route::post('/pengajuan/{loan}/perpanjang', [LoanController::class, 'extend'])->name('pengajuan.extend');

    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');
});

// ---- Dipakai siswa & admin (otorisasi per data lewat Policy) ----
Route::middleware(['auth', 'role:siswa,admin'])->group(function () {
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'readAll'])->name('notifikasi.read-all');
    Route::post('/notifikasi/{id}/baca', [NotifikasiController::class, 'read'])->name('notifikasi.read');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::patch('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'password'])->name('profil.password');

    Route::get('/berkas/{loan}/kartu', [BerkasController::class, 'kartu'])->name('berkas.kartu');
    Route::get('/berkas/{loan}/lampiran', [BerkasController::class, 'lampiran'])->name('berkas.lampiran');
    Route::get('/berkas/media/{media}', [BerkasController::class, 'media'])->name('berkas.media');

    Route::get('/pengajuan/{loan}/bukti', [LoanController::class, 'bukti'])->name('pengajuan.bukti');
});

// ---- Admin ----
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
    Route::get('/verifikasi/{loan}', [VerifikasiController::class, 'show'])->name('verifikasi.show');
    Route::post('/verifikasi/{loan}/periksa', [VerifikasiController::class, 'check'])->name('verifikasi.check');
    Route::post('/verifikasi/{loan}/setujui', [VerifikasiController::class, 'approve'])->name('verifikasi.approve');
    Route::post('/verifikasi/{loan}/tolak', [VerifikasiController::class, 'reject'])->name('verifikasi.reject');
    Route::post('/verifikasi/{loan}/ambil-alih', [VerifikasiController::class, 'takeover'])->name('verifikasi.takeover');
    Route::post('/verifikasi/{loan}/siap-diambil', [VerifikasiController::class, 'ready'])->name('verifikasi.ready');
    Route::get('/verifikasi/{loan}/serah-terima', [HandoverController::class, 'create'])->name('verifikasi.handover.create');
    Route::post('/verifikasi/{loan}/serah-terima', [HandoverController::class, 'store'])->name('verifikasi.handover.store');

    Route::get('/pengembalian', [PengembalianController::class, 'index'])->name('pengembalian.index');
    Route::get('/pengembalian/{loan}', [PengembalianController::class, 'show'])->name('pengembalian.show');
    Route::post('/pengembalian/{loan}', [PengembalianController::class, 'store'])->name('pengembalian.store');

    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

    // Inventaris: alat, unit, foto, kategori, laporan kerusakan
    Route::prefix('inventaris')->name('inventaris.')->group(function () {
        Route::get('/', [InventarisController::class, 'index'])->name('index');
        Route::get('/tambah', [InventarisController::class, 'create'])->name('create');
        Route::post('/', [InventarisController::class, 'store'])->name('store');

        Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
        Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
        Route::patch('/kategori/{category}', [KategoriController::class, 'update'])->name('kategori.update');
        Route::delete('/kategori/{category}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

        Route::get('/kerusakan', [KerusakanController::class, 'index'])->name('kerusakan.index');
        Route::patch('/kerusakan/{report}', [KerusakanController::class, 'update'])->name('kerusakan.update');
        Route::post('/denda/{loan}/bayar', [FineController::class, 'store'])->name('admin.denda.bayar');

        Route::patch('/unit/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::delete('/unit/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
        Route::post('/foto/{photo}/sampul', [FotoController::class, 'cover'])->name('photos.cover');
        Route::delete('/foto/{photo}', [FotoController::class, 'destroy'])->name('photos.destroy');

        Route::get('/{item}/ubah', [InventarisController::class, 'edit'])->name('edit');
        Route::put('/{item}', [InventarisController::class, 'update'])->name('update');
        Route::delete('/{item}', [InventarisController::class, 'destroy'])->name('destroy');
        Route::post('/{item}/unit', [UnitController::class, 'store'])->name('units.store');
        Route::post('/{item}/foto', [FotoController::class, 'store'])->name('photos.store');
    });

    // Manajemen siswa
    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/tambah', [SiswaController::class, 'create'])->name('siswa.create');
    Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::get('/siswa/import', [SiswaController::class, 'importForm'])->name('siswa.import');
    Route::post('/siswa/import', [SiswaController::class, 'import'])->name('siswa.import.store');
    Route::get('/siswa/template', [SiswaController::class, 'template'])->name('siswa.template');
    Route::get('/siswa/{id}/ubah', [SiswaController::class, 'edit'])->whereNumber('id')->name('siswa.edit');
    Route::put('/siswa/{id}', [SiswaController::class, 'update'])->whereNumber('id')->name('siswa.update');
    Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])->whereNumber('id')->name('siswa.destroy');
    Route::post('/siswa/{id}/status', [SiswaController::class, 'toggle'])->whereNumber('id')->name('siswa.toggle');
    Route::post('/siswa/{id}/reset-password', [SiswaController::class, 'resetPassword'])->whereNumber('id')->name('siswa.reset');

    // QR Code
    Route::get('/qrcode/item/{item}', [QRCodeController::class, 'item'])->name('qrcode.item');
    Route::get('/qrcode/loan/{loan}', [QRCodeController::class, 'loan'])->name('qrcode.loan');

    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
    Route::get('/laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');

    // Pengaturan & hari libur
    Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.index');
    Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    Route::post('/pengaturan/libur', [PengaturanController::class, 'storeHoliday'])->name('pengaturan.libur.store');
    Route::delete('/pengaturan/libur/{holiday}', [PengaturanController::class, 'destroyHoliday'])->name('pengaturan.libur.destroy');
});

require __DIR__.'/auth.php';
