<header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6">
    <h1 class="font-display font-semibold text-lg text-navy-900">@yield('page-title', 'Dashboard')</h1>

    <div class="flex items-center gap-4">
        <button class="relative text-slate-500 hover:text-navy-900" aria-label="Notifikasi">
            <i class="ti ti-bell text-xl"></i>
            <span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-gold-500"></span>
        </button>

        <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
            <div class="w-9 h-9 rounded-full bg-navy-900 text-white flex items-center justify-center text-sm font-medium">
                {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
            </div>
            <div class="text-sm leading-tight">
                <p class="font-medium text-slate-800">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="text-slate-400 text-xs">{{ ucfirst(auth()->user()->role ?? 'admin') }}</p>
            </div>
        </div>
    </div>
</header>
