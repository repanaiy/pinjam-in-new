{{-- Kalender rentang tanggal (Alpine). Mengisi input hidden start_date / end_date.
     config: today, start, end, disabled[], full[], holidays{}, maxDays --}}
@props(['config' => [], 'startName' => 'start_date', 'endName' => 'end_date'])
<div x-data="rangeCalendar(@js($config))" {{ $attributes->class('select-none') }}>
    <input type="hidden" name="{{ $startName }}" :value="start">
    <input type="hidden" name="{{ $endName }}" :value="end">
    <div class="mb-3 flex items-center justify-between">
        <button type="button" @click="prev()" :disabled="!canGoPrev" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 disabled:text-slate-300" aria-label="Bulan sebelumnya"><svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.7 5.3a1 1 0 0 1 0 1.4L9.4 10l3.3 3.3a1 1 0 0 1-1.4 1.4l-4-4a1 1 0 0 1 0-1.4l4-4a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg></button>
        <p class="text-sm font-semibold text-slate-900" x-text="title" aria-live="polite"></p>
        <button type="button" @click="next()" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Bulan berikutnya"><svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.3 14.7a1 1 0 0 1 0-1.4l3.3-3.3-3.3-3.3a1 1 0 0 1 1.4-1.4l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 0 1-1.4 0Z" clip-rule="evenodd"/></svg></button>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500">
        <template x-for="w in weekdays" :key="w"><span class="py-1" x-text="w"></span></template>
    </div>
    <div class="mt-1 grid grid-cols-7 gap-1" @mouseleave="hover = ''">
        <template x-for="(iso, i) in cells" :key="i">
            <div>
                <template x-if="iso">
                    <button type="button" :class="cellClass(iso)" :disabled="isDisabled(iso)" :title="reason(iso)"
                            :aria-label="formatTanggal(iso) + (reason(iso) ? ' — ' + reason(iso) : '')"
                            :aria-pressed="iso === start || iso === end" @click="select(iso)" @mouseenter="hover = iso"
                            x-text="Number(iso.slice(8))"></button>
                </template>
            </div>
        </template>
    </div>
    <p x-show="message" x-cloak x-text="message" class="mt-3 text-xs font-medium text-red-700" role="alert"></p>
    <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-sm">
        <p class="font-medium text-slate-800" x-text="label"></p>
        <span x-show="duration" x-cloak class="whitespace-nowrap rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-900" x-text="duration + ' hari'"></span>
    </div>
    <button type="button" x-show="start" x-cloak @click="clear()" class="mt-2 text-xs font-medium text-slate-600 underline hover:text-slate-900">Reset tanggal</button>
</div>
