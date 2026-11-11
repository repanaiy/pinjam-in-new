<x-app-layout title="Inventaris" :breadcrumbs="['Inventaris']">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventaris</h1>
                <p class="mt-1 text-sm text-slate-600">Kelola alat, unit fisik, foto, dan kategori.</p>
            </div>
            <x-button :href="route('admin.inventaris.create')">+ Tambah Item</x-button>
        </div>
    </x-slot>

    @include('admin.inventaris._tabs')

    <form method="GET" action="{{ route('admin.inventaris.index') }}" class="card mb-5 grid gap-3 p-4 sm:grid-cols-4" role="search">
        <div class="sm:col-span-2"><label for="q" class="field-label">Cari</label><input id="q" type="search" name="q" value="{{ $q }}" placeholder="Nama atau kode alat" class="field-control"></div>
        <div>
            <label for="kategori" class="field-label">Kategori</label>
            <select id="kategori" name="kategori" class="field-control"><option value="">Semua</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) $kategori === (string) $c->id)>{{ $c->name }}</option>@endforeach</select>
        </div>
        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field-control"><option value="">Semua</option><option value="active" @selected($status === 'active')>Aktif</option><option value="inactive" @selected($status === 'inactive')>Nonaktif</option></select>
        </div>
        <div class="flex gap-2 sm:col-span-4"><x-button type="submit" size="sm">Terapkan</x-button>@if ($q !== '' || $kategori || $status)<x-button :href="route('admin.inventaris.index')" variant="ghost" size="sm">Reset</x-button>@endif</div>
    </form>

    @if ($items->isEmpty())
        <x-empty-state title="Belum ada alat" text="Tambahkan alat pertama untuk mengisi katalog.">
            <x-button :href="route('admin.inventaris.create')" size="sm">+ Tambah Item</x-button>
        </x-empty-state>
    @else
        <x-table>
            <x-slot name="head">
                <tr>
                    <th scope="col" class="px-4 py-3">Alat</th>
                    <th scope="col" class="px-4 py-3">Kategori</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3">Unit tersedia</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                </tr>
            </x-slot>
            @foreach ($items as $item)
                <tr class="align-middle">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100 text-slate-400">
                                @if ($item->coverUrl())<img src="{{ $item->coverUrl() }}" alt="" class="size-full object-cover">@else<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20 7.5 12 3 4 7.5m16 0v9L12 21m8-13.5-8 4.5m0 9-8-4.5v-9m8 4.5v9M4 7.5l8 4.5"/></svg>@endif
                            </div>
                            <div class="min-w-0"><p class="font-semibold text-slate-900">{{ $item->name }}</p><p class="whitespace-nowrap text-xs text-slate-500">{{ $item->code }}</p></div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $item->category->name }}</td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="font-semibold tabular-nums text-slate-900">{{ $item->avail_units }}/{{ $item->total_units }}</span>
                        @if ($item->avail_units <= $item->min_stock)<x-status-badge tone="amber" label="Stok minim" class="ml-1.5"/>@endif
                    </td>
                    <td class="px-4 py-3"><x-status-badge :tone="$item->status->value === 'active' ? 'green' : 'gray'" :label="$item->status->label()"/></td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <x-button :href="route('admin.inventaris.edit', $item)" variant="secondary" size="sm">Ubah</x-button>
                            <form method="POST" action="{{ route('admin.inventaris.destroy', $item) }}" onsubmit="return confirm('Hapus alat ini? Alat disembunyikan dari katalog, riwayat tetap tersimpan.')">@csrf @method('DELETE')<x-button type="submit" variant="ghost" size="sm" class="!text-red-700 hover:!bg-red-50">Hapus</x-button></form>
                        </div>
                    </td>
                </tr>
            @endforeach
            <x-slot name="foot"><x-pagination :paginator="$items"/></x-slot>
        </x-table>
    @endif
</x-app-layout>
