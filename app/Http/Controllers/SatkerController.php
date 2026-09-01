<?php

namespace App\Http\Controllers;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SatkerController extends Controller
{
    public function index()
    {
        $satkers = Satker::with('user')->orderBy('nama_satker')->get();
        return view('satkers.index', compact('satkers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_satker' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $satker = Satker::create([
            'nama_satker' => $request->nama_satker,
        ]);

        User::create([
            'name' => $request->nama_satker,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'satker',
            'satker_id' => $satker->id,
        ]);

        return redirect()->back()->with('success', "Satker \"{$satker->nama_satker}\" berhasil ditambahkan beserta akun login.");
    }

    public function destroy($id)
    {
        $satker = Satker::findOrFail($id);

        User::where('satker_id', $satker->id)->delete();

        $satker->delete();

        return redirect()->back()->with('success', 'Satker berhasil dihapus.');
    }

    /**
     * Halaman konfirmasi sebelum reset + cetak kredensial semua satker. Wajib lewat sini
     * dulu (bukan langsung tombol satu klik) karena aksinya destruktif — mengganti
     * password SEMUA akun satker sekaligus.
     */
    public function cetakKredensialForm()
    {
        $totalSatker = Satker::whereHas('user')->count();

        return view('admin.satkers-cetak-konfirmasi', compact('totalSatker'));
    }

    /**
     * Reset password SEMUA satker ke password acak baru, lalu tampilkan halaman
     * cetak berisi tabel Nama Satker / Username / Password baru.
     *
     * PENTING: password lama tidak bisa ditampilkan lagi karena tersimpan ter-hash
     * (satu arah) — satu-satunya cara menyediakan tabel kredensial yang lengkap
     * adalah dengan membuat password baru untuk semua akun. Password lama otomatis
     * tidak berlaku lagi setelah ini.
     */
    public function cetakKredensial(Request $request)
    {
        $request->validate([
            'konfirmasi' => 'accepted',
        ], [
            'konfirmasi.accepted' => 'Anda harus mencentang kotak konfirmasi terlebih dahulu.',
        ]);

        $satkers = Satker::with('user')->orderBy('nama_satker')->get();
        $hasil = collect();

        foreach ($satkers as $satker) {
            if (! $satker->user) {
                continue; // satker tanpa akun login, lewati
            }

            $passwordBaru = Str::random(8);
            // password_changed_at di-reset ke null (forceFill karena bukan mass-assignable),
            // supaya satker WAJIB ganti password lagi di login berikutnya setelah direset.
            $satker->user->forceFill([
                'password' => $passwordBaru,
                'password_changed_at' => null,
            ])->save();

            $hasil->push([
                'nama_satker' => $satker->nama_satker,
                'email' => $satker->user->email,
                'password' => $passwordBaru,
            ]);
        }

        return view('admin.satkers-cetak-hasil', [
            'hasil' => $hasil,
            'waktuCetak' => now(),
        ]);
    }
}