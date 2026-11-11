@props(['color' => 'dark'])
<div class="flex items-center gap-3">
    <img src="{{ asset('images/logo_pinjam_in_kotak_perkakas_hijau_senada.svg') }}" alt="Logo" class="{{ $attributes->get('class', 'h-10 w-10') }}">
    <span class="text-xl font-bold tracking-tight {{ $color === 'light' ? 'text-white' : 'text-slate-900' }}">PINJAM-IN</span>
</div>