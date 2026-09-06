<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'nom',
        'email',
        'password',
        'telephone',
        'role',
        'is_profile_completed', // Crucial pour ton onboarding !
    ];

    /**
     * Les attributs qui doivent être cachés pour la protection des données (API).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Le cast des attributs (formatage automatique).
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_profile_completed' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS (L'architecture de ton projet)
    |--------------------------------------------------------------------------
    */

    /**
     * Un utilisateur peut avoir un profil Patient.
     */
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Un utilisateur peut avoir un profil Medecin.
     */
    public function medecin()
    {
        return $this->hasOne(Medecin::class);
    }
}