<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MessageController extends Controller
{
    /**
     * Ambang batas "online": kalau heartbeat terakhir kurang dari sekian detik yang lalu,
     * dianggap online. Dipakai di beberapa tempat, jadi disatukan di sini.
     */
    private const ONLINE_THRESHOLD_SECONDS = 30;

    private function isOnline(?\Illuminate\Support\Carbon $lastSeenAt): bool
    {
        return $lastSeenAt !== null && $lastSeenAt->gt(now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS));
    }

    /**
     * Teks "terakhir online" yang manusiawi, atau null kalau belum pernah online sama sekali.
     */
    private function lastSeenLabel(?\Illuminate\Support\Carbon $lastSeenAt): ?string
    {
        return $lastSeenAt ? $lastSeenAt->diffForHumans() : null;
    }

    /**
     * Menampilkan halaman Live chat.
     *
     * - Admin: melihat daftar semua Satker (untuk dipilih thread-nya) di sisi kiri.
     * - Satker: langsung masuk ke thread miliknya sendiri (hanya dengan Admin).
     */
    public function index()
    {
        if (Auth::user()->role === 'admin') {
            $satkers = Satker::with('user:id,satker_id,last_seen_at')->orderBy('nama_satker')->get()->map(function ($satker) {
                $lastMessage = Message::where('satker_id', $satker->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $satker->last_pesan = $lastMessage->pesan ?? 'Belum ada pesan';

                $lastSeenAt = $satker->user->last_seen_at ?? null;
                $satker->is_online = $this->isOnline($lastSeenAt);
                $satker->last_seen_label = $this->lastSeenLabel($lastSeenAt);

                return $satker;
            });

            return view('admin.chat', compact('satkers'));
        }

        return view('user.chat');
    }

    /**
     * Mengambil riwayat pesan dalam format JSON (dipakai oleh JS di halaman chat).
     */
    public function data(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $request->validate([
                'satker_id' => 'required|exists:satkers,id',
            ]);
            $satkerId = $request->query('satker_id');
        } else {
            $satkerId = $user->satker_id;

            if (! $satkerId) {
                return response()->json(['message' => 'Akun ini belum terhubung ke Satker manapun.'], 422);
            }
        }

        $messages = Message::with('user:id,name,role')
            ->where('satker_id', $satkerId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Menyimpan pesan baru ke database (satu thread, satu satker).
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'pesan' => 'required|string|max:1000',
        ]);

        if ($user->role === 'admin') {
            $request->validate([
                'satker_id' => 'required|exists:satkers,id',
            ]);
            $satkerId = $request->satker_id;
        } else {
            $satkerId = $user->satker_id;

            if (! $satkerId) {
                return response()->json(['message' => 'Akun ini belum terhubung ke Satker manapun.'], 422);
            }
        }

        $message = Message::create([
            'user_id' => $user->id,
            'satker_id' => $satkerId,
            'pesan' => $request->pesan,
        ]);

        Cache::forget("chat_typing_{$satkerId}_{$user->role}");

        event(new \App\Events\MessageSent($message));

        $satkerNama = Satker::find($satkerId)->nama_satker ?? 'Satker';
        \App\Http\Controllers\NotificationController::notifyNewMessage($user, $satkerId, $satkerNama, $request->pesan);

        return response()->json(['status' => 'Pesan terkirim!', 'message' => $message->load('user:id,name,role')]);
    }

    /**
     * Kirim satu pesan yang sama ke SEMUA satker sekaligus (broadcast admin).
     * Setiap satker tetap dapat baris pesan masing-masing di thread-nya sendiri
     * (tabel `messages` tidak berubah struktur), jadi tetap kompatibel dengan
     * fitur notifikasi & event realtime yang sudah ada per-thread.
     */
    public function broadcastStore(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Hanya admin yang bisa mengirim pesan ke semua satker.'], 403);
        }

        $request->validate([
            'pesan' => 'required|string|max:1000',
        ]);

        $satkers = Satker::all();
        $terkirim = 0;

        foreach ($satkers as $satker) {
            $message = Message::create([
                'user_id' => $user->id,
                'satker_id' => $satker->id,
                'pesan' => $request->pesan,
            ]);

            event(new \App\Events\MessageSent($message));

            \App\Http\Controllers\NotificationController::notifyNewMessage(
                $user, $satker->id, $satker->nama_satker, $request->pesan
            );

            $terkirim++;
        }

        return response()->json([
            'status' => "Pesan terkirim ke {$terkirim} satker.",
            'pesan' => $request->pesan,
        ]);
    }

    /**
     * "Detak jantung" kehadiran — dipanggil berkala (tiap ~15 detik) dari JS selama halaman
     * chat terbuka. Nyimpen waktu heartbeat terakhir ke kolom users.last_seen_at (persisten).
     */
    public function heartbeat()
    {
        Auth::user()->update(['last_seen_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Menandai user ini "sedang mengetik" di thread satker tertentu. Sinyal ini pakai cache
     * (bukan kolom database) karena sifatnya sangat sementara (auto-expired 4 detik).
     */
    public function typing(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'satker_id' => 'required|exists:satkers,id',
        ]);
        $satkerId = $request->satker_id;

        if ($user->role === 'satker' && (int) $user->satker_id !== (int) $satkerId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        Cache::put("chat_typing_{$satkerId}_{$user->role}", true, now()->addSeconds(4));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Status lawan bicara untuk satu thread: online, sedang mengetik, dan kapan terakhir
     * online (kalau lagi offline). Dipanggil berkala (tiap ~2 detik) saat sebuah thread
     * sedang dibuka.
     */
    public function status(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $request->validate([
                'satker_id' => 'required|exists:satkers,id',
            ]);
            $satkerId = $request->satker_id;

            $satkerUser = Satker::find($satkerId)?->user;
            $lastSeenAt = $satkerUser?->last_seen_at;
            $online = $this->isOnline($lastSeenAt);
            $typing = Cache::has("chat_typing_{$satkerId}_satker");
        } else {
            $satkerId = $user->satker_id;

            $adminTerbaru = User::where('role', 'admin')
                ->whereNotNull('last_seen_at')
                ->orderByDesc('last_seen_at')
                ->first();

            $lastSeenAt = $adminTerbaru?->last_seen_at;
            $online = $this->isOnline($lastSeenAt);
            $typing = $satkerId ? Cache::has("chat_typing_{$satkerId}_admin") : false;
        }

        return response()->json([
            'online' => $online,
            'typing' => $typing,
            'last_seen' => $online ? null : $this->lastSeenLabel($lastSeenAt),
        ]);
    }

    /**
     * Status online + "terakhir online" untuk SEMUA satker sekaligus — dipakai admin untuk
     * memantau seluruh daftar satker (bukan cuma thread yang lagi dibuka), refresh berkala.
     */
    public function onlineSatkers()
    {
        $data = Satker::with('user:id,satker_id,last_seen_at')->get()
            ->map(function ($satker) {
                $lastSeenAt = $satker->user->last_seen_at ?? null;

                return [
                    'satker_id' => $satker->id,
                    'online' => $this->isOnline($lastSeenAt),
                    'last_seen' => $this->isOnline($lastSeenAt) ? null : $this->lastSeenLabel($lastSeenAt),
                ];
            })
            ->values();

        return response()->json([
            'online' => $data->where('online', true)->pluck('satker_id')->values(),
            'presence' => $data,
        ]);
    }
}