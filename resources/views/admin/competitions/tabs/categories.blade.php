{{-- Onglet Catégories : catégories standard WT, catégories libres, liste. --}}
@php
    // Données de chaque groupe pour la fenêtre « Modifier le groupe » : « -33 -37 … +65 ».
    $groupData = $categories->groupBy(fn ($c) => $c->groupKey())->map(function ($list) {
        $sorted = $list->sortBy(fn ($c) => $c->poids_max === null ? PHP_FLOAT_MAX : (float) $c->poids_max)->values();
        $limits = $sorted->whereNotNull('poids_max')->map(fn ($c) => '-' . \App\Models\CompetitionCategory::formatKg((float) $c->poids_max));
        $last = $sorted->whereNotNull('poids_max')->last();
        if ($sorted->contains(fn ($c) => $c->poids_max === null)) {
            $limits->push('+' . ($last ? \App\Models\CompetitionCategory::formatKg((float) $last->poids_max) : ''));
        }
        $first = $sorted->first();

        return ['name' => $first->groupe, 'label' => $first->groupLabel(), 'age_min' => $first->age_min, 'age_max' => $first->age_max,
            'weights' => $limits->implode(' '), 'locked' => $list->contains('tirage_valide', true)];
    });
@endphp
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">{{ __('messages.competitions.presets_title', ['discipline' => $profile['label']]) }}</div>
            <div class="card-body">
                @if(empty($presets))
                    <p class="text-muted mb-0">{{ __('messages.competitions.no_presets', ['discipline' => $profile['label']]) }}</p>
                @else
                <form method="POST" action="{{ route('admin.competitions.categories.presets', $competition) }}" id="presetsForm">
                    @csrf
                    <table class="table table-sm mb-2">
                        <thead><tr><th></th><th class="text-center">{{ __('messages.competitions.male') }}</th><th class="text-center">{{ __('messages.competitions.female') }}</th></tr></thead>
                        <tbody>
                            @foreach($presets as $groupe => $preset)
                                <tr>
                                    <td>{{ $groupe }} <small class="text-muted">({{ $preset['age_min'] }}{{ $preset['age_max'] ? '-' . $preset['age_max'] : '+' }} ans)</small></td>
                                    @foreach(['M', 'F'] as $sexe)
                                        @php $key = $groupe . '|' . $sexe; @endphp
                                        <td class="text-center">
                                            @if(!empty($preset[$sexe]))
                                                <input type="checkbox" class="form-check-input" name="groupes[]" value="{{ $key }}" @checked(isset($groups[$key]))
                                                    @if(isset($groups[$key])) data-existing="{{ $groups[$key] }}" @if($groupData[$key]['locked'] ?? false) onclick="return false" title="{{ __('messages.competitions.group_locked') }}" @endif @endif>
                                                @if(isset($groups[$key]))@include('admin.competitions.tabs._group_edit_button', ['key' => $key, 'class' => 'py-0 px-1 ms-1'])@endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <small class="text-muted d-block mb-2">{{ __('messages.competitions.presets_help') }}</small>
                    <button class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> {{ __('messages.competitions.groups_save') }}</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">{{ __('messages.competitions.custom_category') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.competitions.categories.store', $competition) }}" class="row g-2">
                    @csrf
                    <div class="col-6">
                        <label class="form-label small">{{ __('messages.competitions.group') }} <span class="text-danger">*</span></label>
                        <input type="text" name="groupe" class="form-control form-control-sm" placeholder="{{ __('messages.competitions.group_placeholder') }}" required maxlength="80" list="groupNames">
                        <datalist id="groupNames">@foreach(collect($groups)->keys()->map(fn ($k) => explode('|', $k)[0])->unique() as $g)<option value="{{ $g }}">@endforeach</datalist>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">{{ __('messages.competitions.sex') }}</label>
                        <select name="sexe" class="form-select form-select-sm">
                            <option value="M">{{ __('messages.competitions.male') }}</option>
                            <option value="F">{{ __('messages.competitions.female') }}</option>
                            <option value="">{{ __('messages.competitions.mixed') }}</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label small">{{ __('messages.competitions.age_min') }}</label>
                        <input type="number" name="age_min" class="form-control form-control-sm" min="3" max="99">
                    </div>
                    <div class="col-4">
                        <label class="form-label small">{{ __('messages.competitions.age_max') }}</label>
                        <input type="number" name="age_max" class="form-control form-control-sm" min="3" max="99">
                    </div>
                    <div class="col-4">
                        <label class="form-label small">{{ __('messages.competitions.weight_max') }}</label>
                        <input type="number" name="poids_max" class="form-control form-control-sm" min="10" max="200" step="0.1">
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block mb-2">{{ __('messages.competitions.custom_category_help') }}</small>
                        <button class="btn btn-outline-primary btn-sm"><i class="fas fa-plus me-1"></i> {{ __('messages.competitions.custom_category') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Durée des manches par groupe d'âge (ex. cadets 1:00, seniors 1:30) ; vide = durée de la compétition. --}}
@if($groups)
    @php $chrono = $competition->chronoSettings(); @endphp
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold"><i class="fas fa-stopwatch me-1"></i> {{ __('messages.competitions.group_durations') }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.competitions.categories.durations', $competition) }}" class="row g-2 align-items-end">
                @csrf
                @foreach($groups as $key => $label)
                    @php $current = $categories->first(fn ($c) => $c->groupKey() === $key)?->manche_s; @endphp
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small mb-1">{{ $label }}</label>
                        <input type="text" name="durees[{{ $key }}]" class="form-control form-control-sm" inputmode="numeric" value="{{ \App\Support\Duration::format($current) }}" placeholder="{{ \App\Support\Duration::format($chrono['round']) }}">
                    </div>
                @endforeach
                <div class="col-12 col-md-auto"><button class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button></div>
            </form>
            <small class="text-muted d-block mt-2">{{ __('messages.competitions.group_durations_help', ['round' => \App\Support\Duration::format($chrono['round']), 'rest' => \App\Support\Duration::format($chrono['rest']), 'rounds' => $chrono['rounds']]) }}</small>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.competitions.category') }}</th>
                    <th>{{ __('messages.competitions.age') }}</th>
                    <th class="text-center">{{ __('messages.competitions.round_time') }}</th>
                    <th class="text-center">{{ __('messages.competitions.athletes_count') }}</th>
                    <th>{{ __('messages.competitions.tab_draw') }}</th>
                    <th class="text-end" style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories->sortBy(fn ($c) => [$c->groupe, $c->sexe, $c->poids_max === null ? 999 : (float) $c->poids_max]) as $c)
                    <tr>
                        <td class="fw-semibold">{{ $categoryLabels[$c->id] }}</td>
                        <td>{{ $c->ageLabel() ?: '-' }}</td>
                        <td class="text-center {{ $c->manche_s ? 'fw-semibold' : 'text-muted' }}">{{ \App\Support\Duration::format($c->roundSeconds()) }}</td>
                        <td class="text-center">{{ $c->athletes_count }}</td>
                        <td>@include('admin.competitions.tabs._draw_status', ['category' => $c])</td>
                        <td class="text-end text-nowrap">
                            @include('admin.competitions.tabs._group_edit_button', ['key' => $c->groupKey()])
                            @unless($c->tirage_valide)
                                <form method="POST" class="d-inline" action="{{ route('admin.competitions.categories.destroy', [$competition, $c]) }}" onsubmit="return dojoConfirmDelete(this, @js(__('messages.competitions.delete_category_confirm')))">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.competitions.no_categories') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modifier un groupe : nom, âges, limites de poids. --}}
<div class="modal fade" id="editGroup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.competitions.categories.group', $competition) }}" class="modal-content">
            @csrf
            <input type="hidden" name="key" data-g="key">
            <div class="modal-header">
                <h5 class="modal-title" data-g="title">{{ __('messages.competitions.group_edit') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-2">
                <div class="col-12">
                    <label class="form-label small">{{ __('messages.competitions.group_name') }}</label>
                    <input type="text" name="groupe" class="form-control" data-g="name" required maxlength="80">
                </div>
                <div class="col-6">
                    <label class="form-label small">{{ __('messages.competitions.age_min') }}</label>
                    <input type="number" name="age_min" class="form-control" data-g="age_min" min="3" max="99">
                </div>
                <div class="col-6">
                    <label class="form-label small">{{ __('messages.competitions.age_max') }}</label>
                    <input type="number" name="age_max" class="form-control" data-g="age_max" min="3" max="99">
                </div>
                <div class="col-12">
                    <label class="form-label small">{{ __('messages.competitions.group_weights') }}</label>
                    <input type="text" name="poids" class="form-control font-monospace" data-g="weights" required>
                    <small class="text-muted">{{ __('messages.competitions.group_weights_help') }}</small>
                </div>
                <div class="col-12"><div class="alert alert-info small mb-0">{{ __('messages.competitions.group_edit_help') }}</div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>
<script>
    // Groupes déjà présents et décochés : confirmation avant de les retirer.
    document.getElementById('presetsForm')?.addEventListener('submit', function (event) {
        var removed = Array.from(this.querySelectorAll('input[data-existing]:not(:checked)')).map(function (i) { return i.dataset.existing; });
        if (!removed.length) { return; }
        event.preventDefault(); event.stopImmediatePropagation();
        var form = this;
        dojoConfirmDelete(form, @js(__('messages.competitions.groups_remove_confirm', ['groups' => '__G__'])).replace('__G__', removed.join(', ')));
    }, true);
    document.addEventListener('click', function (event) {
        var button = event.target.closest && event.target.closest('.js-group-edit');
        if (!button) { return; }
        var g = JSON.parse(button.dataset.group), modal = document.getElementById('editGroup');
        var field = function (n) { return modal.querySelector('[data-g="' + n + '"]'); };
        field('key').value = button.dataset.key;
        field('title').textContent = @js(__('messages.competitions.group_edit_title', ['group' => '__G__'])).replace('__G__', g.label);
        field('name').value = g.name;
        field('age_min').value = g.age_min ?? '';
        field('age_max').value = g.age_max ?? '';
        field('weights').value = g.weights;
        bootstrap.Modal.getOrCreateInstance(modal).show();
    });
</script>
