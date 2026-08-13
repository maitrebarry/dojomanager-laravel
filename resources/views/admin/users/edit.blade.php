@extends('layouts.admin')

@section('title', __('messages.users.edit'))

@section('actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> {{ __('messages.back') }}
    </a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ __('messages.users.edit_named', ['name' => $user->name]) }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('admin.users._form', ['user' => $user])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
