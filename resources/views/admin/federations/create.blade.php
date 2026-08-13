@extends('layouts.admin')

@section('title', __('messages.federations.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.federations.index') }}">{{ __('messages.federations.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.federations.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.federations.store') }}" method="POST">
    @include('admin.federations._form', ['mode' => 'create'])
</form>
@endsection
