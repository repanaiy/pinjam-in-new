<x-app-layout title="Akun" :breadcrumbs="['Akun']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Akun</h1>
        <p class="mt-1 text-sm text-slate-600">Kelola data profil dan password.</p>
    </x-slot>

    <div class="grid max-w-4xl gap-6 lg:grid-cols-2">
        <section class="card p-5" aria-labelledby="profil-title">
            <h2 id="profil-title" class="text-base font-semibold text-slate-900">Data profil</h2>
            <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf @method('PATCH')
                <div class="flex items-center gap-4">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="Avatar {{ $user->name }}" class="size-16 rounded-full object-cover">
                    @else
                        <span class="flex size-16 items-center justify-center rounded-full bg-emerald-700 text-xl font-bold text-white" aria-hidden="true">{{ $user->initials() }}</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <label for="avatar" class="field-label">Avatar</label>
                        <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                        @error('avatar')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
                <x-input name="name" label="Nama lengkap" :value="old('name', $user->name)" required/>
                <x-input name="phone" label="No. HP" type="tel" :value="old('phone', $user->phone)" placeholder="08xxxxxxxxxx"/>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><p class="field-label">Username</p><p class="rounded-lg bg-slate-50 px-3 py-2 text-slate-700">{{ $user->username }}</p></div>
                    <div><p class="field-label">{{ $user->isAdmin() ? 'Kode petugas' : 'NIS • Kelas' }}</p><p class="rounded-lg bg-slate-50 px-3 py-2 text-slate-700">{{ $user->isAdmin() ? ($user->employee_code ?? '—') : ($user->nis ?? '—').' • '.($user->kelas ?? '—') }}</p></div>
                </div>
                <x-button type="submit">Simpan profil</x-button>
            </form>
        </section>

        <section class="card p-5" aria-labelledby="pw-title">
            <h2 id="pw-title" class="text-base font-semibold text-slate-900">Ganti password</h2>
            <form method="POST" action="{{ route('profil.password') }}" class="mt-4 space-y-4">
                @csrf @method('PUT')
                <x-input name="current_password" type="password" label="Password saat ini" autocomplete="current-password" required/>
                <x-input name="password" type="password" label="Password baru" autocomplete="new-password" required hint="Minimal 8 karakter."/>
                <x-input name="password_confirmation" type="password" label="Ulangi password baru" autocomplete="new-password" required/>
                <x-button type="submit">Ganti password</x-button>
            </form>
        </section>
    </div>
</x-app-layout>
