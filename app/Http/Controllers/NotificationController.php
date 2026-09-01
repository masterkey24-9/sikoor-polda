<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use app\Models\Indicator;

class NotificationController extends Controller
{
    
    public function data()
    {
        $userId = Auth::id();

        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $unreadCount = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }


    public function markRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['status' => 'ok']);
    }

  
    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    
    public static function notifyNewMessage(User $sender, int $satkerId, string $satkerNama, string $pesan): void
    {
        if ($sender->role === 'admin') {
            $recipients = User::where('satker_id', $satkerId)->where('role', 'satker')->get();
            $title = 'Pesan baru dari Admin';
            $link = route('messages.index');
        } else {
            $recipients = User::where('role', 'admin')->get();
            $title = "Pesan baru dari {$satkerNama}";
            $link = route('messages.index', ['satker_id' => $satkerId]);
        }

        foreach ($recipients as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'chat',
                'title' => $title,
                'body' => \Illuminate\Support\Str::limit($pesan, 80),
                'link' => $link,
            ]);
        }
    }


    public static function notifyNewDocument(string $satkerNama, string $judulIndikator): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'document',
                'title' => 'Dokumen baru masuk',
                'body' => "{$satkerNama} mengirim laporan: {$judulIndikator}",
                'link' => route('indicators.index'),
            ]);
        }
    }
    public static function notifyNewIndicator(Indicator $indicator): void
{
    $recipients = User::where('satker_id', $indicator->satker_id)
        ->where('role', 'satker')
        ->get();

    foreach ($recipients as $recipient) {
        Notification::create([
            'user_id' => $recipient->id,
            'type' => 'document',
            'title' => 'Tugas baru diterima',
            'body' => "Anda mendapat tugas baru: {$indicator->judul}",
            'link' => route('monitoring.saya'),
        ]);
    }
}
    public static function notifyResultReviewed(\App\Models\IndicatorResult $result): void
    {
        $recipients = User::where('satker_id', $result->satker_id)
            ->where('role', 'satker')
            ->get();

        $judul = $result->indicator->judul ?? 'laporan Anda';
        $statusLabel = $result->status === 'diterima' ? 'diterima' : 'perlu direvisi';

        foreach ($recipients as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'document',
                'title' => 'Laporan Anda telah dinilai',
                'body' => "\"{$judul}\" {$statusLabel}. Nilai: {$result->nilai}.",
                'link' => route('monitoring.saya'),
            ]);
        }
    }

    /**
     * Buat notifikasi otomatis untuk semua admin berdasarkan kondisi data IKPA periode aktif:
     *  1) Penurunan nilai IKPA signifikan per satker (dibanding periode sebelumnya).
     *  2) Deviasi pelaksanaan anggaran — indikator terkait anggaran masuk kategori Merah.
     *  3) Keterlambatan penyelesaian tagihan — belum ada laporan padahal periode sudah lewat.
     *  4) Batas waktu tindak lanjut — laporan menggantung (dikirim/direvisi) tanpa progres
     *     lebih dari beberapa hari.
     *
     * "Jadwal rapat koordinasi" (poin ke-5 di spek) belum bisa dibuat otomatis di sini karena
     * sistem belum punya data jadwal/rapat sama sekali — perlu fitur/tabel baru dulu kalau mau
     * dinotifikasi otomatis juga. Untuk sementara jenis ini masih harus dibuat manual.
     *
     * Aman dipanggil berkali-kali (idempotent): pakai firstOrCreate berdasarkan kombinasi
     * user + type + title (title sudah menyertakan label periode), jadi tidak akan dobel
     * setiap kali halaman dashboard dibuka ulang untuk periode yang sama.
     */
    public static function generateNotifikasiIkpa(
        \Illuminate\Support\Collection $satkerPerformance,
        \Illuminate\Support\Collection $nilaiPerIndikator,
        \Illuminate\Support\Collection $indicators,
        \Carbon\Carbon $rangeAkhir,
        string $labelPeriodeAktif
    ): void {
        $admins = User::where('role', 'admin')->get();
        if ($admins->isEmpty()) {
            return;
        }

        $buat = function (string $type, string $title, string $body, string $link) use ($admins) {
            foreach ($admins as $admin) {
                Notification::firstOrCreate(
                    ['user_id' => $admin->id, 'type' => $type, 'title' => $title],
                    ['body' => $body, 'link' => $link]
                );
            }
        };

        // 1) Penurunan nilai IKPA signifikan per satker
        $ambangPenurunan = config('sikoor.notifikasi_ambang_penurunan', 5);
        foreach ($satkerPerformance as $sp) {
            if (! str_starts_with($sp->trend_label, 'Turun')) {
                continue;
            }

            $besarPenurunan = (float) trim(str_replace('Turun', '', $sp->trend_label));
            if ($besarPenurunan < $ambangPenurunan) {
                continue;
            }

            $buat(
                'penurunan_ikpa',
                "Penurunan nilai IKPA - {$sp->nama_satker} ({$labelPeriodeAktif})",
                "Nilai IKPA {$sp->nama_satker} turun {$besarPenurunan} poin pada {$labelPeriodeAktif}, sekarang " . ($sp->nilai ?? '-') . '.',
                route('indicators.index')
            );
        }

        // 2) Deviasi pelaksanaan anggaran: indikator terkait anggaran masuk kategori Merah
        $judulAnggaran = ['Deviasi Halaman III DIPA', 'Penyerapan Anggaran', 'Belanja Kontraktual'];
        foreach ($nilaiPerIndikator as $item) {
            if (! in_array($item['judul'], $judulAnggaran, true) || $item['warna'] !== 'Kurang') {
                continue;
            }

            $buat(
                'deviasi_anggaran',
                "Deviasi pelaksanaan anggaran - {$item['judul']} ({$labelPeriodeAktif})",
                "Rata-rata nilai {$item['judul']} pada {$labelPeriodeAktif} masuk kategori Kurang (" . ($item['rata'] ?? '-') . ').',
                route('indicators.index')
            );
        }

        // 3) Keterlambatan penyelesaian tagihan: tugas 'Penyelesaian Tagihan' yang periodenya
        // sudah lewat tapi belum ada laporan sama sekali dari satker terkait.
        if ($rangeAkhir->isPast()) {
            foreach ($indicators->where('judul', 'Penyelesaian Tagihan') as $ind) {
                if ($ind->results->isNotEmpty()) {
                    continue;
                }

                $buat(
                    'keterlambatan_tagihan',
                    "Keterlambatan penyelesaian tagihan - {$ind->satker_nama} ({$labelPeriodeAktif})",
                    "{$ind->satker_nama} belum menyampaikan laporan Penyelesaian Tagihan untuk {$labelPeriodeAktif}.",
                    route('indicators.index')
                );
            }
        }

        // 4) Batas waktu tindak lanjut: laporan berstatus "dikirim"/"direvisi" yang menggantung
        // lebih dari beberapa hari tanpa progres.
        $batasHari = config('sikoor.notifikasi_batas_hari_tindak_lanjut', 7);
        foreach ($indicators as $ind) {
            $latestResult = $ind->results->sortByDesc('created_at')->first();
            if (! $latestResult || ! in_array($latestResult->status, ['dikirim', 'direvisi'], true)) {
                continue;
            }
            if ($latestResult->created_at->diffInDays(now()) < $batasHari) {
                continue;
            }

            $buat(
                'batas_tindak_lanjut',
                "Batas waktu tindak lanjut - {$ind->satker_nama}: {$ind->judul} ({$labelPeriodeAktif})",
                "Laporan \"{$ind->judul}\" dari {$ind->satker_nama} berstatus \"{$latestResult->status}\" sudah lebih dari {$batasHari} hari belum ada progres.",
                route('indicators.index')
            );
        }
    }
}