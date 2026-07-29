@extends('layouts.app')

@section('title', 'Indicators')
@section('page-title', 'Indicators')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    {{-- Form tambah indicator baru --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <p class="text-sm font-medium text-slate-700 mb-3">Tambah indicator baru</p>
        <form method="POST" action="{{ route('indicators.store') }}" class="flex items-center gap-3">
            @csrf
            <input type="text" name="name" required placeholder="Nama indicator, misal: Laporan Triwulan I"
                   class="flex-1 h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
            <button type="submit" class="h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium shrink-0">
                Tambah
            </button>
        </form>
    </div>

    {{-- Daftar indicators --}}
    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
        {{-- Ganti dengan @foreach($indicators as $indicator) dari controller --}}
        @foreach ($indicators ?? [
            (object)['id' => 1, 'name' => 'Laporan Triwulan I', 'status' => 'pending'],
            (object)['id' => 2, 'name' => 'Surat Perintah Tugas 08', 'status' => 'terkirim'],
            (object)['id' => 3, 'name' => 'Instruksi Pengamanan', 'status' => 'pending'],
        ] as $indicator)
            <div class="group">
                {{-- Baris indicator --}}
                <button type="button"
                        onclick="document.getElementById('upload-{{ $indicator->id }}').classList.toggle('hidden')"
                        class="w-full flex items-center gap-4 px-5 py-4 text-left hover:bg-slate-50">
                    <i class="ti ti-list-check text-slate-400 text-lg shrink-0"></i>
                    <span class="flex-1 text-sm font-medium text-slate-800">{{ $indicator->name }}</span>

                    @if ($indicator->status === 'terkirim')
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 shrink-0">
                            <i class="ti ti-check text-sm"></i> Terkirim
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 shrink-0">
                            <i class="ti ti-clock text-sm"></i> Belum diunggah
                        </span>
                    @endif

                    <i class="ti ti-chevron-down text-slate-400 shrink-0"></i>
                </button>

                {{-- Form upload, tersembunyi sampai baris diklik --}}
                <div id="upload-{{ $indicator->id }}" class="hidden px-5 pb-5">
                    <form method="POST" action="{{ route('indicator.upload', $indicator->id) }}"
                          enctype="multipart/form-data" class="bg-slate-50 rounded-lg p-4 space-y-3">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Satker tujuan</label>
                            <select name="satker_id" required
                                    class="w-full h-10 px-3 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                                <option value="">Pilih satker...</option>
                                @foreach ($satkers ?? [
                                    (object)['id' => 1, 'nama_satker' => 'Polres Padang'],
                                    (object)['id' => 2, 'nama_satker' => 'Polres Bukittinggi'],
                                    (object)['id' => 3, 'nama_satker' => 'Polres Payakumbuh'],
                                ] as $satker)
                                    <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">File PDF</label>
                            <input type="file" name="file" accept="application/pdf" required
                                   class="w-full text-sm file:mr-3 file:h-9 file:px-3 file:rounded-lg file:border-0 file:bg-navy-900 file:text-white file:text-sm">
                        </div>

                        <button type="submit"
                                class="h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium">
                            Kirim ke satker
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

@endsection
