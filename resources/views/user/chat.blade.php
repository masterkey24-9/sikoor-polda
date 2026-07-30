@extends('layouts.app')

@section('title', 'Live chat')
@section('page-title', 'Live chat')

@section('sidebar')
    @include('components.sidebar-user')
@endsection

@section('content')
<div class="bg-white rounded-xl border border-slate-200 flex flex-col h-[calc(100vh-8rem)] overflow-hidden">

    <div class="h-14 border-b border-slate-200 flex items-center px-5">
        <p class="text-sm font-medium text-slate-800">Admin Polda Sumbar</p>
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
    const currentUserId = {{ auth()->id() }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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
</script>
@endpush
@endsection
