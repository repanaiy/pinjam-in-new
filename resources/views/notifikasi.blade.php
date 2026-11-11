@php
    $user = auth()->user();
@endphp
<x-app-layout title="Notifikasi" :breadcrumbs="['Notifikasi']">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Notifikasi</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $unread ? $unread.' belum dibaca' : 'Semua sudah dibaca' }}</p>
            </div>
            @if ($unread)
                <form method="POST" action="{{ route('notifikasi.read-all') }}">@csrf<x-button type="submit" variant="secondary" size="sm">Tandai semua dibaca</x-button></form>
            @endif
        </div>
    </x-slot>

    @if ($notifications->isEmpty())
        <x-empty-state title="Belum ada notifikasi" text="Pembaruan pengajuan dan pengingat jatuh tempo akan muncul di sini."/>
    @else
        <ul class="space-y-2">
            @foreach ($notifications as $n)
                @php
                    $d = $n->data;
                    $loanId = $d['loan_id'] ?? null;
                    $link = $loanId
                        ? ($user->isAdmin()
                            ? (\Illuminate\Support\Facades\Route::has('admin.verifikasi.show') ? route('admin.verifikasi.show', $loanId) : null)
                            : route('pengajuan.show', $loanId))
                        : null;
                @endphp
                <li class="card flex items-start gap-3 p-4 {{ $n->read_at ? '' : 'border-emerald-300 bg-emerald-50/50' }}">
                    <span class="mt-1.5 size-2.5 shrink-0 rounded-full {{ $n->read_at ? 'bg-slate-300' : 'bg-emerald-600' }}" aria-hidden="true"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900">{{ $d['title'] ?? 'Notifikasi' }} @unless ($n->read_at)<span class="sr-only">(belum dibaca)</span>@endunless</p>
                        <p class="text-sm text-slate-700">{{ $d['message'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $n->created_at->translatedFormat('j M Y, H.i') }} WIB @if ($link)&bull; <a href="{{ $link }}" class="font-semibold text-emerald-700 hover:underline">Lihat pengajuan</a>@endif</p>
                    </div>
                    @unless ($n->read_at)
                        <form method="POST" action="{{ route('notifikasi.read', $n->id) }}">@csrf<button type="submit" class="whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Tandai dibaca</button></form>
                    @endunless
                </li>
            @endforeach
        </ul>
        <x-pagination :paginator="$notifications" class="mt-6"/>
    @endif
</x-app-layout>
