<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Mengambil daftar notifikasi milik user yang sedang login + jumlah belum dibaca.
     * Dipanggil berulang (polling) oleh JS di topbar.
     */
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

    /**
     * Menandai satu notifikasi sebagai sudah dibaca (dipanggil saat notifikasi diklik).
     */
    public function markRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Menandai semua notifikasi milik user sebagai sudah dibaca.
     */
    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Helper: buat notifikasi pesan chat baru untuk "lawan bicara" dari $sender.
     *
     * - Kalau pengirim admin  -> notifikasi dikirim ke semua user Satker terkait.
     * - Kalau pengirim satker -> notifikasi dikirim ke semua user ber-role admin.
     */
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

    /**
     * Helper: buat notifikasi dokumen baru untuk semua admin, dipanggil saat
     * Satker mengunggah laporan (IndicatorResult) baru.
     */
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
}
