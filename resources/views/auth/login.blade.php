<x-guest-layout title="Masuk">
    <div class="card p-6 shadow-sm sm:p-8"
         x-data="{ role: @js(old('role', 'siswa')), show: false, busy: false }">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke akun</h2>
        <p class="mt-1 text-sm text-slate-600">Pilih peran lalu masukkan akun sekolah Anda.</p>

        @if (session('status'))
            <x-alert variant="success" class="mt-5">Password berhasil diatur ulang. Silakan masuk dengan password baru.</x-alert>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5" @submit="busy = true" novalidate>
            @csrf
            <input type="hidden" name="role" :value="role">

            <fieldset>
                <legend class="field-label">Masuk sebagai</legend>
                <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1" role="radiogroup" aria-label="Peran">
                    @foreach (['siswa' => 'Siswa', 'admin' => 'Admin'] as $value => $label)
                        <button type="button" role="radio" :aria-checked="role === '{{ $value }}'" @click="role = '{{ $value }}'"
                                :class="role === '{{ $value }}' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:text-slate-900'"
                                class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors">{{ $label }}</button>
                    @endforeach
                </div>
                @error('role')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </fieldset>

            <x-input name="username" label="Username" autocomplete="username" autofocus required
                     :value="old('username')" placeholder="mis. raka.pratama"/>

            <div>
                <label for="password" class="field-label">Password</label>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="current-password" required
                           class="field-control pr-16 @error('password') border-red-500 @enderror" placeholder="Masukkan password">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-slate-600 hover:text-slate-900"
                            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'" x-text="show ? 'Sembunyi' : 'Lihat'"></button>
                </div>
                @error('password')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-between gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                    <span class="whitespace-nowrap">Ingat saya</span>
                </label>
                <a href="{{ route('password.request') }}" class="whitespace-nowrap text-sm font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">Lupa password?</a>
            </div>

            <x-button type="submit" size="lg" class="w-full" x-bind:disabled="busy">
                <span x-show="!busy">Masuk</span><span x-show="busy" x-cloak>Memproses…</span>
            </x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">Butuh bantuan? Hubungi petugas sarpras.</p>
    </div>
</x-guest-layout>
