<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Filiere;
use App\Models\Module;
use App\Models\Niveau;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Exécute les graines de la base de données (Seeders).
     * Parfaitement synchronisé avec l'interface Angular de votre binôme.
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // 1. NIVEAUX D'ÉTUDE
        // ---------------------------------------------------------------------
        $l1 = Niveau::firstOrCreate(['code' => 'L1'], ['intitule' => 'Licence 1']);
        $l2 = Niveau::firstOrCreate(['code' => 'L2'], ['intitule' => 'Licence 2']);
        $l3 = Niveau::firstOrCreate(['code' => 'L3'], ['intitule' => 'Licence 3']);
        $m1 = Niveau::firstOrCreate(['code' => 'M1'], ['intitule' => 'Master 1']);
        $m2 = Niveau::firstOrCreate(['code' => 'M2'], ['intitule' => 'Master 2']);

        // ---------------------------------------------------------------------
        // 2. ANNÉES ACADÉMIQUES (Format attendu par Angular)
        // ---------------------------------------------------------------------
        AnneeAcademique::firstOrCreate(['annee' => '2023 - 2024'], ['active' => false]);
        $anneeActive = AnneeAcademique::firstOrCreate(['annee' => '2024 - 2025'], ['active' => true]);
        AnneeAcademique::firstOrCreate(['annee' => '2025 - 2026'], ['active' => false]);

        // ---------------------------------------------------------------------
        // 3. FILIÈRES EXACTES DU FRONTEND ANGULAR
        // ---------------------------------------------------------------------
        $filiereGI = Filiere::firstOrCreate(
            ['code' => 'GI'],
            [
                'nom' => 'Génie Informatique',
                'description' => 'Développement logiciel, algorithmique et bases de données.',
            ]
        );

        $filiereRS = Filiere::firstOrCreate(
            ['code' => 'RS'],
            [
                'nom' => 'Réseaux et systèmes',
                'description' => 'Administration des réseaux informatiques et systèmes d’exploitation.',
            ]
        );

        $filiereRT = Filiere::firstOrCreate(
            ['code' => 'RT'],
            [
                'nom' => 'Réseaux et télécommunications',
                'description' => 'Infrastructures télécoms et réseaux sans fil.',
            ]
        );

        $filiereEI = Filiere::firstOrCreate(
            ['code' => 'EI'],
            [
                'nom' => 'Électricité industrielle',
                'description' => 'Génie électrique et énergie industrielle.',
            ]
        );

        $filiereEEA = Filiere::firstOrCreate(
            ['code' => 'EEA'],
            [
                'nom' => 'Électronique, électrotechnique et automatisme',
                'description' => 'Systèmes automatisés et électronique appliquée.',
            ]
        );

        // ---------------------------------------------------------------------
        // 4. MODULES OFFICIELS DU BINÔME (Exactement les 9 modules initiaux)
        // ---------------------------------------------------------------------
        Module::firstOrCreate(['code' => 'GI101'], [
            'nom' => 'Mathématiques appliquées',
            'filiere_id' => $filiereGI->id,
            'niveau_id' => $l1->id,
        ]);

        Module::firstOrCreate(['code' => 'GI102'], [
            'nom' => "Introduction à l'informatique",
            'filiere_id' => $filiereGI->id,
            'niveau_id' => $l1->id,
        ]);

        $modAlgo = Module::firstOrCreate(['code' => 'GI103'], [
            'nom' => 'Algorithmique',
            'filiere_id' => $filiereGI->id,
            'niveau_id' => $l1->id,
        ]);

        $modBD = Module::firstOrCreate(['code' => 'RS201'], [
            'nom' => 'Base de données',
            'filiere_id' => $filiereRS->id,
            'niveau_id' => $l2->id,
        ]);

        Module::firstOrCreate(['code' => 'RS202'], [
            'nom' => 'Systèmes d’exploitation',
            'filiere_id' => $filiereRS->id,
            'niveau_id' => $l2->id,
        ]);

        Module::firstOrCreate(['code' => 'RT101'], [
            'nom' => 'Réseaux',
            'filiere_id' => $filiereRT->id,
            'niveau_id' => $l1->id,
        ]);

        Module::firstOrCreate(['code' => 'RT102'], [
            'nom' => 'Télécommunications',
            'filiere_id' => $filiereRT->id,
            'niveau_id' => $l1->id,
        ]);

        Module::firstOrCreate(['code' => 'EI101'], [
            'nom' => 'Électricité générale',
            'filiere_id' => $filiereEI->id,
            'niveau_id' => $l1->id,
        ]);

        Module::firstOrCreate(['code' => 'EEA101'], [
            'nom' => 'Électronique de base',
            'filiere_id' => $filiereEEA->id,
            'niveau_id' => $l1->id,
        ]);

        // ---------------------------------------------------------------------
        // 5. UTILISATEURS DU FRONTEND (Mots de passe : admin12345, chef12345, etudiant123 ou password123)
        // ---------------------------------------------------------------------
        // 1. Compte Administrateur (Fatou Ouattara)
        User::updateOrCreate(
            ['email' => 'admin@u-auben.bf'],
            [
                'name' => 'Fatou Ouattara',
                'password' => Hash::make('admin12345'),
                'role' => 'admin',
                'filiere_id' => null,
            ]
        );

        // 2. Chef de Filière Génie Informatique (Moussa Traoré)
        User::updateOrCreate(
            ['email' => 'chef.info@u-auben.bf'],
            [
                'name' => 'Moussa Traoré',
                'password' => Hash::make('chef12345'),
                'role' => 'chef_filiere',
                'filiere_id' => $filiereGI->id,
            ]
        );

        // 3. Étudiant (Aïssatou Diallo)
        User::updateOrCreate(
            ['email' => 'etudiant@u-auben.bf'],
            [
                'name' => 'Aïssatou Diallo',
                'password' => Hash::make('etudiant123'),
                'role' => 'etudiant',
                'filiere_id' => null,
            ]
        );
    }
}
