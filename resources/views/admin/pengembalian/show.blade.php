@php
    $unitIds = $loan->loanUnits->pluck('item_unit_id')->map(fn ($i) => (int) $i)->all();
    $defaults = collect($unitIds)->mapWithKeys(fn ($id) => [$id => 'baik'])->all();
    $conditionsInit = old('conditions', $defaults);
    $checksInit = array_map('intval', old('checklist', $errors->any() ? [] : range(0, max(count($checklist) - 1, 0))));
    $hasChecklist = count($checklist) > 0;
    $beforeItems = $handover
        ? $handover->media->map(fn ($m) => ['src' => route('berkas.media', $m), 'alt' => 'Kondisi saat serah terima', 'caption' => 'Sebelum (serah terima) — '.($m->caption ?? 'bukti'), 'type' => $m->media_type === 'video' ? 'video' : 'image'])->values()->all()
        : [];
@endphp
<x-app-layout title="Pengembalian" :breadcrumbs="['Pengembalian', $loan->request_code]">
    <a href="{{ route('admin.pengembalian.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">&lsaquo; Kembali ke daftar</a>

    <div class="card mb-6 flex flex-wrap items-center justify-between gap-4 p-5">
        <div class="min-w-0">
            <p class="text-xs font-medium text-emerald-700">{{ $loan->item->category->name }}</p>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ $loan->item->name }} <span class="font-normal text-slate-500">×{{ $loan->quantity }}</span></h1>
            <p class="text-sm text-slate-600">{{ $loan->user->name }} ({{ $loan->user->kelas }}) &bull; <span class="whitespace-nowrap">{{ $loan->request_code }}</span></p>
            <p class="text-sm text-slate-600">Jatuh tempo <span class="whitespace-nowrap font-medium text-slate-900">{{ $loan->end_date->translatedFormat('l, j M Y') }}</span></p>
        </div>
        <x-status-badge :tone="$badge['tone']" :label="$badge['label']" class="!px-3.5 !py-1.5 !text-sm"/>
    </div>

    <form method="POST" action="{{ route('admin.pengembalian.store', $loan) }}" enctype="multipart/form-data"
          x-data="{
              conditions: @js($conditionsInit),
              checks: @js($checksInit),
              total: {{ count($checklist) }},
              notes: @js(old('notes', '')),
              files: [],
              previews: [],
              busy: false,
              availableNow: {{ $availableNow }},
              lateDays: {{ $lateDays }},
              get values() { return Object.values(this.conditions); },
              get anyRusak() { return this.values.includes('rusak'); },
              get anyHilang() { return this.values.includes('hilang'); },
              get worst() { return this.anyHilang ? 'hilang' : (this.anyRusak ? 'rusak' : 'baik'); },
              get baikCount() { return this.values.filter(v => v === 'baik').length; },
              get unchecked() { return this.total - this.checks.length; },
              get needNotes() { return this.worst !== 'baik' || this.unchecked > 0; },
              get notesOk() { return !this.needNotes || this.notes.trim().length > 0; },
              get mediaOk() { return !this.anyRusak || this.files.length > 0; },
              get ready() { return this.notesOk && this.mediaOk; },
              get previewItems() { return this.previews.map(p => ({ src: p.url, alt: p.name, caption: 'Sesudah (pengembalian) — ' + p.name, type: p.image ? 'image' : 'video' })); },
              pick(e) {
                  this.files = Array.from(e.target.files);
                  this.previews = this.files.map(f => ({ name: f.name, image: f.type.startsWith('image/'), url: URL.createObjectURL(f) }));
              },
          }" @submit="busy = true" class="grid gap-6 lg:grid-cols-3">
        @csrf

        <div class="space-y-6 lg:col-span-2">
            {{-- Sebelum vs sesudah --}}
            <section class="card p-5" aria-labelledby="r-banding">
                <h2 id="r-banding" class="text-base font-semibold text-slate-900">Perbandingan kondisi</h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Sebelum (serah terima)</p>
                        @if ($handover && $handover->media->isNotEmpty())
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($handover->media as $m)
                                    <button type="button" class="group relative block aspect-square w-full cursor-zoom-in overflow-hidden rounded-lg border border-slate-200 bg-slate-800" aria-label="Perbesar bukti serah terima {{ $loop->iteration }}"
                                            @click="$dispatch('open-viewer', { items: @js($beforeItems), index: {{ $loop->index }} })">
                                        @if ($m->media_type === 'video')
                                            <span class="flex size-full items-center justify-center text-white"><svg class="size-10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg></span>
                                        @else
                                            <img src="{{ route('berkas.media', $m) }}" alt="Kondisi saat serah terima" loading="lazy" class="size-full object-cover">
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            @if ($handover->notes)<p class="mt-2 text-xs text-slate-600">Catatan serah terima: {{ $handover->notes }}</p>@endif
                        @else
                            <div class="flex aspect-[4/3] items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-sm text-slate-500">Belum ada foto serah terima.</div>
                        @endif
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Sesudah (pengembalian)</p>
                        <div x-show="previews.length" x-cloak class="grid grid-cols-2 gap-2">
                            <template x-for="(p, i) in previews" :key="p.url">
                                <button type="button" class="relative block aspect-square w-full cursor-zoom-in overflow-hidden rounded-lg border border-slate-200 bg-slate-800" :aria-label="'Perbesar ' + p.name" @click="$dispatch('open-viewer', { items: previewItems, index: i })">
                                    <template x-if="p.image"><img :src="p.url" :alt="p.name" class="size-full object-cover"></template>
                                    <template x-if="!p.image"><span class="flex size-full items-center justify-center text-white"><svg class="size-10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg></span></template>
                                </button>
                            </template>
                        </div>
                        <div x-show="!previews.length" class="flex aspect-[4/3] items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-sm text-slate-500">Unggah bukti di bawah untuk membandingkan.</div>
                    </div>
                </div>
            </section>

            {{-- Kondisi per unit --}}
            <section class="card p-5" aria-labelledby="r-unit">
                <h2 id="r-unit" class="text-base font-semibold text-slate-900">Kondisi akhir tiap unit</h2>
                <p class="mt-1 text-sm text-slate-600">Hanya unit yang rusak atau hilang yang berubah status; unit lain tetap tersedia.</p>
                @if ($loan->loanUnits->isEmpty())
                    <x-alert variant="error" class="mt-3" title="Tidak ada unit tercatat">Peminjaman ini tidak memiliki unit fisik. Periksa data serah terima.</x-alert>
                @endif
                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($loan->loanUnits as $lu)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div><p class="font-semibold text-slate-900">{{ $lu->unit->unit_code }}</p><p class="text-xs text-slate-500">Kondisi saat keluar: {{ $lu->condition_out->label() }}</p></div>
                            <fieldset class="flex gap-1 rounded-xl bg-slate-100 p-1">
                                <legend class="sr-only">Kondisi {{ $lu->unit->unit_code }}</legend>
                                @foreach (['baik' => 'Baik', 'rusak' => 'Rusak', 'hilang' => 'Hilang'] as $val => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" class="peer sr-only" name="conditions[{{ $lu->item_unit_id }}]" value="{{ $val }}" x-model="conditions[{{ $lu->item_unit_id }}]">
                                        <span class="block whitespace-nowrap rounded-lg px-3.5 py-1.5 text-sm font-semibold text-slate-600 peer-checked:bg-white peer-checked:shadow-sm peer-checked:ring-1 peer-checked:ring-slate-200 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-emerald-600
                                            {{ $val === 'baik' ? 'peer-checked:text-emerald-800' : 'peer-checked:text-red-700' }}">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                        </li>
                    @endforeach
                </ul>
                @error('conditions')<p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                @error('conditions.*')<p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </section>

            {{-- Checklist --}}
            <section class="card p-5" aria-labelledby="r-check">
                <h2 id="r-check" class="text-base font-semibold text-slate-900">Checklist saat dikembalikan</h2>
                <p class="mt-1 text-sm text-slate-600">Hapus centang pada butir yang tidak sesuai atau tidak lengkap.</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($checklist as $i => $label)
                        <li><label class="flex gap-3 text-sm text-slate-800"><input type="checkbox" name="checklist[]" value="{{ $i }}" x-model.number="checks" class="mt-0.5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600"><span>{{ $label }}</span></label></li>
                    @endforeach
                </ul>
            </section>

            {{-- Bukti --}}
            <section class="card p-5" aria-labelledby="r-media">
                <h2 id="r-media" class="text-base font-semibold text-slate-900">Bukti foto/video <span class="text-sm font-normal text-slate-500" x-show="anyRusak" x-cloak>— wajib untuk unit rusak</span></h2>
                <p class="mt-1 text-sm text-slate-600">JPG, PNG, atau MP4 • maks. 20 MB per berkas • hingga 6 berkas.</p>
                <label class="mt-3 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 px-4 py-6 text-center hover:bg-slate-50 focus-within:outline focus-within:outline-2 focus-within:outline-emerald-600">
                    <span class="text-sm font-medium text-slate-700">Pilih foto/video</span>
                    <input type="file" name="media[]" multiple accept="image/jpeg,image/png,video/mp4" class="sr-only" @change="pick($event)">
                </label>
                @error('media')<p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                @foreach ($errors->get('media.*') as $messages)@foreach ($messages as $m)<p class="mt-2 text-xs font-medium text-red-700">{{ $m }}</p>@endforeach @endforeach
            </section>

            {{-- Catatan --}}
            <section class="card p-5">
                <label for="notes" class="field-label">Catatan inspeksi <span class="font-normal text-slate-500" x-show="needNotes" x-cloak>(wajib)</span></label>
                <textarea id="notes" name="notes" rows="3" x-model="notes" class="field-control @error('notes') border-red-500 @enderror" placeholder="Jelaskan kondisi alat saat diterima"></textarea>
                @error('notes')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </section>
        </div>

        {{-- Panel kanan --}}
        <aside class="space-y-4 lg:sticky lg:top-4 lg:self-start">
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-slate-900">Pembaruan stok otomatis</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($loan->loanUnits as $lu)
                        <li class="flex items-center justify-between gap-2">
                            <span class="font-medium text-slate-900">{{ $lu->unit->unit_code }}</span>
                            <span class="whitespace-nowrap text-xs font-semibold"
                                  :class="conditions[{{ $lu->item_unit_id }}] === 'baik' ? 'text-emerald-800' : 'text-red-700'"
                                  x-text="{ baik: 'Kembali tersedia', rusak: 'Perlu perbaikan', hilang: 'Nonaktif (hilang)' }[conditions[{{ $lu->item_unit_id }}]]"></span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-600">Unit siap dipinjam setelah ini: <span class="font-bold tabular-nums text-slate-900" x-text="availableNow + baikCount"></span></p>
            </div>

            <div class="card p-5">
                <h3 class="text-sm font-semibold text-slate-900">Denda</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    @if ($lateDays > 0)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Keterlambatan</dt>
                            <dd class="text-right font-medium text-red-700">{{ number_format($lateDays * 10000, 0, ',', '.') }} Rp</dd>
                        </div>
                    @endif
                    @php
                        $damageFines = $loan->damageReports()->sum('fine_amount');
                        $totalFine = $loan->fine_amount + $damageFines;
                    @endphp
                    @if ($damageFines > 0)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Kerusakan</dt>
                            <dd class="text-right font-medium text-red-700">{{ number_format($damageFines, 0, ',', '.') }} Rp</dd>
                        </div>
                    @endif
                    @if ($totalFine > 0)
                        <div class="flex justify-between gap-3 border-t border-slate-100 pt-2">
                            <dt class="font-semibold text-slate-900">Total denda</dt>
                            <dd class="text-right font-bold text-red-700">{{ number_format($totalFine, 0, ',', '.') }} Rp</dd>
                        </div>
                    @endif
                </dl>
                @if ($totalFine > 0 && !$loan->fine_paid)
                    <form method="POST" action="{{ route('admin.denda.bayar', $loan) }}" class="mt-4 space-y-2">
                        @csrf
                        <div>
                            <label for="payment-method-{{ $loan->id }}" class="field-label">Metode pembayaran <span class="font-normal text-slate-500">(opsional)</span></label>
                            <input id="payment-method-{{ $loan->id }}" type="text" name="payment_method" class="field-control" placeholder="mis. Transfer bank, Tunai" value="{{ old('payment_method') }}">
                        </div>
                        <x-button type="submit" variant="secondary" size="lg" class="w-full">Tandai denda lunas</x-button>
                    </form>
                @elseif ($loan->fine_paid)
                    <p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-900">✓ Denda sudah dilunasi.</p>
                @else
                    <p class="mt-3 text-xs text-slate-500">Tidak ada denda.</p>
                @endif
            </div>

            <div class="card p-5">
                <h3 class="text-sm font-semibold text-slate-900">Ringkasan konfirmasi</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Peminjam</dt><dd class="text-right font-medium text-slate-900">{{ $loan->user->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Alat</dt><dd class="text-right font-medium text-slate-900">{{ $loan->item->name }} ×{{ $loan->quantity }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Kondisi akhir</dt>
                        <dd><span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold" :class="worst === 'baik' ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900'" x-text="{ baik: 'Baik', rusak: 'Rusak', hilang: 'Hilang' }[worst]"></span></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Ketepatan</dt><dd class="text-right font-medium {{ $lateDays > 0 ? 'text-red-700' : 'text-slate-900' }}">{{ $lateDays > 0 ? 'Terlambat '.$lateDays.' hari' : 'Tepat waktu' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Petugas penerima</dt><dd class="text-right font-medium text-slate-900">{{ auth()->user()->name }}</dd></div>
                </dl>
                @if ($lateDays > 0)
                    <p class="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-900">Keterlambatan {{ $lateDays }} hari akan dicatat dan menambah riwayat terlambat siswa (saat ini {{ $loan->user->late_count }}×).</p>
                @endif
                <p class="mt-3 text-xs text-red-700" x-show="!notesOk" x-cloak>Isi catatan inspeksi terlebih dahulu.</p>
                <p class="mt-1 text-xs text-red-700" x-show="!mediaOk" x-cloak>Unggah bukti untuk unit yang rusak.</p>
                <x-button type="submit" size="lg" class="mt-4 w-full" x-bind:disabled="!ready || busy || {{ $loan->loanUnits->isEmpty() ? 'true' : 'false' }}">
                    <span x-show="!busy">Selesaikan Pengembalian</span><span x-show="busy" x-cloak>Memproses…</span>
                </x-button>
            </div>
        </aside>
    </form>
</x-app-layout>
