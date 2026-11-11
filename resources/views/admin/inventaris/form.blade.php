@php
    $editing = $item->exists;
    $specsInit = old('specs', collect($item->specs ?? [])->map(fn ($v, $k) => ['key' => $k, 'value' => $v])->values()->all()) ?: [['key' => '', 'value' => '']];
    $unitErrors = $errors->getBag('unit');
    $photoErrors = $errors->getBag('photos');
@endphp
<x-app-layout :title="$editing ? 'Ubah Alat' : 'Tambah Alat'" :breadcrumbs="['Inventaris', $editing ? $item->name : 'Tambah']">
    <a href="{{ route('admin.inventaris.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">&lsaquo; Kembali ke inventaris</a>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $editing ? 'Ubah Alat' : 'Tambah Alat' }}</h1>
        @if ($editing)<p class="mt-1 text-sm text-slate-600">{{ $item->code }} &bull; {{ $item->category->name }}</p>@endif
    </x-slot>

    <form method="POST" action="{{ $editing ? route('admin.inventaris.update', $item) : route('admin.inventaris.store') }}" enctype="multipart/form-data"
          class="grid max-w-5xl gap-6" x-data="{ specs: @js($specsInit), busy: false }" @submit="busy = true">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="card grid gap-4 p-5 sm:grid-cols-2" aria-labelledby="f-info">
            <h2 id="f-info" class="text-base font-semibold text-slate-900 sm:col-span-2">Informasi dasar</h2>
            <div>
                <label for="category_id" class="field-label">Kategori</label>
                <select id="category_id" name="category_id" required class="field-control @error('category_id') border-red-500 @enderror">
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) old('category_id', $item->category_id) === (string) $c->id)>{{ $c->name }}</option>@endforeach
                </select>
                @error('category_id')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </div>
            <x-input name="code" label="Kode alat" :value="old('code', $item->code)" placeholder="mis. ELK-012" required hint="Huruf besar, angka, titik, minus."/>
            <div class="sm:col-span-2"><x-input name="name" label="Nama alat" :value="old('name', $item->name)" required/></div>
            <div class="sm:col-span-2"><x-input name="summary" label="Ringkasan singkat" :value="old('summary', $item->summary)" placeholder="mis. 24,2 MP • Lensa 18-135 mm"/></div>
            <div class="sm:col-span-2"><x-textarea name="description" label="Deskripsi" rows="3">{{ old('description', $item->description) }}</x-textarea></div>
            <x-input name="location" label="Lokasi pengambilan" :value="old('location', $item->location)" placeholder="mis. Ruang Sarpras, Gedung B"/>
            <div>
                <label for="status" class="field-label">Status</label>
                <select id="status" name="status" class="field-control">
                    <option value="active" @selected(old('status', $item->status->value ?? 'active') === 'active')>Aktif (tampil di katalog)</option>
                    <option value="inactive" @selected(old('status', $item->status->value ?? 'active') === 'inactive')>Nonaktif (disembunyikan)</option>
                </select>
                @error('status')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="card p-5" aria-labelledby="f-spec">
            <div class="flex items-center justify-between"><h2 id="f-spec" class="text-base font-semibold text-slate-900">Spesifikasi</h2>
                <button type="button" @click="specs.push({ key: '', value: '' })" class="whitespace-nowrap text-sm font-semibold text-emerald-700 hover:underline">+ Tambah baris</button></div>
            <div class="mt-3 space-y-2">
                <template x-for="(row, i) in specs" :key="i">
                    <div class="grid grid-cols-[1fr_2fr_auto] items-center gap-2">
                        <input type="text" :name="'specs[' + i + '][key]'" x-model="row.key" placeholder="Nama (mis. Sensor)" class="field-control" aria-label="Nama spesifikasi">
                        <input type="text" :name="'specs[' + i + '][value]'" x-model="row.value" placeholder="Nilai (mis. APS-C 24,2 MP)" class="field-control" aria-label="Nilai spesifikasi">
                        <button type="button" @click="specs.splice(i, 1)" class="rounded-lg p-2 text-slate-400 hover:text-red-600" aria-label="Hapus baris">&times;</button>
                    </div>
                </template>
            </div>
        </section>

        <section class="card grid gap-4 p-5" aria-labelledby="f-paket">
            <h2 id="f-paket" class="text-base font-semibold text-slate-900">Isi paket &amp; checklist inspeksi</h2>
            <x-textarea name="package_contents" label="Isi paket" rows="2" placeholder="mis. Body, lensa, baterai + charger, strap">{{ old('package_contents', $item->package_contents) }}</x-textarea>
            <x-textarea name="checklist_text" label="Checklist inspeksi (satu butir per baris)" rows="5" hint="Dipakai saat serah terima dan pengembalian. Kosong = checklist umum.">{{ old('checklist_text', implode("\n", $item->checklist_template ?? [])) }}</x-textarea>
        </section>

        <section class="card grid gap-4 p-5 sm:grid-cols-3" aria-labelledby="f-aturan">
            <h2 id="f-aturan" class="text-base font-semibold text-slate-900 sm:col-span-3">Aturan peminjaman</h2>
            <x-input name="max_loan_days" type="number" min="1" max="30" label="Maks. hari pinjam" :value="old('max_loan_days', $item->max_loan_days)" required/>
            <x-input name="max_qty_per_loan" type="number" min="1" max="20" label="Maks. unit per peminjaman" :value="old('max_qty_per_loan', $item->max_qty_per_loan)" required/>
            <x-input name="min_stock" type="number" min="0" max="100" label="Stok minimum" :value="old('min_stock', $item->min_stock)" required hint="Peringatan di dashboard bila tersedia ≤ nilai ini."/>
        </section>

        <section class="card grid gap-4 p-5 sm:grid-cols-3" aria-labelledby="f-video">
            <h2 id="f-video" class="text-base font-semibold text-slate-900 sm:col-span-3">Video panduan (opsional)</h2>
            <div class="sm:col-span-3"><x-input name="video_url" type="url" label="Tautan video" :value="old('video_url', $item->video_url)" placeholder="https://"/></div>
            <div class="sm:col-span-2"><x-input name="video_title" label="Judul video" :value="old('video_title', $item->video_title)"/></div>
            <x-input name="video_duration" label="Durasi (mm:ss)" :value="old('video_duration', $item->video_duration)" placeholder="04:30"/>
        </section>

        @unless ($editing)
            <section class="card grid gap-4 p-5 sm:grid-cols-2" aria-labelledby="f-awal">
                <h2 id="f-awal" class="text-base font-semibold text-slate-900 sm:col-span-2">Unit &amp; foto awal</h2>
                <x-input name="initial_units" type="number" min="0" max="50" label="Jumlah unit awal" :value="old('initial_units', 1)" hint="Kode unit dibuat otomatis: KODE-01, KODE-02, …"/>
                <div>
                    <label for="photos" class="field-label">Foto (maks. 8, JPG/PNG/WebP, 3 MB)</label>
                    <input id="photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    @foreach ($errors->get('photos.*') as $messages)@foreach ($messages as $m)<p class="mt-1 text-xs font-medium text-red-700">{{ $m }}</p>@endforeach @endforeach
                    @error('photos')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                </div>
            </section>
        @endunless

        <div class="flex gap-2">
            <x-button type="submit" x-bind:disabled="busy"><span x-show="!busy">{{ $editing ? 'Simpan perubahan' : 'Tambah alat' }}</span><span x-show="busy" x-cloak>Menyimpan…</span></x-button>
            <x-button :href="route('admin.inventaris.index')" variant="ghost">Batal</x-button>
        </div>
    </form>

    @if ($editing)
        {{-- ===== Unit fisik ===== --}}
        <section class="card mt-8 max-w-5xl p-5" aria-labelledby="u-title">
            <h2 id="u-title" class="text-base font-semibold text-slate-900">Unit fisik ({{ $units->total() }})</h2>
            @if ($unitErrors->any())
                <x-alert variant="error" class="mt-3" title="Unit tidak tersimpan"><ul class="list-disc pl-4">@foreach ($unitErrors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></x-alert>
            @endif

            <form method="POST" action="{{ route('admin.inventaris.units.store', $item) }}" class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-[1.2fr_1fr_1.5fr_auto] sm:items-end">
                @csrf
                <div><label for="new_unit_code" class="field-label">Kode unit baru</label><input id="new_unit_code" name="unit_code" value="{{ old('unit_code', sprintf('%s-%02d', $item->code, $units->total() + 1)) }}" class="field-control" required></div>
                <div><label for="new_unit_status" class="field-label">Status</label>
                    <select id="new_unit_status" name="status" class="field-control">@foreach (\App\Enums\UnitStatus::cases() as $s)<option value="{{ $s->value }}" @selected($s->value === 'available')>{{ $s->label() }}</option>@endforeach</select></div>
                <div><label for="new_unit_notes" class="field-label">Catatan</label><input id="new_unit_notes" name="notes" class="field-control" placeholder="opsional"></div>
                <x-button type="submit">+ Tambah unit</x-button>
            </form>

            @if ($units->isEmpty())
                <p class="mt-4 text-sm text-slate-500">Belum ada unit. Tambahkan unit agar alat bisa dipinjam.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold text-slate-600"><tr><th scope="col" class="px-2 py-2">Kode</th><th scope="col" class="px-2 py-2">Status</th><th scope="col" class="px-2 py-2">Catatan</th><th scope="col" class="px-2 py-2"><span class="sr-only">Aksi</span></th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($units as $unit)
                                @php $busy = in_array($unit->id, $inUse, true); @endphp
                                <tr class="align-middle">
                                    <td colspan="3" class="px-2 py-2">
                                        <form id="unit-{{ $unit->id }}" method="POST" action="{{ route('admin.inventaris.units.update', $unit) }}" class="grid gap-2 sm:grid-cols-[1.2fr_1fr_1.5fr_auto] sm:items-center">
                                            @csrf @method('PATCH')
                                            <input name="unit_code" value="{{ $unit->unit_code }}" class="field-control" aria-label="Kode unit" required>
                                            @if ($busy)
                                                <input type="hidden" name="status" value="{{ $unit->status->value }}">
                                                <x-status-badge tone="navy" label="Sedang dipinjam"/>
                                            @else
                                                <select name="status" class="field-control" aria-label="Status unit">@foreach (\App\Enums\UnitStatus::cases() as $s)<option value="{{ $s->value }}" @selected($unit->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                                            @endif
                                            <input name="notes" value="{{ $unit->notes }}" class="field-control" aria-label="Catatan unit" placeholder="catatan">
                                            <x-button type="submit" variant="secondary" size="sm">Simpan</x-button>
                                        </form>
                                    </td>
                                    <td class="px-2 py-2 text-right">
                                        <form method="POST" action="{{ route('admin.inventaris.units.destroy', $unit) }}" onsubmit="return confirm('Hapus unit {{ $unit->unit_code }}?')">@csrf @method('DELETE')<x-button type="submit" variant="ghost" size="sm" class="!text-red-700 hover:!bg-red-50" :disabled="$busy">Hapus</x-button></form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-pagination :paginator="$units" class="mt-4"/>
            @endif
        </section>

        {{-- ===== Foto ===== --}}
        <section class="card mt-6 max-w-5xl p-5" aria-labelledby="p-title">
            <h2 id="p-title" class="text-base font-semibold text-slate-900">Foto alat ({{ $item->photos->count() }})</h2>
            <form method="POST" action="{{ route('admin.inventaris.photos.store', $item) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-0 flex-1"><label for="more_photos" class="field-label">Tambah foto</label>
                    <input id="more_photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"></div>
                <x-button type="submit" variant="secondary">Unggah</x-button>
            </form>
            @foreach ($photoErrors->all() as $m)<p class="mt-2 text-xs font-medium text-red-700">{{ $m }}</p>@endforeach

            @if ($item->photos->isEmpty())
                <p class="mt-4 text-sm text-slate-500">Belum ada foto. Foto pertama otomatis menjadi sampul katalog.</p>
            @else
                <ul class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($item->photos as $photo)
                        <li class="overflow-hidden rounded-xl border border-slate-200">
                            <div class="relative aspect-square bg-slate-100">
                                <button type="button" class="size-full cursor-zoom-in" aria-label="Perbesar foto {{ $loop->iteration }}"
                                        @click="$dispatch('open-viewer', { items: @js($item->galleryItems()), index: {{ $loop->index }} })">
                                    <img src="{{ $photo->url() }}" alt="Foto {{ $item->name }}" loading="lazy" class="size-full object-cover">
                                </button>
                                @if ($photo->is_cover)<x-status-badge tone="green" label="Sampul" class="absolute left-2 top-2 shadow-sm"/>@endif
                            </div>
                            <div class="flex items-center justify-between gap-1 p-2">
                                @unless ($photo->is_cover)<form method="POST" action="{{ route('admin.inventaris.photos.cover', $photo) }}">@csrf<button type="submit" class="whitespace-nowrap rounded px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Jadikan sampul</button></form>@else<span></span>@endunless
                                <form method="POST" action="{{ route('admin.inventaris.photos.destroy', $photo) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button type="submit" class="rounded px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Hapus</button></form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- ===== Hapus ===== --}}
        <section class="card mt-6 max-w-5xl border-red-200 p-5">
            <h2 class="text-base font-semibold text-red-900">Hapus alat</h2>
            <p class="mt-1 text-sm text-slate-600">Alat disembunyikan dari katalog (soft delete); riwayat transaksi tetap tersimpan. Tidak bisa dilakukan selama masih ada pengajuan atau peminjaman aktif.</p>
            <form method="POST" action="{{ route('admin.inventaris.destroy', $item) }}" class="mt-3" onsubmit="return confirm('Hapus alat ini?')">@csrf @method('DELETE')<x-button type="submit" variant="destructive">Hapus alat</x-button></form>
        </section>
    @endif
</x-app-layout>
