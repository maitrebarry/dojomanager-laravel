{{-- Champs d'un athlète (ajout et modification). Obligatoires : nom, prénom, poids. --}}
<div class="col-6 col-md-2">
    <label class="form-label small">{{ __('messages.competitions.last_name') }} <span class="text-danger">*</span></label>
    <input type="text" name="nom" class="form-control form-control-sm" value="{{ old('nom') }}" required maxlength="100">
</div>
<div class="col-6 col-md-2">
    <label class="form-label small">{{ __('messages.competitions.first_name') }} <span class="text-danger">*</span></label>
    <input type="text" name="prenom" class="form-control form-control-sm" value="{{ old('prenom') }}" required maxlength="100">
</div>
<div class="col-4 col-md-1">
    <label class="form-label small">{{ __('messages.competitions.weight') }}</label>
    <input type="number" name="poids" class="form-control form-control-sm" value="{{ old('poids') }}" min="10" max="200" step="0.1">
</div>
<div class="col-4 col-md-1">
    <label class="form-label small">{{ __('messages.competitions.sex') }}</label>
    <select name="sexe" class="form-select form-select-sm">
        <option value="">-</option>
        <option value="M" @selected(old('sexe') === 'M')>M</option>
        <option value="F" @selected(old('sexe') === 'F')>F</option>
    </select>
</div>
<div class="col-4 col-md-2">
    <label class="form-label small">{{ __('messages.competitions.birth') }}</label>
    <input type="text" name="naissance" class="form-control form-control-sm @error('naissance') is-invalid @enderror" value="{{ old('naissance') }}" placeholder="15 / 2009 / 12/03/2009" maxlength="20">
    @error('naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-8 col-md-2">
    <label class="form-label small">{{ __('messages.competitions.club') }}</label>
    <input type="text" name="club" class="form-control form-control-sm" value="{{ old('club') }}" maxlength="150">
</div>
<div class="col-4 col-md-1">
    <label class="form-label small">{{ __('messages.competitions.seed') }}</label>
    <input type="number" name="tete_de_serie" class="form-control form-control-sm" value="{{ old('tete_de_serie') }}" min="1" max="32">
</div>
@if(!empty($withCategory))
    <div class="col-12 col-md-6">
        <label class="form-label small">{{ __('messages.competitions.category') }}</label>
        <select name="category_id" class="form-select form-select-sm">
            <option value="">{{ __('messages.competitions.category_auto') }}</option>
            @foreach($categoryLabels as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
        </select>
    </div>
@endif
