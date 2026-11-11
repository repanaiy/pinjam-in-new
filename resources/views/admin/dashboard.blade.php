@php
    $has = fn (string $r) => \Illuminate\Support\Facades\Route::has($r);
    $abs = ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE];
@endphp
<x-app-layout title="Dashboard" :breadcrumbs="['Dashboard']">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard Sarpras</h1>
                <p class="mt-1 text-sm text-slate-600">Ringkasan inventaris dan peminjaman hari ini.</p>
            </div>
            @if ($has('admin.inventaris.create'))
                <x-button :href="route('admin.inventaris.create')">+ Tambah Item</x-button>
            @endif
        </div>
    </x-slot>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat-card label="Total alat" :value="$totalUnits" :spark="$spark['total']"/>
        <x-stat-card label="Tersedia" :value="$availableUnits" :spark="$spark['available']"/>
        <x-stat-card label="Pending" :value="$pending" :spark="$spark['pending']" tone="amber"/>
        <x-stat-card label="Dipinjam" :value="$borrowed" :spark="$spark['borrowed']" tone="navy"/>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <section class="card p-4 lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-900">Aktivitas</h2>
                <span class="text-[10px] text-slate-500">7 hari</span>
            </div>
            <div class="h-48"><canvas id="chart-aktivitas"></canvas></div>
        </section>

        <div class="space-y-4">
            <section class="card p-4">
                <h2 class="text-sm font-bold text-slate-900">Utilisasi 30 hari</h2>
                <div class="mt-3 flex items-end gap-2">
                    <p class="text-3xl font-bold tabular-nums text-slate-900">{{ $utilization }}%</p>
                    <p class="text-xs text-slate-500">unit-hari terpakai</p>
                </div>
            </section>

            <section class="card p-4 space-y-4">
                <h2 class="text-sm font-bold text-slate-900">Perlu perhatian</h2>
                <div class="space-y-3 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">Jatuh tempo</span>
                        <span class="font-bold text-amber-600">{{ $attention['dueToday']->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">Terlambat</span>
                        <span class="font-bold text-red-600">{{ $attention['overdue'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">Stok minim</span>
                        <span class="font-bold text-amber-600">{{ $attention['lowStock']->count() }}</span>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Antrean verifikasi --}}
    <section class="mt-6" aria-labelledby="antrean-title">
        <div class="mb-3 flex items-center justify-between">
            <h2 id="antrean-title" class="text-base font-semibold text-slate-900">Antrean verifikasi</h2>
            <a href="{{ route('admin.verifikasi.index') }}" class="whitespace-nowrap text-sm font-semibold text-emerald-700 hover:underline">Lihat semua &rsaquo;</a>
        </div>
        @if ($queue->isEmpty())
            <x-empty-state title="Tidak ada pengajuan menunggu" text="Semua pengajuan sudah diproses."/>
        @else
            <x-table>
                <x-slot name="head">
                    <tr>
                        <th scope="col" class="px-4 py-3">Peminjam</th>
                        <th scope="col" class="px-4 py-3">Alat &amp; periode</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Lama menunggu</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot>
                @foreach ($queue as $loan)
                    <tr class="align-middle">
                        <td class="px-4 py-3"><p class="font-semibold text-slate-900">{{ $loan->user->name }}</p><p class="text-xs text-slate-500">{{ $loan->user->kelas }}</p></td>
                        <td class="px-4 py-3"><p class="text-slate-900">{{ $loan->item->name }} <span class="text-slate-500">×{{ $loan->quantity }}</span></p><p class="whitespace-nowrap text-xs text-slate-500">{{ $loan->periodLabel() }}</p></td>
                        <td class="px-4 py-3"><x-status-badge :status="$loan->status"/></td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $loan->created_at->diffForHumans($abs) }}</td>
                        <td class="px-4 py-3 text-right"><x-button :href="route('admin.verifikasi.show', $loan)" size="sm">Tinjau</x-button></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('chart-aktivitas');
            if (!el || !window.Chart) return;
            const d = @json($chart);
            new Chart(el, {
                type: 'bar',
                data: {
                    labels: d.labels,
                    datasets: [
                        { label: 'Dipinjam', data: d.borrowed, backgroundColor: '#1E293B', borderRadius: 4 },
                        { label: 'Dikembalikan', data: d.returned, backgroundColor: '#10B981', borderRadius: 4 },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        });
    </script>
</x-app-layout>
