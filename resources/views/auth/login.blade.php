@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-navy-950 px-4">
    <div class="w-full max-w-sm">

        <div class="flex flex-col items-center mb-8">
            <div class="w-12 h-12 rounded-xl bg-gold-500 flex items-center justify-center text-navy-950 font-display font-bold text-xl mb-3">S</div>
            <h1 class="font-display font-semibold text-xl text-white">Sikoor Polda Sumbar</h1>
            <p class="text-slate-400 text-sm mt-1">Sistem koordinasi antar satuan kerja</p>
        </div>

        <div class="bg-white rounded-2xl p-7 shadow-xl">
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" id="email" name="email" required autofocus
                           value="{{ old('email') }}"
                           placeholder="nama@poldasumbar.go.id"
                           class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata sandi</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Masukkan kata sandi"
                           class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" name="remember" class="rounded border-slate-300">
                    Ingat saya
                </label>

                <button type="submit"
                        class="w-full h-11 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center text-slate-500 text-xs mt-6">
            Akses khusus personel Polda Sumbar dan satuan kerja terdaftar.
        </p>
    </div>
</div>
@endsection
