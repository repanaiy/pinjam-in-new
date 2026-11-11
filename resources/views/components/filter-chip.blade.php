@props(['label', 'count' => null, 'active' => false, 'href' => '#'])
<a href="{{ $href }}" @if ($active) aria-current="true" @endif
   {{ $attributes->class([
        'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors',
        'border-emerald-700 bg-emerald-700 text-white' => $active,
        'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => ! $active,
   ]) }}>
    {{ $label }}
    @if (! is_null($count))
        <span @class(['rounded-full px-1.5 text-xs tabular-nums', 'bg-emerald-600 text-white' => $active, 'bg-slate-100 text-slate-600' => ! $active])>{{ $count }}</span>
    @endif
</a>
