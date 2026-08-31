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
     * Menampilkan halaman Live chat.
     *
     * - Admin: melihat daftar semua Satker (untuk dipilih thread-nya) di sisi kiri.
     * - Satker: langsung masuk ke thread miliknya sendiri (hanya dengan Admin).
     */
    public function index()
    {
        if (Auth::user()->role === 'admin') {
            // Daftar satker beserta pratinjau pesan terakhir + status online (dari cache heartbeat).
            $satkers = Satker::with('user:id,satker_id')->orderBy('nama_satker')->get()->map(function ($satker) {
                $lastMessage = Message::where('satker_id', $satker->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $satker->last_pesan = $lastMessage->pesan ?? 'Belum ada pesan';
                $satker->is_online = $satker->user ? Cache::has('chat_online_' . $satker->user->id) : false;

                return $satker;
            });

            return view('admin.chat', compact('satkers'));
        }

        return view('user.chat');
    }

    /**
     * Mengambil riwayat pesan dalam format JSON (dipakai oleh JS di halaman chat).
     *
     * - Admin WAJIB mengirim ?satker_id=X untuk menentukan thread mana yang dibuka.
     * - Satker otomatis dibatasi ke satker_id miliknya sendiri (tidak bisa lihat thread lain).
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
            // Abaikan input apa pun dari client; satker hanya boleh lihat thread-nya sendiri.
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
     * Menyimpan pesan baru ke database
     *
     * - Admin WAJIB mengirim satker_id (menentukan Satker tujuan pesan).
     * - Satker otomatis terkirim ke thread miliknya sendiri.
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

        // Kirim pesan langsung menghentikan status "sedang mengetik" pengirim di thread ini.
        Cache::forget("chat_typing_{$satkerId}_{$user->role}");

        // Event broadcast real-time (channel per-Satker: chat.{satker_id})
        event(new \App\Events\MessageSent($message));

        // Buat notifikasi untuk lawan bicara (admin <-> satker terkait)
        $satkerNama = Satker::find($satkerId)->nama_satker ?? 'Satker';
        \App\Http\Controllers\NotificationController::notifyNewMessage($user, $satkerId, $satkerNama, $request->pesan);

        return response()->json(['status' => 'Pesan terkirim!', 'message' => $message->load('user:id,name,role')]);
    }

    /**
     * "Detak jantung" kehadiran — dipanggil berkala (tiap ~15 detik) dari JS selama halaman
     * chat terbuka. Menandai user ini "online" selama 30 detik ke depan (cache, auto-expired,
     * tidak perlu kolom/migration baru).
     */
    public function heartbeat()
    {
        Cache::put('chat_online_' . Auth::id(), true, now()->addSeconds(30));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Menandai user ini "sedang mengetik" di thread satker tertentu. Sinyal ini otomatis
     * kadaluarsa 4 detik kemudian — JS pemanggil harus mengirim ulang selama user masih
     * mengetik supaya indikatornya tetap muncul di sisi lawan bicara.
     */
    public function typing(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'satker_id' => 'required|exists:satkers,id',
        ]);
        $satkerId = $request->satker_id;

        // Satker cuma boleh menandai "mengetik" untuk thread miliknya sendiri.
        if ($user->role === 'satker' && (int) $user->satker_id !== (int) $satkerId) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        Cache::put("chat_typing_{$satkerId}_{$user->role}", true, now()->addSeconds(4));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Status lawan bicara untuk satu thread: online (heartbeat aktif) dan sedang mengetik
     * (sinyal typing aktif) atau tidak. Dipanggil berkala (tiap ~2 detik) oleh JS saat sebuah
     * thread sedang dibuka.
     *
     * - Kalau yang minta admin: statusnya tentang SATKER yang sedang dibuka.
     * - Kalau yang minta satker: statusnya tentang ADMIN (dianggap online kalau ada admin
     *   manapun yang online, karena semua admin berbagi satu "sisi" percakapan).
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
            $online = $satkerUser ? Cache::has('chat_online_' . $satkerUser->id) : false;
            $typing = Cache::has("chat_typing_{$satkerId}_satker");
        } else {
            $satkerId = $user->satker_id;

            $online = User::where('role', 'admin')->get()
                ->contains(fn ($admin) => Cache::has('chat_online_' . $admin->id));
            $typing = $satkerId ? Cache::has("chat_typing_{$satkerId}_admin") : false;
        }

        return response()->json(['online' => $online, 'typing' => $typing]);
    }

    /**
     * Daftar id satker yang usernya lagi online — dipakai admin untuk nge-refresh titik
     * hijau di SEMUA item daftar satker (bukan cuma thread yang lagi dibuka).
     */
    public function onlineSatkers()
    {
        $online = Satker::with('user:id,satker_id')->get()
            ->filter(fn ($satker) => $satker->user && Cache::has('chat_online_' . $satker->user->id))
            ->pluck('id')
            ->values();

        return response()->json(['online' => $online]);
    }
}