@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">{{ __('messages.federations.name') }} <span class="text-danger">*</span></label>
                <input type="text" name="nom" class="form-control" value="{{ old('nom', $federation->nom) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.federations.acronym') }}</label>
                <input type="text" name="sigle" class="form-control" value="{{ old('sigle', $federation->sigle) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.phone') }}</label>
                <input type="text" name="telephone" class="form-control" value="{{ old('telephone', $federation->telephone) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.email') }}</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $federation->email) }}">
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('messages.address') }}</label>
                <input type="text" name="adresse" class="form-control" value="{{ old('adresse', $federation->adresse) }}">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.federations.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
