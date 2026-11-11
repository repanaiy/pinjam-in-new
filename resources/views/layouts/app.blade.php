@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $title = $attributes->get('title', 'PINJAM-IN');
    $breadcrumbs = $attributes->get('breadcrumbs', []);
    $has = fn (string $r) => \Illuminate\Support\Facades\Route::has($r);
    $to = fn (string $r) => $has($r) ? route($r) : null;

    $nav = $isAdmin ? [
        ['Dashboard', 'admin.dashboard', 'M3 12 12 4l9 8M5 10v10h14V10'],
        ['Verifikasi', 'admin.verifikasi.index', 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Inventaris', 'admin.inventaris.index', 'M20 7.5 12 3 4 7.5m16 0v9L12 21m8-13.5-8 4.5m0 9-8-4.5v-9m8 4.5v9M4 7.5l8 4.5'],
        ['Pengembalian', 'admin.pengembalian.index', 'M3 10h11a5 5 0 0 1 0 10H8m-5-10 4-4m-4 4 4 4'],
        ['Laporan', 'admin.laporan.index', 'M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z'],
        ['Siswa', 'admin.siswa.index', 'M16 11a4 4 0 1 0-8 0m-3 9a7 7 0 0 1 14 0'],
        ['Audit Log', 'admin.audit.index', 'M9 5h6M9 9h6m-6 4h3M7 3h10a2 2 0 0 1 2 2v14l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 0 1 2-2Z'],
        ['Pengaturan', 'admin.pengaturan.index', 'M10.3 3.3a1 1 0 0 1 1.4 0l.7.7a1 1 0 0 0 .9.3l1-.2a1 1 0 0 1 1.2.8l.2 1a1 1 0 0 0 .6.7l.9.4a1 1 0 0 1 .5 1.3l-.4.9a1 1 0 0 0 0 1l.4.9a1 1 0 0 1-.5 1.3l-.9.4a1 1 0 0 0-.6.7l-.2 1a1 1 0 0 1-1.2.8l-1-.2a1 1 0 0 0-.9.3l-.7.7a1 1 0 0 1-1.4 0l-.7-.7a1 1 0 0 0-.9-.3l-1 .2a1 1 0 0 1-1.2-.8l-.2-1a1 1 0 0 0-.6-.7l-.9-.4a1 1 0 0 1-.5-1.3l.4-.9a1 1 0 0 0 0-1l-.4-.9a1 1 0 0 1 .5-1.3l.9-.4a1 1 0 0 0 .6-.7l.2-1a1 1 0 0 1 1.2-.8l1 .2a1 1 0 0 0 .9-.3l.7-.7ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z'],
    ] : [
        ['Katalog', 'katalog.index', 'M3 7.5A1.5 1.5 0 0 1 4.5 6h2.4l1.2-1.8h7.8L17.1 6h2.4A1.5 1.5 0 0 1 21 7.5v10A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z'],
        ['Pengajuan Saya', 'pengajuan.index', 'M9 5h6M9 9h6m-6 4h3M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z'],
        ['Riwayat', 'riwayat.index', 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Notifikasi', 'notifikasi.index', 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0'],
    ];

    // Bottom nav siswa (mobile)
    $bottom = [
        ['Katalog', 'katalog.index', 'M3 7.5A1.5 1.5 0 0 1 4.5 6h2.4l1.2-1.8h7.8L17.1 6h2.4A1.5 1.5 0 0 1 21 7.5v10A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z'],
        ['Pengajuan', 'pengajuan.index', 'M9 5h6M9 9h6m-6 4h3M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z'],
        ['Riwayat', 'riwayat.index', 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Akun', 'profil.edit', 'M16 11a4 4 0 1 0-8 0m-3 9a7 7 0 0 1 14 0'],
    ];

    $unread = $user->unreadNotifications()->count();
    $semester = \App\Models\Setting::get('semester_label', 'Semester ganjil 2026');
    $year = \App\Models\Setting::get('academic_year', '2026/2027');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — PINJAM-IN</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo_pinjam_in_kotak_perkakas_hijau_senada.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans" x-data="{ menu: false }">
<a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[200] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-slate-900 focus:shadow">Lewati ke konten</a>

{{-- ===== Sidebar (desktop) & drawer (mobile admin) ===== --}}
<aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-slate-800 lg:flex" aria-label="Menu utama">
    @include('layouts.partials.sidebar', compact('nav', 'to', 'user', 'year', 'unread'))
</aside>

@if ($isAdmin)
    <div x-cloak x-show="menu" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" @keydown.escape.window="menu = false">
        <div class="absolute inset-0 bg-slate-900/60" @click="menu = false"></div>
        <aside class="absolute inset-y-0 left-0 flex w-72 max-w-[85%] flex-col bg-slate-800" aria-label="Menu utama">
            @include('layouts.partials.sidebar', compact('nav', 'to', 'user', 'year', 'unread'))
        </aside>
    </div>
@endif

<div class="lg:pl-64">
    {{-- Header mobile: gelap, tampil untuk siswa & admin --}}
    <header class="sticky top-0 z-30 flex items-center justify-between gap-3 bg-slate-800 px-4 py-2 text-white lg:hidden">
        <div class="flex items-center gap-2">
            @if ($isAdmin)
                <button type="button" @click="menu = true" class="rounded-lg p-1.5 text-slate-100 hover:bg-slate-700" aria-label="Buka menu"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg></button>
            @endif
            <img src="{{ asset('images/logo_pinjam_in_kotak_perkakas_hijau_senada.svg') }}" alt="Logo" class="h-8 w-8">
            <span class="text-sm font-bold tracking-tight">PINJAM-IN</span>
        </div>
        <a href="{{ $to('notifikasi.index') ?? '#' }}" class="relative rounded-lg p-1.5 text-slate-100 hover:bg-slate-700">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
            @if ($unread)<span class="absolute right-0 top-0 flex min-w-[1rem] items-center justify-center rounded-full bg-emerald-500 px-1 text-[9px] font-bold leading-3 text-slate-900">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
        </a>
    </header>

    {{-- Topbar desktop --}}
    <div class="hidden items-center justify-between gap-4 border-b border-slate-200 bg-white px-8 py-3.5 lg:flex">
        <nav aria-label="Breadcrumb" class="min-w-0 text-sm text-slate-600">
            <ol class="flex items-center gap-1.5">
                <li class="whitespace-nowrap">PINJAM-IN</li>
                @foreach ($breadcrumbs as $crumb)
                    <li aria-hidden="true" class="text-slate-400">/</li>
                    <li class="whitespace-nowrap {{ $loop->last ? 'font-semibold text-slate-900' : '' }}" @if($loop->last) aria-current="page" @endif>{{ $crumb }}</li>
                @endforeach
            </ol>
        </nav>
        <div class="flex items-center gap-3">
            <span class="whitespace-nowrap rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-900 ring-1 ring-inset ring-emerald-200">{{ $semester }}</span>
            <a href="{{ $to('notifikasi.index') ?? '#' }}" class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Notifikasi{{ $unread ? ', '.$unread.' belum dibaca' : '' }}">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
                @if ($unread)<span class="absolute right-0.5 top-0.5 flex min-w-[1.1rem] items-center justify-center rounded-full bg-emerald-600 px-1 text-[10px] font-bold leading-4 text-white">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
            </a>
        </div>
    </div>

    <main id="konten" class="px-4 py-4 {{ $isAdmin ? '' : 'pb-20' }} lg:px-6 lg:pb-6">
        @isset($header)
            <div class="mb-4">{{ $header }}</div>
        @endisset
        {{ $slot }}
    </main>

    <footer class="px-4 pb-24 text-center text-xs text-slate-500 lg:px-8 lg:pb-6 lg:text-left">SMKN 5 Surakarta &bull; Sistem internal v1.0</footer>
</div>

{{-- Bottom nav siswa (mobile) --}}
@unless ($isAdmin)
    <nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-4 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Navigasi bawah">
        @foreach ($bottom as [$label, $route, $icon])
            @php $active = $has($route) && request()->routeIs(explode('.', $route)[0].'.*'); @endphp
            <a href="{{ $to($route) ?? '#' }}" @if ($active) aria-current="page" @endif
               class="flex flex-col items-center gap-1 py-2.5 text-xs font-semibold {{ $active ? 'text-emerald-700' : 'text-slate-600' }} {{ $has($route) ? '' : 'opacity-50' }}">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                <span class="whitespace-nowrap">{{ $label }}</span>
            </a>
        @endforeach
    </nav>
@endunless

<x-image-viewer/>
<x-toast/>
</body>
</html>
