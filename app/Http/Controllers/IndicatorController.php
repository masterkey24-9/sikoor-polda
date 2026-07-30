<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    // Menampilkan daftar semua indikator (untuk Admin dan Satker)
    public function index()
    {
        $indicators = Indicator::orderBy('created_at', 'desc')->get();
        return view('indicators.index', compact('indicators'));
    }

    // Menyimpan indikator baru (Khusus Admin)
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tenggat_waktu' => 'required|date',
            'satker_id' => 'required|exists:satkers,id'
        ]);

        $indicator = Indicator::create([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'tenggat_waktu' => $request->tenggat_waktu,
            'satker_id' => $request->satker_id,
        ]);

        return redirect()->back()->with(['status' => 'Indikator berhasil dibuat!', 'data' => $indicator]);
    }
}