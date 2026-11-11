@php
    $url = fn (array $override = []) => route('katalog.index', array_filter(
        array_merge([
            'q' => $q,
            'kategori' => $categorySlug,
            'urut' => $sort !== 'stok' ? $sort : null,
            'tersedia' => $onlyReady ? 1 : null,
        ], $override),
        fn ($v) => $v !== null && $v !== '' && $v !== false
    ));
    $filtered = $q !== '' || $categorySlug !== '' || $onlyReady;
@endphp
<x-app-layout title="Katalog" :breadcrumbs="['Katalog']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Katalog Alat</h1>
        <p class="mt-1 text-sm text-slate-600">Pilih alat yang ingin dipinjam, lalu ajukan peminjaman.</p>
    </x-slot>

    <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-950">
        <svg class="size-6 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        <p class="text-sm"><span class="font-bold tabular-nums">{{ $readyToday }}</span> unit siap dipinjam hari ini</p>
    </div>

    {{-- Pencarian --}}
    <form method="GET" action="{{ route('katalog.index') }}" class="mb-4" role="search">
        @if ($categorySlug)<input type="hidden" name="kategori" value="{{ $categorySlug }}">@endif
        @if ($sort !== 'stok')<input type="hidden" name="urut" value="{{ $sort }}">@endif
        @if ($onlyReady)<input type="hidden" name="tersedia" value="1">@endif
        <div class="flex gap-2">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.4l3.57 3.58a1 1 0 0 1-1.42 1.42l-3.58-3.57A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                <label for="q" class="sr-only">Cari alat</label>
                <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau kode alat" class="field-control pl-10">
            </div>
            <x-button type="submit">Cari</x-button>
        </div>
    </form>

    {{-- Chip kategori + urut/filter --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2" role="group" aria-label="Kategori">
            <x-filter-chip label="Semua" :count="$totalActive" :active="$categorySlug === ''" :href="$url(['kategori' => null])"/>
            @foreach ($categories as $c)
                <x-filter-chip :label="$c->name" :count="$c->items_count" :active="$categorySlug === $c->slug" :href="$url(['kategori' => $c->slug])"/>
            @endforeach
        </div>
        <form method="GET" action="{{ route('katalog.index') }}" class="flex items-center gap-3">
            @if ($q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
            @if ($categorySlug)<input type="hidden" name="kategori" value="{{ $categorySlug }}">@endif
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="tersedia" value="1" @checked($onlyReady) onchange="this.form.submit()" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                <span class="whitespace-nowrap">Hanya yang tersedia</span>
            </label>
            <label for="urut" class="sr-only">Urutkan</label>
            <select id="urut" name="urut" onchange="this.form.submit()" class="field-control w-auto py-2 pr-8">
                <option value="stok" @selected($sort === 'stok')>Stok tersedia</option>
                <option value="nama" @selected($sort === 'nama')>Nama A–Z</option>
                <option value="terbaru" @selected($sort === 'terbaru')>Terbaru</option>
            </select>
        </form>
    </div>

    @if ($items->isEmpty())
        <x-empty-state :title="$filtered ? 'Tidak ada alat yang cocok' : 'Belum ada alat'"
                       :text="$filtered ? 'Coba ubah kata kunci atau hapus filter.' : 'Petugas sarpras belum menambahkan alat ke katalog.'">
            @if ($filtered)<x-button :href="route('katalog.index')" variant="secondary" size="sm">Reset filter</x-button>@endif
        </x-empty-state>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            @foreach ($items as $item)
                <x-item-card :item="$item"/>
            @endforeach
        </div>
        <x-pagination :paginator="$items" class="mt-6"/>
    @endif
</x-app-layout>
