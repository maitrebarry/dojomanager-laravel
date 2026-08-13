@extends('layouts.admin')

@section('title', __('messages.grades.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.grades.index') }}">{{ __('messages.grades.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.grades.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.grades.store') }}" method="POST">
    @include('admin.grades._form', ['mode' => 'create'])
</form>
@endsection
