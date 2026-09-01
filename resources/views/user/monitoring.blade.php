@extends('layouts.app')

@section('title', 'Monitoring Kinerja')
@section('page-title', 'Monitoring Kinerja Satker')

@section('sidebar')
    @include('components.sidebar-user')
@endsection

@section('content')

    @php
        $granularitas = $granularitas ?? 'bulanan';
        $tahunAktif = $tahunAktif ?? now()->year;
        $triwulanAktif = $triwulanAktif ?? ceil(now()->month / 3);
        $semesterAktif = $semesterAktif ?? (now()->month <= 6 ? 1 : 2);
        $tahunOpsi = range(now()->year, now()->year - 5);
        $sp = ($satkerPerformance ?? collect())->first();

        // Disiapkan di sini (bukan langsung di dalam @json() di script) supaya Blade
        // tidak salah hitung tanda kurung/kurawal dari closure multi-baris.
        $dokumenBaruJs = ($dokumenBaru ?? collect())->map(function ($d) {
            return [
                'id' => $d->id,
                'judul' => $d->judul,
                'deskripsi' => $d->deskripsi,
                'file_pdf_url' => $d->file_pdf ? asset('storage/' . $d->file_pdf) : null,
                'file_excel_url' => $d->file_excel ? asset('storage/' . $d->file_excel) : null,
            ];
        })->values();
    @endphp

    <p class="text-sm text-slate-500 mb-4">
        Halaman ini hanya menampilkan performa satker Anda sendiri —
        <span class="font-medium text-slate-700">{{ $satker->nama_satker ?? '-' }}</span>.
    </p>

    {{-- ================= FILTER PERIODE (tanpa pilihan satker lain) ================= --}}
    <form method="GET" action="{{ route('monitoring.saya') }}" class="flex flex-wrap items-end gap-3 mb-3">
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
        @if (request('periode') || request('granularitas') || request('tahun') || request('triwulan') || request('semester'))
            <a href="{{ route('monitoring.saya') }}" class="h-10 px-4 rounded-lg border border-slate-300 text-sm text-slate-600 hover:bg-slate-50 flex items-center">
                Reset ke bulan berjalan
            </a>
        @endif
    </form>

    <p class="text-xs text-slate-500 mb-6">
        Menampilkan data periode:
        <span class="font-medium text-slate-700">{{ $labelPeriodeAktif ?? (isset($periodeAktif) ? $periodeAktif->translatedFormat('F Y') : now()->translatedFormat('F Y')) }}</span>
    </p>

    {{-- ================= 1. KARTU RINGKASAN ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-navy-900 text-white flex items-center justify-center mb-3">
                <i class="ti ti-trending-up text-lg"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1">Nilai IKPA Satker</p>
            <p class="text-2xl font-display font-semibold text-navy-900">
                {{ !is_null($skorAkhir ?? null) ? number_format($skorAkhir, 2) : '-' }}
            </p>
            @if (! is_null($selisihBulanLalu ?? null))
                <p class="text-xs mt-1 {{ $selisihBulanLalu >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    <i class="ti {{ $selisihBulanLalu >= 0 ? 'ti-arrow-up' : 'ti-arrow-down' }}"></i>
                    {{ number_format(abs($selisihBulanLalu), 2) }} dari periode sebelumnya
                </p>
            @else
                <p class="text-xs text-slate-400 mt-1">Belum ada data periode sebelumnya</p>
            @endif
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-gold-500 text-navy-950 flex items-center justify-center mb-3">
                <i class="ti ti-award text-lg"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1">Kategori Nilai</p>
            <span class="inline-flex px-2.5 py-1 rounded-full text-sm font-medium {{ $kategori['badge'] ?? 'bg-slate-100 text-slate-500' }}">
                {{ $kategori['label'] ?? 'Belum Dinilai' }}
            </span>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center mb-3">
                <i class="ti ti-list-check text-lg"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1">Progres Tugas Periode Ini</p>
            <p class="text-2xl font-display font-semibold text-navy-900">
                {{ $sp->tugas_selesai ?? 0 }} / {{ $sp->total_tugas ?? 0 }}
            </p>
            <p class="text-xs text-slate-400 mt-1">tugas selesai dilaporkan</p>
        </div>

    </div>

    {{-- ================= 2. TREND + INDIKATOR ================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

        <div id="trend" class="bg-white rounded-xl p-5 border border-slate-200 scroll-mt-6">
            <div class="flex items-center justify-between mb-1">
                <p class="text-sm font-medium text-slate-700">Trend Nilai IKPA Satker</p>

                @if (($granularitas ?? 'bulanan') === 'bulanan')
                    @php $trendRangeAktif = $trendRange ?? 6; @endphp
                    <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-0.5">
                        <a href="{{ request()->fullUrlWithQuery(['trend_range' => 6]) }}"
                           class="px-2.5 py-1 rounded-md text-[11px] font-medium {{ $trendRangeAktif == 6 ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            6 Bulan
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['trend_range' => 12]) }}"
                           class="px-2.5 py-1 rounded-md text-[11px] font-medium {{ $trendRangeAktif == 12 ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            1 Tahun
                        </a>
                    </div>
                @endif
            </div>
            <p class="text-[11px] text-slate-400 mb-3">
                @php
                    $jmlTitik = $granularitas === 'bulanan'
                        ? (($trendRange ?? 6) == 12 ? '12 bulan' : '6 bulan')
                        : ['triwulan' => '4 triwulan', 'semester' => '4 semester', 'tahunan' => '5 tahun'][$granularitas ?? 'bulanan'];
                @endphp
                {{ $jmlTitik }} hingga {{ $labelPeriodeAktif ?? (isset($periodeAktif) ? $periodeAktif->translatedFormat('M Y') : now()->translatedFormat('M Y')) }}
            </p>
            <canvas id="chartTrendIkpa" height="220"></canvas>
        </div>

        <div id="indikator" class="bg-white rounded-xl p-5 border border-slate-200 scroll-mt-6">
            <p class="text-sm font-medium text-slate-700 mb-3">Monitoring Indikator IKPA</p>
            <div class="space-y-3">
                @forelse ($nilaiPerIndikator ?? [] as $item)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-slate-600">{{ $loop->iteration }}. {{ $item['judul'] }}</span>
                            <span class="font-medium text-slate-700">
                                {{ !is_null($item['rata']) ? number_format($item['rata'], 2) : '-' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $item['kelas_bar'] }}"
                                     style="width: {{ !is_null($item['rata']) ? min(100, max(0, $item['rata'])) : 0 }}%"></div>
                            </div>
                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[11px] font-medium {{ $item['kelas_badge'] }}">
                                {{ $item['warna'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada indikator untuk periode ini.</p>
                @endforelse
            </div>

            @if (($nilaiPerIndikator ?? collect())->isNotEmpty())
                <a href="{{ route('user.inbox') }}"
                   class="block text-center text-xs font-medium text-navy-800 hover:underline mt-4 pt-3 border-t border-slate-100">
                    Lihat Detail &amp; Kirim Laporan
                </a>
            @endif
        </div>

    </div>

    {{-- ================= 3. NOTIFIKASI + TINDAK LANJUT ================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm font-medium text-slate-700 mb-3">Notifikasi Terbaru</p>
            <div class="space-y-1">
                @forelse ($notifikasiTerbaru ?? [] as $notif)
                    <a href="{{ $notif->link ?? '#' }}" class="flex items-start gap-3 py-2.5 border-b border-slate-50 last:border-0 hover:bg-slate-50 -mx-1 px-1 rounded-lg">
                        <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $notif->read_at ? 'bg-slate-300' : 'bg-gold-500' }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $notif->title }}</p>
                            <p class="text-xs text-slate-500 line-clamp-2">{{ $notif->body }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada notifikasi.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm font-medium text-slate-700 mb-3">Progress Tindak Lanjut</p>
            <div class="relative">
                <canvas id="chartTindakLanjut" height="180"></canvas>
            </div>
            <ul class="mt-4 space-y-2 text-xs">
                <li class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Selesai</span>
                    <span class="text-slate-500">{{ $tindakLanjutSelesai ?? 0 }} ({{ $totalTindakLanjut ? round(($tindakLanjutSelesai ?? 0) / $totalTindakLanjut * 100) : 0 }}%)</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Proses</span>
                    <span class="text-slate-500">{{ $tindakLanjutProses ?? 0 }} ({{ $totalTindakLanjut ? round(($tindakLanjutProses ?? 0) / $totalTindakLanjut * 100) : 0 }}%)</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Belum Ditindaklanjuti</span>
                    <span class="text-slate-500">{{ $tindakLanjutBelum ?? 0 }} ({{ $totalTindakLanjut ? round(($tindakLanjutBelum ?? 0) / $totalTindakLanjut * 100) : 0 }}%)</span>
                </li>
            </ul>
        </div>

    </div>

    {{-- ================= 4. TABEL MONITORING IKPA (satker sendiri) ================= --}}
    <div id="tabel" class="bg-white rounded-xl border border-slate-200 p-5 scroll-mt-6">
        <p class="text-sm font-medium text-slate-700 mb-3">Monitoring IKPA Terbaru</p>
        <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
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
                    @forelse (($satkerPerformance ?? collect()) as $sp)
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
                                    <a href="{{ route('user.inbox') }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-navy-900/5 text-navy-900 hover:bg-navy-900/10"
                                       title="Lihat detail & kirim laporan">
                                        <i class="ti ti-eye text-base"></i>
                                    </a>
                                    <a href="{{ route('monitoring.cetak.saya', request()->only(['granularitas', 'periode', 'tahun', 'triwulan', 'semester'])) }}"
                                       target="_blank"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-navy-900/5 text-navy-900 hover:bg-navy-900/10"
                                       title="Cetak laporan satker saya">
                                        <i class="ti ti-printer text-base"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-6 text-center text-slate-400 text-xs">Belum ada data untuk periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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

    // ===== Trend Nilai IKPA — line chart dengan gradasi =====
    const trendLabels = @json(($trendBulanan ?? collect())->pluck('bulan'));
    const trendNilai = @json(($trendBulanan ?? collect())->pluck('nilai'));

    const ctxTrend = document.getElementById('chartTrendIkpa').getContext('2d');
    const gradientTrend = ctxTrend.createLinearGradient(0, 0, 0, 220);
    gradientTrend.addColorStop(0, 'rgba(212, 175, 55, 0.35)');
    gradientTrend.addColorStop(1, 'rgba(212, 175, 55, 0)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Nilai IKPA',
                data: trendNilai,
                borderColor: '#D4AF37',
                backgroundColor: gradientTrend,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#5C3B1E',
                pointBorderColor: '#D4AF37',
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#3B2312',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: { label: (ctx) => 'Nilai: ' + ctx.raw }
                }
            },
            scales: {
                y: {
                    beginAtZero: true, max: 100,
                    grid: { color: '#F1F5F9' }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // ===== Progress Tindak Lanjut — doughnut dengan angka besar di tengah =====
    const tindakLanjutData = [{{ $tindakLanjutSelesai ?? 0 }}, {{ $tindakLanjutProses ?? 0 }}, {{ $tindakLanjutBelum ?? 0 }}];
    const totalTindakLanjutJs = {{ $totalTindakLanjut ?? 0 }};

    const centerTextPluginTindakLanjut = {
        id: 'centerTextTindakLanjut',
        afterDraw(chart) {
            const { ctx, chartArea: { top, left, width, height } } = chart;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = '700 24px "Plus Jakarta Sans", sans-serif';
            ctx.fillStyle = '#3B2312';
            ctx.fillText(totalTindakLanjutJs, left + width / 2, top + height / 2 - 8);
            ctx.font = '500 11px Inter, sans-serif';
            ctx.fillStyle = '#94A3B8';
            ctx.fillText('Total', left + width / 2, top + height / 2 + 14);
            ctx.restore();
        }
    };

    // ===== Dokumen baru dari Admin (auto-terbuka) =====
    // 1) Kalau ada dokumen baru dari server-side, modalnya otomatis tampil begitu
    //    halaman ini pertama kali dimuat — begitu tampil, langsung ditandai "dibuka".
    // 2) Selama satker masih standby di halaman ini (tanpa reload), dicek berkala
    //    (polling) ke server; kalau admin baru saja kirim dokumen, modal otomatis
    //    muncul sendiri tanpa satker perlu refresh atau klik notifikasi.
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const dokumenAwal = @json($dokumenBaruJs);
        const idSudahDitampilkan = new Set(dokumenAwal.map(d => d.id));

        function tandaiDibuka(id) {
            fetch(`/dokumen/${id}/dibuka`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            }).catch(() => {});
        }

        function buatKartuDokumen(dok) {
            const pdfHtml = dok.file_pdf_url ? `
                <div class="p-3">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-2">
                        <i class="ti ti-file-type-pdf text-red-500"></i> Lampiran PDF
                    </div>
                    <iframe src="${dok.file_pdf_url}" class="w-full h-[75vh] rounded-lg border border-slate-200"></iframe>
                </div>` : '';

            const excelHtml = dok.file_excel_url ? `
                <div class="px-4 pb-4 ${dok.file_pdf_url ? 'pt-0' : 'pt-3'}">
                    <a href="${dok.file_excel_url}" target="_blank"
                       class="inline-flex items-center gap-2 px-3.5 h-10 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium hover:bg-emerald-100 transition">
                        <i class="ti ti-file-type-xls text-sm"></i> Buka lampiran Excel
                    </a>
                    <p class="text-[11px] text-slate-400 mt-1.5">File Excel tidak bisa ditampilkan langsung di halaman, klik untuk membukanya.</p>
                </div>` : '';

            const deskripsiHtml = dok.deskripsi
                ? `<p class="text-xs text-slate-400 truncate">${dok.deskripsi}</p>` : '';

            return `
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">${dok.judul}</p>
                            ${deskripsiHtml}
                        </div>
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-medium bg-gold-500/20 text-gold-700">Baru</span>
                    </div>
                    ${pdfHtml}
                    ${excelHtml}
                </div>`;
        }

        function tampilkanModal(daftarDokumen) {
            const existing = document.getElementById('modalDokumenBaru');
            if (existing) existing.remove();

            const wrapper = document.createElement('div');
            wrapper.id = 'modalDokumenBaru';
            wrapper.className = 'fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 backdrop-blur-sm p-4';
            wrapper.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[92vh] flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-lg bg-gold-500 text-navy-950 flex items-center justify-center shrink-0">
                                <i class="ti ti-file-import text-lg"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-navy-900">Dokumen baru dari Admin</p>
                                <p class="text-xs text-slate-400">${daftarDokumen.length} dokumen baru diterima</p>
                            </div>
                        </div>
                        <button type="button" onclick="sikoorTutupDokumenBaru()"
                                class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">
                        ${daftarDokumen.map(buatKartuDokumen).join('')}
                    </div>
                    <div class="px-5 py-4 border-t border-slate-100 flex justify-end shrink-0">
                        <button type="button" onclick="sikoorTutupDokumenBaru()"
                                class="h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                            Sudah dibaca, tutup
                        </button>
                    </div>
                </div>`;
            document.body.appendChild(wrapper);
        }

        // Tandai dokumen yang sudah tampil dari server-side render saat page load pertama,
        // dan langsung tampilkan modalnya (tanpa perlu tunggu polling).
        if (dokumenAwal.length) {
            tampilkanModal(dokumenAwal);
            dokumenAwal.forEach(dok => tandaiDibuka(dok.id));
        }

        // Polling tiap 15 detik selama satker masih di halaman ini.
        setInterval(function () {
            if (document.hidden) return; // tidak usah cek kalau tab sedang tidak aktif
            fetch(`{{ route('dokumen.cekBaru') }}`, {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => {
                    const baru = (data.dokumen || []).filter(d => ! idSudahDitampilkan.has(d.id));
                    if (! baru.length) return;

                    baru.forEach(d => idSudahDitampilkan.add(d.id));
                    tampilkanModal(baru);
                    baru.forEach(d => tandaiDibuka(d.id));
                })
                .catch(() => {});
        }, 15000);
    })();

    window.sikoorTutupDokumenBaru = function () {
        document.getElementById('modalDokumenBaru')?.remove();
    };

    new Chart(document.getElementById('chartTindakLanjut'), {
        type: 'doughnut',
        data: {
            labels: ['Selesai', 'Proses', 'Belum Ditindaklanjuti'],
            datasets: [{
                data: tindakLanjutData,
                backgroundColor: ['#10B981', '#3B82F6', '#EF4444'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#3B2312',
                    padding: 10,
                    cornerRadius: 8,
                }
            }
        },
        plugins: [centerTextPluginTindakLanjut]
    });
</script>
@endpush

@endsection
