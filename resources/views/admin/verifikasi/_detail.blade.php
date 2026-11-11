@php
    use App\Enums\LoanStatus as S;
    $u = $loan->user;
    $check = $detail['check'];
    $startMode = $errors->has('reject_reason') ? 'reject' : 'approve';
@endphp
<div class="space-y-5">
    {{-- Header --}}
    <div class="card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="whitespace-nowrap text-xs text-slate-500">{{ $loan->request_code }} &bull; diajukan {{ $loan->created_at->translatedFormat('j M Y, H.i') }} WIB</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $loan->item->name }} <span class="font-normal text-slate-500">×{{ $loan->quantity }}</span></h2>
                <p class="text-sm text-slate-600">{{ $loan->periodLabel() }} ({{ $loan->durationDays() }} hari)</p>
            </div>
            <x-status-badge :status="$loan->status"/>
        </div>
        <dl class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm">
            <div><dt class="text-slate-500">Keperluan</dt><dd class="whitespace-pre-line text-slate-900">{{ $loan->purpose }}</dd></div>
        </dl>
    </div>

    {{-- Siswa --}}
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900">Data peminjam</h3>
            <div class="flex flex-wrap gap-2">
                <x-status-badge :tone="$u->is_active ? 'green' : 'red'" :label="$u->is_active ? 'Siswa aktif' : 'Siswa nonaktif'"/>
                @if ($u->late_count > 0)<x-status-badge tone="amber" :label="'Terlambat '.$u->late_count.'×'"/>@endif
            </div>
        </div>
        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Nama</dt><dd class="font-semibold text-slate-900">{{ $u->name }}</dd></div>
            <div><dt class="text-slate-500">NIS • Kelas</dt><dd class="font-semibold text-slate-900">{{ $u->nis ?? '—' }} • {{ $u->kelas ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">No. HP</dt><dd class="text-slate-900">{{ $u->phone ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Username</dt><dd class="text-slate-900">{{ $u->username }}</dd></div>
        </dl>
    </div>

    {{-- Dokumen --}}
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-900">Dokumen</h3>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <p class="mb-1.5 text-xs font-medium text-slate-500">Kartu pelajar</p>
                @if ($detail['cardIsImage'])
                    <button type="button" class="group relative block w-full cursor-zoom-in overflow-hidden rounded-lg border border-slate-200 bg-slate-50" aria-label="Perbesar kartu pelajar {{ $u->name }}"
                            @click="$dispatch('open-viewer', { items: @js([['src' => route('berkas.kartu', $loan), 'alt' => 'Kartu pelajar '.$u->name, 'caption' => 'Kartu pelajar — '.$u->name, 'type' => 'image']]), index: 0 })">
                        <img src="{{ route('berkas.kartu', $loan) }}" alt="Kartu pelajar {{ $u->name }}" loading="lazy" class="max-h-56 w-full object-contain">
                        <span class="pointer-events-none absolute bottom-2 right-2 rounded-full bg-slate-900/70 px-2.5 py-1 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">Klik untuk memperbesar</span>
                    </button>
                @else
                    <x-button :href="route('berkas.kartu', $loan)" target="_blank" rel="noopener" variant="secondary" size="sm">Buka kartu pelajar (PDF)</x-button>
                @endif
            </div>
            <div>
                <p class="mb-1.5 text-xs font-medium text-slate-500">Dokumen pendukung</p>
                @if ($loan->attachment_path)
                    <x-button :href="route('berkas.lampiran', $loan)" target="_blank" rel="noopener" variant="secondary" size="sm">Buka dokumen</x-button>
                @else
                    <p class="text-sm text-slate-500">Tidak ada.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Cek otomatis --}}
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900">Hasil cek otomatis</h3>
            <form method="POST" action="{{ route('admin.verifikasi.check', $loan) }}">@csrf<x-button type="submit" variant="secondary" size="sm">Periksa ketersediaan &amp; kelayakan</x-button></form>
        </div>
        @if ($check['ok'])
            <x-alert variant="success" class="mt-3" title="Identitas sesuai dan jadwal tidak bentrok">{{ $check['free'] }} unit tersedia pada periode ini; pengajuan ini meminta {{ $loan->quantity }}.</x-alert>
        @else
            <x-alert variant="warning" class="mt-3" title="Perlu diperhatikan">
                <ul class="list-disc space-y-0.5 pl-4">@foreach ($check['issues'] as $issue)<li>{{ $issue }}</li>@endforeach</ul>
            </x-alert>
        @endif
    </div>

    {{-- Keputusan --}}
    @if ($detail['canDecide'])
        @if ($detail['locked'])
            <x-alert variant="warning" title="Sedang ditinjau admin lain">
                {{ $loan->reviewer?->name ?? 'Admin lain' }} memegang pengajuan ini. Kamu bisa mengambil alih bila diperlukan.
                <form method="POST" action="{{ route('admin.verifikasi.takeover', $loan) }}" class="mt-3">@csrf<x-button type="submit" variant="secondary" size="sm">Ambil alih tinjauan</x-button></form>
            </x-alert>
        @else
            <div class="card p-5" x-data="{ mode: '{{ $startMode }}', reason: @js(old('reject_reason', '')) }">
                <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Keputusan">
                    <button type="button" role="tab" :aria-selected="mode === 'approve'" @click="mode = 'approve'" :class="mode === 'approve' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600'" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold">Setujui</button>
                    <button type="button" role="tab" :aria-selected="mode === 'reject'" @click="mode = 'reject'" :class="mode === 'reject' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600'" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold">Tolak</button>
                </div>

                <form x-show="mode === 'approve'" method="POST" action="{{ route('admin.verifikasi.approve', $loan) }}" class="mt-5 space-y-4" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <p class="text-sm text-slate-600">Atur jadwal pengambilan. Jam layanan {{ $detail['hourStart'] }}–{{ $detail['hourEnd'] }} WIB.</p>
                    <x-input name="pickup_date" type="date" label="Tanggal pengambilan" :value="old('pickup_date', $detail['defaultPickup'])" :min="today()->toDateString()" :max="$loan->end_date->toDateString()" required/>
                    <div class="grid grid-cols-2 gap-3">
                        <x-input name="pickup_time_start" type="time" label="Jam mulai" :value="old('pickup_time_start', '09:00')" :min="$detail['hourStart']" :max="$detail['hourEnd']" required/>
                        <x-input name="pickup_time_end" type="time" label="Jam selesai" :value="old('pickup_time_end', '11:00')" :min="$detail['hourStart']" :max="$detail['hourEnd']" required/>
                    </div>
                    <x-input name="pickup_location" label="Lokasi pengambilan" :value="old('pickup_location', $loan->item->location ?? 'Ruang Sarpras')" required/>
                    <x-textarea name="admin_note" label="Catatan untuk siswa (opsional)" rows="2">{{ old('admin_note') }}</x-textarea>
                    <x-button type="submit" class="w-full" x-bind:disabled="busy"><span x-show="!busy">Setujui pengajuan</span><span x-show="busy" x-cloak>Memproses…</span></x-button>
                </form>

                <form x-show="mode === 'reject'" x-cloak method="POST" action="{{ route('admin.verifikasi.reject', $loan) }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <span class="field-label">Alasan cepat</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['Jadwal bentrok' => 'Jadwal bentrok dengan kegiatan sekolah pada tanggal tersebut.', 'Dokumen kurang' => 'Dokumen kurang atau tidak terbaca. Unggah kartu pelajar yang jelas.', 'Di luar ketentuan' => 'Pengajuan di luar ketentuan peminjaman sarpras.'] as $chip => $text)
                                <button type="button" @click="reason = @js($text)" class="whitespace-nowrap rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ $chip }}</button>
                            @endforeach
                        </div>
                    </div>
                    <x-textarea name="reject_reason" label="Alasan penolakan" rows="3" x-model="reason" placeholder="Jelaskan alasan agar siswa bisa memperbaiki pengajuan"></x-textarea>
                    <x-button type="submit" variant="destructive" class="w-full">Tolak pengajuan</x-button>
                </form>
            </div>
        @endif
    @endif

    @if ($loan->status === S::Approved || $loan->status === S::ReadyPickup)
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900">Jadwal pengambilan</h3>
            <p class="mt-2 text-sm text-slate-700">
                {{ $loan->pickup_date?->translatedFormat('l, j M Y') }}, {{ substr((string) $loan->pickup_time_start, 0, 5) }}–{{ substr((string) $loan->pickup_time_end, 0, 5) }} WIB &bull; {{ $loan->pickup_location }}
            </p>
            @if ($loan->admin_note)<p class="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-semibold">Catatan:</span> {{ $loan->admin_note }}</p>@endif
            <div class="mt-4">
                @if ($loan->status === S::Approved)
                    <form method="POST" action="{{ route('admin.verifikasi.ready', $loan) }}">@csrf<x-button type="submit">Tandai siap diambil</x-button></form>
                @else
                    <x-button :href="route('admin.verifikasi.handover.create', $loan)">Mulai serah terima</x-button>
                @endif
            </div>
        </div>
    @endif

    @if ($loan->status === S::Rejected)
        <div class="card border-red-200 p-5"><h3 class="text-sm font-semibold text-red-900">Alasan penolakan</h3><p class="mt-2 text-sm text-slate-800">{{ $loan->reject_reason }}</p></div>
    @endif

    @if ($loan->loanUnits->isNotEmpty())
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900">Unit yang diserahkan</h3>
            <ul class="mt-2 flex flex-wrap gap-2">@foreach ($loan->loanUnits as $lu)<li><x-status-badge tone="navy" :label="$lu->unit->unit_code"/></li>@endforeach</ul>
        </div>
    @endif

    {{-- Aktivitas --}}
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Aktivitas</h3>
            <a href="{{ route('admin.audit.index', ['loan' => $loan->id]) }}" class="whitespace-nowrap text-xs font-semibold text-emerald-700 hover:underline">Lihat audit log &rsaquo;</a>
        </div>
        <ol class="mt-3 space-y-3">
            @forelse ($loan->activityLogs->take(6) as $log)
                <li class="flex gap-3 text-sm"><span class="mt-1.5 size-2 shrink-0 rounded-full bg-emerald-600" aria-hidden="true"></span>
                    <div class="min-w-0"><p class="text-slate-900">{{ $log->description ?? $log->action }}</p><p class="text-xs text-slate-500">{{ $log->created_at->translatedFormat('j M Y, H.i') }} &bull; {{ $log->actor?->name ?? 'Sistem' }}</p></div>
                </li>
            @empty
                <li class="text-sm text-slate-500">Belum ada aktivitas.</li>
            @endforelse
        </ol>
    </div>
</div>
