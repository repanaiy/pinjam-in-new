<x-app-layout title="Audit Log" :breadcrumbs="['Audit Log']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Audit Log</h1>
        <p class="mt-1 text-sm text-slate-600">Catatan seluruh perubahan status dan tindakan. Hanya bisa dibaca.</p>
    </x-slot>

    <form method="GET" action="{{ route('admin.audit.index') }}" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        @if ($loanFilter)<input type="hidden" name="loan" value="{{ $loanFilter->id }}">@endif
        <div class="lg:col-span-2"><label for="q" class="field-label">Kode pengajuan</label><input id="q" name="q" value="{{ $q }}" placeholder="REQ-2026-…" class="field-control"></div>
        <div><label for="aktor" class="field-label">Aktor</label><input id="aktor" name="aktor" value="{{ $actor }}" placeholder="Nama/username" class="field-control"></div>
        <div>
            <label for="aksi" class="field-label">Aksi</label>
            <select id="aksi" name="aksi" class="field-control"><option value="">Semua</option>@foreach ($actions as $a)<option value="{{ $a }}" @selected($action === $a)>{{ $a }}</option>@endforeach</select>
        </div>
        <div><label for="dari" class="field-label">Dari</label><input id="dari" type="date" name="dari" value="{{ $from }}" class="field-control"></div>
        <div><label for="sampai" class="field-label">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ $to }}" class="field-control"></div>
        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-6">
            <x-button type="submit" size="sm">Terapkan</x-button>
            <x-button :href="route('admin.audit.index')" variant="ghost" size="sm">Reset</x-button>
            @if ($loanFilter)<x-status-badge tone="navy" :label="'Pengajuan '.$loanFilter->request_code"/>@endif
        </div>
    </form>

    @if ($logs->isEmpty())
        <x-empty-state title="Tidak ada catatan" text="Belum ada aktivitas yang cocok dengan filter."/>
    @else
        <x-table>
            <x-slot name="head">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3">Waktu</th>
                    <th scope="col" class="px-4 py-3">Aktor</th>
                    <th scope="col" class="px-4 py-3">Aksi</th>
                    <th scope="col" class="px-4 py-3">Keterangan</th>
                    <th scope="col" class="px-4 py-3">Pengajuan</th>
                </tr>
            </x-slot>
            @foreach ($logs as $log)
                <tr class="align-top">
                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $log->created_at->translatedFormat('j M Y, H.i') }}</td>
                    <td class="px-4 py-3">{{ $log->actor?->name ?? 'Sistem' }}</td>
                    <td class="px-4 py-3"><x-status-badge tone="gray" :label="$log->action"/></td>
                    <td class="max-w-sm px-4 py-3 text-slate-700">{{ $log->description ?? '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-3">@if ($log->loan)<a href="{{ route('admin.verifikasi.show', $log->loan) }}" class="font-semibold text-emerald-700 hover:underline">{{ $log->loan->request_code }}</a>@else — @endif</td>
                </tr>
            @endforeach
            <x-slot name="foot"><x-pagination :paginator="$logs"/></x-slot>
        </x-table>
    @endif
</x-app-layout>
