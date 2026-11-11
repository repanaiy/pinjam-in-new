<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $attributes->get('title', 'Masuk') }} — PINJAM-IN</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo_pinjam_in_kotak_perkakas_hijau_senada.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans bg-slate-50" x-data>
    <main class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl border border-slate-100">
            <div class="flex justify-center mb-6">
                <x-logo class="h-16 w-16" />
            </div>
            <p class="mb-6 text-center text-sm text-slate-600">Peminjaman alat, tercatat dari awal hingga kembali.</p>
            {{ $slot }}
            <p class="mt-8 text-center text-xs text-slate-400">SMKN 5 Surakarta &bull; Sistem internal v1.0</p>
        </div>
    </main>
    <x-toast/>
</body>
</html>
