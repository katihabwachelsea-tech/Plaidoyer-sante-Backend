<?php

namespace Database\Seeders;

use App\Models\Medecin;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Attache les services selon la spécialité du médecin (présentiel + télé).
 * Usage : php artisan db:seed --class=DoctorServiceSeeder
 */
class DoctorServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Medecin::all() as $medecin) {
            $specialite = trim((string) ($medecin->specialite ?? ''));

            $matched = Service::query()
                ->when($specialite !== '', function ($q) use ($specialite) {
                    $q->where('specialite', $specialite)
                        ->orWhere('specialite', 'like', '%' . $specialite . '%');
                }, function ($q) {
                    $q->where('specialite', 'Générale');
                })
                ->orderBy('nom_service')
                ->get();

            if ($matched->isEmpty()) {
                $matched = Service::where('specialite', 'Générale')->get();
            }
            if ($matched->isEmpty()) {
                $matched = Service::orderBy('nom_service')->limit(3)->get();
            }

            $presentiel = $matched->where('mode', 'presentiel')->take(2);
            $tele = $matched->where('mode', 'teleconsultation')->take(1);
            $ids = $presentiel->merge($tele)->pluck('id')->unique()->values();
            if ($ids->isEmpty()) {
                $ids = $matched->take(3)->pluck('id');
            }

            $medecin->services()->sync($ids->all());
            $this->command?->line("  → {$medecin->user?->nom} ({$specialite}) : {$ids->count()} service(s)");
        }
    }
}
