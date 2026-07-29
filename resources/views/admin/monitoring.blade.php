@extends('layouts.app')

@section('title', 'Monitoring')
@section('page-title', 'Monitoring pengiriman')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    {{-- Ringkasan --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Total dokumen</p>
            <p class="text-2xl font-display font-semibold text-navy-900">{{ $totalDokumen ?? 128 }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Terkirim</p>
            <p class="text-2xl font-display font-semibold text-emerald-600">{{ $terkirim ?? 112 }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Menunggu</p>
            <p class="text-2xl font-display font-semibold text-amber-600">{{ $pending ?? 16 }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="flex items-center gap-3 mb-4">
        <input type="text" placeholder="Cari nama dokumen atau satker..."
               class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-navy-800">
        <select class="h-10 px-3 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
            <option>Semua status</option>
            <option>Terkirim</option>
            <option>Menunggu</option>
        </select>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-left text-slate-500 border-b border-slate-200">
                    <th class="px-5 py-3 font-medium">Dokumen</th>
                    <th class="px-5 py-3 font-medium">Satker tujuan</th>
                    <th class="px-5 py-3 font-medium">Tanggal kirim</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                {{-- Ganti dengan @foreach($dokumen as $d) dari controller --}}
                @foreach ([
                    ['nama' => 'Surat Perintah Tugas 08.pdf', 'satker' => 'Polres Padang', 'tanggal' => '27 Jul 2026', 'status' => 'terkirim'],
                    ['nama' => 'Laporan Operasi Ketupat.pdf', 'satker' => 'Polres Bukittinggi', 'tanggal' => '27 Jul 2026', 'status' => 'terkirim'],
                    ['nama' => 'Instruksi Pengamanan.pdf', 'satker' => 'Polres Payakumbuh', 'tanggal' => '26 Jul 2026', 'status' => 'pending'],
                ] as $d)
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-5 py-3.5 flex items-center gap-2.5">
                            <i class="ti ti-file-type-pdf text-red-500 text-lg"></i>
                            {{ $d['nama'] }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $d['satker'] }}</td>
                        <td class="px-5 py-3.5 text-slate-500">{{ $d['tanggal'] }}</td>
                        <td class="px-5 py-3.5">
                            @if ($d['status'] === 'terkirim')
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                                    <i class="ti ti-check text-sm"></i> Terkirim
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">
                                    <i class="ti ti-clock text-sm"></i> Menunggu
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endsection
