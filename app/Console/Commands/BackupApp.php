<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PharData;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Backup database + berkas privat ke storage/app/backups.
 * Tanpa dependency eksternal; memakai mysqldump (MySQL) atau salin file (SQLite).
 * Arsip dibuat dengan PharData (.tar.gz) agar tidak butuh ekstensi zip.
 */
class BackupApp extends Command
{
    protected $signature = 'app:backup
                            {--keep= : Jumlah hari retensi (default dari BACKUP_KEEP_DAYS)}
                            {--no-files : Hanya backup database, lewati berkas}';

    protected $description = 'Backup database dan berkas privat aplikasi';

    public function handle(): int
    {
        $stamp = now()->format('Y-m-d_His');
        $disk = Storage::disk('local');
        $dir = 'backups';
        $disk->makeDirectory($dir);

        $dbFile = $this->backupDatabase($stamp);
        if ($dbFile === null) {
            $this->error('Backup database gagal.');

            return self::FAILURE;
        }

        $this->info("Database: {$dbFile}");

        if (! $this->option('no-files')) {
            $filesArchive = $this->backupFiles($stamp);
            if ($filesArchive !== null) {
                $this->info("Berkas: {$filesArchive}");
            }
        }

        $this->prune((int) ($this->option('keep') ?: env('BACKUP_KEEP_DAYS', 7)));

        $this->info('Backup selesai.');

        return self::SUCCESS;
    }

    private function backupDatabase(string $stamp): ?string
    {
        $driver = DB::connection()->getDriverName();
        $path = storage_path("app/private/backups/db-{$stamp}.sql");

        File::ensureDirectoryExists(dirname($path));

        if ($driver === 'sqlite') {
            $database = DB::connection()->getDatabaseName();
            if (! File::exists($database)) {
                return null;
            }
            File::copy($database, $path);

            return $path;
        }

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            $this->warn("Driver {$driver} tidak didukung untuk dump otomatis.");

            return null;
        }

        $config = DB::connection()->getConfig();
        $command = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--no-tablespaces',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            $config['database'],
        ];

        $process = new Process($command);
        $process->setTimeout(600);

        if (! empty($config['password'])) {
            $process->setEnv(['MYSQL_PWD' => $config['password']]);
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            return null;
        }

        try {
            $process->run(function ($type, $buffer) use ($handle) {
                fwrite($handle, $buffer);
            });
        } catch (Throwable $e) {
            fclose($handle);
            File::delete($path);
            $this->error($e->getMessage());

            return null;
        }

        fclose($handle);

        if (! $process->isSuccessful()) {
            File::delete($path);
            $this->error(trim($process->getErrorOutput()));

            return null;
        }

        return $path;
    }

    private function backupFiles(string $stamp): ?string
    {
        $source = storage_path('app/private');
        if (! File::isDirectory($source)) {
            return null;
        }

        $archive = storage_path("app/private/backups/files-{$stamp}.tar.gz");

        try {
            // PharData tidak mendukung .tar.gz langsung; buat .tar lalu gzip.
            $tar = storage_path("app/private/backups/files-{$stamp}.tar");
            @unlink($tar);
            @unlink($archive);

            $phar = new PharData($tar);
            $phar->buildFromDirectory($source, '/^(?!.*backups).*$/');
            $phar->compress(\Phar::GZ);
            unset($phar);
            @unlink($tar);

            return file_exists($archive) ? $archive : null;
        } catch (Throwable $e) {
            $this->warn('Arsip berkas gagal: '.$e->getMessage());

            return null;
        }
    }

    private function prune(int $days): void
    {
        $days = max(1, $days);
        $dir = storage_path('app/private/backups');
        if (! File::isDirectory($dir)) {
            return;
        }

        $cutoff = now()->subDays($days)->getTimestamp();

        foreach (File::files($dir) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $this->line('Hapus backup lama: '.$file->getFilename());
            }
        }
    }
}
