@extends('layouts.admin')

@section('title', __('messages.salles.edit'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.salles.index') }}">{{ __('messages.salles.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $salle->nom }}</li>
@endsection

@section('content')
<form action="{{ route('admin.salles.update', $salle) }}" method="POST">
    @include('admin.salles._form', ['mode' => 'edit'])
</form>
@endsection
