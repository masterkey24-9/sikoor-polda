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
            <input type="text" placeholder="Cari satker..."
                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
        </div>
        <div class="flex-1 overflow-y-auto">
            @foreach ([
                ['nama' => 'Polres Padang', 'pesan' => 'Baik, file sudah kami terima.', 'unread' => 0],
                ['nama' => 'Polres Bukittinggi', 'pesan' => 'Mohon konfirmasi dokumen tadi.', 'unread' => 2],
                ['nama' => 'Polres Payakumbuh', 'pesan' => 'Siap laksanakan.', 'unread' => 0],
            ] as $i => $c)
                <button class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 border-b border-slate-100 {{ $i === 0 ? 'bg-slate-50' : '' }}">
                    <div class="w-9 h-9 rounded-full bg-navy-900 text-white flex items-center justify-center text-xs font-medium shrink-0">
                        {{ strtoupper(substr($c['nama'], 7, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $c['nama'] }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $c['pesan'] }}</p>
                    </div>
                    @if ($c['unread'] > 0)
                        <span class="w-5 h-5 rounded-full bg-gold-500 text-navy-950 text-[11px] font-medium flex items-center justify-center shrink-0">{{ $c['unread'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Jendela chat --}}
    <div class="flex-1 flex flex-col min-w-0">
        <div class="h-14 border-b border-slate-200 flex items-center px-5">
            <p class="text-sm font-medium text-slate-800">Polres Padang</p>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-3" id="chatMessages">
            <div class="flex justify-start">
                <div class="max-w-md bg-slate-100 text-slate-700 text-sm rounded-2xl rounded-bl-sm px-4 py-2.5">
                    Selamat siang, mohon informasi terkait dokumen yang baru dikirim.
                </div>
            </div>
            <div class="flex justify-end">
                <div class="max-w-md bg-navy-900 text-white text-sm rounded-2xl rounded-br-sm px-4 py-2.5">
                    Siang, dokumen sudah kami kirim pukul 10.00. Mohon dicek kembali.
                </div>
            </div>
            <div class="flex justify-start">
                <div class="max-w-md bg-slate-100 text-slate-700 text-sm rounded-2xl rounded-bl-sm px-4 py-2.5">
                    Baik, file sudah kami terima.
                </div>
            </div>
        </div>

        <form class="border-t border-slate-200 p-3 flex items-center gap-2" id="chatForm">
            <input type="text" name="pesan" placeholder="Tulis pesan..."
                   class="flex-1 h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
            <button type="submit" class="w-10 h-10 rounded-lg bg-navy-900 hover:bg-navy-800 text-white flex items-center justify-center shrink-0">
                <i class="ti ti-send text-lg"></i>
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Kerangka sederhana. Ganti dengan Laravel Echo + broadcasting saat backend siap:
    // Echo.private('chat.' + satkerId).listen('MessageSent', (e) => { ...append pesan... });
    const form = document.getElementById('chatForm');
    const messages = document.getElementById('chatMessages');

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const input = form.pesan;
        if (!input.value.trim()) return;

        const bubble = document.createElement('div');
        bubble.className = 'flex justify-end';
        bubble.innerHTML = `<div class="max-w-md bg-navy-900 text-white text-sm rounded-2xl rounded-br-sm px-4 py-2.5">${input.value}</div>`;
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        input.value = '';

        // TODO: kirim ke backend, misal fetch('/admin/chat/send', { method: 'POST', body: ... })
    });
</script>
@endpush
@endsection
