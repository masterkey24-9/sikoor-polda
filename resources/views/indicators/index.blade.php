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
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Buat indicator baru + pilih satker tujuan --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 max-w-xl">
        <p class="text-sm font-medium text-slate-700 mb-4">Buat indicator baru</p>

        <form method="POST" action="{{ route('indicators.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="judul" class="block text-sm font-medium text-slate-700 mb-1.5">Judul</label>
                <input type="text" id="judul" name="judul" required maxlength="255"
                       placeholder="Contoh: Laporan Triwulan I"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi (opsional)</label>
                <textarea id="deskripsi" name="deskripsi" rows="3"
                          placeholder="Detail tugas/laporan yang diminta..."
                          class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 resize-none"></textarea>
            </div>

            <div>
                <label for="tenggat_waktu" class="block text-sm font-medium text-slate-700 mb-1.5">Tenggat waktu</label>
                <input type="date" id="tenggat_waktu" name="tenggat_waktu" required
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
            </div>

            <div>
                <label for="file_pdf" class="block text-sm font-medium text-slate-700 mb-1.5">Lampiran PDF (opsional)</label>
                <input type="file" id="file_pdf" name="file_pdf" accept="application/pdf"
                       class="w-full text-sm file:mr-3 file:h-9 file:px-3 file:rounded-lg file:border-0 file:bg-navy-900 file:text-white file:text-sm">
                <p class="text-xs text-slate-400 mt-1">Kirim dokumen pendukung langsung ke satker, maksimal 10MB.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Kirim ke satker (bisa pilih lebih dari satu)</label>
                <div class="border border-slate-300 rounded-lg p-3 max-h-48 overflow-y-auto space-y-2">
                    @foreach ($satkers ?? [] as $satker)
                        <label class="flex items-center gap-2.5 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" name="satker_id[]" value="{{ $satker->id }}"
                                   class="w-4 h-4 rounded border-slate-300 text-navy-800 focus:ring-navy-800">
                            {{ $satker->nama_satker }}
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-slate-400 mt-1">Pilih minimal 1 satker tujuan.</p>
            </div>

            <button type="submit"
                    class="h-11 px-5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                Buat & kirim indicator
            </button>