{{-- Buka: $dispatch('open-modal', 'nama')  Tutup: $dispatch('close-modal', 'nama') atau tombol Esc --}}
@props(['name', 'title' => null, 'maxWidth' => 'lg', 'show' => false])
@php $w = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl'][$maxWidth] ?? 'max-w-lg'; @endphp
<div x-data="{ open: @js($show) }" x-cloak x-show="open"
     @open-modal.window="if ($event.detail === '{{ $name }}') open = true"
     @close-modal.window="if ($event.detail === '{{ $name }}') open = false"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" @if($title) aria-label="{{ $title }}" @endif>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/60" @click="open = false"></div>
    <div x-show="open" x-transition class="relative max-h-[90vh] w-full {{ $w }} overflow-y-auto rounded-t-2xl bg-white p-6 shadow-xl sm:rounded-2xl">
        @if ($title)
            <div class="mb-4 flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
                <button type="button" @click="open = false" class="rounded p-1 text-slate-400 hover:text-slate-700" aria-label="Tutup"><svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg></button>
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
