@extends('layouts.admin')

@section('title', __('messages.grade_passages.edit'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.grade-passages.index') }}">{{ __('messages.grade_passages.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.grade_passages.edit') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.grade-passages.update', $session) }}" method="POST">
    @include('admin.grade-passages._form', ['mode' => 'edit'])
</form>
@endsection
