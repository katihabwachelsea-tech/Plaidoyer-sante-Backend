<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Creneau extends Model
{
    use HasFactory;

    protected $table = 'creneaux';

    protected $fillable = [
        'medecin_id',
        'date',
        'heure_debut',
        'heure_fin',
        'disponible',
    ];

    protected $casts = [
        'date'       => 'date',
        'disponible' => 'boolean',
    ];

    /**
     * Le médecin propriétaire du créneau.
     * medecin_id référence users.id (le compte User du médecin).
     */
    public function medecin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medecin_id');
    }
}
