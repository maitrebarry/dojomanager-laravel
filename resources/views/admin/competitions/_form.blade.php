@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">{{ __('messages.competitions.name') }} <span class="text-danger">*</span></label>
                <input type="text" name="nom" class="form-control" value="{{ old('nom', $competition->nom) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.competitions.date') }}</label>
                <input type="date" name="date_competition" class="form-control" value="{{ old('date_competition', optional($competition->date_competition)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.competitions.place') }}</label>
                <input type="text" name="lieu" class="form-control" value="{{ old('lieu', $competition->lieu) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.competitions.type') }}</label>
                <input type="text" name="type" class="form-control" value="{{ old('type', $competition->type) }}" placeholder="{{ __('messages.competitions.type_placeholder') }}">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.competitions.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
