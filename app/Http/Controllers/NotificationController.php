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
            'type' => 'indicator',
            'title' => 'Tugas baru diterima',
            'body' => "Anda mendapat tugas baru: {$indicator->judul}",
            'link' => route('user.inbox'),
        ]);
    }
}
}
