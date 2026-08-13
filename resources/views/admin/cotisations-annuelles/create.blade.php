@extends('layouts.admin')

@section('title', __('messages.cotisations_annuelles.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.cotisations-annuelles.index') }}">{{ __('messages.cotisations_annuelles.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.cotisations_annuelles.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.cotisations-annuelles.store') }}" method="POST">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="text-muted">{{ __('messages.cotisations_annuelles.create_help') }}</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.cotisations.year') }} <span class="text-danger">*</span></label>
                    <input type="number" name="annee" class="form-control" value="{{ old('annee', $cotisation->annee) }}" min="2000" max="2100" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.cotisations.amount') }} (FCFA) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="montant" class="form-control" value="{{ old('montant', $cotisation->montant) }}" required>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-bolt me-1"></i> {{ __('messages.cotisations_annuelles.launch') }}</button>
                <a href="{{ route('admin.cotisations-annuelles.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
@endsection
