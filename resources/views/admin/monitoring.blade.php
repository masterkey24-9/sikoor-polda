@extends('layouts.app')

@section('title', 'Monitoring')
@section('page-title', 'Monitoring pengiriman')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    

    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-3 mb-4">
        <div>
            <label for="filterSatker" class="block text-xs font-medium text-slate-500 mb-1.5">Pilih Satker</label>
            <select id="filterSatker" name="satker_id"
                    class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-navy-800">
                <option value="">Semua satker</option>
                @foreach ($satkers ?? [] as $satker)
                    <option value="{{ $satker->id }}" @selected(request('satker_id') == $satker->id)>
                        {{ $satker->nama_satker }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filterPeriode" class="block text-xs font-medium text-slate-500 mb-1.5">Pilih Periode</label>
            <input type="month" id="filterPeriode" name="periode" value="{{ request('periode') }}"
                   class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
        </div>

        <button type="submit"
                class="h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
            Terapkan
        </button>
        @if (request('satker_id') || request('periode'))
            <a href="{{ route('dashboard') }}" class="h-10 px-4 rounded-lg border border-slate-300 text-sm text-slate-600 hover:bg-slate-50 flex items-center">
                Reset
            </a>
        @endif
    </form>

    <p class="text-sm font-medium text-slate-700 mb-3">Monitoring Kinerja Satker</p>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Total satker</p>
            <p class="text-2xl font-display font-semibold text-navy-900">{{ $totalSatker ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Rata-rata kinerja</p>
            <p class="text-2xl font-display font-semibold text-emerald-600">
                {{ !is_null($rataRataKinerja ?? null) ? number_format($rataRataKinerja, 1) . '%' : '-' }}
            </p>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm text-slate-500 mb-1">Perlu perhatian</p>
            <p class="text-2xl font-display font-semibold text-red-600">{{ $totalPerluPerhatian ?? 0 }}</p>
        </div>
    </div>

    <p class="text-sm font-medium text-slate-700 mb-3">Daftar Kinerja Satker</p>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-navy-950/[0.03] text-left text-slate-500 border-b border-slate-200">
                    <th class="px-5 py-3 font-medium">Satker</th>
                    <th class="px-5 py-3 font-medium">Nilai</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Progress</th>
                    <th class="px-5 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($satkerPerformance ?? [] as $sp)
                    @php
                        $badgeClass = match ($sp->status) {
                            'Baik' => 'bg-emerald-50 text-emerald-700',
                            'Cukup' => 'bg-gold-500/15 text-navy-900',
                            'Perlu Perhatian' => 'bg-red-50 text-red-700',
                            default => 'bg-slate-100 text-slate-500',
                        };
                        $barClass = match ($sp->status) {
                            'Baik' => 'bg-emerald-500',
                            'Cukup' => 'bg-gold-500',
                            'Perlu Perhatian' => 'bg-red-500',
                            default => 'bg-slate-300',
                        };
                    @endphp
                    <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                        <td class="px-5 py-3.5 text-slate-800 font-medium">{{ $sp->nama_satker }}</td>
                        <td class="px-5 py-3.5">
                            @if (!is_null($sp->nilai))
                                <span class="font-medium text-navy-900">{{ $sp->nilai }}%</span>
                                <span class="text-xs text-slate-400">({{ $sp->tugas_selesai }}/{{ $sp->total_tugas }} tugas)</span>
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full {{ $badgeClass }}">
                                {{ $sp->status }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="w-32 h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full {{ $barClass }}" style="width: {{ $sp->nilai ?? 0 }}%"></div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('dashboard', ['satker_id' => $sp->id]) }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-navy-900"
                               aria-label="Lihat detail {{ $sp->nama_satker }}">
                                <i class="ti ti-eye text-base"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-400">Belum ada data satker.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection