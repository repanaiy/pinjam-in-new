<nav class="mb-5 flex gap-1 rounded-xl bg-slate-100 p-1 sm:inline-flex" aria-label="Bagian inventaris">
    @foreach ([['Alat', 'admin.inventaris.index', ['admin.inventaris.index', 'admin.inventaris.create', 'admin.inventaris.edit']], ['Kategori', 'admin.inventaris.kategori.index', ['admin.inventaris.kategori.*']], ['Laporan kerusakan', 'admin.inventaris.kerusakan.index', ['admin.inventaris.kerusakan.*']]] as [$label, $route, $patterns])
        @php $on = request()->routeIs(...$patterns); @endphp
        <a href="{{ route($route) }}" @if ($on) aria-current="page" @endif
           class="flex-1 whitespace-nowrap rounded-lg px-4 py-2 text-center text-sm font-semibold transition-colors {{ $on ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:text-slate-900' }}">{{ $label }}</a>
    @endforeach
</nav>
