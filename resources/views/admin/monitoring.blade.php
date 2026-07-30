@extends('layouts.app')

@section('title', 'Monitoring')
@section('page-title', 'Monitoring pengiriman')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    {{--
        $indicators diharapkan dikirim dari controller (Indicator::with('satker')->latest()->get())
        Data contoh di bawah cuma fallback kalau belum dikirim dari backend.
    --}}
    @php
        $list = $indicators ?? collect([
            (object)['id' => 1, 'judul' => 'Laporan Triwulan I', 'satker_nama' => 'Polres Padang', 'tenggat_waktu' => '2026-08-15', 'status' => 'pending'],
            (object)['id' => 2, 'judul' => 'Surat Perintah Tugas 08', 'satker_nama' => 'Polres Bukittinggi', 'tenggat_waktu' => '2026-08-05', 'status' => 'terkirim'],
        ]);
        $totalIndicator = count($list);
        $totalTerkirim = collect($list)->where('status', 'terkirim')->count();
        $totalPending = $totalIndicator - $totalTerkirim;
    @endphp

    {{-- Ringkasan --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Total indicator</p>
            <p class="text-2xl font-display font-semibold text-navy-900">{{ $totalIndicator }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Laporan diterima</p>
            <p class="text-2xl font-display font-semibold text-emerald-600">{{ $totalTerkirim }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Menunggu satker</p>
            <p class="text-2xl font-display font-semibold text-amber-600">{{ $totalPending }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="flex items-center gap-3 mb-4">
        <input type="text" id="searchInput" placeholder="Cari judul atau nama satker..."
               class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-navy-800">
    </div>

    {{-- Tabel data terbaru (indicator yang sudah dibuat/di-upload) --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-left text-slate-500 border-b border-slate-200">
                    <th class="px-5 py-3 font-medium">Judul indicator</th>
                    <th class="px-5 py-3 font-medium">Satker tujuan</th>
                    <th class="px-5 py-3 font-medium">Tenggat waktu</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse ($list as $item)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50 row-item"
                        data-search="{{ strtolower($item->judul . ' ' . ($item->satker_nama ?? '')) }}">
                        <td class="px-5 py-3.5 flex items-center gap-2.5">
                            <i class="ti ti-file-type-pdf text-red-500 text-lg"></i>
                            {{ $item->judul }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $item->satker_nama ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-slate-500">
                            {{ \Carbon\Carbon::parse($item->tenggat_waktu)->translatedFormat('d M Y') }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if ($item->status === 'terkirim')
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                                    <i class="ti ti-check text-sm"></i> Diterima
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">
                                    <i class="ti ti-clock text-sm"></i> Menunggu
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-8 text-center text-slate-400">Belum ada indicator dibuat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection

@push('scripts')
<script>
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', () => {
        const keyword = searchInput.value.toLowerCase();
        document.querySelectorAll('.row-item').forEach(row => {
            row.style.display = row.dataset.search.includes(keyword) ? '' : 'none';
        });
    });
</script>
@endpush
