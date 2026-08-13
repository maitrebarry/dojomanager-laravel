@extends('layouts.admin')

@section('title', __('messages.disciples.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.disciples.index') }}">{{ __('messages.disciples.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.disciples.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.disciples.store') }}" method="POST" enctype="multipart/form-data">
    @include('admin.disciples._form', ['mode' => 'create'])
</form>
@endsection
