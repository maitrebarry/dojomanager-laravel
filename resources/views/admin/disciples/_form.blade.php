@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="fas fa-user me-2"></i> {{ __('messages.disciples.title') }}</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.first_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" class="form-control" value="{{ old('prenom', $disciple->prenom) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.last_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" value="{{ old('nom', $disciple->nom) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.gender') }}</label>
                        <select name="sexe" class="form-select">
                            <option value="">-</option>
                            <option value="M" {{ old('sexe', $disciple->sexe) === 'M' ? 'selected' : '' }}>{{ __('messages.male') }}</option>
                            <option value="F" {{ old('sexe', $disciple->sexe) === 'F' ? 'selected' : '' }}>{{ __('messages.female') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.birth_date') }}</label>
                        <input type="date" name="date_naissance" class="form-control" value="{{ old('date_naissance', optional($disciple->date_naissance)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.phone') }}</label>
                        <input type="text" name="telephone" class="form-control" value="{{ old('telephone', $disciple->telephone) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.birth_place') }}</label>
                        <input type="text" name="date_lieu_naissance" class="form-control" value="{{ old('date_lieu_naissance', $disciple->date_lieu_naissance) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.disciples.matricule') }}</label>
                        <input type="text" name="nmle" class="form-control" value="{{ old('nmle', $disciple->nmle) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('messages.address') }}</label>
                        <input type="text" name="adresse" class="form-control" value="{{ old('adresse', $disciple->adresse) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i> {{ __('messages.grade') }} & {{ __('messages.salle') }}</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.salle') }} <span class="text-danger">*</span></label>
                        <select name="salle_id" class="form-select" required>
                            <option value="">-</option>
                            @foreach($salles as $salle)
                                <option value="{{ $salle->id }}" {{ (string) old('salle_id', $disciple->salle_id) === (string) $salle->id ? 'selected' : '' }}>{{ $salle->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.grade') }}</label>
                        <select name="grade_id" class="form-select">
                            <option value="">-</option>
                            @foreach($grades as $grade)
                                <option value="{{ $grade->id }}" {{ (string) old('grade_id', $disciple->grade_id) === (string) $grade->id ? 'selected' : '' }}>{{ $grade->nom_grade }} ({{ $grade->ceinture }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.registration_date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="date_inscription" class="form-control" value="{{ old('date_inscription', optional($disciple->date_inscription)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.grade_date') }}</label>
                        <input type="date" name="date_obtention_grade" class="form-control" value="{{ old('date_obtention_grade', optional($disciple->date_obtention_grade)->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="fas fa-image me-2"></i> {{ __('messages.photo') }}</h6></div>
            <div class="card-body text-center">
                @if($disciple->photo_url)
                    <img src="{{ $disciple->photo_url }}" alt="Photo" class="rounded mb-3" style="width: 140px; height: 140px; object-fit: cover;">
                @else
                    <div class="rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 140px; height: 140px;">
                        <i class="fas fa-user text-muted" style="font-size: 48px;"></i>
                    </div>
                @endif
                <input type="file" name="photo" accept="image/*" class="form-control">
                <small class="text-muted d-block mt-2">JPG, PNG, WEBP · max 4 Mo</small>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body d-grid gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
                <a href="{{ route('admin.disciples.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
            </div>
        </div>
    </div>
</div>
