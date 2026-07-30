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
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Buat indicator baru + pilih satker tujuan --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 max-w-xl">
        <p class="text-sm font-medium text-slate-700 mb-4">Buat indicator baru</p>

        <form method="POST" action="{{ route('indicators.store') }}" class="space-y-4">
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
                <label for="tenggat_waktu" class="block text-sm font-medium text-slate-700 mb-1.5">Tenggat waktu</label>
                <input type="date" id="tenggat_waktu" name="tenggat_waktu" required
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
            </div>

            {{--
                PENTING: field 'satker_id' ini BELUM tentu dikenali backend.
                indicators.store saat ini cuma validasi judul, deskripsi, tenggat_waktu.
                Perlu tambahan validasi + kolom satker_id di tabel indicators
                supaya field ini benar-benar tersimpan dan tugas terarah ke satker yang dipilih.
            --}}
            <div>
                <label for="satker_id" class="block text-sm font-medium text-slate-700 mb-1.5">Kirim ke satker</label>
                <select id="satker_id" name="satker_id" required
                        class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
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

            <button type="submit"
                    class="h-11 px-5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                Buat & kirim indicator
            </button>
        </form>
    </div>

    {{-- Daftar indicator yang sudah dibuat (pantauan status) --}}
    <p class="text-sm font-medium text-slate-700 mb-3">Daftar indicator</p>

    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-xl">
        @foreach ($indicators ?? [
            (object)['id' => 1, 'judul' => 'Laporan Triwulan I', 'satker_nama' => 'Polres Padang', 'status' => 'pending'],
            (object)['id' => 2, 'judul' => 'Surat Perintah Tugas 08', 'satker_nama' => 'Polres Bukittinggi', 'status' => 'terkirim'],
        ] as $indicator)
            <div class="flex items-center gap-4 px-5 py-4">
                <i class="ti ti-file-type-pdf text-red-500 text-lg shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $indicator->judul }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Tujuan: {{ $indicator->satker_nama ?? '-' }}</p>
                </div>

                @if ($indicator->status === 'terkirim')
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 shrink-0">
                        <i class="ti ti-check text-sm"></i> Laporan diterima
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 shrink-0">
                        <i class="ti ti-clock text-sm"></i> Menunggu satker
                    </span>
                @endif
            </div>
        @endforeach
    </div>

@endsection
