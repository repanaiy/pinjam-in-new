@props(['label', 'value', 'hint' => null, 'spark' => [], 'tone' => 'emerald'])
@php
    $stroke = ['emerald' => '#047857', 'amber' => '#B45309', 'navy' => '#1E293B', 'red' => '#B91C1C'][$tone] ?? '#047857';
    $pts = '';
    if (count($spark) > 1) {
        $max = max($spark) ?: 1; $min = min($spark); $range = ($max - $min) ?: 1; $n = count($spark) - 1;
        $pts = collect($spark)->map(fn ($v, $i) => round($i / $n * 96 + 2, 1).','.round(26 - (($v - $min) / $range) * 22, 1))->implode(' ');
    }
@endphp
<div {{ $attributes->class('card p-3') }}>
    <p class="text-[10px] uppercase font-bold tracking-wider text-slate-500">{{ $label }}</p>
    <div class="mt-1 flex items-end justify-between gap-2">
        <p class="text-2xl font-bold tabular-nums text-slate-900">{{ $value }}</p>
        @if ($pts)
            <svg viewBox="0 0 100 30" class="h-6 w-20 shrink-0" aria-hidden="true"><polyline points="{{ $pts }}" fill="none" stroke="{{ $stroke }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @endif
    </div>
</div>
