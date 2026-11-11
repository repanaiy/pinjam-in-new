@props(['title' => 'Belum ada data', 'text' => null])
<div {{ $attributes->class('flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center') }}>
    <svg class="size-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20 7.5 12 3 4 7.5m16 0v9L12 21m8-13.5-8 4.5m0 9-8-4.5v-9m8 4.5v9M4 7.5l8 4.5"/></svg>
    <p class="mt-3 text-sm font-semibold text-slate-800">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-slate-500">{{ $text }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
