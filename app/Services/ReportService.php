<?php

namespace App\Services;

use App\Enums\LoanStatus;
use App\Models\Category;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Satu sumber filter/ringkasan untuk layar Laporan, ekspor PDF, dan ekspor Excel. */
class ReportService
{
    public const APPROVED = [
        LoanStatus::Approved, LoanStatus::ReadyPickup, LoanStatus::Borrowed, LoanStatus::Overdue,
        LoanStatus::Returned, LoanStatus::ReturnedDamaged, LoanStatus::ReturnedLost,
    ];

    public const DECLINED = [LoanStatus::Rejected, LoanStatus::Cancelled];

    /** Normalisasi query string menjadi filter yang aman. */
    public function filters(Request $request): array
    {
        $date = fn (?string $v) => $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false ? $v : null;

        $f = [
            'dari' => $date($request->query('dari')),
            'sampai' => $date($request->query('sampai')),
            'kategori' => Category::query()->whereKey($request->query('kategori'))->value('id'),
            'status' => LoanStatus::tryFrom((string) $request->query('status'))?->value,
            'rentang' => in_array($request->query('rentang'), ['hari-ini', '7-hari', '30-hari', 'semester'], true) ? $request->query('rentang') : null,
        ];

        if ($f['rentang']) {
            [$f['dari'], $f['sampai']] = $this->range($f['rentang']);
        }

        return $f;
    }

    /** @return array{0:string,1:string} */
    public function range(string $key): array
    {
        $today = today();

        return match ($key) {
            'hari-ini' => [$today->toDateString(), $today->toDateString()],
            '7-hari' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            '30-hari' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            // Semester ganjil: Juli–Desember; genap: Januari–Juni (tanggal tetap, bebas masalah luapan bulan).
            default => $today->month >= 7
                ? ["{$today->year}-07-01", "{$today->year}-12-31"]
                : ["{$today->year}-01-01", "{$today->year}-06-30"],
        };
    }

    private function base(array $f): Builder
    {
        return Loan::query()
            ->when($f['dari'] ?? null, fn (Builder $q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($f['sampai'] ?? null, fn (Builder $q, $d) => $q->whereDate('start_date', '<=', $d))
            ->when($f['kategori'] ?? null, fn (Builder $q, $c) => $q->whereHas('item', fn ($i) => $i->where('category_id', $c)))
            ->when($f['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s));
    }

    public function query(array $f): Builder
    {
        return $this->base($f)->with(['user', 'item.category'])->orderByDesc('start_date')->orderByDesc('id');
    }

    /** @return array{total:int,approved:int,declined:int,avg_days:float} */
    public function summary(array $f): array
    {
        $quote = fn (array $statuses) => "'".implode("','", array_map(fn (LoanStatus $s) => $s->value, $statuses))."'";
        $duration = DB::getDriverName() === 'sqlite'
            ? '(julianday(end_date) - julianday(start_date) + 1)'
            : '(DATEDIFF(end_date, start_date) + 1)';

        $row = $this->base($f)->selectRaw(
            'count(*) as total, '
            .'coalesce(sum(case when status in ('.$quote(self::APPROVED).') then 1 else 0 end), 0) as approved, '
            .'coalesce(sum(case when status in ('.$quote(self::DECLINED).') then 1 else 0 end), 0) as declined, '
            ."coalesce(avg({$duration}), 0) as avg_days"
        )->first();

        return [
            'total' => (int) $row->total,
            'approved' => (int) $row->approved,
            'declined' => (int) $row->declined,
            'avg_days' => round((float) $row->avg_days, 1),
        ];
    }

    /** Deskripsi filter untuk header PDF. */
    public function describe(array $f): string
    {
        $parts = [];
        if ($f['dari'] || $f['sampai']) {
            $fmt = fn ($d) => $d ? Carbon::parse($d)->translatedFormat('j M Y') : '…';
            $parts[] = 'Periode '.$fmt($f['dari']).' – '.$fmt($f['sampai']);
        }
        if ($f['kategori']) {
            $parts[] = 'Kategori '.Category::query()->whereKey($f['kategori'])->value('name');
        }
        if ($f['status']) {
            $parts[] = 'Status '.LoanStatus::from($f['status'])->label();
        }

        return $parts ? implode(' • ', $parts) : 'Semua transaksi';
    }
}
