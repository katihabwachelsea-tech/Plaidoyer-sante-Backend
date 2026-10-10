<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // Médecine générale
            ['nom_service' => 'Consultation Générale', 'prix' => 15000, 'mode' => 'presentiel', 'specialite' => 'Médecin généraliste'],
            ['nom_service' => 'Téléconsultation Générale', 'prix' => 12000, 'mode' => 'teleconsultation', 'specialite' => 'Médecin généraliste'],
            ['nom_service' => 'Bilan de santé', 'prix' => 20000, 'mode' => 'presentiel', 'specialite' => 'Médecin généraliste'],
            ['nom_service' => 'Certificat médical', 'prix' => 5000, 'mode' => 'presentiel', 'specialite' => 'Médecin généraliste'],

            // Cardiologie
            ['nom_service' => 'Consultation Cardiologie', 'prix' => 28000, 'mode' => 'presentiel', 'specialite' => 'Cardiologue'],
            ['nom_service' => 'Téléconsultation Cardiologie', 'prix' => 22000, 'mode' => 'teleconsultation', 'specialite' => 'Cardiologue'],
            ['nom_service' => 'Électrocardiogramme', 'prix' => 25000, 'mode' => 'presentiel', 'specialite' => 'Cardiologue'],

            // Pédiatrie
            ['nom_service' => 'Consultation Pédiatrique', 'prix' => 15000, 'mode' => 'presentiel', 'specialite' => 'Pédiatre'],
            ['nom_service' => 'Téléconsultation Pédiatrique', 'prix' => 12000, 'mode' => 'teleconsultation', 'specialite' => 'Pédiatre'],
            ['nom_service' => 'Vaccination', 'prix' => 10000, 'mode' => 'presentiel', 'specialite' => 'Pédiatre'],

            // Dermatologie
            ['nom_service' => 'Consultation Dermatologique', 'prix' => 20000, 'mode' => 'presentiel', 'specialite' => 'Dermatologue'],
            ['nom_service' => 'Téléconsultation Dermatologie', 'prix' => 16000, 'mode' => 'teleconsultation', 'specialite' => 'Dermatologue'],
            ['nom_service' => 'Traitement acné', 'prix' => 18000, 'mode' => 'presentiel', 'specialite' => 'Dermatologue'],

            // Oncologie
            ['nom_service' => 'Consultation Oncologie', 'prix' => 30000, 'mode' => 'presentiel', 'specialite' => 'Oncologue'],
            ['nom_service' => 'Téléconsultation Oncologie', 'prix' => 25000, 'mode' => 'teleconsultation', 'specialite' => 'Oncologue'],
            ['nom_service' => 'Suivi oncologique', 'prix' => 22000, 'mode' => 'presentiel', 'specialite' => 'Oncologue'],

            // Gynécologie
            ['nom_service' => 'Consultation Gynécologique', 'prix' => 22000, 'mode' => 'presentiel', 'specialite' => 'Gynécologue'],
            ['nom_service' => 'Téléconsultation Gynécologie', 'prix' => 18000, 'mode' => 'teleconsultation', 'specialite' => 'Gynécologue'],
            ['nom_service' => 'Suivi de grossesse', 'prix' => 25000, 'mode' => 'presentiel', 'specialite' => 'Gynécologue'],

            // Ophtalmologie
            ['nom_service' => 'Consultation Ophtalmique', 'prix' => 20000, 'mode' => 'presentiel', 'specialite' => 'Ophtalmologue'],
            ['nom_service' => 'Téléconsultation Ophtalmologie', 'prix' => 15000, 'mode' => 'teleconsultation', 'specialite' => 'Ophtalmologue'],
            ['nom_service' => 'Examen de la vue', 'prix' => 12000, 'mode' => 'presentiel', 'specialite' => 'Ophtalmologue'],

            // Orthopédie
            ['nom_service' => 'Consultation Orthopédique', 'prix' => 25000, 'mode' => 'presentiel', 'specialite' => 'Orthopédiste'],
            ['nom_service' => 'Téléconsultation Orthopédie', 'prix' => 20000, 'mode' => 'teleconsultation', 'specialite' => 'Orthopédiste'],
            ['nom_service' => 'Kinésithérapie', 'prix' => 18000, 'mode' => 'presentiel', 'specialite' => 'Orthopédiste'],

            // Neurologie
            ['nom_service' => 'Consultation Neurologique', 'prix' => 30000, 'mode' => 'presentiel', 'specialite' => 'Neurologue'],
            ['nom_service' => 'Téléconsultation Neurologie', 'prix' => 24000, 'mode' => 'teleconsultation', 'specialite' => 'Neurologue'],
            ['nom_service' => 'Suivi migraine', 'prix' => 18000, 'mode' => 'presentiel', 'specialite' => 'Neurologue'],

            // Endocrinologie
            ['nom_service' => 'Consultation Endocrinologie', 'prix' => 25000, 'mode' => 'presentiel', 'specialite' => 'Endocrinologue'],
            ['nom_service' => 'Téléconsultation Endocrinologie', 'prix' => 20000, 'mode' => 'teleconsultation', 'specialite' => 'Endocrinologue'],
            ['nom_service' => 'Suivi Diabète', 'prix' => 18000, 'mode' => 'presentiel', 'specialite' => 'Endocrinologue'],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['nom_service' => $service['nom_service']],
                [
                    'prix' => $service['prix'],
                    'mode' => $service['mode'],
                    'specialite' => $service['specialite'],
                ]
            );
        }
    }
}
