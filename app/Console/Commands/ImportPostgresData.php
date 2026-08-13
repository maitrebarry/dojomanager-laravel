<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;

/**
 * Importe les données métier de l'ancienne base PostgreSQL (backend Spring Boot)
 * vers la base MySQL de l'application Laravel.
 *
 * Les données sont extraites via le client `psql` (aucune extension PHP pgsql requise).
 *
 * Usage : php artisan dojo:import-postgres [--fresh]
 * Identifiants surchargeables via .env (PG_IMPORT_HOST / PORT / DATABASE / USERNAME / PASSWORD).
 */
class ImportPostgresData extends Command
{
    protected $signature = 'dojo:import-postgres
        {--fresh : Vide les tables métier avant import}
        {--skip-users : N\'importe pas les utilisateurs}';

    protected $description = "Importe les données de l'ancienne base PostgreSQL dojomanager_db vers MySQL";

    /**
     * Pipeline d'import : [table PG, table MySQL, transform(row): array].
     * Ordre respectant les dépendances de clés étrangères.
     */
    private function pipeline(): array
    {
        return [
            ['federations', 'federations', null],
            ['maitres', 'maitres', null],
            ['ligues', 'ligues', null],
            ['grades', 'grades', null],
            ['salles', 'salles', null],
            ['disciples', 'disciples', function (array $r) {
                $r['sexe'] = $this->normSexe($r['sexe'] ?? null);
                return $r;
            }],
            ['cotisations', 'cotisations', function (array $r) {
                $r['montant_paye'] = (float) ($r['montant_paye'] ?? 0);
                $r['reste_a_payer'] = (float) ($r['resteapayer'] ?? 0);
                unset($r['resteapayer']);
                return $r;
            }],
            ['cotisation_paiements', 'cotisation_paiements', null],
            ['grade_passage_tariffs', 'grade_passage_tariffs', null],
            ['grade_passage_sessions', 'grade_passage_sessions', null],
            ['grade_passage_candidates', 'grade_passage_candidates', function (array $r) {
                $resultat = $r['resultat'] ?? null;
                $fr = (float) ($r['frais_participation'] ?? 0);
                $pa = (float) ($r['montant_paye'] ?? 0);

                $r['frais_participation'] = $fr;
                $r['montant_paye'] = $pa;
                $r['sexe'] = $this->normSexe($r['sexe'] ?? null);
                $r['note_globale'] = $r['moyenne_generale'] ?? null;
                $r['statut'] = in_array($resultat, ['VALIDE', 'REFUSE'], true) ? $resultat : 'EN_ATTENTE';
                $r['resultat'] = $resultat === 'VALIDE' ? 'ADMIS' : ($resultat === 'REFUSE' ? 'AJOURNE' : null);
                $r['statut_paiement'] = $pa <= 0 ? 'IMPAYE' : ($pa < $fr ? 'PARTIEL' : 'PAYE');
                return $r;
            }],
            ['grade_passage_candidate_payments', 'grade_passage_candidate_payments', function (array $r) {
                $r['montant'] = (float) ($r['montant_paye'] ?? 0);
                $r['created_by_user_id'] = $r['payment_recorded_by_user_id'] ?? null;
                return $r;
            }],
            ['ceintures_noires_manuelles', 'ceintures_noires_manuelles', null],
            ['cotisations_annuelles', 'cotisations_annuelles', null],
            ['cotisations_annuelles_ceintures_noires', 'cotisations_annuelles_ceintures_noires', null],
            ['cotisations_annuelles_paiements', 'cotisations_annuelles_paiements', null],
            ['competitions', 'competitions', null],
            ['combats', 'combats', null],
            ['signatures', 'signatures', null],
        ];
    }

    public function handle(): int
    {
        if (trim((string) Process::run(['which', 'psql'])->output()) === '') {
            $this->error("Le client `psql` est introuvable. Installez postgresql-client.");
            return self::FAILURE;
        }

        // Test de connexion
        $test = $this->psql('SELECT 1');
        if ($test === null) {
            $this->error('Connexion PostgreSQL impossible (vérifiez les identifiants PG_IMPORT_*).');
            return self::FAILURE;
        }

        $this->info("Connexion PostgreSQL OK. Démarrage de l'import...");

        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0');

        $pipeline = $this->pipeline();

        if ($this->option('fresh')) {
            foreach (array_reverse($pipeline) as [$pg, $mysql]) {
                DB::connection('mysql')->table($mysql)->truncate();
            }
            $this->line('Tables métier vidées.');
        }

        $totalImported = 0;

        foreach ($pipeline as [$pgTable, $mysqlTable, $transform]) {
            if (!Schema::hasTable($mysqlTable)) {
                $this->warn("  - $mysqlTable : table MySQL absente, ignorée.");
                continue;
            }

            $rows = $this->fetchTable($pgTable);
            if ($rows === null) {
                $this->warn("  - $pgTable : lecture PostgreSQL échouée, ignorée.");
                continue;
            }

            $mysqlCols = Schema::getColumnListing($mysqlTable);
            $hasCreatedAt = in_array('created_at', $mysqlCols, true);
            $hasUpdatedAt = in_array('updated_at', $mysqlCols, true);
            $now = now();
            $count = 0;

            foreach (array_chunk($rows, 200) as $chunk) {
                $batch = [];

                foreach ($chunk as $pgRow) {
                    $row = (array) $pgRow;

                    if ($transform) {
                        $row = $transform($row);
                    }

                    $row = array_intersect_key($row, array_flip($mysqlCols));

                    if ($hasCreatedAt && empty($row['created_at'])) {
                        $row['created_at'] = $now;
                    }
                    if ($hasUpdatedAt && empty($row['updated_at'])) {
                        $row['updated_at'] = $now;
                    }

                    $batch[] = $row;
                }

                if (!empty($batch)) {
                    DB::connection('mysql')->table($mysqlTable)->insert($batch);
                    $count += count($batch);
                }
            }

            $totalImported += $count;
            $this->line(sprintf('  ✓ %-42s %d ligne(s)', $mysqlTable, $count));
        }

        if (!$this->option('skip-users')) {
            $users = $this->importUsers();
            $totalImported += $users;
            $this->line(sprintf('  ✓ %-42s %d ligne(s) (rôles/périmètre mappés)', 'users', $users));

            $perms = $this->importUserPermissions();
            $totalImported += $perms;
            $this->line(sprintf('  ✓ %-42s %d ligne(s) (mappées par code)', 'user_permissions', $perms));
        }

        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info("Import terminé : $totalImported ligne(s) importée(s).");
        $this->comment("Permissions granulaires PG non importées : les rôles federation/ligue/maitre ont tous les droits DANS leur périmètre.");

        return self::SUCCESS;
    }

    /**
     * Importe les utilisateurs PG avec mapping des rôles vers le modèle multi-tenant,
     * en préservant les mots de passe (BCrypt compatible) et le périmètre.
     */
    private function importUsers(): int
    {
        $rows = $this->fetchTable('users');
        if ($rows === null) {
            $this->warn('  - users : lecture PostgreSQL échouée, ignorée.');
            return 0;
        }

        // Rôles Spring -> rôles Laravel multi-tenant
        $roleMap = [
            'SUPERADMIN' => 'superadmin',
            'ADMIN' => 'federation',   // compte fédération (périmètre = federation_id)
            'LIGUE' => 'ligue',
            'MAITRE' => 'maitre',
            'PRESIDENT' => 'president',
        ];

        DB::connection('mysql')->table('users')->truncate();

        $now = now();
        $seenPhones = [];
        $count = 0;

        foreach ($rows as $r) {
            $r = (array) $r;

            $phone = $r['telephone'] ?? null;
            if ($phone && in_array($phone, $seenPhones, true)) {
                $phone = null; // téléphone unique : on neutralise les doublons de l'ancienne base
            } elseif ($phone) {
                $seenPhones[] = $phone;
            }

            // Les mots de passe de l'ancienne base sont en clair -> on les hache en BCrypt.
            $rawPwd = $r['password'] ?? null;
            $password = ($rawPwd && preg_match('/^\$2[aby]\$/', $rawPwd))
                ? $rawPwd
                : Hash::make($rawPwd !== null && $rawPwd !== '' ? $rawPwd : bin2hex(random_bytes(8)));

            DB::connection('mysql')->table('users')->insert([
                'id' => $r['id'],
                'name' => trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? '')) ?: ($r['email'] ?? 'Utilisateur'),
                'email' => $r['email'] ?? null,
                'phone' => $phone,
                'password' => $password,
                'role' => $roleMap[strtoupper((string) ($r['role'] ?? ''))] ?? 'user',
                'status' => 'active',
                'is_active' => 1,
                'locale' => 'fr',
                'federation_id' => $r['federation_id'] ?? null,
                'ligue_id' => $r['ligue_id'] ?? null,
                'salle_id' => $r['salle_id'] ?? null,
                'grade_id' => $r['grade_id'] ?? null,
                'fonction' => $r['fonction'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $count++;
        }

        $this->fallbackAdmin($now);

        return $count;
    }

    /**
     * Importe les affectations de permissions (user_permissions) en mappant
     * l'id de permission PG vers l'id Laravel PAR CODE (mêmes codes des deux côtés).
     */
    private function importUserPermissions(): int
    {
        $pgPerms = $this->fetchTable('permissions');
        $links = $this->fetchTable('user_permissions');
        if ($pgPerms === null || $links === null) {
            return 0;
        }

        // PG permission_id -> code
        $pgIdToCode = [];
        foreach ($pgPerms as $p) {
            $p = (array) $p;
            $pgIdToCode[$p['id']] = $p['code'] ?? null;
        }

        // code normalisé -> id Laravel
        $codeToLaravelId = [];
        foreach (DB::connection('mysql')->table('permissions')->get(['id', 'slug']) as $lp) {
            $codeToLaravelId[$this->normCode($lp->slug)] = $lp->id;
        }

        $userIds = DB::connection('mysql')->table('users')->pluck('id')->all();

        DB::connection('mysql')->table('user_permissions')->truncate();

        $now = now();
        $seen = [];
        $batch = [];

        foreach ($links as $l) {
            $l = (array) $l;
            $uid = $l['user_id'] ?? null;
            $code = $pgIdToCode[$l['permission_id'] ?? null] ?? null;
            if (!$uid || !$code || !in_array($uid, $userIds)) {
                continue;
            }

            $lid = $codeToLaravelId[$this->normCode($code)] ?? null;
            if (!$lid) {
                continue;
            }

            $key = $uid . '-' . $lid;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $batch[] = [
                'user_id' => $uid,
                'permission_id' => $lid,
                'granted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($batch, 300) as $chunk) {
            DB::connection('mysql')->table('user_permissions')->insert($chunk);
        }

        return count($batch);
    }

    /** Normalise un code de permission (retire les accents, majuscules). */
    private function normCode(?string $code): string
    {
        if ($code === null || $code === '') {
            return '';
        }
        $code = strtr(trim($code), [
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ç' => 'C', 'ç' => 'c', 'À' => 'A', 'à' => 'a', 'Ô' => 'O', 'ô' => 'o',
        ]);
        return mb_strtoupper($code);
    }

    /** Compte super-admin de secours (identifiants connus) pour garantir l'accès. */
    private function fallbackAdmin($now): void
    {
        DB::connection('mysql')->table('users')->updateOrInsert(
            ['email' => 'admin@dojomanager.local'],
            [
                'name' => 'Super Admin',
                'phone' => '70000000',
                'password' => Hash::make('superadmin123'),
                'role' => 'superadmin',
                'status' => 'active',
                'is_active' => 1,
                'locale' => 'fr',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    /**
     * Récupère toutes les lignes d'une table PG sous forme de tableau associatif.
     */
    private function fetchTable(string $table): ?array
    {
        $json = $this->psql("SELECT COALESCE(json_agg(t), '[]'::json) FROM \"{$table}\" t");

        if ($json === null) {
            return null;
        }

        $decoded = json_decode(trim($json), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Exécute une requête via le client psql et retourne la sortie brute (ou null si erreur).
     */
    private function psql(string $sql): ?string
    {
        $result = Process::env(['PGPASSWORD' => env('PG_IMPORT_PASSWORD', 'dojomanager_password')])
            ->run([
                'psql',
                '-h', env('PG_IMPORT_HOST', '127.0.0.1'),
                '-p', (string) env('PG_IMPORT_PORT', '5432'),
                '-U', env('PG_IMPORT_USERNAME', 'dojomanager_user'),
                '-d', env('PG_IMPORT_DATABASE', 'dojomanager_db'),
                '-tAc', $sql,
            ]);

        return $result->successful() ? $result->output() : null;
    }

    /**
     * Normalise la valeur de sexe (l'ancienne base stocke « Homme »/« Femme »).
     */
    private function normSexe(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $v = mb_strtoupper(trim($value));

        return match (true) {
            str_starts_with($v, 'H'), str_starts_with($v, 'M') => 'M',
            str_starts_with($v, 'F') => 'F',
            default => null,
        };
    }
}
