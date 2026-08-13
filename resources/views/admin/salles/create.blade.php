@extends('layouts.admin')

@section('title', __('messages.salles.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.salles.index') }}">{{ __('messages.salles.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.salles.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.salles.store') }}" method="POST">
    @include('admin.salles._form', ['mode' => 'create'])
</form>
@endsection
