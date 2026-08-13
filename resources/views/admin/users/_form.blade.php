@php
    $isEdit = $user !== null;
    $showCancel = $showCancel ?? true;
    $showEmail = $showEmail ?? true;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">{{ __('messages.full_name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if($showEmail)
        <div class="col-md-6">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email ?? '') }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @else
        <div class="col-md-6">
            <label for="phone" class="form-label">{{ __('messages.phone') }}</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif

    @if($showEmail)
        <div class="col-md-6">
            <label for="phone" class="form-label">{{ __('messages.phone') }}</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif

    <div class="col-md-6">
        <label for="role" class="form-label">{{ __('messages.role') }} <span class="text-danger">*</span></label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
            <option value="">{{ __('messages.users.select') }}</option>
            @foreach($roles ?? collect(\App\Shared\Enums\UserRole::assignableBy(Auth::user()))->mapWithKeys(fn($role) => [$role->value => $role->label()])->toArray() as $roleValue => $roleLabel)
                @if(Auth::user()->isSuperAdmin() || $roleValue !== 'superadmin')
                    <option value="{{ $roleValue }}" {{ old('role', $user->role ?? '') === $roleValue ? 'selected' : '' }}>{{ $roleLabel }}</option>
                @endif
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="status" class="form-label">{{ __('messages.status') }} <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach($statuses ?? collect(\App\Shared\Enums\UserStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray() as $statusValue => $statusLabel)
                <option value="{{ $statusValue }}" {{ old('status', $user->status ?? \App\Shared\Enums\UserStatus::ACTIVE->value) === $statusValue ? 'selected' : '' }}>{{ $statusLabel }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">{{ $isEdit ? __('messages.users.new_password') : __('messages.auth.password') }} @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
        <div class="input-group">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" {{ $isEdit ? '' : 'required' }}>
            <button class="btn btn-outline-secondary" type="button" data-password-toggle="password">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($isEdit)<div class="form-text">{{ __('messages.users.keep_password_empty') }}</div>@endif
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">{{ __('messages.users.confirm_password') }} @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
        <div class="input-group">
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" {{ $isEdit ? '' : 'required' }}>
            <button class="btn btn-outline-secondary" type="button" data-password-toggle="password_confirmation">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    @if($showCancel)
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('messages.cancel') }}</a>
    @else
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
    @endif
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> {{ __('messages.save') }}
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var passwordInput = document.getElementById(button.dataset.passwordToggle);
                if (!passwordInput) {
                    return;
                }
                var isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                var icon = button.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye', !isHidden);
                    icon.classList.toggle('fa-eye-slash', isHidden);
                }
            });
        });
    });
</script>
