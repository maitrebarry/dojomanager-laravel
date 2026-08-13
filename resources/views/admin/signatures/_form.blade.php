@csrf
@if(($mode ?? 'create') === 'edit')
    @method('PUT')
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.signatures.master') }} <span class="text-danger">*</span></label>
                <input type="text" name="master_name" class="form-control" value="{{ old('master_name', $signature->master_name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.signatures.grade') }}</label>
                <input type="text" name="master_grade" class="form-control" value="{{ old('master_grade', $signature->master_grade) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.signatures.role') }}</label>
                <input type="text" name="role" class="form-control" value="{{ old('role', $signature->role) }}" placeholder="{{ __('messages.signatures.role_placeholder') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.federations.title') }}</label>
                <select name="federation_id" class="form-select">
                    <option value="">-</option>
                    @foreach($federations as $f)<option value="{{ $f->id }}" {{ (string) old('federation_id', $signature->federation_id) === (string) $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.ligues.title') }}</label>
                <select name="ligue_id" class="form-select">
                    <option value="">-</option>
                    @foreach($ligues as $l)<option value="{{ $l->id }}" {{ (string) old('ligue_id', $signature->ligue_id) === (string) $l->id ? 'selected' : '' }}>{{ $l->nom }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.salle') }}</label>
                <select name="salle_id" class="form-select">
                    <option value="">-</option>
                    @foreach($salles as $s)<option value="{{ $s->id }}" {{ (string) old('salle_id', $signature->salle_id) === (string) $s->id ? 'selected' : '' }}>{{ $s->nom }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.signatures.signature') }} ({{ __('messages.signatures.image') }})</label>
                <input type="file" name="signature" accept="image/*" class="form-control">
                @if($signature->signature_data)
                    <img src="{{ $signature->signature_data }}" alt="signature" class="mt-2" style="height: 60px; max-width: 200px; object-fit: contain; border: 1px solid var(--card-border); padding: 4px;">
                @endif
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.signatures.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</div>
