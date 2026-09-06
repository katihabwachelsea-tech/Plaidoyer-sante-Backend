<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    protected $fillable = [
        'appointment_id', 
        'diagnostic', 
        'ordonnance', 
        'conseils_ia'
    ];

    /**
    * La consultation appartient à un seul RDV.
     */
    public function appointment(): BelongsTo
    {
        // La consultation "appartient à" au RDV (elle porte la FK)
       
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}