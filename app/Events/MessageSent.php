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
        // Setiap Satker punya channel sendiri: 'chat.{satker_id}',
        // supaya pesan hanya disiarkan ke thread yang relevan.
        return [
            new Channel('chat.' . $this->message->satker_id),
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