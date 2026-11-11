<?php

namespace App\Exports;

use App\Models\Loan;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Ekspor Excel laporan; memakai ReportService sehingga selalu mengikuti filter aktif. */
class LoansExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private array $filters) {}

    public function query(): Builder
    {
        return app(ReportService::class)->query($this->filters);
    }

    public function headings(): array
    {
        return ['Kode', 'Diajukan', 'Peminjam', 'NIS', 'Kelas', 'Alat', 'Kode Alat', 'Jumlah', 'Tanggal Pinjam', 'Tanggal Kembali', 'Durasi (hari)', 'Status', 'Terlambat (hari)', 'Keperluan'];
    }

    /** @param Loan $loan */
    public function map($loan): array
    {
        return [
            $loan->request_code,
            $loan->created_at->format('Y-m-d H:i'),
            $loan->user->name,
            $loan->user->nis,
            $loan->user->kelas,
            $loan->item->name,
            $loan->item->code,
            $loan->quantity,
            $loan->start_date->format('Y-m-d'),
            $loan->end_date->format('Y-m-d'),
            $loan->durationDays(),
            $loan->status->label(),
            $loan->late_days,
            $loan->purpose,
        ];
    }
}
