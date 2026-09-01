@extends('layouts.app')

@section('title', 'Detail indicator')
@section('page-title', 'Detail indicator')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

<a href="{{ route('monitoring.ikpa') }}" class="inline-flex items-center gap-1.5 text-sm text-navy-800 hover:underline mb-4">
    <i class="ti ti-arrow-left text-base"></i> Kembali ke Monitoring IKPA
</a>

<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 max-w-2xl">
    <p class="text-lg font-display font-semibold text-navy-900">{{ $indicator->judul }}</p>
    @if ($indicator->deskripsi)
        <p class="text-sm text-slate-500 mt-1">{{ $indicator->deskripsi }}</p>
    @endif

    <div class="mt-4 text-sm">
        <p class="text-slate-400 text-xs">Satker tujuan</p>
        <p class="text-slate-700 font-medium">{{ $indicator->satker->nama_satker ?? '-' }}</p>
    </div>

    @if ($indicator->file_pdf)
        <a href="{{ asset('storage/' . $indicator->file_pdf) }}" target="_blank"
           class="inline-flex items-center gap-1.5 text-sm text-navy-800 hover:underline mt-4">
            <i class="ti ti-paperclip text-base"></i> Lihat lampiran dari admin
        </a>
    @endif
</div>

<p class="text-sm font-medium text-slate-700 mb-3">Laporan yang diterima</p>

<div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-2xl">
    @forelse ($indicator->results as $result)
        <div class="px-5 py-4">
            <div class="flex items-center gap-4">
                <i class="ti ti-file-type-pdf text-red-500 text-lg shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800">{{ $result->satker->nama_satker ?? '-' }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Dikirim {{ $result->created_at->translatedFormat('d M Y, H:i') }}
                        @if (!is_null($result->nilai))
                            &middot; Nilai: <span class="font-medium text-navy-900">{{ $result->nilai }}</span>
                        @endif
                    </p>
                    @if ($result->tindak_lanjut)
                        <p class="text-xs text-slate-500 mt-1.5 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2">
                            <span class="font-medium text-slate-600">Tindak lanjut:</span> {{ $result->tindak_lanjut }}
                        </p>
                    @endif
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full shrink-0
                    {{ $result->status === 'diterima' ? 'bg-emerald-50 text-emerald-700' : ($result->status === 'direvisi' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                    {{ ucfirst($result->status) }}
                </span>
                <a href="{{ asset('storage/' . $result->file_pdf) }}" target="_blank"
                   class="h-9 px-3.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 flex items-center gap-1.5 shrink-0">
                    <i class="ti ti-eye text-base"></i> Lihat file
                </a>
            </div>

            {{-- Form penilaian admin: isi nilai (0-100), status, catatan, dan tindak lanjut --}}
            <form method="POST" action="{{ route('indicator-results.updateStatus', $result->id) }}"
                  class="mt-3 ml-9 flex flex-wrap items-end gap-3 result-review-form">
                @csrf
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Nilai (0-100)</label>
                    <input type="number" name="nilai" min="0" max="100" required
                           value="{{ old('nilai', $result->nilai) }}"
                           class="nilai-input w-24 h-9 px-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Status</label>
                    <select name="status" required
                            class="h-9 px-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                        <option value="diterima" @selected($result->status === 'diterima')>Diterima</option>
                        <option value="direvisi" @selected($result->status === 'direvisi')>Perlu direvisi</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs text-slate-500 mb-1">Catatan (opsional)</label>
                    <input type="text" name="catatan_admin" maxlength="1000"
                           value="{{ old('catatan_admin', $result->catatan_admin) }}"
                           placeholder="Feedback untuk satker..."
                           class="w-full h-9 px-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                </div>
                <div class="w-full flex-1 min-w-[240px]">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs text-slate-500">Tindak lanjut</label>
                        <button type="button" class="autofill-tindak-lanjut text-[11px] text-navy-800 hover:underline">
                            Gunakan saran otomatis
                        </button>
                    </div>
                    <textarea name="tindak_lanjut" rows="2" maxlength="1000"
                              placeholder="Terisi otomatis sesuai nilai, atau tulis sendiri..."
                              class="tindak-lanjut-input w-full px-2.5 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 resize-none">{{ old('tindak_lanjut', $result->tindak_lanjut) }}</textarea>
                </div>
                <button type="submit"
                        class="h-9 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                    Simpan penilaian
                </button>
            </form>
        </div>
    @empty
        <p class="px-5 py-8 text-center text-sm text-slate-400">Belum ada laporan yang diunggah satker.</p>
    @endforelse
</div>

@push('scripts')
<script>
    (function () {
        const AMBANG_HIJAU = {{ config('sikoor.ambang_hijau', 95) }};
        const AMBANG_KUNING = {{ config('sikoor.ambang_kuning', 89) }};

        const SARAN = {
            hijau: 'Kinerja sangat baik. Pertahankan konsistensi ketepatan waktu dan kualitas laporan pada periode berikutnya.',
            kuning: 'Kinerja cukup baik, namun masih ada ruang perbaikan. Mohon lengkapi/perbaiki bagian yang kurang pada laporan berikutnya.',
            merah: 'Kinerja perlu tindak lanjut segera. Koordinasikan dengan satker terkait untuk evaluasi dan pendampingan.',
        };

        function suggestFor(nilai) {
            if (nilai === '' || isNaN(nilai)) return '';
            nilai = Number(nilai);
            if (nilai >= AMBANG_HIJAU) return SARAN.hijau;
            if (nilai >= AMBANG_KUNING) return SARAN.kuning;
            return SARAN.merah;
        }

        document.querySelectorAll('.result-review-form').forEach(form => {
            const nilaiInput = form.querySelector('.nilai-input');
            const tindakLanjutInput = form.querySelector('.tindak-lanjut-input');
            const autofillBtn = form.querySelector('.autofill-tindak-lanjut');

            // Tombol "Gunakan saran otomatis": isi/timpa textarea sesuai nilai saat ini.
            autofillBtn.addEventListener('click', () => {
                const saran = suggestFor(nilaiInput.value);
                if (saran) {
                    tindakLanjutInput.value = saran;
                } else {
                    alert('Isi Nilai (0-100) dulu untuk memunculkan saran otomatis.');
                }
            });

            // Kalau kolom tindak lanjut masih kosong, auto-isi saat admin selesai mengetik nilai.
            nilaiInput.addEventListener('change', () => {
                if (!tindakLanjutInput.value.trim()) {
                    tindakLanjutInput.value = suggestFor(nilaiInput.value);
                }
            });
        });
    })();
</script>
@endpush

@endsection