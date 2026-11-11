<div class="flex h-full flex-col">
    <div class="px-5 pb-4 pt-6"><x-logo class="h-8 w-8" color="light"/></div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2">
        @foreach ($nav as [$label, $route, $icon])
            @php
                $href = $to($route);
                $pattern = substr_count($route, '.') >= 2
                    ? \Illuminate\Support\Str::beforeLast($route, '.').'.*'
                    : (str_starts_with($route, 'admin.') ? $route : \Illuminate\Support\Str::before($route, '.').'.*');
                $active = $href && request()->routeIs($pattern);
            @endphp
            @if ($href)
                <a href="{{ $href }}" @if ($active) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors {{ $active ? 'bg-emerald-700 text-white' : 'text-slate-200 hover:bg-slate-700 hover:text-white' }}">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    <span class="whitespace-nowrap">{{ $label }}</span>
                    @if ($route === 'notifikasi.index' && ($unread ?? 0) > 0)
                        <span class="ml-auto rounded-full bg-emerald-500 px-2 py-0.5 text-xs font-bold text-slate-900">{{ $unread }}</span>
                    @endif
                </a>
            @else
                <span class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-400" title="Segera hadir" aria-disabled="true">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    <span class="whitespace-nowrap">{{ $label }}</span>
                </span>
            @endif
        @endforeach
    </nav>

    <div class="space-y-3 p-4">
        <div class="rounded-xl bg-slate-700/60 p-4">
            <p class="text-xs font-semibold text-emerald-300">Tahun ajaran {{ $year }}</p>
            <p class="mt-1 text-sm text-slate-100">Sarana untuk setiap karya.</p>
        </div>
        <a href="{{ route('profil.edit') }}" class="flex items-center gap-3 rounded-xl bg-slate-900/40 p-3 transition-colors hover:bg-slate-900/70" title="Buka profil &amp; ganti password">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="" class="size-10 shrink-0 rounded-full object-cover">
            @else
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-sm font-bold text-white" aria-hidden="true">{{ $user->initials() }}</span>
            @endif
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                <p class="truncate text-xs text-slate-300">{{ $user->isAdmin() ? 'Petugas Sarpras'.($user->employee_code ? ' • '.$user->employee_code : '') : ($user->kelas ?? 'Siswa') }}</p>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-100 transition-colors hover:bg-slate-700">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0 4-4m-4 4 4 4M10 5V4a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2v-1"/></svg>
                Keluar dari sistem
            </button>
        </form>
    </div>
</div>
