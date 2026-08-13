@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.maitres.name') }} <span class="text-danger">*</span></label>
                <input type="text" name="nom_complet" class="form-control" value="{{ old('nom_complet', $maitre->nom_complet) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.maitres.grade') }}</label>
                <input type="text" name="grade" class="form-control" value="{{ old('grade', $maitre->grade) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.phone') }}</label>
                <input type="text" name="telephone" class="form-control" value="{{ old('telephone', $maitre->telephone) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.email') }}</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $maitre->email) }}">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.maitres.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
