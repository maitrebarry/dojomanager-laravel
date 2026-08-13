@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.first_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="prenom" class="form-control" value="{{ old('prenom', $ceintureNoire->prenom) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.last_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="nom" class="form-control" value="{{ old('nom', $ceintureNoire->nom) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.gender') }}</label>
                <select name="sexe" class="form-select">
                    <option value="">-</option>
                    <option value="M" {{ old('sexe', $ceintureNoire->sexe) === 'M' ? 'selected' : '' }}>{{ __('messages.male') }}</option>
                    <option value="F" {{ old('sexe', $ceintureNoire->sexe) === 'F' ? 'selected' : '' }}>{{ __('messages.female') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.grade') }} (DAN) <span class="text-danger">*</span></label>
                <select name="grade_id" class="form-select" required>
                    <option value="">-</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}" {{ (string) old('grade_id', $ceintureNoire->grade_id) === (string) $grade->id ? 'selected' : '' }}>{{ $grade->nom_grade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.grade_date') }} <span class="text-danger">*</span></label>
                <input type="date" name="date_obtention_grade" class="form-control" value="{{ old('date_obtention_grade', optional($ceintureNoire->date_obtention_grade)->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.federations.title') }} <span class="text-danger">*</span></label>
                <select name="federation_id" class="form-select" required>
                    <option value="">-</option>
                    @foreach($federations as $f)
                        <option value="{{ $f->id }}" {{ (string) old('federation_id', $ceintureNoire->federation_id) === (string) $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.ligues.title') }}</label>
                <select name="ligue_id" class="form-select">
                    <option value="">-</option>
                    @foreach($ligues as $l)
                        <option value="{{ $l->id }}" {{ (string) old('ligue_id', $ceintureNoire->ligue_id) === (string) $l->id ? 'selected' : '' }}>{{ $l->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.salle') }}</label>
                <select name="salle_id" class="form-select">
                    <option value="">-</option>
                    @foreach($salles as $s)
                        <option value="{{ $s->id }}" {{ (string) old('salle_id', $ceintureNoire->salle_id) === (string) $s->id ? 'selected' : '' }}>{{ $s->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.photo') }}</label>
                <input type="file" name="photo" accept="image/*" class="form-control">
                @if($ceintureNoire->photo_url ?? false)
                    <img src="{{ $ceintureNoire->photo_url }}" class="rounded mt-2" style="width: 80px; height: 80px; object-fit: cover;">
                @endif
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.ceintures-noires.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
