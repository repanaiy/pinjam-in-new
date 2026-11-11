@php $temp = session('temp_password'); @endphp
<x-app-layout title="Siswa" :breadcrumbs="['Siswa']">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen Siswa</h1>
                <p class="mt-1 text-sm text-slate-600">Akun siswa dibuat oleh admin; tidak ada pendaftaran mandiri.</p>
            </div>
            <div class="flex gap-2">
                <x-button :href="route('admin.siswa.import')" variant="secondary">Import CSV/Excel</x-button>
                <x-button :href="route('admin.siswa.create')">+ Tambah Siswa</x-button>
            </div>
        </div>
    </x-slot>

    @if ($temp)
        <x-alert variant="success" class="mb-5" title="Password sementara dibuat">
            <p>Berikan kepada <strong>{{ $temp['name'] }}</strong> (username <code>{{ $temp['username'] }}</code>). Password ini hanya tampil sekali:</p>
            <p class="mt-2 flex items-center gap-2" x-data="{ copied: false }">
                <code class="rounded bg-white px-2.5 py-1 text-base font-bold tracking-wide text-slate-900 ring-1 ring-emerald-200">{{ $temp['password'] }}</code>
                <button type="button" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-emerald-900 hover:bg-emerald-100" @click="navigator.clipboard.writeText('{{ $temp['password'] }}'); copied = true" x-text="copied ? 'Tersalin' : 'Salin'"></button>
            </p>
        </x-alert>
    @endif

    <form method="GET" action="{{ route('admin.siswa.index') }}" class="card mb-5 grid gap-3 p-4 sm:grid-cols-4" role="search">
        <div class="sm:col-span-2"><label for="q" class="field-label">Cari</label><input id="q" type="search" name="q" value="{{ $q }}" placeholder="Nama, NIS, atau username" class="field-control"></div>
        <div><label for="kelas" class="field-label">Kelas</label><select id="kelas" name="kelas" class="field-control"><option value="">Semua</option>@foreach ($kelasList as $k)<option value="{{ $k }}" @selected($kelas === $k)>{{ $k }}</option>@endforeach</select></div>
        <div><label for="status" class="field-label">Status</label><select id="status" name="status" class="field-control"><option value="">Semua</option><option value="aktif" @selected($status === 'aktif')>Aktif</option><option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option></select></div>
        <div class="flex gap-2 sm:col-span-4"><x-button type="submit" size="sm">Terapkan</x-button>@if ($q !== '' || $kelas !== '' || $status)<x-button :href="route('admin.siswa.index')" variant="ghost" size="sm">Reset</x-button>@endif</div>
    </form>

    @if ($students->isEmpty())
        <x-empty-state :title="$q !== '' || $kelas !== '' || $status ? 'Tidak ada siswa yang cocok' : 'Belum ada siswa'" text="Tambahkan siswa satu per satu atau impor dari CSV/Excel.">
            <x-button :href="route('admin.siswa.import')" size="sm">Import siswa</x-button>
        </x-empty-state>
    @else
        <x-table>
            <x-slot name="head">
                <tr>
                    <th scope="col" class="px-4 py-3">Siswa</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3">NIS • Kelas</th>
                    <th scope="col" class="px-4 py-3">Terlambat</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                </tr>
            </x-slot>
            @foreach ($students as $s)
                <tr class="align-middle">
                    <td class="px-4 py-3"><p class="font-semibold text-slate-900">{{ $s->name }}</p><p class="text-xs text-slate-500">{{ $s->username }}{{ $s->phone ? ' • '.$s->phone : '' }}</p></td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $s->nis }} • {{ $s->kelas }}</td>
                    <td class="px-4 py-3 tabular-nums">{{ $s->late_count }}×</td>
                    <td class="px-4 py-3"><x-status-badge :tone="$s->is_active ? 'green' : 'gray'" :label="$s->is_active ? 'Aktif' : 'Nonaktif'"/></td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap justify-end gap-1.5">
                            <x-button :href="route('admin.siswa.edit', $s->id)" variant="secondary" size="sm">Ubah</x-button>
                            <form method="POST" action="{{ route('admin.siswa.toggle', $s->id) }}">@csrf<x-button type="submit" variant="ghost" size="sm">{{ $s->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</x-button></form>
                            <form method="POST" action="{{ route('admin.siswa.reset', $s->id) }}" onsubmit="return confirm('Reset password {{ $s->name }}?')">@csrf<x-button type="submit" variant="ghost" size="sm">Reset password</x-button></form>
                            <form method="POST" action="{{ route('admin.siswa.destroy', $s->id) }}" onsubmit="return confirm('Hapus akun {{ $s->name }}? Riwayat transaksi tetap tersimpan.')">@csrf @method('DELETE')<x-button type="submit" variant="ghost" size="sm" class="!text-red-700 hover:!bg-red-50">Hapus</x-button></form>
                        </div>
                    </td>
                </tr>
            @endforeach
            <x-slot name="foot"><x-pagination :paginator="$students"/></x-slot>
        </x-table>
    @endif
</x-app-layout>
