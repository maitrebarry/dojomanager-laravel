@extends('layouts.admin')

@section('title', __('messages.grades.edit'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.grades.index') }}">{{ __('messages.grades.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $grade->nom_grade }}</li>
@endsection

@section('content')
<form action="{{ route('admin.grades.update', $grade) }}" method="POST">
    @include('admin.grades._form', ['mode' => 'edit'])
</form>
@endsection
