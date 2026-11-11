@props(['label' => null, 'name', 'type' => 'text', 'hint' => null, 'error' => null])
@php $err = $error ?? $errors->first($name); @endphp
<div>
    @if ($label)<label for="{{ $attributes->get('id', $name) }}" class="field-label">{{ $label }}</label>@endif
    <input
        id="{{ $attributes->get('id', $name) }}" name="{{ $name }}" type="{{ $type }}"
        {{ $attributes->except('id')->merge(['class' => 'field-control '.($err ? 'border-red-500 focus:border-red-600 focus:ring-red-600' : '')]) }}
        @if ($err) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
    >
    @if ($hint && ! $err)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @if ($err)<p id="{{ $name }}-error" class="mt-1 text-xs font-medium text-red-700">{{ $err }}</p>@endif
</div>
