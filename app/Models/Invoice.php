<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'appointment_id', 
        'montant', 
        'statut_paiement', 
        'methode_paiement', 
        'transaction_id', 
        'date_emission'
    ];

    /**
     * Une facture appartient à un rendez-vous spécifique.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}