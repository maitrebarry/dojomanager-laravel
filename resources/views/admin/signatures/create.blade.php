@extends('layouts.admin')

@section('title', __('messages.signatures.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.signatures.index') }}">{{ __('messages.signatures.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.signatures.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.signatures.store') }}" method="POST" enctype="multipart/form-data">
    @include('admin.signatures._form', ['mode' => 'create'])
</form>
@endsection
