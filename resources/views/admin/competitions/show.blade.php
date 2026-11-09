@extends('layouts.admin')

@section('title', $competition->nom)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.competitions.index') }}">{{ __('messages.competitions.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $competition->nom }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.competitions.edit', $competition) }}" class="btn btn-outline-primary shadow-sm"><i class="fas fa-edit me-1"></i> {{ __('messages.edit') }}</a>
    <a href="{{ route('admin.competitions.index') }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.back') }}</a>
@endsection

@php
    $year = $competition->referenceYear();
    $byCategory = $competition->athletes->groupBy(fn ($a) => $a->category_id ?? 0);
    // Sans catégorie : soit pas encore pesés, soit pesés mais hors de toute catégorie.
    $toWeigh = ($byCategory[0] ?? collect())->whereNull('poids')->values();
    $unclassified = ($byCategory[0] ?? collect())->whereNotNull('poids')->values();
    $categories = $competition->categories;
@endphp

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between gap-2">
        <div>
            <h5 class="fw-bold mb-1">{{ $competition->nom }}</h5>
            <div class="text-muted">
                {{ optional($competition->date_competition)->format('d/m/Y') ?? '-' }}
                @if($competition->lieu) · {{ $competition->lieu }} @endif
                @if($competition->type) · <span class="badge bg-secondary">{{ $competition->type }}</span> @endif
            </div>
        </div>
        <div class="d-flex gap-3 text-center">
            <div><div class="fs-4 fw-bold">{{ $competition->athletes->count() }}</div><small class="text-muted">{{ __('messages.competitions.athletes') }}</small></div>
            <div><div class="fs-4 fw-bold">{{ $categories->count() }}</div><small class="text-muted">{{ __('messages.competitions.categories') }}</small></div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link {{ $tab === 'athletes' ? 'active' : '' }}" href="{{ route('admin.competitions.show', [$competition, 'tab' => 'athletes']) }}"><i class="fas fa-users me-1"></i> {{ __('messages.competitions.tab_athletes') }}</a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'pesee' ? 'active' : '' }}" href="{{ route('admin.competitions.show', [$competition, 'tab' => 'pesee']) }}"><i class="fas fa-scale-unbalanced me-1"></i> {{ __('messages.competitions.tab_weigh') }} @if($toWeigh->isNotEmpty())<span class="badge bg-warning text-dark">{{ $toWeigh->count() }}</span>@endif</a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'categories' ? 'active' : '' }}" href="{{ route('admin.competitions.show', [$competition, 'tab' => 'categories']) }}"><i class="fas fa-weight-scale me-1"></i> {{ __('messages.competitions.tab_categories') }}</a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'tirage' ? 'active' : '' }}" href="{{ route('admin.competitions.show', [$competition, 'tab' => 'tirage']) }}"><i class="fas fa-sitemap me-1"></i> {{ __('messages.competitions.tab_draw') }}</a></li>
</ul>

@if($tab === 'athletes')
    @include('admin.competitions.tabs.athletes')
@elseif($tab === 'pesee')
    @include('admin.competitions.tabs.pesee')
@elseif($tab === 'categories')
    @include('admin.competitions.tabs.categories')
@else
    @include('admin.competitions.tabs.tirage')
@endif
@endsection
