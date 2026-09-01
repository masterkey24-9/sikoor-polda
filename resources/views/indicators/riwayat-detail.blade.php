@extends('layouts.app')

@section('title', 'Detail Riwayat Pengiriman')
@section('page-title', 'Detail Pengiriman')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-sm font-medium text-slate-700">{{ $info->judul }}</p>
            <p class="text-xs text-slate-400 mt-0.5">
                Periode {{ $info->periode ? \Carbon\Carbon::parse($info->periode)->translatedFormat('F Y') : '-' }}
                &middot; Dikirim {{ $info->created_at?->translatedFormat('d M Y, H:i') }}
            </p>
        </div>
        <a href="{{ route('indicators.riwayat') }}"
           class="h-10 px-4 rounded-lg border border-slate-300 hover:bg-slate-50 text-sm font-medium text-slate-700 flex items-center gap-2 shrink-0">
            <i class="ti ti-arrow-left text-sm"></i> Kembali ke Riwayat
        </a>
    </div>

    @if ($info->deskripsi)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1.5">Deskripsi</p>
            <p class="text-sm text-slate-700">{{ $info->deskripsi }}</p>
        </div>
    @endif

    @if ($info->file_pdf || $info->file_excel)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5 flex items-center gap-4">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide shrink-0">Lampiran</p>
            @if ($info->file_pdf)
                <a href="{{ asset('storage/' . $info->file_pdf) }}" target="_blank"
                   class="text-sm text-navy-800 hover:underline flex items-center gap-1.5">
                    <i class="ti ti-file-type-pdf text-red-500"></i> File PDF
                </a>
            @endif
            @if ($info->file_excel)
                <a href="{{ asset('storage/' . $info->file_excel) }}" target="_blank"
                   class="text-sm text-navy-800 hover:underline flex items-center gap-1.5">
                    <i class="ti ti-file-type-xls text-emerald-600"></i> File Excel
                </a>
            @endif
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <p class="text-sm font-medium text-slate-700">Dikirim ke {{ $daftarSatker->count() }} satker</p>
            <p class="text-xs text-slate-400">
                {{ $daftarSatker->filter(fn ($s) => $s->latest_result)->count() }} sudah lapor
                &middot; {{ $daftarSatker->filter(fn ($s) => !$s->latest_result)->count() }} belum lapor
            </p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($daftarSatker as $s)
                @php $r = $s->latest_result; @endphp
                <a href="{{ route('indicators.show', $s->indicator_id) }}"
                   class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50">
                    <i class="ti ti-building-fortress text-slate-400 text-lg shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800">{{ $s->nama_satker }}</p>
                        @if ($r)
                            <p class="text-xs text-slate-400 mt-0.5">
                                Lapor {{ $r->created_at->translatedFormat('d M Y, H:i') }}
                                @if (!is_null($r->nilai))
                                    &middot; Nilai: <span class="font-medium text-navy-900">{{ $r->nilai }}</span>
                                @endif
                            </p>
                        @endif
                    </div>
                    @if (!$r)
                        <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">Belum lapor</span>
                    @elseif (is_null($r->nilai))
                        <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Menunggu dinilai</span>
                    @elseif ($r->status === 'diterima')
                        <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Diterima</span>
                    @else
                        <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Perlu direvisi</span>
                    @endif
                    <i class="ti ti-chevron-right text-slate-300 shrink-0"></i>
                </a>
            @endforeach
        </div>
    </div>

@endsection
