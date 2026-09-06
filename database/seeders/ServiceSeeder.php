<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    /**
     * Alimente la table services avec les types de consultations
     * utilisés dans le flux de réservation Flutter (AppointmentController@store
     * requiert un service_id valide).
     */
    public function run(): void
    {
        $services = [
            ['nom_service' => 'Consultation Générale',        'prix' => 15000],
            ['nom_service' => 'Consultation Spécialiste',     'prix' => 25000],
            ['nom_service' => 'Suivi Chronique',              'prix' => 20000],
            ['nom_service' => 'Urgence Médicale',             'prix' => 35000],
            ['nom_service' => 'Contrôle Tension Artérielle',  'prix' => 12000],
            ['nom_service' => 'Suivi Diabète',                'prix' => 18000],
            ['nom_service' => 'Consultation Oncologie',       'prix' => 30000],
            ['nom_service' => 'Consultation Pédiatrique',     'prix' => 15000],
            ['nom_service' => 'Consultation Gynécologique',   'prix' => 22000],
            ['nom_service' => 'Consultation Dermatologique',  'prix' => 20000],
        ];

        foreach ($services as $service) {
            DB::table('services')->insertOrIgnore([
                'nom_service' => $service['nom_service'],
                'prix'        => $service['prix'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
