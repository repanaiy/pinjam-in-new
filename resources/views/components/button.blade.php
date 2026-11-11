@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button', 'loading' => false])
@php
    $variants = [
        'primary' => 'bg-emerald-700 text-white hover:bg-emerald-800 focus-visible:outline-emerald-700 disabled:bg-emerald-300',
        'secondary' => 'bg-white text-slate-800 border border-slate-300 hover:bg-slate-50 disabled:text-slate-400',
        'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100 disabled:text-slate-400',
        'destructive' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-600 disabled:bg-red-300',
    ];
    $sizes = ['sm' => 'px-3 py-1.5 text-sm', 'md' => 'px-4 py-2.5 text-sm', 'lg' => 'px-5 py-3 text-base'];
    $class = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg font-semibold transition-colors disabled:cursor-not-allowed '
        .($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }} @disabled($loading)>
        @if ($loading)
            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="4"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>
        @endif
        {{ $slot }}
    </button>
@endif
