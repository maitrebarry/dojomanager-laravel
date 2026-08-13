<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CeintureNoireManuelle;
use App\Models\Competition;
use App\Models\Cotisation;
use App\Models\Disciple;
use App\Models\Federation;
use App\Models\GradePassageSession;
use App\Models\Ligue;
use App\Models\Salle;
use App\Models\User;

/**
 * Tableau de bord disposé par rôle (copie fidèle de DashboardService.java) :
 *   superadmin → pilotage global · fédération → pilotage fédéral
 *   ligue → vue ligue · maître → vue maître
 * Chaque vue expose : titre, sous-titre nommé, « Mise à jour », insights, cartes et tables.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->role instanceof \App\Shared\Enums\UserRole ? $user->role->value : (string) $user->role;

        $data = match ($role) {
            'superadmin' => $this->adminDashboard($user, true),
            'federation', 'admin' => $this->adminDashboard($user, false),
            'ligue' => $this->ligueDashboard($user),
            'maitre' => $this->maitreDashboard($user),
            default => $this->genericDashboard(),
        };

        return view('admin.dashboard.index', array_merge($data, [
            'page_title' => __('messages.dashboard'),
            'role' => $role,
            'generated_at' => now()->format('d/m/Y H:i'),
        ]));
    }

    /* ------------------------------------------------------------------ */
    /* Dashboards par rôle                                                  */
    /* ------------------------------------------------------------------ */

    private function adminDashboard($user, bool $global): array
    {
        $ligues = Ligue::visibleTo($user)->get();
        $salles = Salle::visibleTo($user)->with('ligue:id,nom')->get();
        $activeSalles = $salles->where('active', true)->count();
        $disciplesTotal = Disciple::active()->visibleTo($user)->count();
        $maitres = $this->roleUsersCount($user, 'maitre');
        $ligueUsers = $this->roleUsersCount($user, 'ligue');
        $cnCount = $this->ceinturesNoiresCount($user);
        $cnRows = $this->ceinturesNoiresRows($user);
        $m = $this->monthlySummary($user);
        $sessions = $this->sessions($user);
        $competitions = Competition::orderByDesc('date_competition')->limit(6)->get();

        $disciplesBySalle = $this->disciplesBySalle($user);
        $disciplesByLigue = $salles->groupBy('ligue_id')->map(fn ($grp) => $grp->sum(fn ($s) => (int) ($disciplesBySalle[$s->id] ?? 0)));

        $sessionsOuvertes = $sessions->where('finalisee', false)->count();
        $sessionsFinalisees = $sessions->where('finalisee', true)->count();

        $cards = [
            $this->card('ligues', 'Ligues', $ligues->count(), $global ? 'Vue réseau globale' : 'Ligues rattachées à votre fédération', 'primary'),
            $this->card('salles', 'Salles actives', $activeSalles, $salles->count() . ' salle(s) au total', 'success'),
            $this->card('disciples', 'Disciples actifs', $disciplesTotal, 'Base sportive actuellement suivie', 'info'),
            $this->card('maitres', 'Maîtres déclarés', $maitres, $ligueUsers . ' responsable(s) de ligue', 'warning'),
            $this->card('ceintures-noires', 'Ceintures noires', $cnCount, 'Toutes sources confondues', 'dark'),
            $this->card('finances', 'Encaissements du mois', $this->currency($m['paidAmount']), $m['paidCount'] . ' paiement(s) soldés', 'danger'),
        ];

        $tables = [
            [
                'title' => 'Synthèse des ligues',
                'columns' => ['ligue' => 'Ligue', 'salles' => 'Salles', 'disciples' => 'Disciples', 'ceinturesNoires' => 'Ceintures noires'],
                'rows' => $ligues->sortByDesc(fn ($l) => (int) ($disciplesByLigue[$l->id] ?? 0))->take(6)->map(fn ($l) => [
                    'ligue' => $l->nom,
                    'salles' => (string) $salles->where('ligue_id', $l->id)->count(),
                    'disciples' => (string) (int) ($disciplesByLigue[$l->id] ?? 0),
                    'ceinturesNoires' => (string) $cnRows->where('ligue_id', $l->id)->count(),
                ])->values()->all(),
                'empty' => 'Aucune ligue disponible pour le moment.',
            ],
            $this->sessionTable($sessions, 'Sessions de passage de grades'),
            [
                'title' => 'Agenda des compétitions',
                'columns' => ['nom' => 'Nom', 'date' => 'Date', 'lieu' => 'Lieu', 'type' => 'Type'],
                'rows' => $competitions->map(fn ($c) => [
                    'nom' => $c->nom,
                    'date' => optional($c->date_competition)->format('d/m/Y') ?? '-',
                    'lieu' => $c->lieu ?? '-',
                    'type' => $c->type ?? '-',
                ])->values()->all(),
                'empty' => 'Aucune compétition enregistrée.',
            ],
        ];

        $insights = [
            $activeSalles === 0 ? "Aucune salle active n'est encore déclarée." : $activeSalles . ' salle(s) actives sur ' . $salles->count() . '.',
            $m['pendingCount'] === 0 ? 'Toutes les cotisations du mois sont soldées.' : $m['pendingCount'] . ' cotisation(s) restent en attente ce mois-ci.',
            $sessionsOuvertes === 0 ? 'Aucune session de grade ouverte actuellement.' : $sessionsOuvertes . ' session(s) de grade sont encore à piloter.',
            $sessionsFinalisees . ' session(s) finalisée(s) sont disponibles pour consultation et export.',
        ];

        $fedName = $user->federation_id ? Federation::whereKey($user->federation_id)->value('nom') : null;

        return [
            'dashboard_title' => $global ? 'Pilotage global DojoManager' : 'Pilotage fédéral',
            'subtitle' => $global ? "Vue consolidée sur l'ensemble des fédérations" : 'Indicateurs consolidés pour ' . ($fedName ?: 'votre fédération'),
            'insights' => $insights,
            'cards' => $cards,
            'tables' => $tables,
        ];
    }

    private function ligueDashboard($user): array
    {
        $salles = Salle::visibleTo($user)->with('maitre:id,nom_complet')->get();
        $activeSalles = $salles->where('active', true)->count();
        $maitres = $this->roleUsersCount($user, 'maitre');
        $disciplesTotal = Disciple::active()->visibleTo($user)->count();
        $cnCount = $this->ceinturesNoiresCount($user);
        $m = $this->monthlySummary($user);
        $sessions = $this->sessions($user);
        $sessionsOuvertes = $sessions->where('finalisee', false)->count();

        $disciplesBySalle = $this->disciplesBySalle($user);
        $payesBySalle = $m['cotisations']->where('statut', 'PAYE')
            ->groupBy(fn ($c) => $c->disciple?->salle_id)->map->count();

        $cards = [
            $this->card('salles', 'Salles', $salles->count(), $activeSalles . ' active(s)', 'primary'),
            $this->card('maitres', 'Maîtres', $maitres, 'Encadrement opérationnel de la ligue', 'warning'),
            $this->card('disciples', 'Disciples actifs', $disciplesTotal, 'Athlètes rattachés à la ligue', 'info'),
            $this->card('ceintures-noires', 'Ceintures noires', $cnCount, 'Disciples, responsables et ajouts manuels', 'dark'),
            $this->card('payees', 'Cotisations payées', $m['paidCount'], $this->currency($m['paidAmount']) . ' encaissés ce mois', 'success'),
            $this->card('sessions', 'Sessions de grade suivies', $sessions->count(), 'KEUP de la ligue et DAN fédéraux visibles', 'danger'),
        ];

        $tables = [
            [
                'title' => 'Performance des salles',
                'columns' => ['salle' => 'Salle', 'maitre' => 'Maitre', 'disciples' => 'Disciples', 'payes' => 'Payes'],
                'rows' => $salles->sortByDesc(fn ($s) => (int) ($disciplesBySalle[$s->id] ?? 0))->map(fn ($s) => [
                    'salle' => $s->nom,
                    'maitre' => $s->maitre?->nom_complet ?? 'Non affecté',
                    'disciples' => (string) (int) ($disciplesBySalle[$s->id] ?? 0),
                    'payes' => (string) (int) ($payesBySalle[$s->id] ?? 0),
                ])->values()->all(),
                'empty' => 'Aucune salle trouvée pour cette ligue.',
            ],
            $this->sessionTable($sessions, 'Passages de grades suivis'),
            $this->relanceTable($m['cotisations']),
        ];

        $insights = [
            $activeSalles . ' salle(s) active(s) sur ' . $salles->count() . ' dans votre ligue.',
            $m['pendingCount'] === 0 ? 'Aucune cotisation du mois en attente.' : $m['pendingCount'] . ' cotisation(s) du mois restent à régulariser.',
            $sessionsOuvertes . ' session(s) non finalisée(s) nécessitent encore un suivi.',
            $cnCount . ' ceinture(s) noire(s) sont recensées dans votre périmètre.',
        ];

        $ligueName = $user->ligue_id ? Ligue::whereKey($user->ligue_id)->value('nom') : null;

        return [
            'dashboard_title' => 'Vue ligue',
            'subtitle' => 'Suivi opérationnel de ' . ($ligueName ?: 'votre ligue'),
            'insights' => $insights,
            'cards' => $cards,
            'tables' => $tables,
        ];
    }

    private function maitreDashboard($user): array
    {
        $disciplesTotal = Disciple::active()->visibleTo($user)->count();
        $cnCount = $this->ceinturesNoiresCount($user);
        $m = $this->monthlySummary($user);
        $sessions = $this->sessions($user);
        $upcoming = $sessions->filter(fn ($s) => $s->date_session && !$s->date_session->isBefore(now()->startOfDay()))->count();

        $cards = [
            $this->card('disciples', 'Disciples actifs', $disciplesTotal, 'Effectif de votre salle', 'primary'),
            $this->card('payees', 'Cotisations payées', $m['paidCount'], $this->currency($m['paidAmount']) . ' encaissés ce mois', 'success'),
            $this->card('partielles', 'Cotisations partielles', $m['partialCount'], 'Reste à suivre avec les familles', 'warning'),
            $this->card('attente', 'Cotisations en attente', $m['pendingCount'], 'Relances prioritaires', 'danger'),
            $this->card('sessions', 'Passages à venir', $upcoming, 'Sessions visibles pour votre salle', 'info'),
            $this->card('ceintures-noires', 'Ceintures noires', $cnCount, 'Présentes dans votre salle', 'dark'),
        ];

        $tables = [
            $this->relanceTable($m['cotisations'], true),
            $this->sessionTable($sessions, 'Passages de grades visibles'),
        ];

        $insights = [
            $disciplesTotal === 0 ? "Aucun disciple actif n'est rattaché à votre salle." : $disciplesTotal . ' disciple(s) actifs à encadrer.',
            $m['pendingCount'] === 0 ? 'Toutes les cotisations du mois sont à jour.' : $m['pendingCount'] . ' cotisation(s) restent totalement impayées.',
            $m['partialCount'] === 0 ? 'Aucune cotisation partielle en cours.' : $m['partialCount'] . ' cotisation(s) partielles à compléter.',
            $sessions->isEmpty() ? 'Aucune session de grade visible pour votre salle.' : $sessions->count() . ' session(s) de grade concernent actuellement votre salle.',
        ];

        return [
            'dashboard_title' => 'Vue maître',
            'subtitle' => 'Suivi quotidien de votre salle',
            'insights' => $insights,
            'cards' => $cards,
            'tables' => $tables,
        ];
    }

    private function genericDashboard(): array
    {
        return [
            'dashboard_title' => 'Bienvenue dans DojoManager',
            'subtitle' => "Votre rôle ne dispose pas encore d'un tableau de bord enrichi.",
            'insights' => [],
            'cards' => [],
            'tables' => [],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function card(string $key, string $title, $value, string $subtitle, string $tone): array
    {
        return compact('key', 'title', 'value', 'subtitle', 'tone');
    }

    private function currency($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }

    private function sessions($user)
    {
        return GradePassageSession::visibleTo($user)
            ->withCount('candidats')
            ->orderByDesc('date_session')
            ->get();
    }

    /** Compte les comptes UTILISATEUR d'un rôle dans le périmètre courant (fidèle à Spring). */
    private function roleUsersCount($user, string $role): int
    {
        $query = User::where('role', $role);

        if ($user->salle_id) {
            $query->where('salle_id', $user->salle_id);
        } elseif ($user->ligue_id) {
            $query->where('ligue_id', $user->ligue_id);
        } elseif ($user->federation_id) {
            $query->where('federation_id', $user->federation_id);
        }

        return $query->count();
    }

    private function disciplesBySalle($user)
    {
        return Disciple::active()->visibleTo($user)
            ->selectRaw('salle_id, COUNT(*) c')->groupBy('salle_id')->pluck('c', 'salle_id');
    }

    private function monthlySummary($user): array
    {
        $cotisations = Cotisation::visibleTo($user)
            ->where('mois', now()->month)
            ->where('annee', now()->year)
            ->with(['disciple:id,nom,prenom,nom_complet,salle_id,grade_id', 'disciple.salle:id,nom', 'disciple.grade:id,nom_grade'])
            ->get();

        return [
            'cotisations' => $cotisations,
            'paidCount' => $cotisations->where('statut', 'PAYE')->count(),
            'paidAmount' => (float) $cotisations->sum('montant_paye'),
            'partialCount' => $cotisations->where('statut', 'PARTIEL')->count(),
            'pendingCount' => $cotisations->where('statut', 'IMPAYE')->count(),
        ];
    }

    /** Lignes des ceintures noires (disciples DAN + saisies manuelles) avec leur ligue_id. */
    private function ceinturesNoiresRows($user)
    {
        $dan = Disciple::active()->visibleTo($user)
            ->whereHas('grade', fn ($q) => $q->where('type_grade', 'DAN'))
            ->with('salle:id,ligue_id')
            ->get()
            ->map(fn ($d) => (object) ['ligue_id' => $d->salle?->ligue_id]);
        $manuelles = CeintureNoireManuelle::visibleTo($user)->with('salle:id,ligue_id')->get()
            ->map(fn ($c) => (object) ['ligue_id' => $c->salle?->ligue_id]);

        return $dan->concat($manuelles);
    }

    private function ceinturesNoiresCount($user): int
    {
        return $this->ceinturesNoiresRows($user)->count();
    }

    /** Table des sessions (fidèle à buildSessionTable). */
    private function sessionTable($sessions, string $title): array
    {
        return [
            'title' => $title,
            'columns' => ['date' => 'Date', 'type' => 'Type', 'lieu' => 'Lieu', 'candidats' => 'Candidats', 'statut' => 'Statut'],
            'rows' => $sessions->sortByDesc(fn ($s) => $s->date_session)->take(8)->map(fn ($s) => [
                'date' => $s->date_session?->format('d/m/Y') ?? '-',
                'type' => $s->type_grade ?? '-',
                'lieu' => $s->lieu ?? '-',
                'candidats' => (string) ($s->candidats_count ?? 0),
                'statut' => $s->finalisee ? 'Finalisée' : 'Ouverte',
            ])->values()->all(),
            'empty' => 'Aucune session de passage de grades à afficher.',
        ];
    }

    /** Table « Cotisations du mois à relancer » (fidèle à buildLigue/MaitreDashboard). */
    private function relanceTable($cotisations, bool $withGrade = false): array
    {
        $columns = $withGrade
            ? ['disciple' => 'Disciple', 'grade' => 'Grade', 'statut' => 'Statut', 'reste' => 'Reste']
            : ['disciple' => 'Disciple', 'salle' => 'Salle', 'statut' => 'Statut', 'reste' => 'Reste'];

        $rows = $cotisations
            ->filter(fn ($c) => $c->statut !== 'PAYE')
            ->sortBy(fn ($c) => $c->disciple?->full_name)
            ->take(8)
            ->map(function ($c) use ($withGrade) {
                $base = [
                    'disciple' => $c->disciple?->full_name ?? '-',
                    'statut' => $c->statut === 'PARTIEL' ? 'Partielle' : 'En attente',
                    'reste' => $this->currency($c->reste_a_payer),
                ];
                $base += $withGrade
                    ? ['grade' => $c->disciple?->grade?->nom_grade ?? 'Sans grade']
                    : ['salle' => $c->disciple?->salle?->nom ?? '-'];

                return $base;
            })->values()->all();

        return [
            'title' => $withGrade ? 'Disciples à relancer ce mois' : 'Cotisations du mois à relancer',
            'columns' => $columns,
            'rows' => $rows,
            'empty' => 'Aucune relance de cotisation pour ce mois.',
        ];
    }
}
