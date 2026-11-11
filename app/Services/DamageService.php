<?php

namespace App\Services;

use App\Enums\DamageStatus;
use App\Enums\UnitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\DamageReport;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Alur laporan kerusakan: open -> in_repair -> resolved / written_off. */
class DamageService
{
    private const TRANSITIONS = [
        'open' => ['in_repair', 'written_off'],
        'in_repair' => ['resolved', 'written_off'],
        'resolved' => [],
        'written_off' => [],
    ];

    public function __construct(private AuditService $audit) {}

    public function allowed(DamageStatus $from): array
    {
        return array_map(fn ($v) => DamageStatus::from($v), self::TRANSITIONS[$from->value] ?? []);
    }

    public function update(DamageReport $report, DamageStatus $to, ?string $note, ?int $fine, User $admin): DamageReport
    {
        return DB::transaction(function () use ($report, $to, $note, $fine, $admin) {
            $fresh = DamageReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if (! in_array($to, $this->allowed($fresh->status), true)) {
                throw new BusinessRuleException("Status {$fresh->status->label()} tidak bisa diubah menjadi {$to->label()}.");
            }

            $final = in_array($to, [DamageStatus::Resolved, DamageStatus::WrittenOff], true);
            if ($final && blank($note)) {
                throw new BusinessRuleException('Catatan penyelesaian wajib diisi.');
            }

            $fresh->forceFill([
                'status' => $to,
                'resolution_note' => $final ? $note : $fresh->resolution_note,
                'fine_amount' => $fine ?? $fresh->fine_amount,
                'resolved_at' => $final ? now() : null,
            ])->save();

            // Efek ke unit fisik: diperbaiki -> tersedia lagi; dihapusbukukan -> nonaktif.
            $unitStatus = match ($to) {
                DamageStatus::InRepair => UnitStatus::Maintenance,
                DamageStatus::Resolved => UnitStatus::Available,
                DamageStatus::WrittenOff => UnitStatus::Inactive,
                default => null,
            };
            if ($unitStatus) {
                ItemUnit::withTrashed()->whereKey($fresh->item_unit_id)->update(['status' => $unitStatus->value]);
            }

            $this->audit->log($fresh->loan, $admin, 'damage_'.$to->value,
                "Laporan kerusakan unit {$fresh->unit->unit_code}: {$to->label()}",
                ['damage_report_id' => $fresh->id]);

            return $fresh;
        });
    }
}
