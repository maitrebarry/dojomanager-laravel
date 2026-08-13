@extends('layouts.admin')

@section('title', 'Détails utilisateur')

@section('actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Modifier
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-list"></i> Liste
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informations utilisateur</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th class="w-25">Nom:</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th>Rôle:</th>
                        <td><span class="badge bg-info">{{ \App\Shared\Enums\UserRole::tryFrom($user->role)?->label() ?? $user->role }}</span></td>
                    </tr>
                    <tr>
                        <th>Statut:</th>
                        <td><span class="badge bg-secondary">{{ \App\Shared\Enums\UserStatus::tryFrom($user->status)?->label() ?? $user->status }}</span></td>
                    </tr>
                    <tr>
                        <th>Créé le:</th>
                        <td>{{ optional($user->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Modifié le:</th>
                        <td>{{ optional($user->updated_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-edit"></i> Modifier cet utilisateur
                </a>
                <button type="button" class="btn btn-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key"></i> Changer le mot de passe
                </button>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-trash"></i> Supprimer cet utilisateur
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changer le mot de passe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Confirmer</label>
                        <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Changer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
