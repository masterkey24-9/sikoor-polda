@php
    $menu = [
        ['label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'route' => 'dashboard'],
        ['label' => 'Monitoring IKPA', 'icon' => 'ti-chart-bar', 'route' => 'monitoring.ikpa'],
        ['label' => 'Indicators & upload', 'icon' => 'ti-upload', 'route' => 'indicators.index'],
        ['label' => 'Peringatan Satker', 'icon' => 'ti-alert-triangle', 'route' => 'peringatan.index'],
        ['label' => 'Kelola satker', 'icon' => 'ti-building-fortress', 'route' => 'satkers.index'],
    ];
@endphp

<div class="h-20 flex items-center gap-3 px-4 border-b border-navy-800 relative overflow-hidden">
    <div class="absolute inset-0 opacity-[0.06]"
         style="background-image: repeating-linear-gradient(45deg, #D4AF37 0 2px, transparent 2px 14px), repeating-linear-gradient(-45deg, #D4AF37 0 2px, transparent 2px 14px);"></div>
    <div class="flex items-center gap-1.5 relative shrink-0">
        @if (file_exists(public_path('images/logo.png')))
            <img src="{{ asset('images/logo.png') }}" alt="Logo Polda Sumbar"
                 class="w-11 h-11 rounded object-contain shrink-0">
        @else
            <div class="w-11 h-11 rounded bg-gold-500 flex items-center justify-center text-navy-950 font-display font-bold text-sm">S</div>
        @endif
        @if (file_exists(public_path('images/bidkeu.png')))
            <img src="{{ asset('images/bidkeu.png') }}" alt="Logo Bidkeu"
                 class="w-11 h-11 rounded object-contain shrink-0">
        @endif
    </div>
    <div class="leading-tight relative">
        <p class="font-display font-bold text-base text-white tracking-tight">Simpati IKPA</p>
        <p class="text-xs font-medium text-gold-400/90">Polda Sumbar</p>
    </div>
</div>

<nav class="flex-1 px-3 py-4 space-y-1">
    @foreach ($menu as $item)
        @php $active = request()->routeIs($item['route']); @endphp
        <a href="{{ route($item['route']) }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold tracking-wide transition
                  {{ $active ? 'bg-navy-800 text-white shadow-inner' : 'text-slate-200 hover:bg-navy-900 hover:text-white' }}">
            <i class="ti {{ $item['icon'] }} text-lg {{ $active ? 'text-gold-400' : '' }}"></i>
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>

<form method="POST" action="{{ route('logout') }}" class="p-3 border-t border-navy-800">
    @csrf
    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold tracking-wide text-slate-200 hover:bg-navy-900 hover:text-white">
        <i class="ti ti-logout text-lg"></i>
        Keluar
    </button>
</form>