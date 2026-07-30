@extends('layouts.app')

@section('title', 'Indicators')
@section('page-title', 'Indicators')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tombol menuju halaman kirim dokumen --}}
    <div class="flex justify-end mb-4">
        <a href="{{ route('indicators.create') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium">
            <i class="ti ti-plus text-base"></i> Kirim dokumen baru
        </a>
    </div>

    {{-- Daftar indicators --}}
    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
        {{-- Ganti dengan @foreach($indicators as $indicator) dari controller --}}
        @foreach ($indicators ?? [
            (object)['id' => 1, 'name' => 'Laporan Triwulan I', 'status' => 'pending'],
            (object)['id' => 2, 'name' => 'Surat Perintah Tugas 08', 'status' => 'terkirim'],
            (object)['id' => 3, 'name' => 'Instruksi Pengamanan', 'status' => 'pending'],
        ] as $indicator)
            <div class="flex items-center gap-4 px-5 py-4">
                <i class="ti ti-file-type-pdf text-red-500 text-lg shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $indicator->name }}</p>
                    @if (isset($indicator->satker_nama))
                        <p class="text-xs text-slate-400 mt-0.5">Tujuan: {{ $indicator->satker_nama }}</p>
                    @endif
                </div>

                @if ($indicator->status === 'terkirim')
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 shrink-0">
                        <i class="ti ti-check text-sm"></i> Terkirim
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 shrink-0">
                        <i class="ti ti-clock text-sm"></i> Menunggu
                    </span>
                @endif
            </div>
        @endforeach
    </div>

@endsection
