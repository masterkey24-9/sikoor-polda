@extends('layouts.app')

@section('title', 'Peringatan Satker')
@section('page-title', 'Peringatan Satker')

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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <p class="text-sm font-medium text-slate-700 mb-1">Buat peringatan baru</p>
            <p class="text-xs text-slate-400 mb-4">
                Hanya bisa untuk satker yang bulan ini berkategori merah (status "Perlu Perhatian" atau nilai IKPA &lt; 70).
                Kalau batas waktu terlewati sebelum peringatan ditutup, satker itu <span class="font-medium text-red-600">tidak bisa mengirim laporan baru</span> sampai admin menyelesaikan peringatannya.
            </p>

            <form method="POST" action="{{ route('peringatan.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="satker_id" class="block text-sm font-medium text-slate-700 mb-1.5">Satker (kategori merah bulan ini)</label>
                    <select id="satker_id" name="satker_id" required
                            class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                        <option value="" disabled selected>-- Pilih satker --</option>
                        @forelse ($satkerMerah ?? [] as $satker)
                            <option value="{{ $satker->id }}" @selected(old('satker_id') == $satker->id)>{{ $satker->nama_satker }}</option>
                        @empty
                            <option value="" disabled>Tidak ada satker berkategori merah bulan ini</option>
                        @endforelse
                    </select>
                </div>

                <div>
                    <label for="pesan" class="block text-sm font-medium text-slate-700 mb-1.5">Pesan peringatan</label>
                    <textarea id="pesan" name="pesan" rows="3" required maxlength="500"
                              placeholder="Contoh: Segera lengkapi laporan Penyerapan Anggaran, nilai IKPA saat ini di bawah standar."
                              class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 resize-none">{{ old('pesan') }}</textarea>
                </div>

                <div>
                    <label for="batas_waktu" class="block text-sm font-medium text-slate-700 mb-1.5">Batas waktu</label>
                    <input type="datetime-local" id="batas_waktu" name="batas_waktu" required
                           value="{{ old('batas_waktu') }}"
                           class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                </div>

                <button type="submit"
                        class="h-11 px-5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                    Buat peringatan
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
            <p class="text-sm font-medium text-slate-700 px-6 py-4">Daftar peringatan</p>

            @forelse ($peringatan ?? [] as $p)
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800">{{ $p->satker->nama_satker ?? '-' }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $p->pesan }}</p>
                            <p class="text-xs text-slate-400 mt-1.5">
                                Batas waktu: {{ $p->batas_waktu->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>

                        @if ($p->status === 'selesai')
                            <span class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500">Selesai</span>
                        @elseif ($p->sudahLewatBatasWaktu())
                            <span class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-600">Lewat batas waktu</span>
                        @else
                            <span class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-50 text-amber-600">Aktif</span>
                        @endif
                    </div>

                    @if ($p->status === 'aktif')
                        <div class="flex items-center gap-3 mt-3">
                            <form method="POST" action="{{ route('peringatan.selesai', $p->id) }}">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-navy-800 hover:underline">
                                    Tandai selesai (buka kunci upload)
                                </button>
                            </form>
                            <form method="POST" action="{{ route('peringatan.destroy', $p->id) }}"
                                  onsubmit="return confirm('Hapus peringatan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <p class="px-6 py-8 text-center text-sm text-slate-400">Belum ada peringatan dibuat.</p>
            @endforelse
        </div>

    </div>

@endsection
