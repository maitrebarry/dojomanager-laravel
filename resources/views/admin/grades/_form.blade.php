@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.grades.level') }} <span class="text-danger">*</span></label>
                <input type="number" min="0" name="niveau" class="form-control" value="{{ old('niveau', $grade->niveau) }}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">{{ __('messages.grades.name') }} <span class="text-danger">*</span></label>
                <input type="text" name="nom_grade" class="form-control" value="{{ old('nom_grade', $grade->nom_grade) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.grades.belt') }} <span class="text-danger">*</span></label>
                <input type="text" name="ceinture" class="form-control" value="{{ old('ceinture', $grade->ceinture) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.grades.type') }} <span class="text-danger">*</span></label>
                <select name="type_grade" class="form-select" required>
                    <option value="KEUP" {{ old('type_grade', $grade->type_grade) === 'KEUP' ? 'selected' : '' }}>KEUP</option>
                    <option value="DAN" {{ old('type_grade', $grade->type_grade) === 'DAN' ? 'selected' : '' }}>DAN</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.grades.federation') }} <span class="text-danger">*</span></label>
                <select name="federation_id" class="form-select" required>
                    <option value="">-</option>
                    @foreach($federations as $federation)
                        <option value="{{ $federation->id }}" {{ (string) old('federation_id', $grade->federation_id) === (string) $federation->id ? 'selected' : '' }}>{{ $federation->nom }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.grades.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
