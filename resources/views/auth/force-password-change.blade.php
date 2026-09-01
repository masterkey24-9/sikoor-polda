@extends('layouts.app')

@section('title', 'Ganti Password')

@section('content')

<div class="min-h-screen flex items-center justify-center bg-navy-950 relative overflow-hidden px-4">

    <div class="absolute inset-0 opacity-[0.08]"
         style="background-image: radial-gradient(circle, #C89B3C 1px, transparent 1px); background-size: 28px 28px;"></div>
    <div class="absolute -top-32 -left-24 w-96 h-96 bg-gold-500/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -right-10 w-[26rem] h-[26rem] bg-navy-700/40 rounded-full blur-3xl"></div>

    <div class="w-full max-w-sm relative">

        <div class="flex flex-col items-center mb-6">
            @if (file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="Logo Simpati IKPA Polda Sumbar"
                     class="w-12 h-12 rounded-xl object-contain mb-3 shadow-lg">
            @else
                <div class="w-12 h-12 rounded-xl bg-gold-500 flex items-center justify-center text-navy-950 font-display font-bold text-xl mb-3">S</div>
            @endif
            <h1 class="font-display font-semibold text-xl text-white text-center">Ganti Password Anda</h1>
            <p class="text-slate-400 text-sm mt-2 text-center leading-relaxed">
                Ini pertama kalinya Anda login (atau password Anda baru saja direset admin).
                Demi keamanan, silakan ganti password default Anda sebelum melanjutkan.
            </p>
        </div>

        <div class="bg-white rounded-2xl p-7 shadow-xl">

            @if ($errors->updatePassword->any() ?? false)
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                    {{ $errors->updatePassword->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Password saat ini
                    </label>
                    <input type="password" id="current_password" name="current_password" required autofocus
                           placeholder="Password yang diberikan admin"
                           class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Password baru
                    </label>
                    <input type="password" id="password" name="password" required
                           placeholder="Minimal 8 karakter"
                           class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Ulangi password baru
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           placeholder="Ketik ulang password baru"
                           class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                </div>

                <button type="submit"
                        class="w-full h-11 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                    Simpan &amp; Lanjutkan
                </button>
            </form>
        </div>

        <p class="text-center text-slate-400 text-xs mt-6">
            Anda tidak bisa mengakses halaman lain sebelum mengganti password.
        </p>
    </div>
</div>

@endsection
