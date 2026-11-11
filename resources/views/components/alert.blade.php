@props(['variant' => 'info', 'title' => null])
@php
    $v = [
        'info' => ['bg-sky-50 border-sky-200 text-sky-900', 'text-sky-600'],
        'success' => ['bg-emerald-50 border-emerald-200 text-emerald-900', 'text-emerald-600'],
        'warning' => ['bg-amber-50 border-amber-200 text-amber-900', 'text-amber-600'],
        'error' => ['bg-red-50 border-red-200 text-red-900', 'text-red-600'],
    ][$variant] ?? ['bg-sky-50 border-sky-200 text-sky-900', 'text-sky-600'];
@endphp
<div role="{{ $variant === 'error' ? 'alert' : 'status' }}" {{ $attributes->class(['flex gap-3 rounded-xl border p-4', $v[0]]) }}>
    <svg class="mt-0.5 size-5 shrink-0 {{ $v[1] }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-4a1 1 0 0 0-1 1v3a1 1 0 0 0 2 0V7a1 1 0 0 0-1-1Zm0 8a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2Z" clip-rule="evenodd"/></svg>
    <div class="min-w-0 text-sm">
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>
</div>
