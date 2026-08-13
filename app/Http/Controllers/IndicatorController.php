<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\Satker;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    /**
     * Daftar semua indicator (khusus admin), dipakai di resources/views/indicators/index.blade.php
     */
    public function index()
    {
        $indicators = Indicator::with(['satker', 'results'])->latest()->get();
        $satkers = Satker::orderBy('nama_satker')->get();

        return view('indicators.index', compact('indicators', 'satkers'));
    }

    /**
     * Buat indicator baru. Form memilih beberapa satker sekaligus (satker_id[]),
     * sementara tabel indicators hanya punya satu satker_id per baris,
     * jadi kita buat satu record Indicator untuk tiap satker yang dipilih.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'file_pdf' => 'nullable|mimes:pdf|max:10240',
            'periode' => 'nullable|date_format:Y-m',
            'satker_id' => 'required|array|min:1',
            'satker_id.*' => 'exists:satkers,id',
        ]);

        $filePath = null;
        if ($request->hasFile('file_pdf')) {
            $filePath = $request->file('file_pdf')->store('uploads', 'public');
        }

        // Default periode ke bulan berjalan kalau admin tidak mengisi,
        // supaya indicator tetap muncul saat difilter per periode di dashboard.
        $periode = $validated['periode'] ?? now()->format('Y-m');
        $periode .= '-01';

        foreach ($validated['satker_id'] as $satkerId) {
            $indicator = Indicator::create([
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'file_pdf' => $filePath,
                'satker_id' => $satkerId,
                'periode' => $periode,
            ]);

            NotificationController::notifyNewIndicator($indicator);
        }

        return redirect()->back()->with('success', 'Indicator berhasil dibuat dan dikirim ke satker terpilih.');
    }

    /**
     * Detail satu indicator + daftar laporan yang sudah masuk dari satker,
     * dipakai di resources/views/indicators/show.blade.php untuk admin menilai.
     */
    public function show($id)
    {
        $indicator = Indicator::with(['satker', 'results.satker'])->findOrFail($id);

        return view('indicators.show', compact('indicator'));
    }
}
