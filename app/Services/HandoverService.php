<?php

namespace App\Services;

use App\Enums\InspectionType;
use App\Enums\ItemCondition;
use App\Enums\LoanStatus;
use App\Exceptions\IllegalTransitionException;
use App\Models\InspectionMedia;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\LoanInspection;
use App\Models\LoanUnit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class HandoverService
{
    public function __construct(
        private LoanService $loans,
        private AuditService $audit,
        private NotificationService $notifications,
    ) {}

    /**
     * Serah terima: ready_pickup -> borrowed. Memilih unit fisik, menyimpan inspeksi 'handover'
     * (checklist + foto/video kondisi awal) dan memulai pemantauan jatuh tempo.
     *
     * @param  int[]  $unitIds  harus berjumlah sama dengan loans.quantity
     * @param  int[]  $checkedIndexes  indeks butir checklist yang diperiksa
     * @param  UploadedFile[]  $media
     */
    public function handover(Loan $loan, User $admin, array $unitIds, array $checkedIndexes, ?string $notes, array $media): Loan
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($loan, $admin, $unitIds, $checkedIndexes, $notes, $media, &$stored) {
                Item::query()->whereKey($loan->item_id)->lockForUpdate()->firstOrFail();
                $fresh = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->with(['item', 'user'])->firstOrFail();

                if ($fresh->status !== LoanStatus::ReadyPickup) {
                    throw new IllegalTransitionException($fresh->status, LoanStatus::Borrowed);
                }

                $unitIds = array_values(array_unique(array_map('intval', $unitIds)));
                if (count($unitIds) !== $fresh->quantity) {
                    throw ValidationException::withMessages(['unit_ids' => "Pilih tepat {$fresh->quantity} unit."]);
                }

                $units = ItemUnit::query()->whereIn('id', $unitIds)->where('item_id', $fresh->item_id)->lockForUpdate()->get();
                $lendable = ItemUnit::query()->lendable()->whereIn('id', $unitIds)->count();
                if ($units->count() !== count($unitIds) || $lendable !== count($unitIds)) {
                    throw ValidationException::withMessages(['unit_ids' => 'Ada unit yang tidak tersedia atau sedang dipinjam. Muat ulang halaman.']);
                }

                $labels = $fresh->item->checklistItems();
                $checked = collect($checkedIndexes)->map(fn ($i) => (int) $i)->unique();
                if ($checked->intersect(array_keys($labels))->count() < count($labels)) {
                    throw ValidationException::withMessages(['checklist' => 'Semua butir checklist harus diperiksa.']);
                }

                foreach ($units as $unit) {
                    LoanUnit::create([
                        'loan_id' => $fresh->id,
                        'item_unit_id' => $unit->id,
                        'condition_out' => ItemCondition::Baik->value,
                    ]);
                }

                $inspection = LoanInspection::create([
                    'loan_id' => $fresh->id,
                    'type' => InspectionType::Handover->value,
                    'checklist' => array_map(fn ($label) => ['label' => $label, 'checked' => true], $labels),
                    'final_condition' => ItemCondition::Baik->value,
                    'notes' => $notes,
                    'inspected_by' => $admin->id,
                    'inspected_at' => now(),
                ]);

                foreach ($media as $file) {
                    $path = $file->store("inspections/{$fresh->id}/handover", 'local');
                    $stored[] = $path;
                    InspectionMedia::create([
                        'inspection_id' => $inspection->id,
                        'media_type' => str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'photo',
                        'path' => $path,
                        'caption' => 'Kondisi awal',
                    ]);
                }

                $updated = $this->loans->transition($fresh, LoanStatus::Borrowed, $admin,
                    ['borrowed_at' => now()],
                    'handover', 'Serah terima alat ke siswa',
                    ['unit_codes' => $units->pluck('unit_code')->all()]);

                $this->notifications->handedOver($updated->load('user', 'item'));

                return $updated;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($stored);
            throw $e;
        }
    }
}
