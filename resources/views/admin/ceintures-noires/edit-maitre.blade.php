@extends('layouts.admin')

@section('title', __('messages.ceintures_noires.edit_manager'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.ceintures-noires.index') }}">{{ __('messages.ceintures_noires.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $maitre->name }}</li>
@endsection

@section('content')
<form action="{{ route('admin.ceintures-noires.maitres.update', $maitre) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0"><i class="fas fa-id-card me-2"></i> {{ $maitre->name }} — {{ $maitre->grade?->nom_grade }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.birth_date') }}</label>
                    <input type="date" name="date_naissance" class="form-control" value="{{ old('date_naissance', optional($maitre->date_naissance)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.birth_place') }}</label>
                    <input type="text" name="date_lieu_naissance" class="form-control" value="{{ old('date_lieu_naissance', $maitre->date_lieu_naissance) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.disciples.matricule') }}</label>
                    <input type="text" class="form-control" value="{{ $maitre->matricule ?: __('messages.ceintures_noires.matricule_pending') }}" disabled readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('messages.address') }}</label>
                    <input type="text" name="adresse" class="form-control" value="{{ old('adresse', $maitre->adresse) }}">
                </div>
            </div>
        </div>
        <div class="mt-4 mb-4 mx-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <a href="{{ route('admin.ceintures-noires.index') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
        </div>
    </div>
</form>
@endsection
