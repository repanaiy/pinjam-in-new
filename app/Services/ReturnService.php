<?php

namespace App\Services;

use App\Enums\DamageStatus;
use App\Enums\InspectionType;
use App\Enums\ItemCondition;
use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Models\DamageReport;
use App\Models\InspectionMedia;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\LoanInspection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReturnService
{
    public function __construct(
        private LoanService $loans,
        private NotificationService $notifications,
    ) {}

    /**
     * Selesaikan pengembalian & inspeksi (borrowed/overdue -> returned | returned_damaged | returned_lost).
     *
     * Kondisi dinilai PER UNIT: hanya unit yang rusak/hilang yang berubah status
     * (maintenance/lost) dan dibuatkan damage_reports; unit lain tetap available.
     * Status loan mengikuti kondisi terburuk (hilang > rusak > baik).
     *
     * @param  array<int|string,string>  $conditions  [item_unit_id => baik|rusak|hilang]
     * @param  int[]  $checkedIndexes  indeks butir checklist yang sesuai
     * @param  UploadedFile[]  $media
     */
    public function complete(Loan $loan, User $admin, array $conditions, array $checkedIndexes, ?string $notes, array $media): Loan
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($loan, $admin, $conditions, $checkedIndexes, $notes, $media, &$stored) {
                Item::query()->whereKey($loan->item_id)->lockForUpdate()->firstOrFail();
                $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->with(['item', 'user', 'loanUnits'])->firstOrFail();

                if (! in_array($fresh->status, [LoanStatus::Borrowed, LoanStatus::Overdue], true)) {
                    throw new IllegalTransitionException($fresh->status, LoanStatus::Returned);
                }

                // ---- kondisi per unit
                $expected = $fresh->loanUnits->pluck('item_unit_id')->map(fn ($i) => (int) $i)->sort()->values()->all();
                $given = collect(array_keys($conditions))->map(fn ($i) => (int) $i)->sort()->values()->all();
                if ($expected === [] || $expected !== $given) {
                    throw ValidationException::withMessages(['conditions' => 'Isi kondisi untuk setiap unit yang dipinjam.']);
                }

                $parsed = collect($conditions)->mapWithKeys(function ($value, $unitId) {
                    $c = ItemCondition::tryFrom((string) $value);
                    if (! $c) {
                        throw ValidationException::withMessages(['conditions' => 'Kondisi unit tidak valid.']);
                    }

                    return [(int) $unitId => $c];
                });

                $worst = $parsed->contains(ItemCondition::Hilang) ? ItemCondition::Hilang
                    : ($parsed->contains(ItemCondition::Rusak) ? ItemCondition::Rusak : ItemCondition::Baik);

                // ---- checklist
                $labels = $fresh->item->checklistItems();
                $checked = collect($checkedIndexes)->map(fn ($i) => (int) $i)->unique();
                $checklist = collect($labels)->map(fn ($label, $i) => ['label' => $label, 'checked' => $checked->contains($i)])->all();
                $allChecked = collect($checklist)->every(fn ($c) => $c['checked']);

                if (($worst !== ItemCondition::Baik || ! $allChecked) && blank($notes)) {
                    throw ValidationException::withMessages(['notes' => 'Catatan inspeksi wajib diisi bila ada unit rusak/hilang atau butir checklist yang belum sesuai.']);
                }
                if ($parsed->contains(ItemCondition::Rusak) && $media === []) {
                    throw ValidationException::withMessages(['media' => 'Unggah bukti foto/video untuk unit yang rusak.']);
                }

                // ---- keterlambatan (perbandingan tanggal)
                $now = now();
                $lateDays = $fresh->lateDaysAt($now);

                // ---- unit fisik, loan_units, damage_reports
                $units = ItemUnit::query()->withTrashed()->whereIn('id', $expected)->lockForUpdate()->get()->keyBy('id');

                foreach ($fresh->loanUnits as $lu) {
                    $condition = $parsed[$lu->item_unit_id];
                    $lu->update(['condition_in' => $condition->value, 'returned_at' => $now]);

                    if ($condition !== ItemCondition::Baik) {
                        $units[$lu->item_unit_id]->update(['status' => $condition->unitStatus()->value]);

                        DamageReport::create([
                            'loan_id' => $fresh->id,
                            'item_unit_id' => $lu->item_unit_id,
                            'type' => $condition->value,
                            'description' => $notes,
                            'status' => DamageStatus::Open->value,
                            'reported_by' => $admin->id,
                        ]);
                    }
                }

                // ---- inspeksi 'return' + bukti
                $inspection = LoanInspection::create([
                    'loan_id' => $fresh->id,
                    'type' => InspectionType::Return->value,
                    'checklist' => $checklist,
                    'final_condition' => $worst->value,
                    'notes' => $notes,
                    'inspected_by' => $admin->id,
                    'inspected_at' => $now,
                ]);

                foreach ($media as $file) {
                    $path = $file->store("inspections/{$fresh->id}/return", 'local');
                    $stored[] = $path;
                    InspectionMedia::create([
                        'inspection_id' => $inspection->id,
                        'media_type' => str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'photo',
                        'path' => $path,
                        'caption' => 'Kondisi saat dikembalikan',
                    ]);
                }

                $fineAmount = 0;
                if ($lateDays > 0) {
                    $fresh->user->increment('late_count');
                    $fineAmount = $lateDays * 10000;
                }

                $target = $worst->loanStatus();
                $updated = $this->loans->transition($fresh, $target, $admin, [
                    'returned_at' => $now,
                    'is_late' => $lateDays > 0,
                    'late_days' => $lateDays,
                    'fine_amount' => $fineAmount,
                ], $target->value, match ($worst) {
                    ItemCondition::Baik => 'Pengembalian selesai, kondisi baik',
                    ItemCondition::Rusak => 'Pengembalian selesai, ada unit rusak',
                    ItemCondition::Hilang => 'Pengembalian selesai, ada unit hilang',
                }, [
                    'late_days' => $lateDays,
                    'fine_amount' => $fineAmount,
                    'conditions' => $parsed->map(fn ($c, $id) => $units[$id]->unit_code.':'.$c->value)->values()->all(),
                ]);

                $this->notifications->returned($updated->load('user', 'item'), $worst);

                return $updated;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($stored);
            throw $e;
        }
    }
}
