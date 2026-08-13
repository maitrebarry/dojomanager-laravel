<?php

namespace Database\Seeders;

use App\Models\Disciple;
use App\Models\Federation;
use App\Models\Grade;
use App\Models\Ligue;
use App\Models\Maitre;
use App\Models\Salle;
use App\Models\User;
use App\Shared\Enums\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Super administrateur par défaut
        User::updateOrCreate(
            ['email' => 'admin@dojomanager.local'],
            [
                'name' => 'Super Admin',
                'phone' => '70000000',
                'password' => Hash::make('superadmin123'),
                'role' => UserRole::SUPERADMIN->value,
                'status' => UserStatus::ACTIVE->value,
                'is_active' => true,
            ]
        );

        // Permissions
        $this->call(PermissionSeeder::class);

        // Jeu de données de démonstration
        $this->seedDemoData();
    }

    private function seedDemoData(): void
    {
        $federation = Federation::firstOrCreate(
            ['nom' => 'Fédération Malienne de Taekwondo'],
            ['sigle' => 'FMTKD', 'adresse' => 'Bamako', 'telephone' => '20000000', 'email' => 'contact@fmtkd.ml']
        );

        $ligue = Ligue::firstOrCreate(
            ['nom' => 'Ligue de Ségou', 'federation_id' => $federation->id],
            ['region' => 'Ségou', 'active' => true]
        );

        $maitre = Maitre::firstOrCreate(
            ['nom_complet' => 'Maître Amadou Traoré'],
            ['telephone' => '76000000', 'email' => 'a.traore@example.ml', 'grade' => '4e Dan']
        );

        $salle = Salle::firstOrCreate(
            ['nom' => 'Dojo Central Ségou', 'ligue_id' => $ligue->id],
            ['adresse' => 'Quartier Administratif', 'telephone' => '21000000', 'mensualite' => 5000, 'active' => true, 'maitre_id' => $maitre->id]
        );

        // Quelques grades KEUP + DAN
        $gradesData = [
            ['niveau' => 10, 'nom_grade' => '10e Keup', 'ceinture' => 'Blanche', 'type_grade' => 'KEUP'],
            ['niveau' => 8, 'nom_grade' => '8e Keup', 'ceinture' => 'Jaune', 'type_grade' => 'KEUP'],
            ['niveau' => 4, 'nom_grade' => '4e Keup', 'ceinture' => 'Bleue', 'type_grade' => 'KEUP'],
            ['niveau' => 1, 'nom_grade' => '1er Keup', 'ceinture' => 'Rouge', 'type_grade' => 'KEUP'],
            ['niveau' => 1, 'nom_grade' => '1er Dan', 'ceinture' => 'Noire', 'type_grade' => 'DAN'],
        ];

        $grades = [];
        foreach ($gradesData as $g) {
            $grades[] = Grade::firstOrCreate(
                ['nom_grade' => $g['nom_grade'], 'federation_id' => $federation->id],
                array_merge($g, ['federation_id' => $federation->id])
            );
        }

        // Quelques disciples de démonstration
        $disciplesData = [
            ['nom' => 'Diallo', 'prenom' => 'Fatoumata', 'sexe' => 'F', 'nmle' => 'DOJO-0001'],
            ['nom' => 'Koné', 'prenom' => 'Ibrahim', 'sexe' => 'M', 'nmle' => 'DOJO-0002'],
            ['nom' => 'Sangaré', 'prenom' => 'Awa', 'sexe' => 'F', 'nmle' => 'DOJO-0003'],
            ['nom' => 'Touré', 'prenom' => 'Moussa', 'sexe' => 'M', 'nmle' => 'DOJO-0004'],
        ];

        foreach ($disciplesData as $i => $d) {
            Disciple::firstOrCreate(
                ['nmle' => $d['nmle']],
                array_merge($d, [
                    'nom_complet' => $d['prenom'] . ' ' . $d['nom'],
                    'date_inscription' => now()->subMonths($i)->toDateString(),
                    'salle_id' => $salle->id,
                    'grade_id' => $grades[array_rand($grades)]->id,
                ])
            );
        }
    }
}
