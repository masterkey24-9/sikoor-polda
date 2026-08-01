<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function index()
    {
        $indicators = Indicator::orderBy('created_at', 'desc')->get();
        $satkers = \App\Models\Satker::all();
        return view('indicators.index', compact('indicators', 'satkers'));
    }

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

    // BARU
    public function show($id)
    {
        $indicator = Indicator::with(['satker', 'results.satker'])->findOrFail($id);
        return view('indicators.show', compact('indicator'));
    }
}