@props(['empty' => 'Belum ada data.'])
<div {{ $attributes->class('card overflow-hidden') }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600">{{ $head }}</thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">{{ $slot }}</tbody>
        </table>
    </div>
    @isset($foot)<div class="border-t border-slate-200 px-4 py-3">{{ $foot }}</div>@endisset
</div>
