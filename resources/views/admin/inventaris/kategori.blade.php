<x-app-layout title="Kategori" :breadcrumbs="['Inventaris', 'Kategori']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventaris</h1>
        <p class="mt-1 text-sm text-slate-600">Kelola alat, unit fisik, foto, dan kategori.</p>
    </x-slot>
    @include('admin.inventaris._tabs')

    @if ($errors->any())
        <x-alert variant="error" class="mb-4" title="Kategori tidak tersimpan"><ul class="list-disc pl-4">@foreach ($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></x-alert>
    @endif

    <form method="POST" action="{{ route('admin.inventaris.kategori.store') }}" class="card mb-5 grid gap-3 p-4 sm:grid-cols-[2fr_1fr_1fr_auto] sm:items-end">
        @csrf
        <div><label for="n_name" class="field-label">Kategori baru</label><input id="n_name" name="name" class="field-control" required placeholder="mis. Audio Visual"></div>
        <div><label for="n_prefix" class="field-label">Awalan kode</label><input id="n_prefix" name="code_prefix" class="field-control" maxlength="10" placeholder="AVS"></div>
        <div><label for="n_order" class="field-label">Urutan</label><input id="n_order" type="number" name="sort_order" value="0" min="0" max="999" class="field-control"></div>
        <x-button type="submit">+ Tambah</x-button>
    </form>

    @if ($categories->isEmpty())
        <x-empty-state title="Belum ada kategori" text="Tambahkan kategori untuk mengelompokkan alat."/>
    @else
        <div class="card divide-y divide-slate-100">
            @foreach ($categories as $c)
                <div class="grid gap-3 p-4 sm:grid-cols-[2fr_1fr_1fr_auto_auto] sm:items-center">
                    <form id="cat-{{ $c->id }}" method="POST" action="{{ route('admin.inventaris.kategori.update', $c) }}" class="contents">
                        @csrf @method('PATCH')
                        <input name="name" value="{{ $c->name }}" class="field-control" aria-label="Nama kategori" required>
                        <input name="code_prefix" value="{{ $c->code_prefix }}" class="field-control" aria-label="Awalan kode" maxlength="10">
                        <input type="number" name="sort_order" value="{{ $c->sort_order }}" class="field-control" aria-label="Urutan" min="0" max="999">
                        <x-button type="submit" variant="secondary" size="sm">Simpan</x-button>
                    </form>
                    <form method="POST" action="{{ route('admin.inventaris.kategori.destroy', $c) }}" onsubmit="return confirm('Hapus kategori {{ $c->name }}?')" class="flex items-center justify-end gap-2">
                        @csrf @method('DELETE')
                        <span class="whitespace-nowrap text-xs text-slate-500">{{ $c->items_count }} alat</span>
                        <x-button type="submit" variant="ghost" size="sm" class="!text-red-700 hover:!bg-red-50">Hapus</x-button>
                    </form>
                </div>
            @endforeach
        </div>
        <x-pagination :paginator="$categories" class="mt-5"/>
    @endif
</x-app-layout>
