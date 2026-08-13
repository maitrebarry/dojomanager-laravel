@extends('layouts.admin')

@section('title', __('messages.ligues.edit'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.ligues.index') }}">{{ __('messages.ligues.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $ligue->nom }}</li>
@endsection

@section('content')
<form action="{{ route('admin.ligues.update', $ligue) }}" method="POST">
    @include('admin.ligues._form', ['mode' => 'edit'])
</form>
@endsection
