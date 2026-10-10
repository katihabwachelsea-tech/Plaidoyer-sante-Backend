<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    // 1. Configuration de la clé primaire personnalisée
    protected $primaryKey = 'user_id';
    public $incrementing = false; // Car user_id vient de la table users
    protected $keyType = 'int';


    // 2. Les champs remplissables
    protected $fillable = [
        'user_id',
        'date_naissance',
        'groupe_sanguin',
        'maladie',
        'antecedents',
        'allergies',
    ];
    
public function user()
{
    // Un patient appartient à un utilisateur (User)
    // On précise 'user_id' car c'est ta clé étrangère
    return $this->belongsTo(User::class, 'user_id');
}

}
