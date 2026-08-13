@extends('layouts.admin')

@section('title', __('messages.ceintures_noires.add'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.ceintures-noires.index') }}">{{ __('messages.ceintures_noires.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.ceintures_noires.add') }}</li>
@endsection

@section('content')
<form action="{{ route('admin.ceintures-noires.store') }}" method="POST" enctype="multipart/form-data">
    @include('admin.ceintures-noires._form', ['mode' => 'create'])
</form>
@endsection
