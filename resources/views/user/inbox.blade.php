@extends('layouts.app')

@section('title', 'Dokumen masuk')
@section('page-title', 'Dokumen masuk')

@section('sidebar')
    @include('components.sidebar-user')
@endsection

@section('content')

<div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
    {{-- Ganti dengan @foreach($dokumen as $d) dari controller --}}
    @foreach ([
        ['nama' => 'Surat Perintah Tugas 08.pdf', 'dari' => 'Admin Polda Sumbar', 'tanggal' => '27 Jul 2026', 'baru' => true],
        ['nama' => 'Laporan Operasi Ketupat.pdf', 'dari' => 'Admin Polda Sumbar', 'tanggal' => '25 Jul 2026', 'baru' => false],
        ['nama' => 'Instruksi Pengamanan.pdf', 'dari' => 'Admin Polda Sumbar', 'tanggal' => '22 Jul 2026', 'baru' => false],
    ] as $d)
        <div class="flex items-center gap-4 px-5 py-4">
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-file-type-pdf text-red-500 text-xl"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-800 truncate flex items-center gap-2">
                    {{ $d['nama'] }}
                    @if ($d['baru'])
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-gold-500/15 text-gold-500">Baru</span>
                    @endif
                </p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $d['dari'] }} &middot; {{ $d['tanggal'] }}</p>
            </div>
            <a href="#" class="w-9 h-9 rounded-lg border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 shrink-0" aria-label="Unduh {{ $d['nama'] }}">
                <i class="ti ti-download text-lg"></i>
            </a>
        </div>
    @endforeach
</div>

@endsection
