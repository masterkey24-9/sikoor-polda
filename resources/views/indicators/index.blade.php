@extends('layouts.app')

@section('title', 'Indicators')
@section('page-title', 'Indicators')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')
<div class="max-w-xl">

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

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form method="POST" action="{{ route('indicators.store') }}" enctype="multipart/form-data" class="space-y-5" id="uploadForm">
            @csrf

            {{-- 1. Upload file --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">1. Unggah file PDF</label>
                <label for="file"
                       class="flex flex-col items-center justify-center gap-2 h-32 border-2 border-dashed border-slate-300 rounded-lg cursor-pointer hover:border-navy-800 hover:bg-slate-50 transition">
                    <i class="ti ti-upload text-2xl text-slate-400"></i>
                    <span class="text-sm text-slate-500" id="fileLabel">Klik untuk pilih file, atau seret ke sini</span>
                    <span class="text-xs text-slate-400">Hanya PDF, maksimal 10MB</span>
                </label>
                <input type="file" id="file" name="file" accept="application/pdf" required class="hidden">
            </div>

            {{-- 2. Deskripsi --}}
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">2. Deskripsi / judul dokumen</label>
                <textarea id="name" name="name" required rows="3"
                          placeholder="Contoh: Surat Perintah Tugas 08 untuk kegiatan pengamanan..."
                          class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 resize-none"></textarea>
            </div>

            {{-- 3. Pilih satker tujuan --}}
            <div>
                <label for="satker_id" class="block text-sm font-medium text-slate-700 mb-1.5">3. Kirim ke satker</label>
                <select id="satker_id" name="satker_id" required
                        class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                    <option value="">Pilih satker...</option>
                    {{-- Data satker dari backend --}}
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
                    class="w-full h-11 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                Kirim dokumen
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const fileInput = document.getElementById('file');
    const fileLabel = document.getElementById('fileLabel');

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;

        if (file.type !== 'application/pdf') {
            alert('File harus berformat PDF.');
            fileInput.value = '';
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert('Ukuran file maksimal 10MB.');
            fileInput.value = '';
            return;
        }
        fileLabel.textContent = file.name;
    });
</script>
@endpush
@endsection