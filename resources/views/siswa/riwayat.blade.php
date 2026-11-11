<x-app-layout title="Riwayat" :breadcrumbs="['Riwayat']">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Riwayat Peminjaman</h1>
                <p class="mt-1 text-sm text-slate-600">Semua transaksi dan catatan dari admin.</p>
            </div>
            <form method="GET" action="{{ route('riwayat.index') }}" class="flex items-center gap-2">
                <label for="status" class="text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" onchange="this.form.submit()" class="field-control w-auto py-2 pr-8">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>@endforeach
                </select>
            </form>
        </div>
    </x-slot>

    @if ($loans->isEmpty())
        <x-empty-state :title="$status ? 'Tidak ada transaksi berstatus ini' : 'Belum ada riwayat'" text="Transaksi akan muncul di sini setelah kamu mengajukan peminjaman.">
            @if ($status)<x-button :href="route('riwayat.index')" variant="secondary" size="sm">Reset filter</x-button>@endif
        </x-empty-state>
    @else
        <x-table>
            <x-slot name="head">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3">Kode</th>
                    <th scope="col" class="px-4 py-3">Alat</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3">Periode</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Catatan admin</th>
                </tr>
            </x-slot>
            @foreach ($loans as $loan)
                <tr class="align-top">
                    <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('pengajuan.show', $loan) }}" class="font-semibold text-emerald-700 hover:underline">{{ $loan->request_code }}</a></td>
                    <td class="px-4 py-3">{{ $loan->item->name }} <span class="text-slate-500">×{{ $loan->quantity }}</span></td>
                    <td class="whitespace-nowrap px-4 py-3">{{ $loan->periodLabel() }}</td>
                    <td class="px-4 py-3"><x-status-badge :status="$loan->status"/></td>
                    <td class="max-w-xs px-4 py-3 text-slate-600">{{ $loan->reject_reason ?? $loan->admin_note ?? '—' }}</td>
                </tr>
            @endforeach
            <x-slot name="foot"><x-pagination :paginator="$loans"/></x-slot>
        </x-table>
    @endif
</x-app-layout>
