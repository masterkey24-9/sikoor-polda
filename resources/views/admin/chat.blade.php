@extends('layouts.app')

@section('title', 'Live chat')
@section('page-title', 'Live chat')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

<a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-navy-800 hover:underline mb-4">
    <i class="ti ti-arrow-left text-base"></i> Kembali ke monitoring
</a>

<div class="bg-white rounded-xl border border-slate-200 flex h-[calc(100vh-10.5rem)] overflow-hidden">

    {{-- Daftar satker --}}
    <div class="w-72 shrink-0 border-r border-slate-200 flex flex-col">
        <div class="p-3 border-b border-slate-200 flex items-center gap-2">
            <input type="text" id="searchSatker" placeholder="Cari satker..."
                   class="flex-1 h-9 px-3 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
            <button type="button" id="openBroadcastBtn" title="Kirim pesan ke semua satker"
                    class="w-9 h-9 shrink-0 rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50 hover:text-navy-900 flex items-center justify-center">
                <i class="ti ti-speakerphone text-lg"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto" id="satkerList">
            @forelse ($satkers as $i => $satker)
                <button type="button"
                        class="satker-item w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 border-b border-slate-100 {{ $i === 0 ? 'bg-slate-50' : '' }}"
                        data-satker-id="{{ $satker->id }}"
                        data-satker-nama="{{ $satker->nama_satker }}">
                    <div class="relative shrink-0">
                        <div class="w-9 h-9 rounded-full bg-navy-900 text-white flex items-center justify-center text-xs font-medium">
                            {{ strtoupper(substr($satker->nama_satker, 0, 2)) }}
                        </div>
                        {{-- Titik status online, diupdate oleh JS (polling online-status) --}}
                        <span class="status-dot absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white bg-slate-300"
                              data-status-dot="{{ $satker->id }}"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $satker->nama_satker }}</p>
                            <p class="text-[10px] text-slate-400 shrink-0 status-label" data-status-label="{{ $satker->id }}">&nbsp;</p>
                        </div>
                        <p class="text-xs text-slate-400 truncate">{{ $satker->last_pesan }}</p>
                    </div>
                </button>
            @empty
                <p class="text-center text-xs text-slate-400 p-4">Belum ada data Satker.</p>
            @endforelse
        </div>
    </div>

    {{-- Jendela chat --}}
    <div class="flex-1 flex flex-col min-w-0">
        <div class="h-14 border-b border-slate-200 flex flex-col justify-center px-5">
            <p class="text-sm font-medium text-slate-800" id="activeSatkerName">Pilih Satker di sebelah kiri</p>
            <p class="text-xs text-slate-400" id="activeSatkerStatus">&nbsp;</p>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-3" id="chatMessages">
            <p class="text-center text-xs text-slate-400">Pilih Satker untuk mulai percakapan.</p>
        </div>

        <form class="border-t border-slate-200 p-3 flex items-center gap-2" id="chatForm">
            <input type="text" name="pesan" placeholder="Tulis pesan..." disabled
                   class="flex-1 h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 disabled:bg-slate-50">
            <button type="submit" disabled class="w-10 h-10 rounded-lg bg-navy-900 hover:bg-navy-800 text-white flex items-center justify-center shrink-0 disabled:opacity-50">
                <i class="ti ti-send text-lg"></i>
            </button>
        </form>
    </div>
</div>

{{-- Modal: kirim pesan ke semua satker sekaligus --}}
<div id="broadcastModal" class="hidden fixed inset-0 bg-slate-900/40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                <i class="ti ti-speakerphone text-lg text-navy-800"></i>
                Kirim ke Semua Satker
            </p>
            <button type="button" id="closeBroadcastBtn" class="text-slate-400 hover:text-slate-600">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>
        <div class="p-5">
            <p class="text-xs text-slate-500 mb-3">
                Pesan ini akan dikirim ke thread chat <span class="font-medium text-slate-700">semua {{ $satkers->count() }} satker</span> sekaligus.
            </p>
            <textarea id="broadcastText" rows="4" placeholder="Tulis pengumuman..."
                      class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 resize-none"></textarea>
            <p id="broadcastError" class="hidden text-xs text-red-500 mt-2"></p>
        </div>
        <div class="px-5 py-4 border-t border-slate-200 flex justify-end gap-2">
            <button type="button" id="cancelBroadcastBtn" class="px-4 h-9 rounded-lg text-sm text-slate-600 hover:bg-slate-50">
                Batal
            </button>
            <button type="button" id="sendBroadcastBtn" class="px-4 h-9 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium flex items-center gap-1.5">
                <i class="ti ti-send text-base"></i> Kirim ke Semua
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Kerangka sederhana. Ganti dengan Laravel Echo + broadcasting saat backend siap:
    // Echo.channel('chat.' + satkerId).listen('.message.sent', (e) => { ...append pesan... });
    const form = document.getElementById('chatForm');
    const messagesEl = document.getElementById('chatMessages');
    const activeSatkerName = document.getElementById('activeSatkerName');
    const inputPesan = form.pesan;
    const submitBtn = form.querySelector('button[type="submit"]');
    const currentUserId = {{ auth()->id() }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let activeSatkerId = null;
    let pollTimer = null;
    let onlineStatusTimer = null;
    let liveStatusTimer = null;
    let typingTimeout = null;
    let opponentTypingBubble = null;

    // ===== 1. Status "terakhir online" & "sedang online" untuk SEMUA satker (sidebar) =====
    async function loadOnlineStatusAll() {
        try {
            const res = await fetch(`{{ route('messages.onlineStatus') }}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            data.forEach(item => {
                const dot = document.querySelector(`[data-status-dot="${item.satker_id}"]`);
                const label = document.querySelector(`[data-status-label="${item.satker_id}"]`);

                if (dot) {
                    dot.classList.toggle('bg-green-500', item.online);
                    dot.classList.toggle('bg-slate-300', !item.online);
                }
                if (label) {
                    label.textContent = item.online ? 'Online' : item.last_seen_label.replace('Terakhir online ', '');
                    label.classList.toggle('text-green-600', item.online);
                    label.classList.toggle('text-slate-400', !item.online);
                }
            });
        } catch (err) {
            // Diamkan saja, coba lagi di polling berikutnya.
        }
    }

    // ===== 2. Status live thread yang sedang dibuka: online lawan bicara + sedang mengetik =====
    async function loadLiveStatus() {
        if (!activeSatkerId) return;

        try {
            const res = await fetch(`{{ route('messages.liveStatus') }}?satker_id=${activeSatkerId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            const statusEl = document.getElementById('activeSatkerStatus');
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

    // ===== 3. Kirim sinyal "saya sedang mengetik" ke server (di-debounce, dikirim tiap 1.5 detik saat mengetik) =====
    function notifyTyping() {
        if (!activeSatkerId) return;
        if (typingTimeout) return; // sudah baru saja kirim, tunggu jeda

        fetch('{{ route('messages.typing') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ satker_id: activeSatkerId }),
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
        if (!activeSatkerId) return;

        try {
            const res = await fetch(`{{ route('messages.data') }}?satker_id=${activeSatkerId}`, {
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

    function selectSatker(id, nama, btnEl) {
        activeSatkerId = id;
        activeSatkerName.textContent = nama;
        inputPesan.disabled = false;
        submitBtn.disabled = false;

        document.querySelectorAll('.satker-item').forEach(el => el.classList.remove('bg-slate-50'));
        if (btnEl) btnEl.classList.add('bg-slate-50');

        messagesEl.innerHTML = '<p class="text-center text-xs text-slate-400">Memuat pesan...</p>';
        loadMessages();

        if (pollTimer) clearInterval(pollTimer);
        // Polling sederhana tiap 5 detik. Ganti dengan Laravel Echo + broadcasting untuk real-time sebenarnya.
        pollTimer = setInterval(loadMessages, 5000);

        // Status online + sedang mengetik untuk thread yang baru dibuka, di-poll lebih sering (2 detik)
        // supaya balon "sedang mengetik..." terasa responsif.
        if (liveStatusTimer) clearInterval(liveStatusTimer);
        loadLiveStatus();
        liveStatusTimer = setInterval(loadLiveStatus, 2000);
    }

    document.querySelectorAll('.satker-item').forEach(btn => {
        btn.addEventListener('click', () => {
            selectSatker(btn.dataset.satkerId, btn.dataset.satkerNama, btn);
        });
    });

    document.getElementById('searchSatker').addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('.satker-item').forEach(btn => {
            const match = btn.dataset.satkerNama.toLowerCase().includes(q);
            btn.classList.toggle('hidden', !match);
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!activeSatkerId || !inputPesan.value.trim()) return;

        const pesanText = inputPesan.value;
        inputPesan.value = '';

        try {
            const res = await fetch('{{ route('messages.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ pesan: pesanText, satker_id: activeSatkerId }),
            });
            if (!res.ok) throw new Error('Gagal mengirim pesan');
            const data = await res.json();
            renderBubble(data.message);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        } catch (err) {
            alert('Pesan gagal terkirim. Coba lagi.');
        }
    });

    // ===== 4. Kirim pesan ke SEMUA satker sekaligus (broadcast) =====
    const broadcastModal = document.getElementById('broadcastModal');
    const openBroadcastBtn = document.getElementById('openBroadcastBtn');
    const closeBroadcastBtn = document.getElementById('closeBroadcastBtn');
    const cancelBroadcastBtn = document.getElementById('cancelBroadcastBtn');
    const sendBroadcastBtn = document.getElementById('sendBroadcastBtn');
    const broadcastText = document.getElementById('broadcastText');
    const broadcastError = document.getElementById('broadcastError');

    function openBroadcastModal() {
        broadcastText.value = '';
        broadcastError.classList.add('hidden');
        broadcastModal.classList.remove('hidden');
        broadcastText.focus();
    }

    function closeBroadcastModal() {
        broadcastModal.classList.add('hidden');
    }

    openBroadcastBtn.addEventListener('click', openBroadcastModal);
    closeBroadcastBtn.addEventListener('click', closeBroadcastModal);
    cancelBroadcastBtn.addEventListener('click', closeBroadcastModal);
    broadcastModal.addEventListener('click', (e) => {
        if (e.target === broadcastModal) closeBroadcastModal();
    });

    sendBroadcastBtn.addEventListener('click', async () => {
        const pesanText = broadcastText.value.trim();
        if (!pesanText) {
            broadcastError.textContent = 'Pesan tidak boleh kosong.';
            broadcastError.classList.remove('hidden');
            return;
        }

        sendBroadcastBtn.disabled = true;
        sendBroadcastBtn.classList.add('opacity-60');

        try {
            const res = await fetch('{{ route('messages.broadcast') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ pesan: pesanText }),
            });
            if (!res.ok) throw new Error('Gagal mengirim broadcast');
            const data = await res.json();

            // Update pratinjau pesan terakhir di semua item satker pada sidebar.
            document.querySelectorAll('.satker-item').forEach(btn => {
                const preview = btn.querySelector('p.text-xs.text-slate-400.truncate');
                if (preview) preview.textContent = data.pesan;
            });

            // Kalau thread yang lagi dibuka termasuk yang dikirimi, refresh isinya.
            if (activeSatkerId) loadMessages();

            closeBroadcastModal();
        } catch (err) {
            broadcastError.textContent = 'Gagal mengirim pesan. Coba lagi.';
            broadcastError.classList.remove('hidden');
        } finally {
            sendBroadcastBtn.disabled = false;
            sendBroadcastBtn.classList.remove('opacity-60');
        }
    });

    // Otomatis buka thread Satker pertama saat halaman dimuat (jika ada).
    const firstBtn = document.querySelector('.satker-item');
    if (firstBtn) {
        selectSatker(firstBtn.dataset.satkerId, firstBtn.dataset.satkerNama, firstBtn);
    }

    // Status online/last-seen semua satker di sidebar: dimuat langsung, lalu di-poll tiap 5 detik.
    loadOnlineStatusAll();
    onlineStatusTimer = setInterval(loadOnlineStatusAll, 5000);
</script>
@endpush
@endsection
