@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.salles.name') }} <span class="text-danger">*</span></label>
                <input type="text" name="nom" class="form-control" value="{{ old('nom', $salle->nom) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.phone') }}</label>
                <input type="text" name="telephone" class="form-control" value="{{ old('telephone', $salle->telephone) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.salles.ligue') }} <span class="text-danger">*</span></label>
                <select name="ligue_id" class="form-select" required>
                    <option value="">-</option>
                    @foreach($ligues as $ligue)
                        <option value="{{ $ligue->id }}" {{ (string) old('ligue_id', $salle->ligue_id) === (string) $ligue->id ? 'selected' : '' }}>{{ $ligue->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.salles.maitre') }}</label>
                <select name="maitre_id" class="form-select">
                    <option value="">{{ __('messages.salles.no_maitre') }}</option>
                    @foreach($maitres as $maitre)
                        <option value="{{ $maitre->id }}" {{ (string) old('maitre_id', $salle->maitre_id) === (string) $maitre->id ? 'selected' : '' }}>{{ $maitre->nom_complet }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.salles.monthly_fee') }} (FCFA)</label>
                <input type="number" step="0.01" min="0" name="mensualite" class="form-control" value="{{ old('mensualite', $salle->mensualite) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.address') }}</label>
                <input type="text" name="adresse" class="form-control" value="{{ old('adresse', $salle->adresse) }}">
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="active" value="0">
                    <input class="form-check-input" type="checkbox" name="active" value="1" id="activeSwitch" {{ old('active', $salle->active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="activeSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.salles.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
