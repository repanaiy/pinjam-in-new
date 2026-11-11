<x-guest-layout title="Lupa Password">
    <div class="card p-6 shadow-sm sm:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Lupa password</h2>
        <p class="mt-1 text-sm text-slate-600">Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang password.</p>

        @if (session('status'))
            <x-alert variant="success" class="mt-5">Tautan pengaturan ulang password telah dikirim ke email Anda.</x-alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <x-input name="email" type="email" label="Email" autocomplete="email" autofocus required :value="old('email')" placeholder="nama@sman-nusantara.sch.id"/>
            <x-button type="submit" size="lg" class="w-full">Kirim tautan reset</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:underline">Kembali ke halaman masuk</a>
        </p>
        <p class="mt-2 text-center text-xs text-slate-500">Tidak punya email terdaftar? Hubungi petugas sarpras untuk reset password.</p>
    </div>
</x-guest-layout>
