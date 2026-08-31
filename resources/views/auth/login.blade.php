@extends('layouts.app')

@section('title', 'Masuk')

@push('styles')
<style>
    @keyframes simpatiFadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes simpatiFadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    @keyframes simpatiBlobIn {
        from { opacity: 0; transform: scale(0.85); }
        to   { opacity: 1; transform: scale(1); }
    }
    .anim-fade-up {
        opacity: 0;
        animation: simpatiFadeUp 0.6s ease-out forwards;
    }
    .anim-fade-in {
        opacity: 0;
        animation: simpatiFadeIn 0.8s ease-out forwards;
    }
    .anim-blob {
        opacity: 0;
        animation: simpatiBlobIn 1.1s ease-out forwards;
    }
    .delay-1 { animation-delay: .05s; }
    .delay-2 { animation-delay: .15s; }
    .delay-3 { animation-delay: .25s; }
    .delay-4 { animation-delay: .35s; }
    .delay-5 { animation-delay: .45s; }
</style>
@endpush

@section('content')

@php
    $loginBackgroundImage = asset('images/login-background.jpeg');
    $loginLogo = asset('images/logo.png');
    $loginLogoExists = file_exists(public_path('images/logo.png'));
    $loginBgExists = file_exists(public_path('images/login-background.jpeg'));
@endphp

<div class="min-h-screen flex bg-navy-950 relative overflow-hidden">

    
    <div class="hidden lg:block lg:w-1/2 relative">
        <div class="absolute inset-0 bg-gradient-to-br from-navy-950 via-navy-900 to-navy-950"></div>
        <div class="absolute inset-0 opacity-[0.08]"
             style="background-image: radial-gradient(circle, #C89B3C 1px, transparent 1px); background-size: 28px 28px;"></div>

        @if ($loginBgExists)
            <div class="absolute inset-0 bg-cover bg-center anim-fade-in"
                 style="background-image: url('{{ $loginBackgroundImage }}');"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-navy-950/10 via-navy-950/10 to-navy-950"></div>
        @endif

        <div class="absolute -top-32 -left-24 w-96 h-96 bg-gold-500/20 rounded-full blur-3xl anim-blob delay-1"></div>
        <div class="absolute -bottom-40 -left-10 w-[26rem] h-[26rem] bg-navy-700/40 rounded-full blur-3xl anim-blob delay-2"></div>
    </div>

    {{-- Panel kanan: form login (digeser ke sisi kanan layar) --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center px-4 relative overflow-hidden">
        {{-- Dekorasi background untuk panel kanan --}}
        <div class="absolute inset-0 bg-gradient-to-br from-navy-950 via-navy-900 to-navy-950 lg:hidden"></div>
        <div class="absolute inset-0 opacity-[0.08] lg:hidden"
             style="background-image: radial-gradient(circle, #C89B3C 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="absolute -top-32 -right-24 w-96 h-96 bg-gold-500/20 rounded-full blur-3xl anim-blob delay-1"></div>
        <div class="absolute -bottom-40 -right-32 w-[28rem] h-[28rem] bg-navy-700/50 rounded-full blur-3xl anim-blob delay-2"></div>
        <div class="absolute top-1/2 right-0 translate-x-1/3 -translate-y-1/2 w-[36rem] h-[36rem] border border-gold-500/10 rounded-full anim-blob delay-3"></div>

        <div class="w-full max-w-sm relative">

            <div class="flex flex-col items-center mb-8">
                @if ($loginLogoExists)
                    <img src="{{ $loginLogo }}" alt="Logo Simpati IKPA Polda Sumbar"
                         class="w-12 h-12 rounded-xl object-contain mb-3 shadow-lg anim-fade-up delay-1">
                @else
                    <div class="w-12 h-12 rounded-xl bg-gold-500 flex items-center justify-center text-navy-950 font-display font-bold text-xl mb-3 anim-fade-up delay-1">S</div>
                @endif
                <h1 class="font-display font-semibold text-xl text-white anim-fade-up delay-2">SIMPATI IKPA</h1>
                <p class="text-slate-400 text-sm mt-1 text-center leading-relaxed anim-fade-up delay-3">
                    Sistem Informasi Monitoring Pemantauan dan Tindak Lanjut Indikator Kinerja Pelaksanaan Anggaran
                </p>
            </div>

            <div class="bg-white rounded-2xl p-7 shadow-xl anim-fade-up delay-4">
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

            <p class="text-center text-slate-500 text-xs mt-6 text-white anim-fade-up delay-5">
                Akses khusus personel Polda Sumbar dan satuan kerja terdaftar.
            </p>
        </div>
    </div>
</div>
@endsection