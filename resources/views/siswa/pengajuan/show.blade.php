@php
    use App\Enums\LoanStatus as S;
    $tones = [
        'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
        'green' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
        'navy' => 'border-slate-700 bg-slate-800 text-white',
        'deepgreen' => 'border-emerald-800 bg-emerald-800 text-white',
        'red' => 'border-red-200 bg-red-50 text-red-950',
        'gray' => 'border-slate-200 bg-slate-100 text-slate-800',
    ];
    $banner = $tones[$loan->status->tone()] ?? $tones['gray'];
    $showSchedule = in_array($loan->status, [S::Approved, S::ReadyPickup], true) && $loan->pickup_date;
    $canCancel = auth()->user()->can('cancel', $loan);
    $canExtend = in_array($loan->status, [S::Borrowed, S::Overdue], true) && $loan->canExtend();
    $canResubmit = auth()->user()->can('resubmit', $loan) && $loan->resubmissions->isEmpty();
    $proofUrl = \Illuminate\Support\Facades\Route::has('pengajuan.bukti') ? route('pengajuan.bukti', $loan) : null;
@endphp
<x-app-layout :title="$loan->request_code" :breadcrumbs="['Pengajuan Saya', $loan->request_code]">
    <a href="{{ route('pengajuan.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">&lsaquo; Pengajuan saya</a>

    {{-- Banner status --}}
    <div class="rounded-xl border p-5 {{ $banner }}" role="status">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-lg font-bold">{{ $loan->status->label() }}</p>
            <span class="whitespace-nowrap text-sm opacity-90">{{ $loan->request_code }}</span>
        </div>
        <p class="mt-1 text-sm opacity-90">{{ $loan->status->description() }}</p>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Kartu alat --}}
            <div class="card flex gap-4 p-4">
                <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100 text-slate-400">
                    @if ($loan->item->coverUrl())<img src="{{ $loan->item->coverUrl() }}" alt="" class="size-full object-cover">@else<svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20 7.5 12 3 4 7.5m16 0v9L12 21m8-13.5-8 4.5m0 9-8-4.5v-9m8 4.5v9M4 7.5l8 4.5"/></svg>@endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-emerald-700">{{ $loan->item->category->name }}</p>
                    <p class="font-semibold text-slate-900">{{ $loan->item->name }}</p>
                    <p class="text-sm text-slate-600">{{ $loan->item->code }} &bull; {{ $loan->quantity }} unit</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $loan->periodLabel() }} ({{ $loan->durationDays() }} hari)</p>
                </div>
            </div>

            {{-- Stepper 5 langkah --}}
            <div class="card p-5">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Lacak peminjaman</h2>
                <x-stepper :steps="$steps"/>
            </div>

            {{-- Jadwal pengambilan --}}
            @if ($showSchedule)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Jadwal pengambilan</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">Tanggal</dt><dd class="font-semibold text-slate-900">{{ $loan->pickup_date->translatedFormat('l, j M Y') }}</dd></div>
                        <div><dt class="text-slate-500">Jam</dt><dd class="font-semibold text-slate-900">{{ substr((string) $loan->pickup_time_start, 0, 5) }}@if ($loan->pickup_time_end) – {{ substr((string) $loan->pickup_time_end, 0, 5) }}@endif WIB</dd></div>
                        <div><dt class="text-slate-500">Lokasi</dt><dd class="font-semibold text-slate-900">{{ $loan->pickup_location ?? $loan->item->location }}</dd></div>
                        <div><dt class="text-slate-500">Petugas</dt><dd class="font-semibold text-slate-900">{{ $loan->reviewer?->name ?? '—' }}</dd></div>
                    </dl>
                    @if ($loan->admin_note)<p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-semibold">Catatan petugas:</span> {{ $loan->admin_note }}</p>@endif
                    <x-alert variant="warning" class="mt-4">Bawa kartu pelajar asli saat mengambil alat.</x-alert>
                </div>
            @endif

            {{-- Ditolak --}}
            @if ($loan->status === S::Rejected)
                <div class="card border-red-200 p-5">
                    <h2 class="text-sm font-semibold text-red-900">Alasan penolakan</h2>
                    <p class="mt-2 text-sm text-slate-800">{{ $loan->reject_reason ?? 'Tidak ada alasan yang dicantumkan.' }}</p>
                    @if ($canResubmit)
                        <x-button :href="route('katalog.show', ['item' => $loan->item_id, 'ulang' => $loan->id])" class="mt-4">Ajukan Ulang</x-button>
                    @elseif ($loan->resubmissions->isNotEmpty())
                        <p class="mt-3 text-sm text-slate-600">Sudah diajukan ulang: <a class="font-semibold text-emerald-700 hover:underline" href="{{ route('pengajuan.show', $loan->resubmissions->first()) }}">{{ $loan->resubmissions->first()->request_code }}</a></p>
                    @endif
                </div>
            @endif

            @if ($loan->status === S::Cancelled && $loan->cancel_reason)
                <div class="card p-5"><h2 class="text-sm font-semibold text-slate-900">Alasan pembatalan</h2><p class="mt-2 text-sm text-slate-700">{{ $loan->cancel_reason }}</p></div>
            @endif

            {{-- Bukti pengembalian --}}
            @php $returnInspection = $loan->inspections->first(fn ($i) => $i->type->value === 'return'); @endphp
            @if ($returnInspection && $loan->returned_at)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Bukti pengembalian</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">Dikembalikan</dt><dd class="font-semibold text-slate-900">{{ $loan->returned_at->translatedFormat('j M Y, H.i') }} WIB</dd></div>
                        <div><dt class="text-slate-500">Kondisi akhir</dt><dd class="font-semibold text-slate-900">{{ $returnInspection->final_condition?->label() }}</dd></div>
                        <div><dt class="text-slate-500">Petugas penerima</dt><dd class="font-semibold text-slate-900">{{ $returnInspection->inspector?->name ?? '—' }}</dd></div>
                        <div><dt class="text-slate-500">Ketepatan waktu</dt><dd class="font-semibold {{ $loan->is_late ? 'text-red-700' : 'text-emerald-800' }}">{{ $loan->is_late ? 'Terlambat '.$loan->late_days.' hari' : 'Tepat waktu' }}</dd></div>
                    </dl>
                    @if ($returnInspection->notes)<p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-semibold">Catatan petugas:</span> {{ $returnInspection->notes }}</p>@endif
                    <p class="mt-3 text-sm font-semibold text-emerald-800">Transaksi selesai.</p>
                </div>
            @endif

            {{-- Foto kondisi alat (serah terima & pengembalian) --}}
            @php
                $mediaItems = [];
                foreach ($loan->inspections->sortBy(fn ($i) => $i->type->value === 'handover' ? 0 : 1) as $insp) {
                    foreach ($insp->media as $m) {
                        $label = $insp->type->value === 'handover' ? 'Saat diserahkan' : 'Saat dikembalikan';
                        $mediaItems[] = ['src' => route('berkas.media', $m), 'alt' => $label, 'caption' => $label.' — '.$loan->item->name, 'type' => $m->media_type === 'video' ? 'video' : 'image', 'label' => $label];
                    }
                }
            @endphp
            @if ($mediaItems)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Foto kondisi alat</h2>
                    <p class="mt-1 text-xs text-slate-500">Dokumentasi petugas. Klik untuk memperbesar.</p>
                    <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($mediaItems as $k => $mi)
                            <li>
                                <button type="button" class="group relative block aspect-square w-full cursor-zoom-in overflow-hidden rounded-lg border border-slate-200 bg-slate-800" aria-label="Perbesar: {{ $mi['label'] }}"
                                        @click="$dispatch('open-viewer', { items: @js($mediaItems), index: {{ $k }} })">
                                    @if ($mi['type'] === 'video')
                                        <span class="flex size-full items-center justify-center text-white"><svg class="size-10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg></span>
                                    @else
                                        <img src="{{ $mi['src'] }}" alt="{{ $mi['alt'] }}" loading="lazy" class="size-full object-cover">
                                    @endif
                                </button>
                                <p class="mt-1 text-xs text-slate-600">{{ $mi['label'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Detail pengajuan --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">Detail pengajuan</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Keperluan</dt><dd class="whitespace-pre-line text-slate-900">{{ $loan->purpose }}</dd></div>
                    <div><dt class="text-slate-500">Diajukan</dt><dd class="text-slate-900">{{ $loan->created_at->translatedFormat('j M Y, H.i') }} WIB</dd></div>
                    <div class="flex flex-wrap gap-x-6 gap-y-1">
                        <div><dt class="sr-only">Kartu pelajar</dt><dd>
                            @if (in_array(strtolower(pathinfo($loan->student_card_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true))
                                <a href="{{ route('berkas.kartu', $loan) }}" target="_blank" rel="noopener" class="font-semibold text-emerald-700 hover:underline"
                                   @click.prevent="$dispatch('open-viewer', { items: @js([['src' => route('berkas.kartu', $loan), 'alt' => 'Kartu pelajar', 'caption' => 'Kartu pelajar — '.$loan->request_code, 'type' => 'image']]), index: 0 })">Lihat kartu pelajar</a>
                            @else
                                <a href="{{ route('berkas.kartu', $loan) }}" target="_blank" rel="noopener" class="font-semibold text-emerald-700 hover:underline">Buka kartu pelajar (PDF)</a>
                            @endif
                        </dd></div>
                        @if ($loan->attachment_path)<div><dt class="sr-only">Dokumen pendukung</dt><dd><a href="{{ route('berkas.lampiran', $loan) }}" target="_blank" rel="noopener" class="font-semibold text-emerald-700 hover:underline">Lihat dokumen pendukung</a></dd></div>@endif
                    </div>
                    @if ($loan->is_late)<div><dt class="text-slate-500">Keterlambatan</dt><dd class="font-semibold text-red-700">{{ $loan->late_days }} hari (Denda: Rp {{ number_format($loan->fine_amount, 0, ',', '.') }})</dd></div>@endif
                    @if ($loan->extensions_count > 0 || $loan->extended_until)<div><dt class="text-slate-500">Perpanjangan</dt><dd class="font-semibold text-slate-900">Sudah diperpanjang {{ $loan->extensions_count }}x (Batas baru: {{ $loan->end_date->translatedFormat('j M Y') }})</dd></div>@endif
                    @if ($loan->totalFineAmount() > 0)
                        <div>
                            <dt class="text-slate-500">Total Denda</dt>
                            <dd class="font-semibold text-red-700">Rp {{ number_format($loan->totalFineAmount(), 0, ',', '.') }}
                                <span class="text-xs text-slate-500 font-normal">
                                    (Keterlambatan: Rp {{ number_format($loan->fine_amount, 0, ',', '.') }}
                                    @php $damageSum = $loan->damageReports->sum('fine_amount'); @endphp
                                    @if($damageSum > 0), Kerusakan: Rp {{ number_format($damageSum, 0, ',', '.') }}@endif)
                                </span>
                                @if ($loan->fine_paid)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800">✓ Lunas</span>
                                @else
                                    <span class="ml-2 inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Belum lunas</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Aksi --}}
            @if ($canExtend)
                <div class="card p-5 border-emerald-200 bg-emerald-50/50">
                    <h2 class="text-sm font-semibold text-emerald-950">Perpanjang peminjaman</h2>
                    <p class="mt-1 text-sm text-slate-600">Perpanjang waktu peminjaman alat maksimal 3 hari.</p>
                    <form method="POST" action="{{ route('pengajuan.extend', $loan) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="days" class="block text-xs font-semibold text-slate-700">Durasi Perpanjangan (Hari)</label>
                            <select id="days" name="days" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="1">1 Hari</option>
                                <option value="2">2 Hari</option>
                                <option value="3">3 Hari</option>
                            </select>
                        </div>
                        <x-button type="submit" variant="primary" class="w-full">Perpanjang Sekarang</x-button>
                    </form>
                </div>
            @endif

            @if ($canCancel)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Pilih aksi</h2>
                    <p class="mt-1 text-sm text-slate-600">Batalkan bila tidak jadi meminjam, atau lanjutkan menunggu proses berikutnya.</p>
                    <div class="mt-4 flex flex-col gap-2">
                        <x-button :href="route('pengajuan.index')" variant="primary">Lanjutkan</x-button>
                        <x-button variant="secondary" @click="$dispatch('open-modal', 'batal')" class="!border-red-300 !text-red-700 hover:!bg-red-50">Batalkan pengajuan</x-button>
                    </div>
                </div>
            @endif

            @if ($proofUrl && in_array($loan->status, [S::Approved, S::ReadyPickup, S::Borrowed, S::Overdue, S::Returned, S::ReturnedDamaged, S::ReturnedLost], true))
                <x-button :href="$proofUrl" variant="secondary" class="w-full">Unduh bukti peminjaman</x-button>
            @endif

            {{-- Aktivitas terbaru --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-900">Aktivitas terbaru</h2>
                @if ($loan->activityLogs->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Belum ada aktivitas.</p>
                @else
                    <ol class="mt-3 space-y-3">
                        @foreach ($loan->activityLogs->take(6) as $log)
                            <li class="flex gap-3 text-sm">
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-emerald-600" aria-hidden="true"></span>
                                <div class="min-w-0">
                                    <p class="text-slate-900">{{ $log->description ?? ucfirst(str_replace('_', ' ', $log->action)) }}</p>
                                    <p class="text-xs text-slate-500">{{ $log->created_at->translatedFormat('j M Y, H.i') }} &bull; {{ $log->actor?->name ?? 'Sistem' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </div>

    @if ($canCancel)
        <x-modal name="batal" title="Batalkan pengajuan?" maxWidth="md">
            <form method="POST" action="{{ route('pengajuan.cancel', $loan) }}" class="space-y-4">
                @csrf
                <p class="text-sm text-slate-600">Reservasi alat akan dilepas dan tidak bisa dikembalikan. Kamu tetap bisa mengajukan lagi nanti.</p>
                <x-textarea name="cancel_reason" label="Alasan (opsional)" rows="2" maxlength="255"/>
                <div class="flex justify-end gap-2">
                    <x-button variant="ghost" @click="$dispatch('close-modal', 'batal')">Kembali</x-button>
                    <x-button type="submit" variant="destructive">Ya, batalkan</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
