@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Dashboard Vide -->
<div class="text-center py-5">
    <i class="fas fa-inbox" style="font-size: 64px; color: #ccc;"></i>
    <p class="mt-4 text-muted">Bienvenue dans Licence Régionale TKD</p>
</div>
@endsection
