@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.ligues.name') }} <span class="text-danger">*</span></label>
                <input type="text" name="nom" class="form-control" value="{{ old('nom', $ligue->nom) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.ligues.region') }}</label>
                <input type="text" name="region" class="form-control" value="{{ old('region', $ligue->region) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.ligues.federation') }} <span class="text-danger">*</span></label>
                <select name="federation_id" class="form-select" required>
                    <option value="">-</option>
                    @foreach($federations as $federation)
                        <option value="{{ $federation->id }}" {{ (string) old('federation_id', $ligue->federation_id) === (string) $federation->id ? 'selected' : '' }}>{{ $federation->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="active" value="0">
                    <input class="form-check-input" type="checkbox" name="active" value="1" id="activeSwitch" {{ old('active', $ligue->active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="activeSwitch">{{ __('messages.active') }}</label>
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.ligues.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
