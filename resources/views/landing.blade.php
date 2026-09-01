@extends('layouts.app')

@section('title', 'Beranda')

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
    .anim-fade-up { opacity: 0; animation: simpatiFadeUp 0.7s ease-out forwards; }
    .anim-fade-in { opacity: 0; animation: simpatiFadeIn 0.9s ease-out forwards; }
    .anim-blob { opacity: 0; animation: simpatiBlobIn 1.1s ease-out forwards; }
    .delay-1 { animation-delay: .05s; }
    .delay-2 { animation-delay: .15s; }
    .delay-3 { animation-delay: .25s; }
    .delay-4 { animation-delay: .35s; }
    .delay-5 { animation-delay: .45s; }

    .landing-hero {
        background:
            radial-gradient(120% 90% at 85% 15%, rgba(212,175,55,0.16), transparent 55%),
            radial-gradient(90% 70% at 10% 100%, rgba(212,175,55,0.10), transparent 55%),
            linear-gradient(135deg, #2A1A0D 0%, #3B2312 45%, #4A2E16 75%, #5C3B1E 100%);
    }
    .landing-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.07;
        background-image: radial-gradient(circle, #D4AF37 1px, transparent 1px);
        background-size: 26px 26px;
        pointer-events: none;
    }
    .landing-hero-grid {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
    }
    .landing-shape {
        position: absolute;
        border: 1px solid rgba(212,175,55,0.18);
        border-radius: 1.5rem;
        transform: rotate(var(--rot, 12deg));
    }
</style>
@endpush

@section('content')

@php
    $landingLogo = asset('images/logo.png');
    $landingLogoExists = file_exists(public_path('images/logo.png'));
    $bidkeuLogo = asset('images/bidkeu.png');
    $bidkeuLogoExists = file_exists(public_path('images/bidkeu.png'));
@endphp

<div class="min-h-screen bg-canvas">

    {{-- ===== NAVBAR ===== --}}
    <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    @if ($landingLogoExists)
                        <img src="{{ $landingLogo }}" alt="Logo Polda Sumbar" class="w-9 h-9 object-contain">
                    @endif
                    @if ($bidkeuLogoExists)
                        <img src="{{ $bidkeuLogo }}" alt="Logo Bidkeu" class="w-9 h-9 object-contain">
                    @endif
                </div>
                <span class="font-display font-bold text-lg text-navy-950">SIMPATI <span class="text-gold-500">IKPA</span></span>
            </div>
            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-2 h-10 px-5 rounded-lg bg-navy-950 hover:bg-navy-900 text-white text-sm font-semibold transition">
                Masuk
                <i class="ti ti-login-2 text-base"></i>
            </a>
        </div>
    </header>

    {{-- ===== HERO ===== --}}
    <section class="landing-hero relative overflow-hidden">
        <div class="landing-hero-grid">
            <div class="landing-shape anim-blob delay-1" style="--rot:8deg; width:22rem; height:22rem; top:-6rem; right:8%;"></div>
            <div class="landing-shape anim-blob delay-2" style="--rot:-10deg; width:16rem; height:16rem; bottom:-4rem; right:22%;"></div>
            <div class="landing-shape anim-blob delay-3" style="--rot:20deg; width:12rem; height:12rem; top:35%; right:2%;"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-gold-500/10 rounded-full blur-3xl anim-blob delay-2"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 pt-20 pb-24">
            <div class="flex items-center gap-4 mb-8 anim-fade-up delay-1">
                <div class="flex items-center gap-3">
                    @if ($landingLogoExists)
                        <div class="w-14 h-14 rounded-xl bg-white/10 ring-1 ring-white/15 flex items-center justify-center p-2">
                            <img src="{{ $landingLogo }}" alt="Logo Polda Sumbar" class="w-full h-full object-contain">
                        </div>
                    @endif
                    @if ($bidkeuLogoExists)
                        <div class="w-14 h-14 rounded-xl bg-white/10 ring-1 ring-white/15 flex items-center justify-center p-2">
                            <img src="{{ $bidkeuLogo }}" alt="Logo Bidkeu" class="w-full h-full object-contain">
                        </div>
                    @endif
                </div>
                <div class="h-8 w-px bg-white/15"></div>
                <span class="text-slate-300 text-sm font-medium tracking-wide">Kepolisian Daerah Sumatera Barat</span>
            </div>

            <h1 class="font-display font-extrabold text-4xl sm:text-5xl lg:text-6xl text-white leading-tight anim-fade-up delay-2">
                SIMPATI <span class="text-gold-400">IKPA</span>
            </h1>

            <p class="mt-6 max-w-2xl text-xl sm:text-2xl font-semibold text-slate-100 leading-snug anim-fade-up delay-3">
                Platform terintegrasi untuk pemantauan tingkat lanjut, <span class="italic text-gold-400">Early Warning</span>,
                evaluasi dan layanan konsultasi.
            </p>

            <p class="mt-5 max-w-2xl text-slate-400 text-sm sm:text-base leading-relaxed anim-fade-up delay-4">
                Mendukung pengelolaan Indikator Kinerja Pelaksanaan Anggaran (IKPA) di seluruh satuan kerja
                Polda Sumatera Barat &mdash; mulai dari pemantauan capaian, deteksi dini risiko, penyusunan
                laporan, hingga komunikasi langsung dengan tim pengelola.
            </p>

            <div class="mt-9 flex flex-wrap items-center gap-4 anim-fade-up delay-5">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 h-12 px-7 rounded-lg bg-gold-500 hover:bg-gold-400 text-navy-950 text-sm font-bold transition shadow-lg shadow-gold-500/20">
                    Masuk ke Sistem
                    <i class="ti ti-arrow-right text-base"></i>
                </a>
                <a href="#fitur"
                   class="inline-flex items-center gap-2 h-12 px-7 rounded-lg border border-white/20 text-white text-sm font-semibold hover:bg-white/5 transition">
                    Lihat Fitur
                </a>
            </div>
        </div>
    </section>

    {{-- ===== FITUR ===== --}}
    <section id="fitur" class="max-w-7xl mx-auto px-6 py-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <div class="bg-white rounded-2xl p-6 border border-slate-200 hover:shadow-lg hover:-translate-y-0.5 transition">
                <div class="w-11 h-11 rounded-xl bg-navy-950/5 flex items-center justify-center mb-4">
                    <i class="ti ti-chart-line text-navy-900 text-xl"></i>
                </div>
                <h3 class="font-display font-semibold text-navy-950 mb-2">Monitoring</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Pemantauan capaian Indikator Kinerja Pelaksanaan Anggaran (IKPA) secara real-time
                    di seluruh satuan kerja.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200 hover:shadow-lg hover:-translate-y-0.5 transition">
                <div class="w-11 h-11 rounded-xl bg-navy-950/5 flex items-center justify-center mb-4">
                    <i class="ti ti-alert-triangle text-navy-900 text-xl"></i>
                </div>
                <h3 class="font-display font-semibold text-navy-950 mb-2">Early Warning System</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Sistem peringatan dini yang memberi notifikasi otomatis saat ada indikator
                    berisiko turun atau menyimpang dari target.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200 hover:shadow-lg hover:-translate-y-0.5 transition">
                <div class="w-11 h-11 rounded-xl bg-navy-950/5 flex items-center justify-center mb-4">
                    <i class="ti ti-report-analytics text-navy-900 text-xl"></i>
                </div>
                <h3 class="font-display font-semibold text-navy-950 mb-2">Evaluasi &amp; Pelaporan</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Rekapitulasi dan evaluasi capaian indikator per periode &mdash; bulanan, triwulan,
                    semester, hingga tahunan &mdash; untuk mendukung pengambilan keputusan.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200 hover:shadow-lg hover:-translate-y-0.5 transition">
                <div class="w-11 h-11 rounded-xl bg-navy-950/5 flex items-center justify-center mb-4">
                    <i class="ti ti-message-circle-2 text-navy-900 text-xl"></i>
                </div>
                <h3 class="font-display font-semibold text-navy-950 mb-2">Layanan Konsultasi</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Komunikasi langsung antara admin Polda dan satuan kerja melalui fitur pesan
                    dan chat untuk tindak lanjut kendala.
                </p>
            </div>

        </div>
    </section>

    {{-- ===== FOOTER ===== --}}
    <footer class="border-t border-slate-200 py-8">
        <div class="max-w-7xl mx-auto px-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} SIMPATI IKPA &mdash; Kepolisian Daerah Sumatera Barat. Seluruh hak cipta dilindungi.
        </div>
    </footer>
</div>
@endsection