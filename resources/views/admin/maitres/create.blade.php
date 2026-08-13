@extends('layouts.admin')

@section('title', __('messages.maitres.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.maitres.index') }}">{{ __('messages.maitres.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.maitres.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.maitres.store') }}" method="POST">
    @include('admin.maitres._form', ['mode' => 'create'])
</form>
@endsection
