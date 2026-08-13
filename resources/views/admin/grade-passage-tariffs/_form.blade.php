@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.grades.type') }} <span class="text-danger">*</span></label>
                <select name="type_grade" class="form-select" required>
                    <option value="KEUP" {{ old('type_grade', $tariff->type_grade) === 'KEUP' ? 'selected' : '' }}>KEUP</option>
                    <option value="DAN" {{ old('type_grade', $tariff->type_grade) === 'DAN' ? 'selected' : '' }}>DAN</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">{{ __('messages.grade_passage_tariffs.label') }} <span class="text-danger">*</span></label>
                <input type="text" name="tarif_label" class="form-control" value="{{ old('tarif_label', $tariff->tarif_label) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.cotisations.amount') }} (FCFA) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="montant" class="form-control" value="{{ old('montant', $tariff->montant) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.federations.title') }}</label>
                <select name="federation_id" class="form-select">
                    <option value="">-</option>
                    @foreach($federations as $f)
                        <option value="{{ $f->id }}" {{ (string) old('federation_id', $tariff->federation_id) === (string) $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.grade') }}</label>
                <select name="grade_id" class="form-select">
                    <option value="">-</option>
                    @foreach($grades as $g)
                        <option value="{{ $g->id }}" {{ (string) old('grade_id', $tariff->grade_id) === (string) $g->id ? 'selected' : '' }}>{{ $g->nom_grade }} ({{ $g->type_grade }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="active" value="0">
                    <input class="form-check-input" type="checkbox" name="active" value="1" id="activeSwitch" {{ old('active', $tariff->active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="activeSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.grade-passage-tariffs.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
