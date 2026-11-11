<x-guest-layout title="Atur Ulang Password">
    <div class="card p-6 shadow-sm sm:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Atur ulang password</h2>
        <p class="mt-1 text-sm text-slate-600">Buat password baru untuk akun Anda.</p>

        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-input name="email" type="email" label="Email" autocomplete="email" required :value="old('email', $request->email)"/>
            <x-input name="password" type="password" label="Password baru" autocomplete="new-password" required hint="Minimal 8 karakter."/>
            <x-input name="password_confirmation" type="password" label="Ulangi password baru" autocomplete="new-password" required/>
            <x-button type="submit" size="lg" class="w-full">Simpan password baru</x-button>
        </form>
    </div>
</x-guest-layout>
