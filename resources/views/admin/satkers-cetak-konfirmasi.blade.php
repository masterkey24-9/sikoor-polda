@extends('layouts.app')

@section('title', 'Cetak Kredensial Satker')
@section('page-title', 'Cetak Kredensial Satker')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    <a href="{{ route('satkers.index') }}" class="inline-flex items-center gap-1.5 text-sm text-navy-800 hover:underline mb-4">
        <i class="ti ti-arrow-left text-base"></i> Kembali ke Kelola Satker
    </a>

    <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-xl">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i class="ti ti-alert-triangle text-lg"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-800">Ini akan mengganti password SEMUA satker</p>
                <p class="text-xs text-slate-500 mt-1">
                    Password lama tidak bisa ditampilkan lagi (tersimpan terenkripsi). Satu-satunya cara mencetak
                    daftar lengkap Nama Satker + Username + Password adalah dengan membuat password <strong>baru</strong>
                    untuk semua akun ({{ $totalSatker }} satker). Password lama otomatis <strong>tidak berlaku lagi</strong>
                    setelah ini, satker harus pakai password baru dari hasil cetak ini.
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('satkers.cetakKredensial') }}">
            @csrf

            <label class="flex items-start gap-2.5 text-sm text-slate-700 mb-5 cursor-pointer">
                <input type="checkbox" name="konfirmasi" value="1" required
                       class="mt-0.5 w-4 h-4 rounded border-slate-300 text-navy-800 focus:ring-navy-800">
                Saya paham ini akan mengganti password SEMUA satker ({{ $totalSatker }} akun), dan password lama tidak
                akan bisa dipakai lagi setelah ini.
            </label>

            <button type="submit"
                    class="h-11 px-5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                Reset & Cetak Kredensial Semua Satker
            </button>
        </form>
    </div>

@endsection
