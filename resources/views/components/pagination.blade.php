@props(['paginator'])
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" {{ $attributes->class('flex flex-wrap items-center justify-between gap-3') }}>
        <p class="text-sm text-slate-600">Menampilkan <span class="font-semibold">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span> dari <span class="font-semibold">{{ $paginator->total() }}</span></p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm text-slate-400">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Sebelumnya</a>
            @endif
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                <a href="{{ $url }}" @if ($page == $paginator->currentPage()) aria-current="page" @endif
                   @class(['rounded-lg px-3 py-1.5 text-sm font-medium tabular-nums', 'bg-emerald-700 text-white' => $page == $paginator->currentPage(), 'text-slate-700 hover:bg-slate-100' => $page != $paginator->currentPage()])>{{ $page }}</a>
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Berikutnya</a>
            @else
                <span class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm text-slate-400">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif
