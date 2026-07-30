<header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 relative">
    <h1 class="font-display font-semibold text-lg text-navy-900">@yield('page-title', 'Dashboard')</h1>

    <div class="flex items-center gap-4">
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
        // Polling sederhana tiap 10 detik untuk update badge unread count.
        setInterval(loadNotifications, 10000);
    })();
</script>
@endpush
@endonce
