{{-- Onglet Catégories : catégories standard WT, catégories libres, liste. --}}
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">{{ __('messages.competitions.presets') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.competitions.categories.presets', $competition) }}">
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
                                            @if(isset($groups[$key]))
                                                <i class="fas fa-check text-success" title="Déjà ajoutées"></i>
                                            @else
                                                <input type="checkbox" class="form-check-input" name="groupes[]" value="{{ $key }}">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <small class="text-muted d-block mb-2">{{ __('messages.competitions.presets_help') }}</small>
                    <button class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> {{ __('messages.competitions.presets_add') }}</button>
                </form>
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

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.competitions.category') }}</th>
                    <th>{{ __('messages.competitions.age') }}</th>
                    <th class="text-center">{{ __('messages.competitions.athletes_count') }}</th>
                    <th>{{ __('messages.competitions.tab_draw') }}</th>
                    <th class="text-end" style="width: 60px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories->sortBy(fn ($c) => [$c->groupe, $c->sexe, $c->poids_max === null ? 999 : (float) $c->poids_max]) as $c)
                    <tr>
                        <td class="fw-semibold">{{ $categoryLabels[$c->id] }}</td>
                        <td>{{ $c->ageLabel() ?: '-' }}</td>
                        <td class="text-center">{{ $c->athletes_count }}</td>
                        <td>@include('admin.competitions.tabs._draw_status', ['category' => $c])</td>
                        <td class="text-end">
                            @unless($c->tirage_valide)
                                <form method="POST" action="{{ route('admin.competitions.categories.destroy', [$competition, $c]) }}" onsubmit="return dojoConfirmDelete(this, @js(__('messages.competitions.delete_category_confirm')))">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">{{ __('messages.competitions.no_categories') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
