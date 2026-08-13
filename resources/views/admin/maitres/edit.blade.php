@extends('layouts.admin')

@section('title', __('messages.maitres.edit'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.maitres.index') }}">{{ __('messages.maitres.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $maitre->nom_complet }}</li>
@endsection

@section('content')
<form action="{{ route('admin.maitres.update', $maitre) }}" method="POST">
    @include('admin.maitres._form', ['mode' => 'edit'])
</form>
@endsection
