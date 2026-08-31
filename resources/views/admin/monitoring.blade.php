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

    <p class="text-sm font-medium text-slate-700 mb-3">Nilai Satker Tertinggi (Bulanan)</p>
    <div class="bg-white rounded-xl p-5 border border-slate-200 mb-6">
        <canvas id="chartTopSatkerBulanan" height="90"></canvas>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm font-medium text-slate-700 mb-3">Nilai Pantauan per Satker</p>
            <canvas id="chartNilaiPerSatker" height="220"></canvas>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <p class="text-sm font-medium text-slate-700 mb-3">Persentase Status Indicator</p>
            <canvas id="chartStatusIndicator" height="220"></canvas>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    // ===== 1) Nilai Satker Tertinggi (Bulanan) — line chart dengan gradasi =====
    const bulanLabels = @json($monthlyTopSatker->pluck('bulan'));
    const bulanNilai = @json($monthlyTopSatker->pluck('nilai'));
    const bulanSatker = @json($monthlyTopSatker->pluck('satker'));

    const ctxLine = document.getElementById('chartTopSatkerBulanan').getContext('2d');
    const gradientLine = ctxLine.createLinearGradient(0, 0, 0, 260);
    gradientLine.addColorStop(0, 'rgba(212, 175, 55, 0.35)');
    gradientLine.addColorStop(1, 'rgba(212, 175, 55, 0)');

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: bulanLabels,
            datasets: [{
                label: 'Nilai satker tertinggi',
                data: bulanNilai,
                borderColor: '#D4AF37',
                backgroundColor: gradientLine,
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
                    callbacks: {
                        label: (ctx) => 'Nilai: ' + ctx.raw + '%',
                        afterLabel: (ctx) => 'Satker: ' + (bulanSatker[ctx.dataIndex] ?? '-')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true, max: 100,
                    ticks: { callback: (v) => v + '%' },
                    grid: { color: '#F1F5F9' }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // ===== 2) Nilai Pantauan per Satker — bar chart =====
    const satkerLabels = @json($chartSatkerLabels);
    const satkerNilai = @json($chartSatkerNilai);

    new Chart(document.getElementById('chartNilaiPerSatker'), {
        type: 'bar',
        data: {
            labels: satkerLabels,
            datasets: [{
                label: 'Nilai (%)',
                data: satkerNilai,
                backgroundColor: '#5C3B1E',
                hoverBackgroundColor: '#D4AF37',
                borderRadius: 6,
                maxBarThickness: 26,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#3B2312',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: { label: (ctx) => 'Nilai: ' + ctx.raw + '%' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true, max: 100,
                    ticks: { callback: (v) => v + '%' },
                    grid: { color: '#F1F5F9' }
                },
                x: {
                    ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 },
                    grid: { display: false }
                }
            }
        }
    });

    // ===== 3) Persentase Status Indicator — doughnut dengan angka besar di tengah =====
    const totalDiterima = {{ $totalIndikatorDiterima }};
    const totalMenunggu = {{ $totalIndikatorMenunggu }};
    const totalSemua = totalDiterima + totalMenunggu;
    const persenDiterima = totalSemua > 0 ? Math.round((totalDiterima / totalSemua) * 100) : 0;

    const centerTextPlugin = {
        id: 'centerText',
        afterDraw(chart) {
            const { ctx, chartArea: { top, left, width, height } } = chart;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = '700 26px "Plus Jakarta Sans", sans-serif';
            ctx.fillStyle = '#3B2312';
            ctx.fillText(persenDiterima + '%', left + width / 2, top + height / 2 - 8);
            ctx.font = '500 11px Inter, sans-serif';
            ctx.fillStyle = '#94A3B8';
            ctx.fillText('Diterima', left + width / 2, top + height / 2 + 14);
            ctx.restore();
        }
    };

    new Chart(document.getElementById('chartStatusIndicator'), {
        type: 'doughnut',
        data: {
            labels: ['Diterima', 'Menunggu'],
            datasets: [{
                data: [totalDiterima, totalMenunggu],
                backgroundColor: ['#10B981', '#F1E4C3'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            cutout: '72%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                tooltip: {
                    backgroundColor: '#3B2312',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: (ctx) => {
                            const pct = totalSemua > 0 ? Math.round((ctx.raw / totalSemua) * 100) : 0;
                            return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                        }
                    }
                }
            }
        },
        plugins: [centerTextPlugin]
    });
</script>
@endpush

@endsection