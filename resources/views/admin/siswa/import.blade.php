<x-app-layout title="Import Siswa" :breadcrumbs="['Siswa', 'Import']">
    <a href="{{ route('admin.siswa.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">&lsaquo; Kembali ke daftar siswa</a>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Import Siswa</h1>
        <p class="mt-1 text-sm text-slate-600">Unggah CSV atau Excel dengan kolom <strong>nama, nis, kelas, no_hp</strong> (no_hp opsional).</p>
    </x-slot>

    <div class="grid max-w-5xl gap-6">
        <section class="card p-5">
            <form method="POST" action="{{ route('admin.siswa.import.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <div class="min-w-0 flex-1">
                    <label for="file" class="field-label">Berkas (CSV, XLSX, XLS • maks. 2 MB • maks. 500 baris)</label>
                    <input id="file" type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    @error('file')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                </div>
                <x-button type="submit" x-bind:disabled="busy"><span x-show="!busy">Import</span><span x-show="busy" x-cloak>Memproses…</span></x-button>
                <x-button :href="route('admin.siswa.template')" variant="secondary">Unduh template</x-button>
            </form>
            <p class="mt-3 text-xs text-slate-500">Username dibuat dari nama dan password acak dibuat per siswa. Baris yang tidak valid dilewati dan dilaporkan di bawah.</p>
        </section>

        @if ($result)
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Baris dibaca" :value="$result['total']"/>
                <x-stat-card label="Berhasil dibuat" :value="count($result['created'])"/>
                <x-stat-card label="Ditolak" :value="count($result['errors'])" tone="red"/>
            </div>

            @if ($result['created'])
                <section class="card p-5" x-data="{
                    rows: @js($result['created']),
                    csv() {
                        const q = v => '&quot;' + String(v).replace(/&quot;/g, '&quot;&quot;') + '&quot;';
                        const lines = ['nama,username,password,nis,kelas', ...this.rows.map(r => [r.name, r.username, r.password, r.nis, r.kelas].map(q).join(','))];
                        const url = URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/csv' }));
                        const a = Object.assign(document.createElement('a'), { href: url, download: 'kredensial-siswa.csv' });
                        a.click(); URL.revokeObjectURL(url);
                    },
                }">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900">Akun yang dibuat</h2>
                        <x-button type="button" variant="secondary" size="sm" @click="csv()">Unduh kredensial (CSV)</x-button>
                    </div>
                    <x-alert variant="warning" class="mt-3" title="Simpan sekarang">Password hanya tampil di halaman ini. Unduh atau salin sebelum meninggalkan halaman.</x-alert>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold text-slate-600"><tr><th scope="col" class="px-3 py-2">Baris</th><th scope="col" class="px-3 py-2">Nama</th><th scope="col" class="px-3 py-2">NIS</th><th scope="col" class="px-3 py-2">Kelas</th><th scope="col" class="px-3 py-2">Username</th><th scope="col" class="px-3 py-2">Password</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($result['created'] as $r)
                                    <tr><td class="px-3 py-2 tabular-nums">{{ $r['row'] }}</td><td class="px-3 py-2">{{ $r['name'] }}</td><td class="px-3 py-2">{{ $r['nis'] }}</td><td class="px-3 py-2">{{ $r['kelas'] }}</td><td class="px-3 py-2">{{ $r['username'] }}</td><td class="px-3 py-2 font-mono">{{ $r['password'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if ($result['errors'])
                <section class="card border-red-200 p-5">
                    <h2 class="text-base font-semibold text-red-900">Baris yang ditolak</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold text-slate-600"><tr><th scope="col" class="px-3 py-2">Baris</th><th scope="col" class="px-3 py-2">Nama</th><th scope="col" class="px-3 py-2">NIS</th><th scope="col" class="px-3 py-2">Alasan</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($result['errors'] as $e)
                                    <tr class="align-top"><td class="px-3 py-2 tabular-nums">{{ $e['row'] }}</td><td class="px-3 py-2">{{ $e['name'] ?: '—' }}</td><td class="px-3 py-2">{{ $e['nis'] ?: '—' }}</td><td class="px-3 py-2 text-red-800">{{ implode(' ', $e['messages']) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-app-layout>
