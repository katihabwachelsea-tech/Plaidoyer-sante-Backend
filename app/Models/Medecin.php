<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medecin extends Model
{
    // 1. Configuration de la clé primaire (liée à User)
    protected $primaryKey = 'user_id';
    public $incrementing = false; 
    protected $keyType = 'int';

    // 2. Les champs que le médecin ou l'admin peuvent modifier
    protected $fillable = [
        'user_id',
        'specialite',
        'licence',
        'hopital',
        'biographie',
        'is_validated', // Géré par l'Admin
        'disponibilite' // Géré par le Médecin
    ];
    // 3. Cast pour s'assurer que les booleans sont bien traités comme tels
    protected $casts = [
        'is_validated' => 'boolean',
        'disponibilite' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Lien vers le compte utilisateur (nom, email, tel)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Liste des rendez-vous reçus par ce médecin
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'medecin_id', 'user_id');
    }

    public function services()
{
    return $this->belongsToMany(Service::class, 'medecin_service', 'medecin_id', 'service_id');
}
}
