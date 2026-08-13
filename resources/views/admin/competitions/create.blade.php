@extends('layouts.admin')

@section('title', __('messages.competitions.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.competitions.index') }}">{{ __('messages.competitions.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.competitions.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.competitions.store') }}" method="POST">
    @include('admin.competitions._form', ['mode' => 'create'])
</form>
@endsection
