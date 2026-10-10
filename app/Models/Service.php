<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = ['nom_service', 'prix', 'mode', 'specialite'];

    /**
     * Un service peut être lié à plusieurs rendez-vous.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medecins()
{
    return $this->belongsToMany(Medecin::class, 'medecin_service', 'service_id', 'medecin_id');
}
}