<header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 relative">
    {{-- Aksen geometris tipis khas ukiran, sebagai border bawah header --}}
    <div class="absolute bottom-0 left-0 right-0 h-[3px] opacity-70"
         style="background-image: repeating-linear-gradient(135deg, #D4AF37 0 6px, transparent 6px 12px);"></div>

    <h1 class="font-display font-semibold text-lg text-navy-900">@yield('page-title', 'Dashboard')</h1>

    <div class="flex items-center gap-4">
        <div class="hidden md:flex items-center gap-2 text-xs text-slate-500 pr-4 border-r border-slate-200">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            Sistem Online
            <span id="liveClock" class="font-medium text-slate-600 tabular-nums"></span>
        </div>

        <div class="relative">

            <button id="notifBell" class="relative text-slate-500 hover:text-navy-900" aria-label="Notifikasi">
                <i class="ti ti-bell text-xl"></i>
                <span id="notifBadge" class="hidden absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-gold-500 text-navy-950 text-[10px] font-medium flex items-center justify-center"></span>
            </button>

            {{-- Dropdown notifikasi --}}
            <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <p class="text-sm font-medium text-slate-800">Notifikasi</p>
                    <button id="notifMarkAll" class="text-xs text-navy-800 hover:underline">Tandai semua dibaca</button>
                </div>
                <div id="notifList" class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                    <p class="text-center text-xs text-slate-400 p-5">Memuat notifikasi...</p>
                </div>
            </div>
        </div>

        <div class="relative pl-4 border-l border-slate-200">
            <button id="profileMenuBtn" type="button"
                    class="flex items-center gap-3 rounded-lg hover:bg-slate-50 pr-2 py-1 transition"
                    aria-haspopup="true" aria-expanded="false">
                <div class="w-9 h-9 rounded-full bg-navy-900 text-white flex items-center justify-center text-sm font-medium shrink-0">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="text-sm leading-tight text-left">
                    <p class="font-medium text-slate-800">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-slate-400 text-xs">{{ ucfirst(auth()->user()->role ?? 'admin') }}</p>
                </div>
                <i class="ti ti-chevron-down text-slate-400 text-base ml-1"></i>
            </button>

            {{-- Dropdown menu profil --}}
            <div id="profileMenuDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email ?? '' }}</p>
                </div>
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-navy-900">
                    <i class="ti ti-user-circle text-lg"></i>
                    Profil saya
                </a>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                        <i class="ti ti-logout text-lg"></i>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

@once
@push('scripts')
<script>
    (function () {
        const bell = document.getElementById('notifBell');
        const dropdown = document.getElementById('notifDropdown');
        const badge = document.getElementById('notifBadge');
        const list = document.getElementById('notifList');
        const markAllBtn = document.getElementById('notifMarkAll');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function timeAgo(dateStr) {
            const diffSec = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if (diffSec < 60) return 'Baru saja';
            const diffMin = Math.floor(diffSec / 60);
            if (diffMin < 60) return `${diffMin} menit lalu`;
            const diffHour = Math.floor(diffMin / 60);
            if (diffHour < 24) return `${diffHour} jam lalu`;
            const diffDay = Math.floor(diffHour / 24);
            return `${diffDay} hari lalu`;
        }

        function iconFor(type) {
            return type === 'chat' ? 'ti-message-circle' : 'ti-file-text';
        }

        function renderNotifications(notifications) {
            if (notifications.length === 0) {
                list.innerHTML = '<p class="text-center text-xs text-slate-400 p-5">Belum ada notifikasi.</p>';
                return;
            }

            list.innerHTML = '';
            notifications.forEach((n) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = `w-full text-left flex items-start gap-3 px-4 py-3 hover:bg-slate-50 ${n.read_at ? '' : 'bg-slate-50/70'}`;
                item.innerHTML = `
                    <div class="w-8 h-8 rounded-full bg-navy-900/10 text-navy-900 flex items-center justify-center shrink-0">
                        <i class="ti ${iconFor(n.type)} text-base"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800 truncate"></p>
                        <p class="text-xs text-slate-500 line-clamp-2"></p>
                        <p class="text-[11px] text-slate-400 mt-0.5"></p>
                    </div>
                    ${n.read_at ? '' : '<span class="w-2 h-2 rounded-full bg-gold-500 mt-1.5 shrink-0"></span>'}
                `;
                item.querySelector('.font-medium').textContent = n.title;
                item.querySelectorAll('p')[1].textContent = n.body;
                item.querySelectorAll('p')[2].textContent = timeAgo(n.created_at);

                item.addEventListener('click', async () => {
                    try {
                        await fetch(`/notifications/${n.id}/read`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        });
                    } catch (e) { /* diamkan, tetap lanjut navigasi */ }

                    if (n.link) window.location.href = n.link;
                });

                list.appendChild(item);
            });
        }

        async function loadNotifications() {
            try {
                const res = await fetch('{{ route('notifications.data') }}', {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('Gagal memuat notifikasi');
                const data = await res.json();

                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                renderNotifications(data.notifications);
            } catch (err) {
                list.innerHTML = '<p class="text-center text-xs text-red-500 p-5">Gagal memuat notifikasi.</p>';
            }
        }

        bell.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) loadNotifications();
        });

        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && e.target !== bell) {
                dropdown.classList.add('hidden');
            }
        });

        const profileBtn = document.getElementById('profileMenuBtn');
        const profileDropdown = document.getElementById('profileMenuDropdown');

        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const willShow = profileDropdown.classList.contains('hidden');
            profileDropdown.classList.toggle('hidden');
            profileBtn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
        });

        document.addEventListener('click', (e) => {
            if (!profileDropdown.contains(e.target) && e.target !== profileBtn && !profileBtn.contains(e.target)) {
                profileDropdown.classList.add('hidden');
                profileBtn.setAttribute('aria-expanded', 'false');
            }
        });

        markAllBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            try {
                await fetch('{{ route('notifications.readAll') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                loadNotifications();
            } catch (err) {
                alert('Gagal menandai semua dibaca.');
            }
        });

        loadNotifications();

        setInterval(loadNotifications, 10000);

        const liveClock = document.getElementById('liveClock');
        if (liveClock) {
            function updateClock() {
                const now = new Date();
                const time = now.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit' });
                liveClock.textContent = `${time} WIB`;
            }
            updateClock();
            setInterval(updateClock, 1000);
        }
    })();
</script>
@endpush
@endonce
