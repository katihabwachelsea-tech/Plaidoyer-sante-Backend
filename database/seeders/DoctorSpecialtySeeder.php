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
            3  => ['specialite' => 'Gynécologue', 'hopital' => 'Maternité de Bujumbura', 'biographie' => 'Gynécologue attentive au suivi de la santé féminine, de la grossesse et du bien-être de ses patientes.'],
            4  => ['specialite' => 'Ophtalmologue', 'hopital' => 'Clinique de la Vision', 'biographie' => 'Ophtalmologue spécialisé dans le diagnostic et le traitement des troubles de la vue et des affections oculaires.'],
            5  => ['specialite' => 'Orthopédiste', 'hopital' => 'Centre Orthopédique de Kamenge', 'biographie' => 'Orthopédiste spécialisé dans le diagnostic et le traitement des douleurs articulaires et des traumatismes.'],
            6  => ['specialite' => 'Neurologue', 'hopital' => 'CHU de Kamenge', 'biographie' => 'Neurologue dédié au suivi des troubles neurologiques, des céphalées et des maladies du système nerveux.'],
            9  => ['specialite' => 'Médecin généraliste', 'hopital' => 'Hôpital Militaire de Bujumbura', 'biographie' => 'Médecin généraliste offrant un suivi médical global, la prévention et le traitement des maladies courantes.'],
            10 => ['specialite' => 'Oncologue', 'hopital' => 'Hôpital Prince Régent Charles', 'biographie' => 'Oncologue spécialisé dans le diagnostic, le suivi et la prise en charge des maladies cancéreuses.'],
            11 => ['specialite' => 'Endocrinologue', 'hopital' => 'Clinique de l’Espoir', 'biographie' => 'Endocrinologue spécialisé dans le suivi du diabète, des hormones et des troubles métaboliques.'],
            13 => ['specialite' => 'Cardiologue', 'hopital' => 'CHU de Kamenge', 'biographie' => 'Cardiologue spécialisé dans le suivi cardiovasculaire, le dépistage et la prévention des maladies du cœur.'],
            14 => ['specialite' => 'Pédiatre', 'hopital' => 'Clinique Prince Louis Rwagasore', 'biographie' => 'Pédiatre attentive au suivi médical des enfants et à la prévention des maladies courantes.'],
            15 => ['specialite' => 'Dermatologue', 'hopital' => 'Clinique de l’Espoir', 'biographie' => 'Dermatologue qui accompagne ses patients dans le traitement des affections de la peau et des soins dermatologiques.'],
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
