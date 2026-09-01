@extends('layouts.app')

@section('title', 'Monitoring IKPA')
@section('page-title', 'Monitoring IKPA')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-navy-800 hover:underline mb-4">
        <i class="ti ti-arrow-left text-base"></i> Kembali ke Dashboard
    </a>

    @php
        $granularitas = $granularitas ?? 'bulanan';
        $tahunAktif = $tahunAktif ?? now()->year;
        $triwulanAktif = $triwulanAktif ?? ceil(now()->month / 3);
        $semesterAktif = $semesterAktif ?? (now()->month <= 6 ? 1 : 2);
        $tahunOpsi = range(now()->year, now()->year - 5);
    @endphp

    <form method="GET" action="{{ route('monitoring.ikpa') }}" class="flex flex-wrap items-end gap-3 mb-3">
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
            <label for="filterGranularitas" class="block text-xs font-medium text-slate-500 mb-1.5">Tampilan Periode</label>
            <select id="filterGranularitas" name="granularitas" onchange="toggleFilterPeriode(this.value)"
                    class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                <option value="bulanan" @selected($granularitas === 'bulanan')>Bulanan</option>
                <option value="triwulan" @selected($granularitas === 'triwulan')>Triwulan</option>
                <option value="semester" @selected($granularitas === 'semester')>Semester</option>
                <option value="tahunan" @selected($granularitas === 'tahunan')>Tahunan</option>
            </select>
        </div>

        {{-- Mode Bulanan --}}
        <div id="filterWrapBulanan" class="{{ $granularitas === 'bulanan' ? '' : 'hidden' }}">
            <label for="filterPeriode" class="block text-xs font-medium text-slate-500 mb-1.5">Pilih Bulan</label>
            <input type="month" id="filterPeriode" name="periode"
                   value="{{ isset($periodeAktif) ? $periodeAktif->format('Y-m') : now()->format('Y-m') }}"
                   class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
        </div>

        {{-- Mode Triwulan --}}
        <div id="filterWrapTriwulan" class="{{ $granularitas === 'triwulan' ? 'flex items-end gap-2' : 'hidden' }}">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Tahun</label>
                <select name="tahun" class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                    @foreach ($tahunOpsi as $th)
                        <option value="{{ $th }}" @selected($tahunAktif == $th)>{{ $th }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Triwulan</label>
                <select name="triwulan" class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                    <option value="1" @selected($triwulanAktif == 1)>TW 1 (Jan - Mar)</option>
                    <option value="2" @selected($triwulanAktif == 2)>TW 2 (Apr - Jun)</option>
                    <option value="3" @selected($triwulanAktif == 3)>TW 3 (Jul - Sep)</option>
                    <option value="4" @selected($triwulanAktif == 4)>TW 4 (Okt - Des)</option>
                </select>
            </div>
        </div>

        {{-- Mode Semester --}}
        <div id="filterWrapSemester" class="{{ $granularitas === 'semester' ? 'flex items-end gap-2' : 'hidden' }}">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Tahun</label>
                <select name="tahun" class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                    @foreach ($tahunOpsi as $th)
                        <option value="{{ $th }}" @selected($tahunAktif == $th)>{{ $th }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Semester</label>
                <select name="semester" class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                    <option value="1" @selected($semesterAktif == 1)>Semester 1 (Jan - Jun)</option>
                    <option value="2" @selected($semesterAktif == 2)>Semester 2 (Jul - Des)</option>
                </select>
            </div>
        </div>

        {{-- Mode Tahunan --}}
        <div id="filterWrapTahunan" class="{{ $granularitas === 'tahunan' ? '' : 'hidden' }}">
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Tahun</label>
            <select name="tahun" class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                @foreach ($tahunOpsi as $th)
                    <option value="{{ $th }}" @selected($tahunAktif == $th)>{{ $th }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit"
                class="h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
            Terapkan
        </button>
        @if (request('satker_id') || request('periode') || request('granularitas') || request('tahun') || request('triwulan') || request('semester'))
            <a href="{{ route('monitoring.ikpa') }}" class="h-10 px-4 rounded-lg border border-slate-300 text-sm text-slate-600 hover:bg-slate-50 flex items-center">
                Reset ke bulan berjalan
            </a>
        @endif
    </form>

    <p class="text-xs text-slate-500 mb-6">
        Menampilkan data periode:
        <span class="font-medium text-slate-700">{{ $labelPeriodeAktif ?? (isset($periodeAktif) ? $periodeAktif->translatedFormat('F Y') : now()->translatedFormat('F Y')) }}</span>
        @if (request()->filled('satker_id'))
            &middot; Satker:
            <span class="font-medium text-slate-700">
                {{ ($satkers ?? collect())->firstWhere('id', request('satker_id'))->nama_satker ?? '-' }}
            </span>
        @else
            &middot; <span class="font-medium text-slate-700">Semua satker</span>
        @endif
    </p>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-700 mb-3">Monitoring IKPA Terbaru</p>
        <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
            <table class="w-full text-sm min-w-[1180px]">
                <thead class="sticky top-0 bg-white">
                    <tr class="text-xs text-slate-400 border-b border-slate-100">
                        <th class="text-left font-medium pb-2 w-8">No</th>
                        <th class="text-left font-medium pb-2">Satker</th>
                        <th class="text-right font-medium pb-2">Nilai IKPA</th>
                        <th class="text-left font-medium pb-2 pl-4">Kategori</th>
                        <th class="text-right font-medium pb-2">% Penyerapan Anggaran</th>
                        <th class="text-right font-medium pb-2">Deviasi Hal. III DIPA</th>
                        <th class="text-right font-medium pb-2">Penyelesaian Tagihan</th>
                        <th class="text-right font-medium pb-2">Belanja Kontraktual</th>
                        <th class="text-right font-medium pb-2">Pengelolaan UP/TUP</th>
                        <th class="text-left font-medium pb-2 pl-4">Update Terakhir</th>
                        <th class="text-center font-medium pb-2">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse (($satkerPerformance ?? collect())->sortByDesc(fn ($sp) => optional($sp->update_terakhir)->timestamp)->values() as $sp)
                        <tr>
                            <td class="py-2.5 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="py-2.5 text-slate-700">{{ $sp->nama_satker }}</td>
                            <td class="py-2.5 text-right font-medium text-slate-700">
                                {{ !is_null($sp->nilai) ? number_format($sp->nilai, 2) : '-' }}
                            </td>
                            <td class="py-2.5 pl-4">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ $sp->kategori_badge }}">
                                    {{ $sp->kategori_label }}
                                </span>
                            </td>
                            <td class="py-2.5 text-right text-slate-600">
                                {{ !is_null($sp->detail_indikator['Penyerapan Anggaran']) ? number_format($sp->detail_indikator['Penyerapan Anggaran'], 2) . '%' : '-' }}
                            </td>
                            <td class="py-2.5 text-right text-slate-600">
                                {{ !is_null($sp->detail_indikator['Deviasi Halaman III DIPA']) ? number_format($sp->detail_indikator['Deviasi Halaman III DIPA'], 2) : '-' }}
                            </td>
                            <td class="py-2.5 text-right text-slate-600">
                                {{ !is_null($sp->detail_indikator['Penyelesaian Tagihan']) ? number_format($sp->detail_indikator['Penyelesaian Tagihan'], 2) : '-' }}
                            </td>
                            <td class="py-2.5 text-right text-slate-600">
                                {{ !is_null($sp->detail_indikator['Belanja Kontraktual']) ? number_format($sp->detail_indikator['Belanja Kontraktual'], 2) : '-' }}
                            </td>
                            <td class="py-2.5 text-right text-slate-600">
                                {{ !is_null($sp->detail_indikator['Pengelolaan UP/TUP']) ? number_format($sp->detail_indikator['Pengelolaan UP/TUP'], 2) : '-' }}
                            </td>
                            <td class="py-2.5 pl-4 text-slate-500 text-xs">
                                {{ optional($sp->update_terakhir)->translatedFormat('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="py-2.5">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('indicators.index', ['satker_id' => $sp->id]) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-navy-900/5 text-navy-900 hover:bg-navy-900/10"
                                       title="Lihat & nilai laporan satker ini">
                                        <i class="ti ti-eye text-base"></i>
                                    </a>
                                    <a href="{{ route('monitoring.cetak', array_merge(['satker' => $sp->id], request()->only(['granularitas', 'periode', 'tahun', 'triwulan', 'semester']))) }}"
                                       target="_blank"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-navy-900/5 text-navy-900 hover:bg-navy-900/10"
                                       title="Cetak laporan satker ini">
                                        <i class="ti ti-printer text-base"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-6 text-center text-slate-400 text-xs">Belum ada data satker.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@push('scripts')
<script>
    // Tampilkan input periode yang sesuai dengan mode granularitas yang dipilih
    function toggleFilterPeriode(mode) {
        const wraps = {
            bulanan: document.getElementById('filterWrapBulanan'),
            triwulan: document.getElementById('filterWrapTriwulan'),
            semester: document.getElementById('filterWrapSemester'),
            tahunan: document.getElementById('filterWrapTahunan'),
        };
        Object.entries(wraps).forEach(([key, el]) => {
            if (!el) return;
            el.classList.toggle('hidden', key !== mode);
            el.classList.toggle('flex', key !== 'bulanan' && key !== 'tahunan' && key === mode);
            el.classList.toggle('items-end', key !== 'bulanan' && key !== 'tahunan' && key === mode);
            el.classList.toggle('gap-2', key !== 'bulanan' && key !== 'tahunan' && key === mode);
        });
    }
</script>
@endpush

@endsection