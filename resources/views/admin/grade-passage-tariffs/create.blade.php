@extends('layouts.admin')

@section('title', __('messages.grade_passage_tariffs.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.grade-passage-tariffs.index') }}">{{ __('messages.grade_passage_tariffs.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.grade_passage_tariffs.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.grade-passage-tariffs.store') }}" method="POST">
    @include('admin.grade-passage-tariffs._form', ['mode' => 'create'])
</form>
@endsection
