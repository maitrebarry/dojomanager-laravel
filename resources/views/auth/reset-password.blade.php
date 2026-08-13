<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - Licence Régionale TKD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(21, 38, 69, 0.94), rgba(64, 96, 160, 0.82));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 24px;
        }

        .auth-card {
            width: 100%;
            max-width: 540px;
            border: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.26);
        }

        .auth-header {
            background: #152645;
            color: #fff;
            padding: 28px 32px;
        }

        .auth-body {
            padding: 32px;
            background: #fff;
        }

        .form-control,
        .btn {
            border-radius: 8px;
            min-height: 46px;
        }
    </style>
</head>
<body>
    <main class="card auth-card">
        <div class="auth-header">
            <h1 class="h4 mb-1"><i class="fas fa-lock"></i> Nouveau mot de passe</h1>
            <p class="mb-0 opacity-75">Choisissez un nouveau mot de passe pour votre compte.</p>
        </div>
        <div class="auth-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $email) }}" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Nouveau mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save"></i> Réinitialiser
                </button>
            </form>
        </div>
    </main>
</body>
</html>
