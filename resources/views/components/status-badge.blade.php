@props(['status' => null, 'tone' => 'gray', 'label' => null])
@php
    if ($status instanceof \App\Enums\LoanStatus) {
        $tone = $status->tone();
        $label ??= $status->label();
    }
    $tones = [
        'amber' => 'bg-amber-100 text-amber-900 ring-amber-200',
        'green' => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
        'navy' => 'bg-slate-800 text-white ring-slate-800',
        'deepgreen' => 'bg-emerald-800 text-white ring-emerald-800',
        'red' => 'bg-red-100 text-red-900 ring-red-200',
        'gray' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold leading-none ring-1 ring-inset', $tones[$tone] ?? $tones['gray']]) }}>
    {{ $label ?? $slot }}
</span>
