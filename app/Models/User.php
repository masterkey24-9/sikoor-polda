<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',        
        'satker_id',  
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Relasi: seorang user (role satker) terhubung ke satu Satker.
     */
    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }

    /**
     * Dianggap "online" kalau ada aktivitas dalam N detik terakhir.
     * (di-update oleh middleware UpdateLastSeen tiap request/polling)
     */
    public function isOnline(int $thresholdSeconds = 60): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subSeconds($thresholdSeconds));
    }

    /**
     * Label untuk ditampilkan di UI: "Online" atau "Terakhir online 5 menit lalu".
     */
    public function lastSeenLabel(): string
    {
        if (! $this->last_seen_at) {
            return 'Belum pernah online';
        }

        return $this->isOnline()
            ? 'Online'
            : 'Terakhir online ' . $this->last_seen_at->diffForHumans();
    }
}
