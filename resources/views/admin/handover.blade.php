<x-app-layout title="Serah Terima" :breadcrumbs="['Verifikasi', $loan->request_code, 'Serah Terima']">
    <a href="{{ route('admin.verifikasi.show', $loan) }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">&lsaquo; Kembali ke pengajuan</a>

    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Serah Terima Alat</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $loan->request_code }} &bull; {{ $loan->user->name }} ({{ $loan->user->kelas }})</p>
    </x-slot>

    <form method="POST" action="{{ route('admin.verifikasi.handover.store', $loan) }}" enctype="multipart/form-data"
          x-data="{
              cardOk: {{ old('card_verified') ? 'true' : 'false' }},
              units: @js(array_map('intval', old('unit_ids', []))),
              checks: @js(array_map('intval', old('checklist', []))),
              files: [],
              previews: [],
              busy: false,
              pick(e) {
                  this.files = Array.from(e.target.files);
                  this.previews = this.files.map(f => ({ src: URL.createObjectURL(f), alt: f.name, caption: f.name, type: f.type.startsWith('video/') ? 'video' : 'image' }));
              },
              need: {{ $loan->quantity }},
              total: {{ count($checklist) }},
              get ready() { return this.cardOk && this.units.length === this.need && this.checks.length === this.total && this.files.length > 0; },
          }" @submit="busy = true" class="grid gap-6 lg:grid-cols-3">
        @csrf

        <div class="space-y-6 lg:col-span-2">
            {{-- 1. Kartu pelajar --}}
            <section class="card p-5" aria-labelledby="h-kartu">
                <h2 id="h-kartu" class="text-base font-semibold text-slate-900">1. Verifikasi kartu pelajar asli</h2>
                <p class="mt-1 text-sm text-slate-600">Cocokkan kartu asli yang dibawa siswa dengan foto yang diunggah.</p>
                <div class="mt-3">
                    @if ($cardIsImage)
                        <button type="button" class="inline-block cursor-zoom-in overflow-hidden rounded-lg border border-slate-200 bg-slate-50" aria-label="Perbesar kartu pelajar"
                                @click="$dispatch('open-viewer', { items: @js([['src' => route('berkas.kartu', $loan), 'alt' => 'Kartu pelajar '.$loan->user->name, 'caption' => 'Kartu pelajar — '.$loan->user->name, 'type' => 'image']]), index: 0 })">
                            <img src="{{ route('berkas.kartu', $loan) }}" alt="Kartu pelajar {{ $loan->user->name }}" class="max-h-48 object-contain">
                        </button>
                    @else
                        <x-button :href="route('berkas.kartu', $loan)" target="_blank" rel="noopener" variant="secondary" size="sm">Buka kartu pelajar (PDF)</x-button>
                    @endif
                </div>
                <label class="mt-4 flex gap-3 text-sm text-slate-800">
                    <input type="checkbox" name="card_verified" value="1" x-model="cardOk" class="mt-0.5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                    <span>Kartu pelajar asli sudah saya periksa dan sesuai dengan peminjam.</span>
                </label>
                @error('card_verified')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </section>

            {{-- 2. Pilih unit --}}
            <section class="card p-5" aria-labelledby="h-unit">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="h-unit" class="text-base font-semibold text-slate-900">2. Pilih unit fisik</h2>
                    <x-status-badge :tone="'gray'"><span x-text="units.length + ' / ' + need + ' dipilih'"></span></x-status-badge>
                </div>
                @if ($units->count() < $loan->quantity)
                    <x-alert variant="error" class="mt-3" title="Unit tidak cukup">Hanya {{ $units->count() }} unit siap diserahkan, padahal dibutuhkan {{ $loan->quantity }}. Periksa status unit di Inventaris.</x-alert>
                @endif
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($units as $unit)
                        <li>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50">
                                <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}" x-model.number="units" :disabled="!units.includes({{ $unit->id }}) && units.length >= need" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                <span class="font-semibold text-slate-900">{{ $unit->unit_code }}</span>
                                @if ($unit->notes)<span class="truncate text-xs text-slate-500">{{ $unit->notes }}</span>@endif
                            </label>
                        </li>
                    @endforeach
                </ul>
                @error('unit_ids')<p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </section>

            {{-- 3. Checklist --}}
            <section class="card p-5" aria-labelledby="h-check">
                <h2 id="h-check" class="text-base font-semibold text-slate-900">3. Checklist kondisi awal</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($checklist as $i => $label)
                        <li>
                            <label class="flex gap-3 text-sm text-slate-800">
                                <input type="checkbox" name="checklist[]" value="{{ $i }}" x-model.number="checks" class="mt-0.5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                <span>{{ $label }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                @error('checklist')<p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </section>

            {{-- 4. Bukti --}}
            <section class="card p-5" aria-labelledby="h-media">
                <h2 id="h-media" class="text-base font-semibold text-slate-900">4. Foto/video kondisi awal</h2>
                <p class="mt-1 text-sm text-slate-600">JPG, PNG, atau MP4 • maks. 20 MB per berkas • 1–6 berkas. Dipakai sebagai pembanding saat pengembalian.</p>
                <label class="mt-3 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 px-4 py-6 text-center hover:bg-slate-50 focus-within:outline focus-within:outline-2 focus-within:outline-emerald-600">
                    <span class="text-sm font-medium text-slate-700">Pilih foto/video</span>
                    <input type="file" name="media[]" multiple accept="image/jpeg,image/png,video/mp4" class="sr-only" @change="pick($event)">
                </label>
                <ul class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4" x-show="previews.length" x-cloak aria-label="Pratinjau bukti">
                    <template x-for="(p, i) in previews" :key="p.src">
                        <li>
                            <button type="button" class="group relative block aspect-square w-full cursor-zoom-in overflow-hidden rounded-lg border border-slate-200 bg-slate-800" :aria-label="'Perbesar ' + p.alt" @click="$dispatch('open-viewer', { items: previews, index: i })">
                                <template x-if="p.type === 'image'"><img :src="p.src" :alt="p.alt" class="size-full object-cover"></template>
                                <template x-if="p.type === 'video'"><span class="flex size-full items-center justify-center text-white"><svg class="size-8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg></span></template>
                            </button>
                            <p class="mt-1 truncate text-[11px] text-slate-500" x-text="p.alt"></p>
                        </li>
                    </template>
                </ul>
                @error('media')<p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                @foreach ($errors->get('media.*') as $messages)@foreach ($messages as $m)<p class="mt-2 text-xs font-medium text-red-700">{{ $m }}</p>@endforeach @endforeach
            </section>

            <section class="card p-5">
                <x-textarea name="notes" label="Catatan serah terima (opsional)" rows="2">{{ old('notes') }}</x-textarea>
            </section>
        </div>

        {{-- Ringkasan --}}
        <aside class="space-y-4 lg:sticky lg:top-4 lg:self-start">
            <div class="card p-5">
                <p class="text-xs font-medium text-emerald-700">{{ $loan->item->category->name }}</p>
                <h2 class="font-semibold text-slate-900">{{ $loan->item->name }} <span class="font-normal text-slate-500">×{{ $loan->quantity }}</span></h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="text-slate-500">Periode</dt><dd class="font-medium text-slate-900">{{ $loan->periodLabel() }}</dd></div>
                    <div><dt class="text-slate-500">Jatuh tempo</dt><dd class="font-medium text-slate-900">{{ $loan->end_date->translatedFormat('l, j M Y') }}</dd></div>
                    <div><dt class="text-slate-500">Petugas</dt><dd class="font-medium text-slate-900">{{ auth()->user()->name }}</dd></div>
                </dl>
            </div>
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-slate-900">Kesiapan</h3>
                <ul class="mt-2 space-y-1.5 text-sm">
                    <li class="flex items-center gap-2" :class="cardOk ? 'text-emerald-800' : 'text-slate-500'"><span x-text="cardOk ? '✓' : '○'" aria-hidden="true"></span>Kartu pelajar diverifikasi</li>
                    <li class="flex items-center gap-2" :class="units.length === need ? 'text-emerald-800' : 'text-slate-500'"><span x-text="units.length === need ? '✓' : '○'" aria-hidden="true"></span>Unit dipilih</li>
                    <li class="flex items-center gap-2" :class="checks.length === total ? 'text-emerald-800' : 'text-slate-500'"><span x-text="checks.length === total ? '✓' : '○'" aria-hidden="true"></span>Checklist lengkap</li>
                    <li class="flex items-center gap-2" :class="files.length ? 'text-emerald-800' : 'text-slate-500'"><span x-text="files.length ? '✓' : '○'" aria-hidden="true"></span>Bukti diunggah</li>
                </ul>
                <x-button type="submit" size="lg" class="mt-4 w-full" x-bind:disabled="!ready || busy"><span x-show="!busy">Konfirmasi Serah Terima</span><span x-show="busy" x-cloak>Memproses…</span></x-button>
            </div>
        </aside>
    </form>
</x-app-layout>
