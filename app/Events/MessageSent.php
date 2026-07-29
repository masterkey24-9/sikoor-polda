<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Variabel ini akan otomatis dikirim ke Frontend (berisi data pesan dan pengirim)
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        // Karena di controller kita pakai with('user'), kita pastikan relasi user ikut terbawa
        $this->message = $message->load('user:id,name,role');
    }

    /**
     * Tentukan di "Channel" mana pesan ini disiarkan
     */
    public function broadcastOn(): array
    {
        // Kita gunakan public channel bernama 'chat' agar lebih mudah ditangkap Frontend
        return [
            new Channel('chat'),
        ];
    }

    /**
     * Nama event yang akan di-listen oleh Frontend (JavaScript)
     */
    public function broadcastAs()
    {
        return 'message.sent';
    }
}