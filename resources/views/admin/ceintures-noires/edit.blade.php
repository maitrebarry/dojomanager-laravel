@extends('layouts.admin')

@section('title', __('messages.ceintures_noires.edit'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.ceintures-noires.index') }}">{{ __('messages.ceintures_noires.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $ceintureNoire->full_name }}</li>
@endsection

@section('content')
<form action="{{ route('admin.ceintures-noires.update', $ceintureNoire) }}" method="POST" enctype="multipart/form-data">
    @include('admin.ceintures-noires._form', ['mode' => 'edit'])
</form>
@endsection
