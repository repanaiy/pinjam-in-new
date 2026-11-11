{{-- Panel samping. Buka: $dispatch('open-drawer', 'nama') --}}
@props(['name', 'title' => null, 'side' => 'right', 'width' => 'max-w-md'])
<div x-data="{ open: false }" x-cloak x-show="open"
     @open-drawer.window="if ($event.detail === '{{ $name }}') open = true"
     @close-drawer.window="if ($event.detail === '{{ $name }}') open = false"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-50" role="dialog" aria-modal="true" @if($title) aria-label="{{ $title }}" @endif>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/60" @click="open = false"></div>
    <aside x-show="open"
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="{{ $side === 'right' ? 'translate-x-full' : '-translate-x-full' }}" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="{{ $side === 'right' ? 'translate-x-full' : '-translate-x-full' }}"
           class="absolute inset-y-0 {{ $side === 'right' ? 'right-0' : 'left-0' }} flex w-full {{ $width }} flex-col bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
            <button type="button" @click="open = false" class="rounded p-1 text-slate-400 hover:text-slate-700" aria-label="Tutup"><svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg></button>
        </div>
        <div class="flex-1 overflow-y-auto p-5">{{ $slot }}</div>
    </aside>
</div>
