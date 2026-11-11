<x-app-layout title="Laporan Kerusakan" :breadcrumbs="['Inventaris', 'Laporan kerusakan']">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventaris</h1>
        <p class="mt-1 text-sm text-slate-600">Tindak lanjut unit rusak atau hilang dari pengembalian.</p>
    </x-slot>
    @include('admin.inventaris._tabs')

    <div class="mb-5 flex flex-wrap gap-2" role="group" aria-label="Filter status">
        <x-filter-chip label="Aktif" :count="$counts['aktif']" :active="$filter === 'aktif'" :href="route('admin.inventaris.kerusakan.index')"/>
        @foreach (\App\Enums\DamageStatus::cases() as $s)
            <x-filter-chip :label="$s->label()" :active="$filter === $s->value" :href="route('admin.inventaris.kerusakan.index', ['status' => $s->value])"/>
        @endforeach
        <x-filter-chip label="Semua" :count="$counts['semua']" :active="$filter === 'semua'" :href="route('admin.inventaris.kerusakan.index', ['status' => 'semua'])"/>
    </div>

    @if ($reports->isEmpty())
        <x-empty-state title="Tidak ada laporan" text="Laporan dibuat otomatis saat unit dikembalikan rusak atau hilang."/>
    @else
        <ul class="space-y-3">
            @foreach ($reports as $r)
                @php $next = $damage->allowed($r->status); @endphp
                <li class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs text-slate-500">{{ $r->created_at->translatedFormat('j M Y') }} &bull; {{ $r->loan->request_code }} &bull; {{ $r->loan->user->name }}</p>
                            <p class="font-semibold text-slate-900">{{ $r->unit->item->name }} <span class="font-normal text-slate-500">— {{ $r->unit->unit_code }}</span></p>
                            <p class="mt-1 text-sm text-slate-700">{{ $r->description }}</p>
                            @if ($r->resolution_note)<p class="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-semibold">Penyelesaian:</span> {{ $r->resolution_note }}</p>@endif
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <x-status-badge :tone="$r->type->value === 'hilang' ? 'red' : 'amber'" :label="$r->type->label()"/>
                            <x-status-badge :tone="in_array($r->status->value, ['resolved']) ? 'green' : ($r->status->value === 'written_off' ? 'gray' : 'navy')" :label="$r->status->label()"/>
                        </div>
                    </div>
                    @if ($next)
                        <form method="POST" action="{{ route('admin.inventaris.kerusakan.update', $r) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                            @csrf @method('PATCH')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div><label for="st-{{ $r->id }}" class="field-label">Ubah status</label>
                                    <select id="st-{{ $r->id }}" name="status" class="field-control">@foreach ($next as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach</select></div>
                                <div><label for="fine-{{ $r->id }}" class="field-label">Denda (Rp) <span class="font-normal text-slate-500">(jika ada)</span></label>
                                    <input id="fine-{{ $r->id }}" type="number" name="fine_amount" class="field-control" placeholder="0" value="{{ $r->fine_amount ?? 0 }}" min="0" step="1000"></div>
                            </div>
                            <div><label for="nt-{{ $r->id }}" class="field-label">Catatan <span class="font-normal text-slate-500">(wajib saat selesai/dihapusbukukan)</span></label>
                                <input id="nt-{{ $r->id }}" name="resolution_note" class="field-control" maxlength="1000"></div>
                            <div class="flex justify-end">
                                <x-button type="submit" variant="secondary">Simpan</x-button>
                            </div>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
        <x-pagination :paginator="$reports" class="mt-6"/>
    @endif
</x-app-layout>
