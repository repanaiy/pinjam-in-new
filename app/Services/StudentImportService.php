<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Imports\StudentsSheet;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import siswa dari CSV/Excel: kolom nama, NIS, kelas, no. HP.
 * Baris valid dibuat; baris tidak valid dilaporkan beserta alasannya.
 * Username dibuat dari nama; password acak dibuat per siswa dan hanya ditampilkan sekali.
 */
class StudentImportService
{
    public const MAX_ROWS = 500;

    private const ALIASES = [
        'nama' => ['nama', 'name', 'nama_siswa', 'nama_lengkap'],
        'nis' => ['nis', 'nomor_induk', 'no_induk'],
        'kelas' => ['kelas', 'class'],
        'no_hp' => ['no_hp', 'hp', 'telepon', 'no_telepon', 'no_telp', 'phone', 'nomor_hp'],
    ];

    public function __construct(private AuditService $audit) {}

    /**
     * @return array{total:int,created:array<int,array{row:int,name:string,username:string,nis:string,kelas:string,password:string}>,errors:array<int,array{row:int,nis:string,name:string,messages:string[]}>}
     */
    public function import(UploadedFile $file, User $admin): array
    {
        $rows = $this->parse($file);

        if ($rows === []) {
            throw new BusinessRuleException('File tidak berisi data siswa.');
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new BusinessRuleException('Maksimal '.self::MAX_ROWS.' baris per import.');
        }

        $existingNis = User::withTrashed()->whereNotNull('nis')->pluck('nis')->flip();
        $seenNis = [];
        $created = [];
        $errors = [];

        DB::transaction(function () use ($rows, $existingNis, &$seenNis, &$created, &$errors) {
            foreach ($rows as $i => $row) {
                $line = $i + 2; // baris 1 = header

                $v = Validator::make($row, [
                    'nama' => ['required', 'string', 'max:120'],
                    'nis' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/'],
                    'kelas' => ['required', 'string', 'max:30'],
                    'no_hp' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s]{8,25}$/'],
                ], [
                    'nama.required' => 'Nama kosong.',
                    'nama.max' => 'Nama terlalu panjang (maks. 120).',
                    'nis.required' => 'NIS kosong.',
                    'nis.max' => 'NIS terlalu panjang (maks. 30).',
                    'nis.regex' => 'NIS hanya boleh huruf, angka, titik, minus, atau garis bawah.',
                    'kelas.required' => 'Kelas kosong.',
                    'kelas.max' => 'Kelas terlalu panjang (maks. 30).',
                    'no_hp.regex' => 'No. HP tidak valid.',
                    'no_hp.max' => 'No. HP terlalu panjang.',
                ]);

                $messages = $v->errors()->all();
                $nis = (string) ($row['nis'] ?? '');

                if ($nis !== '' && ! $v->errors()->has('nis')) {
                    if (isset($seenNis[$nis])) {
                        $messages[] = "NIS dobel dalam file (baris {$seenNis[$nis]}).";
                    } elseif ($existingNis->has($nis)) {
                        $messages[] = 'NIS sudah terdaftar.';
                    }
                }

                if ($messages !== []) {
                    $errors[] = ['row' => $line, 'nis' => $nis, 'name' => (string) ($row['nama'] ?? ''), 'messages' => $messages];

                    continue;
                }

                $seenNis[$nis] = $line;
                $password = Str::random(10);
                $username = $this->uniqueUsername($row['nama']);

                User::create([
                    'name' => $row['nama'],
                    'username' => $username,
                    'password' => $password,
                    'role' => Role::Siswa->value,
                    'nis' => $nis,
                    'kelas' => $row['kelas'],
                    'phone' => $row['no_hp'] ?: null,
                    'is_active' => true,
                ]);

                $created[] = ['row' => $line, 'name' => $row['nama'], 'username' => $username, 'nis' => $nis, 'kelas' => $row['kelas'], 'password' => $password];
            }
        });

        $this->audit->log(null, $admin, 'student_import', count($created).' siswa diimpor, '.count($errors).' baris ditolak', [
            'created' => count($created), 'errors' => count($errors),
        ]);

        return ['total' => count($rows), 'created' => $created, 'errors' => $errors];
    }

    /** @return array<int,array{nama:string,nis:string,kelas:string,no_hp:string}> */
    public function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        $raw = in_array($ext, ['xlsx', 'xls'], true)
            ? (Excel::toArray(new StudentsSheet, $file)[0] ?? [])
            : $this->readCsv($file->getRealPath());

        if ($raw !== [] && ! $this->hasRequiredHeaders((array) $raw[0])) {
            throw new BusinessRuleException('Kolom wajib tidak lengkap. Gunakan header: nama, nis, kelas, no_hp.');
        }

        $rows = [];
        foreach ($raw as $record) {
            $row = $this->normalize((array) $record);

            if (collect($row)->every(fn ($v) => $v === '')) {
                continue; // baris kosong
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return array<int,array<string,string>> */
    private function readCsv(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $first = (string) ($lines[0] ?? '');
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        $records = array_map(fn ($l) => str_getcsv($l, $delimiter), array_filter($lines, fn ($l) => trim($l) !== ''));
        $records = array_values($records);

        if ($records === []) {
            return [];
        }

        $headers = array_map(fn ($h) => $this->slug((string) $h), array_shift($records));

        return array_map(function ($r) use ($headers) {
            $row = [];
            foreach ($headers as $i => $h) {
                $row[$h] = $r[$i] ?? '';
            }

            return $row;
        }, $records);
    }

    private function slug(string $header): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($header))), '_');
    }

    private function hasRequiredHeaders(array $firstRecord): bool
    {
        $keys = array_map(fn ($k) => $this->slug((string) $k), array_keys($firstRecord));

        foreach (['nama', 'nis', 'kelas'] as $field) {
            if (array_intersect(self::ALIASES[$field], $keys) === []) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string,string> */
    private function normalize(array $record): array
    {
        $byKey = [];
        foreach ($record as $k => $v) {
            $byKey[$this->slug((string) $k)] = $v;
        }

        $out = [];
        foreach (self::ALIASES as $field => $aliases) {
            $value = '';
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $byKey)) {
                    $value = $byKey[$alias];
                    break;
                }
            }
            // Excel sering menyimpan angka (NIS) sebagai float/int.
            $out[$field] = is_float($value) ? (string) (int) $value : trim((string) $value);
        }

        return $out;
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::of(Str::ascii($name))->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->limit(45, '')->toString() ?: 'siswa';
        $username = $base;

        for ($i = 2; User::withTrashed()->where('username', $username)->exists(); $i++) {
            $username = "{$base}{$i}";
        }

        return $username;
    }
}
