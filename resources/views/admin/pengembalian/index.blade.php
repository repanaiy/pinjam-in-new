@php
    $url = fn (array $o = []) => route('admin.pengembalian.index', array_filter(array_merge(['q' => $q, 'f' => $filter !== 'semua' ? $filter : null], $o)));
@endphp
<x-app-layout title="Pengembalian" :breadcrumbs="['Pengembalian']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pengembalian &amp; Inspeksi</h1>
        <p class="mt-1 text-sm text-slate-600">Cari peminjaman aktif, periksa kondisi alat, lalu selesaikan pengembalian.</p>
    </x-slot>

    <form method="GET" action="{{ route('admin.pengembalian.index') }}" class="mb-4" role="search">
        @if ($filter !== 'semua')<input type="hidden" name="f" value="{{ $filter }}">@endif
        <div class="flex gap-2">
            <div class="flex-1"><label for="q" class="sr-only">Cari peminjaman aktif</label><input id="q" type="search" name="q" value="{{ $q }}" placeholder="Cari ID, siswa, alat, atau kode unit" class="field-control"></div>
            <x-button type="submit">Cari</x-button>
        </div>
    </form>

    <div class="mb-5 flex flex-wrap gap-2" role="group" aria-label="Filter">
        <x-filter-chip label="Semua aktif" :count="$counts['semua']" :active="$filter === 'semua'" :href="$url(['f' => null])"/>
        <x-filter-chip label="Jatuh tempo hari ini" :count="$counts['hari-ini']" :active="$filter === 'hari-ini'" :href="$url(['f' => 'hari-ini'])"/>
        <x-filter-chip label="Terlambat" :count="$counts['terlambat']" :active="$filter === 'terlambat'" :href="$url(['f' => 'terlambat'])"/>
    </div>

    @if ($loans->isEmpty())
        <x-empty-state :title="$q !== '' || $filter !== 'semua' ? 'Tidak ada peminjaman yang cocok' : 'Tidak ada peminjaman aktif'"
                       :text="$q !== '' || $filter !== 'semua' ? 'Coba ubah kata kunci atau filter.' : 'Semua alat sudah kembali.'">
            @if ($q !== '' || $filter !== 'semua')<x-button :href="route('admin.pengembalian.index')" variant="secondary" size="sm">Reset</x-button>@endif
        </x-empty-state>
    @else
        <x-table>
            <x-slot name="head">
                <tr>
                    <th scope="col" class="px-4 py-3">Peminjam</th>
                    <th scope="col" class="px-4 py-3">Alat</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3">Jatuh tempo</th>
                    <th scope="col" class="px-4 py-3">Keterangan</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                </tr>
            </x-slot>
            @foreach ($loans as $loan)
                @php $badge = $loan->dueBadge(); @endphp
                <tr class="align-middle">
                    <td class="px-4 py-3"><p class="font-semibold text-slate-900">{{ $loan->user->name }}</p><p class="whitespace-nowrap text-xs text-slate-500">{{ $loan->request_code }} &bull; {{ $loan->user->kelas }}</p></td>
                    <td class="px-4 py-3">
                        <p class="text-slate-900">{{ $loan->item->name }} <span class="text-slate-500">×{{ $loan->quantity }}</span></p>
                        <p class="text-xs text-slate-500">{{ $loan->loanUnits->map(fn ($lu) => $lu->unit->unit_code)->implode(', ') }}</p>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $loan->end_date->translatedFormat('j M Y') }}</td>
                    <td class="px-4 py-3"><x-status-badge :tone="$badge['tone']" :label="$badge['label']"/></td>
                    <td class="px-4 py-3 text-right"><x-button :href="route('admin.pengembalian.show', $loan)" size="sm">Proses pengembalian</x-button></td>
                </tr>
            @endforeach
            <x-slot name="foot"><x-pagination :paginator="$loans"/></x-slot>
        </x-table>
    @endif
</x-app-layout>
