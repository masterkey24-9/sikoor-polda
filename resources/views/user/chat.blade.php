@extends('layouts.app')

@section('title', 'Live chat')
@section('page-title', 'Live chat')

@section('sidebar')
    @include('components.sidebar-user')
@endsection

@section('content')

<<<<<<< HEAD
    <div class="h-14 border-b border-slate-200 flex items-center px-5">
        <div>
            <p class="text-sm font-medium text-slate-800">Admin Polda Sumbar</p>
            <p class="text-xs" id="adminStatus"></p>
        </div>
=======
<a href="{{ route('user.inbox') }}" class="inline-flex items-center gap-1.5 text-sm text-navy-800 hover:underline mb-4">
    <i class="ti ti-arrow-left text-base"></i> Kembali ke inbox
</a>

<div class="bg-white rounded-xl border border-slate-200 flex flex-col h-[calc(100vh-10.5rem)] overflow-hidden">

    <div class="h-14 border-b border-slate-200 flex flex-col justify-center px-5">
        <p class="text-sm font-medium text-slate-800">Admin Polda Sumbar</p>
        <p class="text-xs text-slate-400" id="adminStatus">&nbsp;</p>
>>>>>>> 99e40b150c528c51c17e3fc3aa26fb94f75ef9c6
    </div>

    <div class="flex-1 overflow-y-auto p-5 space-y-3" id="chatMessages">
        <p class="text-center text-xs text-slate-400">Memuat pesan...</p>
    </div>

    <form class="border-t border-slate-200 p-3 flex items-center gap-2" id="chatForm">
        <input type="text" name="pesan" placeholder="Tulis pesan..."
               class="flex-1 h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
        <button type="submit" class="w-10 h-10 rounded-lg bg-navy-900 hover:bg-navy-800 text-white flex items-center justify-center shrink-0">
            <i class="ti ti-send text-lg"></i>
        </button>
    </form>
</div>

@push('scripts')
<script>
    // Kerangka sederhana. Ganti dengan Laravel Echo + broadcasting saat backend siap:
    // Echo.channel('chat.' + satkerId).listen('.message.sent', (e) => { ...append pesan... });
    const form = document.getElementById('chatForm');
    const messagesEl = document.getElementById('chatMessages');
<<<<<<< HEAD
    const adminStatus = document.getElementById('adminStatus');
=======
    const inputPesan = form.pesan;
>>>>>>> 99e40b150c528c51c17e3fc3aa26fb94f75ef9c6
    const currentUserId = {{ auth()->id() }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let lastTypingPing = 0;

    async function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(body || {}),
        });
    }

    // ===== Heartbeat: tandai satker ini "online" selama halaman chat terbuka =====
    function sendHeartbeat() {
        postJson('{{ route('chat.heartbeat') }}').catch(() => {});
    }
    sendHeartbeat();
    setInterval(sendHeartbeat, 15000);

    // ===== Status Admin: online + sedang mengetik, tiap 2 detik =====
    async function refreshAdminStatus() {
        try {
            const res = await fetch('{{ route('chat.status') }}', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();

            if (data.typing) {
                adminStatus.textContent = 'Sedang mengetik...';
                adminStatus.className = 'text-xs text-navy-800 italic';
            } else if (data.online) {
                adminStatus.textContent = 'Online';
                adminStatus.className = 'text-xs text-emerald-600';
            } else {
                adminStatus.textContent = 'Offline';
                adminStatus.className = 'text-xs text-slate-400';
            }
        } catch (err) { /* diamkan */ }
    }
    refreshAdminStatus();
    setInterval(refreshAdminStatus, 2000);

    // ===== Kirim sinyal "sedang mengetik", di-throttle biar nggak spam tiap huruf =====
    form.pesan.addEventListener('input', () => {
        const now = Date.now();
        if (now - lastTypingPing < 2000) return;
        lastTypingPing = now;
        postJson('{{ route('chat.typing') }}', { satker_id: {{ auth()->user()->satker_id ?? 'null' }} }).catch(() => {});
    });

    let typingTimeout = null;
    let opponentTypingBubble = null;

    // Status online/last-seen Admin + apakah Admin sedang mengetik, di-poll tiap 2 detik.
    async function loadLiveStatus() {
        try {
            const res = await fetch('{{ route('messages.liveStatus') }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            const statusEl = document.getElementById('adminStatus');
            statusEl.textContent = data.typing
                ? 'Sedang mengetik...'
                : (data.online ? 'Online' : data.last_seen_label);
            statusEl.classList.toggle('text-navy-800', data.typing);
            statusEl.classList.toggle('italic', data.typing);
            statusEl.classList.toggle('text-green-600', !data.typing && data.online);
            statusEl.classList.toggle('text-slate-400', !data.typing && !data.online);

            renderTypingBubble(data.typing);
        } catch (err) {
            // Diamkan, coba lagi di polling berikutnya.
        }
    }

    function renderTypingBubble(isTyping) {
        if (isTyping && !opponentTypingBubble) {
            opponentTypingBubble = document.createElement('div');
            opponentTypingBubble.className = 'flex justify-start';
            opponentTypingBubble.innerHTML = `
                <div class="bg-slate-100 text-slate-400 text-sm rounded-2xl rounded-bl-sm px-4 py-2.5 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:300ms"></span>
                </div>`;
            messagesEl.appendChild(opponentTypingBubble);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        } else if (!isTyping && opponentTypingBubble) {
            opponentTypingBubble.remove();
            opponentTypingBubble = null;
        }
    }

    // Kirim sinyal "saya sedang mengetik" ke server, dibatasi tiap 1.5 detik saat mengetik.
    function notifyTyping() {
        if (typingTimeout) return;

        fetch('{{ route('messages.typing') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        }).catch(() => {});

        typingTimeout = setTimeout(() => { typingTimeout = null; }, 1500);
    }

    inputPesan.addEventListener('input', notifyTyping);

    function renderBubble(msg) {
        const isMine = msg.user_id === currentUserId;
        const bubble = document.createElement('div');
        bubble.className = isMine ? 'flex justify-end' : 'flex justify-start';
        bubble.innerHTML = isMine
            ? `<div class="max-w-md bg-navy-900 text-white text-sm rounded-2xl rounded-br-sm px-4 py-2.5"></div>`
            : `<div class="max-w-md bg-slate-100 text-slate-700 text-sm rounded-2xl rounded-bl-sm px-4 py-2.5"></div>`;
        bubble.firstElementChild.textContent = msg.pesan;
        messagesEl.appendChild(bubble);
    }

    async function loadMessages() {
        try {
            const res = await fetch('{{ route('messages.data') }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('Gagal memuat pesan');
            const data = await res.json();

            messagesEl.innerHTML = '';
            opponentTypingBubble = null; // elemen lama sudah ikut terhapus bareng innerHTML di atas
            if (data.length === 0) {
                messagesEl.innerHTML = '<p class="text-center text-xs text-slate-400">Belum ada pesan.</p>';
                return;
            }
            data.forEach(renderBubble);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        } catch (err) {
            messagesEl.innerHTML = '<p class="text-center text-xs text-red-500">Gagal memuat pesan.</p>';
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = form.pesan;
        if (!input.value.trim()) return;

        const pesanText = input.value;
        input.value = '';

        try {
            const res = await fetch('{{ route('messages.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ pesan: pesanText }),
            });
            if (!res.ok) throw new Error('Gagal mengirim pesan');
            const data = await res.json();
            renderBubble(data.message);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        } catch (err) {
            alert('Pesan gagal terkirim. Coba lagi.');
        }
    });

    loadMessages();
    // Polling sederhana tiap 5 detik. Ganti dengan Laravel Echo + broadcasting untuk real-time sebenarnya.
    setInterval(loadMessages, 5000);

    // Status online Admin + sedang mengetik, di-poll lebih sering (2 detik) biar responsif.
    loadLiveStatus();
    setInterval(loadLiveStatus, 2000);
</script>
@endpush
@endsection