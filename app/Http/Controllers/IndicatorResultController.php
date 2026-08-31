<?php
namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Kunci upload kalau satker ini punya peringatan aktif yang batas waktunya
        // sudah lewat. Admin harus menutup peringatan itu dulu (tombol "Tandai
        // selesai" di halaman Peringatan Satker) sebelum satker bisa upload lagi.
        $peringatanMengunci = \App\Models\PeringatanSatker::where('satker_id', $user->satker_id)
            ->mengunci()
            ->latest('batas_waktu')
            ->first();

        if ($peringatanMengunci) {
            return redirect()->back()->with('error',
                'Satker Anda dikunci dari pengiriman laporan karena melewati batas waktu peringatan ('
                . $peringatanMengunci->batas_waktu->translatedFormat('d M Y, H:i')
                . '). Hubungi admin untuk menyelesaikan peringatan tersebut.'
            );
        }

        $indicator = Indicator::findOrFail($indicator_id);

        
        if ($indicator->satker_id !== $user->satker_id) {
            return redirect()->back()->with('error', 'Indicator ini bukan ditugaskan untuk satker Anda.');
        }

        $filePath = $request->file('file_pdf')->store('uploads', 'public');

        IndicatorResult::create([
            'indicator_id' => $indicator_id,
            'satker_id' => $user->satker_id,
            'file_pdf' => $filePath,
            'status' => 'dikirim'
        ]);

        NotificationController::notifyNewDocument($user->name, $indicator->judul ?? 'indikator');

        return redirect()->back()->with('success', 'Laporan berhasil dikirim.');
    }

    /**
     * Admin menilai laporan yang masuk: mengisi nilai (0-100), mengubah status
     * (diterima/direvisi), dan opsional catatan_admin. Sebelumnya kolom
     * 'nilai' dan perubahan status tidak pernah dipakai di manapun.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $validated = $request->validate([
            'status' => 'required|in:diterima,direvisi',
            'nilai' => 'required|integer|min:0|max:100',
            'catatan_admin' => 'nullable|string|max:1000',
        ]);

        $result = IndicatorResult::findOrFail($id);
        $result->update($validated);

        NotificationController::notifyResultReviewed($result);
        
        return redirect()->back()->with('success', 'Penilaian laporan berhasil disimpan.');
    }
}