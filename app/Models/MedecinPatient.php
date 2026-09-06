<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Table : medecin_patients
 * Carnet de patients géré par le médecin (indépendant du système de rendez-vous).
 * Correspond aux données créées/synchronisées depuis le module SQLite Flutter.
 */
class MedecinPatient extends Model
{
    use HasFactory;

    protected $table = 'medecin_patients';

    protected $fillable = [
        'medecin_id',
        'nom',
        'prenom',
        'age',
        'pays',
        'maladie',
        'conseils',
        'date_creation',
        'derniere_visite',
    ];

    protected $casts = [
        'date_creation'   => 'date',
        'derniere_visite' => 'date',
        'age'             => 'integer',
    ];

    /**
     * Le médecin qui gère ce patient.
     */
    public function medecin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medecin_id');
    }
}
