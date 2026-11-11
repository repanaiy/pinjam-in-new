{{-- Toast global. Flash dari server: ->with('toast', ['type'=>'error','title'=>'...','message'=>'...']) --}}
@if (session('toast'))
    <script>document.addEventListener('alpine:init', () => window.toast(@json(session('toast'))));</script>
@endif
<div class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4" aria-live="assertive">
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div x-transition.opacity role="alert"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-white p-4 shadow-lg"
             :class="{'border-red-200': t.type==='error', 'border-emerald-200': t.type==='success', 'border-amber-200': t.type==='warning', 'border-slate-200': t.type==='info'}">
            <span class="mt-1 size-2.5 shrink-0 rounded-full"
                  :class="{'bg-red-600': t.type==='error', 'bg-emerald-600': t.type==='success', 'bg-amber-500': t.type==='warning', 'bg-slate-500': t.type==='info'}"></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900" x-text="t.title" x-show="t.title"></p>
                <p class="text-sm text-slate-600" x-text="t.message"></p>
            </div>
            <button type="button" class="rounded p-1 text-slate-400 hover:text-slate-700" @click="$store.toasts.remove(t.id)" aria-label="Tutup">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
        </div>
    </template>
</div>
