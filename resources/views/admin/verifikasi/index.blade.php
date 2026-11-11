@php
    $abs = ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE];
    $keep = array_filter(['q' => $q, 'status' => $status !== 'antrean' ? $status : null, 'urut' => $sort !== 'terlama' ? $sort : null, 'dari' => $from, 'sampai' => $to]);
@endphp
<x-app-layout title="Verifikasi" :breadcrumbs="$selected ? ['Verifikasi', $selected->request_code] : ['Verifikasi']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Verifikasi Pengajuan</h1>
        <p class="mt-1 text-sm text-slate-600">Tinjau pengajuan, setujui dengan jadwal pengambilan, atau tolak dengan alasan.</p>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-12">
        {{-- ===== Kiri: antrean ===== --}}
        <section class="{{ $selected ? 'hidden lg:block' : '' }} space-y-4 lg:col-span-5" aria-label="Antrean pengajuan">
            <form method="GET" action="{{ route('admin.verifikasi.index') }}" class="card space-y-3 p-4">
                <div>
                    <label for="q" class="sr-only">Cari</label>
                    <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Cari ID, siswa, atau alat" class="field-control">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="status" class="field-label">Status</label>
                        <select id="status" name="status" class="field-control">
                            <option value="antrean" @selected($status === 'antrean')>Antrean aktif</option>
                            @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="urut" class="field-label">Urutan</label>
                        <select id="urut" name="urut" class="field-control">
                            <option value="terlama" @selected($sort === 'terlama')>Terlama dulu</option>
                            <option value="terbaru" @selected($sort === 'terbaru')>Terbaru dulu</option>
                        </select>
                    </div>
                    <div><label for="dari" class="field-label">Diajukan dari</label><input id="dari" type="date" name="dari" value="{{ $from }}" class="field-control"></div>
                    <div><label for="sampai" class="field-label">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ $to }}" class="field-control"></div>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" size="sm">Terapkan</x-button>
                    @if ($keep)<x-button :href="route('admin.verifikasi.index')" variant="ghost" size="sm">Reset</x-button>@endif
                </div>
            </form>

            @if ($loans->isEmpty())
                <x-empty-state title="Tidak ada pengajuan" :text="$keep ? 'Tidak ada yang cocok dengan filter.' : 'Antrean kosong. Semua pengajuan sudah diproses.'"/>
            @else
                <ul class="space-y-2">
                    @foreach ($loans as $l)
                        @php $active = $selected && $selected->id === $l->id; @endphp
                        <li>
                            <a href="{{ route('admin.verifikasi.show', ['loan' => $l] + $keep) }}" @if ($active) aria-current="true" @endif
                               class="card block p-4 transition-colors hover:border-emerald-600 {{ $active ? 'border-emerald-600 ring-1 ring-emerald-600' : '' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="whitespace-nowrap text-xs text-slate-500">{{ $l->request_code }}</p>
                                        <p class="truncate font-semibold text-slate-900">{{ $l->user->name }}</p>
                                        <p class="truncate text-sm text-slate-600">{{ $l->item->name }} ×{{ $l->quantity }}</p>
                                        <p class="whitespace-nowrap text-xs text-slate-500">{{ $l->periodLabel() }}</p>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                                        <x-status-badge :status="$l->status"/>
                                        <span class="whitespace-nowrap text-xs text-slate-500">{{ $l->created_at->diffForHumans($abs) }}</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <x-pagination :paginator="$loans"/>
            @endif
        </section>

        {{-- ===== Kanan: detail ===== --}}
        <section class="lg:col-span-7" aria-label="Detail pengajuan">
            @if ($selected)
                <a href="{{ route('admin.verifikasi.index', $keep) }}" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900 lg:hidden">&lsaquo; Kembali ke antrean</a>
                @include('admin.verifikasi._detail', ['loan' => $selected, 'detail' => $detail])
            @else
                <x-empty-state title="Pilih pengajuan untuk ditinjau" text="Detail, kartu pelajar, dan hasil cek otomatis tampil di sini."/>
            @endif
        </section>
    </div>
</x-app-layout>
