<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Medecin;
use App\Models\Service;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. D'abord les services (requis avant de créer des RDV)
        $this->call(ServiceSeeder::class);

        // 2. Ensuite les liaisons médecin <-> services (seulement si des médecins existent)
        $medecins = Medecin::all();
        $services = Service::all();

        if ($medecins->isNotEmpty() && $services->isNotEmpty()) {
            foreach ($medecins as $medecin) {
                $count         = min($services->count(), rand(1, 3));
                $randomServices = $services->random($count)->pluck('id');
                $medecin->services()->syncWithoutDetaching($randomServices);
            }
        }
    }
}