@php
    $link = fn (array $o = []) => route('admin.laporan.index', array_filter(array_merge($exportQuery, $o), fn ($v) => $v !== null && $v !== ''));
    $quick = ['hari-ini' => 'Hari ini', '7-hari' => '7 hari', '30-hari' => '30 hari', 'semester' => 'Semester ini'];
    $hasFilter = $exportQuery !== [];
@endphp
<x-app-layout title="Laporan" :breadcrumbs="['Laporan']">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Laporan Peminjaman</h1>
                <p class="mt-1 text-sm text-slate-600">Ringkasan dan daftar transaksi. Ekspor mengikuti filter yang aktif.</p>
            </div>
            <div class="flex gap-2">
                <x-button :href="route('admin.laporan.pdf', $exportQuery)" variant="secondary">Ekspor PDF</x-button>
                <x-button :href="route('admin.laporan.excel', $exportQuery)">Ekspor Excel</x-button>
            </div>
        </div>
    </x-slot>

    <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Rentang cepat">
        @foreach ($quick as $key => $label)
            <x-filter-chip :label="$label" :active="$filters['rentang'] === $key" :href="route('admin.laporan.index', array_filter(['rentang' => $key, 'kategori' => $filters['kategori'], 'status' => $filters['status']]))"/>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.laporan.index') }}" class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <div><label for="dari" class="field-label">Tanggal pinjam dari</label><input id="dari" type="date" name="dari" value="{{ $filters['dari'] }}" class="field-control"></div>
        <div><label for="sampai" class="field-label">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ $filters['sampai'] }}" class="field-control"></div>
        <div><label for="kategori" class="field-label">Kategori</label><select id="kategori" name="kategori" class="field-control"><option value="">Semua</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) $filters['kategori'] === (string) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div><label for="status" class="field-label">Status</label><select id="status" name="status" class="field-control"><option value="">Semua</option>@foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>{{ $s->label() }}</option>@endforeach</select></div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-4"><x-button type="submit" size="sm">Terapkan</x-button>@if ($hasFilter)<x-button :href="route('admin.laporan.index')" variant="ghost" size="sm">Reset</x-button>@endif</div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total transaksi" :value="$summary['total']"/>
        <x-stat-card label="Disetujui" :value="$summary['approved']" hint="Disetujui hingga selesai"/>
        <x-stat-card label="Ditolak / batal" :value="$summary['declined']" tone="red"/>
        <x-stat-card label="Rata-rata durasi" :value="number_format($summary['avg_days'], 1, ',', '.').' hari'" tone="navy"/>
    </div>

    <div class="mt-6">
        @if ($loans->isEmpty())
            <x-empty-state title="Tidak ada transaksi" :text="$hasFilter ? 'Tidak ada data pada filter ini. Coba ubah rentang tanggal.' : 'Belum ada transaksi peminjaman.'">
                @if ($hasFilter)<x-button :href="route('admin.laporan.index')" variant="secondary" size="sm">Reset filter</x-button>@endif
            </x-empty-state>
        @else
            <x-table>
                <x-slot name="head">
                    <tr>
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Kode</th>
                        <th scope="col" class="px-4 py-3">Peminjam</th>
                        <th scope="col" class="px-4 py-3">Alat</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Periode</th>
                        <th scope="col" class="px-4 py-3">Durasi</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                    </tr>
                </x-slot>
                @foreach ($loans as $loan)
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('admin.verifikasi.show', $loan) }}" class="font-semibold text-emerald-700 hover:underline">{{ $loan->request_code }}</a></td>
                        <td class="px-4 py-3"><p class="text-slate-900">{{ $loan->user->name }}</p><p class="text-xs text-slate-500">{{ $loan->user->kelas }}</p></td>
                        <td class="px-4 py-3">{{ $loan->item->name }} <span class="text-slate-500">×{{ $loan->quantity }}</span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $loan->periodLabel() }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $loan->durationDays() }} hari</td>
                        <td class="px-4 py-3"><x-status-badge :status="$loan->status"/>@if ($loan->is_late)<p class="mt-1 whitespace-nowrap text-xs text-red-700">Terlambat {{ $loan->late_days }} hari</p>@endif</td>
                    </tr>
                @endforeach
                <x-slot name="foot"><x-pagination :paginator="$loans"/></x-slot>
            </x-table>
        @endif
    </div>
</x-app-layout>
