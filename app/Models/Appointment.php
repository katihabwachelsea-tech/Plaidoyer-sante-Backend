<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

  // Les champs que l'on peut remplir lors de la création d'un RDV
    protected $fillable = [
        'patient_id',
        'medecin_id',
        'service_id',
        'date_rdv',
        'motif',
        'statut', // en_attente, confirme, termine, annule
    ];
// Pour que Laravel traite 'date_rdv' comme un objet Carbon (pratique pour le formatage)
    protected $casts = [
        'date_rdv' => 'datetime',
    ];

  /*
    |--------------------------------------------------------------------------
    | Relations : Le lien avec les autres tables
    |--------------------------------------------------------------------------
    */

    /**
     * Le patient qui a pris le rendez-vous.
     * Note : On précise 'user_id' car c'est la PK de la table patients.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'user_id');
    }

    /**
     * Le médecin qui reçoit le patient.
     */
    public function medecin(): BelongsTo
    {
        return $this->belongsTo(Medecin::class, 'medecin_id', 'user_id');
    }

    /**
     * Le type de service/soin choisi (ex: Consultation Générale).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * La facture associée à ce rendez-vous (Paiement).
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
    * Un RDV possède une seule consultation.
     */
    public function consultation(): HasOne
    {
        // Le RDV "possède" la consultation
        return $this->hasOne(Consultation::class, 'appointment_id');
    }

    
}