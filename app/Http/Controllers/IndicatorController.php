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
        return response()->json($indicators);
    }

    // Menyimpan indikator baru (Khusus Admin)
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tenggat_waktu' => 'required|date',
        ]);

        $indicator = Indicator::create($request->all());

        return response()->json(['status' => 'Indikator berhasil dibuat!', 'data' => $indicator]);
    }
}
```

### 3. Tambahkan Model Indicator
Buka `app/Models/Indicator.php` dan pastikan `$fillable` sudah diset agar data bisa tersimpan:

```php:Indicator Model:app/Models/Indicator.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    use HasFactory;

    protected $fillable = ['judul', 'deskripsi', 'tenggat_waktu'];
}
```

### 4. Tambahkan Route Indikator
Buka `routes/web.php` dan tambahkan route untuk manajemen indikator ini:

```php:Routes:routes/web.php
// ... existing code ...

Route::middleware('auth')->group(function () {
    // ... existing route ...

    // Route Manajemen Indikator
    Route::get('/indicators', [\App\Http\Controllers\IndicatorController::class, 'index'])->name('indicators.index');
    Route::post('/indicators', [\App\Http\Controllers\IndicatorController::class, 'store'])->name('indicators.store');
    
    // Route untuk Satker mengunggah PDF (yang sebelumnya sudah kita buat)
    Route::post('/indicator/{indicator_id}/upload', [\App\Http\Controllers\IndicatorResultController::class, 'store'])->name('indicator.upload');
});
```

**Catatan untuk Anda:**
Dengan ini, Backend untuk **Fitur Indikator** sudah siap. Admin sekarang bisa melakukan `POST` ke `/indicators` untuk membuat tugas, dan Satker bisa melakukan `GET` ke `/indicators` untuk melihat apa saja tugas yang harus dikerjakan.

Apakah Anda ingin saya buatkan tampilan sederhana (Blade) untuk admin agar bisa mencoba fitur ini dari browser, atau kita lanjut ke fitur lain?