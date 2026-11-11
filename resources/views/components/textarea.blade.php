@props(['label' => null, 'name', 'rows' => 4, 'hint' => null, 'error' => null])
@php $err = $error ?? $errors->first($name); @endphp
<div>
    @if ($label)<label for="{{ $name }}" class="field-label">{{ $label }}</label>@endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'field-control '.($err ? 'border-red-500' : '')]) }}
        @if ($err) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>{{ $slot }}</textarea>
    @if ($hint && ! $err)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @if ($err)<p id="{{ $name }}-error" class="mt-1 text-xs font-medium text-red-700">{{ $err }}</p>@endif
</div>
