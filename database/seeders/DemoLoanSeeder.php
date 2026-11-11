<?php

namespace Database\Seeders;

use App\Enums\LoanStatus;
use App\Models\ActivityLog;
use App\Models\InspectionMedia;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\LoanInspection;
use App\Models\LoanUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/** Contoh pengajuan di berbagai status agar halaman siswa/admin tidak kosong saat demo. */
class DemoLoanSeeder extends Seeder
{
    private const CARD = 'student-cards/demo-kartu.jpg';

    public function run(): void
    {
        if (Loan::query()->exists()) {
            return; // jangan menggandakan data demo
        }

        // Foto sementara (kamera Zenit) sebagai placeholder kartu pelajar — berkas privat.
        $assets = database_path(ItemPhotoSeeder::ASSETS);
        Storage::disk('local')->put(self::CARD, File::get("{$assets}/zenit-kecil.jpg"));

        $admin = User::where('username', 'maya.sari')->firstOrFail();
        $u = fn (string $name) => User::where('username', $name)->firstOrFail();
        $i = fn (string $code) => Item::where('code', $code)->firstOrFail();

        $day = fn (int $offset) => $this->workday(today()->addDays($offset));

        // [siswa, kode alat, qty, mulai, selesai, status, lampiran ekstra]
        $rows = [
            ['nadia.putri', 'AVS-004', 1, $day(2), $day(4), LoanStatus::Pending, []],
            ['salsa.nur', 'LAB-007', 1, $day(1), $day(3), LoanStatus::Reviewing, ['reviewed_by' => $admin->id]],
            ['bagas.aji', 'ELK-012', 1, $day(3), $day(6), LoanStatus::Approved, [
                'reviewed_by' => $admin->id, 'approved_at' => now()->subHours(3), 'pickup_date' => $day(2),
                'pickup_time_start' => '09:00', 'pickup_time_end' => '11:00', 'pickup_location' => 'Ruang Sarpras, Gedung B',
            ]],
            ['salsa.nur', 'AVS-009', 2, $day(1), $day(3), LoanStatus::ReadyPickup, [
                'reviewed_by' => $admin->id, 'approved_at' => now()->subDay(), 'pickup_date' => $day(1),
                'pickup_time_start' => '08:00', 'pickup_time_end' => '10:00', 'pickup_location' => 'Ruang Sarpras, Gedung B',
            ]],
            ['raka.pratama', 'ELK-012', 2, $day(-2), $day(2), LoanStatus::Borrowed, [
                'reviewed_by' => $admin->id, 'approved_at' => now()->subDays(4), 'borrowed_at' => now()->subDays(2),
            ]],
            ['rizky.akbar', 'LAB-007', 1, $day(-8), $day(-3), LoanStatus::Overdue, [
                'reviewed_by' => $admin->id, 'approved_at' => now()->subDays(10), 'borrowed_at' => now()->subDays(8),
                'is_late' => true, 'late_days' => 3,
            ]],
            ['alya.rahma', 'AVS-015', 1, $day(-10), $day(-7), LoanStatus::Returned, [
                'reviewed_by' => $admin->id, 'approved_at' => now()->subDays(12), 'borrowed_at' => now()->subDays(10), 'returned_at' => now()->subDays(7),
            ]],
            ['raka.pratama', 'AVS-004', 1, $day(5), $day(7), LoanStatus::Rejected, [
                'reviewed_by' => $admin->id, 'reviewed_at' => now()->subDay(),
                'reject_reason' => 'Proyektor dipakai untuk kegiatan sekolah pada tanggal tersebut. Silakan pilih tanggal lain.',
            ]],
            ['nadia.putri', 'PRK-003', 1, $day(4), $day(6), LoanStatus::Cancelled, [
                'cancelled_at' => now()->subHours(20), 'cancel_reason' => 'Jadwal praktikum berubah.',
            ]],
            ['bagas.aji', 'OLR-001', 2, $day(-6), $day(-5), LoanStatus::Expired, [
                'reviewed_by' => $admin->id, 'approved_at' => now()->subDays(7), 'expired_at' => now()->subDays(5),
            ]],
        ];

        foreach ($rows as [$username, $code, $qty, $start, $end, $status, $extra]) {
            $item = $i($code);
            $student = $u($username);

            $loan = new Loan(array_merge([
                'request_code' => 'TMP-'.uniqid(),
                'user_id' => $student->id,
                'item_id' => $item->id,
                'quantity' => $qty,
                'start_date' => $start,
                'end_date' => $end,
                'purpose' => 'Keperluan kegiatan sekolah: '.$item->name.' untuk dokumentasi dan praktik kelas.',
                'student_card_path' => self::CARD,
                'agreement_accepted' => true,
                'status' => $status,
            ], $extra));
            $loan->created_at = now()->subDays(max(1, abs(today()->diffInDays($start, false)) + 1));
            $loan->save();
            $loan->update(['request_code' => sprintf('REQ-%d-%04d', now()->year, 1000 + $loan->id)]);

            $this->log($loan, $student, 'submitted', 'Pengajuan dikirim', $loan->created_at);
            if ($admin && ! in_array($status, [LoanStatus::Pending, LoanStatus::Cancelled], true)) {
                $this->log($loan, $admin, $status->value, 'Status diubah menjadi '.$status->label(), now()->subHours(2));
            }
            if ($status === LoanStatus::Cancelled) {
                $this->log($loan, $student, 'cancelled', 'Pengajuan dibatalkan oleh siswa', $loan->cancelled_at);
            }

            // Unit fisik terikat pada peminjaman yang sudah diserahkan.
            if (in_array($status, [LoanStatus::Borrowed, LoanStatus::Overdue, LoanStatus::Returned], true)) {
                ItemUnit::where('item_id', $item->id)->orderBy('id')->limit($qty)->get()->each(fn ($unit) => LoanUnit::create([
                    'loan_id' => $loan->id,
                    'item_unit_id' => $unit->id,
                    'condition_out' => 'baik',
                    'condition_in' => $status === LoanStatus::Returned ? 'baik' : null,
                    'returned_at' => $status === LoanStatus::Returned ? $loan->returned_at : null,
                ]));

                // Inspeksi serah terima (dan pengembalian untuk yang sudah selesai) agar layar pengembalian punya data pembanding.
                $checklist = fn () => array_map(fn ($l) => ['label' => $l, 'checked' => true], $item->checklistItems());

                $handover = LoanInspection::create([
                    'loan_id' => $loan->id, 'type' => 'handover', 'checklist' => $checklist(), 'final_condition' => 'baik',
                    'notes' => 'Unit lengkap dan berfungsi baik.', 'inspected_by' => $admin->id, 'inspected_at' => $loan->borrowed_at,
                ]);
                $this->media($handover, "inspections/{$loan->id}/handover", ['zenit-bodi.jpg', 'zenit-lensa.jpg'], 'Kondisi awal', $assets);

                if ($status === LoanStatus::Returned) {
                    $return = LoanInspection::create([
                        'loan_id' => $loan->id, 'type' => 'return', 'checklist' => $checklist(), 'final_condition' => 'baik',
                        'notes' => 'Dikembalikan lengkap dan baik.', 'inspected_by' => $admin->id, 'inspected_at' => $loan->returned_at,
                    ]);
                    $this->media($return, "inspections/{$loan->id}/return", ['zenit-utuh.jpg'], 'Kondisi saat dikembalikan', $assets);
                }
            }
        }

        // Rizky terlambat sekali (sinkron dengan loan overdue di atas).
        User::where('username', 'rizky.akbar')->update(['late_count' => 1]);
    }

    /** @param string[] $files nama berkas di folder aset demo */
    private function media(LoanInspection $inspection, string $dir, array $files, string $caption, string $assets): void
    {
        foreach ($files as $i => $file) {
            $path = "{$dir}/demo-".($i + 1).'.jpg';
            Storage::disk('local')->put($path, File::get("{$assets}/{$file}"));

            InspectionMedia::create(['inspection_id' => $inspection->id, 'media_type' => 'photo', 'path' => $path, 'caption' => $caption]);
        }
    }

    private function workday(Carbon $d): string
    {
        $d = $d->copy();
        while ($d->isWeekend()) {
            $d->addDay();
        }

        return $d->toDateString();
    }

    private function log(Loan $loan, ?User $actor, string $action, string $description, $at): void
    {
        ActivityLog::create([
            'loan_id' => $loan->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'description' => $description,
            'created_at' => $at,
        ]);
    }
}
