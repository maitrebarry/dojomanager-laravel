{{-- Onglet Athlètes : saisie, import, liste par catégorie. --}}
@if($categories->isEmpty())
    <div class="alert alert-warning">
        <i class="fas fa-triangle-exclamation me-1"></i> {{ __('messages.competitions.no_categories') }}
        <a href="{{ route('admin.competitions.show', [$competition, 'tab' => 'categories']) }}" class="alert-link">{{ __('messages.competitions.tab_categories') }} →</a>
    </div>
@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addAthlete"><i class="fas fa-user-plus me-1"></i> {{ __('messages.competitions.add_athlete') }}</button>
    <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#importList"><i class="fas fa-file-import me-1"></i> {{ __('messages.competitions.import') }}</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.competitions.athletes.template', $competition) }}"><i class="fas fa-file-excel me-1"></i> {{ __('messages.competitions.import_template') }}</a>
    @if($competition->athletes->isNotEmpty())
        <a class="btn btn-outline-dark" target="_blank" href="{{ route('admin.competitions.athletes.pdf', $competition) }}"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.competitions.list_pdf') }}</a>
    @endif
    @if($unclassified->isNotEmpty() && $categories->isNotEmpty())
        <form method="POST" action="{{ route('admin.competitions.athletes.assign', $competition) }}">
            @csrf
            <button class="btn btn-outline-warning"><i class="fas fa-wand-magic-sparkles me-1"></i> {{ __('messages.competitions.assign') }}</button>
        </form>
    @endif
</div>

{{-- Ajout manuel en tableau : une ligne par athlète, « Ajouter une ligne » ou Entrée pour la suivante.
     Les lignes passent par la même vérification que l'import (lignes en erreur → écran d'aperçu). --}}
<div class="collapse" id="addAthlete">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.competitions.athletes.confirm', $competition) }}" id="addRowsForm">
                @csrf
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-2" id="addRowsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>{{ __('messages.competitions.last_name') }} <span class="text-danger">*</span></th>
                                <th>{{ __('messages.competitions.first_name') }} <span class="text-danger">*</span></th>
                                <th style="width: 100px;">{{ __('messages.competitions.weight') }}</th>
                                <th style="width: 80px;">{{ __('messages.competitions.sex') }}</th>
                                <th style="width: 160px;">{{ __('messages.competitions.birth') }}</th>
                                <th>{{ __('messages.competitions.club') }}</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <template id="addRowTemplate">
                    <tr>
                        <td class="text-muted small row-number"></td>
                        <td><input name="rows[__i__][nom]" class="form-control form-control-sm" maxlength="100"></td>
                        <td><input name="rows[__i__][prenom]" class="form-control form-control-sm" maxlength="100"></td>
                        <td><input name="rows[__i__][poids]" class="form-control form-control-sm" inputmode="decimal" placeholder="kg"></td>
                        <td>
                            <select name="rows[__i__][sexe]" class="form-select form-select-sm">
                                <option value="">-</option><option value="M">M</option><option value="F">F</option>
                            </select>
                        </td>
                        <td><input name="rows[__i__][naissance]" class="form-control form-control-sm" placeholder="15 / 2009 / 12/03/2009"></td>
                        <td><input name="rows[__i__][club]" class="form-control form-control-sm" maxlength="150"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="{{ __('messages.delete') }}"><i class="fas fa-times"></i></button></td>
                    </tr>
                </template>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addRowButton"><i class="fas fa-plus me-1"></i> {{ __('messages.competitions.add_row') }}</button>
                    <select name="groupe" class="form-select form-select-sm w-auto">
                        <option value="">{{ __('messages.competitions.category_auto') }}</option>
                        @foreach($groups as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                    <button class="btn btn-primary btn-sm ms-auto"><i class="fas fa-save me-1"></i> {{ __('messages.competitions.save_rows') }}</button>
                </div>
            </form>
            <small class="text-muted d-block mt-2">{{ __('messages.competitions.required_hint') }} {{ __('messages.competitions.add_rows_help') }}</small>
        </div>
    </div>
</div>

{{-- Import : fichier ou copier-coller, puis écran d'aperçu --}}
<div class="collapse" id="importList">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.competitions.athletes.import', $competition) }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('messages.competitions.import_file') }}</label>
                    <input type="file" name="fichier" class="form-control" accept=".xlsx,.xls,.csv,.ods,.txt,.pdf">
                    <label class="form-label fw-semibold mt-3">{{ __('messages.competitions.import_paste') }}</label>
                    <div class="small text-muted mb-1"><i class="fas fa-camera me-1"></i> {{ __('messages.competitions.import_photo_tip') }}</div>
                    <textarea name="texte" class="form-control font-monospace" rows="5" placeholder="{{ __('messages.competitions.import_paste_placeholder') }}"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('messages.competitions.import_order') }}</label>
                    <select name="ordre" class="form-select mb-1">
                        @foreach($orders as $key => $columns)
                            <option value="{{ $key }}">{{ collect($columns)->map(fn ($c) => __('messages.competitions.col_' . $c))->implode(', ') }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mb-3">{{ __('messages.competitions.import_order_help') }}</small>
                    <label class="form-label fw-semibold">{{ __('messages.competitions.import_group') }}</label>
                    <select name="groupe" class="form-select">
                        <option value="">{{ __('messages.competitions.import_group_auto') }}</option>
                        @foreach($groups as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                    <small class="text-muted d-block mt-1">{{ __('messages.competitions.import_group_help') }}</small>
                    <small class="text-muted d-block mt-2">{{ __('messages.competitions.required_hint') }}</small>
                    <button class="btn btn-success mt-3"><i class="fas fa-eye me-1"></i> {{ __('messages.competitions.import_read') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Liste par catégorie --}}
@php
    $sections = $categories->filter(fn ($c) => isset($byCategory[$c->id]))->map(fn ($c) => ['id' => $c->id, 'label' => $categoryLabels[$c->id], 'athletes' => $byCategory[$c->id], 'locked' => $c->tirage_valide]);
    if ($unclassified->isNotEmpty()) {
        $sections->prepend(['id' => 0, 'label' => __('messages.competitions.unclassified'), 'athletes' => $unclassified, 'locked' => false, 'help' => __('messages.competitions.unclassified_help')]);
    }
    if ($toWeigh->isNotEmpty()) {
        $sections->prepend(['id' => 0, 'label' => __('messages.competitions.to_weigh'), 'athletes' => $toWeigh, 'locked' => false, 'help' => __('messages.competitions.to_weigh_help')]);
    }
@endphp
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.competitions.last_name') }}</th>
                    <th>{{ __('messages.competitions.first_name') }}</th>
                    <th class="text-center">{{ __('messages.competitions.age') }}</th>
                    <th class="text-center">{{ __('messages.competitions.sex') }}</th>
                    <th>{{ __('messages.competitions.club') }}</th>
                    <th class="text-end">{{ __('messages.competitions.weight') }}</th>
                    <th class="text-center">{{ __('messages.competitions.seed') }}</th>
                    <th class="text-end" style="width: 100px;">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sections as $section)
                    <tr class="{{ $section['id'] ? 'table-secondary' : 'table-warning' }}">
                        <td colspan="8" class="fw-bold">
                            {{ $section['label'] }} <span class="badge bg-dark ms-1">{{ $section['athletes']->count() }}</span>
                            @if($section['locked'])<span class="badge bg-success ms-1"><i class="fas fa-lock"></i> {{ __('messages.competitions.draw_status_valid') }}</span>@endif
                            @if($section['id'])
                                <a class="small ms-2" target="_blank" href="{{ route('admin.competitions.athletes.pdf', [$competition, 'category' => $section['id']]) }}"><i class="fas fa-file-pdf"></i> PDF</a>
                            @else
                                <div class="small fw-normal">{{ $section['help'] }}</div>
                            @endif
                        </td>
                    </tr>
                    @foreach($section['athletes'] as $a)
                        @php
                            $editData = [
                                'nom' => $a->nom, 'prenom' => $a->prenom, 'poids' => $a->poids !== null ? (float) $a->poids : null, 'sexe' => $a->sexe,
                                'naissance' => $a->date_naissance ? $a->date_naissance->format('d/m/Y') : $a->age,
                                'club' => $a->club, 'tete_de_serie' => $a->tete_de_serie, 'category_id' => $a->category_id,
                            ];
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $a->nom }}</td>
                            <td>{{ $a->prenom }}</td>
                            <td class="text-center">{{ $a->sportAge($year) ?? '-' }}</td>
                            <td class="text-center">{{ $a->sexe ?? '-' }}</td>
                            <td>{{ $a->club ?? '-' }}</td>
                            <td class="text-end">{{ $a->poids !== null ? \App\Models\CompetitionCategory::formatKg((float) $a->poids) : '-' }}</td>
                            <td class="text-center">{{ $a->tete_de_serie ?? '' }}</td>
                            <td class="text-end">
                                @unless($section['locked'])
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAthlete"
                                            data-action="{{ route('admin.competitions.athletes.update', [$competition, $a]) }}"
                                            data-athlete="{{ json_encode($editData) }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.competitions.athletes.destroy', [$competition, $a]) }}" onsubmit="return dojoConfirmDelete(this, @js(__('messages.competitions.delete_athlete_confirm')))">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">{{ __('messages.competitions.no_athletes') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modification d'un athlète --}}
<div class="modal fade" id="editAthlete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content" id="editAthleteForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.competitions.edit_athlete') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-2">
                @include('admin.competitions.tabs._athlete_fields', ['athlete' => null, 'withCategory' => true])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('editAthlete').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var data = JSON.parse(button.getAttribute('data-athlete'));
        var form = document.getElementById('editAthleteForm');
        form.action = button.getAttribute('data-action');
        Object.keys(data).forEach(function (key) {
            var field = form.querySelector('[name="' + key + '"]');
            if (field) { field.value = data[key] === null ? '' : data[key]; }
        });
    });

    // Saisie en tableau : ajouter / retirer des lignes, Entrée passe à une nouvelle ligne.
    (function () {
        var tbody = document.querySelector('#addRowsTable tbody');
        var template = document.getElementById('addRowTemplate');
        var counter = 0;

        function renumber() {
            tbody.querySelectorAll('.row-number').forEach(function (cell, index) { cell.textContent = index + 1; });
        }

        function addRow(focus) {
            var html = template.innerHTML.replace(/__i__/g, counter++);
            tbody.insertAdjacentHTML('beforeend', html);
            renumber();
            if (focus) { tbody.lastElementChild.querySelector('input').focus(); }
        }

        function isEmpty(row) {
            return Array.prototype.every.call(row.querySelectorAll('input, select'), function (field) { return field.value.trim() === ''; });
        }

        document.getElementById('addRowButton').addEventListener('click', function () { addRow(true); });

        tbody.addEventListener('click', function (event) {
            var button = event.target.closest('.remove-row');
            if (!button) { return; }
            button.closest('tr').remove();
            if (!tbody.children.length) { addRow(false); }
            renumber();
        });

        tbody.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') { return; }
            event.preventDefault();
            var row = event.target.closest('tr');
            if (row === tbody.lastElementChild && !isEmpty(row)) { addRow(true); }
            else if (row.nextElementSibling) { row.nextElementSibling.querySelector('input').focus(); }
        });

        // Les lignes laissées vides ne sont pas envoyées.
        document.getElementById('addRowsForm').addEventListener('submit', function () {
            Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (row) {
                if (isEmpty(row)) { row.remove(); }
            });
        });

        for (var i = 0; i < 3; i++) { addRow(false); }
    })();
</script>
