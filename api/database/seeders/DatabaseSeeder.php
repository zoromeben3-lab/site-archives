<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Archive;
use App\Models\Filiere;
use App\Models\Module;
use App\Models\Niveau;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        // 5. COMPTE ADMINISTRATEUR (identifiants lus dans .env, jamais écrits dans le code)
        //    ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD
        //    Les autres admins se créent ensuite depuis la page « Utilisateurs ».
        // ---------------------------------------------------------------------
        $local = app()->environment('local', 'testing');

        $adminEmail = env('ADMIN_EMAIL', 'admin@u-auben.bf');
        $adminNom = env('ADMIN_NAME', 'Administrateur');
        $adminMdp = env('ADMIN_PASSWORD');
        $mdpGenere = false;

        if (! $adminMdp) {
            if ($local) {
                $adminMdp = 'admin12345'; // confort de développement uniquement
            } else {
                $adminMdp = Str::password(16, symbols: false);
                $mdpGenere = true;
            }
        }

        // firstOrCreate : relancer le seeder ne réinitialise JAMAIS un mot de passe existant
        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            ['name' => $adminNom, 'password' => $adminMdp, 'role' => 'admin', 'filiere_id' => null]
        );

        if ($mdpGenere && $admin->wasRecentlyCreated) {
            $this->command?->warn("Compte admin créé : {$adminEmail} / mot de passe : {$adminMdp}");
            $this->command?->warn('Notez-le maintenant et changez-le depuis « Mon profil ».');
        }

        // ---------------------------------------------------------------------
        // 6. COMPTES ET ARCHIVES DE DÉMONSTRATION (environnement local uniquement)
        // ---------------------------------------------------------------------
        if (! $local) {
            return;
        }

        User::firstOrCreate(
            ['email' => 'chef.info@u-auben.bf'],
            ['name' => 'Moussa Traoré', 'password' => 'chef12345', 'role' => 'chef_filiere', 'filiere_id' => $filiereGI->id]
        );
        User::firstOrCreate(
            ['email' => 'etudiant@u-auben.bf'],
            ['name' => 'Aïssatou Diallo', 'password' => 'etudiant123', 'role' => 'etudiant', 'filiere_id' => null]
        );

        $demo = [
            ['Algorithmique - Devoir 1', 'devoir', 'GI103', '2024 - 2025'],
            ['Algorithmique - Test 1', 'test', 'GI103', '2024 - 2025'],
            ['Algorithmique - Épreuve de session', 'examen', 'GI103', '2023 - 2024'],
            ['Mathématiques appliquées - Devoir 1', 'devoir', 'GI101', '2024 - 2025'],
            ['Introduction à l\'informatique - Test 1', 'test', 'GI102', '2024 - 2025'],
            ['Base de données - Épreuve de session', 'examen', 'RS201', '2024 - 2025'],
            ['Réseaux - Devoir 1', 'devoir', 'RT101', '2023 - 2024'],
            ['Électricité générale - Test 1', 'test', 'EI101', '2024 - 2025'],
        ];

        foreach ($demo as [$titre, $type, $codeModule, $annee]) {
            if (Archive::where('titre', $titre)->exists()) {
                continue;
            }

            $module = Module::with(['filiere', 'niveau'])->where('code', $codeModule)->first();
            $anneeObj = AnneeAcademique::where('annee', $annee)->first();
            if (! $module || ! $anneeObj) {
                continue;
            }

            $contenu = self::pdfSimple([
                $titre,
                "Filiere : {$module->filiere->nom}",
                "Niveau : {$module->niveau->intitule}",
                "Module : {$module->nom}",
                "Annee academique : {$annee}",
                '',
                'Document de demonstration genere automatiquement.',
                'Remplacez-le en deposant de vrais documents depuis la page Ajouter une archive.',
            ]);

            $chemin = 'archives/demo-' . Str::slug($titre) . '.pdf';
            Storage::disk('local')->put($chemin, $contenu);

            Archive::create([
                'titre' => $titre,
                'description' => 'Archive de démonstration.',
                'type' => $type,
                'module_id' => $module->id,
                'annee_academique_id' => $anneeObj->id,
                'user_id' => $admin->id,
                'file_path' => $chemin,
                'file_name_original' => Str::slug($titre) . '.pdf',
                'file_size' => strlen($contenu),
                'mime_type' => 'application/pdf',
                'downloads_count' => 0,
            ]);
        }
    }

    // Fabrique un petit PDF valide d'une page (texte ASCII) pour les archives de démonstration.
    private static function pdfSimple(array $lignes): string
    {
        $echap = fn (string $t) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], Str::ascii($t));

        $flux = 'BT /F1 16 Tf 60 780 Td (' . $echap($lignes[0]) . ") Tj ET\n";
        $y = 750;
        foreach (array_slice($lignes, 1) as $ligne) {
            $flux .= "BT /F1 11 Tf 60 {$y} Td (" . $echap($ligne) . ") Tj ET\n";
            $y -= 20;
        }

        $objets = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($flux) . " >>\nstream\n" . $flux . 'endstream',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $positions = [];
        foreach ($objets as $i => $objet) {
            $positions[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n{$objet}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objets) + 1) . "\n0000000000 65535 f \n";
        foreach ($positions as $pos) {
            $pdf .= sprintf("%010d 00000 n \n", $pos);
        }
        $pdf .= 'trailer\n<< /Size ' . (count($objets) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
