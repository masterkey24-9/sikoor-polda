@extends('layouts.app')

@section('title', 'Detail indicator')
@section('page-title', 'Detail indicator')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

<a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-navy-800 hover:underline mb-4">
    <i class="ti ti-arrow-left text-base"></i> Kembali ke monitoring
</a>

<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 max-w-2xl">
    <p class="text-lg font-display font-semibold text-navy-900">{{ $indicator->judul }}</p>
    @if ($indicator->deskripsi)
        <p class="text-sm text-slate-500 mt-1">{{ $indicator->deskripsi }}</p>
    @endif

    <div class="flex items-center gap-6 mt-4 text-sm">
        <div>
            <p class="text-slate-400 text-xs">Satker tujuan</p>
            <p class="text-slate-700 font-medium">{{ $indicator->satker->nama_satker ?? '-' }}</p>
        </div>
        <div>
            <p class="text-slate-400 text-xs">Tenggat waktu</p>
            <p class="text-slate-700 font-medium">{{ \Carbon\Carbon::parse($indicator->tenggat_waktu)->translatedFormat('d M Y') }}</p>
        </div>
    </div>
</div>

<p class="text-sm font-medium text-slate-700 mb-3">Laporan yang diterima</p>

<div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-2xl">
    @forelse ($indicator->results as $result)
        <div class="flex items-center gap-4 px-5 py-4">
            <i class="ti ti-file-type-pdf text-red-500 text-lg shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800">{{ $result->satker->nama_satker ?? '-' }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    Dikirim {{ $result->created_at->translatedFormat('d M Y, H:i') }}
                </p>
            </div>
            <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 shrink-0">
                {{ ucfirst($result->status) }}
            </span>
            <a href="{{ asset('storage/' . $result->file_pdf) }}" target="_blank"
               class="h-9 px-3.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 flex items-center gap-1.5 shrink-0">
                <i class="ti ti-eye text-base"></i> Lihat file
            </a>
        </div>
    @empty
        <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada laporan yang diunggah satker.</p>
    @endforelse
</div>

@endsection
