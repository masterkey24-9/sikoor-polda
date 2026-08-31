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
            // Daftar satker beserta pratinjau pesan terakhir.
            $satkers = Satker::orderBy('nama_satker')->get()->map(function ($satker) {
                $lastMessage = Message::where('satker_id', $satker->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $satker->last_pesan = $lastMessage->pesan ?? 'Belum ada pesan';

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

        // Event broadcast real-time (channel per-Satker: chat.{satker_id})
        event(new \App\Events\MessageSent($message));

        // Buat notifikasi untuk lawan bicara (admin <-> satker terkait)
        $satkerNama = Satker::find($satkerId)->nama_satker ?? 'Satker';
        \App\Http\Controllers\NotificationController::notifyNewMessage($user, $satkerId, $satkerNama, $request->pesan);

        return response()->json(['status' => 'Pesan terkirim!', 'message' => $message->load('user:id,name,role')]);
    }

    /**
     * Status online + "terakhir online" untuk SEMUA satker sekaligus.
     * Dipakai admin untuk menampilkan indikator di daftar satker (sidebar kiri).
     */
    public function onlineStatus()
    {
        $satkers = Satker::with('user')->get()->map(function ($satker) {
            $user = $satker->user;

            return [
                'satker_id' => $satker->id,
                'online' => $user?->isOnline() ?? false,
                'last_seen_label' => $user?->lastSeenLabel() ?? 'Belum pernah online',
            ];
        });

        return response()->json($satkers);
    }

    /**
     * Status "live" untuk thread yang sedang dibuka: online/offline lawan bicara
     * + apakah lawan bicara sedang mengetik. Di-poll berkala (lebih sering
     * daripada pesan) supaya balon "sedang mengetik..." terasa responsif.
     */
    public function liveStatus(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $request->validate([
                'satker_id' => 'required|exists:satkers,id',
            ]);
            $satkerId = $request->query('satker_id');
            $opponent = Satker::find($satkerId)?->user;
            $typingKey = "typing:satker:{$satkerId}";

            return response()->json([
                'online' => $opponent?->isOnline() ?? false,
                'last_seen_label' => $opponent?->lastSeenLabel() ?? 'Belum pernah online',
                'typing' => (bool) Cache::get($typingKey, false),
            ]);
        }

        // Role satker: lawan bicaranya adalah "Admin" (bisa siapa saja dari akun admin).
        $satkerId = $user->satker_id;
        $typingKey = "typing:admin:{$satkerId}";
        $admin = User::where('role', 'admin')->orderByDesc('last_seen_at')->first();

        return response()->json([
            'online' => $admin?->isOnline() ?? false,
            'last_seen_label' => $admin?->lastSeenLabel() ?? 'Belum pernah online',
            'typing' => (bool) Cache::get($typingKey, false),
        ]);
    }

    /**
     * Dipanggil dari JS setiap kali user mengetik di kolom pesan.
     * Menyimpan flag "sedang mengetik" sebentar (3 detik) di cache,
     * lalu otomatis hilang kalau berhenti mengetik.
     */
    public function typing(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $request->validate([
                'satker_id' => 'required|exists:satkers,id',
            ]);
            $key = "typing:admin:{$request->satker_id}";
        } else {
            if (! $user->satker_id) {
                return response()->json(['message' => 'Akun ini belum terhubung ke Satker manapun.'], 422);
            }
            $key = "typing:satker:{$user->satker_id}";
        }

        Cache::put($key, true, now()->addSeconds(3));

        return response()->json(['status' => 'ok']);
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

            // Event broadcast realtime per-thread, sama seperti pengiriman pesan biasa.
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
}
