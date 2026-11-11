@php $editing = $student->exists; @endphp
<x-app-layout :title="$editing ? 'Ubah Siswa' : 'Tambah Siswa'" :breadcrumbs="['Siswa', $editing ? $student->name : 'Tambah']">
    <a href="{{ route('admin.siswa.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">&lsaquo; Kembali ke daftar siswa</a>
    <x-slot name="header"><h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $editing ? 'Ubah Siswa' : 'Tambah Siswa' }}</h1></x-slot>

    <form method="POST" action="{{ $editing ? route('admin.siswa.update', $student->id) : route('admin.siswa.store') }}" class="card grid max-w-3xl gap-4 p-5 sm:grid-cols-2"
          x-data="{ pw: '', gen() { const c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; this.pw = Array.from(crypto.getRandomValues(new Uint32Array(10)), n => c[n % c.length]).join(''); } }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="sm:col-span-2"><x-input name="name" label="Nama lengkap" :value="old('name', $student->name)" required/></div>
        <x-input name="username" label="Username" :value="old('username', $student->username)" required hint="Huruf kecil, angka, titik, minus."/>
        <x-input name="email" type="email" label="Email (opsional)" :value="old('email', $student->email)" hint="Diperlukan bila siswa ingin reset password sendiri."/>
        <x-input name="nis" label="NIS" :value="old('nis', $student->nis)" required/>
        <x-input name="kelas" label="Kelas" :value="old('kelas', $student->kelas)" required placeholder="mis. XII IPA 2"/>
        <div class="sm:col-span-2"><x-input name="phone" type="tel" label="No. HP" :value="old('phone', $student->phone)" placeholder="08xxxxxxxxxx"/></div>

        <div class="sm:col-span-2">
            <label for="password" class="field-label">{{ $editing ? 'Password baru (kosongkan bila tidak diganti)' : 'Password awal' }}</label>
            <div class="flex gap-2">
                <input id="password" name="password" type="text" x-model="pw" autocomplete="new-password" class="field-control @error('password') border-red-500 @enderror" @if (! $editing) required @endif>
                <button type="button" @click="gen()" class="whitespace-nowrap rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Buat acak</button>
            </div>
            @error('password')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-3 text-sm text-slate-800 sm:col-span-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $student->is_active ?? true)) class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
            Akun aktif (siswa bisa masuk dan mengajukan peminjaman)
        </label>

        <div class="flex gap-2 sm:col-span-2"><x-button type="submit">{{ $editing ? 'Simpan perubahan' : 'Tambah siswa' }}</x-button><x-button :href="route('admin.siswa.index')" variant="ghost">Batal</x-button></div>
    </form>
</x-app-layout>
