<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Permissions = codes exacts du backend Spring (PermissionService.initDefaultPermissions).
 * Le code est stocké dans la colonne `slug` ; `module` sert au regroupement dans l'onglet Permissions.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Tableau de bord' => [
                'DASHBOARD_VIEW' => 'Voir le tableau de bord',
            ],
            'Disciples' => [
                'DISCIPLE_CREATE' => 'Créer des disciples',
                'DISCIPLE_READ' => 'Lire des disciples',
                'DISCIPLE_UPDATE' => 'Mettre à jour des disciples',
                'DISCIPLE_DELETE' => 'Supprimer des disciples',
            ],
            'Ceintures noires' => [
                'CEINTURESNOIRES_CREATE' => 'Créer des ceintures noires manuelles',
                'CEINTURESNOIRES_READ' => "Lire l'annuaire des ceintures noires",
                'CEINTURESNOIRES_UPDATE' => 'Mettre à jour ou archiver des ceintures noires manuelles',
                'CEINTURESNOIRES_DELETE' => 'Supprimer des ceintures noires manuelles',
            ],
            'Passages de grades' => [
                'PASSAGEGRADES_READ' => 'Lire les sessions de passage de grades',
                'PASSAGEGRADES_MANAGE' => 'Gérer les passages de grades',
                'PASSAGEGRADES_VALIDATE' => 'Valider les candidatures de passage de grades par délégation',
                'PASSAGEGRADES_EVALUATE' => 'Examiner les candidats de passage de grades par délégation',
            ],
            'Cotisations' => [
                'COTISATION_MANAGE' => 'Gérer les cotisations',
            ],
            'Compétitions' => [
                'COMPETITION_MANAGE' => 'Gérer les compétitions',
                'COMBAT_MANAGE' => 'Gérer les combats',
            ],
            'Utilisateurs' => [
                'UTILISATEUR_CREATE' => 'Créer des utilisateurs',
                'UTILISATEUR_READ' => 'Lire les utilisateurs',
                'UTILISATEUR_UPDATE' => 'Mettre à jour des utilisateurs',
                'UTILISATEUR_DELETE' => 'Supprimer des utilisateurs',
            ],
            'Fédérations' => [
                'FÉDÉRATION_CREATE' => 'Créer des fédérations',
                'FÉDÉRATION_READ' => 'Lire des fédérations',
                'FÉDÉRATION_UPDATE' => 'Mettre à jour des fédérations',
                'FÉDÉRATION_DELETE' => 'Supprimer des fédérations',
            ],
            'Ligues' => [
                'LIGUE_CREATE' => 'Créer des ligues',
                'LIGUE_READ' => 'Lire des ligues',
                'LIGUE_UPDATE' => 'Mettre à jour des ligues',
                'LIGUE_DELETE' => 'Supprimer des ligues',
            ],
            'Salles' => [
                'SALLE_CREATE' => 'Créer des salles',
                'SALLE_READ' => 'Lire des salles',
                'SALLE_UPDATE' => 'Mettre à jour des salles',
                'SALLE_DELETE' => 'Supprimer des salles',
            ],
            'Maîtres' => [
                'MAITRE_CREATE' => 'Créer des maîtres',
                'MAITRE_READ' => 'Lire les maîtres',
                'MAITRE_UPDATE' => 'Mettre à jour des maîtres',
                'MAITRE_DELETE' => 'Supprimer des maîtres',
                'MAITRE_MANAGE' => 'Gérer les maîtres',
            ],
            'Grades' => [
                'GRADES_CREATE' => 'Créer des grades',
                'GRADES_READ' => 'Lire des grades',
                'GRADES_UPDATE' => 'Mettre à jour des grades',
                'GRADES_DELETE' => 'Supprimer des grades',
            ],
            'Permissions' => [
                'PERMISSION_ASSIGN' => 'Assigner des permissions aux utilisateurs',
                'PERMISSION_READ' => 'Lire la liste des permissions',
                'PERMISSION_MANAGE' => 'Gérer les permissions',
            ],
            'Paramètres' => [
                'PARAMETRES_READ' => 'Lire les paramètres',
                'PARAMETRES_MANAGE' => 'Gérer les paramètres',
            ],
            'Rapports' => [
                'COVID_ANALYTICS' => 'Accéder aux rapports',
            ],
        ];

        $order = 0;

        foreach ($groups as $module => $permissions) {
            foreach ($permissions as $code => $description) {
                $order += 1;
                $action = strtolower((string) (explode('_', $code)[1] ?? 'manage'));

                $model = Permission::withTrashed()->firstOrNew(['slug' => $code]);
                if ($model->exists && $model->trashed()) {
                    $model->restore();
                }
                $model->fill([
                    'name' => $description,
                    'module' => $module,
                    'slug' => $code,
                    'action' => $action,
                    'order' => $order,
                    'is_active' => true,
                ]);
                $model->save();
            }
        }
    }
}
