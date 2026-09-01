@extends('layouts.app')

@section('title', 'Riwayat Pengiriman')
@section('page-title', 'Riwayat Pengiriman Indikator')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-sm font-medium text-slate-700">Riwayat Pengiriman Indikator</p>
            <p class="text-xs text-slate-400 mt-0.5">Setiap baris adalah satu kali "Buat &amp; kirim indicator". Klik untuk lihat satker mana saja yang dituju dan status laporannya.</p>
        </div>
        <a href="{{ route('indicators.index') }}"
           class="h-10 px-4 rounded-lg border border-slate-300 hover:bg-slate-50 text-sm font-medium text-slate-700 flex items-center gap-2 shrink-0">
            <i class="ti ti-arrow-left text-sm"></i> Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs text-slate-400 border-b border-slate-100">
                    <th class="text-left font-medium px-6 py-3">Indikator</th>
                    <th class="text-left font-medium px-6 py-3">Periode</th>
                    <th class="text-left font-medium px-6 py-3">Dikirim pada</th>
                    <th class="text-left font-medium px-6 py-3">Tujuan</th>
                    <th class="text-left font-medium px-6 py-3">Progres Laporan</th>
                    <th class="text-right font-medium px-6 py-3">Lampiran</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($batches as $b)
                    @php
                        $persen = $b->total_satker > 0 ? round($b->sudah_lapor / $b->total_satker * 100) : 0;
                    @endphp
                    <tr class="hover:bg-slate-50 cursor-pointer"
                        onclick="window.location='{{ route('indicators.riwayat.detail', $b->batch_id) }}'">
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-800">{{ $b->judul }}</p>
                            @if ($b->deskripsi)
                                <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ $b->deskripsi }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            {{ $b->periode ? \Carbon\Carbon::parse($b->periode)->translatedFormat('F Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs">
                            {{ $b->dikirim_pada?->translatedFormat('d M Y, H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 text-slate-700">
                                <i class="ti ti-building-fortress text-slate-400"></i>
                                {{ $b->total_satker }} satker
                            </span>
                        </td>
                        <td class="px-6 py-4 min-w-[160px]">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $persen == 100 ? 'bg-emerald-500' : ($persen > 0 ? 'bg-amber-500' : 'bg-slate-300') }}"
                                         style="width: {{ max(2, $persen) }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-500 shrink-0">{{ $b->sudah_lapor }}/{{ $b->total_satker }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if ($b->file_pdf)
                                <i class="ti ti-file-type-pdf text-red-500 text-lg" title="Ada lampiran PDF"></i>
                            @endif
                            @if ($b->file_excel)
                                <i class="ti ti-file-type-xls text-emerald-600 text-lg" title="Ada lampiran Excel"></i>
                            @endif
                            <i class="ti ti-chevron-right text-slate-300 ml-2"></i>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-400 text-sm">
                            Belum ada riwayat pengiriman indicator.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
