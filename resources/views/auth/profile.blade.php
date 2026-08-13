@extends('layouts.app')

@section('title', __('messages.profile'))

@section('layout_content')
@php
    $activeTab = in_array(($activeProfileTab ?? 'info'), ['info', 'password'], true) ? ($activeProfileTab ?? 'info') : 'info';
    if ($errors->has('old_password') || $errors->has('new_password')) {
        $activeTab = 'password';
    }
@endphp

<style>
    .profile-shell {
        max-width: 920px;
        margin: 0 auto;
    }

    .profile-hero {
        background: var(--navbar-bg);
        color: var(--navbar-text);
        border-radius: 8px 8px 0 0;
        padding: 24px;
        border: 1px solid var(--card-border);
        border-bottom: 0;
    }

    .profile-avatar {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.25);
        font-size: 26px;
        flex: 0 0 auto;
        overflow: hidden;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-card {
        border-radius: 0 0 8px 8px;
        border-top: 0;
    }

    .profile-tabs .nav-link {
        color: var(--body-text);
        border-radius: 6px 6px 0 0;
        font-weight: 600;
    }

    .profile-tabs .nav-link.active {
        color: var(--primary-color);
    }
</style>

<div class="profile-shell">
    <div class="profile-hero">
        <div class="d-flex align-items-center gap-3">
            <div class="profile-avatar">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}">
                @else
                    <i class="fas fa-user"></i>
                @endif
            </div>
            <div>
                <h4 class="mb-1">{{ $user->name }}</h4>
                <div class="small opacity-75">{{ $user->email }}</div>
            </div>
        </div>
    </div>

    <div class="card profile-card">
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <strong>{{ __('messages.error') }}!</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-times-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <ul class="nav nav-tabs profile-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'info' ? 'active' : '' }}"
                            id="info-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#info-panel"
                            type="button"
                            role="tab"
                            aria-controls="info-panel"
                            aria-selected="{{ $activeTab === 'info' ? 'true' : 'false' }}">
                        <i class="fas fa-id-card"></i> {{ __('messages.auth.personal_info') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'password' ? 'active' : '' }}"
                            id="password-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#password-panel"
                            type="button"
                            role="tab"
                            aria-controls="password-panel"
                            aria-selected="{{ $activeTab === 'password' ? 'true' : 'false' }}">
                        <i class="fas fa-key"></i> {{ __('messages.auth.change_password') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'info' ? 'show active' : '' }}" id="info-panel" role="tabpanel" aria-labelledby="info-tab">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="profile_tab" value="info">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user"></i> {{ __('messages.name') }}
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $user->name) }}"
                                       required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> {{ __('messages.email') }}
                                </label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $user->email) }}"
                                       required>
                                @error('email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> {{ __('messages.phone') }}
                                </label>
                                <input type="text"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       id="phone"
                                       name="phone"
                                       value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="avatar" class="form-label">
                                    <i class="fas fa-camera"></i> {{ __('messages.auth.profile_photo') }}
                                </label>
                                <input type="file"
                                       class="form-control @error('avatar') is-invalid @enderror"
                                       id="avatar"
                                       name="avatar"
                                       accept="image/jpeg,image/png,image/webp,image/jpg">
                                @error('avatar')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> {{ __('messages.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('messages.save') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'password' ? 'show active' : '' }}" id="password-panel" role="tabpanel" aria-labelledby="password-tab">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="profile_tab" value="password">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="old_password" class="form-label">
                                    <i class="fas fa-lock"></i> {{ __('messages.auth.old_password') }}
                                </label>
                                <input type="password"
                                       class="form-control @error('old_password') is-invalid @enderror"
                                       id="old_password"
                                       name="old_password"
                                       required>
                                @error('old_password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-lock"></i> {{ __('messages.auth.new_password') }}
                                </label>
                                <input type="password"
                                       class="form-control @error('new_password') is-invalid @enderror"
                                       id="new_password"
                                       name="new_password"
                                       required>
                                @error('new_password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="new_password_confirmation" class="form-label">
                                    <i class="fas fa-lock"></i> {{ __('messages.auth.confirm_new_password') }}
                                </label>
                                <input type="password"
                                       class="form-control"
                                       id="new_password_confirmation"
                                       name="new_password_confirmation"
                                       required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> {{ __('messages.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('messages.auth.change_password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
