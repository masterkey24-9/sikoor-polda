@extends('layouts.app')

@section('title', 'Indicators')
@section('page-title', 'Indicators')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    @if (session('success') || session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
            {{ session('success') ?? session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Muncul kalau datang dari tombol "Lihat" di tabel Monitoring IKPA (bawa ?satker_id=X).
         Ini pintu masuk langsung ke halaman penilaian laporan per satker. --}}
    @if ($satkerFilterAktif ?? null)
        <div class="bg-white rounded-xl border border-slate-200 mb-6">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <p class="text-sm font-medium text-slate-700">Laporan {{ $satkerFilterAktif->nama_satker }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Klik salah satu tugas di bawah untuk menilai laporan yang masuk.</p>
                </div>
                <a href="{{ route('indicators.index') }}" class="text-xs font-medium text-navy-800 hover:underline shrink-0">
                    &times; Hapus filter
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($indicatorsSatkerFilter ?? [] as $ind)
                    @php $latestResult = $ind->results->sortByDesc('created_at')->first(); @endphp
                    <a href="{{ route('indicators.show', $ind->id) }}"
                       class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50">
                        <i class="ti ti-clipboard-list text-slate-400 text-lg shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800">{{ $ind->judul }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $ind->periode ? \Carbon\Carbon::parse($ind->periode)->translatedFormat('F Y') : '-' }}
                                @if ($latestResult)
                                    &middot; Dikirim {{ $latestResult->created_at->translatedFormat('d M Y') }}
                                    @if (!is_null($latestResult->nilai))
                                        &middot; Nilai: <span class="font-medium text-navy-900">{{ $latestResult->nilai }}</span>
                                    @endif
                                @endif
                            </p>
                        </div>
                        @if (!$latestResult)
                            <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">Belum lapor</span>
                        @elseif (is_null($latestResult->nilai))
                            <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Menunggu dinilai</span>
                        @elseif ($latestResult->status === 'diterima')
                            <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Diterima</span>
                        @else
                            <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Perlu direvisi</span>
                        @endif
                        <i class="ti ti-chevron-right text-slate-300 shrink-0"></i>
                    </a>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-slate-400">Belum ada tugas untuk satker ini.</p>
                @endforelse
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <p class="text-sm font-medium text-slate-700 mb-4">Buat indicator baru</p>

        <form method="POST" action="{{ route('indicators.store') }}" enctype="multipart/form-data" class="space-y-4" id="indicatorForm">
            @csrf

            <div>
                <label for="judul" class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Indikator</label>
                <select id="judul" name="judul" required
                        class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                    <option value="" disabled selected>-- Pilih jenis indikator --</option>
                    @foreach ($jenisIndikator ?? [] as $jenis)
                        <option value="{{ $jenis }}" @selected(old('judul') === $jenis)>{{ $jenis }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1">Jenis indikator sudah baku, supaya konsisten dengan data di halaman Monitoring.</p>
            </div>

            <div>
                <label for="periode" class="block text-sm font-medium text-slate-700 mb-1.5">Periode</label>
                <input type="month" id="periode" name="periode" value="{{ old('periode', now()->format('Y-m')) }}"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                <p class="text-xs text-slate-400 mt-1">Dipakai untuk filter periode di halaman monitoring.</p>
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi (opsional)</label>
                <textarea id="deskripsi" name="deskripsi" rows="3"
                          placeholder="Detail tugas/laporan yang diminta..."
                          class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 resize-none"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Lampiran (opsional)</label>
                <div class="flex items-start gap-3">
                    <div>
                        <input type="file" id="file_pdf" name="file_pdf" accept="application/pdf" class="hidden"
                               onchange="sikoorUpdateFileLabel(this, 'file_pdf_label')">
                        <label for="file_pdf"
                               class="cursor-pointer flex flex-col items-center justify-center w-20 h-20 rounded-lg border-2 border-dashed border-slate-300 hover:border-red-400 hover:bg-red-50 transition">
                            <i class="ti ti-file-type-pdf text-red-500 text-2xl"></i>
                            <span class="text-[11px] text-slate-500 mt-1">PDF</span>
                        </label>
                    </div>
                    <div>
                        <input type="file" id="file_excel" name="file_excel" accept=".xlsx,.xls,.csv" class="hidden"
                               onchange="sikoorUpdateFileLabel(this, 'file_excel_label')">
                        <label for="file_excel"
                               class="cursor-pointer flex flex-col items-center justify-center w-20 h-20 rounded-lg border-2 border-dashed border-slate-300 hover:border-emerald-400 hover:bg-emerald-50 transition">
                            <i class="ti ti-file-type-xls text-emerald-600 text-2xl"></i>
                            <span class="text-[11px] text-slate-500 mt-1">Excel</span>
                        </label>
                    </div>
                    <div class="flex-1 text-xs text-slate-500 space-y-1.5 pt-1">
                        <p id="file_pdf_label">Belum ada file PDF dipilih</p>
                        <p id="file_excel_label">Belum ada file Excel dipilih</p>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-1.5">Kirim dokumen pendukung langsung ke satker (PDF dan/atau Excel), maksimal 10MB per file.</p>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-slate-700">Kirim ke satker</label>
                    <label class="flex items-center gap-2 text-xs text-navy-800 cursor-pointer font-medium">
                        <input type="checkbox" id="selectAllSatker"
                               class="w-4 h-4 rounded border-slate-300 text-navy-800 focus:ring-navy-800">
                        Pilih semua satker
                    </label>
                </div>
                <div class="border border-slate-300 rounded-lg p-3 max-h-48 overflow-y-auto space-y-2">
                    @forelse ($satkers ?? [] as $satker)
                        <label class="flex items-center gap-2.5 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" name="satker_id[]" value="{{ $satker->id }}"
                                   class="satker-checkbox w-4 h-4 rounded border-slate-300 text-navy-800 focus:ring-navy-800">
                            {{ $satker->nama_satker }}
                        </label>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada satker terdaftar.</p>
                    @endforelse
                </div>
                <p class="text-xs text-slate-400 mt-1">Pilih minimal 1 satker tujuan, atau centang "Pilih semua satker".</p>
            </div>

            <button type="submit"
                    class="h-11 px-5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                Buat & kirim indicator
            </button>
        </form>
    </div>

    {{-- Ringkasan dipakai sebagai <details> (disclosure widget bawaan browser, tanpa JS):
         di layar lebar (lg ke atas) otomatis kebuka duluan ("open") dan duduk sejajar
         di samping form. Kalau layarnya sempit dan dia jatuh ke bawah form, judulnya
         tetap bisa diklik untuk collapse/expand, jadi nggak makan tempat vertikal. --}}
    <details open class="bg-white rounded-xl border border-slate-200 p-6 group">
        <summary class="cursor-pointer list-none flex items-center justify-between">
            <span>
                <span class="block text-sm font-medium text-slate-700">Ringkasan Indikator Bulan Ini</span>
                <span class="block text-xs text-slate-400 mt-0.5">Status &amp; warna sama persis dengan panel "Monitoring Indikator IKPA" di dashboard.</span>
            </span>
            <i class="ti ti-chevron-down text-slate-400 shrink-0 ml-3 transition-transform group-open:rotate-180"></i>
        </summary>

        <div class="mt-4 -mx-6 border-t border-slate-100 divide-y divide-slate-100">
            @forelse ($ringkasanIndikator ?? [] as $item)
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $item['judul'] }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $item['sudah_lapor'] }}/{{ $item['total_satker'] }} satker sudah lapor
                            @if (! is_null($item['rata']))
                                &middot; Rata-rata nilai {{ number_format($item['rata'], 2) }}
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-medium {{ $item['kelas'] }}">
                        {{ $item['warna'] }}
                    </span>
                </div>
            @empty
                <p class="px-6 py-8 text-center text-sm text-slate-400">Belum ada jenis indikator terdaftar.</p>
            @endforelse
        </div>
    </details>

    </div>

@endsection

@push('scripts')
<script>
    const selectAll = document.getElementById('selectAllSatker');
    const checkboxes = document.querySelectorAll('.satker-checkbox');

    selectAll.addEventListener('change', () => {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            selectAll.checked = [...checkboxes].every(c => c.checked);
        });
    });

    function sikoorUpdateFileLabel(input, labelId) {
        const label = document.getElementById(labelId);
        if (!label) return;
        const jenis = labelId === 'file_pdf_label' ? 'PDF' : 'Excel';
        label.textContent = input.files.length
            ? input.files[0].name
            : `Belum ada file ${jenis} dipilih`;
    }
</script>
@endpush