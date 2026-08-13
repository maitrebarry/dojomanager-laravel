@extends('layouts.admin')

@section('title', __('messages.users.title'))

@section('actions')
    @if(Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('UTILISATEUR_CREATE'))
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ __('messages.users.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('messages.users.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="fas fa-search"></i> {{ __('messages.search') }}
                </button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.name') }}</th>
                    <th>{{ __('messages.email') }}</th>
                    <th>{{ __('messages.phone') }}</th>
                    <th>{{ __('messages.role') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th style="width: 120px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?? '-' }}</td>
                        <td>{{ \App\Shared\Enums\UserRole::tryFrom($user->role)?->label() ?? $user->role }}</td>
                        <td>{{ \App\Shared\Enums\UserStatus::tryFrom($user->status)?->label() ?? $user->status }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary" title="{{ __('messages.view') }}">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('UTILISATEUR_UPDATE'))
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary" title="{{ __('messages.edit') }}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if(Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('UTILISATEUR_DELETE'))
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm(@json(__('messages.users.delete_confirm')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="{{ __('messages.delete') }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">{{ __('messages.users.none_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="card-footer bg-white">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
