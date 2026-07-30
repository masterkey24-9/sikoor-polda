<?php
namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IndicatorResultController extends Controller
{
    public function store(Request $request, $indicator_id)
    {
        $request->validate([
            'file_pdf' => 'required|mimes:pdf|max:5120'
        ]);

        $user = Auth::user();

        if ($user->role !== 'satker' || empty($user->satker_id)) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $filePath = $request->file('file_pdf')->store('uploads', 'public');

        IndicatorResult::create([
            'indicator_id' => $indicator_id,
            'satker_id' => $user->satker_id,
            'file_pdf' => $filePath,
            'status' => 'dikirim'
        ]);

        return redirect()->back()->with('success', 'Laporan berhasil dikirim.');
    }
}
