<?php

namespace Database\Seeders;

use App\Models\Medecin;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Assigne une spécialité UNIQUE à chaque médecin + ses services.
 * Usage : php artisan db:seed --class=DoctorSpecialtySeeder
 */
class DoctorSpecialtySeeder extends Seeder
{
    public function run(): void
    {
        // Une spécialité distincte par médecin (user_id)
        $assignments = [
            3  => ['specialite' => 'Gynécologue', 'hopital' => 'Maternité de Bujumbura', 'biographie' => 'Gynécologie et suivi de grossesse.'],
            4  => ['specialite' => 'Ophtalmologue', 'hopital' => 'Clinique de la Vision', 'biographie' => 'Ophtalmologie et examens de la vue.'],
            5  => ['specialite' => 'Orthopédiste', 'hopital' => 'Centre Orthopédique de Kamenge', 'biographie' => 'Traumatologie et articulations.'],
            6  => ['specialite' => 'Neurologue', 'hopital' => 'CHU de Kamenge', 'biographie' => 'Neurologie et céphalées.'],
            9  => ['specialite' => 'Médecin généraliste', 'hopital' => 'Hôpital Militaire de Bujumbura', 'biographie' => 'Médecine générale et bilans.'],
            10 => ['specialite' => 'Oncologue', 'hopital' => 'Hôpital Prince Régent Charles', 'biographie' => 'Oncologie clinique.'],
            11 => ['specialite' => 'Endocrinologue', 'hopital' => 'Clinique de l’Espoir', 'biographie' => 'Diabète et endocrinologie.'],
            13 => ['specialite' => 'Cardiologue', 'hopital' => 'CHU de Kamenge', 'biographie' => 'Cardiologie interventionnelle.'],
            14 => ['specialite' => 'Pédiatre', 'hopital' => 'Clinique Prince Louis Rwagasore', 'biographie' => 'Pédiatrie et vaccination.'],
            15 => ['specialite' => 'Dermatologue', 'hopital' => 'Clinique de l’Espoir', 'biographie' => 'Dermatologie médicale.'],
        ];

        foreach ($assignments as $userId => $data) {
            $medecin = Medecin::where('user_id', $userId)->first();
            if (!$medecin) {
                continue;
            }

            $medecin->update([
                'specialite' => $data['specialite'],
                'hopital' => $data['hopital'],
                'biographie' => $data['biographie'],
                'disponibilite' => $medecin->disponibilite ?: 'Lun–Ven, 8h–17h',
                'is_validated' => $userId === 11 ? $medecin->is_validated : true,
            ]);

            $matched = Service::where('specialite', $data['specialite'])
                ->orderBy('nom_service')
                ->get();

            if ($matched->isEmpty()) {
                $matched = Service::where('specialite', 'Médecin généraliste')->limit(3)->get();
            }

            $presentiel = $matched->where('mode', 'presentiel')->take(2);
            $tele = $matched->where('mode', 'teleconsultation')->take(1);
            $ids = $presentiel->merge($tele)->pluck('id')->unique()->values();
            if ($ids->isEmpty()) {
                $ids = $matched->take(3)->pluck('id');
            }

            $medecin->services()->sync($ids->all());

            $nom = User::find($userId)?->nom ?? "id=$userId";
            $this->command?->line("  → {$nom} : {$data['specialite']} ({$ids->count()} services)");
        }
    }
}
