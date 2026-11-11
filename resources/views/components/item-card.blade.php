@props(['item'])
@php
    $total = $item->total_units ?? $item->units()->count();
    $ready = $item->ready_units ?? 0;
    $detail = \Illuminate\Support\Facades\Route::has('katalog.show') ? route('katalog.show', $item) : '#';
    $cover = $item->coverUrl();
@endphp
<article {{ $attributes->class('card overflow-hidden hover:shadow-md transition-shadow') }}>
    <div class="relative aspect-video bg-slate-100">
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $item->name }}" loading="lazy" class="size-full object-cover">
        @else
            <div class="flex size-full items-center justify-center text-slate-300" aria-hidden="true">
                <img src="{{ asset('images/logo_pinjam_in_kotak_perkakas_hijau_senada.svg') }}" alt="Foto" class="size-10 text-slate-300">
            </div>
        @endif
        <div class="absolute right-2 top-2">
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $ready > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $ready > 0 ? $ready.' unit' : 'Habis' }}</span>
        </div>
    </div>
    <div class="p-3">
        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">{{ $item->category->name }}</p>
        <h3 class="mt-0.5 text-sm font-bold leading-tight text-slate-900">{{ $item->name }}</h3>
        @if ($item->summary)<p class="mt-1 line-clamp-2 text-xs text-slate-600">{{ $item->summary }}</p>@endif
        <div class="mt-2 flex items-center justify-between text-[10px] text-slate-500">
            <span>{{ $item->code }}</span>
            <span>Maks. {{ $item->max_loan_days }}h</span>
        </div>
        <x-button :href="$detail" variant="secondary" size="sm" class="mt-3 w-full">Detail</x-button>
    </div>
</article>
