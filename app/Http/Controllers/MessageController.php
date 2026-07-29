<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Mengambil riwayat pesan (beserta nama pengirimnya)
     */
    public function index()
    {
        // Mengambil semua pesan urut dari yang terlama ke terbaru
        $messages = Message::with('user:id,name,role')->orderBy('created_at', 'asc')->get();
        
        return response()->json($messages);
    }

    /**
     * Menyimpan pesan baru ke database
     */
    public function store(Request $request)
    {
        $request->validate([
            'pesan' => 'required|string|max:1000'
        ]);

        $message = Message::create([
            'user_id' => Auth::id(),
            'pesan' => $request->pesan
        ]);

        // Nanti di sini kita akan memanggil Event Pusher agar pesan muncul real-time
        event(new \App\Events\MessageSent($message));

        return response()->json(['status' => 'Pesan terkirim!', 'message' => $message]);
    }
}