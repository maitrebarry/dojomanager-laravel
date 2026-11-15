{{-- Onglet Synthèse : chiffres clés, avancement par catégorie, clubs et ordre de passage des combats. --}}
@php
    $athletes = $competition->athletes;
    $active = $categories->filter(fn ($c) => $c->athletes_count > 0);
    $fights = $categories->flatMap(fn ($c) => $c->combats)->reject(fn ($f) => $f->exempt);
    $played = $fights->filter(fn ($f) => $f->vainqueur_athlete_id)->count();
    $finished = $active->filter(fn ($c) => $podiums[$c->id]['complete'])->count();
    $clubs = $athletes->groupBy(fn ($a) => $a->club ?: __('messages.competitions.no_club'))->map->count()->sortDesc();
    $groupsCount = $athletes->filter(fn ($a) => $a->category_id)
        ->groupBy(fn ($a) => $categories->firstWhere('id', $a->category_id)?->groupLabel())->map->count();
    // Prochains combats à appeler : les premiers combats prêts (deux combattants connus), un par aire.
    $nextIds = $fightOrder->filter(fn ($i) => $i['combat']->isReady())->take($aires)->pluck('combat.id')->all();
    $kpis = [
        ['fa-users', __('messages.competitions.athletes'), $athletes->count(), $toWeigh->isNotEmpty() ? __('messages.competitions.summary_to_weigh', ['count' => $toWeigh->count()]) : __('messages.competitions.summary_all_weighed')],
        ['fa-weight-scale', __('messages.competitions.categories'), $active->count(), __('messages.competitions.summary_categories', ['drawn' => $active->filter(fn ($c) => $c->tirage_at)->count(), 'valid' => $active->filter(fn ($c) => $c->tirage_valide)->count()])],
        ['fa-hand-fist', __('messages.competitions.summary_fights'), $fights->count(), __('messages.competitions.summary_played', ['played' => $played, 'left' => $fights->count() - $played])],
        ['fa-trophy', __('messages.competitions.summary_finished'), $finished . ' / ' . $active->count(), __('messages.competitions.summary_clubs', ['count' => $clubs->count()])],
    ];
@endphp

<div class="row g-3 mb-3">
    @foreach($kpis as [$icon, $label, $value, $hint])
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><i class="fas {{ $icon }} me-1"></i> {{ $label }}</div>
                    <div class="fs-3 fw-bold">{{ $value }}</div>
                    <div class="small text-muted">{{ $hint }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($fights->isNotEmpty())
    <div class="progress mb-3" style="height: 10px;" title="{{ $played }} / {{ $fights->count() }}">
        <div class="progress-bar bg-success" style="width: {{ round($played / max(1, $fights->count()) * 100) }}%;"></div>
    </div>
@endif

@if($unclassified->isNotEmpty() || $toWeigh->isNotEmpty() || $active->contains(fn ($c) => $c->athletes_count >= 2 && !$c->tirage_at))
    <div class="alert alert-warning small">
        <i class="fas fa-triangle-exclamation me-1"></i> <strong>{{ __('messages.competitions.summary_todo') }}</strong>
        <ul class="mb-0">
            @if($toWeigh->isNotEmpty())<li>{{ __('messages.competitions.summary_todo_weigh', ['count' => $toWeigh->count()]) }}</li>@endif
            @if($unclassified->isNotEmpty())<li>{{ __('messages.competitions.summary_todo_unclassified', ['count' => $unclassified->count()]) }}</li>@endif
            @php $notDrawn = $active->filter(fn ($c) => $c->athletes_count >= 2 && !$c->tirage_at)->count(); @endphp
            @if($notDrawn)<li>{{ __('messages.competitions.summary_todo_draw', ['count' => $notDrawn]) }}</li>@endif
        </ul>
    </div>
@endif

<div class="row g-3 mb-3">
    {{-- Avancement par catégorie --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-list-check me-1"></i> {{ __('messages.competitions.summary_by_category') }}</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.competitions.category') }}</th>
                            <th class="text-center">{{ __('messages.competitions.athletes') }}</th>
                            <th class="text-center">{{ __('messages.competitions.summary_fights') }}</th>
                            <th>{{ __('messages.competitions.tab_draw') }}</th>
                            <th>{{ __('messages.competitions.gold') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($active as $c)
                            @php
                                $catFights = $c->combats->reject(fn ($f) => $f->exempt);
                                $catPlayed = $catFights->filter(fn ($f) => $f->vainqueur_athlete_id)->count();
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.competitions.show', [$competition, 'tab' => 'tirage']) }}#categorie-{{ $c->id }}" class="text-decoration-none">{{ $categoryLabels[$c->id] }}</a></td>
                                <td class="text-center">{{ $c->athletes_count }}</td>
                                <td class="text-center">{{ $catFights->isNotEmpty() ? $catPlayed . ' / ' . $catFights->count() : '-' }}</td>
                                <td>@include('admin.competitions.tabs._draw_status', ['category' => $c])</td>
                                <td class="small">
                                    @if($podiums[$c->id]['complete'])🥇 {{ $podiums[$c->id]['gold']?->fullName() }}@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.competitions.no_athletes') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Répartition par groupe et par club --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-people-group me-1"></i> {{ __('messages.competitions.summary_by_group') }}</div>
            <ul class="list-group list-group-flush small">
                @forelse($groupsCount as $label => $count)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $label }}</span><strong>{{ $count }}</strong></li>
                @empty
                    <li class="list-group-item text-muted">—</li>
                @endforelse
            </ul>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-building me-1"></i> {{ __('messages.competitions.summary_by_club') }}</div>
            <ul class="list-group list-group-flush small" style="max-height: 320px; overflow-y: auto;">
                @forelse($clubs as $club => $count)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $club }}</span>
                        <span>
                            @if(isset($medals[$club]))<span class="text-muted me-2">🥇{{ $medals[$club]['gold'] }} 🥈{{ $medals[$club]['silver'] }} 🥉{{ $medals[$club]['bronze'] }}</span>@endif
                            <strong>{{ $count }}</strong>
                        </span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">—</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

{{-- Rapport journalier imprimable (français ou arabe) --}}
@php
    $reportDays = $fights->filter(fn ($f) => $f->vainqueur_athlete_id)->map(fn ($f) => ($f->joue_at ?? $f->updated_at)?->toDateString())
        ->push(now()->toDateString())->push($competition->date_competition?->toDateString())
        ->filter()->unique()->sortDesc()->values();
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold me-2"><i class="fas fa-file-lines me-1"></i> {{ __('messages.competitions.report_title') }}</span>
        <select id="reportDay" class="form-select form-select-sm w-auto">
            @foreach($reportDays as $d)
                <option value="{{ $d }}" @selected($d === now()->toDateString())>{{ \Illuminate\Support\Carbon::parse($d)->locale('fr')->translatedFormat('l j F Y') }}</option>
            @endforeach
        </select>
        <a class="btn btn-sm btn-outline-success js-report" data-lang="fr" target="_blank" href="#"><i class="fas fa-print me-1"></i> {{ __('messages.competitions.report_fr') }}</a>
        <a class="btn btn-sm btn-outline-success js-report" data-lang="ar" target="_blank" href="#"><i class="fas fa-print me-1"></i> {{ __('messages.competitions.report_ar') }}</a>
        <small class="text-muted w-100">{{ __('messages.competitions.report_help') }}</small>
    </div>
</div>
<script>
    document.querySelectorAll('.js-report').forEach(function (link) {
        link.addEventListener('click', function () {
            this.href = @js(route('admin.competitions.report', $competition)) + '?lang=' + this.dataset.lang
                + '&aires=' + @js($aires) + '&date=' + document.getElementById('reportDay').value;
        });
    });
</script>

{{-- Ordre de passage de tous les combats --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold me-2"><i class="fas fa-list-ol me-1"></i> {{ __('messages.competitions.order_title') }}</span>
        <form method="GET" action="{{ route('admin.competitions.show', $competition) }}" class="d-flex align-items-center gap-1">
            <input type="hidden" name="tab" value="synthese">
            <label class="small text-muted" for="aires">{{ __('messages.competitions.order_areas') }}</label>
            <select name="aires" id="aires" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                @for($n = 1; $n <= 6; $n++)<option value="{{ $n }}" @selected($aires === $n)>{{ $n }}</option>@endfor
            </select>
        </form>
        <a class="btn btn-sm btn-dark ms-auto" target="_blank" href="{{ route('admin.competitions.chrono', $competition) }}"><i class="fas fa-stopwatch me-1"></i> {{ __('messages.competitions.chrono') }}</a>
        @if($fightOrder->isNotEmpty())
            <a class="btn btn-sm btn-outline-dark" target="_blank" href="{{ route('admin.competitions.order.pdf', [$competition, 'aires' => $aires]) }}"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.competitions.order_pdf') }}</a>
        @endif
    </div>
    <div class="card-body py-2 small text-muted">{{ __('messages.competitions.order_help') }}</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">{{ __('messages.competitions.order_number') }}</th>
                    @if($aires > 1)<th style="width: 60px;">{{ __('messages.competitions.order_area') }}</th>@endif
                    <th>{{ __('messages.competitions.category') }}</th>
                    <th>{{ __('messages.competitions.round') }}</th>
                    <th class="text-center" title="{{ __('messages.competitions.order_bracket_no_help') }}">{{ __('messages.competitions.order_bracket_no') }}</th>
                    <th style="color: {{ $profile['corners'][1]['color'] }};">{{ $profile['corners'][1]['name'] }}</th>
                    <th style="color: {{ $profile['corners'][2]['color'] }};">{{ $profile['corners'][2]['name'] }}</th>
                    <th>{{ __('messages.competitions.winner_to_fill') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fightOrder as $item)
                    @php $f = $item['combat']; @endphp
                    <tr class="{{ in_array($f->id, $nextIds, true) ? 'table-warning' : ($f->vainqueur_athlete_id ? 'text-muted' : '') }}">
                        <td class="fw-bold">
                            {{ $item['order'] }}
                            @if(in_array($f->id, $nextIds, true))<span class="badge bg-warning text-dark ms-1">{{ __('messages.competitions.order_next') }}</span>@endif
                        </td>
                        @if($aires > 1)<td>{{ $item['aire'] }}</td>@endif
                        <td class="small">{{ $categoryLabels[$item['category_id']] }}</td>
                        <td class="small">{{ $item['stage'] }}</td>
                        <td class="text-center">{{ $f->numero }}</td>
                        <td>{{ $f->athlete1?->fullName() ?? '…' }}</td>
                        <td>{{ $f->athlete2?->fullName() ?? '…' }}</td>
                        <td>
                            @if($f->vainqueurAthlete)<i class="fas fa-trophy text-warning me-1"></i>{{ $f->vainqueurAthlete->fullName() }} @if($f->score)<span class="text-muted">({{ $f->score }})</span>@endif @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('messages.competitions.order_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
