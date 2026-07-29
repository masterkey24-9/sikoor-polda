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
```

### 2. Apa langkah selanjutnya?
Backend Anda sekarang sudah punya:
1. **Autentikasi** (Breeze).
2. **Database** (Satker, User, Indicator, Result, Message).
3. **Fitur Upload** (PDF ke Storage).
4. **Fitur Chat** (Real-time via Pusher).
5. **Fitur Indikator** (Tugas untuk Satker).

**Sebagai Backend, tugas Anda sekarang adalah:**
* Memberikan data **API Key Pusher** dan **Struktur Database** ini kepada rekan Frontend Anda.
* Membantu rekan Frontend melakukan *testing* (apakah tombol di UI mereka sudah berhasil memanggil controller yang Anda buat).

Apakah Anda ingin saya buatkan semacam **"Ringkasan API"** atau **"Panduan Pengetesan"** yang bisa Anda berikan kepada rekan tim Anda agar dia lebih mudah bekerja? Atau ada bagian lain yang ingin Anda eksplorasi?