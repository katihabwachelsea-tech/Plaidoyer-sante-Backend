<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Creneau;
use App\Models\Invoice;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        // Photos iStock selon le genre — noms des médecins inchangés
        $photoHomme1 = 'https://media.istockphoto.com/id/1203995945/photo/portrait-of-mature-male-doctor-wearing-white-coat-standing-in-hospital-corridor.jpg?s=170667a&w=0&k=20&c=p-cciIWNGLWHc6N7T85rMmn2ynEIg0pD5NEnEO9qBu8=';
        $photoHomme2 = 'https://media.istockphoto.com/id/185288528/photo/smiling-african-american-male-doctor-isolated.jpg?s=612x612&w=0&k=20&c=JCCdlizGVIVlbv0yOA99T_lc9hg3Ms9LXC2WIjOiZzw=';
        $photoFemme1 = 'https://media.istockphoto.com/id/2185361097/photo/black-woman-doctor-and-smile-with-arms-crossed-at-hospital-for-medical-service-female-person.jpg?s=2048x2048&w=is&k=20&c=hLW5fqHCj4JgjJAf_0aafN6nFb9ZAQIrygmQknqgDMg=';
        $photoFemme2 = 'https://media.istockphoto.com/id/2050883733/photo/happy-successful-doctor-woman-medical-worker-in-white-lab-coat-standing-with-crossed-arms-on.jpg?s=170667a&w=0&k=20&c=xdOAJYw5TsS-khijd6Vri2aXStJG__6kcnaLtwzVnAA=';
        $photoFemme3 = 'https://media.istockphoto.com/id/171296819/photo/african-american-female-doctor-holding-a-clipboard-isolated.jpg?s=612x612&w=0&k=20&c=hCJk-9gsOff8Fac04a11VMOwflMYiRXUVfAj3UTn67U=';

        $doctors = [
            [
                'email' => 'medecin@plaidoyersante.bi',
                'nom' => 'Dr. Jean Ndayishimiye',
                'telephone' => '79000001',
                'photo_url' => $photoHomme1,
                'specialite' => 'Oncologue',
                'licence' => 'MED-2024001',
                'hopital' => 'Hôpital Prince Régent Charles',
                'biographie' => 'Oncologue clinicien à Bujumbura, suivi des cancers solides.',
                'disponibilite' => 'Lun–Ven, 8h–17h',
                'validated' => true,
            ],
            [
                'email' => 'chelsea@plaidoyersante.bi',
                'nom' => 'Dr. Chelsea Niyonkuru',
                'telephone' => '79000004',
                'photo_url' => $photoFemme1,
                'specialite' => 'Cardiologue',
                'licence' => 'MED-2024004',
                'hopital' => 'CHU de Kamenge',
                'biographie' => 'Cardiologue interventionnelle, hypertension et insuffisance cardiaque.',
                'disponibilite' => 'Lun–Sam, 9h–16h',
                'validated' => true,
            ],
            [
                'email' => 'patrick@plaidoyersante.bi',
                'nom' => 'Dr. Patrick Baranshamaje',
                'telephone' => '79000005',
                'photo_url' => $photoHomme2,
                'specialite' => 'Pédiatre',
                'licence' => 'MED-2024005',
                'hopital' => 'Clinique Prince Louis Rwagasore',
                'biographie' => 'Pédiatre, vaccination et suivi de croissance.',
                'disponibilite' => 'Lun–Ven, 8h–15h',
                'validated' => true,
            ],
            [
                'email' => 'sarah@plaidoyersante.bi',
                'nom' => 'Dr. Sarah Munezero',
                'telephone' => '79000006',
                'photo_url' => $photoFemme2,
                'specialite' => 'Dermatologue',
                'licence' => 'MED-2024006',
                'hopital' => 'Clinique de l’Espoir',
                'biographie' => 'Dermatologie médicale et esthétique.',
                'disponibilite' => 'Mar–Sam, 10h–17h',
                'validated' => true,
            ],
            [
                'email' => 'medecin.attente@plaidoyersante.bi',
                'nom' => 'Dr. Aline Hakizimana',
                'telephone' => '79000002',
                'photo_url' => $photoFemme3,
                'specialite' => 'Endocrinologue',
                'licence' => 'MED-2024999',
                'hopital' => 'Clinique de l’Espoir',
                'biographie' => 'Endocrinologie et suivi diabète. Profil en attente de validation admin.',
                'disponibilite' => 'Sur rendez-vous',
                'validated' => false,
            ],
        ];

        foreach ($doctors as $doctor) {
            $user = User::firstOrCreate(
                ['email' => $doctor['email']],
                [
                    'nom' => $doctor['nom'],
                    'password' => $password,
                    'telephone' => $doctor['telephone'],
                    'photo_url' => $doctor['photo_url'],
                    'role' => 'medecin',
                    'is_profile_completed' => true,
                ]
            );
            $user->update(['photo_url' => $doctor['photo_url']]);

            Medecin::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'specialite' => $doctor['specialite'],
                    'licence' => $doctor['licence'],
                    'hopital' => $doctor['hopital'],
                    'biographie' => $doctor['biographie'],
                    'disponibilite' => $doctor['disponibilite'],
                    'is_validated' => $doctor['validated'],
                ]
            );
        }

        $patients = [
            ['email' => 'patient@plaidoyersante.bi', 'nom' => 'Jean Kabila', 'tel' => '79000003', 'naissance' => '1991-03-12', 'groupe' => 'O+', 'maladie' => 'Suivi oncologique', 'antecedents' => 'Toux persistante'],
            ['email' => 'marie@plaidoyersante.bi', 'nom' => 'Marie Ndikumana', 'tel' => '79000011', 'naissance' => '1990-05-15', 'groupe' => 'A+', 'maladie' => 'Hypertension', 'antecedents' => 'Diabète familial'],
            ['email' => 'eric@plaidoyersante.bi', 'nom' => 'Eric Nduwimana', 'tel' => '79000012', 'naissance' => '1986-11-02', 'groupe' => 'B+', 'maladie' => 'Diabète type 2', 'antecedents' => 'Obésité'],
            ['email' => 'claudine@plaidoyersante.bi', 'nom' => 'Claudine Hakizimana', 'tel' => '79000013', 'naissance' => '1995-07-21', 'groupe' => 'O-', 'maladie' => 'Asthme', 'antecedents' => 'Allergie aux acariens'],
            ['email' => 'pascal@plaidoyersante.bi', 'nom' => 'Pascal Bizimana', 'tel' => '79000014', 'naissance' => '1978-01-09', 'groupe' => 'AB+', 'maladie' => 'Douleurs thoraciques', 'antecedents' => 'Tabagisme sevré'],
            ['email' => 'diane@plaidoyersante.bi', 'nom' => 'Diane Irakoze', 'tel' => '79000015', 'naissance' => '2001-09-30', 'groupe' => 'A-', 'maladie' => 'Suivi dermatologique', 'antecedents' => 'Eczéma'],
        ];

        foreach ($patients as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'nom' => $row['nom'],
                    'password' => $password,
                    'telephone' => $row['tel'],
                    'role' => 'patient',
                    'is_profile_completed' => true,
                ]
            );

            Patient::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'date_naissance' => $row['naissance'],
                    'groupe_sanguin' => $row['groupe'],
                    'maladie' => $row['maladie'],
                    'antecedents' => $row['antecedents'],
                ]
            );
        }

        Medecin::whereHas('user', function ($q) {
            $q->where('email', '!=', 'medecin.attente@plaidoyersante.bi');
        })->update(['is_validated' => true]);

        $serviceIds = Service::pluck('id');
        $allDoctors = Medecin::all();

        foreach ($allDoctors as $medecin) {
            if ($serviceIds->isNotEmpty()) {
                $medecin->services()->syncWithoutDetaching(
                    $serviceIds->random(min(4, $serviceIds->count()))->all()
                );
            }

            $this->seedSlots($medecin->user_id);
            $this->seedAgenda($medecin->user_id);
        }
    }

    private function seedSlots(int $medecinUserId): void
    {
        $hours = ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];

        for ($day = 0; $day < 14; $day++) {
            $date = Carbon::today()->addDays($day)->toDateString();
            foreach ($hours as $start) {
                $endHour = ((int) substr($start, 0, 2)) + 1;
                Creneau::firstOrCreate(
                    [
                        'medecin_id' => $medecinUserId,
                        'date' => $date,
                        'heure_debut' => $start,
                    ],
                    [
                        'heure_fin' => sprintf('%02d:00', $endHour),
                        'disponible' => true,
                    ]
                );
            }
        }
    }

    private function seedAgenda(int $medecinUserId): void
    {
        $patients = Patient::query()->get();
        $services = Service::query()->get();
        if ($patients->isEmpty() || $services->isEmpty()) {
            return;
        }

        $motifs = [
            'Douleurs thoraciques depuis 3 jours',
            'Contrôle tension artérielle',
            'Suivi diabète et bilan',
            'Toux persistante et fatigue',
            'Consultation de suivi post-traitement',
            'Éruption cutanée et prurit',
            'Fièvre et douleurs articulaires',
            'Bilan annuel de prévention',
        ];

        $plan = [
            ['offset' => -2, 'hour' => 10, 'statut' => 'Termine'],
            ['offset' => -1, 'hour' => 15, 'statut' => 'Termine'],
            ['offset' => 0, 'hour' => 9, 'statut' => 'Confirme'],
            ['offset' => 0, 'hour' => 11, 'statut' => 'Confirme'],
            ['offset' => 0, 'hour' => 14, 'statut' => 'Confirme'],
            ['offset' => 1, 'hour' => 9, 'statut' => 'Confirme'],
            ['offset' => 2, 'hour' => 16, 'statut' => 'Confirme'],
            ['offset' => 3, 'hour' => 10, 'statut' => 'En_attente'],
        ];

        foreach ($plan as $index => $item) {
            $patient = $patients[$index % $patients->count()];
            $service = $services[$index % $services->count()];
            $dateRdv = Carbon::today()->addDays($item['offset'])->setTime($item['hour'], 0);

            $appointment = Appointment::firstOrCreate(
                [
                    'medecin_id' => $medecinUserId,
                    'patient_id' => $patient->user_id,
                    'date_rdv' => $dateRdv,
                ],
                [
                    'service_id' => $service->id,
                    'motif' => $motifs[$index % count($motifs)],
                    'statut' => $item['statut'],
                ]
            );

            $paid = in_array($item['statut'], ['Confirme', 'Termine'], true);
            Invoice::firstOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'montant' => $service->prix,
                    'statut_paiement' => $paid ? 'Paye' : 'Non_paye',
                    'methode_paiement' => $paid ? 'Ecocash' : null,
                    'transaction_id' => $paid ? 'ECO-'.strtoupper(Str::random(8)) : null,
                    'date_emission' => $dateRdv->copy()->subHours(2),
                ]
            );

            if ($item['statut'] === 'Termine') {
                Consultation::firstOrCreate(
                    ['appointment_id' => $appointment->id],
                    [
                        'diagnostic' => 'Examen clinique stable. Surveillance et hygiène de vie renforcées.',
                        'ordonnance' => 'Paracétamol 500 mg si douleur. Contrôle dans 7 jours.',
                        'conseils_ia' => 'Hydratation, activité physique adaptée, revenir si aggravation.',
                    ]
                );
            }

            if (in_array($item['statut'], ['Confirme', 'En_attente'], true)) {
                Creneau::where('medecin_id', $medecinUserId)
                    ->whereDate('date', $dateRdv->toDateString())
                    ->where('heure_debut', $dateRdv->format('H:i'))
                    ->update(['disponible' => false]);
            }
        }
    }
}
