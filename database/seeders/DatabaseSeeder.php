<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ServiceSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(DemoSeeder::class);
        $this->call(DoctorPhotoSeeder::class);
        $this->call(DoctorSpecialtySeeder::class);
    }
}
