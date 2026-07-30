@extends('layouts.app')

@section('title', 'Live chat')
@section('page-title', 'Live chat')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')
<div class="bg-white rounded-xl border border-slate-200 flex h-[calc(100vh-8rem)] overflow-hidden">

    {{-- Daftar satker --}}
    <div class="w-72 shrink-0 border-r border-slate-200 flex flex-col">
        <div class="p-3 border-b border-slate-200">
            <input type="text" id="searchSatker" placeholder="Cari satker..."
                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
        </div>
        <div class="flex-1 overflow-y-auto" id="satkerList">
            @forelse ($satkers as $i => $satker)
                <button type="button"
                        class="satker-item w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 border-b border-slate-100 {{ $i === 0 ? 'bg-slate-50' : '' }}"
                        data-satker-id="{{ $satker->id }}"
                        data-satker-nama="{{ $satker->nama_satker }}">
                    <div class="w-9 h-9 rounded-full bg-navy-900 text-white flex items-center justify-center text-xs font-medium shrink-0">
                        {{ strtoupper(substr($satker->nama_satker, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $satker->nama_satker }}</p>
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
        <div class="h-14 border-b border-slate-200 flex items-center px-5">
            <p class="text-sm font-medium text-slate-800" id="activeSatkerName">Pilih Satker di sebelah kiri</p>
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

    // Otomatis buka thread sesuai ?satker_id= di URL (misal dari klik notifikasi),
    // atau thread Satker pertama kalau tidak ada.
    const urlParams = new URLSearchParams(window.location.search);
    const requestedSatkerId = urlParams.get('satker_id');
    const requestedBtn = requestedSatkerId
        ? document.querySelector(`.satker-item[data-satker-id="${requestedSatkerId}"]`)
        : null;

    const initialBtn = requestedBtn || document.querySelector('.satker-item');
    if (initialBtn) {
        selectSatker(initialBtn.dataset.satkerId, initialBtn.dataset.satkerNama, initialBtn);
    }
</script>
@endpush
@endsection
