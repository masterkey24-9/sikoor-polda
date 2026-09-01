@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    <p class="text-xs text-slate-400 mb-4">
        Ringkasan bulan {{ now()->translatedFormat('F Y') }}. Untuk data lengkap dan bisa difilter
        (periode, satker, dst), buka
        <a href="{{ route('monitoring.ikpa') }}" class="text-navy-800 font-medium hover:underline">Monitoring IKPA</a>.
    </p>

    {{-- ================= KARTU RINGKASAN ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-navy-900 text-white flex items-center justify-center mb-3">
                <i class="ti ti-trending-up text-lg"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1">Nilai IKPA Rata-rata</p>
            <p class="text-2xl font-display font-semibold text-navy-900">
                {{ !is_null($rataRataKinerja ?? null) ? number_format($rataRataKinerja, 2) : '-' }}
            </p>
            @if (! is_null($selisihBulanLalu ?? null))
                <p class="text-xs mt-1 {{ $selisihBulanLalu >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    <i class="ti {{ $selisihBulanLalu >= 0 ? 'ti-arrow-up' : 'ti-arrow-down' }}"></i>
                    {{ number_format(abs($selisihBulanLalu), 2) }} dari bulan lalu
                </p>
            @else
                <p class="text-xs text-slate-400 mt-1">Belum ada data bulan lalu</p>
            @endif
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center mb-3">
                <i class="ti ti-circle-check text-lg"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1">Satker Nilai &ge; 90</p>
            <p class="text-2xl font-display font-semibold text-navy-900">{{ $totalSangatBaik ?? 0 }} Satker</p>
            <p class="text-xs text-slate-400 mt-1">({{ number_format($persenSangatBaik ?? 0, 2) }}%)</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-amber-500 text-white flex items-center justify-center mb-3">
                <i class="ti ti-alert-triangle text-lg"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1">Satker Perlu Perhatian</p>
            <p class="text-2xl font-display font-semibold text-navy-900">{{ $totalPerluPerhatian ?? 0 }} Satker</p>
            <p class="text-xs text-slate-400 mt-1">({{ number_format($persenPerluPerhatian ?? 0, 2) }}%)</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-red-600 text-white flex items-center justify-center mb-3">
                <i class="ti ti-alert-octagon text-lg"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1">Satker Nilai &lt; 70</p>
            <p class="text-2xl font-display font-semibold text-navy-900">{{ $totalKurang ?? 0 }} Satker</p>
            <p class="text-xs text-slate-400 mt-1">({{ number_format($persenKurang ?? 0, 2) }}%)</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-gold-500 text-navy-950 flex items-center justify-center mb-3">
                <i class="ti ti-building text-lg"></i>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1">Total Satker</p>
            <p class="text-2xl font-display font-semibold text-navy-900">{{ $totalSatker ?? 0 }} Satker</p>
        </div>

    </div>

    {{-- ================= MINI TREND + MINI KATEGORI + MINI PRIORITAS ================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        <div class="bg-white rounded-xl p-5 border border-slate-200 flex flex-col">
            <p class="text-sm font-medium text-slate-700 mb-3">Trend Nilai IKPA Rata-rata</p>
            <canvas id="chartTrendMini" height="160"></canvas>
            <a href="{{ route('monitoring.ikpa') }}#trend"
               class="mt-4 pt-3 border-t border-slate-100 text-center text-xs font-medium text-navy-800 hover:underline">
                Lihat Selengkapnya
            </a>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 flex flex-col">
            <p class="text-sm font-medium text-slate-700 mb-3">Kategori Nilai IKPA Satker</p>
            <div class="relative">
                <canvas id="chartKategoriMini" height="160"></canvas>
            </div>
            <a href="{{ route('monitoring.ikpa') }}#kategori"
               class="mt-4 pt-3 border-t border-slate-100 text-center text-xs font-medium text-navy-800 hover:underline">
                Lihat Selengkapnya
            </a>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 flex flex-col">
            <p class="text-sm font-medium text-slate-700 mb-3">Satker Perlu Perhatian</p>
            <div class="space-y-2.5 flex-1">
                @forelse ($satkerPrioritasMini ?? [] as $sp)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600 truncate pr-2">{{ $sp->nama_satker }}</span>
                        <span class="shrink-0 flex items-center gap-2">
                            <span class="text-slate-700 font-medium">{{ !is_null($sp->nilai) ? number_format($sp->nilai, 2) : '-' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $sp->kategori_badge }}">{{ $sp->kategori_label }}</span>
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada data satker.</p>
                @endforelse
            </div>
            <a href="{{ route('monitoring.ikpa') }}#prioritas"
               class="mt-4 pt-3 border-t border-slate-100 text-center text-xs font-medium text-navy-800 hover:underline">
                Lihat Selengkapnya
            </a>
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    // Mini trend (6 bulan, tanpa opsi/tooltip detail)
    const trendMiniLabels = @json(($trendMini ?? collect())->pluck('bulan'));
    const trendMiniNilai = @json(($trendMini ?? collect())->pluck('nilai'));

    const ctxTrendMini = document.getElementById('chartTrendMini').getContext('2d');
    const gradientMini = ctxTrendMini.createLinearGradient(0, 0, 0, 160);
    gradientMini.addColorStop(0, 'rgba(212, 175, 55, 0.35)');
    gradientMini.addColorStop(1, 'rgba(212, 175, 55, 0)');

    new Chart(ctxTrendMini, {
        type: 'line',
        data: {
            labels: trendMiniLabels,
            datasets: [{
                data: trendMiniNilai,
                borderColor: '#D4AF37',
                backgroundColor: gradientMini,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: '#5C3B1E',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } }, grid: { display: false } }
            }
        }
    });

    // Mini kategori donut
    const kategoriMiniData = [{{ $totalSangatBaik ?? 0 }}, {{ $totalBaik ?? 0 }}, {{ $totalCukup ?? 0 }}, {{ $totalKurang ?? 0 }}];

    new Chart(document.getElementById('chartKategoriMini'), {
        type: 'doughnut',
        data: {
            labels: ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'],
            datasets: [{
                data: kategoriMiniData,
                backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { display: true, position: 'bottom', labels: { boxWidth: 8, font: { size: 10 } } }
            }
        }
    });
</script>
@endpush
