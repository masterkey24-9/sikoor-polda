@extends('layouts.app')

@section('title', 'Indikator IKPA')
@section('page-title', 'Indikator IKPA')

@section('sidebar')
    @include('components.sidebar-admin')
@endsection

@section('content')

    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-navy-800 hover:underline mb-4">
        <i class="ti ti-arrow-left text-base"></i> Kembali ke Dashboard
    </a>

    @if (session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-600 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-slate-50 rounded-2xl p-6 mb-6 border border-slate-100">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
            <div>
                <h1 class="text-lg font-display font-semibold text-navy-950">Indikator IKPA</h1>
                <p class="text-sm text-slate-500">Rata-rata capaian tiap indikator periode {{ $periodeAktif->translatedFormat('F Y') }}</p>
            </div>

            <form method="GET" action="{{ route('ikpa-indikator.index') }}" class="flex items-end gap-2">
                <div>
                    <label for="filterPeriode" class="block text-xs font-medium text-slate-500 mb-1.5">Pilih Bulan</label>
                    <input type="month" id="filterPeriode" name="periode"
                           value="{{ $periodeAktif->format('Y-m') }}"
                           onchange="this.form.submit()"
                           class="h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($kartuIndikator as $item)
                <div class="bg-white rounded-xl p-5 border border-slate-200">
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <p class="text-sm font-medium text-slate-700">{{ $item['nama'] }}</p>
                        <span class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-medium bg-blue-50 text-blue-600">
                            Bobot {{ number_format($item['bobot'], 2) }}%
                        </span>
                    </div>

                    <p class="text-3xl font-display font-bold text-navy-950 mb-3">
                        {{ !is_null($item['rata']) ? number_format($item['rata'], 2) . '%' : '-' }}
                    </p>

                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden mb-2">
                        <div class="h-full rounded-full {{ $item['kelas_bar'] }}"
                             style="width: {{ !is_null($item['rata']) ? min(100, max(2, $item['rata'])) : 0 }}%"></div>
                    </div>

                    <p class="flex items-center gap-1.5 text-xs font-medium {{ $item['status_kelas'] }}">
                        <i class="ti {{ $item['aman'] ? 'ti-circle-check' : 'ti-circle-x' }} text-sm"></i>
                        {{ $item['status_label'] }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-400 col-span-full text-center py-8">Belum ada indikator. Tambahkan lewat panel di bawah.</p>
            @endforelse
        </div>
    </div>

    {{-- ================= PENGATURAN BOBOT INDIKATOR ================= --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <p class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Pengaturan Bobot Indikator</p>
                <p class="text-xs mt-1 {{ $totalBobot == 100 ? 'text-slate-400' : 'text-amber-600' }}">
                    Total bobot saat ini: {{ number_format($totalBobot, 2) }}%
                    {{ $totalBobot == 100 ? '' : '— idealnya berjumlah 100%' }}
                </p>
            </div>
            <button type="button" onclick="document.getElementById('modalTambahIndikator').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 h-10 px-4 rounded-lg bg-navy-800 text-white text-sm font-medium hover:bg-navy-900">
                <i class="ti ti-plus text-base"></i> Tambah Indikator
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-slate-400 border-b border-slate-100">
                        <th class="text-left font-medium pb-2 w-24">Kode</th>
                        <th class="text-left font-medium pb-2">Nama Indikator</th>
                        <th class="text-right font-medium pb-2 w-32">Bobot (%)</th>
                        <th class="text-center font-medium pb-2 w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarBobot as $item)
                        {{-- Form update ditaruh di luar <tr> lalu dihubungkan lewat atribut form="..."
                             (HTML5), supaya markup <table> tetap valid (form tidak boleh langsung
                             membungkus tr/td). --}}
                        <form id="form-update-{{ $item->id }}" method="POST" action="{{ route('ikpa-indikator.update', $item->id) }}">
                            @csrf
                            @method('PUT')
                        </form>
                        <tr>
                            <td class="py-2.5 text-slate-500 font-mono text-xs">{{ $item->kode }}</td>
                            <td class="py-2.5">
                                <input type="text" name="nama" value="{{ $item->nama }}" required
                                       form="form-update-{{ $item->id }}"
                                       class="w-full h-9 px-2.5 rounded-lg border border-transparent hover:border-slate-200 focus:border-slate-300 text-sm focus:outline-none">
                            </td>
                            <td class="py-2.5">
                                <input type="number" name="bobot" value="{{ $item->bobot }}" step="0.01" min="0" max="100" required
                                       form="form-update-{{ $item->id }}"
                                       class="w-24 ml-auto block h-9 px-2.5 rounded-lg border border-transparent hover:border-slate-200 focus:border-slate-300 text-sm text-right focus:outline-none">
                            </td>
                            <td class="py-2.5">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="submit" form="form-update-{{ $item->id }}" title="Simpan"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-emerald-600 hover:bg-emerald-50">
                                        <i class="ti ti-device-floppy text-base"></i>
                                    </button>
                                    <form method="POST" action="{{ route('ikpa-indikator.destroy', $item->id) }}"
                                          onsubmit="return confirm('Hapus indikator {{ $item->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50">
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-slate-400 text-xs">Belum ada indikator.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= MODAL TAMBAH INDIKATOR ================= --}}
    <div id="modalTambahIndikator" class="hidden fixed inset-0 bg-navy-950/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="text-base font-semibold text-navy-950">Tambah Indikator IKPA</p>
                <button type="button" onclick="document.getElementById('modalTambahIndikator').classList.add('hidden')"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100">
                    <i class="ti ti-x text-base"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('ikpa-indikator.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Nama Indikator</label>
                    <input type="text" name="nama" required placeholder="Contoh: Retur SP2D"
                           class="w-full h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Bobot (%)</label>
                    <input type="number" name="bobot" step="0.01" min="0" max="100" required placeholder="Contoh: 10"
                           class="w-full h-10 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
                </div>
                <p class="text-xs text-slate-400">Kode akan dibuat otomatis. Nama indikator akan muncul di dropdown "Buat Indikator" pada halaman Indicators & upload.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modalTambahIndikator').classList.add('hidden')"
                            class="h-10 px-4 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100">
                        Batal
                    </button>
                    <button type="submit" class="h-10 px-4 rounded-lg bg-navy-800 text-white text-sm font-medium hover:bg-navy-900">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
