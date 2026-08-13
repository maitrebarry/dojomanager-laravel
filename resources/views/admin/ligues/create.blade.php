@extends('layouts.admin')

@section('title', __('messages.ligues.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.ligues.index') }}">{{ __('messages.ligues.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.ligues.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.ligues.store') }}" method="POST">
    @include('admin.ligues._form', ['mode' => 'create'])
</form>
@endsection
