<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['appointment_id', 'sender_id', 'contenu', 'lu_at'];

    protected $casts = [
        'lu_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function format(int $currentUserId): array
    {
        return [
            'id'         => $this->id,
            'contenu'    => $this->contenu,
            'sender_id'  => $this->sender_id,
            'sender_nom' => $this->sender?->nom ?? '',
            'is_mine'    => $this->sender_id === $currentUserId,
            'lu'         => $this->lu_at !== null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
