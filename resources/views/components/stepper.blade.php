{{-- steps: [['label' => 'Pending', 'at' => Carbon|string|null, 'state' => 'done|current|todo'], ...] --}}
@props(['steps' => []])
<ol {{ $attributes->class('grid gap-4 sm:grid-flow-col sm:auto-cols-fr') }} aria-label="Tahapan peminjaman">
    @foreach ($steps as $i => $s)
        @php $state = $s['state'] ?? 'todo'; @endphp
        <li class="relative flex gap-3 sm:flex-col sm:gap-2" @if ($state === 'current') aria-current="step" @endif>
            @unless ($loop->last)
                <span aria-hidden="true" class="absolute left-4 top-8 h-[calc(100%-1rem)] w-0.5 sm:left-8 sm:top-4 sm:h-0.5 sm:w-[calc(100%-1rem)] {{ $state === 'done' ? 'bg-emerald-600' : 'bg-slate-200' }}"></span>
            @endunless
            <span @class([
                'relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                'bg-emerald-700 text-white' => $state === 'done',
                'bg-white text-emerald-800 ring-2 ring-emerald-700' => $state === 'current',
                'bg-slate-100 text-slate-500' => $state === 'todo',
            ])>
                @if ($state === 'done')
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                @else {{ $i + 1 }} @endif
            </span>
            <span class="min-w-0 sm:pr-2">
                <span @class(['block text-sm font-semibold', 'text-slate-900' => $state !== 'todo', 'text-slate-500' => $state === 'todo'])>{{ $s['label'] }}</span>
                @if (! empty($s['at']))
                    <span class="block text-xs text-slate-500">{{ $s['at'] instanceof \Carbon\CarbonInterface ? $s['at']->translatedFormat('j M Y, H.i') : $s['at'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>
