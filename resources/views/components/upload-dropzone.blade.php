@props(['name', 'label' => null, 'hint' => null, 'maxMb' => 5, 'accept' => ['image/jpeg', 'image/png', 'application/pdf'], 'required' => false])
@php $err = $errors->first($name); @endphp
<div x-data="dropzone({ maxMb: {{ $maxMb }}, accept: @js($accept) })">
    @if ($label)<span class="field-label">{{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</span>@endif
    <label @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="drop($event)"
           :class="over ? 'border-emerald-600 bg-emerald-50' : 'border-slate-300 bg-white hover:bg-slate-50'"
           class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors focus-within:outline focus-within:outline-2 focus-within:outline-emerald-600">
        <svg x-show="!previewUrl" class="size-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
        <div x-show="previewUrl" x-cloak class="mb-2 size-20 overflow-hidden rounded-lg border border-slate-200">
            <img :src="previewUrl" class="size-full object-cover">
        </div>
        <span class="mt-2 text-sm font-medium text-slate-700" x-show="!file">Seret berkas ke sini atau <span class="text-emerald-700 underline">pilih berkas</span></span>
        <span class="mt-2 text-sm font-medium text-slate-900" x-show="file" x-cloak><span x-text="file && file.name" class="break-all"></span> <span class="text-slate-500" x-text="'(' + size() + ')'"></span></span>
        @if ($hint)<span class="mt-1 text-xs text-slate-500">{{ $hint }}</span>@endif
        <input x-ref="input" type="file" name="{{ $name }}" class="sr-only" accept="{{ implode(',', $accept) }}" @change="pick($event.target.files)" @required($required)>
    </label>
    <button type="button" x-show="file" x-cloak @click="clear()" class="mt-2 text-xs font-medium text-slate-600 underline hover:text-slate-900">Hapus berkas</button>
    <p x-show="error" x-cloak x-text="error" class="mt-1 text-xs font-medium text-red-700" role="alert"></p>
    @if ($err)<p class="mt-1 text-xs font-medium text-red-700" role="alert">{{ $err }}</p>@endif
</div>
