@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.grade_passages.date') }} <span class="text-danger">*</span></label>
                <input type="date" name="date_session" class="form-control" value="{{ old('date_session', optional($session->date_session)->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">{{ __('messages.grade_passages.place') }} <span class="text-danger">*</span></label>
                <input type="text" name="lieu" class="form-control" value="{{ old('lieu', $session->lieu) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.grades.type') }} <span class="text-danger">*</span></label>
                <select name="type_grade" class="form-select" required>
                    <option value="KEUP" {{ old('type_grade', $session->type_grade) === 'KEUP' ? 'selected' : '' }}>KEUP</option>
                    <option value="DAN" {{ old('type_grade', $session->type_grade) === 'DAN' ? 'selected' : '' }}>DAN</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.grade_passages.fee') }} (FCFA)</label>
                <input type="number" step="0.01" min="0" name="frais_participation" class="form-control" value="{{ old('frais_participation', $session->frais_participation) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.grade_passages.notation') }}</label>
                <select name="type_notation" class="form-select">
                    <option value="NOTE" {{ old('type_notation', $session->type_notation) === 'NOTE' ? 'selected' : '' }}>{{ __('messages.grade_passages.notation_note') }}</option>
                    <option value="ADMIS" {{ old('type_notation', $session->type_notation) === 'ADMIS' ? 'selected' : '' }}>{{ __('messages.grade_passages.notation_admis') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.federations.title') }}</label>
                <select name="federation_id" class="form-select">
                    <option value="">-</option>
                    @foreach($federations as $f)
                        <option value="{{ $f->id }}" {{ (string) old('federation_id', $session->federation_id) === (string) $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('messages.grade_passages.announcement') }}</label>
                <textarea name="annonce" class="form-control" rows="2">{{ old('annonce', $session->annonce) }}</textarea>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.grade-passages.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
