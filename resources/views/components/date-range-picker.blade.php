{{-- Field pilih rentang: tombol ringkas + kalender dropdown. Memakai <x-calendar>. --}}
@props(['label' => 'Periode peminjaman', 'config' => [], 'startName' => 'start_date', 'endName' => 'end_date'])
@php $err = $errors->first($startName) ?: $errors->first($endName); @endphp
<div x-data="{ open: false }" @keydown.escape="open = false" @click.outside="open = false" class="relative">
    <span class="field-label">{{ $label }}</span>
    <div x-data="rangeCalendar(@js($config))" class="contents">
        <input type="hidden" name="{{ $startName }}" :value="start">
        <input type="hidden" name="{{ $endName }}" :value="end">
        <button type="button" @click="open = !open" :aria-expanded="open"
                class="field-control flex items-center justify-between text-left {{ $err ? 'border-red-500' : '' }}">
            <span x-text="label" :class="start ? 'text-slate-900' : 'text-slate-400'"></span>
            <svg class="size-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M6 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h.5A2.5 2.5 0 0 1 18 6.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 2 15.5v-9A2.5 2.5 0 0 1 4.5 4H5V3a1 1 0 0 1 1-1Zm-2 6v7.5a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5V8H4Z" clip-rule="evenodd"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition class="absolute z-30 mt-2 w-full min-w-[19rem] rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
            <div class="mb-3 flex items-center justify-between">
                <button type="button" @click="prev()" :disabled="!canGoPrev" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 disabled:text-slate-300" aria-label="Bulan sebelumnya">&lsaquo;</button>
                <p class="text-sm font-semibold text-slate-900" x-text="title"></p>
                <button type="button" @click="next()" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Bulan berikutnya">&rsaquo;</button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500"><template x-for="w in weekdays" :key="w"><span class="py-1" x-text="w"></span></template></div>
            <div class="mt-1 grid grid-cols-7 gap-1" @mouseleave="hover = ''">
                <template x-for="(iso, i) in cells" :key="i"><div><template x-if="iso">
                    <button type="button" :class="cellClass(iso)" :disabled="isDisabled(iso)" :title="reason(iso)" @click="select(iso); if (end) open = false" @mouseenter="hover = iso" x-text="Number(iso.slice(8))"></button>
                </template></div></template>
            </div>
            <p x-show="message" x-cloak x-text="message" class="mt-3 text-xs font-medium text-red-700" role="alert"></p>
        </div>
    </div>
    @if ($err)<p class="mt-1 text-xs font-medium text-red-700">{{ $err }}</p>@endif
</div>
