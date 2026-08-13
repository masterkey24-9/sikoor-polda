@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page-title', 'Profil saya')

@section('sidebar')
    @include(auth()->user()->role === 'admin' ? 'components.sidebar-admin' : 'components.sidebar-user')
@endsection

@section('content')
<div class="max-w-2xl space-y-6">

    @if (session('status') === 'profile-updated')
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
            Profil berhasil diperbarui.
        </div>
    @endif
    @if (session('status') === 'password-updated')
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
            Kata sandi berhasil diperbarui.
        </div>
    @endif

    {{-- Ringkasan akun --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-navy-900 text-white flex items-center justify-center text-xl font-medium shrink-0">
            {{ substr($user->name ?? 'A', 0, 1) }}
        </div>
        <div>
            <p class="font-display font-semibold text-lg text-navy-900">{{ $user->name }}</p>
            <p class="text-slate-400 text-sm">{{ $user->email }}</p>
            <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-full bg-gold-500/15 text-navy-900">
                {{ ucfirst($user->role ?? 'user') }}
            </span>
        </div>
    </div>

    {{-- Informasi profil --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="font-display font-semibold text-navy-900 mb-1">Informasi profil</h2>
        <p class="text-sm text-slate-400 mb-5">Perbarui nama dan alamat email akun Anda.</p>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 max-w-md">
            @csrf
            @method('patch')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nama</label>
                <input type="text" id="name" name="name" required autocomplete="name"
                       value="{{ old('name', $user->name) }}"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input type="email" id="email" name="email" required autocomplete="username"
                       value="{{ old('email', $user->email) }}"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="h-11 px-5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                Simpan perubahan
            </button>
        </form>
    </div>

    {{-- Ubah kata sandi --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="font-display font-semibold text-navy-900 mb-1">Ubah kata sandi</h2>
        <p class="text-sm text-slate-400 mb-5">Gunakan kata sandi yang panjang dan acak agar akun tetap aman.</p>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4 max-w-md">
            @csrf
            @method('put')

            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata sandi saat ini</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                @error('current_password', 'updatePassword')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata sandi baru</label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                @error('password', 'updatePassword')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi kata sandi baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-navy-800 focus:border-navy-800">
                @error('password_confirmation', 'updatePassword')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="h-11 px-5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-sm font-medium transition">
                Perbarui kata sandi
            </button>
        </form>
    </div>

    {{-- Hapus akun --}}
    <div class="bg-white rounded-2xl border border-red-200 p-6">
        <h2 class="font-display font-semibold text-red-700 mb-1">Hapus akun</h2>
        <p class="text-sm text-slate-400 mb-5">Setelah akun dihapus, seluruh data akan dihapus permanen. Simpan data yang masih diperlukan sebelum melanjutkan.</p>

        <button type="button" onclick="document.getElementById('deleteAccountForm').classList.toggle('hidden')"
                class="h-10 px-4 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium transition">
            Hapus akun
        </button>

        <form id="deleteAccountForm" method="POST" action="{{ route('profile.destroy') }}" class="hidden mt-4 max-w-md space-y-3">
            @csrf
            @method('delete')
            <div>
                <label for="delete_password" class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi dengan kata sandi</label>
                <input type="password" id="delete_password" name="password"
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600">
                @error('password', 'userDeletion')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    onclick="return confirm('Yakin ingin menghapus akun ini secara permanen?')"
                    class="h-10 px-4 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition">
                Konfirmasi hapus akun
            </button>
        </form>
    </div>

</div>
@endsection
