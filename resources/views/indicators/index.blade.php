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

    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 max-w-xl">
        <p class="text-sm font-medium text-slate-700 mb-4">Buat indicator baru</p>

        <form method="POST" action="{{ route('indicators.store') }}" enctype="multipart/form-data" class="space-y-4" id="indicatorForm">
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
                <label for="file_pdf" class="block text-sm font-medium text-slate-700 mb-1.5">Lampiran PDF (opsional)</label>
                <input type="file" id="file_pdf" name="file_pdf" accept="application/pdf"
                       class="w-full text-sm file:mr-3 file:h-9 file:px-3 file:rounded-lg file:border-0 file:bg-navy-900 file:text-white file:text-sm">
                <p class="text-xs text-slate-400 mt-1">Kirim dokumen pendukung langsung ke satker, maksimal 10MB.</p>
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

    <p class="text-sm font-medium text-slate-700 mb-3">Daftar indicator</p>

    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-xl">
        @forelse ($indicators ?? [] as $indicator)
            <a href="{{ route('indicators.show', $indicator->id) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50">
                <i class="ti ti-file-type-pdf text-red-500 text-lg shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $indicator->judul }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Tujuan: {{ $indicator->satker->nama_satker ?? '-' }}</p>
                </div>

                @if ($indicator->results && $indicator->results->count() > 0)
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 shrink-0">
                        <i class="ti ti-check text-sm"></i> Laporan diterima
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 shrink-0">
                        <i class="ti ti-clock text-sm"></i> Menunggu satker
                    </span>
                @endif
            </a>
        @empty
            <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada indicator dibuat.</p>
        @endforelse
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
</script>
@endpush
