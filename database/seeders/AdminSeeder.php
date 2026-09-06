<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // On vérifie d'abord si l'admin n'existe pas déjà pour éviter les doublons
        User::firstOrCreate(
            ['email' => 'admin@plaidoyersante.bi'], // Condition de recherche
            [
                'nom' => 'Admin Plaidoyer',
                'password' => Hash::make('password123'),
                'telephone' => '1234567890',
                'role' => 'admin',
            ]
        );
    }
}