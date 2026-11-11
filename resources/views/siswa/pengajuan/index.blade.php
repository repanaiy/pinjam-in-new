<x-app-layout title="Pengajuan Saya" :breadcrumbs="['Pengajuan Saya']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pengajuan Saya</h1>
        <p class="mt-1 text-sm text-slate-600">Peminjaman yang sedang berjalan dan pengajuan yang ditolak.</p>
    </x-slot>

    @if ($loans->isEmpty())
        <x-empty-state title="Belum ada pengajuan aktif" text="Pilih alat di katalog lalu ajukan peminjaman.">
            <x-button :href="route('katalog.index')" size="sm">Buka katalog</x-button>
        </x-empty-state>
    @else
        <div class="space-y-3">
            @foreach ($loans as $loan)
                <a href="{{ route('pengajuan.show', $loan) }}" class="card flex flex-col gap-3 p-4 transition-colors hover:border-emerald-600 sm:flex-row sm:items-center">
                    <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100 text-slate-400">
                        @if ($loan->item->coverUrl())<img src="{{ $loan->item->coverUrl() }}" alt="" class="size-full object-cover">@else<svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20 7.5 12 3 4 7.5m16 0v9L12 21m8-13.5-8 4.5m0 9-8-4.5v-9m8 4.5v9M4 7.5l8 4.5"/></svg>@endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-500"><span class="whitespace-nowrap">{{ $loan->request_code }}</span></p>
                        <p class="font-semibold text-slate-900">{{ $loan->item->name }} <span class="font-normal text-slate-500">×{{ $loan->quantity }}</span></p>
                        <p class="text-sm text-slate-600">{{ $loan->periodLabel() }}</p>
                    </div>
                    <div class="flex items-center gap-3 sm:flex-col sm:items-end">
                        <x-status-badge :status="$loan->status"/>
                        @if ($loan->status === \App\Enums\LoanStatus::Rejected)<span class="whitespace-nowrap text-xs font-semibold text-emerald-700">Lihat alasan &rsaquo;</span>@endif
                    </div>
                </a>
            @endforeach
        </div>
        <x-pagination :paginator="$loans" class="mt-6"/>
    @endif
</x-app-layout>
