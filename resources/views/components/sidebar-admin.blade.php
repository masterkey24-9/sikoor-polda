@php
    $menu = [
        ['label' => 'Monitoring', 'icon' => 'ti-chart-bar', 'route' => 'dashboard'],
        ['label' => 'Indicators & upload', 'icon' => 'ti-upload', 'route' => 'indicators.index'],
        ['label' => 'Kelola satker', 'icon' => 'ti-building-fortress', 'route' => 'satkers.index'],
        ['label' => 'Live chat', 'icon' => 'ti-message-circle', 'route' => 'messages.index'],
    ];
@endphp

<div class="h-16 flex items-center gap-3 px-5 border-b border-navy-800">
    <div class="w-8 h-8 rounded bg-gold-500 flex items-center justify-center text-navy-950 font-display font-bold text-sm">S</div>
    <div class="leading-tight">
        <p class="font-display font-semibold text-sm text-white">Sikoor</p>
        <p class="text-[11px] text-slate-400">Polda Sumbar</p>
    </div>
</div>

<nav class="flex-1 px-3 py-4 space-y-1">
    @foreach ($menu as $item)
        @php $active = request()->routeIs($item['route']); @endphp
        <a href="{{ route($item['route']) }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition
                  {{ $active ? 'bg-navy-800 text-white font-medium' : 'text-slate-300 hover:bg-navy-900 hover:text-white' }}">
            <i class="ti {{ $item['icon'] }} text-lg"></i>
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>

<form method="POST" action="{{ route('logout') }}" class="p-3 border-t border-navy-800">
    @csrf
    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-300 hover:bg-navy-900 hover:text-white">
        <i class="ti ti-logout text-lg"></i>
        Keluar
    </button>
</form>
