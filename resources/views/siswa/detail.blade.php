@php
    $photos = $item->galleryItems();
    $videoType = $item->videoType();
    $qty = (int) old('quantity', $source->quantity ?? 1);
    $purpose = old('purpose', $source->purpose ?? '');
    $resubmitId = old('resubmitted_from_id', $source->id ?? null);
@endphp
<x-app-layout :title="$item->name" :breadcrumbs="['Katalog', $item->name]">
    <a href="{{ route('katalog.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">&lsaquo; Kembali ke katalog</a>

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- ===== Kiri: info alat ===== --}}
        <div class="space-y-6 lg:col-span-3">
            <div class="card overflow-hidden" x-data="{ i: 0, photos: @js($photos) }">
                <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                    <template x-if="photos.length">
                        <div x-data="hoverZoom(2.2)" class="size-full cursor-zoom-in" role="button" tabindex="0" aria-label="Perbesar foto"
                             @pointermove="move($event)" @pointerleave="out()"
                             @click="$dispatch('open-viewer', { items: photos, index: i })"
                             @keydown.enter.prevent="$dispatch('open-viewer', { items: photos, index: i })"
                             @keydown.space.prevent="$dispatch('open-viewer', { items: photos, index: i })">
                            <img :src="photos[i].src" :alt="photos[i].alt" class="size-full object-cover transition-transform duration-150 ease-out" :style="style" draggable="false">
                        </div>
                    </template>
                    <template x-if="!photos.length">
                        <div class="flex size-full items-center justify-center text-slate-400"><svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h2.4l1.2-1.8h7.8L17.1 6h2.4A1.5 1.5 0 0 1 21 7.5v10A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z"/><circle cx="12" cy="12.5" r="3.5"/></svg></div>
                    </template>
                    <x-status-badge :tone="$ready > 0 ? 'green' : 'gray'" :label="$ready > 0 ? $ready.' tersedia hari ini' : 'Habis hari ini'" class="pointer-events-none absolute left-3 top-3 shadow-sm"/>
                    <span x-show="photos.length" class="pointer-events-none absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-full bg-slate-900/70 px-2.5 py-1 text-xs font-medium text-white">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M8 11h6M11 8v6m10 7-4.5-4.5"/></svg>
                        Klik untuk memperbesar
                    </span>
                </div>
                <div x-show="photos.length > 1" x-cloak class="flex gap-2 overflow-x-auto p-3" role="group" aria-label="Pilih foto">
                    <template x-for="(p, idx) in photos" :key="idx">
                        <button type="button" @click="i = idx" :aria-current="i === idx" :class="i === idx ? 'ring-2 ring-emerald-700' : 'ring-1 ring-slate-200 opacity-80 hover:opacity-100'" class="size-16 shrink-0 overflow-hidden rounded-lg" :aria-label="'Foto ' + (idx + 1)"><img :src="p.src" alt="" loading="lazy" class="size-full object-cover"></button>
                    </template>
                </div>
            </div>

            <div class="card p-5">
                <p class="text-xs font-medium text-emerald-700">{{ $item->category->name }}</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $item->name }}</h1>
                <p class="mt-1 text-sm text-slate-500"><span class="whitespace-nowrap">{{ $item->code }}</span> &bull; <span class="whitespace-nowrap">{{ $ready }}/{{ $total }} tersedia</span> &bull; <span class="whitespace-nowrap">Maks. {{ $item->max_loan_days }} hari</span></p>
                @if ($item->description)<p class="mt-4 text-sm leading-relaxed text-slate-700">{{ $item->description }}</p>@endif
                @if ($item->location)<p class="mt-3 text-sm text-slate-600"><span class="font-semibold text-slate-800">Lokasi pengambilan:</span> {{ $item->location }}</p>@endif
            </div>

            @if ($videoType)
                @if ($videoType === 'link')
                    <div class="card flex items-center gap-4 p-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-white" aria-hidden="true"><svg class="size-6" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7L8 5Z"/></svg></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">{{ $item->video_title ?? 'Video panduan' }}</p>
                            <p class="text-xs text-slate-500">Video panduan @if ($item->video_duration)&bull; {{ $item->video_duration }}@endif</p>
                        </div>
                        <x-button :href="$item->videoLink()" target="_blank" rel="noopener noreferrer" variant="secondary" size="sm">Tonton</x-button>
                    </div>
                @else
                    {{-- Pemutar dimuat setelah diklik (tidak ada pihak ketiga yang dimuat sebelum itu). --}}
                    <div class="card overflow-hidden" x-data="{ play: false }">
                        <div class="relative aspect-video bg-slate-900">
                            <template x-if="!play">
                                <button type="button" @click="play = true" class="group absolute inset-0 flex items-center justify-center bg-cover bg-center"
                                        @if (! empty($photos)) style="background-image: url('{{ $photos[0]['src'] }}')" @endif aria-label="Putar video panduan">
                                    <span class="absolute inset-0 bg-slate-900/55 transition-colors group-hover:bg-slate-900/40"></span>
                                    <span class="relative flex size-16 items-center justify-center rounded-full bg-white/95 text-slate-900 shadow-lg transition-transform group-hover:scale-105"><svg class="ml-1 size-8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg></span>
                                </button>
                            </template>
                            <template x-if="play">
                                @if ($videoType === 'file')
                                    <video controls autoplay playsinline class="size-full bg-black" src="{{ $item->videoEmbedUrl() }}">Peramban Anda tidak mendukung pemutar video.</video>
                                @else
                                    <iframe src="{{ $item->videoEmbedUrl() }}" title="{{ $item->video_title ?? 'Video panduan '.$item->name }}" class="size-full border-0" loading="lazy"
                                            allow="accelerometer; autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                @endif
                            </template>
                        </div>
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div class="min-w-0"><p class="text-sm font-semibold text-slate-900">{{ $item->video_title ?? 'Video panduan' }}</p><p class="text-xs text-slate-500">Video panduan @if ($item->video_duration)&bull; {{ $item->video_duration }}@endif</p></div>
                            <a href="{{ $item->videoLink() }}" target="_blank" rel="noopener noreferrer" class="whitespace-nowrap text-xs font-semibold text-emerald-700 hover:underline">Buka sumber</a>
                        </div>
                    </div>
                @endif
            @endif

            @if (! empty($item->specs))
                <div class="card overflow-hidden">
                    <h2 class="border-b border-slate-200 px-5 py-3 text-sm font-semibold text-slate-900">Spesifikasi</h2>
                    <dl class="divide-y divide-slate-100 text-sm">
                        @foreach ($item->specs as $label => $value)
                            <div class="grid grid-cols-3 gap-3 px-5 py-2.5"><dt class="font-medium text-slate-600">{{ $label }}</dt><dd class="col-span-2 text-slate-900">{{ is_array($value) ? implode(', ', $value) : $value }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if ($item->package_contents)
                <div class="card p-5"><h2 class="text-sm font-semibold text-slate-900">Isi paket</h2><p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $item->package_contents }}</p></div>
            @endif
        </div>

        {{-- ===== Kanan: panel & form ===== --}}
        <div class="space-y-6 lg:col-span-2">
            <aside class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                <h2 class="text-sm font-semibold text-emerald-950">Pinjam dengan bertanggung jawab</h2>
                <ul class="mt-3 space-y-2 text-sm text-emerald-950">
                    @foreach (['Bawa kartu pelajar asli saat mengambil alat.', 'Kembalikan tepat waktu sesuai tanggal yang dipilih.', 'Jaga kondisi alat dan lengkapi seluruh isi paket.', 'Laporkan kerusakan atau kehilangan kepada petugas segera.'] as $rule)
                        <li class="flex gap-2"><svg class="mt-0.5 size-4 shrink-0 text-emerald-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg><span>{{ $rule }}</span></li>
                    @endforeach
                </ul>
            </aside>

            <section class="card p-5 lg:sticky lg:top-4" aria-labelledby="form-title">
                <h2 id="form-title" class="text-lg font-bold text-slate-900">Atur peminjamanmu</h2>

                @if ($source)
                    <x-alert variant="info" class="mt-4" title="Ajukan ulang">Data dari pengajuan {{ $source->request_code }} sudah disalin. Pilih tanggal baru; kartu pelajar lama dipakai kembali bila tidak diganti.</x-alert>
                @endif

                @if (session()->has('unavailable'))
                    <x-alert variant="error" class="mt-4" title="Alat Tidak Tersedia">
                        Pilih tanggal lain untuk melanjutkan. Pengajuan belum disimpan; unggah ulang berkasmu setelah memilih tanggal.
                    </x-alert>
                @elseif ($errors->any())
                    <x-alert variant="error" class="mt-4" title="Periksa kembali isianmu">Ada isian yang belum sesuai, lihat penanda merah di bawah.</x-alert>
                @endif

                @if ($lateWarning)
                    <x-alert variant="warning" class="mt-4" title="Riwayat keterlambatan">Kamu sudah terlambat {{ auth()->user()->late_count }} kali. Pastikan mengembalikan alat tepat waktu.</x-alert>
                @endif

                @if ($ready <= 0 && ! session()->has('unavailable'))
                    <x-alert variant="warning" class="mt-4">Stok hari ini habis, tetapi kamu masih bisa mengajukan untuk tanggal yang tersedia.</x-alert>
                @endif

                <form method="POST" action="{{ route('pengajuan.store', $item) }}" enctype="multipart/form-data" class="mt-5 space-y-5"
                      x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    @if ($resubmitId)<input type="hidden" name="resubmitted_from_id" value="{{ $resubmitId }}">@endif
                    @error('resubmitted_from_id')<p class="text-xs font-medium text-red-700">{{ $message }}</p>@enderror

                    <div>
                        <span class="field-label" id="qty-label">Jumlah unit</span>
                        <div x-data="qtyStepper({ value: {{ $qty }}, max: {{ $item->max_qty_per_loan }} })" class="inline-flex items-center rounded-lg border border-slate-300" role="group" aria-labelledby="qty-label">
                            <input type="hidden" name="quantity" :value="value">
                            <button type="button" @click="dec()" :disabled="value <= min" class="px-3.5 py-2 text-lg font-semibold text-slate-700 hover:bg-slate-50 disabled:text-slate-300" aria-label="Kurangi">&minus;</button>
                            <span class="min-w-[2.5rem] text-center text-sm font-semibold tabular-nums" x-text="value" aria-live="polite"></span>
                            <button type="button" @click="inc()" :disabled="value >= max" class="px-3.5 py-2 text-lg font-semibold text-slate-700 hover:bg-slate-50 disabled:text-slate-300" aria-label="Tambah">+</button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Maksimal {{ $item->max_qty_per_loan }} unit per peminjaman.</p>
                        @error('quantity')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <x-date-range-picker :config="$calendarConfig"/>
                    <p class="-mt-3 text-xs text-slate-500">Maksimal {{ $item->max_loan_days }} hari. Akhir pekan, hari libur, dan tanggal penuh tidak bisa dipilih.</p>

                    <x-textarea name="purpose" label="Keperluan" rows="3" placeholder="Jelaskan untuk apa alat dipakai. Minimal 10 karakter.">{{ $purpose }}</x-textarea>

                    <x-upload-dropzone name="student_card" label="Kartu pelajar" :required="! $source" hint="JPG, PNG, atau PDF • maks. 5 MB"/>
                    <x-upload-dropzone name="attachment" label="Dokumen pendukung (opsional)" hint="Mis. surat kegiatan • JPG, PNG, atau PDF • maks. 5 MB"/>

                    <div>
                        <label class="flex gap-3 text-sm text-slate-700">
                            <input type="checkbox" name="agreement_accepted" value="1" @checked(old('agreement_accepted')) class="mt-0.5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                            <span>Saya bertanggung jawab atas alat yang dipinjam dan bersedia mengganti bila rusak atau hilang.</span>
                        </label>
                        @error('agreement_accepted')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <x-button type="submit" size="lg" class="w-full" x-bind:disabled="busy">
                        <span x-show="!busy">Ajukan Peminjaman</span><span x-show="busy" x-cloak>Mengirim…</span>
                    </x-button>
                    <p class="text-center text-xs text-slate-500">Ditinjau admin maksimal {{ $reviewSla }} hari kerja. Tanpa biaya.</p>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
