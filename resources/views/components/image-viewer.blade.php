{{-- Penampil gambar/video layar penuh. Dipasang sekali di layout; dibuka lewat
     $dispatch('open-viewer', { items: [{ src, alt, caption, type: 'image'|'video' }], index: 0 }) --}}
<div x-data="imageViewer()" x-cloak x-show="open" x-ref="dialog"
     @open-viewer.window="show($event.detail)" @keydown.window="onKey($event)"
     x-transition.opacity.duration.150ms
     class="fixed inset-0 z-[90] flex flex-col bg-slate-950/95 text-white" role="dialog" aria-modal="true" aria-label="Penampil gambar">

    {{-- Bilah atas --}}
    <header class="flex items-center justify-between gap-3 px-3 py-3 sm:px-5">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold" x-text="current.caption || current.alt || 'Gambar'"></p>
            <p class="text-xs text-slate-300" aria-live="polite" x-text="counter"></p>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <template x-if="canZoom">
                <div class="flex items-center gap-1">
                    <button type="button" @click="zoomOut()" :disabled="scale <= MIN" class="rounded-lg p-2 hover:bg-white/10 disabled:opacity-30" aria-label="Perkecil">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M8 11h6m7 10-4.5-4.5"/></svg></button>
                    <button type="button" @click="reset()" class="min-w-[3.25rem] rounded-lg px-1.5 py-2 text-xs font-semibold tabular-nums hover:bg-white/10" aria-label="Setel ulang zoom" x-text="zoomLabel"></button>
                    <button type="button" @click="zoomIn()" :disabled="scale >= MAX" class="rounded-lg p-2 hover:bg-white/10 disabled:opacity-30" aria-label="Perbesar">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M8 11h6M11 8v6m10 7-4.5-4.5"/></svg></button>
                </div>
            </template>
            <a :href="current.src" target="_blank" rel="noopener" class="rounded-lg p-2 hover:bg-white/10" aria-label="Buka ukuran asli di tab baru">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6m0-6-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg></a>
            <button type="button" x-ref="close" @click="close()" class="rounded-lg p-2 hover:bg-white/10" aria-label="Tutup (Esc)">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
    </header>

    {{-- Panggung --}}
    <div x-ref="stage" class="relative min-h-0 flex-1 touch-none select-none overflow-hidden"
         @wheel.prevent="onWheel($event)" @dblclick="onDblClick($event)"
         @pointerdown="onDown($event)" @pointermove="onMove($event)" @pointerup="onUp($event)" @pointercancel="onUp($event)"
         @click="onBackdrop($event)">
        <template x-if="!isVideo">
            <img x-ref="img" :src="current.src" :alt="current.alt || ''" draggable="false"
                 class="absolute inset-0 m-auto max-h-full max-w-full object-contain will-change-transform"
                 :class="(dragging || pinching) ? '' : 'transition-transform duration-150'" :style="imgStyle">
        </template>
        <template x-if="isVideo">
            <video x-ref="video" controls playsinline preload="metadata" :src="current.src" class="absolute inset-0 m-auto max-h-full max-w-full bg-black">
                Peramban Anda tidak mendukung pemutar video.
            </video>
        </template>

        <template x-if="items.length > 1">
            <div>
                <button type="button" @click.stop="go(-1)" @pointerdown.stop class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-2.5 hover:bg-black/70 sm:left-4" aria-label="Sebelumnya">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7"/></svg></button>
                <button type="button" @click.stop="go(1)" @pointerdown.stop class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-2.5 hover:bg-black/70 sm:right-4" aria-label="Berikutnya">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
            </div>
        </template>
    </div>

    {{-- Thumbnail + petunjuk --}}
    <footer class="px-3 pb-3 pt-2 sm:px-5">
        <template x-if="items.length > 1">
            <ul class="mb-2 flex justify-center gap-2 overflow-x-auto" aria-label="Pilih gambar">
                <template x-for="(it, i) in items" :key="i">
                    <li class="shrink-0">
                        <button type="button" @click="select(i)" :aria-current="i === index" :aria-label="'Gambar ' + (i + 1)"
                                :class="i === index ? 'ring-2 ring-emerald-400' : 'opacity-60 hover:opacity-100'" class="block size-14 overflow-hidden rounded-lg bg-slate-800 transition-opacity">
                            <template x-if="it.type !== 'video'"><img :src="it.src" alt="" class="size-full object-cover"></template>
                            <template x-if="it.type === 'video'"><span class="flex size-full items-center justify-center text-slate-200"><svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg></span></template>
                        </button>
                    </li>
                </template>
            </ul>
        </template>
        <p class="text-center text-[11px] text-slate-400" x-show="canZoom">Gulir atau cubit untuk zoom &bull; seret untuk menggeser &bull; klik dua kali untuk memperbesar &bull; panah/Esc di papan ketik</p>
    </footer>
</div>
