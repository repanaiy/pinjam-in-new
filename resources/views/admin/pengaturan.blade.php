@php $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu']; @endphp
<x-app-layout title="Pengaturan" :breadcrumbs="['Pengaturan']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pengaturan</h1>
        <p class="mt-1 text-sm text-slate-600">Jam layanan, aturan peminjaman, tahun ajaran, dan hari libur.</p>
    </x-slot>

    <div class="grid max-w-4xl gap-6">
        <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="grid gap-6">
            @csrf @method('PUT')

            <section class="card p-5" aria-labelledby="s-layanan">
                <h2 id="s-layanan" class="text-base font-semibold text-slate-900">Layanan</h2>
                <fieldset class="mt-3">
                    <legend class="field-label">Hari layanan</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($dayNames as $n => $label)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="service_days[]" value="{{ $n }}" class="peer sr-only" @checked(in_array($n, old('service_days', $serviceDays)))>
                                <span class="block whitespace-nowrap rounded-full border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 peer-checked:border-emerald-700 peer-checked:bg-emerald-700 peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-emerald-600">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('service_days')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                </fieldset>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-input name="service_hour_start" type="time" label="Jam buka" :value="old('service_hour_start', $values['service_hour_start'])" required/>
                    <x-input name="service_hour_end" type="time" label="Jam tutup" :value="old('service_hour_end', $values['service_hour_end'])" required/>
                </div>
            </section>

            <section class="card grid gap-4 p-5 sm:grid-cols-2" aria-labelledby="s-aturan">
                <h2 id="s-aturan" class="text-base font-semibold text-slate-900 sm:col-span-2">Aturan peminjaman</h2>
                <x-input name="max_loan_days" type="number" min="1" max="30" label="Maks. hari pinjam (default alat baru)" :value="old('max_loan_days', $values['max_loan_days'])" required/>
                <x-input name="review_sla_workdays" type="number" min="1" max="10" label="Batas peninjauan admin (hari kerja)" :value="old('review_sla_workdays', $values['review_sla_workdays'])" required hint="Lewat batas ini admin diingatkan."/>
                <x-input name="pickup_grace_workdays" type="number" min="0" max="10" label="Toleransi pengambilan (hari kerja)" :value="old('pickup_grace_workdays', $values['pickup_grace_workdays'])" required hint="Setelah jadwal + toleransi, pengajuan kedaluwarsa."/>
                <x-input name="late_warning_threshold" type="number" min="0" max="20" label="Ambang peringatan keterlambatan" :value="old('late_warning_threshold', $values['late_warning_threshold'])" required hint="0 = nonaktif."/>
            </section>

            <section class="card grid gap-4 p-5 sm:grid-cols-2" aria-labelledby="s-tahun">
                <h2 id="s-tahun" class="text-base font-semibold text-slate-900 sm:col-span-2">Tahun ajaran</h2>
                <x-input name="academic_year" label="Tahun ajaran" :value="old('academic_year', $values['academic_year'])" placeholder="2026/2027" required/>
                <x-input name="semester_label" label="Label semester" :value="old('semester_label', $values['semester_label'])" placeholder="Semester ganjil 2026" required/>
            </section>

            <div><x-button type="submit">Simpan pengaturan</x-button></div>
        </form>

        <section class="card p-5" aria-labelledby="s-libur">
            <h2 id="s-libur" class="text-base font-semibold text-slate-900">Hari libur</h2>
            <p class="mt-1 text-sm text-slate-600">Tanggal libur tidak bisa dipilih sebagai tanggal pinjam, kembali, atau pengambilan.</p>
            <form method="POST" action="{{ route('admin.pengaturan.libur.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_2fr_auto] sm:items-end">
                @csrf
                <div><label for="h_date" class="field-label">Tanggal</label><input id="h_date" type="date" name="date" value="{{ old('date') }}" class="field-control @error('date') border-red-500 @enderror" required>@error('date')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="h_name" class="field-label">Nama</label><input id="h_name" name="name" value="{{ old('name') }}" class="field-control @error('name') border-red-500 @enderror" placeholder="mis. Libur semester" required>@error('name')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror</div>
                <x-button type="submit">+ Tambah</x-button>
            </form>

            @if ($holidays->isEmpty())
                <p class="mt-4 text-sm text-slate-500">Belum ada hari libur terdaftar.</p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($holidays as $h)
                        <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                            <span><span class="whitespace-nowrap font-semibold text-slate-900">{{ $h->date->translatedFormat('l, j M Y') }}</span> <span class="text-slate-600">— {{ $h->name }}</span></span>
                            <form method="POST" action="{{ route('admin.pengaturan.libur.destroy', $h) }}" onsubmit="return confirm('Hapus hari libur ini?')">@csrf @method('DELETE')<x-button type="submit" variant="ghost" size="sm" class="!text-red-700 hover:!bg-red-50">Hapus</x-button></form>
                        </li>
                    @endforeach
                </ul>
                <x-pagination :paginator="$holidays" class="mt-4"/>
            @endif
        </section>
    </div>
</x-app-layout>
