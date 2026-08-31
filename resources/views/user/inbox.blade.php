@extends('layouts.app')

@section('title', 'Tugas & Laporan')
@section('page-title', 'Tugas & laporan')

@section('sidebar')
    @include('components.sidebar-user')
@endsection

@section('content')

    @if (($peringatanAktif ?? collect())->isNotEmpty())
        <div class="mb-4 rounded-lg bg-red-600 text-white overflow-hidden">
            <div class="whitespace-nowrap py-2.5" style="animation: sikoorMarquee 22s linear infinite;">
                @foreach ($peringatanAktif as $p)
                    <span class="inline-flex items-center gap-2 px-6 text-sm font-medium">
                        <i class="ti ti-alert-triangle"></i>
                        @if ($p->sudahLewatBatasWaktu())
                            SUDAH LEWAT BATAS WAKTU ({{ $p->batas_waktu->translatedFormat('d M Y, H:i') }}) —
                        @else
                            Batas waktu {{ $p->batas_waktu->translatedFormat('d M Y, H:i') }} —
                        @endif
                        {{ $p->pesan }}
                    </span>
                @endforeach
            </div>
        </div>
        <style>
            @keyframes sikoorMarquee {
                0%   { transform: translateX(100%); }
                100% { transform: translateX(-100%); }
            }
        </style>
    @endif

    @if ($terkunci ?? false)
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            Pengiriman laporan baru dikunci sementara karena melewati batas waktu peringatan di atas. Hubungi admin untuk membuka kembali.
        </div>
    @endif

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" action="{{ route('user.inbox') }}" class="flex items-end gap-3 mb-4">
        <div>
            <label for="periode" class="block text-xs font-medium text-slate-500 mb-1.5">Periode</label>
            <input type="month" id="periode" name="periode"
                   value="{{ isset($periodeAktif) ? $periodeAktif->format('Y-m') : now()->format('Y-m') }}"
                   class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
        </div>
        <button type="submit"
                class="h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
            Tampilkan
        </button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-xl">
        @forelse ($indicators ?? [] as $indicator)
            @php $latestResult = $indicator->results->sortByDesc('created_at')->first(); @endphp
            <div>
                <button type="button"
                        onclick="document.getElementById('form-{{ $indicator->id }}').classList.toggle('hidden')"
                        class="w-full flex items-start gap-4 px-5 py-4 text-left hover:bg-slate-50">
                    <i class="ti ti-clipboard-list text-slate-400 text-lg shrink-0 mt-0.5"></i>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-slate-800">{{ $indicator->judul }}</p>
                            @if ($indicator->periode)
                                <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500">
                                    {{ \Carbon\Carbon::parse($indicator->periode)->translatedFormat('M Y') }}
                                </span>
                            @endif
                        </div>
                        @if ($indicator->deskripsi)
                            <p class="text-xs text-slate-400 mt-0.5">{{ $indicator->deskripsi }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-3 mt-1.5">
                            @if ($indicator->file_pdf)
                                <a href="{{ asset('storage/' . $indicator->file_pdf) }}" target="_blank"
                                   onclick="event.stopPropagation()"
                                   class="inline-flex items-center gap-1.5 text-xs text-navy-800 hover:underline">
                                    <i class="ti ti-file-type-pdf text-red-500 text-sm"></i> Lampiran PDF dari admin
                                </a>
                            @endif
                            @if ($indicator->file_excel)
                                <a href="{{ asset('storage/' . $indicator->file_excel) }}" target="_blank"
                                   onclick="event.stopPropagation()"
                                   class="inline-flex items-center gap-1.5 text-xs text-navy-800 hover:underline">
                                    <i class="ti ti-file-type-xls text-emerald-600 text-sm"></i> Lampiran Excel dari admin
                                </a>
                            @endif
                        </div>

                        @if ($latestResult)
                            <div class="flex flex-wrap items-center gap-3 mt-1.5">
                                @if ($latestResult->file_pdf)
                                    <a href="{{ asset('storage/' . $latestResult->file_pdf) }}" target="_blank"
                                       onclick="event.stopPropagation()"
                                       class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:underline">
                                        <i class="ti ti-file-type-pdf text-red-500 text-sm"></i> Laporan PDF saya
                                    </a>
                                @endif
                                @if ($latestResult->file_excel)
                                    <a href="{{ asset('storage/' . $latestResult->file_excel) }}" target="_blank"
                                       onclick="event.stopPropagation()"
                                       class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:underline">
                                        <i class="ti ti-file-type-xls text-emerald-600 text-sm"></i> Laporan Excel saya
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if ($latestResult && !is_null($latestResult->nilai))
                            <p class="text-xs text-slate-600 mt-1.5">
                                Nilai dari admin: <span class="font-medium text-navy-900">{{ $latestResult->nilai }}</span>
                            </p>
                        @endif
                        @if ($latestResult && $latestResult->catatan_admin)
                            <p class="text-xs text-slate-500 mt-0.5 italic">"{{ $latestResult->catatan_admin }}"</p>
                        @endif
                    </div>

                    @if (! $latestResult)
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 shrink-0">
                            <i class="ti ti-clock text-sm"></i> Belum diunggah
                        </span>
                    @elseif ($latestResult->status === 'diterima')
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 shrink-0">
                            <i class="ti ti-check text-sm"></i> Diterima
                        </span>
                    @elseif ($latestResult->status === 'direvisi')
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-red-50 text-red-700 shrink-0">
                            <i class="ti ti-alert-triangle text-sm"></i> Perlu direvisi
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            <i class="ti ti-hourglass text-sm"></i> Menunggu dinilai
                        </span>
                    @endif
                </button>

                <div id="form-{{ $indicator->id }}" class="hidden px-5 pb-5">
                    @if ($terkunci ?? false)
                        <div class="bg-slate-50 rounded-lg p-4 text-xs text-slate-500">
                            Pengiriman laporan dikunci sampai peringatan batas waktu diselesaikan admin.
                        </div>
                    @else
                    <form method="POST" action="{{ route('indicator.upload', $indicator->id) }}"
                          enctype="multipart/form-data" class="bg-slate-50 rounded-lg p-4 space-y-3">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">
                                Laporan (PDF dan/atau Excel)
                                @if ($latestResult)
                                    <span class="font-normal text-slate-400">(mengunggah ulang akan mengirim laporan baru)</span>
                                @endif
                            </label>
                            <div class="flex items-start gap-3">
                                <div>
                                    <input type="file" id="file_pdf_{{ $indicator->id }}" name="file_pdf" accept="application/pdf" class="hidden"
                                           onchange="sikoorUpdateFileLabel(this, 'file_pdf_label_{{ $indicator->id }}')">
                                    <label for="file_pdf_{{ $indicator->id }}"
                                           class="cursor-pointer flex flex-col items-center justify-center w-16 h-16 rounded-lg border-2 border-dashed border-slate-300 hover:border-red-400 hover:bg-red-50 transition">
                                        <i class="ti ti-file-type-pdf text-red-500 text-xl"></i>
                                        <span class="text-[10px] text-slate-500 mt-0.5">PDF</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="file" id="file_excel_{{ $indicator->id }}" name="file_excel" accept=".xlsx,.xls,.csv" class="hidden"
                                           onchange="sikoorUpdateFileLabel(this, 'file_excel_label_{{ $indicator->id }}')">
                                    <label for="file_excel_{{ $indicator->id }}"
                                           class="cursor-pointer flex flex-col items-center justify-center w-16 h-16 rounded-lg border-2 border-dashed border-slate-300 hover:border-emerald-400 hover:bg-emerald-50 transition">
                                        <i class="ti ti-file-type-xls text-emerald-600 text-xl"></i>
                                        <span class="text-[10px] text-slate-500 mt-0.5">Excel</span>
                                    </label>
                                </div>
                                <div class="flex-1 text-xs text-slate-500 space-y-1 pt-1">
                                    <p id="file_pdf_label_{{ $indicator->id }}">Belum ada file PDF dipilih</p>
                                    <p id="file_excel_label_{{ $indicator->id }}">Belum ada file Excel dipilih</p>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-1.5">Unggah minimal satu file, maksimal 5MB per file.</p>
                        </div>

                        <button type="submit"
                                class="h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium">
                            Kirim laporan
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-slate-400 text-center">Belum ada tugas yang ditugaskan.</p>
        @endforelse
    </div>

@endsection

@push('scripts')
<script>
    function sikoorUpdateFileLabel(input, labelId) {
        const label = document.getElementById(labelId);
        if (!label) return;
        const jenis = labelId.startsWith('file_pdf') ? 'PDF' : 'Excel';
        label.textContent = input.files.length
            ? input.files[0].name
            : `Belum ada file ${jenis} dipilih`;
    }
</script>
@endpush