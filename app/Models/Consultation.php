<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    protected $fillable = [
        'appointment_id',
        'anamnese',
        'examen',
        'diagnostic',
        'ordonnance',
        'conseils_ia',
        'signature_path',
        'date_consultation',
        // SOAP
        'soap_s',
        'soap_o',
        'soap_a',
        'soap_p',
        // Données structurées
        'constantes_vitales',
        'medicaments',
        'examens_complementaires',
    ];

    protected $casts = [
        'constantes_vitales'       => 'array',
        'medicaments'              => 'array',
        'examens_complementaires'  => 'array',
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