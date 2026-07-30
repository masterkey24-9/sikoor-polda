<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
