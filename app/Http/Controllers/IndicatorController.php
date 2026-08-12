<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\Satker;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function index()
    {
        $indicators = Indicator::with(['satker', 'results'])
            ->orderBy('created_at', 'desc')
            ->get();

        $satkers = Satker::all();

        return view('indicators.index', compact('indicators', 'satkers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'satker_id' => 'required|array|min:1',
            'satker_id.*' => 'exists:satkers,id',
            'file_pdf' => 'nullable|mimes:pdf|max:10240',
        ]);

        $filePath = null;
        if ($request->hasFile('file_pdf')) {
            $filePath = $request->file('file_pdf')->store('indicator-files', 'public');
        }

        $jumlah = 0;
        foreach ($request->satker_id as $satkerId) {
            $indicator = Indicator::create([
                'judul' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'satker_id' => $satkerId,
                'file_pdf' => $filePath,
            ]);

            NotificationController::notifyNewIndicator($indicator);
            $jumlah++;
        }

        return redirect()->back()->with(['status' => "Indikator berhasil dikirim ke {$jumlah} satker!"]);
    }

    public function show($id)
    {
        $indicator = Indicator::with(['satker', 'results.satker'])->findOrFail($id);
        return view('indicators.show', compact('indicator'));
    }
}
