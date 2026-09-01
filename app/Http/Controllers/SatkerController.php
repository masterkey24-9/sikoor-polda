<?php

namespace App\Http\Controllers;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Http\Request;

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
     * Reset password SEMUA satker ke password acak baru, simpan hasilnya sementara di
     * session, lalu REDIRECT (bukan render langsung) ke halaman hasil.
     *
     * Pola redirect ini (Post/Redirect/Get) penting: kalau hasilnya dirender langsung
     * sebagai respons POST, lalu admin me-refresh halaman itu, browser akan nanya
     * "kirim ulang form?" — kalau di-iyakan, password akan di-generate ULANG (nimpa yang
     * sudah dicetak/di-download), padahal kertas yang sudah dicetak masih nunjukin yang lama.
     * Dengan redirect ke GET, refresh jadi aman (baca ulang data yang sama dari session).
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

            $passwordBaru = $this->generatePasswordAman();
            $satker->user->update(['password' => $passwordBaru]); // otomatis ter-hash (cast 'hashed')

            $hasil->push([
                'nama_satker' => $satker->nama_satker,
                'email' => $satker->user->email,
                'password' => $passwordBaru,
            ]);
        }

        session([
            'kredensial_hasil' => $hasil->all(),
            'kredensial_waktu' => now()->toIso8601String(),
        ]);

        return redirect()->route('satkers.cetakKredensialHasil');
    }

    /**
     * Menampilkan hasil generate password terakhir (dari session) — TIDAK generate ulang
     * apa pun, jadi aman di-refresh berkali-kali tanpa mengubah password yang sudah dicetak.
     */
    public function cetakKredensialHasil()
    {
        if (! session()->has('kredensial_hasil')) {
            return redirect()->route('satkers.cetakKredensialForm')
                ->with('error', 'Belum ada hasil cetak kredensial. Silakan generate ulang.');
        }

        $hasil = collect(session('kredensial_hasil'));
        $waktuCetak = \Carbon\Carbon::parse(session('kredensial_waktu'));

        return view('admin.satkers-cetak-hasil', compact('hasil', 'waktuCetak'));
    }

    /**
     * Karakter yang sengaja DIHINDARI karena gampang ketuker kalau dibaca dari kertas
     * cetakan atau ditulis tangan: l (L kecil), I (i besar), 1 (angka satu),
     * O (O besar), 0 (angka nol), o (o kecil).
     */
    private function generatePasswordAman(int $panjang = 8): string
    {
        $karakter = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $hasil = '';

        for ($i = 0; $i < $panjang; $i++) {
            $hasil .= $karakter[random_int(0, strlen($karakter) - 1)];
        }

        return $hasil;
    }
}