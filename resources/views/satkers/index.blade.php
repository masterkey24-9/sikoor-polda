@extends('layouts.app')

@section('title', 'Satker')
@section('page-title', 'Kelola satker')

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

    {{-- Form tambah satker baru --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 max-w-xl">
        <p class="text-sm font-medium text-slate-700 mb-4">Tambah satker baru</p>

        <form method="POST" action="{{ route('satkers.store') }}" class="flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <label for="nama_satker" class="block text-sm font-medium text-slate-700 mb-1.5">Nama satker</label>
                <input type="text" id="nama_satker" name="nama_satker" required maxlength="255"
                       placeholder="Contoh: Polres Solok"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800">
            </div>
            <button type="submit"
                    class="h-11 px-5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition shrink-0">
                Tambah
            </button>
        </form>
    </div>

    {{-- Daftar satker terdaftar --}}
    <p class="text-sm font-medium text-slate-700 mb-3">Daftar satker terdaftar</p>

    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-xl">
        @forelse ($satkers as $satker)
            <div class="flex items-center gap-3 px-5 py-3.5">
                <i class="ti ti-building-fortress text-slate-400 text-lg shrink-0"></i>
                <span class="flex-1 text-sm text-slate-800">{{ $satker->nama_satker }}</span>

                <form method="POST" action="{{ route('satkers.destroy', $satker->id) }}"
                      onsubmit="return confirm('Hapus satker {{ $satker->nama_satker }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-slate-400 hover:text-red-500" aria-label="Hapus {{ $satker->nama_satker }}">
                        <i class="ti ti-trash text-lg"></i>
                    </button>
                </form>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-slate-400 text-center">Belum ada satker terdaftar.</p>
        @endforelse
    </div>

@endsection