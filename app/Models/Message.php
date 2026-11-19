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

    public function format(int $currentUserId, Appointment $appointment): array
    {
        $senderId = (int) $this->sender_id;
        $isPatient = $senderId === (int) $appointment->patient_id;
        $isDoctor = $senderId === (int) $appointment->medecin_id;

        if ($isPatient) {
            $senderName = $appointment->patient?->user?->nom;
            $senderRole = 'patient';
        } elseif ($isDoctor) {
            $senderName = $appointment->medecin?->user?->nom;
            $senderRole = 'medecin';
        } else {
            $senderName = $this->sender?->nom;
            $senderRole = $this->sender?->role;
        }

        return [
            'id'         => $this->id,
            'contenu'    => $this->contenu,
            'sender_id'  => $this->sender_id,
            'sender_nom'  => $senderName ?? '',
            'sender_role' => $senderRole ?? '',
            'is_mine'     => $senderId === $currentUserId,
            'lu'         => $this->lu_at !== null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
