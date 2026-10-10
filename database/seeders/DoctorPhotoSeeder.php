<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Attribue des photos de profil aux médecins.
 * Photos iStock : femmes pour les prénoms féminins, hommes pour les prénoms masculins.
 *
 * Usage : php artisan db:seed --class=DoctorPhotoSeeder
 */
class DoctorPhotoSeeder extends Seeder
{
    // Photos iStock (liste de référence) — femmes / hommes, noms inchangés
    private const PHOTOS_HOMME = [
        // Jean-Paul, Emmanuel, Patrick, Hassan
        'https://media.istockphoto.com/id/1203995945/photo/portrait-of-mature-male-doctor-wearing-white-coat-standing-in-hospital-corridor.jpg?s=170667a&w=0&k=20&c=p-cciIWNGLWHc6N7T85rMmn2ynEIg0pD5NEnEO9qBu8=',
        'https://media.istockphoto.com/id/1390000431/photo/shot-of-a-mature-doctor-using-a-digital-tablet-in-a-modern-hospital.jpg?s=170667a&w=0&k=20&c=KLScLZH6NJw3m5GaFWngSzSTEmn0rE_0D-MSl772vQU=',
        'https://media.istockphoto.com/id/185288528/photo/smiling-african-american-male-doctor-isolated.jpg?s=612x612&w=0&k=20&c=JCCdlizGVIVlbv0yOA99T_lc9hg3Ms9LXC2WIjOiZzw=',
        'https://media.istockphoto.com/id/1372701329/photo/shot-of-a-young-male-doctor-standing-with-his-arms-crossed-in-an-office-at-a-hospital.jpg?s=612x612&w=0&k=20&c=6NEKvLXkwWsaCcWrI9CqhOBLxWVkDhH8JfVuy2WBaFk=',
    ];

    private const PHOTOS_FEMME = [
        // Christa, Alice, Laure, Mireille
        'https://media.istockphoto.com/id/2185361097/photo/black-woman-doctor-and-smile-with-arms-crossed-at-hospital-for-medical-service-female-person.jpg?s=2048x2048&w=is&k=20&c=hLW5fqHCj4JgjJAf_0aafN6nFb9ZAQIrygmQknqgDMg=',
        'https://media.istockphoto.com/id/2050883733/photo/happy-successful-doctor-woman-medical-worker-in-white-lab-coat-standing-with-crossed-arms-on.jpg?s=170667a&w=0&k=20&c=xdOAJYw5TsS-khijd6Vri2aXStJG__6kcnaLtwzVnAA=',
        'https://media.istockphoto.com/id/171296819/photo/african-american-female-doctor-holding-a-clipboard-isolated.jpg?s=612x612&w=0&k=20&c=hCJk-9gsOff8Fac04a11VMOwflMYiRXUVfAj3UTn67U=',
        'https://media.istockphoto.com/id/171313563/photo/confident-african-american-female-doctor-isolated.jpg?s=612x612&w=0&k=20&c=2N6561X3mIMGbZAs32X97kwhm_4v2MZXwq_cBO2X_UI=',
    ];

    public function run(): void
    {
        $medecins = DB::table('users')
            ->where('role', 'medecin')
            ->get(['id', 'nom', 'photo_url']);

        $hommeIndex = 0;
        $femmeIndex = 0;
        $updated    = 0;

        foreach ($medecins as $user) {
            // Nettoyer le nom : enlever "Dr.", "Docteur", etc.
            $nomNettoye = preg_replace('/^(dr\.?|docteur)\s+/i', '', trim($user->nom));
            // Premier prénom (Jean-Paul → jean)
            $prenom = strtolower(preg_split('/[\s\-]+/', $nomNettoye)[0] ?? '');
            $estFemme = $this->estPrenomFeminin($prenom);

            if ($estFemme) {
                $photo = self::PHOTOS_FEMME[$femmeIndex % count(self::PHOTOS_FEMME)];
                $femmeIndex++;
            } else {
                $photo = self::PHOTOS_HOMME[$hommeIndex % count(self::PHOTOS_HOMME)];
                $hommeIndex++;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['photo_url' => $photo]);

            $genre = $estFemme ? 'F' : 'H';
            $this->command->line("  → [{$genre}] {$user->nom} (id={$user->id})");
            $updated++;
        }

        $this->command->info("\n✅ $updated médecin(s) mis à jour.");
    }

    /**
     * Heuristique simple pour deviner si un prénom est féminin.
     * À enrichir selon les prénoms réels de ta BDD.
     */
    private function estPrenomFeminin(string $prenom): bool
    {
        $prenomsFeminins = [
            // Prénoms africains / français féminins courants
            'amina', 'fatima', 'marie', 'grace', 'alice', 'claire',
            'claudine', 'diane', 'emma', 'eve', 'flora', 'gabrielle',
            'hawa', 'helene', 'hope', 'isabelle', 'jane', 'josephine',
            'julie', 'laure', 'lea', 'linda', 'lisa', 'lucie',
            'mariette', 'martine', 'nadia', 'nadine', 'nadege',
            'nathalie', 'nicole', 'patience', 'pauline', 'rachel',
            'reine', 'rose', 'sandrine', 'sarah', 'sophia', 'sophie',
            'stella', 'sylvie', 'therese', 'veronique', 'victoire',
            'viviane', 'yvonne', 'zoe', 'aisha', 'blessing', 'chantal',
            'chloe', 'divine', 'esperance', 'eunice', 'fatoumata',
            'felicite', 'flore', 'francoise', 'gloire', 'honorine',
            'immaculee', 'inesse', 'jeannette', 'joelle', 'joice',
            'judith', 'justine', 'kevine', 'larissa', 'latifa',
            'laurence', 'leontine', 'lydie', 'madeleine', 'marcelline',
            'marguerite', 'marinette', 'merveille', 'michelle', 'miriam',
            'monique', 'murielle', 'ornella', 'pascaline', 'prisca',
            'prudence', 'regine', 'rita', 'rolande', 'rosalie',
            'rosette', 'ruth', 'sabine', 'salome', 'simone',
            'solange', 'sonia', 'suzanne', 'tatiana', 'valentina',
            'valerie', 'vanessa', 'virginie', 'wivine', 'yolande',
            // Ajouts spécifiques
            'chelsea', 'christa', 'aline', 'angele', 'angie', 'ange',
            'beatrice', 'bernadette', 'brigitte', 'carine', 'carole',
            'cecile', 'celeste', 'christelle', 'christine', 'clara',
            'corinne', 'dorcas', 'edith', 'elise', 'eloise',
            'esther', 'eudoxie', 'fanny', 'floriane', 'fortune',
            'francine', 'frederique', 'gisele', 'gladys', 'heloise',
            'ingrid', 'irene', 'jacqueline', 'jessica', 'jocelyne',
            'joy', 'karen', 'katia', 'laetitia', 'laura',
            'leila', 'liliane', 'line', 'lise', 'lola',
            'louisa', 'luce', 'luise', 'madelaine', 'magdalene',
            'manon', 'mariam', 'marielle', 'marina', 'marlene',
            'maryse', 'mathilde', 'melissa', 'micheline', 'mireille',
            'nadya', 'noella', 'noeline', 'odette', 'odile',
            'olive', 'olivia', 'paule', 'penelope', 'petronille',
            'philomene', 'pierrette', 'priscille', 'rebecca', 'regine',
            'renee', 'romaine', 'rosine', 'rosy', 'salima',
            'samira', 'sephora', 'seraphine', 'sibyl', 'sibylle',
            'stephanie', 'tanya', 'tina', 'ulrike', 'ursula',
            'vicky', 'victoria', 'violette', 'wendy', 'wivina',
        ];

        return in_array($prenom, $prenomsFeminins, true);
    }
}
