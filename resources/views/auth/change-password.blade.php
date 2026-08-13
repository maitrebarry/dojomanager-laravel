@extends('layouts.app')

@section('title', 'Changer le mot de passe')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-key"></i> Changer le mot de passe
                </h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <strong>Erreur!</strong>
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

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="old_password" class="form-label">
                            <i class="fas fa-lock"></i> Ancien mot de passe
                        </label>
                        <input 
                            type="password" 
                            class="form-control @error('old_password') is-invalid @enderror" 
                            id="old_password" 
                            name="old_password" 
                            placeholder="Entrez votre ancien mot de passe"
                            required
                        >
                        @error('old_password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="fas fa-lock"></i> Nouveau mot de passe
                        </label>
                        <input 
                            type="password" 
                            class="form-control @error('new_password') is-invalid @enderror" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="Entrez votre nouveau mot de passe"
                            required
                        >
                        @error('new_password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="new_password_confirmation" class="form-label">
                            <i class="fas fa-lock"></i> Confirmer le nouveau mot de passe
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="new_password_confirmation" 
                            name="new_password_confirmation" 
                            placeholder="Confirmez votre nouveau mot de passe"
                            required
                        >
                    </div>

                    <div class="d-flex justify-content-between gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Changer le mot de passe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
