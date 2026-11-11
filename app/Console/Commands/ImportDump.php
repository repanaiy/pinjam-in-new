<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mengimpor dump SQL (mysqldump/MariaDB) ke database aktif. Menimpa tabel yang ada (DROP TABLE IF EXISTS di dump).
 * Baris DROP/CREATE DATABASE dan USE dibuang agar dump berjalan di database mana pun.
 */
class ImportDump extends Command
{
    protected $signature = 'db:import-dump {file=database/dumps/pinjam_alat.sql} {--force}';

    protected $description = 'Impor dump SQL ke database aktif (menimpa tabel)';

    public function handle(): int
    {
        $file = base_path($this->argument('file'));
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Ini menimpa tabel di database aktif. Lanjut?')) {
            return self::FAILURE;
        }

        $sql = (string) file_get_contents($file);
        // Dump kadang berisi dua salinan; ambil hanya yang pertama.
        $parts = preg_split('/^-- Current Database:.*$/m', $sql);
        $sql = count($parts) > 2 ? $parts[0].$parts[1] : $sql;
        $sql = preg_replace('/^(\/\*!40000 DROP DATABASE.*|CREATE DATABASE.*|USE `.*`;)$/m', '', $sql);

        DB::unprepared($sql);
        $this->info('Dump diimpor: '.DB::table('users')->count().' pengguna, '.DB::table('items')->count().' alat.');

        return self::SUCCESS;
    }
}
