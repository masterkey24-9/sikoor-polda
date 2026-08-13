@extends('layouts.app')

@section('title', 'Tugas & Laporan')
@section('page-title', 'Tugas & laporan')

@section('sidebar')
    @include('components.sidebar-user')
@endsection

@section('content')

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

    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-xl">
        @forelse ($indicators ?? [] as $indicator)
            @php $latestResult = $indicator->results->sortByDesc('created_at')->first(); @endphp
            <div>
                <button type="button"
                        onclick="document.getElementById('form-{{ $indicator->id }}').classList.toggle('hidden')"
                        class="w-full flex items-start gap-4 px-5 py-4 text-left hover:bg-slate-50">
                    <i class="ti ti-clipboard-list text-slate-400 text-lg shrink-0 mt-0.5"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800">{{ $indicator->judul }}</p>
                        @if ($indicator->deskripsi)
                            <p class="text-xs text-slate-400 mt-0.5">{{ $indicator->deskripsi }}</p>
                        @endif
                        @if ($indicator->file_pdf)
                            <a href="{{ asset('storage/' . $indicator->file_pdf) }}" target="_blank"
                               onclick="event.stopPropagation()"
                               class="inline-flex items-center gap-1.5 text-xs text-navy-800 hover:underline mt-1.5">
                                <i class="ti ti-paperclip text-sm"></i> Lihat lampiran dari admin
                            </a>
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
                    <form method="POST" action="{{ route('indicator.upload', $indicator->id) }}"
                          enctype="multipart/form-data" class="bg-slate-50 rounded-lg p-4 space-y-3">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">
                                File PDF laporan
                                @if ($latestResult)
                                    <span class="font-normal text-slate-400">(mengunggah ulang akan mengirim laporan baru)</span>
                                @endif
                            </label>
                            <input type="file" name="file_pdf" accept="application/pdf" required
                                   class="w-full text-sm file:mr-3 file:h-9 file:px-3 file:rounded-lg file:border-0 file:bg-navy-900 file:text-white file:text-sm">
                            <p class="text-xs text-slate-400 mt-1">Maksimal 5MB, format PDF.</p>
                        </div>

                        <button type="submit"
                                class="h-10 px-4 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium">
                            Kirim laporan
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-slate-400 text-center">Belum ada tugas yang ditugaskan.</p>
        @endforelse
    </div>

@endsection